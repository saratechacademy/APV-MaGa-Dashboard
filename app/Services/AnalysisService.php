<?php

namespace App\Services;

use App\Models\SensorReading;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Compares one quantity (light, air temperature, soil moisture…) between
 * several series: the zones of a site, or the same zone on several sites.
 *
 * Readings are averaged per hour or per day. A series made of several
 * sensors (two reference soil probes) is the mean of its sensors, each
 * weighted equally. Differences are computed only on the hours or days
 * where both series have data, so an outage on one side does not bias them.
 * The first series is the baseline the others are compared with.
 */
class AnalysisService
{
    /**
     * @param  array<int, array{label: string, parameters: Collection}>  $series
     * @param  string  $step  'hour' or 'day'
     */
    public function compare(string $quantity, array $series, Carbon $from, Carbon $to, string $step): array
    {
        $spec   = config("analysis.quantities.{$quantity}");
        $step   = ($spec['only_daily'] ?? false) ? 'day' : $step;
        $bucket = $step === 'day' ? 86400 : 3600;
        $daily  = $step === 'day' ? ($spec['daily'] ?? null) : null;
        $factor = $daily['factor'] ?? 1;
        $rawUnit = $series[0]['parameters']->first()?->unit ?? '';
        $unit   = $daily['unit'] ?? $rawUnit;
        $title  = $daily['title'] ?? $spec['title'];

        $expected = max(1, (int) ceil(($to->getTimestamp() - $from->getTimestamp()) / $bucket));
        $built = [];

        foreach ($series as $s) {
            $ids = $s['parameters']->pluck('id')->all();
            $values = $this->bucketed($ids, $from, $to, $bucket, $spec['aggregate']);
            $values = array_map(fn ($v) => $v * $factor, $values);
            $hourly = ($spec['diurnal'] ?? false) ? $this->bucketed($ids, $from, $to, 3600, $spec['aggregate']) : [];

            $built[] = [
                'label'     => $s['label'],
                'values'    => $values,
                'stats'     => $this->stats($values, $expected),
                'diurnal'   => $this->diurnal($hourly),
                'sensors'   => count($ids),
            ];
        }

        $baseline = $built[0];
        foreach ($built as $i => &$s) {
            $s['comparison'] = $i === 0 ? null : $this->versus($baseline, $s, $spec);
        }
        unset($s);

        $keys = collect($built)->flatMap(fn ($s) => array_keys($s['values']))->unique()->sort()->values();

        return [
            'quantity' => $quantity,
            'title'    => $title,
            'unit'     => $unit,
            'step'     => $step,
            'about'    => $spec['about'],
            'from'     => $from->toIso8601String(),
            'to'       => $to->toIso8601String(),
            'expected' => $expected,
            'labels'   => $keys->map(fn ($t) => $t * 1000)->all(),
            'series'   => array_map(fn ($s) => [
                'label'      => $s['label'],
                'sensors'    => $s['sensors'],
                'points'     => $keys->map(fn ($t) => isset($s['values'][$t]) ? round($s['values'][$t], 3) : null)->all(),
                'stats'      => $s['stats'],
                'diurnal'    => $s['diurnal'],
                'comparison' => $s['comparison'],
                // Gap to the baseline at each instant both have.
                'gaps'       => $s === $built[0] ? null : $keys->map(fn ($t) => isset($s['values'][$t], $built[0]['values'][$t])
                    ? round($s['values'][$t] - $built[0]['values'][$t], 3) : null)->all(),
            ], $built),
            'findings' => $this->findings($built, $quantity, $unit, $rawUnit, $step, $spec),
        ];
    }

    /**
     * Per-bucket value of a set of sensors: each sensor's mean (or sum) in
     * the bucket, then the mean over the sensors that reported in it.
     *
     * @return array<int, float>  bucket start (unix seconds) => value
     */
    private function bucketed(array $ids, Carbon $from, Carbon $to, int $bucket, string $aggregate): array
    {
        if (!$ids) {
            return [];
        }

        $perSensor = [];
        $readings = SensorReading::toBase()
            ->whereIn('site_parameter_id', $ids)
            ->where('read_at', '>=', $from)
            ->where('read_at', '<', $to)
            ->whereNotNull('value')
            ->select('site_parameter_id', 'value', 'read_at')
            ->cursor();

        foreach ($readings as $r) {
            $key = intdiv(Carbon::parse($r->read_at)->getTimestamp(), $bucket) * $bucket;
            $perSensor[$key][$r->site_parameter_id][] = (float) $r->value;
        }

        $out = [];
        foreach ($perSensor as $key => $sensors) {
            $each = array_map(fn ($v) => $aggregate === 'sum' ? array_sum($v) : array_sum($v) / count($v), $sensors);
            $out[$key] = array_sum($each) / count($each);
        }
        ksort($out);

        return $out;
    }

    /** @param array<int, float> $values */
    private function stats(array $values, int $expected): array
    {
        $n = count($values);
        if ($n === 0) {
            return ['n' => 0, 'coverage' => 0, 'mean' => null, 'min' => null, 'max' => null, 'std' => null];
        }

        $mean = array_sum($values) / $n;
        $var  = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $values)) / max(1, $n - 1);

        return [
            'n'        => $n,
            'coverage' => round(min(100, $n / $expected * 100), 1),
            'mean'     => round($mean, 3),
            'min'      => round(min($values), 3),
            'max'      => round(max($values), 3),
            'std'      => $n > 1 ? round(sqrt($var), 3) : null,
        ];
    }

    /**
     * Mean per hour of the day (UTC), 0 to 23.
     *
     * @param  array<int, float>  $hourly
     * @return array<int, ?float>
     */
    private function diurnal(array $hourly): array
    {
        if (!$hourly) {
            return [];
        }

        $byHour = [];
        foreach ($hourly as $t => $v) {
            $byHour[(int) gmdate('G', $t)][] = $v;
        }

        return array_map(fn ($h) => isset($byHour[$h]) ? round(array_sum($byHour[$h]) / count($byHour[$h]), 3) : null, range(0, 23));
    }

    /** One series against the baseline, on the buckets both have. */
    private function versus(array $base, array $other, array $spec): ?array
    {
        $common = array_intersect_key($base['values'], $other['values']);
        if (!$common) {
            return null;
        }

        $a = array_sum($common) / count($common);
        $b = array_sum(array_intersect_key($other['values'], $common)) / count($common);
        $relative = ($spec['relative'] ?? false) && abs($a) > 1e-9;

        $peak = null;
        foreach ($other['diurnal'] as $h => $v) {
            $ref = $base['diurnal'][$h] ?? null;
            if ($v !== null && $ref !== null && ($peak === null || abs($v - $ref) > abs($peak['difference']))) {
                $peak = ['hour' => $h, 'difference' => round($v - $ref, 3)];
            }
        }

        return [
            'common'      => count($common),
            'baseline'    => round($a, 3),
            'mean'        => round($b, 3),
            'difference'  => round($b - $a, 3),
            'percent'     => $relative ? round(($b - $a) / $a * 100, 1) : null,
            'ratio'       => $relative ? round($b / $a * 100, 1) : null,
            'peak'        => $peak,
        ];
    }

    /**
     * Plain-language statements, built only from the computed numbers so
     * that every figure in them can be checked in the table.
     *
     * @return string[]
     */
    private function findings(array $built, string $quantity, string $unit, string $rawUnit, string $step, array $spec): array
    {
        $unitText = $unit ? " {$unit}" : '';
        $buckets  = $step === 'day' ? 'days' : 'hours';
        $base     = $built[0];
        $out      = [];

        if ($base['stats']['n'] === 0) {
            return ["No data for {$base['label']} in this period: there is nothing to compare against."];
        }

        foreach (array_slice($built, 1) as $s) {
            $c = $s['comparison'];
            if (!$c) {
                $out[] = "{$s['label']}: no {$buckets} with data at the same time as {$base['label']}, so no comparison is possible.";
                continue;
            }

            $sign = $c['difference'] >= 0 ? '+' : '−';
            $abs  = $this->num(abs($c['difference']));

            if ($quantity === 'par' && $c['ratio'] !== null) {
                $out[] = "{$s['label']} receives {$this->num($c['ratio'])} % of the light measured at {$base['label']} "
                    . "({$this->num($c['mean'])} vs {$this->num($c['baseline'])}{$unitText} on average over {$c['common']} common {$buckets}).";
            } else {
                $pct = $c['percent'] !== null ? " ({$sign}{$this->num(abs($c['percent']))} %)" : '';
                $out[] = "{$s['label']} averages {$this->num($c['mean'])}{$unitText} against {$this->num($c['baseline'])}{$unitText} at {$base['label']}: "
                    . "{$sign}{$abs}{$unitText}{$pct}, over {$c['common']} common {$buckets}.";
            }

            if ($c['peak'] && abs($c['peak']['difference']) > 0) {
                $peakSign = $c['peak']['difference'] >= 0 ? '+' : '−';
                // The hour-of-day profile is in the sensors' own unit, also on a daily step.
                $peakUnit = $rawUnit ? " {$rawUnit}" : '';
                $out[] = "The gap is largest around " . sprintf('%02d:00', $c['peak']['hour']) . " UTC ({$peakSign}{$this->num(abs($c['peak']['difference']))}{$peakUnit} on average at that hour).";
            }
        }

        $low = collect($built)->filter(fn ($s) => $s['stats']['n'] > 0 && $s['stats']['coverage'] < 70);
        foreach ($low as $s) {
            $out[] = "Caution: data covers only {$this->num($s['stats']['coverage'])} % of the period for {$s['label']}; results may not be representative.";
        }

        return $out;
    }

    private function num(float $v): string
    {
        $abs = abs($v);
        $decimals = $abs >= 100 ? 0 : ($abs >= 10 ? 1 : 2);

        $text = number_format($v, $decimals, '.', ' ');

        return $decimals > 0 ? rtrim(rtrim($text, '0'), '.') : $text;
    }
}

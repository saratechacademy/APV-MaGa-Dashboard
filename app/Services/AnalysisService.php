<?php

namespace App\Services;

use App\Models\ManualReading;
use App\Models\SensorReading;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Compares series of measurements: the zones of a site, the same zone on
 * several sites, one place over two periods, or any parameters together.
 *
 * Readings are averaged per hour or per day. A series made of several
 * sensors (two reference soil probes) is the mean of its sensors, each
 * weighted equally. Differences and correlations are computed only on the
 * hours or days where both series have data, so an outage on one side does
 * not bias them. The first series is the baseline the others are compared
 * with. When series cover different periods, they are aligned on the day
 * (or hour) since the start of their own period.
 */
class AnalysisService
{
    /**
     * @param  array<int, array{label: string, parameters: Collection, quantity: string, zone?: ?string, from?: Carbon, to?: Carbon}>  $series
     * @param  string  $step  'hour' or 'day'
     * @param  bool  $correlate  also relate the first two series (free comparison)
     */
    public function compare(array $series, Carbon $from, Carbon $to, string $step, bool $correlate = false): array
    {
        $quantities = array_values(array_unique(array_column($series, 'quantity')));
        $specs      = array_combine($quantities, array_map(fn ($q) => config("analysis.quantities.{$q}") ?? config("analysis.fallback.{$q}"), $quantities));
        $step       = collect($specs)->contains(fn ($s) => $s['only_daily'] ?? false) ? 'day' : $step;
        $bucket     = $step === 'day' ? 86400 : 3600;
        $relative   = collect($series)->contains(fn ($s) => isset($s['from']));
        $single     = count($quantities) === 1 ? $quantities[0] : null;

        $built = [];
        foreach ($series as $s) {
            $spec   = $specs[$s['quantity']];
            $daily  = $step === 'day' ? ($spec['daily'] ?? null) : null;
            $sFrom  = $s['from'] ?? $from;
            $sTo    = $s['to'] ?? $to;
            $manual = ($spec['source'] ?? 'sensor') === 'manual';
            $ids    = $s['parameters']->pluck('id')->all();

            $values = $this->bucketed($ids, $sFrom, $sTo, $bucket, $spec['aggregate'], $manual, $s['zone'] ?? null);
            $values = array_map(fn ($v) => $v * ($daily['factor'] ?? 1), $values);
            $hourly = ($spec['diurnal'] ?? false) && !$manual ? $this->bucketed($ids, $sFrom, $sTo, 3600, $spec['aggregate'], false, null) : [];

            if ($relative) {
                // Keyed by position in the series' own period: day 0, 1, 2…
                $values = collect($values)->mapWithKeys(fn ($v, $t) => [intdiv($t - $sFrom->getTimestamp(), $bucket) => $v])->all();
            }

            $rawUnit = $s['parameters']->first()?->unit ?? '';
            $built[] = [
                'label'    => $s['label'],
                'quantity' => $s['quantity'],
                'title'    => $daily['title'] ?? $spec['title'],
                'unit'     => $daily['unit'] ?? $rawUnit,
                'rawUnit'  => $rawUnit,
                'values'   => $values,
                'stats'    => $this->stats($values, max(1, (int) ceil(($sTo->getTimestamp() - $sFrom->getTimestamp()) / $bucket))),
                'diurnal'  => $this->diurnal($hourly),
                'sensors'  => count($ids),
                'period'   => [$sFrom->toIso8601String(), $sTo->toIso8601String()],
            ];
        }

        // Differences only make sense between series in the same unit.
        $comparable = count(array_unique(array_column($built, 'unit'))) === 1;
        foreach ($built as $i => &$s) {
            $s['comparison'] = $i > 0 && $comparable ? $this->versus($built[0], $s, $specs[$s['quantity']]) : null;
        }
        unset($s);

        $keys = collect($built)->flatMap(fn ($s) => array_keys($s['values']))->unique()->sort()->values();
        $correlation = $correlate && count($built) >= 2 ? $this->correlation($built[0], $built[1]) : null;

        return [
            'quantity'    => $single,
            'title'       => $single ? $built[0]['title'] : implode(' · ', array_unique(array_column($built, 'title'))),
            'unit'        => $comparable ? $built[0]['unit'] : null,
            'units'       => array_values(array_unique(array_column($built, 'unit'))),
            'step'        => $step,
            'relative'    => $relative,
            'comparable'  => $comparable,
            'about'       => implode(' ', array_map(fn ($q) => $specs[$q]['about'], $quantities)),
            'from'        => $from->toIso8601String(),
            'to'          => $to->toIso8601String(),
            'expected'    => $built[0]['stats']['expected'],
            'labels'      => $relative ? $keys->all() : $keys->map(fn ($t) => $t * 1000)->all(),
            'series'      => array_map(fn ($s) => [
                'label'      => $s['label'],
                'title'      => $s['title'],
                'unit'       => $s['unit'],
                'sensors'    => $s['sensors'],
                'period'     => $s['period'],
                'points'     => $keys->map(fn ($t) => isset($s['values'][$t]) ? round($s['values'][$t], 3) : null)->all(),
                'stats'      => $s['stats'],
                'diurnal'    => $s['diurnal'],
                'comparison' => $s['comparison'],
                // Gap to the baseline at each instant both have.
                'gaps'       => $s['comparison'] === null ? null : $keys->map(fn ($t) => isset($s['values'][$t], $built[0]['values'][$t])
                    ? round($s['values'][$t] - $built[0]['values'][$t], 3) : null)->all(),
            ], $built),
            'correlation' => $correlation,
            'findings'    => $this->findings($built, $single, $step, $correlation, $relative),
        ];
    }

    /**
     * Per-bucket value of a set of parameters: each one's mean (or sum) in
     * the bucket, then the mean over the parameters that have data in it.
     * Manual entries can be restricted to the plot they were taken on.
     *
     * @return array<int, float>  bucket start (unix seconds) => value
     */
    private function bucketed(array $ids, Carbon $from, Carbon $to, int $bucket, string $aggregate, bool $manual, ?string $zone): array
    {
        if (!$ids) {
            return [];
        }

        $column = $manual ? 'reading_date' : 'read_at';
        $readings = ($manual ? ManualReading::toBase() : SensorReading::toBase())
            ->whereIn('site_parameter_id', $ids)
            ->where($column, '>=', $from)
            ->where($column, '<', $to)
            ->whereNotNull('value')
            ->when($manual && $zone, fn ($q) => $q->where('zone', $zone))
            ->select('site_parameter_id', 'value', $column . ' as at')
            ->cursor();

        $perParameter = [];
        foreach ($readings as $r) {
            if (!is_numeric($r->value)) {
                continue;
            }
            $key = intdiv(Carbon::parse($r->at)->getTimestamp(), $bucket) * $bucket;
            $perParameter[$key][$r->site_parameter_id][] = (float) $r->value;
        }

        $out = [];
        foreach ($perParameter as $key => $params) {
            $each = array_map(fn ($v) => $aggregate === 'sum' ? array_sum($v) : array_sum($v) / count($v), $params);
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
            return ['n' => 0, 'expected' => $expected, 'coverage' => 0, 'mean' => null, 'min' => null, 'max' => null, 'std' => null];
        }

        $mean = array_sum($values) / $n;
        $var  = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $values)) / max(1, $n - 1);

        return [
            'n'        => $n,
            'expected' => $expected,
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
            'common'     => count($common),
            'baseline'   => round($a, 3),
            'mean'       => round($b, 3),
            'difference' => round($b - $a, 3),
            'percent'    => $relative ? round(($b - $a) / $a * 100, 1) : null,
            'ratio'      => $relative ? round($b / $a * 100, 1) : null,
            'peak'       => $peak,
        ];
    }

    /**
     * Pearson correlation and least-squares line between two series, on the
     * buckets both have; null below 3 common points or for a constant series.
     */
    private function correlation(array $x, array $y): ?array
    {
        $common = array_keys(array_intersect_key($x['values'], $y['values']));
        $n = count($common);
        if ($n < 3) {
            return null;
        }

        $xs = array_map(fn ($k) => $x['values'][$k], $common);
        $ys = array_map(fn ($k) => $y['values'][$k], $common);
        $mx = array_sum($xs) / $n;
        $my = array_sum($ys) / $n;

        $sxy = $sxx = $syy = 0.0;
        foreach ($xs as $i => $xv) {
            $sxy += ($xv - $mx) * ($ys[$i] - $my);
            $sxx += ($xv - $mx) ** 2;
            $syy += ($ys[$i] - $my) ** 2;
        }
        if ($sxx < 1e-12 || $syy < 1e-12) {
            return null;
        }

        $slope = $sxy / $sxx;
        $every = max(1, (int) ceil($n / 1500));   // at most ~1 500 points in the scatter plot
        $points = [];
        for ($i = 0; $i < $n; $i += $every) {
            $points[] = [round($xs[$i], 3), round($ys[$i], 3)];
        }

        return [
            'x'         => $x['label'],
            'y'         => $y['label'],
            'xUnit'     => $x['unit'],
            'yUnit'     => $y['unit'],
            'n'         => $n,
            'r'         => round($sxy / sqrt($sxx * $syy), 3),
            'slope'     => round($slope, 4),
            'intercept' => round($my - $slope * $mx, 4),
            'points'    => $points,
        ];
    }

    /**
     * Plain-language statements, built only from the computed numbers so
     * that every figure in them can be checked in the table.
     *
     * @return string[]
     */
    private function findings(array $built, ?string $quantity, string $step, ?array $correlation, bool $relative): array
    {
        $buckets = $step === 'day' ? 'days' : 'hours';
        $base    = $built[0];
        $out     = [];

        if ($base['stats']['n'] === 0) {
            return ["No data for {$base['label']} in this period: there is nothing to compare against."];
        }

        foreach (array_slice($built, 1) as $s) {
            if ($s['stats']['n'] === 0) {
                $out[] = "No data for {$s['label']} in this period.";
                continue;
            }

            $c = $s['comparison'];
            if (!$c) {
                if ($s['unit'] === $base['unit']) {
                    $out[] = "{$s['label']}: no {$buckets} with data at the same time as {$base['label']}, so no comparison is possible.";
                }
                continue;
            }

            $unitText = $s['unit'] ? " {$s['unit']}" : '';
            $sign = $c['difference'] >= 0 ? '+' : '−';
            $over = $relative ? "over {$c['common']} matching {$buckets}" : "over {$c['common']} common {$buckets}";

            if ($quantity === 'par' && $c['ratio'] !== null) {
                $out[] = "{$s['label']} receives {$this->num($c['ratio'])} % of the light measured at {$base['label']} "
                    . "({$this->num($c['mean'])} vs {$this->num($c['baseline'])}{$unitText} on average {$over}).";
            } else {
                $pct = $c['percent'] !== null ? " ({$sign}{$this->num(abs($c['percent']))} %)" : '';
                $out[] = "{$s['label']} averages {$this->num($c['mean'])}{$unitText} against {$this->num($c['baseline'])}{$unitText} at {$base['label']}: "
                    . "{$sign}{$this->num(abs($c['difference']))}{$unitText}{$pct}, {$over}.";
            }

            if ($c['peak'] && abs($c['peak']['difference']) > 0) {
                // The hour-of-day profile is in the sensors' own unit, also on a daily step.
                $peakSign = $c['peak']['difference'] >= 0 ? '+' : '−';
                $peakUnit = $s['rawUnit'] ? " {$s['rawUnit']}" : '';
                $out[] = 'The gap is largest around ' . sprintf('%02d:00', $c['peak']['hour']) . " UTC ({$peakSign}{$this->num(abs($c['peak']['difference']))}{$peakUnit} on average at that hour).";
            }
        }

        if ($correlation) {
            $r = $correlation['r'];
            $strength = match (true) {
                abs($r) >= 0.7 => 'strong',
                abs($r) >= 0.4 => 'moderate',
                abs($r) >= 0.2 => 'weak',
                default        => 'no clear',
            };
            $direction = $strength === 'no clear' ? '' : ($r > 0 ? ' positive' : ' negative');
            $out[] = "{$correlation['x']} and {$correlation['y']}: {$strength}{$direction} relationship (r = {$this->num($r)}, {$correlation['n']} common {$buckets}). "
                . 'A correlation shows that two series move together, not that one causes the other.';
        }

        foreach ($built as $s) {
            if ($s['stats']['n'] > 0 && $s['stats']['coverage'] < 70) {
                $out[] = "Caution: data covers only {$this->num($s['stats']['coverage'])} % of the period for {$s['label']}; results may not be representative.";
            }
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

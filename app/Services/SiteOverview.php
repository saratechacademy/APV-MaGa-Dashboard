<?php

namespace App\Services;

use App\Models\SensorReading;
use App\Models\Site;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Everything the "All Sites" overview shows, derived from parameter *metrics*
 * (config/overview.php) rather than from parameter names or slugs, so it is
 * correct for any site whatever created its parameters.
 *
 * Expects sites loaded with activeCategories.activeParameters.latestReading.
 */
class SiteOverview
{
    /** Bucket sizes (seconds) a time window is cut into, aiming at ~100 points. */
    private const BUCKETS = [300, 600, 900, 1800, 3600, 7200, 10800, 21600, 43200, 86400];

    /**
     * Up to four KPI tiles for a site card: the mean of the latest readings of
     * each metric, flagged stale when even the newest of them is older than
     * its category's offline threshold. Fresh metrics come first; stale ones
     * only fill the remaining tiles, so a sensor silent for months never
     * hides a live one.
     *
     * @return array<int, array{metric: string, label: string, unit: string, value: float, at: Carbon, stale: bool}>
     */
    public function tiles(Site $site): array
    {
        $latest = $this->latestByMetric($site);

        $tiles = [];
        foreach (config('overview.tiles') as $metric) {
            if (isset($latest[$metric])) {
                $tiles[] = [
                    'metric' => $metric,
                    'label'  => config("overview.metrics.{$metric}.label"),
                    'unit'   => config("overview.metrics.{$metric}.unit"),
                    'value'  => array_sum($latest[$metric]['values']) / count($latest[$metric]['values']),
                    'at'     => $latest[$metric]['at'],
                    'stale'  => $latest[$metric]['stale'],
                ];
            }
        }

        // Stable: keeps the configured order within fresh and within stale.
        usort($tiles, fn ($a, $b) => $a['stale'] <=> $b['stale']);

        return array_slice($tiles, 0, 4);
    }

    /**
     * The metric a site's sparkline shows: the first candidate with fresh
     * data, else the first with any data, else none.
     */
    public function sparklineMetric(Site $site): ?string
    {
        $latest     = $this->latestByMetric($site);
        $candidates = collect(config('overview.sparkline'))->filter(fn ($m) => isset($latest[$m]));

        return $candidates->first(fn ($m) => !$latest[$m]['stale']) ?? $candidates->first();
    }

    /** @return array<string, array{values: float[], at: Carbon, stale: bool}> */
    private function latestByMetric(Site $site): array
    {
        $latest = [];

        foreach ($site->activeCategories as $category) {
            foreach ($category->activeParameters as $parameter) {
                $reading = $parameter->latestReading;
                if (!$parameter->metric || !$reading || $reading->value === null) {
                    continue;
                }

                $fresh = $reading->read_at->gte(now()->subMinutes($category->offlineThresholdMinutes()));

                $entry = &$latest[$parameter->metric];
                $entry[$fresh ? 'fresh' : 'old'][] = $reading->value;
                if (!isset($entry['at']) || $reading->read_at->gt($entry['at'])) {
                    $entry['at'] = $reading->read_at;
                }
                unset($entry);
            }
        }

        // The value shown is the mean of the sensors still reporting; one that
        // went silent a month ago must not weigh on today's figure. Only when
        // none is reporting are the last known values averaged, flagged stale.
        return array_map(fn (array $entry) => [
            'values' => $entry['fresh'] ?? $entry['old'],
            'at'     => $entry['at'],
            'stale'  => empty($entry['fresh']),
        ], $latest);
    }

    /**
     * Whether data is actually arriving, for the top-bar badge:
     * 'live' when at least one sensor reading is within its category's
     * offline threshold, 'stale' when the sites have readings but none is
     * that recent, 'none' when nothing was ever received.
     *
     * @param  iterable<Site>  $sites
     * @return array{state: string, at: ?Carbon, live: int, total: int}
     */
    public function liveStatus(iterable $sites): array
    {
        $live = $total = 0;
        $newest = null;

        foreach ($sites as $site) {
            $total++;
            $siteLive = false;

            foreach ($site->activeCategories as $category) {
                $freshSince = now()->subMinutes($category->offlineThresholdMinutes());

                foreach ($category->activeParameters as $parameter) {
                    $at = $parameter->latestReading?->read_at;
                    if (!$at) {
                        continue;
                    }
                    $newest   = $newest && $newest->gt($at) ? $newest : $at;
                    $siteLive = $siteLive || $at->gte($freshSince);
                }
            }

            $live += (int) $siteLive;
        }

        return [
            'state' => $live ? 'live' : ($newest ? 'stale' : 'none'),
            'at'    => $newest,
            'live'  => $live,
            'total' => $total,
        ];
    }

    /**
     * 'critical' / 'warning' when a fresh reading of any parameter crosses its
     * threshold, 'normal' otherwise. Stale readings don't raise alerts: a
     * sensor silent for a month says nothing about now. A site with no fresh
     * reading at all is 'offline' (or 'nodata' if it never sent anything).
     */
    public function status(Site $site): string
    {
        $status = 'normal';
        $anyReading = $anyFresh = false;

        foreach ($site->activeCategories as $category) {
            $freshSince = now()->subMinutes($category->offlineThresholdMinutes());

            foreach ($category->activeParameters as $parameter) {
                $reading = $parameter->latestReading;
                if (!$reading) {
                    continue;
                }
                $anyReading = true;
                if ($reading->read_at->lt($freshSince)) {
                    continue;
                }
                $anyFresh = true;
                if ($parameter->isCritical($reading->value)) {
                    return 'critical';
                }
                if ($parameter->isWarn($reading->value)) {
                    $status = 'warning';
                }
            }
        }

        // "Normal" is a statement about current readings: a site sending
        // nothing can't be called normal.
        if (!$anyFresh) {
            return $anyReading ? 'offline' : 'nodata';
        }

        return $status;
    }

    /**
     * The cross-site charts to draw: the first four configured metrics that at
     * least one of these sites has.
     *
     * @return string[]
     */
    public function chartMetrics(Collection $sites): array
    {
        $has = $sites->reduce(fn ($all, Site $site) => $all + $this->metricsOf($site), []);

        return collect(config('overview.charts'))->filter(fn ($m) => isset($has[$m]))->take(4)->values()->all();
    }

    /**
     * Per-metric, per-site series over [$from, $to] on one shared time axis:
     * readings are averaged into fixed buckets, because sites (and sources)
     * don't report at the same instants.
     *
     * @param  string[]  $metrics
     * @return array{labels: string[], series: array<string, array<int, array{site_id: int, name: string, data: array<int, float|null>}>>}
     */
    public function series(Collection $sites, array $metrics, Carbon $from, Carbon $to): array
    {
        $fromTs = $from->getTimestamp();
        $toTs   = $to->getTimestamp();
        $span   = max(1, $toTs - $fromTs);
        $bucket = collect(self::BUCKETS)->first(fn ($b) => $span / $b <= 100, 86400);

        $first  = intdiv($fromTs, $bucket) * $bucket;
        $count  = intdiv($toTs - $first, $bucket) + 1;
        $format = $span > 48 * 3600 ? 'd/m H:i' : 'H:i';
        $labels = [];
        for ($i = 0; $i < $count; $i++) {
            $labels[] = Carbon::createFromTimestamp($first + $i * $bucket, config('app.timezone'))->format($format);
        }

        // parameter id => [metric, site id]
        $owners = [];
        foreach ($sites as $site) {
            foreach ($site->activeCategories as $category) {
                foreach ($category->activeParameters as $parameter) {
                    if (in_array($parameter->metric, $metrics, true)) {
                        $owners[$parameter->id] = [$parameter->metric, $site->id];
                    }
                }
            }
        }

        $sums = [];
        SensorReading::query()->toBase()
            ->select('site_parameter_id', 'value', 'read_at')
            ->whereIn('site_parameter_id', array_keys($owners))
            ->whereBetween('read_at', [$from, $to])
            ->whereNotNull('value')
            ->orderBy('id')
            ->each(function ($row) use (&$sums, $owners, $first, $bucket) {
                [$metric, $siteId] = $owners[$row->site_parameter_id];
                $index = intdiv(Carbon::parse($row->read_at, config('app.timezone'))->getTimestamp() - $first, $bucket);
                $cell  = &$sums[$metric][$siteId][$row->site_parameter_id][$index];
                $cell  = [($cell[0] ?? 0) + $row->value, ($cell[1] ?? 0) + 1];
                unset($cell);
            }, 2000);

        // How long a sensor's last value stands in for it: an hour, in buckets.
        $carry = max(1, intdiv(3600, $bucket));

        $series = [];
        foreach ($metrics as $metric) {
            $series[$metric] = [];
            foreach ($sites as $site) {
                if (empty($sums[$metric][$site->id])) {
                    continue;
                }
                $data = $this->siteAverage($sums[$metric][$site->id], $count, $carry);
                $series[$metric][] = ['site_id' => $site->id, 'name' => $site->name, 'data' => $data];
            }
        }

        return ['labels' => $labels, 'series' => $series];
    }

    /**
     * Mean across a site's sensors, bucket by bucket. Sensors of one site
     * report at different instants (six soil probes, each every 20 minutes),
     * so a plain per-bucket mean would average a different subset of sensors
     * each time and zigzag between them. Each sensor's last value is therefore
     * carried forward for up to $carry buckets before averaging.
     *
     * @param  array<int, array<int, array{0: float, 1: int}>>  $perSensor  sensor => bucket => [sum, count]
     * @return array<int, float|null>
     */
    private function siteAverage(array $perSensor, int $count, int $carry): array
    {
        $sum = array_fill(0, $count, 0.0);
        $n   = array_fill(0, $count, 0);

        foreach ($perSensor as $buckets) {
            $last = null;
            $age  = 0;
            for ($i = 0; $i < $count; $i++) {
                if (isset($buckets[$i])) {
                    $last = $buckets[$i][0] / $buckets[$i][1];
                    $age  = 0;
                } elseif ($last !== null && ++$age > $carry) {
                    $last = null;
                }
                if ($last !== null) {
                    $sum[$i] += $last;
                    $n[$i]++;
                }
            }
        }

        return array_map(fn ($s, $c) => $c ? round($s / $c, 2) : null, $sum, $n);
    }

    /** @return array<string, true> */
    private function metricsOf(Site $site): array
    {
        $metrics = [];
        foreach ($site->activeCategories as $category) {
            foreach ($category->activeParameters as $parameter) {
                if ($parameter->metric) {
                    $metrics[$parameter->metric] = true;
                }
            }
        }

        return $metrics;
    }
}

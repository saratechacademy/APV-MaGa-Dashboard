<?php

namespace App\Services;

use App\Models\SensorReading;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteParameter;
use App\Services\Concerns\BuildsSiteStructure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Support\Zones;
use Illuminate\Support\Str;

/**
 * Copies a site's HOBOnet station (LI-COR Cloud) into sensor_readings, one
 * parameter per sensor, averaged into fixed buckets (config licor.bucket_minutes).
 * Parameters, groups and charts are created the first time a sensor is seen;
 * after that they belong to the admin and are only looked up by slug.
 */
class LicorSync
{
    use BuildsSiteStructure;

    /**
     * Records are fetched and stored this much at a time. A whole day of a
     * station could exceed PHP's 128 MB once decoded (it did while fetching
     * months of history). Longer than RECENT_WINDOW_MS, so an ordinary run
     * stays a single request.
     */
    private const WINDOW_MS = 12 * 3600 * 1000;

    /** How far behind the most advanced sensor an ordinary run looks. */
    private const RECENT_WINDOW_MS = 6 * 3600 * 1000;

    /** Set for a run that also fetches the history from this date (licor:sync --from). */
    private ?Carbon $historyFrom = null;

    public function __construct(private readonly LicorClient $client) {}

    /**
     * @param  array  $devices  every station of the account (LicorClient::devices())
     * @return array{sensors: int, created: int, stored: int, rejected: int, errors: string[]}
     */
    public function syncSite(Site $site, array $devices, ?Carbon $historyFrom = null): array
    {
        $this->historyFrom = $historyFrom;

        $stats = ['sensors' => 0, 'created' => 0, 'stored' => 0, 'rejected' => 0, 'errors' => []];
        $now   = now();

        $device = collect($devices)->firstWhere('deviceSerialNumber', $site->licor_device_serial);

        if (!$device) {
            $stats['errors'][] = "station {$site->licor_device_serial} not found on LI-COR";
        }

        $sensors = $device['sensors'] ?? [];
        // Created grouped by measurement, in config order (all temperatures,
        // then humidity...), so cards and charts come out in a readable order.
        $order = array_flip(array_keys(config('licor.measurements')));
        usort($sensors, fn ($a, $b) => [$order[$a['measurementType'] ?? ''] ?? PHP_INT_MAX, $a['sensorSerialNumber']]
            <=> [$order[$b['measurementType'] ?? ''] ?? PHP_INT_MAX, $b['sensorSerialNumber']]);

        $bucketMs = config('licor.bucket_minutes') * 60 * 1000;
        $targets  = [];

        foreach ($sensors as $sensor) {
            $spec = config('licor.measurements')[$sensor['measurementType'] ?? ''] ?? null;
            if (!$spec) {
                continue;
            }

            $category = $this->ensureCategory($site, $spec['category'], config('licor.offline_threshold_minutes'));
            if (!$category->is_active) {
                continue;
            }

            $parameter = $this->parameter($site, $category, $sensor['sensorSerialNumber'], $spec, $stats);
            if (!$parameter->is_active) {
                continue;
            }

            $stats['sensors']++;
            $targets[$sensor['sensorSerialNumber']] = [$parameter, $spec, $this->nextBucketStart($parameter, $now, $bucketMs)];
        }

        if ($targets) {
            try {
                // Whole station, one window at a time from the oldest bucket any
                // sensor is missing, each window stored before the next is
                // fetched: a week of 30 sensors does not fit in memory at once.
                // Windows are bucket-aligned, so no bucket straddles two of them.
                // A window too heavy to load (a station uploading a backlog)
                // is halved, down to one bucket; after that the windows grow
                // back two-fold, rather than at once, which would only fail
                // and be split again on every window of the backlog.
                $nowMs = $now->getTimestampMs();
                $step  = self::WINDOW_MS;
                for ($start = $this->fetchFrom($site, array_column($targets, 2)); $start < $nowMs; ) {
                    $end = min($start + $step, $nowMs);
                    try {
                        $records = $this->client->records($site->licor_device_serial, $start, $end);
                    } catch (LicorWindowTooLarge $e) {
                        if ($step <= $bucketMs) {
                            throw $e;
                        }
                        $step = max($bucketMs, intdiv($step, 2 * $bucketMs) * $bucketMs);
                        continue;
                    }

                    foreach ($targets as $serial => [$parameter, $spec, $from]) {
                        $this->storeBuckets($site, $parameter, $spec, $records[$serial] ?? [], max($from, $start), $end, $bucketMs, $now, $stats);
                    }
                    unset($records);
                    $start = $end;
                    $step  = min(self::WINDOW_MS, $step * 2);
                }
            } catch (ConnectionException | RequestException | LicorWindowTooLarge $e) {
                $stats['errors'][] = "station {$site->licor_device_serial}: {$e->getMessage()}";
            }
        }

        $site->forceFill([
            'licor_synced_at'  => $stats['errors'] ? $site->licor_synced_at : $now,
            'licor_sync_error' => $stats['errors'] ? Str::limit(implode(' | ', $stats['errors']), 490) : null,
        ])->save();

        return $stats;
    }

    /**
     * Where to start fetching the station from. Normally a little before the
     * most advanced sensor: if it started from the least advanced one, a
     * single sensor that stopped (or never reported) would make every run
     * re-download the whole station from further and further back.
     *
     * Once a day the fetch does start from the least advanced sensor, bounded
     * by the lookback: HOBOnet motes buffer their data when out of range and
     * deliver it late, and that is when it gets picked up.
     *
     * @param  int[]  $starts  each sensor's next missing bucket (epoch ms, bucket-aligned)
     */
    private function fetchFrom(Site $site, array $starts): int
    {
        $oldest = min($starts);

        if ($this->historyFrom || Cache::add("licor-catch-up:{$site->id}", true, now()->addDay())) {
            return $oldest;
        }

        return max($oldest, max($starts) - self::RECENT_WINDOW_MS);
    }

    private function storeBuckets(Site $site, SiteParameter $parameter, array $spec, array $records, int $from, int $until, int $bucketMs, Carbon $now, array &$stats): void
    {
        if (!$records) {
            return;
        }

        $buckets = [];
        foreach ($records as [$ts, $value]) {
            if (is_numeric($value) && $ts >= $from && $ts < $until) {
                $buckets[intdiv((int) $ts, $bucketMs) * $bucketMs][] = (float) $value;
            }
        }

        // A bucket is stored once a reading from its last minute (or later)
        // has arrived; the open one is fetched again, whole, next run.
        $receivedUntil = max(array_column($records, 0)) + config('licor.logging_interval_seconds') * 1000;

        $rows = [];
        foreach ($buckets as $start => $values) {
            if ($start + $bucketMs > $receivedUntil) {
                continue;
            }

            $value = $this->aggregate($values, $spec['aggregate'] ?? 'mean');
            if ($parameter->isOutOfRange($value)) {
                $stats['rejected']++;
                continue;
            }

            $rows[] = [
                'site_id'           => $site->id,
                'site_parameter_id' => $parameter->id,
                'value'             => round($value, 3),
                'read_at'           => Carbon::createFromTimestampMs($start)->setTimezone(config('app.timezone')),
                'created_at'        => $now,
                'updated_at'        => $now,
            ];
        }

        DB::transaction(function () use ($rows, &$stats) {
            foreach (array_chunk($rows, 500) as $chunk) {
                // The table allows one reading per parameter and instant:
                // fetching history again leaves what is stored as it is.
                $stats['stored'] += SensorReading::insertOrIgnore($chunk);
            }
        });
    }

    /** read_at is a bucket's start: resume at the bucket after the last stored one. */
    private function nextBucketStart(SiteParameter $parameter, Carbon $now, int $bucketMs): int
    {
        $last = $this->historyFrom ? null : SensorReading::where('site_parameter_id', $parameter->id)->max('read_at');

        if ($last) {
            return Carbon::parse($last, config('app.timezone'))->getTimestampMs() + $bucketMs;
        }

        $start = ($this->historyFrom ?? $this->firstSyncStart($now, 'licor'))->getTimestampMs();

        return intdiv($start, $bucketMs) * $bucketMs;
    }

    private function aggregate(array $values, string $how): float
    {
        return match ($how) {
            'sum'      => array_sum($values),
            'max'      => max($values),
            'circular' => $this->circularMean($values),
            default    => array_sum($values) / count($values),
        };
    }

    private function circularMean(array $degrees): float
    {
        $sin = array_sum(array_map(fn ($d) => sin(deg2rad($d)), $degrees));
        $cos = array_sum(array_map(fn ($d) => cos(deg2rad($d)), $degrees));

        return fmod(rad2deg(atan2($sin, $cos)) + 360, 360);
    }

    /**
     * Re-applies the site's sensor locations to its existing LI-COR
     * parameters, after an admin edited them: names and PAR metrics follow.
     * A parameter the admin renamed by hand is left alone.
     *
     * @param  array<string, string>  $previous  the locations before the edit
     * @return int  parameters updated
     */
    public function applyLocations(Site $site, array $previous = []): int
    {
        $specs   = collect(config('licor.measurements'));
        $updated = 0;
        $touched = $vacated = [];

        $parameters = SiteParameter::where('slug', 'like', 'licor%')
            ->whereHas('category', fn ($q) => $q->where('site_id', $site->id))
            ->get();

        foreach ($parameters as $parameter) {
            $base = explode('_', $parameter->slug)[1] ?? null;
            $spec = $specs->first(fn ($s) => $s['metric'] === $parameter->metric
                || ($s['metric'] === 'par' && str_starts_with((string) $parameter->metric, 'par')));

            if (!$base || !$spec) {
                continue;
            }

            // Only names this class generated itself are regenerated.
            $generated = [
                $this->parameterName($spec, $base, null),
                $this->parameterName($spec, $base, $previous[$base] ?? null),
            ];
            $location = $site->licor_locations[$base] ?? null;

            $changes = ['metric' => $this->parameterMetric($spec, $location), 'zone' => Zones::fromName($location)];
            if (in_array($parameter->name, $generated, true)) {
                $changes['name'] = $this->parameterName($spec, $base, $location);
            }

            // Same for the group: one the admin moved the card to is kept.
            $generatedGroups = [$spec['group'], $this->groupName($spec, $previous[$base] ?? null)];
            if (in_array($parameter->group_name, $generatedGroups, true)) {
                $group = $this->ensureGroup($parameter->category, $this->groupName($spec, $location));
                $changes += ['group_name' => $group->name, 'site_parameter_group_id' => $group->id];
                $touched[$parameter->site_category_id] = $parameter->category;
                $vacated[$parameter->site_category_id][] = $parameter->site_parameter_group_id;
            }

            $parameter->fill($changes);
            if ($parameter->isDirty()) {
                $parameter->save();
                $updated++;
            }
        }

        foreach ($touched as $categoryId => $category) {
            // A group this sync created and that the move left empty
            // ("Weather stations" once every sensor has its location).
            $category->parameterGroups()
                ->whereIn('id', array_filter($vacated[$categoryId]))
                ->whereDoesntHave('parameters')
                ->delete();
            $this->orderLocationGroups($site, $category);
        }

        return $updated;
    }

    /**
     * Location first when known, as for ThingsBoard; otherwise the sensor
     * serial is the only thing telling two stations apart.
     */
    private function parameterName(array $spec, string $base, ?string $location): string
    {
        return $location ? "{$location} – {$spec['name']}" : "{$spec['name']} – {$base}";
    }

    private function groupName(array $spec, ?string $location): string
    {
        return ($spec['group_by_location'] ?? false) && $location ? $location : $spec['group'];
    }

    /**
     * Location groups follow the order in which the site lists its locations
     * (Reference, Under PV...), not the order sensors happen to be met, and
     * come before any other group of the category.
     */
    private function orderLocationGroups(Site $site, SiteCategory $category): void
    {
        $order = collect($site->licor_locations ?? [])->unique()->values()->flip();

        $category->parameterGroups()->orderBy('sort_order')->get()
            ->sortBy(fn ($group) => [$order[$group->name] ?? PHP_INT_MAX, $group->sort_order])
            ->values()
            ->each(fn ($group, $position) => $group->sort_order === $position ?: $group->update(['sort_order' => $position]));
    }

    private function parameterMetric(array $spec, ?string $location): string
    {
        return $spec['metric'] === 'par' && $location ? 'par_' . Str::slug($location, '_') : $spec['metric'];
    }

    private function parameter(Site $site, SiteCategory $category, string $sensorSerial, array $spec, array &$stats): SiteParameter
    {
        $slug = 'licor_' . str_replace('-', '_', $sensorSerial);

        if ($parameter = $category->parameters()->where('slug', $slug)->first()) {
            return $parameter;
        }

        $base     = explode('-', $sensorSerial)[0];
        $location = $site->licor_locations[$base] ?? null;
        $group    = $this->ensureGroup($category, $this->groupName($spec, $location));
        $this->orderLocationGroups($site, $category);

        $stats['created']++;

        $parameter = $category->parameters()->create([
            'name'                    => $this->parameterName($spec, $base, $location),
            'slug'                    => $slug,
            'unit'                    => $spec['unit'] ?? null,
            'data_type'               => 'float',
            'metric'                  => $this->parameterMetric($spec, $location),
            'zone'                    => Zones::fromName($location),
            'input_type'              => 'sensor',
            'control_type'            => 'readonly',
            'group_name'              => $group->name,
            'site_parameter_group_id' => $group->id,
            'description'             => "LI-COR sensor {$sensorSerial}",
            'is_active'               => true,
            'show_on_dashboard'       => true,
            'sort_order'              => $category->parameters()->count(),
        ]);

        if (isset($spec['chart'])) {
            $this->addToChart($category, $spec['chart'], $parameter, $location === 'Reference');
        }

        return $parameter;
    }
}

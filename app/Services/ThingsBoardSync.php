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
use Illuminate\Support\Facades\DB;
use App\Support\Zones;
use Illuminate\Support\Str;

/**
 * Copies ThingsBoard telemetry into sensor_readings for one site, following
 * the device-name mapping in config/thingsboard.php. Categories, groups and
 * parameters are created the first time a device is seen; after that they
 * belong to the admin (rename, regroup, set thresholds, deactivate) and are
 * only looked up by slug.
 */
class ThingsBoardSync
{
    use BuildsSiteStructure;

    /** Points fetched per key and per request. */
    private const PAGE_SIZE = 1000;

    /** Cap on requests per device and per run, so one device can't hold up the others. */
    private const MAX_PAGES = 20;

    public function __construct(private readonly ThingsBoardClient $client) {}

    /**
     * @param  array  $devices  every device of the ThingsBoard account (ThingsBoardClient::devices())
     * @return array{devices: int, created: int, stored: int, rejected: int, errors: string[]}
     */
    public function syncSite(Site $site, array $devices): array
    {
        $stats = ['devices' => 0, 'created' => 0, 'stored' => 0, 'rejected' => 0, 'errors' => []];
        $now   = now();

        // Parameters are created in the order devices are met: by name, so
        // the dashboard lists Humidity-1-Shadow, -1-Sun, -2-Shadow... together
        // instead of in ThingsBoard's arbitrary order.
        usort($devices, fn ($a, $b) => strnatcasecmp($a['name'] ?? '', $b['name'] ?? ''));

        foreach ($devices as $device) {
            $mapping = $this->mappingFor((string) $site->thingsboard_prefix, $device['name'] ?? '');
            if (!$mapping) {
                continue;
            }

            $category = $this->ensureCategory($site, $mapping['category'], config('thingsboard.offline_threshold_minutes'));
            if (!$category->is_active) {
                continue;
            }

            $stats['devices']++;

            // [parameter, spec, epoch ms of the last instant already stored]
            $targets = [];
            // [computed parameter, the parameter it is computed from]
            $computed = [];
            foreach ($mapping['parameters'] as $spec) {
                if (isset($spec['daily_increase_of'])) {
                    $source = collect($mapping['parameters'])->firstWhere('key', $spec['daily_increase_of']);
                    $computed[] = [$this->parameter($category, $mapping, $spec, $stats), $this->parameter($category, $mapping, $source, $stats)];
                    continue;
                }

                if (!empty($spec['tank_volume'])) {
                    // No tank geometry on the site: no volume, rather than a wrong one.
                    if (!$litresPerCm = $site->tankLitresPerCm()) {
                        continue;
                    }
                    $spec['scale'] = $litresPerCm;
                }

                $parameter = $this->parameter($category, $mapping, $spec, $stats);
                if ($parameter->is_active && $parameter->isSensor()) {
                    $targets[] = [$parameter, $spec, $this->syncedUntil($parameter, $now)];
                }
            }

            if (!$targets) {
                continue;
            }

            // One unreachable device must not cost the others their data:
            // record it and move on; it resumes from where it stopped next run.
            try {
                $this->syncDevice($site, $device['id']['id'], $targets, $now, $stats);
            } catch (ConnectionException | RequestException $e) {
                $stats['errors'][] = "{$device['name']}: {$e->getMessage()}";
            }

            foreach ($computed as [$parameter, $source]) {
                if ($parameter->is_active) {
                    $this->storeDailyIncrease($site, $parameter, $source);
                }
            }
        }

        $site->forceFill([
            'thingsboard_synced_at'  => $stats['errors'] ? $site->thingsboard_synced_at : $now,
            'thingsboard_sync_error' => $stats['errors'] ? Str::limit(implode(' | ', $stats['errors']), 490) : null,
        ])->save();

        return $stats;
    }

    /**
     * Names of the devices a site with this prefix would be fed by.
     *
     * @return string[]
     */
    public function mappedDevices(string $prefix, array $devices): array
    {
        return array_values(array_filter(
            array_column($devices, 'name'),
            fn (string $name) => $this->mappingFor($prefix, $name) !== null,
        ));
    }

    /**
     * Fetches and stores a device's new points page by page until it is caught
     * up, so a long outage is recovered in one run rather than 1000 points at
     * a time.
     *
     * @param  array  $targets  [[SiteParameter, spec, epoch ms synced until], ...]
     */
    private function syncDevice(Site $site, string $deviceId, array $targets, Carbon $now, array &$stats): void
    {
        // Parameters are fetched together only when they resume from the same
        // instant (the keys of one device message always do). Otherwise one
        // key that stopped, or never reported, would make every run re-read
        // the others from as far back as that one.
        $groups = [];
        foreach ($targets as $target) {
            $groups[$target[2]][] = $target;
        }

        foreach ($groups as $group) {
            $this->syncKeys($site, $deviceId, $group, $now, $stats);
        }
    }

    private function syncKeys(Site $site, string $deviceId, array $targets, Carbon $now, array &$stats): void
    {
        $keys = array_values(array_unique(array_map(fn ($t) => $t[1]['key'], $targets)));

        for ($page = 0; $keys && $page < self::MAX_PAGES; $page++) {
            // Only keys that still have a full page behind them are asked
            // again, from the oldest point any of them is missing.
            $pending = array_filter($targets, fn ($t) => in_array($t[1]['key'], $keys, true));

            $series = $this->client->timeseries(
                $deviceId,
                $keys,
                min(array_map(fn ($t) => $t[2], $pending)) + 1,
                $now->getTimestampMs(),
                self::PAGE_SIZE,
            );

            $rows = [];

            foreach ($pending as $i => [$parameter, $spec, $syncedUntil]) {
                foreach ($series[$spec['key']] ?? [] as $point) {
                    if ($point['ts'] <= $syncedUntil) {
                        continue;
                    }
                    $targets[$i][2] = $point['ts'];

                    $value = $this->convert($point['value'] ?? null, $parameter, $spec);
                    if ($value === null || $parameter->isOutOfRange($value)) {
                        $stats['rejected']++;
                        continue;
                    }

                    $rows[] = [
                        'site_id'           => $site->id,
                        'site_parameter_id' => $parameter->id,
                        'value'             => $value,
                        'read_at'           => Carbon::createFromTimestampMs($point['ts'])->setTimezone(config('app.timezone')),
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];
                }
            }

            // All or nothing per page: a half-written page would leave some
            // parameters ahead of others with no way to tell on the next run.
            DB::transaction(function () use ($rows) {
                foreach (array_chunk($rows, 500) as $chunk) {
                    // The table allows one reading per parameter and instant.
                    SensorReading::insertOrIgnore($chunk);
                }
            });
            $stats['stored'] += count($rows);

            $keys = array_values(array_filter($keys, fn ($key) => count($series[$key] ?? []) >= self::PAGE_SIZE));
        }
    }

    /**
     * Resolves "<prefix>-<zone>-<sensor>-<rest>" against the configured zones
     * and sensors; null when the device isn't that prefix's or isn't mapped.
     */
    private function mappingFor(string $prefix, string $deviceName): ?array
    {
        $prefix .= '-';
        if (!Str::startsWith(Str::lower($deviceName), Str::lower($prefix))) {
            return null;
        }

        $label = substr($deviceName, strlen($prefix));
        $parts = explode('-', $label);

        $sensor = $this->lookup(config('thingsboard.sensors'), $parts[1] ?? '');
        // The zone as named for this kind of sensor; the label (and so the
        // parameter slug) keeps the device's own name.
        $zone   = $this->lookup($sensor['zone_aliases'] ?? [], $parts[0] ?? '') ?? ($parts[0] ?? '');
        $group  = $this->lookup(config('thingsboard.zones'), $zone);

        if (!$group || !$sensor) {
            return null;
        }

        return $sensor + [
            'group'     => $group,
            'label'     => str_replace('-', ' ', $label),
            // "APV 1 Shadow": where the sensor is, without repeating what it is.
            'position'  => implode(' ', array_merge([$zone], array_slice($parts, 2))),
            'reference' => strcasecmp($parts[0], 'Reference') === 0,
        ];
    }

    /** Device names aren't consistently cased (AfriFarm-Business-valve-1). */
    private function lookup(array $map, string $segment): mixed
    {
        foreach ($map as $name => $value) {
            if (strcasecmp($name, $segment) === 0) {
                return $value;
            }
        }

        return null;
    }

    private function parameter(SiteCategory $category, array $mapping, array $spec, array &$stats): SiteParameter
    {
        $slug = Str::slug($mapping['label'] . ' ' . $spec['name'], '_');

        if ($parameter = $category->parameters()->where('slug', $slug)->first()) {
            return $parameter;
        }

        $group = $this->ensureGroup($category, $mapping['group']);

        $stats['created']++;

        // Position first: dashboard cards cut long names short, and "where" is
        // what tells two cards of the same unit apart.
        $parameter = $category->parameters()->create([
            'name'                    => $mapping['position'] . ' – ' . $spec['name'],
            'slug'                    => $slug,
            'unit'                    => $spec['unit'] ?? null,
            'data_type'               => $spec['data_type'] ?? 'float',
            'metric'                  => $spec['metric'] ?? null,
            'zone'                    => Zones::fromName($mapping['group']),
            'input_type'              => 'sensor',
            'control_type'            => 'readonly',
            'group_name'              => $group->name,
            'site_parameter_group_id' => $group->id,
            'is_active'               => true,
            'show_on_dashboard'       => true,
            'sort_order'              => $category->parameters()->count(),
        ]);

        if (isset($spec['chart'])) {
            // Reference-field series are dashed, to read APV vs reference at a glance.
            $this->addToChart($category, $spec['chart'], $parameter, $mapping['reference'], $spec['chart_type'] ?? 'line');
        }

        return $parameter;
    }

    /**
     * Daily growth of a cumulative counter (a flow meter's running total), one
     * reading per day, timestamped at that day's last counter reading — so the
     * current day's figure moves with the data and a finished day's stays put.
     *
     * Each increase is counted on the day of the reading that revealed it:
     * water used while the meter was offline lands on the day it came back,
     * since nothing says when in between it flowed. A counter going down is a
     * reset or a replaced meter and counts for nothing. Days without any
     * reading get no value rather than a zero that was never measured.
     */
    private function storeDailyIncrease(Site $site, SiteParameter $parameter, SiteParameter $source): void
    {
        $lastComputed = SensorReading::where('site_parameter_id', $parameter->id)->max('read_at');
        $lastSource   = SensorReading::where('site_parameter_id', $source->id)->max('read_at');

        if (!$lastSource || $lastComputed === $lastSource) {
            return;
        }

        // The last computed day may have been partial: redo it, and all after it.
        $from = $lastComputed ? Carbon::parse($lastComputed, config('app.timezone'))->startOfDay() : null;

        $previous = $from
            ? SensorReading::where('site_parameter_id', $source->id)->where('read_at', '<', $from)->orderByDesc('read_at')->value('value')
            : null;

        $days = [];
        SensorReading::where('site_parameter_id', $source->id)
            ->when($from, fn ($q) => $q->where('read_at', '>=', $from))
            ->whereNotNull('value')
            ->orderBy('read_at')
            ->toBase()
            ->select('value', 'read_at')
            ->each(function ($row) use (&$days, &$previous) {
                $day = substr($row->read_at, 0, 10);
                $days[$day] ??= ['total' => 0.0, 'at' => $row->read_at];
                $days[$day]['at'] = $row->read_at;

                if ($previous !== null && $row->value > $previous) {
                    $days[$day]['total'] += $row->value - $previous;
                }
                $previous = $row->value;
            }, 2000);

        DB::transaction(function () use ($site, $parameter, $from, $days) {
            SensorReading::where('site_parameter_id', $parameter->id)
                ->when($from, fn ($q) => $q->where('read_at', '>=', $from))
                ->delete();

            $now = now();
            SensorReading::insertOrIgnore(array_map(fn (array $day) => [
                'site_id'           => $site->id,
                'site_parameter_id' => $parameter->id,
                'value'             => round($day['total'], 2),
                'read_at'           => $day['at'],
                'created_at'        => $now,
                'updated_at'        => $now,
            ], array_values($days)));
        });
    }

    /**
     * read_at is stored to the second, so everything up to the end of the last
     * stored second counts as synced — otherwise a point at hh:mm:ss.500 would
     * be fetched and stored again on every run.
     */
    private function syncedUntil(SiteParameter $parameter, Carbon $now): int
    {
        $last = SensorReading::where('site_parameter_id', $parameter->id)->max('read_at');

        return $last
            ? Carbon::parse($last, config('app.timezone'))->getTimestamp() * 1000 + 999
            : $now->copy()->subDays(config('thingsboard.lookback_days'))->getTimestampMs();
    }

    private function convert(mixed $raw, SiteParameter $parameter, array $spec): ?float
    {
        if ($parameter->data_type === 'switch') {
            $state = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            return $state === null ? null : (float) $state;
        }

        if (!is_numeric($raw)) {
            return null;
        }

        $value = (float) $raw * ($spec['scale'] ?? 1);

        return isset($spec['precision']) ? round($value, $spec['precision']) : $value;
    }
}

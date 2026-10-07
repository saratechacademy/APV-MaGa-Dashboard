<?php

namespace App\Services;

use App\Models\SensorReading;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteParameter;
use App\Models\SiteParameterGroup;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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

        foreach ($devices as $device) {
            $mapping = $this->mappingFor($site, $device['name'] ?? '');
            if (!$mapping) {
                continue;
            }

            $category = $this->category($site, $mapping['category']);
            if (!$category->is_active) {
                continue;
            }

            $stats['devices']++;

            // [parameter, spec, epoch ms of the last instant already stored]
            $targets = [];
            foreach ($mapping['parameters'] as $spec) {
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
        }

        $site->forceFill([
            'thingsboard_synced_at'  => $stats['errors'] ? $site->thingsboard_synced_at : $now,
            'thingsboard_sync_error' => $stats['errors'] ? Str::limit(implode(' | ', $stats['errors']), 490) : null,
        ])->save();

        return $stats;
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
                    SensorReading::insert($chunk);
                }
            });
            $stats['stored'] += count($rows);

            $keys = array_values(array_filter($keys, fn ($key) => count($series[$key] ?? []) >= self::PAGE_SIZE));
        }
    }

    /**
     * Resolves "<prefix>-<zone>-<sensor>-<rest>" against the configured zones
     * and sensors; null when the device isn't this site's or isn't mapped.
     */
    private function mappingFor(Site $site, string $deviceName): ?array
    {
        $prefix = $site->thingsboard_prefix . '-';
        if (!Str::startsWith(Str::lower($deviceName), Str::lower($prefix))) {
            return null;
        }

        $label = substr($deviceName, strlen($prefix));
        $parts = explode('-', $label);

        $group  = $this->lookup(config('thingsboard.zones'), $parts[0] ?? '');
        $sensor = $this->lookup(config('thingsboard.sensors'), $parts[1] ?? '');

        if (!$group || !$sensor) {
            return null;
        }

        return $sensor + ['group' => $group, 'label' => str_replace('-', ' ', $label)];
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

    private function category(Site $site, string $slug): SiteCategory
    {
        $defaults = collect(SiteCategory::defaults())->firstWhere('slug', $slug);

        return $site->categories()->firstOrCreate(['slug' => $slug], [
            'name'                      => $defaults['name'] ?? Str::headline($slug),
            'icon'                      => $defaults['icon'] ?? null,
            'color'                     => $defaults['color'] ?? null,
            'offline_threshold_minutes' => config('thingsboard.offline_threshold_minutes'),
            'is_active'                 => true,
            'sort_order'                => $site->categories()->count(),
        ]);
    }

    private function parameter(SiteCategory $category, array $mapping, array $spec, array &$stats): SiteParameter
    {
        $slug = Str::slug($mapping['label'] . ' ' . $spec['name'], '_');

        if ($parameter = $category->parameters()->where('slug', $slug)->first()) {
            return $parameter;
        }

        $groupCount = $category->parameterGroups()->count();
        $group = SiteParameterGroup::firstOrCreate(
            ['site_category_id' => $category->id, 'name' => $mapping['group']],
            ['color' => SiteParameterGroup::nextPaletteColor($groupCount), 'sort_order' => $groupCount],
        );

        $stats['created']++;

        return $category->parameters()->create([
            'name'                    => $mapping['label'] . ' – ' . $spec['name'],
            'slug'                    => $slug,
            'unit'                    => $spec['unit'] ?? null,
            'data_type'               => $spec['data_type'] ?? 'float',
            'input_type'              => 'sensor',
            'control_type'            => 'readonly',
            'group_name'              => $group->name,
            'site_parameter_group_id' => $group->id,
            'is_active'               => true,
            'show_on_dashboard'       => true,
            'sort_order'              => $category->parameters()->count(),
        ]);
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

<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Models\User;
use App\Services\ThingsBoardClient;
use App\Services\ThingsBoardSync;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use RuntimeException;

/**
 * One-command provisioning for a fresh or existing installation: makes sure
 * every site in config('thingsboard.sites') exists and is linked to its
 * ThingsBoard devices, then runs a first sync, which creates the categories,
 * groups and parameters. Safe to run again: it only fills in what is missing.
 */
class ThingsBoardSetup extends Command
{
    protected $signature = 'thingsboard:setup
                            {--owner= : E-mail of the user who owns sites that have to be created (default: the first active admin)}
                            {--dry-run : Show what would be linked or created without changing anything}';

    protected $description = 'Create or link the ThingsBoard sites, then create their parameters with a first sync';

    public function handle(): int
    {
        $client = ThingsBoardClient::fromConfig();

        try {
            $devices = $client->devices();
        } catch (RuntimeException | ConnectionException | RequestException $e) {
            $this->error("Cannot reach ThingsBoard: {$e->getMessage()}");

            return self::FAILURE;
        }

        $sync   = new ThingsBoardSync($client);
        $dryRun = $this->option('dry-run');
        $linked = 0;

        foreach (config('thingsboard.sites') as $prefix => $definition) {
            $mapped = $sync->mappedDevices($prefix, $devices);

            if (!$mapped) {
                $this->warn("{$prefix}: no mapped device on ThingsBoard, skipped.");
                continue;
            }

            [$site, $action] = $this->resolveSite($prefix, $definition);

            if ($action === 'ambiguous') {
                $this->error("{$prefix}: several sites could match ({$site}). Set the \"ThingsBoard device prefix\" "
                    . 'on the right one in admin → edit site, then run this command again.');

                return self::FAILURE;
            }
            if ($action === 'no-owner') {
                $this->error("{$prefix}: the site has to be created but no owner was found. Pass --owner=<e-mail of an existing user>.");

                return self::FAILURE;
            }

            $this->line("{$prefix}: {$action} site \"{$site->name}\" — " . count($mapped) . ' device(s).');

            if ($dryRun) {
                continue;
            }

            $site->thingsboard_prefix = $prefix;
            $site->status ??= 'active';
            $site->save();
            $linked++;

            // Categories that already existed keep the 5-minute default, which
            // flags every 15-20 minute device as stale between two reports.
            $site->categories()
                ->whereIn('slug', array_unique(array_column(config('thingsboard.sensors'), 'category')))
                ->where('offline_threshold_minutes', '<', config('thingsboard.offline_threshold_minutes'))
                ->update(['offline_threshold_minutes' => config('thingsboard.offline_threshold_minutes')]);
        }

        if ($dryRun) {
            $this->line('Dry run: nothing was changed.');

            return self::SUCCESS;
        }

        if ($linked === 0) {
            return self::SUCCESS;
        }

        $status = $this->call('thingsboard:sync');

        $this->newLine();
        $this->line('To keep the data flowing, the server cron must run the scheduler every minute:');
        $this->line('  * * * * * cd ' . base_path() . ' && php artisan schedule:run >> /dev/null 2>&1');

        return $status;
    }

    /**
     * @return array{0: Site|string|null, 1: string}  the site and what happens to it
     *                                                (already linked / linking existing / creating),
     *                                                or a reason it can't be resolved.
     */
    private function resolveSite(string $prefix, array $definition): array
    {
        if ($site = Site::where('thingsboard_prefix', $prefix)->first()) {
            return [$site, 'already linked to'];
        }

        // An installation that predates the integration already has its sites,
        // under names like "UTG Agrivoltaic Site - GAM": reuse rather than duplicate.
        $candidates = Site::whereNull('thingsboard_prefix')
            ->where(fn ($q) => $q->where('name', 'like', "%{$prefix}%")->orWhere('name', $definition['name']))
            ->get();

        if ($candidates->count() > 1) {
            return [$candidates->pluck('name')->implode(', '), 'ambiguous'];
        }
        if ($candidates->count() === 1) {
            return [$candidates->first(), 'linking existing'];
        }

        $owner = $this->option('owner')
            ? User::where('email', $this->option('owner'))->first()
            : User::where('role', 'admin')->where('status', 'active')->orderBy('id')->first();

        if (!$owner) {
            return [null, 'no-owner'];
        }

        return [new Site($definition + ['user_id' => $owner->id]), 'creating'];
    }
}

<?php

namespace App\Console\Commands;

use App\Services\ThingsBoardClient;
use App\Services\ThingsBoardSync;
use App\Support\SitePresets;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use RuntimeException;

/**
 * One-command provisioning for a fresh or existing installation: makes sure
 * every preset site that has a ThingsBoard prefix exists and is linked to its
 * devices, then runs a first sync, which creates the categories, groups and
 * parameters. Safe to run again: it only fills in what is missing.
 *
 * The sites come from the presets file (database/presets/sites.json), not
 * from code. A site not in the file is linked from Admin → Data sources.
 */
class ThingsBoardSetup extends Command
{
    protected $signature = 'thingsboard:setup
                            {--presets= : Site presets file (default: database/presets/sites.json)}
                            {--owner= : E-mail of the user who owns sites that have to be created (default: the first active admin)}
                            {--dry-run : Show what would be linked or created without changing anything}';

    protected $description = 'Create or link the ThingsBoard sites of the presets file, then create their parameters with a first sync';

    public function handle(): int
    {
        $client = ThingsBoardClient::fromConfig();

        try {
            $presets = SitePresets::load($this->option('presets'));
            $devices = $client->devices();
        } catch (RuntimeException | ConnectionException | RequestException $e) {
            $this->error("Cannot set up ThingsBoard: {$e->getMessage()}");

            return self::FAILURE;
        }

        $sync   = new ThingsBoardSync($client);
        $dryRun = $this->option('dry-run');
        $linked = 0;

        foreach ($presets as $preset) {
            $prefix = $preset['thingsboard_prefix'] ?? null;
            if (!$prefix) {
                continue;
            }

            $mapped = $sync->mappedDevices($prefix, $devices);

            if (!$mapped) {
                $this->warn("{$prefix}: no mapped device on ThingsBoard, skipped.");
                continue;
            }

            [$site, $action] = SitePresets::resolve($preset, 'thingsboard_prefix', $this->option('owner'));

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

            SitePresets::fill($site, $preset);
            $site->save();
            $linked++;

            $site->alignOfflineThresholds('thingsboard');
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
}

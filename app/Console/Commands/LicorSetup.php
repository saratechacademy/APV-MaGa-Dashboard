<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Models\SiteParameterGroup;
use App\Services\LicorClient;
use App\Services\LicorSync;
use App\Support\SitePresets;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * One-command provisioning, like thingsboard:setup: links every preset site
 * that has a LI-COR station to it (creating the site when it doesn't exist),
 * adds the manual agriculture parameters, then runs a first sync, which
 * creates the weather/PAR parameters. Safe to run again.
 *
 * The sites come from the presets file (database/presets/sites.json), not
 * from code. A station not in the file is linked from Admin → Data sources.
 */
class LicorSetup extends Command
{
    protected $signature = 'licor:setup
                            {--presets= : Site presets file (default: database/presets/sites.json)}
                            {--owner= : E-mail of the user who owns sites that have to be created (default: the first active admin)}
                            {--dry-run : Show what would be linked or created without changing anything}';

    protected $description = 'Create or link the HOBOnet (LI-COR) sites of the presets file, add their agriculture parameters, then sync their weather and PAR data';

    public function handle(): int
    {
        try {
            $presets = SitePresets::load($this->option('presets'));
            $devices = LicorClient::fromConfig()->devices();
        } catch (RuntimeException | ConnectionException | RequestException $e) {
            $this->error("Cannot set up LI-COR: {$e->getMessage()}");

            return self::FAILURE;
        }

        $dryRun = $this->option('dry-run');
        $linked = 0;

        foreach ($presets as $preset) {
            $serial = (string) ($preset['licor_device_serial'] ?? '');
            if ($serial === '') {
                continue;
            }

            $station = collect($devices)->firstWhere('deviceSerialNumber', $serial);

            if (!$station) {
                $this->warn("{$serial}: station not found on LI-COR, skipped.");
                continue;
            }

            [$site, $action] = SitePresets::resolve($preset, 'licor_device_serial', $this->option('owner'));

            if ($action === 'ambiguous') {
                $this->error("{$serial}: several sites could match ({$site}). Set the \"LI-COR station serial\" "
                    . 'on the right one in admin → edit site, then run this command again.');

                return self::FAILURE;
            }
            if ($action === 'no-owner') {
                $this->error("{$serial}: the site has to be created but no owner was found. Pass --owner=<e-mail of an existing user>.");

                return self::FAILURE;
            }

            $this->line("{$serial}: {$action} site \"{$site->name}\" — " . count($station['sensors'] ?? []) . ' sensor(s).');

            if ($dryRun) {
                continue;
            }

            $previousLocations = $site->licor_locations ?? [];
            SitePresets::fill($site, $preset);
            $site->save();
            $linked++;

            // Sensors the file locates that the site did not know yet: their
            // existing parameters are renamed and regrouped now.
            if (($site->licor_locations ?? []) != $previousLocations) {
                (new LicorSync(LicorClient::fromConfig()))->applyLocations($site, $previousLocations);
            }

            $this->addAgricultureParameters($site);

            $site->alignOfflineThresholds('licor');
        }

        if ($dryRun) {
            $this->line('Dry run: nothing was changed.');

            return self::SUCCESS;
        }

        if ($linked === 0) {
            return self::SUCCESS;
        }

        $status = $this->call('licor:sync');

        $this->newLine();
        $this->line('To keep the data flowing, the server cron must run the scheduler every minute:');
        $this->line('  * * * * * cd ' . base_path() . ' && php artisan schedule:run >> /dev/null 2>&1');

        return $status;
    }

    private function addAgricultureParameters(Site $site): void
    {
        $category = $site->categories()->firstOrCreate(['slug' => 'agriculture'], [
            'name'       => 'Agriculture',
            'icon'       => '🌿',
            'color'      => '#15803d',
            'is_active'  => true,
            'sort_order' => $site->categories()->count(),
        ]);

        foreach (config('licor.agriculture_parameters') as $spec) {
            $slug = Str::slug($spec['name'], '_');
            if ($category->parameters()->where('slug', $slug)->exists()) {
                continue;
            }

            $count = $category->parameterGroups()->count();
            $group = $category->parameterGroups()->firstOrCreate(['name' => $spec['group']], [
                'color'      => SiteParameterGroup::nextPaletteColor($count),
                'sort_order' => $count,
            ]);

            $category->parameters()->create([
                'name'                    => $spec['name'],
                'slug'                    => $slug,
                'unit'                    => $spec['unit'],
                'data_type'               => 'float',
                'input_type'              => 'manual',
                'control_type'            => 'readonly',
                'group_name'              => $group->name,
                'site_parameter_group_id' => $group->id,
                'is_active'               => true,
                'show_on_dashboard'       => true,
                'sort_order'              => $category->parameters()->count(),
            ]);
        }
    }
}

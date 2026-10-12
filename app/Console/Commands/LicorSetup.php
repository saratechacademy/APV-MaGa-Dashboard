<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Models\User;
use App\Services\LicorClient;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * One-command provisioning, like thingsboard:setup: links every station in
 * config('licor.sites') to its site (creating the site when it doesn't
 * exist), adds the manual agriculture parameters, then runs a first sync,
 * which creates the weather/PAR parameters. Safe to run again.
 */
class LicorSetup extends Command
{
    protected $signature = 'licor:setup
                            {--owner= : E-mail of the user who owns sites that have to be created (default: the first active admin)}
                            {--dry-run : Show what would be linked or created without changing anything}';

    protected $description = 'Create or link the HOBOnet (LI-COR) sites, add their agriculture parameters, then sync their weather and PAR data';

    public function handle(): int
    {
        try {
            $devices = LicorClient::fromConfig()->devices();
        } catch (RuntimeException | ConnectionException | RequestException $e) {
            $this->error("Cannot reach LI-COR: {$e->getMessage()}");

            return self::FAILURE;
        }

        $dryRun = $this->option('dry-run');
        $linked = 0;

        foreach (config('licor.sites') as $serial => $definition) {
            $serial  = (string) $serial;
            $station = collect($devices)->firstWhere('deviceSerialNumber', $serial);

            if (!$station) {
                $this->warn("{$serial}: station not found on LI-COR, skipped.");
                continue;
            }

            [$site, $action] = $this->resolveSite($serial, $definition);

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

            $site->licor_device_serial = $serial;
            $site->status ??= 'active';
            $site->save();
            $linked++;

            $this->addAgricultureParameters($site);
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

    /**
     * @return array{0: Site|string|null, 1: string}
     */
    private function resolveSite(string $serial, array $definition): array
    {
        if ($site = Site::where('licor_device_serial', $serial)->first()) {
            return [$site, 'already linked to'];
        }

        $match = $definition['match'];

        // Same site as the ThingsBoard devices, when that is already set up.
        if ($site = Site::whereNull('licor_device_serial')->where('thingsboard_prefix', $match)->first()) {
            return [$site, 'linking existing'];
        }

        $candidates = Site::whereNull('licor_device_serial')
            ->where(fn ($q) => $q->where('name', 'like', "%{$match}%")->orWhere('name', $definition['name']))
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

        return [new Site(collect($definition)->except('match')->all() + ['user_id' => $owner->id]), 'creating'];
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
                'color'      => \App\Models\SiteParameterGroup::nextPaletteColor($count),
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

<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Services\ThingsBoardClient;
use App\Services\ThingsBoardSync;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class ThingsBoardSyncCommand extends Command
{
    protected $signature = 'thingsboard:sync
                            {--site= : Only sync the site with this slug}';

    protected $description = 'Pull new ThingsBoard telemetry into every site that has a ThingsBoard device prefix';

    public function handle(): int
    {
        $sites = Site::whereNotNull('thingsboard_prefix')
            ->where('status', 'active')
            ->when($this->option('site'), fn ($q, $slug) => $q->where('slug', $slug))
            ->get();

        if ($sites->isEmpty()) {
            $this->line('No active site has a ThingsBoard device prefix; nothing to sync.');

            return self::SUCCESS;
        }

        // Two runs at once (scheduler + a run started by hand) would both
        // read the same "last stored reading" and store every new point twice.
        // The lock expires on its own in case a run is killed mid-way.
        $lock = Cache::lock('thingsboard-sync', 30 * 60);

        if (!$lock->get()) {
            $this->line('Another ThingsBoard sync is still running; skipping this one.');

            return self::SUCCESS;
        }

        try {
            return $this->sync($sites);
        } finally {
            $lock->release();
        }
    }

    private function sync(Collection $sites): int
    {
        $client = ThingsBoardClient::fromConfig();

        try {
            $devices = $client->devices();
        } catch (RuntimeException | ConnectionException | RequestException $e) {
            // Nothing could be fetched at all: every linked site is affected.
            $error = Str::limit($e->getMessage(), 490);
            $sites->each(fn (Site $site) => $site->forceFill(['thingsboard_sync_error' => $error])->save());

            return $this->reportFailure("ThingsBoard sync failed: {$e->getMessage()}");
        }

        $sync   = new ThingsBoardSync($client);
        $failed = false;

        foreach ($sites as $site) {
            $stats = $sync->syncSite($site, $devices);

            $this->line("{$site->name} ({$site->thingsboard_prefix}): {$stats['devices']} device(s), "
                . "{$stats['stored']} reading(s) stored, {$stats['rejected']} rejected, "
                . "{$stats['created']} parameter(s) created.");

            foreach ($stats['errors'] as $error) {
                $failed = true;
                $this->reportFailure("ThingsBoard sync failed for {$site->name} — {$error}");
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /** Scheduled runs have no console to read: failures go to the log too. */
    private function reportFailure(string $message): int
    {
        Log::error($message);
        $this->error($message);

        return self::FAILURE;
    }
}

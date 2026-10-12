<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Services\LicorClient;
use App\Services\LicorSync;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class LicorSyncCommand extends Command
{
    protected $signature = 'licor:sync
                            {--site= : Only sync the site with this slug}';

    protected $description = 'Pull new HOBOnet (LI-COR Cloud) weather and PAR data into every site that has a LI-COR station serial';

    public function handle(): int
    {
        $sites = Site::whereNotNull('licor_device_serial')
            ->where('status', 'active')
            ->when($this->option('site'), fn ($q, $slug) => $q->where('slug', $slug))
            ->get();

        if ($sites->isEmpty()) {
            $this->line('No active site has a LI-COR station serial; nothing to sync.');

            return self::SUCCESS;
        }

        // Two overlapping runs would both resume from the same last reading
        // and store every new bucket twice.
        $lock = Cache::lock('licor-sync', 30 * 60);

        if (!$lock->get()) {
            $this->line('Another LI-COR sync is still running; skipping this one.');

            return self::SUCCESS;
        }

        try {
            return $this->sync($sites);
        } finally {
            $lock->release();
        }
    }

    private function sync($sites): int
    {
        $client = LicorClient::fromConfig();

        try {
            $devices = $client->devices();
        } catch (RuntimeException | ConnectionException | RequestException $e) {
            $error = Str::limit($e->getMessage(), 490);
            $sites->each(fn (Site $site) => $site->forceFill(['licor_sync_error' => $error])->save());

            return $this->reportFailure("LI-COR sync failed: {$e->getMessage()}");
        }

        $sync   = new LicorSync($client);
        $failed = false;

        foreach ($sites as $site) {
            $stats = $sync->syncSite($site, $devices);

            $this->line("{$site->name} ({$site->licor_device_serial}): {$stats['sensors']} sensor(s), "
                . "{$stats['stored']} reading(s) stored, {$stats['rejected']} rejected, "
                . "{$stats['created']} parameter(s) created.");

            foreach ($stats['errors'] as $error) {
                $failed = true;
                $this->reportFailure("LI-COR sync failed for {$site->name} — {$error}");
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

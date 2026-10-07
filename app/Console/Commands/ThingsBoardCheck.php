<?php

namespace App\Console\Commands;

use App\Services\ThingsBoardClient;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class ThingsBoardCheck extends Command
{
    protected $signature = 'thingsboard:check
                            {--device= : Only show the device with this name or ID}';

    protected $description = 'Verify the ThingsBoard connection and list the devices and telemetry it exposes';

    public function handle(): int
    {
        $client = ThingsBoardClient::fromConfig();

        try {
            $user = $client->currentUser();
            $this->info('Connected to ' . config('thingsboard.url') . ' as ' . ($user['email'] ?? '?') . ' (' . ($user['authority'] ?? '?') . ').');

            $devices = $client->devices();

            if ($filter = $this->option('device')) {
                $devices = array_values(array_filter(
                    $devices,
                    fn (array $d) => ($d['name'] ?? null) === $filter || ($d['id']['id'] ?? null) === $filter,
                ));
            }

            $this->line(count($devices) . ' device(s) found.');

            foreach ($devices as $device) {
                $id = $device['id']['id'];

                $this->newLine();
                $this->line("<comment>{$device['name']}</comment> — type: " . ($device['type'] ?? '?') . ", id: {$id}");

                $rows = [];
                foreach ($client->latestTelemetry($id) as $key => $point) {
                    $rows[] = [
                        $key,
                        Str::limit((string) ($point['value'] ?? ''), 60),
                        isset($point['ts']) ? Carbon::createFromTimestampMs($point['ts'])->toDateTimeString() . ' UTC' : '',
                    ];
                }

                $rows
                    ? $this->table(['Key', 'Latest value', 'Reported at'], $rows)
                    : $this->line('  No telemetry.');
            }
        } catch (RuntimeException | ConnectionException | RequestException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

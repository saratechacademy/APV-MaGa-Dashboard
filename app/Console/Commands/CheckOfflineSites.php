<?php

namespace App\Console\Commands;

use App\Services\SiteOfflineMonitor;
use Illuminate\Console\Command;

class CheckOfflineSites extends Command
{
    protected $signature = 'sites:check-offline';

    protected $description = 'E-mail administrators and site users when a site stops sending data, and when it starts again';

    public function handle(SiteOfflineMonitor $monitor): int
    {
        $announced = $monitor->check();

        if (!$announced) {
            $this->line('No change: nothing to announce.');
        }

        foreach ($announced as $event) {
            $this->line("{$event['site']}: "
                . ($event['down'] ? 'stopped — ' . implode(', ', $event['down']) . '. ' : '')
                . ($event['up'] ? 'back — ' . implode(', ', $event['up']) . '. ' : '')
                . "{$event['recipients']} recipient(s).");
        }

        return self::SUCCESS;
    }
}

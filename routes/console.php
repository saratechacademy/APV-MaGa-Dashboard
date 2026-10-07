<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// ThingsBoard devices report every 15-20 minutes. Needs the server cron to
// run `php artisan schedule:run` every minute. Overlapping runs are prevented
// by the command's own lock, which also covers runs started by hand.
$thingsboardSync = Schedule::command('thingsboard:sync')
    ->everyTenMinutes()
    ->when(fn () => filled(config('thingsboard.url')));

if ($heartbeat = config('thingsboard.heartbeat_url')) {
    $thingsboardSync->pingOnSuccess($heartbeat);
}

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

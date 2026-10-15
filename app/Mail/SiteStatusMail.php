<?php
// app/Mail/SiteStatusMail.php

namespace App\Mail;

use App\Models\Site;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * "These parts of the site stopped sending data" and/or "these are sending
 * again" — one message per site and per check (see SiteOfflineMonitor).
 */
class SiteStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $siteUrl;

    /**
     * @param  array<int, array{category: \App\Models\SiteCategory, since: \Illuminate\Support\Carbon}>  $down
     * @param  array<int, array{category: \App\Models\SiteCategory, since: \Illuminate\Support\Carbon, back: \Illuminate\Support\Carbon}>  $up
     */
    public function __construct(
        public Site $site,
        public array $down,
        public array $up,
        public array $sensorsDown = [],
        public array $sensorsUp = [],
    ) {
        $this->siteUrl = route('dashboard.site', $site);
    }

    /**
     * Sensor events by device: a soil probe is one line ("APV 1 Sun —
     * Moisture, Conductivity, Temperature"), not three. Parameter names are
     * "<device or location> – <measurement>".
     *
     * @return array<int, array{device: string, measurements: string[], since: \Illuminate\Support\Carbon, back: ?\Illuminate\Support\Carbon}>
     */
    public static function byDevice(array $events): array
    {
        return collect($events)
            ->groupBy(fn ($e) => str_contains($e['parameter']->name, ' – ') ? explode(' – ', $e['parameter']->name, 2)[0] : $e['parameter']->name)
            ->map(fn ($group, $device) => [
                'device'       => $device,
                'measurements' => $group->map(fn ($e) => str_contains($e['parameter']->name, ' – ') ? explode(' – ', $e['parameter']->name, 2)[1] : null)->filter()->values()->all(),
                'since'        => $group->min('since'),
                'back'         => $group->max('back'),
            ])
            ->values()->all();
    }

    public function build()
    {
        $names = fn (array $events) => implode(', ', array_map(fn ($e) => $e['category']->name, $events));

        $sensors = fn (array $events) => count(self::byDevice($events)) === 1
            ? self::byDevice($events)[0]['device']
            : count(self::byDevice($events)) . ' sensors';

        $subject = match (true) {
            $this->down && $this->up => "{$this->site->name}: {$names($this->down)} stopped reporting, {$names($this->up)} back",
            (bool) $this->down       => "{$this->site->name}: {$names($this->down)} stopped reporting",
            (bool) $this->up         => "{$this->site->name}: {$names($this->up)} reporting again",
            $this->sensorsDown && $this->sensorsUp => "{$this->site->name}: {$sensors($this->sensorsDown)} stopped reporting, {$sensors($this->sensorsUp)} back",
            (bool) $this->sensorsDown => "{$this->site->name}: {$sensors($this->sensorsDown)} stopped reporting",
            default                   => "{$this->site->name}: {$sensors($this->sensorsUp)} reporting again",
        };

        return $this->subject($subject)->view('emails.site-status');
    }
}

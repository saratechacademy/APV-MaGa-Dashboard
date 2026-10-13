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
    public function __construct(public Site $site, public array $down, public array $up)
    {
        $this->siteUrl = route('dashboard.site', $site);
    }

    public function build()
    {
        $names = fn (array $events) => implode(', ', array_map(fn ($e) => $e['category']->name, $events));

        $subject = match (true) {
            $this->down && $this->up => "{$this->site->name}: {$names($this->down)} stopped reporting, {$names($this->up)} back",
            (bool) $this->down       => "{$this->site->name}: {$names($this->down)} stopped reporting",
            default                  => "{$this->site->name}: {$names($this->up)} reporting again",
        };

        return $this->subject($subject)->view('emails.site-status');
    }
}

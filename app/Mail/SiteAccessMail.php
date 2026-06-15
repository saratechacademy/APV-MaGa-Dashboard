<?php
// app/Mail/SiteAccessMail.php

namespace App\Mail;

use App\Models\Site;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SiteAccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public Site $site;
    public string $role;
    public string $loginUrl;

    public function __construct(User $user, Site $site, string $role)
    {
        $this->user = $user;
        $this->site = $site;
        $this->role = $role;
        $this->loginUrl = route('login');
    }

    public function build()
    {
        return $this->subject("New site access: {$this->site->name}")
                     ->view('emails.site-access');
    }
}
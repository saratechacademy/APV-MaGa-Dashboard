<?php
// app/Mail/WelcomeUserMail.php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $loginUrl;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->loginUrl = route('login');
    }

    public function build()
    {
        return $this->subject('Welcome to APV-MaGa')
                     ->view('emails.welcome');
    }
}
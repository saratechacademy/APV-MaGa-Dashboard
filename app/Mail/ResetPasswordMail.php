<?php
// app/Mail/ResetPasswordMail.php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $resetUrl;
    public int $expireMinutes;

    public function __construct(User $user, string $resetUrl, int $expireMinutes = 60)
    {
        $this->user = $user;
        $this->resetUrl = $resetUrl;
        $this->expireMinutes = $expireMinutes;
    }

    public function build()
    {
        return $this->subject('Reset your APV-MaGa password')
                     ->view('emails.reset-password');
    }
}
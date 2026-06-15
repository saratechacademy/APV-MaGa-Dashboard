<?php
// app/Mail/AccountApprovedMail.php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountApprovedMail extends Mailable
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
        return $this->subject('Your APV-MaGa account is active')
                     ->view('emails.account-approved');
    }
}
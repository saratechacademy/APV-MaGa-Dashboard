<?php
// app/Mail/NewRegistrationMail.php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the administrators that someone registered and is waiting for an
 * account approval.
 */
class NewRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $usersUrl;

    public function __construct(public User $user)
    {
        $this->usersUrl = route('admin.users');
    }

    public function build()
    {
        return $this->subject("New account awaiting approval: {$this->user->name}")
                     ->view('emails.new-registration');
    }
}

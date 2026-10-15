<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** The daily database copy failed (see the db:backup command). */
class BackupFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $reason) {}

    public function build()
    {
        return $this->subject('APV-MaGa: the daily database backup failed')
                    ->view('emails.backup-failed');
    }
}

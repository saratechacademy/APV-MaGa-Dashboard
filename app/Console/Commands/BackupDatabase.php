<?php

namespace App\Console\Commands;

use App\Mail\BackupFailedMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PDO;
use RuntimeException;

/**
 * Daily copy of the SQLite database: the whole platform (sites, history,
 * accounts) is that one file. The copy is consistent (VACUUM INTO, waiting
 * for the syncs' writes rather than failing), checked, compressed, and old
 * copies are pruned. When it fails, the administrators are told: a backup
 * that silently stopped is only discovered the day it is needed.
 */
class BackupDatabase extends Command
{
    protected $signature = 'db:backup';

    protected $description = 'Copy the SQLite database to the backup folder (config backup.path), and e-mail the administrators if it fails';

    public function handle(): int
    {
        try {
            $file = $this->backup();
        } catch (\Throwable $e) {
            $this->error("Backup failed: {$e->getMessage()}");
            Log::error("Database backup failed: {$e->getMessage()}");
            $this->tellAdministrators($e->getMessage());

            return self::FAILURE;
        }

        $this->line("Backup written: {$file}");

        return self::SUCCESS;
    }

    private function backup(): string
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'sqlite') {
            throw new RuntimeException('only SQLite databases are backed up by this command; use the database server\'s own tools.');
        }

        $dir = rtrim(config('backup.path'), '/\\');
        if (!is_dir($dir) && !@mkdir($dir, 0750, true)) {
            throw new RuntimeException("cannot create the backup folder {$dir}");
        }
        if (!is_writable($dir)) {
            throw new RuntimeException("the backup folder {$dir} is not writable");
        }

        $copy = $dir . DIRECTORY_SEPARATOR . 'apvmaga-' . now()->format('Y-m-d') . '.sqlite';
        @unlink($copy);
        @unlink("{$copy}.gz");

        try {
            $pdo = $connection->getPdo();
            $pdo->exec('PRAGMA busy_timeout = ' . (int) config('backup.wait_seconds') * 1000);
            $pdo->exec('VACUUM INTO ' . $pdo->quote($copy));

            $check = (new PDO("sqlite:{$copy}"))->query('PRAGMA integrity_check')->fetchColumn();
            if ($check !== 'ok') {
                throw new RuntimeException("the copy failed its integrity check ({$check})");
            }

            $this->gzip($copy);
        } finally {
            @unlink($copy);
        }

        foreach (glob($dir . DIRECTORY_SEPARATOR . 'apvmaga-*.sqlite.gz') ?: [] as $old) {
            if (filemtime($old) < now()->subDays(config('backup.keep_days'))->getTimestamp()) {
                @unlink($old);
            }
        }

        return "{$copy}.gz";
    }

    private function gzip(string $file): void
    {
        $in  = fopen($file, 'rb');
        $out = gzopen("{$file}.gz", 'wb6');
        if (!$in || !$out) {
            throw new RuntimeException("cannot compress {$file}");
        }

        while (!feof($in)) {
            gzwrite($out, fread($in, 1024 * 1024));
        }
        fclose($in);
        gzclose($out);
    }

    private function tellAdministrators(string $reason): void
    {
        $admins = User::where('role', User::ROLE_ADMIN)->where('status', 'active')
            ->where('receives_alerts', true)->pluck('email');

        try {
            if ($admins->isNotEmpty()) {
                Mail::to($admins)->send(new BackupFailedMail($reason));
            }
        } catch (\Throwable $e) {
            Log::error("Backup failure e-mail could not be sent: {$e->getMessage()}");
        }
    }
}

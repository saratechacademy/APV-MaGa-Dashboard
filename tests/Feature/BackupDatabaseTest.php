<?php

namespace Tests\Feature;

use App\Mail\BackupFailedMail;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The backup runs unattended at night: these tests make sure it writes a
 * usable copy, prunes old ones, and that a failure reaches the administrators.
 * Runs against a real SQLite file (VACUUM INTO cannot copy an in-memory one).
 */
class BackupDatabaseTest extends TestCase
{
    private string $dir;
    private string $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'apvmaga-backup-test-' . uniqid();
        $this->db  = $this->dir . '-db.sqlite';
        touch($this->db);

        config(['database.connections.sqlite.database' => $this->db, 'backup.path' => $this->dir]);
        app('db')->purge('sqlite');
        Artisan::call('migrate', ['--force' => true]);

        Mail::fake();
    }

    protected function tearDown(): void
    {
        app('db')->purge('sqlite');
        foreach (glob($this->dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->dir);
        @unlink($this->db);

        parent::tearDown();
    }

    public function test_writes_a_checked_compressed_copy_and_prunes_old_ones(): void
    {
        Site::factory()->create(['name' => 'Copied site']);
        mkdir($this->dir);
        $old = $this->dir . DIRECTORY_SEPARATOR . 'apvmaga-2026-09-01.sqlite.gz';
        touch($old, Carbon::now()->subDays(20)->getTimestamp());

        $this->artisan('db:backup')->assertSuccessful();

        $copy = $this->dir . DIRECTORY_SEPARATOR . 'apvmaga-' . now()->format('Y-m-d') . '.sqlite.gz';
        $this->assertFileExists($copy);
        $this->assertFileDoesNotExist($old);
        $this->assertFileDoesNotExist(substr($copy, 0, -3));

        $restored = $this->dir . DIRECTORY_SEPARATOR . 'restored.sqlite';
        file_put_contents($restored, gzdecode(file_get_contents($copy)));
        $names = (new \PDO("sqlite:{$restored}"))->query('select name from sites')->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertSame(['Copied site'], $names);

        Mail::assertNothingSent();
    }

    public function test_a_failure_is_e_mailed_to_the_administrators_who_take_alerts(): void
    {
        User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => 'active', 'email' => 'admin@example.test']);
        User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => 'active', 'email' => 'quiet@example.test', 'receives_alerts' => false]);
        User::factory()->create(['role' => User::ROLE_AGENT, 'status' => 'active', 'email' => 'agent@example.test']);

        // The backup folder cannot be created: a file is in the way.
        file_put_contents($this->dir, 'not a folder');

        $this->artisan('db:backup')->expectsOutputToContain('Backup failed')->assertFailed();

        Mail::assertSent(BackupFailedMail::class, fn (BackupFailedMail $mail) =>
            $mail->hasTo('admin@example.test') && !$mail->hasTo('quiet@example.test') && !$mail->hasTo('agent@example.test'));

        unlink($this->dir);
    }
}

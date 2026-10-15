<?php

// Daily database copy (php artisan db:backup, scheduled in routes/console.php).
return [
    // Where the compressed copies go. Must be writable by the web server user,
    // which runs the scheduler. Ideally a folder that is itself copied off the
    // server.
    'path' => env('BACKUP_PATH', storage_path('app/backups')),

    // Copies older than this are deleted.
    'keep_days' => (int) env('BACKUP_KEEP_DAYS', 14),

    // The syncs write every 10 minutes; the backup waits up to this long for
    // them to finish instead of failing.
    'wait_seconds' => 300,
];

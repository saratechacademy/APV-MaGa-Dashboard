# APV-MaGa — Deployment Guide

This guide covers three procedures:

- **A. Deploying an installation connected to the APV-MaGa data sources.** The server builds its sites, sensors and history from ThingsBoard and LI-COR Cloud. The database starts empty.
- **B. Moving an existing installation to another server.** The database, including accounts and manual entries, is transferred.
- **C. Updating an installation in service** to the latest version of the code. The database and settings are kept.

Commands are run as root from the project root. Commands that must run as the web server user are prefixed with `sudo -u $WEB`, where `$WEB` is that user (`www` on AAPanel, `www-data` on Debian/Ubuntu):

```bash
cd /path/to/apvmaga
WEB=www
```

## Prerequisites

- PHP 8.2 or later with the `pdo_sqlite` extension
- Composer 2
- git
- cron
- an SMTP account for outgoing e-mail
- the ThingsBoard and LI-COR Cloud credentials, provided separately by Saratech

---

## A. Deploying an installation connected to the data sources

### A1. Source code

On a server with an existing copy of the project:

```bash
sudo -u $WEB git init                     # only if the directory is not a git repository
sudo -u $WEB git remote add origin https://github.com/saratechacademy/APV-MaGa-Dashboard.git   # idem
sudo -u $WEB git fetch origin
sudo -u $WEB git reset --hard origin/main
```

Git refuses to run as root in a directory owned by another user, hence `sudo -u $WEB`.

Do not use `git pull` on a clone made before 13 October 2026: the history was rewritten on that date.

On a new server, clone the repository instead and hand it over to the web server user:

```bash
git clone https://github.com/saratechacademy/APV-MaGa-Dashboard.git apvmaga
cd apvmaga && cp .env.example .env
chown -R $WEB:$WEB .
```

### A2. Files from previous versions

Files left by earlier versions under `app/`, `config/`, `routes/` or `database/migrations/` are not removed by git. A stale migration would run against the new database. List them, then move them out of the project:

```bash
sudo -u $WEB git status --short | grep -E '^\?\? (app|config|routes|database/migrations)/'

mkdir -p ~/apvmaga-old-files
sudo -u $WEB git status --short | grep -E '^\?\? (app|config|routes|database/migrations)/' | cut -c4- \
  | while read f; do mkdir -p ~/apvmaga-old-files/$(dirname "$f"); mv "$f" ~/apvmaga-old-files/"$f"; done
```

### A3. Dependencies and database

This step replaces any existing database.

```bash
composer install --no-dev --optimize-autoloader
sed -i '/^DB_/d' .env && echo "DB_CONNECTION=sqlite" >> .env
rm -f database/database.sqlite && touch database/database.sqlite
chown -R $WEB:$WEB .
grep -q '^APP_KEY=base64' .env || sudo -u $WEB php artisan key:generate --force
sudo -u $WEB php artisan config:clear
sudo -u $WEB php artisan migrate --force
sudo -u $WEB php artisan db:seed --force
```

`db:seed` creates the administrator `admin@apvmaga.com`. Its initial password is set in `database/seeders/AdminSeeder.php` and must be changed at first login.

### A4. Data source credentials

Remove the placeholder lines shipped with `.env.example`, then append the credentials file (`apvmaga-sources.env`):

```bash
sed -i '/^THINGSBOARD_/d; /^LICOR_/d' .env
grep -E '^(THINGSBOARD|LICOR)_' ~/apvmaga-sources.env >> .env
sudo -u $WEB php artisan config:clear
sudo -u $WEB php artisan thingsboard:check | grep -c " — type: "
```

The last command returns the number of devices visible on the ThingsBoard account; an error indicates invalid credentials. Delete `apvmaga-sources.env` once the values are in place.

`THINGSBOARD_LOOKBACK_DAYS` and `LICOR_LOOKBACK_DAYS` set how many days of history the initial synchronisation loads (90 and 30 in the credentials file). To start from a date instead — the day the first crop was planted, 2026-06-01 — set `THINGSBOARD_HISTORY_FROM=2026-06-01` and `LICOR_HISTORY_FROM=2026-06-01`. On an installation that already has data, fetch the missing history with `sudo -u $WEB php artisan thingsboard:sync --from=2026-06-01` and `sudo -u $WEB php artisan licor:sync --from=2026-06-01` (readings already stored are kept; each can take an hour or more).

### A5. Sites

Preview, then create the sites. The sites are defined in `database/presets/sites.json`.

```bash
sudo -u $WEB php artisan thingsboard:setup --dry-run
sudo -u $WEB php artisan licor:setup --dry-run
sudo -u $WEB php artisan thingsboard:setup
sudo -u $WEB php artisan licor:setup
```

Expected result: the sites University of The Gambia and AfriFarm are created by `thingsboard:setup`. `licor:setup` links them to their weather station ("already linked to") and creates Fass Community Garden. Both commands are idempotent.

Loading the LI-COR history takes a few minutes.

### A6. Scheduler

The scheduler runs the synchronisations (every 10 minutes), the offline alerts and the nightly backup.

```bash
echo "* * * * * $WEB cd $(pwd) && $(which php) artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/apvmaga-scheduler
sudo -u $WEB php artisan schedule:list
```

### A7. Mail

Set the SMTP account in `.env`:

```
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="APV-MaGa"
```

Then clear the configuration and send a test message:

```bash
sudo -u $WEB php artisan config:clear
sudo -u $WEB php artisan tinker --execute="Mail::raw('APV-MaGa test', fn (\$m) => \$m->to('admin@example.org')->subject('APV-MaGa test'));"
```

### A8. Backups

```bash
mkdir -p /var/backups/apvmaga && chown $WEB:$WEB /var/backups/apvmaga
echo "BACKUP_PATH=/var/backups/apvmaga" >> .env
sudo -u $WEB php artisan config:clear
sudo -u $WEB php artisan db:backup
```

Replicate `BACKUP_PATH` to off-server storage.

### A9. Production settings

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<public address>
LOG_STACK=daily
LOG_LEVEL=warning
```

Set `APP_URL` to `https://` only once the TLS certificate is installed: HTTP requests are then redirected to HTTPS. Then run `sudo -u $WEB php artisan config:clear`.

### A10. Verification

- [ ] `sudo -u $WEB php artisan thingsboard:sync` and `sudo -u $WEB php artisan licor:sync` complete without error.
- [ ] **All Sites** shows University of The Gambia, AfriFarm and Fass Community Garden.
- [ ] **Admin → Sites** shows `ok` for every ThingsBoard and LI-COR link, ten minutes after the scheduler is installed.
- [ ] `db:backup` writes `apvmaga-<date>.sqlite.gz`.
- [ ] The test e-mail is received.

User accounts are created in **Admin → Users**. Accounts, manual entries and administrative settings are specific to each installation.

---

## B. Moving an installation to another server

The full state of an installation is held in `database/database.sqlite`. Steps marked **[source]** are performed on the current server; all other steps on the target server.

Create the accounts the target team needs on the source server before step B3, since only transferred accounts will exist afterwards.

1. **Prepare the target.** Follow A1, A2, then `composer install --no-dev --optimize-autoloader`. Configure `.env` as in A4, A7, A8 and A9, with `DB_CONNECTION=sqlite`. Keep the target's `APP_KEY`.
2. **[source] Stop the scheduler**: comment out the line in `/etc/cron.d/apvmaga-scheduler`.
3. **[source] Export the database** and transfer it over SSH:
   ```bash
   sqlite3 -cmd ".timeout 300000" database/database.sqlite ".backup /root/apvmaga-transfer.sqlite"
   scp /root/apvmaga-transfer.sqlite <user>@<target>:~/
   ```
4. **Import on the target**:
   ```bash
   cp ~/apvmaga-transfer.sqlite database/database.sqlite
   chown -R $WEB:$WEB .
   sudo -u $WEB php artisan migrate --force
   sudo -u $WEB php artisan config:clear && sudo -u $WEB php artisan cache:clear && sudo -u $WEB php artisan view:clear
   ```
5. **Start the scheduler** on the target (A6).
6. **Verify** as in A10, with the existing accounts. Readings recorded during the interruption are retrieved by the first synchronisation.

**Rollback.** Remove `/etc/cron.d/apvmaga-scheduler` on the target, and uncomment the scheduler line on the source. The source resumes from its last stored reading.

The database file contains password hashes and API keys. Transfer it only over SSH.

---

## C. Updating an installation in service

The site stays online during the update; the database, accounts and settings are kept. Run the steps in order from the project root.

### C1. Back up the database

```bash
STAMP=$(date +%Y%m%d-%H%M)
sqlite3 -cmd ".timeout 300000" database/database.sqlite ".backup /root/apvmaga-before-update-$STAMP.sqlite"
cp .env /root/apvmaga-env-before-update-$STAMP
PREVIOUS=$(sudo -u $WEB git rev-parse HEAD)    # the version to return to if needed
```

### C2. Check the working copy

```bash
sudo -u $WEB git status --short
```

The output must be empty. A modified tracked file (` M`) means someone edited the server copy: compare it with `sudo -u $WEB git diff` before going on, since the update would conflict with it. Untracked files (`??`) are handled as in A2. Files the hosting panel creates in the project root (on AAPanel: `.htaccess`, `.user.ini`, `404.html`, `502.html`, `index.html`) are not part of the project: leave them in place and hide them from git once:

```bash
printf '.htaccess
.user.ini
404.html
502.html
/index.html
' >> .git/info/exclude
```

### C3. Fetch the new version

```bash
sudo -u $WEB git fetch origin
sudo -u $WEB git log --oneline HEAD..origin/main     # what the update brings
sudo -u $WEB git merge --ff-only origin/main
```

`--ff-only` stops without changing anything if the server copy has diverged from GitHub (see C2).

On a server installed before 13 October 2026, the merge always stops with `Not possible to fast-forward`: the repository history was rewritten on that date. Check with C2 that the server copy has no local edits, then replace the merge with:

```bash
sudo -u $WEB git reset --hard origin/main
```

Later updates use the merge normally.

### C4. Dependencies, database and caches

```bash
sudo -u $WEB git diff --quiet $PREVIOUS HEAD -- composer.lock || composer install --no-dev --optimize-autoloader
find . ! -user $WEB ! -name .user.ini -exec chown $WEB:$WEB {} +
sudo -u $WEB php artisan migrate --force
sudo -u $WEB php artisan optimize:clear
sudo -u $WEB php artisan config:cache
```

`composer install` runs only when the dependencies changed. The `find … chown` line hands back to `$WEB` the files composer created as root; it skips `.user.ini`, which AAPanel locks (`chown -R` stops on it with `Operation not permitted`). `migrate` applies only the new migrations; it prints `Nothing to migrate` otherwise. Run `config:cache` again after every later change to `.env`, otherwise the change is ignored.

PHP-FPM does not need restarting: new code is picked up on the next request.

### C5. Verify

- [ ] The login page opens, and **All Sites** shows every site with its status.
- [ ] One page per site opens with its charts.
- [ ] **Admin → Sites** shows `ok` for the ThingsBoard and LI-COR links ten minutes later.
- [ ] `storage/logs/laravel-$(date +%F).log` has no new `ERROR` line.

### C6. Roll back

If the new version misbehaves:

```bash
sudo -u $WEB git reset --hard $PREVIOUS
sudo -u $WEB php artisan optimize:clear && sudo -u $WEB php artisan config:cache
```

Restore the database backup only if the update ran migrations (C4 listed some) — readings and entries recorded since the backup are then lost:

```bash
cp /root/apvmaga-before-update-$STAMP.sqlite database/database.sqlite && chown $WEB:$WEB database/database.sqlite
```

### C7. Updating PHP

PHP updates (security releases) are independent of the application. On AAPanel the PHP version is shared by every site on the server, so an update affects all of them and restarts PHP-FPM; plan it and check each site afterwards. The application requires PHP 8.2 or later.

---

## Troubleshooting

| Symptom | Cause / action |
|---|---|
| `Cannot set up ThingsBoard` or `LI-COR` | Invalid credentials in `.env`; correct them, then `php artisan config:clear` |
| `no owner was found` | No active administrator; run `php artisan db:seed --force` |
| `several sites could match` | Set the ThingsBoard prefix or LI-COR station on the intended site (Admin → Sites → Edit), then rerun A5 |
| `Permission denied`, `readonly database` | `chown -R $WEB:$WEB .` |
| `detected dubious ownership in repository` | Run git as the web server user: `sudo -u $WEB git …` |
| Badge `late` in Admin → Sites | The scheduler is not running; check `/etc/cron.d/apvmaga-scheduler` and `which php` |
| `Not possible to fast-forward` (C3) | The server copy has local commits or edits; see C2. To discard them: `sudo -u $WEB git reset --hard origin/main` |
| A change to `.env` has no effect | The configuration is cached: `sudo -u $WEB php artisan config:cache` |
| Merge conflicts after `git pull` | `sudo -u $WEB git merge --abort`, then `sudo -u $WEB git fetch origin` and `sudo -u $WEB git reset --hard origin/main` |

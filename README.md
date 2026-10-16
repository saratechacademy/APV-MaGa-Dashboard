# APV-MaGa Dashboard

Monitoring platform for agrivoltaic sites.
*Plateforme de suivi des sites agrivoltaïques.*

Saratech, funded by UNU. Production: https://apvmaga.saratechniger.com

---

## Features

- Dashboards: all sites, then per site one tab per category, with cards, charts and raw data.
- Data sources: ThingsBoard and LI-COR Cloud (HOBOnet), synchronised every ten minutes; REST API for IoT devices.
- Manual entry of field measurements.
- E-mail alerts when a category or a sensor stops reporting, and when it resumes.
- Warning / critical thresholds per parameter.
- Roles: administrator, agent, observer, assigned per site.
- CSV and Excel export.
- Nightly database backup.
- User manual (`/help`, FR/EN, PDF) and technical documentation (`/developer-docs`).

Laravel 12, PHP 8.2+, SQLite or MySQL.

---

## Installation

```bash
git clone https://github.com/saratechacademy/APV-MaGa-Dashboard.git
cd APV-MaGa-Dashboard
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed            # administrator admin@apvmaga.com
php artisan serve
```

Initial password: `database/seeders/AdminSeeder.php`. Change it at first login.

Data sources: see [Connecting another installation to the same data sources](#connecting-another-installation-to-the-same-data-sources). Tests: `php artisan test`.

---

## Data sources

### ThingsBoard

Device names: `<prefix>-<zone>-<sensor>-<n>`, e.g. `UTG-APV-Humidity-1-Shadow`.

- prefix → site
- zone (`APV`, `Reference`, `General`, `Normal`) → parameter group
- sensor type → category and parameters (`config/thingsboard.php`)

`Business`, `Spare` and test devices are ignored. Tank volume requires the tank radius and number of tanks (Admin → Sites → Edit).

### LI-COR (HOBOnet)

Weather and PAR sensors, stored as 10-minute values. Stations upload every 6 to 10 hours; data is marked stale after 12 hours.

Sensor locations: Admin → Sites → Edit, "LI-COR sensor locations", one `serial = location` per line.

### Linking sites

- `thingsboard:setup` and `licor:setup` create or link the sites listed in `database/presets/sites.json`. They can be run again.
- **Admin → Data sources** links any other source to a site.

### Checking

- **Admin → Sites**: one badge per source and site, `ok`, `late` or `failed`.
- `php artisan thingsboard:check`: devices and latest values.
- `php artisan thingsboard:sync`, `php artisan licor:sync`: sync by hand.
- `THINGSBOARD_HEARTBEAT_URL` (optional): called after each successful ThingsBoard sync.

---

## Offline alerts

| Watched | Announced after |
|---|---|
| a category of a site | its offline threshold (Admin → Sites → Categories) |
| a single sensor, while its category reports | 3 × the category threshold (`ALERTS_SENSOR_THRESHOLD_MULTIPLIER`) |

- One e-mail when it stops, one when it resumes.
- Recipients: active administrators, the site's owner and assigned users whose **Receive offline alerts** box is ticked (Admin → Users).
- Requires the scheduler and `MAIL_MAILER=smtp`.

---

## Backups

`php artisan db:backup` runs nightly at 02:35 (SQLite only).

- `BACKUP_PATH`: destination, writable by the web server user (default `storage/app/backups`).
- `BACKUP_KEEP_DAYS`: retention (default 14).
- On failure, administrators receiving alerts get an e-mail.

Copy `BACKUP_PATH` to another machine. With MySQL, use the database server's backups.

---

## API for IoT devices

Header `X-API-Key`: the site's key (site page → "API Key").

| Request | Purpose |
|---|---|
| `POST /api/sensors/{site}/{category}` | send readings, e.g. `{"solar_output": 4.2}` |
| `GET /api/sensors/{site}/{category}/latest` | latest readings |
| `GET /api/sensors/{site}/status` | status of every category |
| `GET /api/sensors/{site}/{category}/history?from=&to=&params=` | raw history, 90 days max |
| `GET /api/sensors/{site}/{category}/stats?period=hour\|day\|week\|month&from=&to=` | aggregates |
| `GET /api/commands/{site}/{category}` | desired state of controllable switches |

`from` / `to`: `YYYY-MM-DD`. Details: `/developer-docs`.

---

## Updating a server

As the web server user (`www` on AAPanel, `www-data` on Debian/Ubuntu), from the project folder:

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear && php artisan cache:clear && php artisan view:clear
```

Production `.env`:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<public address>
LOG_STACK=daily
LOG_LEVEL=warning
```

Cron, every minute:

```
* * * * * www cd /path/to/the/project && php artisan schedule:run >> /dev/null 2>&1
```

---

## Connecting another installation to the same data sources

Every command is run on the server, in a terminal, from the project folder (the one containing `artisan`). Copy each block as it is.

### Step 1 — Open the project folder and name the web server user

```bash
cd /path/to/the/project
WEB=www          # AAPanel: www — Debian/Ubuntu: www-data
```

Keep this terminal open until the end: the next steps use `$WEB`.

### Step 2 — Check the requirements

```bash
php -v
php -m | grep -i pdo_sqlite
composer --version
git --version
```

Expected: PHP 8.2 or later; `pdo_sqlite`; a Composer and a git version.

### Step 3 — Download the new version

If the project is already a git clone:

```bash
git fetch origin
git reset --hard origin/main
```

If the project was copied without git:

```bash
git init
git remote add origin https://github.com/saratechacademy/APV-MaGa-Dashboard.git
git fetch origin
git reset --hard origin/main
```

Do not use `git pull`. Expected: `HEAD is now at …`. `.env`, `storage/` and `vendor/` are kept.

### Step 4 — Move out the files of the old version

```bash
git status --short | grep -E '^\?\? (app|config|routes|database/migrations)/'
```

If this lists files, move them out of the project:

```bash
mkdir -p ~/apvmaga-old-files
git status --short | grep -E '^\?\? (app|config|routes|database/migrations)/' | cut -c4- | while read f; do mkdir -p ~/apvmaga-old-files/$(dirname "$f"); mv "$f" ~/apvmaga-old-files/"$f"; done
```

Expected: running the first command again lists nothing.

### Step 5 — Install the dependencies and create a new, empty database

The previous database is replaced.

```bash
composer install --no-dev --optimize-autoloader
sed -i '/^DB_/d' .env
echo "DB_CONNECTION=sqlite" >> .env
rm -f database/database.sqlite
touch database/database.sqlite
chown -R $WEB:$WEB storage bootstrap/cache database
sudo -u $WEB php artisan config:clear
sudo -u $WEB php artisan migrate --force
sudo -u $WEB php artisan db:seed --force
```

Expected: every migration `DONE`, then `DatabaseSeedersAdminSeeder … DONE`. The administrator account is `admin@apvmaga.com`; its password is in `database/seeders/AdminSeeder.php` (change it at first login).

### Step 6 — Add the ThingsBoard and LI-COR credentials

Copy the settings file received from Saratech to your home folder as `~/apvmaga-sources.env`, then:

```bash
sed -i '/^THINGSBOARD_/d; /^LICOR_/d' .env
grep -E '^(THINGSBOARD|LICOR)_' ~/apvmaga-sources.env >> .env
sudo -u $WEB php artisan config:clear
sudo -u $WEB php artisan thingsboard:check | grep -c " — type: "
```

Expected: the last command prints a number of devices (around 90). An error here means a wrong ThingsBoard URL or password in `.env`.

### Step 7 — Preview the sites

```bash
sudo -u $WEB php artisan thingsboard:setup --dry-run
sudo -u $WEB php artisan licor:setup --dry-run
```

Nothing is changed. Expected:

```
UTG: creating site "University of The Gambia" — 16 device(s).
AfriFarm: creating site "AfriFarm" — 13 device(s).
Dry run: nothing was changed.
22490036: creating site "University of The Gambia" — 30 sensor(s).
22490035: creating site "AfriFarm" — 30 sensor(s).
22490037: creating site "Fass Community Garden" — 30 sensor(s).
Dry run: nothing was changed.
```

`linking existing site` or `already linked to` instead of `creating site`: the site already exists and will be used.

### Step 8 — Create the sites and load the history

```bash
sudo -u $WEB php artisan thingsboard:setup
sudo -u $WEB php artisan licor:setup
```

Expected, one line per site and no error, e.g.:

```
University of The Gambia (22490036): 30 sensor(s), … reading(s) stored, 0 rejected, 30 parameter(s) created.
```

The LI-COR history takes a few minutes.

### Step 9 — Start the scheduler

```bash
sudo bash -c "echo '* * * * * $WEB cd $(pwd) && $(which php) artisan schedule:run >> /dev/null 2>&1' > /etc/cron.d/apvmaga-scheduler"
sudo -u $WEB php artisan schedule:list
```

Expected: `thingsboard:sync`, `licor:sync`, `sites:check-offline` and `db:backup` are listed.

### Step 10 — E-mail settings

```bash
nano .env
```

Set these lines with your SMTP account, then save (Ctrl+O, Enter, Ctrl+X):

```
MAIL_MAILER=smtp
MAIL_HOST=…
MAIL_PORT=587
MAIL_USERNAME=…
MAIL_PASSWORD=…
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=…
MAIL_FROM_NAME="APV-MaGa"
```

Then send a test, with your own address:

```bash
sudo -u $WEB php artisan config:clear
sudo -u $WEB php artisan tinker --execute="Mail::raw('APV-MaGa test', fn (\$m) => \$m->to('you@example.org')->subject('APV-MaGa test'));"
```

Expected: the e-mail arrives within a minute.

### Step 11 — Backup folder

```bash
mkdir -p /var/backups/apvmaga
chown $WEB:$WEB /var/backups/apvmaga
echo "BACKUP_PATH=/var/backups/apvmaga" >> .env
sudo -u $WEB php artisan config:clear
sudo -u $WEB php artisan db:backup
```

Expected: `Backup written: /var/backups/apvmaga/apvmaga-<date>.sqlite.gz`.

### Step 12 — Check

```bash
sudo -u $WEB php artisan thingsboard:sync
sudo -u $WEB php artisan licor:sync
```

Expected: one line per site, no error.

Then, in a browser:

| Page | Expected |
|---|---|
| Log in, **All Sites** | University of The Gambia, AfriFarm, Fass Community Garden |
| **Admin → Sites**, after ten minutes | ThingsBoard and LI-COR badges `ok` |

### Step 13 — Delete the settings file

```bash
rm ~/apvmaga-sources.env
```

### If a command fails

| Message | Fix |
|---|---|
| `no owner was found` | `sudo -u $WEB php artisan db:seed --force` |
| `Cannot set up ThingsBoard` / `LI-COR` | check the credentials in `.env` (step 6), then `sudo -u $WEB php artisan config:clear` |
| `Permission denied` / `readonly database` | `chown -R $WEB:$WEB storage bootstrap/cache database` |

User accounts, manual entries and admin changes are not shared with the Saratech server.

---

## Moving the installation to another server

Moves everything, accounts included, by copying `database/database.sqlite`.

- **[old]**: step done on the current server; other steps on the new server.
- The database copy contains secrets: transfer it with `scp`.
- Create the new team's accounts on the current server before step 6.

```bash
cd /path/to/the/project
WEB=www        # www-data on Debian/Ubuntu
```

**Prepare the new server**

1. Save the current state:
   ```bash
   cp .env ~/apvmaga-env-before-transfer
   cp database/database.sqlite ~/apvmaga-db-before-transfer.sqlite   # or mysqldump for MySQL
   ```
2. Check `php -v` (8.2+), `php -m | grep -i sqlite` (`pdo_sqlite`), `composer --version`.
3. Update the code and move out the old files: steps 3 and 4 of the previous section, then `composer install --no-dev --optimize-autoloader`.
4. In `.env`: the ThingsBoard and LI-COR lines, `DB_CONNECTION=sqlite` (delete the other `DB_…` lines), the production settings, `BACKUP_PATH`, `MAIL_…`. Keep `APP_KEY`.

**Switch over**

5. **[old]** Comment out the scheduler line in the cron.
6. **[old]** Copy and send the database:
   ```bash
   sqlite3 -cmd ".timeout 300000" database/database.sqlite ".backup /root/apvmaga-transfer.sqlite"
   scp /root/apvmaga-transfer.sqlite <user>@<new server>:~/
   ```
7. Install it:
   ```bash
   cp ~/apvmaga-transfer.sqlite database/database.sqlite
   chown -R $WEB:$WEB database storage bootstrap/cache
   sudo -u $WEB php artisan migrate --force
   sudo -u $WEB php artisan config:clear && sudo -u $WEB php artisan cache:clear && sudo -u $WEB php artisan view:clear
   ```
8. Start the scheduler, as root:
   ```bash
   echo "* * * * * $WEB cd $(pwd) && php artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/apvmaga-scheduler
   ```

**Check**

| Check | Expected |
|---|---|
| `sudo -u $WEB php artisan thingsboard:sync`, `licor:sync` | no error |
| `sudo -u $WEB php artisan db:backup` | `Backup written: …` |
| Log in | usual accounts and passwords work |
| **Admin → Sites**, after ten minutes | badges `ok` |
| "Forgot password?" | the e-mail arrives |

**Rollback**

- New server: restore `~/apvmaga-env-before-transfer` as `.env` and the saved database, run `sudo -u $WEB php artisan migrate --force`, delete `/etc/cron.d/apvmaga-scheduler`.
- Current server: uncomment the scheduler line.

---

## Contact

saratechacademy@gmail.com

# APV-MaGa Dashboard

Monitoring platform for agrivoltaic sites: sensor data, weather, irrigation, water and field measurements, on one dashboard.
*Plateforme de suivi des sites agrivoltaïques : capteurs, météo, irrigation, eau et mesures de terrain, sur un seul tableau de bord.*

Developed by **Saratech**, funded by **UNU (United Nations University)**. Production: https://apvmaga.saratechniger.com

---

## Features

- **Multi-site dashboards** — an overview of all sites, and per site one tab per category (Irrigation, Water, Weather, Solar, Agriculture…) with cards, charts and raw data; periods 1H / 6H / 24H / 7D or custom.
- **Automatic data sources** — ThingsBoard (soil probes, valves, flow meters, tank sensors) and LI-COR Cloud (HOBOnet weather stations and PAR sensors), synchronised every ten minutes; IoT devices can also post through a REST API.
- **Manual entry** — field measurements (plants, harvest) entered by agents.
- **Offline alerts** — e-mail when part of a site, or a single sensor, stops reporting, and when it comes back.
- **Thresholds** — warning / critical levels and valid ranges per parameter, shown on the cards.
- **Roles** — administrator, agent and observer, assigned per site.
- **Export** — CSV and Excel, per category, per site or across all sites.
- **Nightly backup** of the database, with an e-mail if it fails.
- **Help** — user manual (`/help`, FR/EN, also as PDF) and technical documentation (`/developer-docs`).

Built with Laravel 12 (PHP 8.2+), Blade and Chart.js, on SQLite (MySQL also supported).

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
php artisan db:seed            # creates the first administrator, admin@apvmaga.com
php artisan serve
```

The administrator's initial password is in `database/seeders/AdminSeeder.php`: change it at first login.

To connect the data sources, follow [Connecting another installation to the same data sources](#connecting-another-installation-to-the-same-data-sources). Tests: `php artisan test`.

---

## Data sources

### ThingsBoard

Devices are named `<prefix>-<zone>-<sensor>-<n>`, e.g. `UTG-APV-Humidity-1-Shadow`:

- the **prefix** selects the site (`UTG`, `AfriFarm`…);
- the **zone** (`APV`, `Reference`, `General`, `Normal`) becomes the parameter group;
- the **sensor type** selects the category and parameters (`config/thingsboard.php`).

`Business`, `Spare` and test devices are ignored. The tank volume is computed from the water height and the site's tank radius and number of tanks (Admin → Sites → Edit); without them, no volume is shown. The daily water use is computed from the flow meter.

### LI-COR (HOBOnet)

Weather stations (temperature, humidity, dew point, wind, gusts, wind direction, rain, pressure) and PAR sensors. The 1-minute readings are stored as 10-minute values (sum for rain, maximum for gusts, vector mean for wind direction).

- Stations upload in batches every 6 to 10 hours: data appears that long after it is measured, and is only marked stale after 12 hours.
- Each location (Reference, Under PV, Between PV, Module edge) has its own sensors. Weather cards are grouped by location, and charts compare the locations. Sensor locations are set per site in Admin → Sites → Edit ("LI-COR sensor locations", one `serial = location` per line).

### Linking sites

- `database/presets/sites.json` lists the Gambian sites with their ThingsBoard prefix, LI-COR station, coordinates, tank and sensor locations. `php artisan thingsboard:setup` and `php artisan licor:setup` create or link these sites. Both can be run again: they only add what is missing, and never overwrite what was changed in the admin.
- **Admin → Data sources** lists what the ThingsBoard and LI-COR accounts expose, and links any source to a site in one click.
- Parameters created by a sync can be renamed, regrouped, given thresholds or deactivated like any other.

### Checking

- **Admin → Sites** shows one badge per source and site: `ok`, `late` (no successful sync for a while — usually the cron is not running) or `failed` (hover for the error).
- `php artisan thingsboard:check` lists every ThingsBoard device with its latest values.
- `php artisan thingsboard:sync` and `php artisan licor:sync` run a sync by hand.
- Optional: `THINGSBOARD_HEARTBEAT_URL` (healthchecks.io, Uptime Kuma…) is called after each successful ThingsBoard sync, to be warned when syncs stop.

---

## Offline alerts

- **Category**: an e-mail goes out when a category of a site (Irrigation, Water, Weather…) sends nothing for longer than its offline threshold (Admin → Sites → Categories), and another when data comes back.
- **Single sensor**: a sensor that stops while the rest of its category still reports is announced after three times the category threshold (3 h for ThingsBoard, 36 h for LI-COR; `ALERTS_SENSOR_THRESHOLD_MULTIPLIER`).
- **Recipients**: active administrators, the site's owner and the users assigned to it — among those whose **Receive offline alerts** box is ticked (Admin → Users). The box is ticked by default for administrators and agents.
- **Frequency**: one message when it stops, one when it resumes, nothing in between.
- **Not announced**: what never reported, deactivated categories or sites, manual parameters, and silences caused by the platform's own link to ThingsBoard or LI-COR failing (shown by the badges instead).

Needs the scheduler and `MAIL_MAILER=smtp` with an SMTP account in `.env`.

---

## Backups

With SQLite, the whole platform is the file `database/database.sqlite`.

- **Nightly copy**: at 02:35 the scheduler runs `php artisan db:backup`. It writes a checked, compressed copy to `BACKUP_PATH` (default `storage/app/backups`) and keeps it `BACKUP_KEEP_DAYS` days (default 14). If it fails, the administrators who receive alerts get an e-mail.
- **Requirements**: `BACKUP_PATH` must be writable by the web server user; copy that folder to another machine as well.
- **MySQL**: use the database server's own backups.

---

## API for IoT devices

Each site has its own API key (site page → "API Key" tab), sent in the `X-API-Key` header. Requests are rate-limited.

| Request | Purpose |
|---|---|
| `POST /api/sensors/{site}/{category}` | send readings, e.g. `{"solar_output": 4.2, "panel_temperature": 42.3}` |
| `GET /api/sensors/{site}/{category}/latest` | latest readings |
| `GET /api/sensors/{site}/status` | status of every category |
| `GET /api/sensors/{site}/{category}/history?from=&to=&params=` | raw history, 90 days at most |
| `GET /api/sensors/{site}/{category}/stats?period=hour\|day\|week\|month&from=&to=` | averages, minimum, maximum, sum |
| `GET /api/commands/{site}/{category}` | desired state of controllable switches (valves, pumps…) |

`from` and `to` are dates (`YYYY-MM-DD`) and include the whole day. Details and examples are on `/developer-docs`.

---

## Updating a server

Run as the web server user (`sudo -u www …` on AAPanel, `sudo -u www-data …` on Debian/Ubuntu), from the project folder:

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear && php artisan cache:clear && php artisan view:clear
```

**Production `.env`**:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<public address>
LOG_STACK=daily
LOG_LEVEL=warning
```

When `APP_URL` is `https://`, pages requested over `http://` are redirected; device `POST`s are not.

**Scheduler**: the cron must run it every minute, as the web server user. It drives the syncs, the alerts and the backup:

```
* * * * * www cd /path/to/the/project && php artisan schedule:run >> /dev/null 2>&1
```

---

## Connecting another installation to the same data sources

Use this to set up a new installation, or to give another server the same sites as the Saratech server without copying its database. Several installations can read the same ThingsBoard and LI-COR accounts at the same time.

**You need**

- the ThingsBoard and LI-COR accounts (a settings file from Saratech; it contains passwords: keep it private, delete it once used);
- PHP 8.2+, Composer and git;
- an active administrator account, which becomes the owner of the sites created (on a new installation: `php artisan db:seed`).

Commands are run from the project folder, as the web server user.

**1. Update the code**

```bash
git fetch origin
git reset --hard origin/main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
```

- **Older clone**: do not use `git pull` on a clone made before 13 October 2026; the history was rewritten and a pull ends in merge conflicts.
- **Copied without git**: first run `git init && git remote add origin https://github.com/saratechacademy/APV-MaGa-Dashboard.git`.

**2. Add the accounts to `.env`**, then `php artisan config:clear`:

```
THINGSBOARD_URL=…
THINGSBOARD_USERNAME=…
THINGSBOARD_PASSWORD=…
LICOR_API_TOKEN=…
THINGSBOARD_LOOKBACK_DAYS=90
LICOR_LOOKBACK_DAYS=30
```

The last two lines set how many days of history the first sync loads. 90 and 30 cover at least what the Saratech server holds (ThingsBoard since 9 August 2026, LI-COR since 5 October 2026).

**3. Create the sites and load the history**

```bash
php artisan thingsboard:setup --dry-run     # preview, nothing changed
php artisan thingsboard:setup
php artisan licor:setup --dry-run
php artisan licor:setup
```

Expected:

```
UTG: creating site "University of The Gambia" — 16 device(s).
AfriFarm: creating site "AfriFarm" — 13 device(s).
22490037: creating site "Fass Community Garden" — 30 sensor(s).
University of The Gambia (22490036): 30 sensor(s), … reading(s) stored, 0 rejected, 30 parameter(s) created.
```

A site that already exists under the same name is linked instead ("linking existing site").

**4. Keep the data flowing**

- Scheduler, as root, from the project folder:
  ```bash
  WEB=www        # www-data on Debian/Ubuntu
  echo "* * * * * $WEB cd $(pwd) && php artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/apvmaga-scheduler
  ```
  If cron does not find `php`, use the full path given by `which php`.
- `MAIL_MAILER=smtp` and the `MAIL_…` settings of your SMTP account.
- `BACKUP_PATH=` a folder outside `public/`, writable by the web server user (`mkdir -p /var/backups/apvmaga && chown $WEB:$WEB /var/backups/apvmaga`).
- `php artisan config:clear`.

**5. Check**

| Check | Expected |
|---|---|
| Log in, open **All Sites** | University of The Gambia, AfriFarm and Fass Community Garden, with tiles and charts |
| **Admin → Sites**, ten minutes later | ThingsBoard (UTG, AfriFarm) and LI-COR (all three) badges `ok` |
| `php artisan db:backup` | `Backup written: …/apvmaga-<date>.sqlite.gz` |
| "Forgot password?" with the address of an account whose mailbox you can read | the e-mail arrives |

**Not shared between installations**: user accounts, manual entries and changes made in the admin. Each installation sends its own alerts, so someone with an account on both receives them twice.

---

## Moving the installation to another server

This moves everything, including accounts, manual entries and admin settings. Everything is in `database/database.sqlite`, so it means updating the code on the new server and copying that file there. No sensor needs reconfiguring.

- **Who does what**: steps marked **[old]** are done on the current server; all others on the new server.
- **Secrets**: the database copy and the settings file contain secrets; send them server to server (`scp`), never by plain e-mail.
- **Accounts**: create the new team's accounts on the current server before step 6, so they can log in afterwards.

Commands are run from the project folder, with `WEB` set to the web server user:

```bash
cd /path/to/the/project
WEB=www        # www-data on Debian/Ubuntu
```

**Prepare the new server** (no interruption)

1. Save the current state:
   ```bash
   cp .env ~/apvmaga-env-before-transfer
   sqlite3 database/database.sqlite ".backup $HOME/apvmaga-db-before-transfer.sqlite"   # or mysqldump for MySQL
   ```
2. Check: `php -v` is 8.2 or later; `php -m | grep -i sqlite` lists `pdo_sqlite`; `composer --version` works; the web root points to `public/`.
3. Update the code as in step 1 of the previous section. Then run `git status --short`. Any `??` file under `app/`, `config/`, `routes/` or `database/migrations/` belongs to an older version: move it out of the project. An old migration left there would run against the transferred database.
4. In `.env`, set:
   - the ThingsBoard and LI-COR lines of the settings file;
   - `DB_CONNECTION=sqlite`, and delete the other `DB_…` lines;
   - the production settings of [Updating a server](#updating-a-server), with `APP_URL` set only once the HTTPS certificate is installed;
   - `BACKUP_PATH` and `MAIL_…`.

   Keep the existing `APP_KEY`.

**Switch over** (under an hour; data measured meanwhile is fetched afterwards)

5. **[old]** Comment out the scheduler line in the cron, so the two servers never run at the same time.
6. **[old]** Copy the database and send it:
   ```bash
   sqlite3 -cmd ".timeout 300000" database/database.sqlite ".backup /root/apvmaga-transfer.sqlite"
   scp /root/apvmaga-transfer.sqlite <user>@<new server>:~/
   ```
7. Put it in place:
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
| `sudo -u $WEB php artisan thingsboard:sync`, then `licor:sync` | one line per site, no error |
| `sudo -u $WEB php artisan db:backup` | `Backup written: …` |
| Log in | the usual accounts and passwords work |
| **Admin → Sites**, ten minutes later | every badge `ok` |
| "Forgot password?" | the e-mail arrives |

**If something goes wrong**, roll back:

- **New server**: restore `~/apvmaga-env-before-transfer` as `.env` and the saved database, run `sudo -u $WEB php artisan migrate --force`, then delete `/etc/cron.d/apvmaga-scheduler`.
- **Current server**: uncomment the scheduler line. It catches up on the data it missed at its next sync.

---

## Contact

**Saratech Academy** — saratechacademy@gmail.com

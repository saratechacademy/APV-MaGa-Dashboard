# APV-MaGa Dashboard

Monitoring platform for agrivoltaic research sites, developed by Saratech with funding from the United Nations University (UNU).

It centralises sensor telemetry, weather and light measurements, and field observations for several sites, and alerts the people concerned when a site or a sensor stops reporting.

Production: https://apvmaga.saratechniger.com

## Features

- Overview of all sites and per-site dashboards by category (irrigation, water, weather, solar, agriculture), with charts and raw data
- Automatic synchronisation with ThingsBoard and LI-COR Cloud (HOBOnet) every 10 minutes
- REST API for IoT devices
- Manual entry of field measurements
- E-mail alerts on loss and recovery of data, per category and per sensor
- Warning and critical thresholds per parameter
- Role-based access (administrator, agent, observer), assigned per site
- CSV and Excel export
- Nightly database backup
- User manual (FR/EN) and technical documentation in the application

## Stack

Laravel 12 · PHP 8.2+ · SQLite · Blade · Chart.js

## Installation

```bash
git clone https://github.com/saratechacademy/APV-MaGa-Dashboard.git
cd APV-MaGa-Dashboard
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed
php artisan serve
```

`db:seed` creates the administrator `admin@apvmaga.com`; its initial password is set in `database/seeders/AdminSeeder.php`.

Production deployment, connection to the data sources and server migration are described in the [Deployment Guide](docs/deployment-guide.md).

## Configuration

Project-specific variables in `.env`:

| Variable | Purpose | Default |
|---|---|---|
| `THINGSBOARD_URL`, `THINGSBOARD_USERNAME`, `THINGSBOARD_PASSWORD` | ThingsBoard account | — |
| `THINGSBOARD_LOOKBACK_DAYS` | History loaded by the first synchronisation | 7 |
| `THINGSBOARD_HEARTBEAT_URL` | URL called after each successful synchronisation (monitoring) | — |
| `LICOR_API_TOKEN` | LI-COR Cloud API token | — |
| `LICOR_LOOKBACK_DAYS` | History loaded by the first synchronisation | 7 |
| `ALERTS_SENSOR_THRESHOLD_MULTIPLIER` | Delay before a single silent sensor is reported, as a multiple of its category threshold | 3 |
| `BACKUP_PATH` | Destination of the nightly backups | `storage/app/backups` |
| `BACKUP_KEEP_DAYS` | Backup retention, in days | 14 |

Outgoing e-mail uses the standard Laravel `MAIL_*` settings.

## Data sources

**ThingsBoard.** Device names follow `<prefix>-<zone>-<sensor>-<n>` (e.g. `UTG-APV-Humidity-1-Shadow`):

- the prefix identifies the site;
- the zone (`APV`, `Reference`, `General`, `Normal`) becomes the parameter group;
- the sensor type determines the category and parameters, as defined in `config/thingsboard.php`.

**LI-COR Cloud.** Weather and PAR sensors are aggregated into 10-minute values and grouped by location (Reference, Under PV, Between PV, Module edge). Stations upload every 6 to 10 hours.

**Site provisioning.** Sites are defined in `database/presets/sites.json` and created by `thingsboard:setup` and `licor:setup`. Other sources are linked from **Admin → Data sources**.

## Operations

The scheduler must run every minute:

```
* * * * * www cd /path/to/apvmaga && php artisan schedule:run >> /dev/null 2>&1
```

| Command | Purpose | Scheduled |
|---|---|---|
| `thingsboard:sync` | Synchronise ThingsBoard telemetry | every 10 min |
| `licor:sync` | Synchronise LI-COR stations | every 10 min |
| `sites:check-offline` | Send loss / recovery alerts | every 10 min |
| `db:backup` | Back up the database | daily, 02:35 |
| `thingsboard:check` | List ThingsBoard devices and latest values | — |
| `thingsboard:setup`, `licor:setup` | Create or link the preset sites | — |

Synchronisation status is shown per site in **Admin → Sites**.

## Updating

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear && php artisan cache:clear && php artisan view:clear
```

Run as the web server user.

## API

Devices authenticate with their site's API key in the `X-API-Key` header.

| Endpoint | Description |
|---|---|
| `POST /api/sensors/{site}/{category}` | Submit readings |
| `GET /api/sensors/{site}/{category}/latest` | Latest readings |
| `GET /api/sensors/{site}/status` | Status of each category |
| `GET /api/sensors/{site}/{category}/history` | Raw history (`from`, `to`, `params`; 90 days max.) |
| `GET /api/sensors/{site}/{category}/stats` | Aggregates (`period`: hour, day, week, month) |
| `GET /api/commands/{site}/{category}` | Desired state of controllable actuators |

Full reference: `/developer-docs` in the application.

## Testing

```bash
php artisan test
```

## Contact

Saratech — saratechacademy@gmail.com

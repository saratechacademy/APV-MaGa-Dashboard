# APV-MaGa Dashboard
**Agrivoltaic monitoring platform for solar, water, irrigation, weather and agricultural data collection, visualization and management across multiple field sites in West Africa.**
**Plateforme de monitoring agrivoltaïque pour la collecte, visualisation et gestion des données solaires, hydriques, d'irrigation, météorologiques et agricoles sur plusieurs sites de terrain en Afrique de l'Ouest.**

---

## Overview

APV-MaGa is a full-stack web application built with Laravel for monitoring agrivoltaic installations. It enables real-time data collection from IoT sensors and modules, manual data entry by field agents, remote control of field equipment (valves, pumps, cooling fans), and dynamic visualization through configurable dashboards.

Developed by **Saratech**, funded by **UNU (United Nations University)**.

---

## Features

- **Multi-site monitoring** — manage multiple agrivoltaic field sites, each with its own categories, parameters, charts and API key; sites can be duplicated (categories, parameters, groups and charts included) to speed up onboarding similar installations
- **Real-time sensor data** — receive data from IoT sensors and modules via REST API, with per-parameter min/max validation at ingestion and rate limiting to protect against flooding
- **Remote actuator control** — toggle switches (valves, pumps, cooling fans) remotely, with sync status tracking (Synced / Pending / No device report / Stale)
- **Parameter groups & alert thresholds** — organize related parameters into color-coded, drag-and-drop-orderable groups; configure warning/critical thresholds per parameter with direction-aware alerting (below or above the threshold, depending on what "bad" means for that reading)
- **Offline detection** — configurable per-category threshold; dashboards flag parameters as "No data" or "Stale" when devices stop reporting
- **Manual data entry** — field agents can input agricultural, water, irrigation or weather readings (crop yield, plant health, tank levels, etc.), with saved records and category-specific CSV/Excel export
- **Dynamic dashboards** — configurable charts per site and category, with 1H / 6H / 24H / 7D presets plus a custom date-range picker for any historical window
- **Historical data & statistics API** — query raw history or aggregated stats (avg/min/max/sum/count) over hour/day/week/month periods
- **Role-based access** — admin, agent, and observer roles, with per-site assignment; observers get read + export access scoped to their assigned sites
- **Email notifications** — account creation, approval, site access, and password reset emails (branded)
- **Data export** — export readings to CSV or Excel, per site, per category, or across all accessible sites, with formula-injection-safe cell encoding
- **Bilingual interface** — French and English support, including `/help` and `/docs` pages
- **Responsive design** — works on desktop and mobile, with off-canvas navigation

---

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.2)
- **Database**: MariaDB (production) / SQLite (local development)
- **Frontend**: Blade templates, Chart.js
- **Hardware**: IoT sensors and modules (ESP32, Arduino, Raspberry Pi, etc.)
- **Deployment**: Hostinger VPS (AAPanel / Nginx)

---

## Project Structure

```
app/
├── Http/Controllers/
│   ├── Api/                  # IoT sensor & actuator API endpoints
│   ├── Concerns/              # Shared traits (AuthorizesSiteAccess, ResolvesDateRange)
│   ├── ActuatorController.php
│   ├── AdminController.php
│   └── DashboardController.php
├── Mail/                      # Transactional email classes (welcome, approval, site access, reset password)
├── Models/                     # Eloquent models (Site, SiteCategory, SiteParameter, SiteParameterGroup,
│                                # SensorReading, ManualReading, ActuatorCommand, User, ...)
├── Support/                    # Small framework-agnostic helpers (e.g. SafeExport for CSV/Excel output)
└── Exports/                    # CSV/Excel export classes

resources/views/
├── admin/                       # Admin panel views
├── auth/                        # Login, register, forgot/reset password
├── dashboard/                   # Dashboard & site detail views
├── emails/                      # Email layout & templates
├── layouts/                     # Layout templates (dashboard shell, sidebar)
├── help.blade.php               # User manual (FR/EN)
└── docs.blade.php               # Technical/API documentation (FR/EN)

routes/
├── web.php                       # Web routes
└── api.php                       # API routes for IoT sensors & actuators

tests/
├── Feature/                      # HTTP-level tests (auth, admin CRUD, exports, API endpoints...)
└── Unit/                         # Pure logic tests (threshold alerting)
```

---

## Installation

### Requirements
- PHP >= 8.2
- Composer
- MariaDB 10.x (or SQLite for local development)
- Node.js & NPM

### Steps
```bash
# Clone the repository
git clone https://github.com/saratechacademy/APV-MaGa-Dashboard.git
cd APV-MaGa-Dashboard

# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Set up database
php artisan migrate
php artisan db:seed --class=DatabaseSeeder

# Build assets
npm run build

# Start server
php artisan serve
```

---

## Testing

```bash
php artisan test
```

The suite covers authentication and authorization boundaries (admin/agent/observer, per-site assignment), IoT API ingestion and querying, threshold-based alerting, manual reading validation, actuator control, and export content — see `tests/Feature` and `tests/Unit`.

---

## API Usage (IoT Sensors & Actuators)

### Authentication
All API requests require the `X-API-Key` header with the site's API key (available from the dashboard's "API Key" tab for each site). Requests are rate-limited per site to protect against flooding.

### Date ranges
`history` and `stats` accept `from`/`to` as plain dates (`YYYY-MM-DD`); each covers the full calendar day (`from` at 00:00:00, `to` at 23:59:59), so `?from=2026-05-01&to=2026-05-01` returns that entire day rather than an empty, zero-width window.

### Send sensor data
```http
POST /api/sensors/{site}/{category}
X-API-Key: your-site-api-key
Content-Type: application/json

{
  "solar_output": 4.2,
  "solar_irradiance": 850.5,
  "panel_temperature": 42.3
}
```

### Get latest readings
```http
GET /api/sensors/{site}/{category}/latest
X-API-Key: your-site-api-key
```

### Check site status (all categories)
```http
GET /api/sensors/{site}/status
X-API-Key: your-site-api-key
```

### Historical data (max 90 days)
```http
GET /api/sensors/{site}/{category}/history?from=&to=&params=&page=&per_page=
X-API-Key: your-site-api-key
```

### Aggregated statistics
```http
GET /api/sensors/{site}/{category}/stats?period=hour|day|week|month&from=&to=&params=
X-API-Key: your-site-api-key
```

### Actuator commands (switches)
```http
GET /api/commands/{site}/{category}
X-API-Key: your-site-api-key
```

Full endpoint documentation, including request/response formats and available categories/parameters per site, is available on the `/docs` page (admin access).

---

## ThingsBoard integration

Sites whose sensors report to a ThingsBoard instance (soil moisture probes, valves, flow meters, tank level/turbidity sensors) are fed automatically: every ten minutes the dashboard pulls the new telemetry and stores it like any other sensor reading.

### Setting it up on an installation

1. Update the code and database:
   ```bash
   git pull origin main
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   ```
2. Add the ThingsBoard account to `.env`, then run `php artisan config:clear`:
   ```
   THINGSBOARD_URL=http://<host>:<port>
   THINGSBOARD_USERNAME=<account e-mail>
   THINGSBOARD_PASSWORD="<password>"
   THINGSBOARD_LOOKBACK_DAYS=60
   ```
   `THINGSBOARD_LOOKBACK_DAYS` is how much history the first sync loads (default 7).
3. Preview, then run the setup:
   ```bash
   php artisan thingsboard:setup --dry-run
   php artisan thingsboard:setup
   ```
   For each site listed under `sites` in `config/thingsboard.php`, this links the existing site (matched by its name) or creates it, creates the categories, groups and parameters, and loads the history. It is safe to run again. If two existing sites could match, it stops and asks you to set the **ThingsBoard device prefix** on the right one (Admin → Sites → Edit).
4. Make the sync automatic by adding the Laravel scheduler to the server cron, run as the web server user:
   ```
   * * * * * cd /path/to/the/project && php artisan schedule:run >> /dev/null 2>&1
   ```

### Checking that it works

- **Admin → Sites** shows a badge per linked site: `ThingsBoard ok`, `late` (no successful sync for 30 minutes — usually the cron is not running) or `failed` (hover for the error).
- `php artisan thingsboard:check` tests the connection and lists every device with its latest values.
- `php artisan thingsboard:sync` runs a sync by hand.
- Optional: set `THINGSBOARD_HEARTBEAT_URL` to a monitoring URL (healthchecks.io, Uptime Kuma...) to be alerted when the sync stops.

### How devices are mapped

Devices are named `<prefix>-<zone>-<sensor>-<n>`, e.g. `UTG-APV-Humidity-1-Shadow`. The prefix selects the site; the zone (`APV`, `Reference`, `General`, `Normal`) becomes the parameter group; the sensor type selects the category and parameters. `Business`, `Spare` and test devices are ignored. The mapping lives in `config/thingsboard.php`; adding a site is one line under `sites`.

Parameters created by the sync can be renamed, regrouped, given thresholds or deactivated in the admin like any other.

---

## Sites

The platform supports an unlimited number of sites. Each site can be configured with its own:

- **Categories** (Solar, Water, Irrigation, Weather, Agriculture, or custom)
- **Parameters** — sensor or manual input, with optional actuator control (readonly/controllable switches), min/max range and warning/critical alert thresholds
- **Parameter groups** — visually group related parameters on the dashboard, reorderable by drag-and-drop or keyboard
- **Charts** — configurable per category (full/half/third width, dual-axis support)
- **Offline threshold** — per-category, used to flag stale/offline parameters
- **API key** — for IoT device authentication
- **Users** — with role-based access (admin, agent, observer) and per-site assignment

Sites can be created, edited, or duplicated (with their full category/parameter/group/chart configuration) directly from the Admin Panel without any code changes.

---

## Deployment

Production: `https://apvmaga.saratechniger.com` (Hostinger VPS, AAPanel + Nginx).

After pulling updates on the server:
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate
php artisan view:clear
php artisan config:clear
php artisan cache:clear
```

---

## License

MIT License — see [LICENSE](LICENSE) for details.

---

## Contact

**Saratech Academy**
saratechacademy@gmail.com

Funded by **UNU (United Nations University)**.
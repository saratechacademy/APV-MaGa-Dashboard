# APV-MaGa Dashboard

**Agrivoltaic monitoring platform for solar and agricultural data collection, visualization and management across multiple field sites in West Africa.**

**Plateforme de monitoring agrivoltaïque pour la collecte, visualisation et gestion des données solaires et agricoles sur plusieurs sites de terrain en Afrique de l'Ouest.**

---

## Overview

APV-MaGa is a full-stack web application built with Laravel for monitoring agrivoltaic installations. It enables real-time data collection from IoT sensors and modules, manual data entry by field agents, and dynamic visualization through configurable dashboards.

---

## Features

- **Multi-site monitoring** — manage multiple agrivoltaic field sites
- **Real-time sensor data** — receive data from IoT sensors and modules via REST API
- **Manual data entry** — field agents can input agricultural data (crop yield, plant health, etc.)
- **Dynamic dashboards** — configurable charts per site and category
- **Role-based access** — admin, site manager, and field agent roles
- **Data export** — export readings to CSV or Excel
- **Bilingual interface** — French and English support
- **Responsive design** — works on desktop and mobile

---

## Tech Stack

- **Backend**: Laravel 11 (PHP 8.2)
- **Database**: MySQL
- **Frontend**: Blade templates, Chart.js
- **Hardware**: IoT sensors and modules (ESP32, Arduino, Raspberry Pi, etc.)
- **Deployment**: VPS (AAPanel / Nginx)

---

## Project Structure

```
app/
├── Http/Controllers/
│   ├── Api/           # IoT sensor API endpoints
│   ├── Admin/         # Admin panel controllers
│   └── Dashboard/     # Dashboard controllers
├── Models/            # Eloquent models
└── Exports/           # CSV/Excel export classes

resources/views/
├── admin/             # Admin panel views
├── dashboard/         # Dashboard views
├── layouts/           # Layout templates
└── help.blade.php     # User manual (FR/EN)

routes/
├── web.php            # Web routes
└── api.php            # API routes for IoT sensors
```

---

## Installation

### Requirements

- PHP >= 8.2
- Composer
- MySQL 8.0+
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

## API Usage (IoT Sensors)

### Authentication

All API requests require the `X-API-Key` header with the site's API key.

### Send sensor data

```http
POST /api/sensors/data
X-API-Key: your-site-api-key
Content-Type: application/json

{
  "category": "solar",
  "readings": {
    "solar_output": 4.2,
    "solar_irradiance": 850.5,
    "panel_temperature": 42.3
  }
}
```

### Get schema

```http
GET /api/sensors/schema
X-API-Key: your-site-api-key
```

---

## Sites

The platform supports an unlimited number of sites. Each site can be configured with its own:

- **Categories** (Solar, Water, Irrigation, Weather, Agriculture, or custom)
- **Parameters** (sensor or manual input)
- **Charts** (configurable per category)
- **API key** (for IoT device authentication)
- **Users** (with role-based access)

Sites can be created and managed directly from the Admin Panel without any code changes.

---

## License

MIT License — see [LICENSE](LICENSE) for details.

---

## Contact

**Saratech Academy**  
saratechacademy@gmail.com  


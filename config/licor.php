<?php

// LI-COR Cloud (HOBOnet) integration — pulls the weather stations and PAR
// sensors of each site into APV-MaGa (see App\Services\LicorSync and the
// licor:setup / licor:sync commands).
//
// A site is linked through its "LI-COR station serial" (admin → edit site,
// Admin → Data sources, or `php artisan licor:setup` for the sites of
// database/presets/sites.json). Each sensor of the station becomes one
// parameter; where a sensor stands (Reference, Under PV...) is a fact about
// the site, kept on the site itself ("LI-COR sensor locations").
return [
    'url'   => env('LICOR_URL', 'https://api.licor.cloud/v2'),
    'token' => env('LICOR_API_TOKEN'),

    // How far back the first sync of a sensor goes.
    'lookback_days' => (int) env('LICOR_LOOKBACK_DAYS', 7),

    // HOBOnet logs every minute: ~1,440 points per sensor per day, 90 sensors
    // per site. Readings are averaged into buckets of this many minutes
    // before being stored, which keeps the SQLite database a manageable size.
    'bucket_minutes' => 10,

    // Logging interval of the stations, used to tell when a bucket is complete.
    'logging_interval_seconds' => 60,

    // Stations don't stream: they upload in batches every 6 to 10 hours
    // (measured on the three Gambian stations), so the newest reading is
    // routinely that old. A sync is "late" (badge in admin) after an hour
    // without a successful run, but a reading is "stale" only past 12 hours —
    // anything shorter declares working stations offline between two uploads.
    'late_after_minutes' => 60,
    'offline_threshold_minutes' => 720,

    // LI-COR measurement type => parameter. 'metric' is what it measures, which
    // the All Sites overview is built from (config/overview.php); a PAR sensor
    // whose location the site knows gets it appended (par_reference, par_under_pv...),
    // since light under the panels and in the open are different things.
    // 'aggregate' says how the 1-minute
    // readings of a bucket are combined: mean, max (gusts), sum (rain) or
    // circular (wind direction, where 350° and 10° average to 0°, not 180°).
    'measurements' => [
        'Temperature'    => ['category' => 'weather', 'group' => 'Weather stations', 'name' => 'Air temperature',   'metric' => 'air_temperature',   'unit' => '°C',  'chart' => 'Air temperature (°C)'],
        'RH'             => ['category' => 'weather', 'group' => 'Weather stations', 'name' => 'Relative humidity', 'metric' => 'relative_humidity', 'unit' => '%',   'chart' => 'Relative humidity (%)'],
        'Dew Point'      => ['category' => 'weather', 'group' => 'Weather stations', 'name' => 'Dew point',         'metric' => 'dew_point',         'unit' => '°C',  'chart' => 'Dew point (°C)'],
        'Wind Speed'     => ['category' => 'weather', 'group' => 'Weather stations', 'name' => 'Wind speed',        'metric' => 'wind_speed',        'unit' => 'm/s', 'chart' => 'Wind (m/s)'],
        'Gust Speed'     => ['category' => 'weather', 'group' => 'Weather stations', 'name' => 'Gust speed',        'metric' => 'gust_speed',        'unit' => 'm/s', 'chart' => 'Wind (m/s)', 'aggregate' => 'max'],
        'Wind Direction' => ['category' => 'weather', 'group' => 'Weather stations', 'name' => 'Wind direction',    'metric' => 'wind_direction',    'unit' => '°',   'chart' => 'Wind direction (°)', 'aggregate' => 'circular'],
        'Rain'           => ['category' => 'weather', 'group' => 'Weather stations', 'name' => 'Rain',              'metric' => 'rain',              'unit' => 'mm',  'chart' => 'Rain (mm)', 'aggregate' => 'sum'],
        'Pressure'       => ['category' => 'weather', 'group' => 'Weather stations', 'name' => 'Air pressure',      'metric' => 'air_pressure',      'unit' => 'hPa', 'chart' => 'Air pressure (hPa)'],
        'PAR'            => ['category' => 'solar',   'group' => 'PAR',              'name' => 'PAR',               'metric' => 'par',               'unit' => 'µmol/m²/s', 'chart' => 'PAR (µmol/m²/s)'],
    ],

    // Manual-entry agriculture parameters licor:setup adds to every site it
    // handles (the partners' "Agriculture" table).
    'agriculture_parameters' => [
        ['name' => 'Number of leaves per plant', 'unit' => 'count',   'group' => 'Plant measurements'],
        ['name' => 'Stem girth',                 'unit' => 'cm',      'group' => 'Plant measurements'],
        ['name' => 'Plant height',               'unit' => 'cm',      'group' => 'Plant measurements'],
        ['name' => 'Chlorophyll content',        'unit' => 'mg/g FM', 'group' => 'Plant measurements'],
        ['name' => 'Stand count',                'unit' => 'count',   'group' => 'Plant measurements'],
        ['name' => 'Bulb diameter',              'unit' => 'cm',      'group' => 'Harvest'],
        ['name' => 'Bulb weight',                'unit' => 'g',       'group' => 'Harvest'],
        ['name' => 'Yield',                      'unit' => 'kg/ha',   'group' => 'Harvest'],
        ['name' => 'Harvest index',              'unit' => null,      'group' => 'Harvest'],
    ],
];

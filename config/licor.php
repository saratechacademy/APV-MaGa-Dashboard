<?php

// LI-COR Cloud (HOBOnet) integration — pulls the weather stations and PAR
// sensors of each site into APV-MaGa (see App\Services\LicorSync and the
// licor:setup / licor:sync commands).
//
// A site is linked through its "LI-COR station serial" (admin → edit site,
// or `php artisan licor:setup` for every station listed under 'sites').
// Each sensor of the station becomes one parameter; the mapping below
// follows the partners' "Sensor Set Up and Parameters" document.
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

    // Stations upload their data in batches, typically 2-4 hours after it was
    // measured: a sync is "late" (badge in admin) only after an hour without
    // a successful run, but a reading is "stale" only past 6 hours.
    'late_after_minutes' => 60,
    'offline_threshold_minutes' => 360,

    // Stations that `php artisan licor:setup` links or creates, keyed by the
    // LI-COR gateway serial. 'match' finds an existing site: by its
    // ThingsBoard prefix first, then by a name containing it.
    'sites' => [
        '22490036' => ['match' => 'UTG',      'name' => 'University of The Gambia', 'country' => 'Gambia', 'latitude' => 13.2478, 'longitude' => -16.5356],
        '22490035' => ['match' => 'AfriFarm', 'name' => 'AfriFarm', 'country' => 'Gambia'],
        '22490037' => ['match' => 'Fass',     'name' => 'Fass Community Garden', 'country' => 'Gambia'],
    ],

    // LI-COR measurement type => parameter. 'aggregate' says how the 1-minute
    // readings of a bucket are combined: mean, max (gusts), sum (rain) or
    // circular (wind direction, where 350° and 10° average to 0°, not 180°).
    'measurements' => [
        'Temperature'    => ['category' => 'weather', 'group' => 'Weather stations', 'name' => 'Air temperature',   'unit' => '°C',  'chart' => 'Air temperature (°C)'],
        'RH'             => ['category' => 'weather', 'group' => 'Weather stations', 'name' => 'Relative humidity', 'unit' => '%',   'chart' => 'Relative humidity (%)'],
        'Dew Point'      => ['category' => 'weather', 'group' => 'Weather stations', 'name' => 'Dew point',         'unit' => '°C',  'chart' => 'Dew point (°C)'],
        'Wind Speed'     => ['category' => 'weather', 'group' => 'Weather stations', 'name' => 'Wind speed',        'unit' => 'm/s', 'chart' => 'Wind (m/s)'],
        'Gust Speed'     => ['category' => 'weather', 'group' => 'Weather stations', 'name' => 'Gust speed',        'unit' => 'm/s', 'chart' => 'Wind (m/s)', 'aggregate' => 'max'],
        'Wind Direction' => ['category' => 'weather', 'group' => 'Weather stations', 'name' => 'Wind direction',    'unit' => '°',   'chart' => 'Wind direction (°)', 'aggregate' => 'circular'],
        'Rain'           => ['category' => 'weather', 'group' => 'Weather stations', 'name' => 'Rain',              'unit' => 'mm',  'chart' => 'Rain (mm)', 'aggregate' => 'sum'],
        'Pressure'       => ['category' => 'weather', 'group' => 'Weather stations', 'name' => 'Air pressure',      'unit' => 'hPa', 'chart' => 'Air pressure (hPa)'],
        'PAR'            => ['category' => 'solar',   'group' => 'PAR',              'name' => 'PAR',               'unit' => 'µmol/m²/s', 'chart' => 'PAR (µmol/m²/s)'],
    ],

    // Sensor serial (without the "-1/-2/-3" channel) => where it is. Used to
    // name parameters; sensors not listed here are named by their serial.
    // Only the PAR sensors have a location in the partners' document so far.
    'locations' => [
        // UTG
        '22261662' => 'Reference', '22261663' => 'Module edge', '22261664' => 'Under PV', '22261665' => 'Between PV',
        // AfriFarm
        '22261671' => 'Reference', '22261668' => 'Module edge', '22261669' => 'Under PV', '22261670' => 'Between PV',
        // Fass
        '22261666' => 'Reference', '22261667' => 'Module edge', '22261672' => 'Under PV', '22261673' => 'Between PV',
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

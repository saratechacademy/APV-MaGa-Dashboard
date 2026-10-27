<?php

// ThingsBoard integration — pulls telemetry from the partner's ThingsBoard
// instance into APV-MaGa (see App\Services\ThingsBoardSync and the
// thingsboard:check / thingsboard:sync commands).
//
// A site is linked to ThingsBoard through its "ThingsBoard device prefix"
// (admin → edit site, Admin → Data sources, or `php artisan thingsboard:setup`
// for the sites of database/presets/sites.json). Devices there are named
// "<prefix>-<zone>-<sensor>-<n>[-<position>]", e.g. UTG-APV-Humidity-1-Shadow,
// and the mapping below follows the partners' "Sensor Set Up and Parameters"
// document for The Gambia. Devices in a zone or of a sensor type that is not
// listed here (Business, Spare, test devices...) are ignored.
return [
    'url'      => env('THINGSBOARD_URL'),
    'username' => env('THINGSBOARD_USERNAME'),
    'password' => env('THINGSBOARD_PASSWORD'),

    // How far back the first sync of a parameter goes; later syncs resume
    // from the parameter's last stored reading.
    'lookback_days' => (int) env('THINGSBOARD_LOOKBACK_DAYS', 7),

    // Or a date (YYYY-MM-DD) the first sync starts from instead, e.g. the
    // day the first crop was planted. History of an installation that already
    // has data is fetched with `--from` on the sync command.
    'history_from' => env('THINGSBOARD_HISTORY_FROM'),

    // A site whose last successful sync is older than this is flagged "late"
    // in the admin (the sync is scheduled every 10 minutes).
    'late_after_minutes' => 30,

    // Optional dead-man's-switch: a URL (healthchecks.io, Uptime Kuma, Better
    // Stack...) pinged after every fully successful sync. That service then
    // alerts when the pings stop — the only check that still works when the
    // server, its cron or the whole application is down.
    'heartbeat_url' => env('THINGSBOARD_HEARTBEAT_URL'),

    // Offline threshold given to categories the sync creates: these devices
    // report every 15-20 minutes, far slower than the 5-minute default.
    'offline_threshold_minutes' => 60,

    // Zone segment of the device name => parameter group on the dashboard.
    'zones' => [
        'APV'       => 'APV field',
        'Reference' => 'Reference field',
        'Normal'    => 'Entire farm',
        'General'   => 'Entire farm',
        // The commercial plot next to the trial, outside the comparison.
        'Business'  => 'Business field',
    ],

    // Sensor segment of the device name => category and the parameters read
    // from it. 'key' is the ThingsBoard telemetry key; 'data_type' defaults
    // to float; 'scale'/'precision' convert the raw value before storing;
    // 'tank_volume' converts a water height (cm) into litres using the site's
    // own tank geometry (admin → edit site) and is skipped for a site without one;
    // 'daily_increase_of' makes a parameter that isn't read from ThingsBoard
    // but computed: how much the named cumulative key grew each day (a flow
    // meter only reports its ever-growing total);
    // 'chart' is the dashboard chart a new parameter is added to, so each
    // chart only ever mixes values of one unit; 'metric' is what it measures,
    // which the All Sites overview is built from (config/overview.php).
    'sensors' => [
        'Humidity' => [
            'category'   => 'irrigation',
            'parameters' => [
                ['key' => 'humidity',     'name' => 'Moisture',     'unit' => '%',     'metric' => 'soil_moisture',     'chart' => 'Soil moisture (%)'],
                ['key' => 'conductivity', 'name' => 'Conductivity', 'unit' => 'µS/cm', 'metric' => 'soil_conductivity', 'chart' => 'Soil conductivity (µS/cm)'],
                ['key' => 'temperature',  'name' => 'Temperature',  'unit' => '°C',    'metric' => 'soil_temperature',  'chart' => 'Soil temperature (°C)'],
            ],
        ],
        'Flow' => [
            'category'   => 'irrigation',
            'parameters' => [
                ['key' => 'Flow_level', 'name' => 'Total flow', 'unit' => 'L', 'metric' => 'total_flow', 'chart' => 'Total flow (L)'],
                ['daily_increase_of' => 'Flow_level', 'name' => 'Daily water use', 'unit' => 'L', 'metric' => 'daily_water_use', 'chart' => 'Daily water use (L)', 'chart_type' => 'bar'],
            ],
        ],
        'Valve' => [
            'category'   => 'irrigation',
            // UTG's business-field valve is named UTG-Normal-Valve-0, where
            // the other sites use "Business".
            'zone_aliases' => ['Normal' => 'Business'],
            'parameters' => [
                ['key' => 'valve_state', 'name' => 'Valve state', 'data_type' => 'switch', 'metric' => 'valve_state'],
            ],
        ],
        'Ultrasonic' => [
            'category'   => 'water',
            'parameters' => [
                ['key' => 'tank_distance', 'name' => 'Water level', 'unit' => 'cm', 'metric' => 'water_level', 'chart' => 'Water level (cm)'],
            ],
        ],
        'Turbidity' => [
            'category'   => 'water',
            'parameters' => [
                ['key' => 'turbidity', 'name' => 'Turbidity', 'metric' => 'turbidity', 'chart' => 'Turbidity'],
            ],
        ],
        'Pressure' => [
            'category'   => 'water',
            'parameters' => [
                ['key' => 'tank_level', 'name' => 'Tank level', 'unit' => 'cm', 'metric' => 'tank_level', 'chart' => 'Water level (cm)'],
                // Volume of the tank from the measured water height, as the
                // partners compute it: π × r² × height / 1000 × number of tanks.
                ['key' => 'tank_level', 'name' => 'Tank volume', 'unit' => 'L', 'tank_volume' => true, 'precision' => 0, 'metric' => 'tank_volume', 'chart' => 'Tank volume (L)'],
            ],
        ],
    ],
];

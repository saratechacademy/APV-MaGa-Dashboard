<?php

// ThingsBoard integration — pulls telemetry from the partner's ThingsBoard
// instance into APV-MaGa (see App\Services\ThingsBoardSync and the
// thingsboard:check / thingsboard:sync commands).
//
// A site is linked to ThingsBoard through its "ThingsBoard device prefix"
// (admin → edit site). Devices there are named
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
    ],

    // Sensor segment of the device name => category and the parameters read
    // from it. 'key' is the ThingsBoard telemetry key; 'data_type' defaults
    // to float; 'scale'/'precision' convert the raw value before storing.
    'sensors' => [
        'Humidity' => [
            'category'   => 'irrigation',
            'parameters' => [
                ['key' => 'humidity',     'name' => 'Soil moisture',    'unit' => '%'],
                ['key' => 'conductivity', 'name' => 'Conductivity',     'unit' => 'µS/cm'],
                ['key' => 'temperature',  'name' => 'Soil temperature', 'unit' => '°C'],
            ],
        ],
        'Flow' => [
            'category'   => 'irrigation',
            'parameters' => [
                ['key' => 'Flow_level', 'name' => 'Total flow', 'unit' => 'L'],
            ],
        ],
        'Valve' => [
            'category'   => 'irrigation',
            'parameters' => [
                ['key' => 'valve_state', 'name' => 'State', 'data_type' => 'switch'],
            ],
        ],
        'Ultrasonic' => [
            'category'   => 'water',
            'parameters' => [
                ['key' => 'tank_distance', 'name' => 'Water level', 'unit' => 'cm'],
            ],
        ],
        'Turbidity' => [
            'category'   => 'water',
            'parameters' => [
                ['key' => 'turbidity', 'name' => 'Turbidity'],
            ],
        ],
        'Pressure' => [
            'category'   => 'water',
            'parameters' => [
                ['key' => 'tank_level', 'name' => 'Tank level', 'unit' => 'cm'],
                // Volume of the tank from the measured water height, as the
                // partners compute it: π × r² × height / 1000, r = 180 cm.
                ['key' => 'tank_level', 'name' => 'Tank volume', 'unit' => 'L', 'scale' => M_PI * 180 * 180 / 1000, 'precision' => 0],
            ],
        ],
    ],
];

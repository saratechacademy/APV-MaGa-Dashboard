<?php

// The "All Sites" overview is built from *metrics*: what a parameter measures,
// independently of its name, slug or data source. site_parameters.metric holds
// the key; the ThingsBoard and LI-COR syncs set it when they create a
// parameter, and the migration that added the column backfilled existing ones.
//
// A site has a metric when at least one of its active parameters carries it;
// several parameters with the same metric (four air-temperature sensors, six
// soil-moisture probes) are averaged.
return [
    // key => how it is shown. 'label' is the short form used on site cards.
    'metrics' => [
        'solar_output'      => ['title' => 'Solar output',        'label' => 'Solar output',  'unit' => 'kW'],
        'par_reference'     => ['title' => 'PAR (reference)',     'label' => 'PAR',           'unit' => 'µmol/m²/s'],
        'air_temperature'   => ['title' => 'Air temperature',     'label' => 'Air temp',      'unit' => '°C'],
        'relative_humidity' => ['title' => 'Relative humidity',   'label' => 'Air humidity',  'unit' => '%'],
        'soil_moisture'     => ['title' => 'Soil moisture',       'label' => 'Soil moisture', 'unit' => '%'],
        'tank_volume'       => ['title' => 'Tank volume',         'label' => 'Tank',          'unit' => 'L'],
        'tank_fill_level'   => ['title' => 'Tank fill level',     'label' => 'Tank fill',     'unit' => '%'],
        'borehole_level'    => ['title' => 'Borehole water level', 'label' => 'Borehole',     'unit' => 'm'],
        'wind_speed'        => ['title' => 'Wind speed',          'label' => 'Wind',          'unit' => 'm/s'],
    ],

    // Site card: the first 4 of these the site actually has.
    'tiles' => ['solar_output', 'air_temperature', 'soil_moisture', 'par_reference', 'tank_volume', 'tank_fill_level', 'borehole_level', 'relative_humidity', 'wind_speed'],

    // Site card sparkline: the first of these the site has.
    'sparkline' => ['solar_output', 'par_reference', 'air_temperature', 'soil_moisture'],

    // Cross-site charts: the first 4 of these that at least one site has.
    'charts' => ['solar_output', 'par_reference', 'air_temperature', 'soil_moisture', 'relative_humidity', 'tank_volume', 'borehole_level', 'wind_speed'],
];

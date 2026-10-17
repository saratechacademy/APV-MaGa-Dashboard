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
        'daily_water_use'   => ['title' => 'Water used today',    'label' => 'Water today',   'unit' => 'L'],
        'tank_fill_level'   => ['title' => 'Tank fill level',     'label' => 'Tank fill',     'unit' => '%'],
        'borehole_level'    => ['title' => 'Borehole water level', 'label' => 'Borehole',     'unit' => 'm'],
        'wind_speed'        => ['title' => 'Wind speed',          'label' => 'Wind',          'unit' => 'm/s'],
    ],

    // Every metric a parameter can be given (admin → parameter → "Measures").
    // The data-source syncs set it themselves; this list is what an admin
    // chooses from for a parameter created by hand or fed by another device.
    'catalog' => [
        'solar_output'      => 'Solar output (kW)',
        'par'               => 'PAR — location unknown',
        'par_reference'     => 'PAR — reference (open sky)',
        'par_module_edge'   => 'PAR — module edge',
        'par_under_pv'      => 'PAR — under the panels',
        'par_between_pv'    => 'PAR — between the panels',
        'air_temperature'   => 'Air temperature',
        'relative_humidity' => 'Relative humidity (air)',
        'dew_point'         => 'Dew point',
        'wind_speed'        => 'Wind speed',
        'gust_speed'        => 'Gust speed',
        'wind_direction'    => 'Wind direction',
        'rain'              => 'Rain',
        'air_pressure'      => 'Air pressure',
        'soil_moisture'     => 'Soil moisture',
        'soil_conductivity' => 'Soil conductivity',
        'soil_temperature'  => 'Soil temperature',
        'total_flow'        => 'Irrigation flow (cumulative)',
        'daily_water_use'   => 'Irrigation water used per day',
        'valve_state'       => 'Valve state',
        'tank_level'        => 'Tank water height',
        'tank_volume'       => 'Tank volume',
        'tank_fill_level'   => 'Tank fill level (%)',
        'water_level'       => 'Water level (ultrasonic)',
        'borehole_level'    => 'Borehole water level',
        'turbidity'         => 'Turbidity',
        'plant_height'               => 'Plant height (field measurement)',
        'number_of_leaves_per_plant' => 'Leaves per plant (field measurement)',
        'stem_girth'                 => 'Stem girth (field measurement)',
        'chlorophyll_content'        => 'Chlorophyll content (field measurement)',
        'stand_count'                => 'Stand count (field measurement)',
        'bulb_diameter'              => 'Bulb diameter (field measurement)',
        'bulb_weight'                => 'Bulb weight (field measurement)',
        'yield'                      => 'Yield (field measurement)',
        'harvest_index'              => 'Harvest index (field measurement)',
    ],

    // Site card: the first 4 of these the site actually has.
    'tiles' => ['solar_output', 'air_temperature', 'soil_moisture', 'par_reference', 'tank_volume', 'daily_water_use', 'tank_fill_level', 'borehole_level', 'relative_humidity', 'wind_speed'],

    // Site card sparkline: the first of these the site has.
    'sparkline' => ['solar_output', 'par_reference', 'air_temperature', 'soil_moisture'],

    // Cross-site charts: the first 4 of these that at least one site has.
    'charts' => ['solar_output', 'par_reference', 'air_temperature', 'soil_moisture', 'relative_humidity', 'tank_volume', 'borehole_level', 'wind_speed'],

    // Data expected but not connected yet: a category tab shows this notice
    // as long as the site has no active parameter with the metric, instead
    // of silently lacking it. Remove an entry once the source is connected
    // everywhere.
    'awaiting' => [
        'solar' => [
            'metric' => 'solar_output',
            'title'  => 'Photovoltaic production — coming soon',
            'text'   => 'Production, battery and consumption data from the solar installation (Victron VRM) will appear here once access to the installation is granted.',
        ],
    ],
];

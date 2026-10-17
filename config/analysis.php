<?php

// Analysis page: which quantities can be compared and how.
//
// 'aggregate'  how readings are combined in an hour or a day: mean, or sum
//              (rain falls in amounts that add up).
// 'relative'   a percentage difference makes sense (zero means "none"): yes
//              for light, wind or rain; no for temperatures, where +10 % of
//              2 °C is meaningless.
// 'daily'      on a daily step, the value shown instead of the mean, with
//              its unit (PAR → daily light integral).
// 'diurnal'    the hour-of-day profile is meaningful.
// 'about'      plain-language explanation shown with the results.
return [
    'max_days' => 92,

    'quantities' => [
        'par' => [
            'title' => 'Light (PAR)', 'aggregate' => 'mean', 'relative' => true, 'diurnal' => true,
            'daily' => ['title' => 'Daily light integral (DLI)', 'unit' => 'mol/m²/day', 'factor' => 0.0864],
            'about' => 'Photosynthetically active radiation: the light plants use. Its daily total, the daily light integral (DLI), drives crop growth. Lower values under the panels measure the shading they provide.',
        ],
        'air_temperature' => [
            'title' => 'Air temperature', 'aggregate' => 'mean', 'relative' => false, 'diurnal' => true,
            'about' => 'Air temperature near the crop. Panels shade the plants by day and slow cooling at night; the hour-of-day profile shows when each effect dominates.',
        ],
        'relative_humidity' => [
            'title' => 'Relative humidity', 'aggregate' => 'mean', 'relative' => false, 'diurnal' => true,
            'about' => 'Moisture of the air relative to saturation. Higher humidity lowers crop water demand, and raises disease risk when it stays high.',
        ],
        'dew_point' => [
            'title' => 'Dew point', 'aggregate' => 'mean', 'relative' => false, 'diurnal' => true,
            'about' => 'Temperature at which dew forms: a measure of the amount of water vapour in the air, independent of air temperature.',
        ],
        'wind_speed' => [
            'title' => 'Wind speed', 'aggregate' => 'mean', 'relative' => true, 'diurnal' => true,
            'about' => 'Average wind speed. Panel rows can slow the wind, which reduces evaporation and mechanical stress on plants.',
        ],
        'gust_speed' => [
            'title' => 'Gust speed', 'aggregate' => 'mean', 'relative' => true, 'diurnal' => true,
            'about' => 'Strongest short gusts. Useful to assess wind protection, and the mechanical load on the structure.',
        ],
        'rain' => [
            'title' => 'Rain', 'aggregate' => 'sum', 'relative' => true, 'diurnal' => false,
            'about' => 'Rainfall recorded by the gauge, summed per hour or per day.',
        ],
        'air_pressure' => [
            'title' => 'Air pressure', 'aggregate' => 'mean', 'relative' => false, 'diurnal' => false,
            'about' => 'Atmospheric pressure; falling pressure often announces rain.',
        ],
        'soil_moisture' => [
            'title' => 'Soil moisture', 'aggregate' => 'mean', 'relative' => false, 'diurnal' => true,
            'about' => 'Water content of the soil, in % of volume. Differences between plots show the combined effect of shading on evaporation and of irrigation.',
        ],
        'soil_temperature' => [
            'title' => 'Soil temperature', 'aggregate' => 'mean', 'relative' => false, 'diurnal' => true,
            'about' => 'Temperature in the root zone. Shade keeps the soil cooler in the hottest hours.',
        ],
        'soil_conductivity' => [
            'title' => 'Soil conductivity', 'aggregate' => 'mean', 'relative' => true, 'diurnal' => false,
            'about' => 'Electrical conductivity of the soil, an indicator of dissolved salts and nutrients.',
        ],
        'daily_water_use' => [
            'title' => 'Irrigation water used', 'aggregate' => 'sum', 'relative' => true, 'diurnal' => false, 'only_daily' => true,
            'about' => 'Water delivered by the irrigation system each day, from the flow meters.',
        ],
        'tank_volume' => [
            'title' => 'Tank volume', 'aggregate' => 'mean', 'relative' => false, 'diurnal' => false,
            'about' => 'Water stored in the tank.',
        ],
        'solar_output' => [
            'title' => 'Solar output', 'aggregate' => 'mean', 'relative' => true, 'diurnal' => true,
            'about' => 'Electrical power produced by the photovoltaic system.',
        ],
    ],
];

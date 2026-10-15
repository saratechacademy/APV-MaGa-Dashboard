<?php

// E-mail alerts (see App\Services\SiteOfflineMonitor and the db:backup command).
return [
    // A single sensor that goes quiet while the rest of its category keeps
    // reporting is announced once it has been silent this many times the
    // category's offline threshold: 3 h for ThingsBoard probes (60 min), 36 h
    // for LI-COR stations (12 h). Measured on the Gambian sites, isolated
    // gaps of one to three hours (lost radio packets) are routine; a shorter
    // delay would e-mail about them every week.
    'sensor_threshold_multiplier' => (int) env('ALERTS_SENSOR_THRESHOLD_MULTIPLIER', 3),
];

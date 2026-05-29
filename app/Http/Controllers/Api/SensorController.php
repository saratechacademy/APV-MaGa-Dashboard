<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\SolarReading;
use App\Models\WaterReading;
use App\Models\IrrigationReading;
use App\Models\WeatherReading;
use Illuminate\Http\Request;

class SensorController extends Controller
{
    // Vérifie la clé API et retourne le site
    private function authenticate(Request $request)
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            return null;
        }

        return Site::where('api_key', $apiKey)->first();
    }

    // POST /api/sensors/solar
    public function solar(Request $request)
    {
        $site = $this->authenticate($request);

        if (!$site) {
            return response()->json(['error' => 'Clé API invalide'], 401);
        }

        $data = $request->validate([
            'power_kw'       => 'required|numeric|min:0',
            'irradiance_wm2' => 'nullable|numeric|min:0',
            'panel_temp_c'   => 'nullable|numeric',
            'efficiency_pct' => 'nullable|numeric|min:0|max:100',
            'recorded_at'    => 'nullable|date',
        ]);

        $reading = SolarReading::create([
            'site_id'        => $site->id,
            'power_kw'       => $data['power_kw'],
            'irradiance_wm2' => $data['irradiance_wm2'] ?? null,
            'panel_temp_c'   => $data['panel_temp_c'] ?? null,
            'efficiency_pct' => $data['efficiency_pct'] ?? null,
            'recorded_at'    => $data['recorded_at'] ?? now(),
        ]);

        return response()->json([
            'success' => true,
            'site'    => $site->name,
            'data'    => $reading,
        ], 201);
    }

    // POST /api/sensors/water
    public function water(Request $request)
    {
        $site = $this->authenticate($request);

        if (!$site) {
            return response()->json(['error' => 'Clé API invalide'], 401);
        }

        $data = $request->validate([
            'borehole_level_m' => 'required|numeric|min:0',
            'tank_fill_pct'    => 'nullable|numeric|min:0|max:100',
            'min_threshold_m'  => 'nullable|numeric|min:0',
            'recorded_at'      => 'nullable|date',
        ]);

        // Calcul automatique du statut
        $status = 'ok';
        if (isset($data['min_threshold_m'])) {
            if ($data['borehole_level_m'] < $data['min_threshold_m'] * 0.8) {
                $status = 'error';
            } elseif ($data['borehole_level_m'] < $data['min_threshold_m']) {
                $status = 'warn';
            }
        }

        $reading = WaterReading::create([
            'site_id'          => $site->id,
            'borehole_level_m' => $data['borehole_level_m'],
            'tank_fill_pct'    => $data['tank_fill_pct'] ?? null,
            'min_threshold_m'  => $data['min_threshold_m'] ?? null,
            'status'           => $status,
            'recorded_at'      => $data['recorded_at'] ?? now(),
        ]);

        return response()->json([
            'success' => true,
            'site'    => $site->name,
            'data'    => $reading,
        ], 201);
    }

    // POST /api/sensors/irrigation
    public function irrigation(Request $request)
    {
        $site = $this->authenticate($request);

        if (!$site) {
            return response()->json(['error' => 'Clé API invalide'], 401);
        }

        $data = $request->validate([
            'flow_rate_lmin'      => 'nullable|numeric|min:0',
            'moisture_zone_a_pct' => 'nullable|numeric|min:0|max:100',
            'moisture_zone_b_pct' => 'nullable|numeric|min:0|max:100',
            'moisture_zone_c_pct' => 'nullable|numeric|min:0|max:100',
            'valve_v01_open'      => 'nullable|boolean',
            'valve_v02_open'      => 'nullable|boolean',
            'valve_v03_open'      => 'nullable|boolean',
            'pump_runtime_min'    => 'nullable|numeric|min:0',
            'water_collected_l'   => 'nullable|numeric|min:0',
            'recorded_at'         => 'nullable|date',
        ]);

        $reading = IrrigationReading::create([
            'site_id'             => $site->id,
            'flow_rate_lmin'      => $data['flow_rate_lmin'] ?? null,
            'moisture_zone_a_pct' => $data['moisture_zone_a_pct'] ?? null,
            'moisture_zone_b_pct' => $data['moisture_zone_b_pct'] ?? null,
            'moisture_zone_c_pct' => $data['moisture_zone_c_pct'] ?? null,
            'valve_v01_open'      => $data['valve_v01_open'] ?? false,
            'valve_v02_open'      => $data['valve_v02_open'] ?? false,
            'valve_v03_open'      => $data['valve_v03_open'] ?? false,
            'pump_runtime_min'    => $data['pump_runtime_min'] ?? null,
            'water_collected_l'   => $data['water_collected_l'] ?? null,
            'recorded_at'         => $data['recorded_at'] ?? now(),
        ]);

        return response()->json([
            'success' => true,
            'site'    => $site->name,
            'data'    => $reading,
        ], 201);
    }

    // POST /api/sensors/weather
    public function weather(Request $request)
    {
        $site = $this->authenticate($request);

        if (!$site) {
            return response()->json(['error' => 'Clé API invalide'], 401);
        }

        $data = $request->validate([
            'temp_c'         => 'nullable|numeric',
            'humidity_pct'   => 'nullable|numeric|min:0|max:100',
            'pressure_hpa'   => 'nullable|numeric|min:0',
            'wind_speed_ms'  => 'nullable|numeric|min:0',
            'wind_direction' => 'nullable|string|max:10',
            'irradiance_wm2' => 'nullable|numeric|min:0',
            'recorded_at'    => 'nullable|date',
        ]);

        $reading = WeatherReading::create([
            'site_id'        => $site->id,
            'temp_c'         => $data['temp_c'] ?? null,
            'humidity_pct'   => $data['humidity_pct'] ?? null,
            'pressure_hpa'   => $data['pressure_hpa'] ?? null,
            'wind_speed_ms'  => $data['wind_speed_ms'] ?? null,
            'wind_direction' => $data['wind_direction'] ?? null,
            'irradiance_wm2' => $data['irradiance_wm2'] ?? null,
            'recorded_at'    => $data['recorded_at'] ?? now(),
        ]);

        return response()->json([
            'success' => true,
            'site'    => $site->name,
            'data'    => $reading,
        ], 201);
    }

    // GET /api/sensors/status
    public function status(Request $request)
    {
        $site = $this->authenticate($request);

        if (!$site) {
            return response()->json(['error' => 'Clé API invalide'], 401);
        }

        return response()->json([
            'success'    => true,
            'site'       => $site->name,
            'country'    => $site->country,
            'status'     => $site->status,
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
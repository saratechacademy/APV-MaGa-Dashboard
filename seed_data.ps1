# APV-MaGa - Continuous Random Data via API
# Usage: .\seed_data.ps1 -ApiKey "apv-xxxx" -Interval 5 -Count 0

param(
    [string]$ApiKey    = "apv-0hHGSELuPP3uaL8qMqBR7yk4waas7SEU",
    [string]$BaseUrl   = "http://127.0.0.1:8000",
    [int]$Interval     = 5,
    [int]$Count        = 0
)

$headers = @{
    "X-API-Key"    = $ApiKey
    "Content-Type" = "application/json"
    "Accept"       = "application/json"
}

function Get-SolarFactor {
    $hour = (Get-Date).Hour
    $factor = [Math]::Max(0, [Math]::Sin(($hour - 6) * [Math]::PI / 12))
    return $factor
}

function Get-Random-Double($min, $max) {
    return [Math]::Round($min + (Get-Random -Minimum 0 -Maximum 100) / 100.0 * ($max - $min), 2)
}

$iteration = 0
Write-Host "🌱 APV-MaGa API Data Seeder" -ForegroundColor Green
Write-Host "🔑 API Key: $($ApiKey.Substring(0,12))..." -ForegroundColor Gray
Write-Host "🌐 URL: $BaseUrl" -ForegroundColor Gray
Write-Host "⏱  Interval: ${Interval}s | Count: $(if($Count -eq 0){'∞'}else{$Count})" -ForegroundColor Gray
Write-Host "Press Ctrl+C to stop.`n" -ForegroundColor Gray

while ($Count -eq 0 -or $iteration -lt $Count) {

    $solarFactor = Get-SolarFactor

    # ── Solar ────────────────────────────────────────
    try {
        $solar = @{
            power_kw       = [Math]::Round($solarFactor * 5.5 * (0.85 + (Get-Random -Min -10 -Max 10)/100), 2)
            irradiance_wm2 = [Math]::Round($solarFactor * 900 + (Get-Random -Min -50 -Max 50), 1)
            panel_temp_c   = [Math]::Round(25 + $solarFactor * 35 + (Get-Random -Min -3 -Max 3), 1)
            efficiency_pct = Get-Random-Double 16 19
        } | ConvertTo-Json

        $r = Invoke-WebRequest -Uri "$BaseUrl/api/sensors/solar" `
            -Method POST -Headers $headers -Body $solar -UseBasicParsing
        Write-Host "  ☀ Solar:   $(($solar | ConvertFrom-Json).power_kw) kW  |  $($r.StatusCode)" -ForegroundColor Yellow
    } catch { Write-Host "  ☀ Solar ERROR: $_" -ForegroundColor Red }

    # ── Water ────────────────────────────────────────
    try {
        $water = @{
            borehole_level_m = Get-Random-Double 6.5 12.0
            tank_level_pct   = Get-Random -Min 45 -Max 95
            min_threshold_m  = 5.0
        } | ConvertTo-Json

        $r = Invoke-WebRequest -Uri "$BaseUrl/api/sensors/water" `
            -Method POST -Headers $headers -Body $water -UseBasicParsing
        Write-Host "  💧 Water:   $(($water | ConvertFrom-Json).borehole_level_m) m  |  $($r.StatusCode)" -ForegroundColor Cyan
    } catch { Write-Host "  💧 Water ERROR: $_" -ForegroundColor Red }

    # ── Weather ──────────────────────────────────────
    try {
        $dirs = @("N","NE","E","SE","S","SW","W","NW")
        $weather = @{
            temperature_c  = Get-Random-Double 26 38
            humidity_pct   = Get-Random -Min 40 -Max 85
            pressure_hpa   = Get-Random-Double 1008 1015
            wind_speed_ms  = Get-Random-Double 0.5 4.5
            wind_direction = $dirs | Get-Random
            irradiance_wm2 = [Math]::Round($solarFactor * 850 + (Get-Random -Min -30 -Max 30), 1)
        } | ConvertTo-Json

        $r = Invoke-WebRequest -Uri "$BaseUrl/api/sensors/weather" `
            -Method POST -Headers $headers -Body $weather -UseBasicParsing
        Write-Host "  🌡 Weather: $(($weather | ConvertFrom-Json).temperature_c)°C  |  $($r.StatusCode)" -ForegroundColor Magenta
    } catch { Write-Host "  🌡 Weather ERROR: $_" -ForegroundColor Red }

    # ── Irrigation ───────────────────────────────────
    try {
        $irrigation = @{
            flow_rate_lpm       = Get-Random-Double 8.0 15.0
            zone_a_moisture_pct = Get-Random -Min 40 -Max 80
            zone_b_moisture_pct = Get-Random -Min 35 -Max 75
            zone_c_moisture_pct = Get-Random -Min 30 -Max 70
            valve_01_open       = (Get-Random -Min 0 -Max 2) -eq 1
            valve_02_open       = (Get-Random -Min 0 -Max 2) -eq 1
            valve_03_open       = (Get-Random -Min 0 -Max 2) -eq 1
        } | ConvertTo-Json

        $r = Invoke-WebRequest -Uri "$BaseUrl/api/sensors/irrigation" `
            -Method POST -Headers $headers -Body $irrigation -UseBasicParsing
        Write-Host "  🔋 Irrig:  $(($irrigation | ConvertFrom-Json).flow_rate_lpm) L/min  |  $($r.StatusCode)" -ForegroundColor Green
    } catch { Write-Host "  🔋 Irrig ERROR: $_" -ForegroundColor Red }

    $iteration++
    Write-Host "`n[#$iteration] $(Get-Date -Format 'HH:mm:ss') — Next in ${Interval}s..." -ForegroundColor DarkGray
    Write-Host "─────────────────────────────────────────" -ForegroundColor DarkGray

    if ($Count -eq 0 -or $iteration -lt $Count) {
        Start-Sleep -Seconds $Interval
    }
}

Write-Host "`n✅ Done! $iteration reading(s) sent." -ForegroundColor Green
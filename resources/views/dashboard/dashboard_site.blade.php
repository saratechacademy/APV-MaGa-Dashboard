@extends('layouts.dashboard')

@section('page-title', $site->name)
@section('page-crumb', $site->country . ' · ' . $site->capacity_kw . ' kWp')

@push('styles')
<style>
/* DETAIL TABS */
.detail-tabs-bar{display:flex;padding:0;background:var(--surface);border-bottom:1px solid var(--border);margin:-20px -20px 20px;padding:0 20px}
.d-tab{font-family:'DM Sans',sans-serif;font-size:13.5px;font-weight:500;padding:12px 16px;border:none;background:transparent;cursor:pointer;color:var(--muted);border-bottom:2px solid transparent;margin-bottom:-1px;transition:color .15s}
.d-tab.active{color:var(--blue);border-bottom-color:var(--blue)}
.d-tab:hover:not(.active){color:var(--text)}
.tab-panel{display:none}.tab-panel.active{display:block}
/* DETAIL KPIs */
.d-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px}
.d-kpi{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);padding:14px 16px}
.d-kpi-val{font-family:'DM Mono',monospace;font-size:26px;font-weight:500;line-height:1}
.d-kpi-unit{font-size:15px;color:var(--muted)}
.d-kpi-lbl{font-size:12px;color:var(--muted);margin-top:4px}
.d-kpi-badge{margin-top:6px}
/* LAYOUTS */
.d-charts-2{display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:16px}
.d-charts-2eq{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
.d-charts-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px}
/* API KEY */
.api-key-box{background:#0f1929;border:1px solid #1e2d45;border-radius:9px;padding:12px 16px;display:flex;align-items:center;gap:10px;margin-bottom:16px}
.api-key-val{font-family:'DM Mono',monospace;font-size:13px;color:#93c5fd;flex:1;word-break:break-all}
.btn-copy{font-family:'DM Sans',sans-serif;font-size:11px;padding:4px 10px;border:1px solid #1e2d45;background:#1a2640;color:#94a3b8;border-radius:6px;cursor:pointer}
.btn-copy:hover{background:#1e3254;color:#fff}
/* AGRICULTURE */
.ag-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px}
.ag-field{display:flex;flex-direction:column;gap:4px}
.ag-label{font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.4px}
.ag-input{font-family:'DM Sans',sans-serif;font-size:13px;padding:7px 10px;border:1px solid var(--border);border-radius:7px;background:var(--bg);color:var(--text);width:100%;transition:border-color .15s}
.ag-input:focus{outline:none;border-color:var(--blue);background:#fff}
textarea.ag-input{resize:vertical;min-height:62px}
select.ag-input{cursor:pointer}
</style>
@endpush

@section('content')

{{-- Tabs --}}
<div class="detail-tabs-bar">
  <button class="d-tab active" data-tab="solar"       onclick="switchTab('solar',this)">☀ Solar</button>
  <button class="d-tab"        data-tab="water"       onclick="switchTab('water',this)">💧 Water</button>
  <button class="d-tab"        data-tab="irrigation"  onclick="switchTab('irrigation',this)">🔋 Irrigation</button>
  <button class="d-tab"        data-tab="weather"     onclick="switchTab('weather',this)">🌡 Weather</button>
  <button class="d-tab"        data-tab="agriculture" onclick="switchTab('agriculture',this)">🌿 Agriculture</button>
  <button class="d-tab"        data-tab="raw"         onclick="switchTab('raw',this)">📋 Raw Data</button>
  @if(auth()->user()->isAdmin() || auth()->id() === $site->user_id)
  <button class="d-tab"        data-tab="apikey"      onclick="switchTab('apikey',this)" style="margin-left:auto">🔑 API Key</button>
  @endif
</div>

{{-- ===== SOLAR TAB ===== --}}
<div class="tab-panel active" id="tab-solar">
  @php $solar = $site->latestSolar(); @endphp
  <div class="d-kpis">
    <div class="d-kpi">
      <div class="d-kpi-val">{{ $solar ? number_format($solar->power_kw, 2) : '—' }}<span class="d-kpi-unit"> kW</span></div>
      <div class="d-kpi-lbl">Power Output</div>
      <div class="d-kpi-badge"><span class="badge badge-ok">✓ Online</span></div>
    </div>
    <div class="d-kpi">
      <div class="d-kpi-val">{{ $solar ? number_format($solar->irradiance_wm2) : '—' }}<span class="d-kpi-unit"> W/m²</span></div>
      <div class="d-kpi-lbl">Solar Irradiance</div>
    </div>
    <div class="d-kpi">
      <div class="d-kpi-val">{{ $solar ? number_format($solar->panel_temp_c, 1) : '—' }}<span class="d-kpi-unit"> °C</span></div>
      <div class="d-kpi-lbl">Panel Temperature</div>
    </div>
    <div class="d-kpi">
      <div class="d-kpi-val">{{ $solar && $solar->efficiency_pct ? number_format($solar->efficiency_pct, 1) : '—' }}<span class="d-kpi-unit"> %</span></div>
      <div class="d-kpi-lbl">Efficiency</div>
    </div>
  </div>
  <div class="d-charts-2">
    <div class="chart-card">
      <div class="cc-head"><div class="cc-title">Power Output + Irradiance</div><button class="dl-btn" onclick="exportData('csv')">⬇ CSV</button></div>
      <div style="position:relative;height:200px"><canvas id="d-chart-solar-main"></canvas></div>
    </div>
    <div class="chart-card">
      <div class="cc-head"><div class="cc-title">Panel Temperature (°C)</div><button class="dl-btn" onclick="exportData('csv')">⬇ CSV</button></div>
      <div style="position:relative;height:200px"><canvas id="d-chart-panel-temp"></canvas></div>
    </div>
  </div>
</div>

{{-- ===== WATER TAB ===== --}}
<div class="tab-panel" id="tab-water">
  @php $water = $site->latestWater(); $isWarn = $water?->isWarning(); @endphp
  <div class="d-kpis">
    <div class="d-kpi">
      <div class="d-kpi-val {{ $isWarn ? 'text-amber' : '' }}" style="{{ $isWarn ? 'color:var(--amber)' : '' }}">{{ $water ? number_format($water->borehole_level_m, 2) : '—' }}<span class="d-kpi-unit"> m</span></div>
      <div class="d-kpi-lbl">Borehole Level</div>
      <div class="d-kpi-badge"><span class="badge {{ $isWarn ? 'badge-warn' : 'badge-ok' }}">{{ $isWarn ? '⚠ Low' : '✓ Normal' }}</span></div>
    </div>
    <div class="d-kpi">
      <div class="d-kpi-val">{{ $water ? $water->tank_level_pct : '—' }}<span class="d-kpi-unit"> %</span></div>
      <div class="d-kpi-lbl">Tank Fill Level</div>
    </div>
    <div class="d-kpi">
      <div class="d-kpi-val">{{ $water ? number_format($water->min_threshold_m, 1) : '—' }}<span class="d-kpi-unit"> m</span></div>
      <div class="d-kpi-lbl">Min Threshold</div>
    </div>
    <div class="d-kpi">
      <div class="d-kpi-val">{{ $water ? $water->created_at->format('H:i') : '—' }}</div>
      <div class="d-kpi-lbl">Last Reading</div>
    </div>
  </div>
  <div class="d-charts-2eq">
    <div class="chart-card">
      <div class="cc-head"><div class="cc-title">Borehole Level (m)</div><button class="dl-btn" onclick="exportData('csv')">⬇ CSV</button></div>
      <div style="position:relative;height:180px"><canvas id="d-chart-borehole"></canvas></div>
    </div>
    <div class="chart-card">
      <div class="cc-head"><div class="cc-title">Tank Fill Level (%)</div><button class="dl-btn" onclick="exportData('csv')">⬇ CSV</button></div>
      <div style="position:relative;height:180px"><canvas id="d-chart-tank-detail"></canvas></div>
    </div>
  </div>
</div>

{{-- ===== IRRIGATION TAB ===== --}}
<div class="tab-panel" id="tab-irrigation">
  @php $irrig = $site->irrigationReadings()->latest()->first(); @endphp
  <div class="d-kpis">
    <div class="d-kpi">
      <div class="d-kpi-val">{{ $irrig ? number_format($irrig->flow_rate_lpm, 1) : '—' }}<span class="d-kpi-unit"> L/min</span></div>
      <div class="d-kpi-lbl">Flow Rate</div>
    </div>
    <div class="d-kpi">
      <div class="d-kpi-val">{{ $irrig ? $irrig->zone_a_moisture_pct . '%' : '—' }}</div>
      <div class="d-kpi-lbl">Zone A Moisture</div>
    </div>
    <div class="d-kpi">
      <div class="d-kpi-val">{{ $irrig ? $irrig->zone_b_moisture_pct . '%' : '—' }}</div>
      <div class="d-kpi-lbl">Zone B Moisture</div>
    </div>
    <div class="d-kpi">
      <div class="d-kpi-val">{{ $irrig ? $irrig->openValvesCount() : '—' }}</div>
      <div class="d-kpi-lbl">Open Valves</div>
    </div>
  </div>
  <div class="chart-card" style="margin-bottom:16px">
    <div class="cc-head"><div class="cc-title">Irrigation Flow Rate (L/min)</div><button class="dl-btn" onclick="exportData('csv')">⬇ CSV</button></div>
    <div style="position:relative;height:180px"><canvas id="d-chart-flow"></canvas></div>
  </div>
  <div class="d-charts-2eq">
    <div class="chart-card">
      <div class="cc-head"><div class="cc-title">Soil Moisture Sensors (%)</div><button class="dl-btn" onclick="exportData('csv')">⬇ CSV</button></div>
      <div style="position:relative;height:180px"><canvas id="d-chart-moisture"></canvas></div>
      <div class="chart-legend" id="legend-moisture"></div>
    </div>
    <div class="chart-card">
      <div class="cc-head"><div class="cc-title">Zone Readings</div></div>
      <table class="raw-table">
        <thead><tr><th>Zone</th><th>Moisture %</th><th>Valve</th></tr></thead>
        <tbody>
          <tr><td>Zone A</td><td>{{ $irrig?->zone_a_moisture_pct ?? '—' }}</td><td><span class="badge {{ $irrig?->valve_01_open ? 'badge-ok' : 'badge-err' }}">{{ $irrig?->valve_01_open ? 'Open' : 'Closed' }}</span></td></tr>
          <tr><td>Zone B</td><td>{{ $irrig?->zone_b_moisture_pct ?? '—' }}</td><td><span class="badge {{ $irrig?->valve_02_open ? 'badge-ok' : 'badge-err' }}">{{ $irrig?->valve_02_open ? 'Open' : 'Closed' }}</span></td></tr>
          <tr><td>Zone C</td><td>{{ $irrig?->zone_c_moisture_pct ?? '—' }}</td><td><span class="badge {{ $irrig?->valve_03_open ? 'badge-ok' : 'badge-err' }}">{{ $irrig?->valve_03_open ? 'Open' : 'Closed' }}</span></td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- ===== WEATHER TAB ===== --}}
<div class="tab-panel" id="tab-weather">
  @php $wx = $site->latestWeather(); @endphp
  <div class="d-kpis">
    <div class="d-kpi">
      <div class="d-kpi-val">{{ $wx ? number_format($wx->temperature_c, 1) : '—' }}<span class="d-kpi-unit"> °C</span></div>
      <div class="d-kpi-lbl">Temperature</div>
    </div>
    <div class="d-kpi">
      <div class="d-kpi-val">{{ $wx ? $wx->humidity_pct : '—' }}<span class="d-kpi-unit"> %</span></div>
      <div class="d-kpi-lbl">Humidity</div>
    </div>
    <div class="d-kpi">
      <div class="d-kpi-val">{{ $wx ? number_format($wx->pressure_hpa) : '—' }}<span class="d-kpi-unit"> hPa</span></div>
      <div class="d-kpi-lbl">Air Pressure</div>
    </div>
    <div class="d-kpi">
      <div class="d-kpi-val">{{ $wx ? number_format($wx->wind_speed_ms, 1) : '—' }}<span class="d-kpi-unit"> m/s</span></div>
      <div class="d-kpi-lbl">Wind Speed {{ $wx?->wind_direction ?? '' }}</div>
    </div>
  </div>
  <div class="d-charts-3">
    <div class="chart-card">
      <div class="cc-head"><div class="cc-title">Temperature (°C)</div><button class="dl-btn" onclick="exportData('csv')">⬇ CSV</button></div>
      <div style="position:relative;height:170px"><canvas id="d-chart-temp-d"></canvas></div>
    </div>
    <div class="chart-card">
      <div class="cc-head"><div class="cc-title">Air Pressure (hPa)</div><button class="dl-btn" onclick="exportData('csv')">⬇ CSV</button></div>
      <div style="position:relative;height:170px"><canvas id="d-chart-pressure"></canvas></div>
    </div>
    <div class="chart-card">
      <div class="cc-head"><div class="cc-title">Wind Speed (m/s)</div><button class="dl-btn" onclick="exportData('csv')">⬇ CSV</button></div>
      <div style="position:relative;height:170px"><canvas id="d-chart-wind"></canvas></div>
    </div>
  </div>
</div>

{{-- ===== AGRICULTURE TAB ===== --}}
<div class="tab-panel" id="tab-agriculture">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <div>
      <div style="font-size:16px;font-weight:600">Agricultural Data Entry</div>
      <div style="font-size:12px;color:var(--muted);margin-top:2px">Record field observations, measurements and yield data</div>
    </div>
    <div style="display:flex;gap:8px">
      <button class="btn" onclick="exportData('csv')">⬇ Export CSV</button>
      <button class="btn btn-blue" onclick="exportData('excel')">⬇ Export Excel</button>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
    {{-- Yield form --}}
    <div class="chart-card">
      <div class="cc-head" style="margin-bottom:14px">
        <div class="cc-title">🌾 Yield Data</div>
        <span class="badge badge-ok">Entry form</span>
      </div>
      <form method="POST" action="{{ route('agriculture.store', $site) }}">
        @csrf
        <input type="hidden" name="type" value="yield">
        <div class="ag-row">
          <div class="ag-field">
            <label class="ag-label">Crop type</label>
            <select name="crop_type" class="ag-input">
              <option value="">Select crop…</option>
              @foreach(['Groundnut','Millet','Sorghum','Maize','Cowpea','Sesame','Other'] as $crop)
                <option>{{ $crop }}</option>
              @endforeach
            </select>
          </div>
          <div class="ag-field">
            <label class="ag-label">Plot / Zone</label>
            <select name="zone" class="ag-input">
              <option>Zone A</option><option>Zone B</option><option>Zone C</option><option>Full site</option>
            </select>
          </div>
        </div>
        <div class="ag-row">
          <div class="ag-field">
            <label class="ag-label">Harvest date</label>
            <input type="date" name="harvest_date" class="ag-input" value="{{ date('Y-m-d') }}">
          </div>
          <div class="ag-field">
            <label class="ag-label">Plot area (m²)</label>
            <input type="number" name="area_m2" class="ag-input" placeholder="e.g. 100">
          </div>
        </div>
        <div class="ag-row">
          <div class="ag-field">
            <label class="ag-label">Fresh yield (kg)</label>
            <input type="number" name="fresh_yield_kg" step="0.1" class="ag-input" placeholder="e.g. 45.2">
          </div>
          <div class="ag-field">
            <label class="ag-label">Dry yield (kg)</label>
            <input type="number" name="dry_yield_kg" step="0.1" class="ag-input" placeholder="e.g. 38.0">
          </div>
        </div>
        <div class="ag-field" style="margin-bottom:12px">
          <label class="ag-label">Notes</label>
          <textarea name="notes" class="ag-input" rows="2" placeholder="Harvest conditions, quality observations…"></textarea>
        </div>
        <button type="submit" class="btn btn-blue" style="width:100%;justify-content:center">+ Save yield record</button>
      </form>
    </div>

    {{-- Plant measurements form --}}
    <div class="chart-card">
      <div class="cc-head" style="margin-bottom:14px">
        <div class="cc-title">📏 Plant Measurements</div>
        <span class="badge badge-ok">Entry form</span>
      </div>
      <form method="POST" action="{{ route('agriculture.store', $site) }}">
        @csrf
        <input type="hidden" name="type" value="measurement">
        <div class="ag-row">
          <div class="ag-field">
            <label class="ag-label">Observation date</label>
            <input type="date" name="observation_date" class="ag-input" value="{{ date('Y-m-d') }}">
          </div>
          <div class="ag-field">
            <label class="ag-label">Growth stage</label>
            <select name="growth_stage" class="ag-input">
              @foreach(['Germination','Seedling','Vegetative','Flowering','Pod / grain fill','Maturity','Harvest'] as $stage)
                <option>{{ $stage }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="ag-row">
          <div class="ag-field">
            <label class="ag-label">Plant height (cm)</label>
            <input type="number" name="plant_height_cm" class="ag-input" placeholder="e.g. 62">
          </div>
          <div class="ag-field">
            <label class="ag-label">Canopy cover (%)</label>
            <input type="number" name="canopy_cover_pct" class="ag-input" placeholder="e.g. 78">
          </div>
        </div>
        <div class="ag-row">
          <div class="ag-field">
            <label class="ag-label">Leaf area index</label>
            <input type="number" name="leaf_area_index" step="0.01" class="ag-input" placeholder="e.g. 2.34">
          </div>
          <div class="ag-field">
            <label class="ag-label">Plant health</label>
            <select name="plant_health" class="ag-input">
              <option>Excellent</option><option>Good</option><option>Fair</option><option>Poor</option><option>Stressed</option>
            </select>
          </div>
        </div>
        <div class="ag-field" style="margin-bottom:12px">
          <label class="ag-label">Observations</label>
          <textarea name="observations" class="ag-input" rows="2" placeholder="Visible stress signs, pest/disease, leaf colour…"></textarea>
        </div>
        <button type="submit" class="btn btn-blue" style="width:100%;justify-content:center">+ Save measurement</button>
      </form>
    </div>
  </div>

  {{-- Records table --}}
  <div class="chart-card" style="padding:0;overflow:hidden">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--border)">
      <span style="font-size:13px;font-weight:600">Saved Records</span>
      <div style="display:flex;gap:6px">
        <button class="dl-btn" onclick="exportData('csv')">⬇ CSV</button>
        <button class="dl-btn" onclick="exportData('excel')">⬇ XLS</button>
      </div>
    </div>
    <table class="raw-table">
      <thead><tr><th>Date</th><th>Type</th><th>Zone</th><th>Crop / Stage</th><th>Key value</th><th>Notes</th></tr></thead>
      <tbody>
        @forelse($site->agricultureRecords()->latest()->take(20)->get() as $rec)
          <tr>
            <td>{{ $rec->created_at->format('Y-m-d') }}</td>
            <td><span class="badge {{ $rec->type === 'yield' ? 'badge-ok' : '' }}" style="{{ $rec->type !== 'yield' ? 'background:var(--blue-bg);color:var(--blue);border-color:var(--blue-bd)' : '' }}">{{ ucfirst($rec->type) }}</span></td>
            <td>{{ $rec->zone ?? '—' }}</td>
            <td>{{ $rec->crop_type ?? $rec->growth_stage ?? '—' }}</td>
            <td>{{ $rec->fresh_yield_kg ? 'Fresh: '.$rec->fresh_yield_kg.' kg' : ($rec->plant_height_cm ? 'H: '.$rec->plant_height_cm.' cm' : '—') }}</td>
            <td style="color:var(--muted)">{{ Str::limit($rec->notes ?? $rec->observations ?? '—', 40) }}</td>
          </tr>
        @empty
          <tr><td colspan="6" style="text-align:center;padding:20px;color:var(--muted)">No records yet</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- ===== RAW DATA TAB ===== --}}
<div class="tab-panel" id="tab-raw">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
    <div style="font-size:13px;color:var(--muted)">All tables reflect the selected time range.</div>
    <div style="display:flex;gap:8px">
      <button class="btn" onclick="exportData('csv')">⬇ All CSV</button>
      <button class="btn btn-blue" onclick="exportData('excel')">⬇ All Excel</button>
    </div>
  </div>

  {{-- Solar raw --}}
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);margin-bottom:16px;overflow:hidden">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--border);background:var(--bg)">
      <span style="font-size:13px;font-weight:600">☀ Solar Panels</span>
      <div style="display:flex;gap:6px"><button class="dl-btn" onclick="exportData('csv')">⬇ CSV</button><button class="dl-btn" onclick="exportData('excel')">⬇ XLS</button></div>
    </div>
    <div style="overflow-x:auto">
      <table class="raw-table">
        <thead><tr><th>Timestamp</th><th>Power (kW)</th><th>Irradiance (W/m²)</th><th>Panel Temp (°C)</th><th>Efficiency (%)</th></tr></thead>
        <tbody>
          @foreach($site->solarReadings()->latest()->take(10)->get() as $r)
          <tr>
            <td>{{ $r->created_at->format('Y-m-d H:i:s') }}</td>
            <td>{{ $r->power_kw }}</td>
            <td>{{ $r->irradiance_wm2 }}</td>
            <td>{{ $r->panel_temp_c }}</td>
            <td>{{ $r->efficiency_pct ?? '—' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- Water raw --}}
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);margin-bottom:16px;overflow:hidden">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--border);background:var(--bg)">
      <span style="font-size:13px;font-weight:600">💧 Water Systems</span>
      <div style="display:flex;gap:6px"><button class="dl-btn" onclick="exportData('csv')">⬇ CSV</button><button class="dl-btn" onclick="exportData('excel')">⬇ XLS</button></div>
    </div>
    <div style="overflow-x:auto">
      <table class="raw-table">
        <thead><tr><th>Timestamp</th><th>Borehole (m)</th><th>Tank Fill (%)</th><th>Threshold (m)</th><th>Status</th></tr></thead>
        <tbody>
          @foreach($site->waterReadings()->latest()->take(10)->get() as $r)
          <tr>
            <td>{{ $r->created_at->format('Y-m-d H:i:s') }}</td>
            <td>{{ $r->borehole_level_m }}</td>
            <td>{{ $r->tank_level_pct }}</td>
            <td>{{ $r->min_threshold_m }}</td>
            <td><span class="badge {{ $r->isWarning() ? 'badge-warn' : 'badge-ok' }}">{{ $r->isWarning() ? '⚠ Low' : '✓ OK' }}</span></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- Weather raw --}}
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);overflow:hidden">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--border);background:var(--bg)">
      <span style="font-size:13px;font-weight:600">🌡 Weather Station</span>
      <div style="display:flex;gap:6px"><button class="dl-btn" onclick="exportData('csv')">⬇ CSV</button><button class="dl-btn" onclick="exportData('excel')">⬇ XLS</button></div>
    </div>
    <div style="overflow-x:auto">
      <table class="raw-table">
        <thead><tr><th>Timestamp</th><th>Temp (°C)</th><th>Humidity (%)</th><th>Pressure (hPa)</th><th>Wind (m/s)</th><th>Direction</th><th>Irradiance (W/m²)</th></tr></thead>
        <tbody>
          @foreach($site->weatherReadings()->latest()->take(10)->get() as $r)
          <tr>
            <td>{{ $r->created_at->format('Y-m-d H:i:s') }}</td>
            <td>{{ $r->temperature_c }}</td>
            <td>{{ $r->humidity_pct }}</td>
            <td>{{ $r->pressure_hpa }}</td>
            <td>{{ $r->wind_speed_ms }}</td>
            <td>{{ $r->wind_direction ?? '—' }}</td>
            <td>{{ $r->irradiance_wm2 ?? '—' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- ===== API KEY TAB ===== --}}
@if(auth()->user()->isAdmin() || auth()->id() === $site->user_id)
<div class="tab-panel" id="tab-apikey">
  <div class="d-kpis" style="grid-template-columns:1fr 1fr">
    <div class="d-kpi" style="grid-column:1/-1">
      <div class="d-kpi-lbl" style="margin-bottom:8px">API Key for <strong>{{ $site->name }}</strong></div>
      <div class="api-key-box">
        <div class="api-key-val" id="api-key-val">{{ $site->api_key }}</div>
        <button class="btn-copy" onclick="copyKey()">Copy</button>
      </div>
      <div style="font-size:12px;color:var(--muted);margin-top:8px">
        Use this key in the <code style="font-family:'DM Mono',monospace;background:var(--bg);padding:1px 5px;border-radius:4px;font-size:11px">X-API-Key</code> header when posting sensor data.
      </div>
    </div>
  </div>

  <div class="chart-card" style="margin-top:16px">
    <div class="cc-head"><div class="cc-title">Example — PowerShell</div></div>
    <pre style="font-family:'DM Mono',monospace;font-size:12px;color:#334155;background:var(--bg);padding:14px;border-radius:8px;overflow-x:auto;white-space:pre-wrap;line-height:1.6">Invoke-WebRequest -Uri "http://your-server/api/sensors/solar" \
  -Method POST \
  -Headers @{ "X-API-Key" = "{{ $site->api_key }}"; "Content-Type" = "application/json" } \
  -Body '{"power_kw": 3.5, "irradiance_wm2": 820, "panel_temp_c": 52.1, "efficiency_pct": 18.4}' \
  -UseBasicParsing</pre>
  </div>

  <div class="chart-card" style="margin-top:16px">
    <div class="cc-head"><div class="cc-title">Available Endpoints</div></div>
    <table class="raw-table">
      <thead><tr><th>Method</th><th>Endpoint</th><th>Description</th></tr></thead>
      <tbody>
        <tr><td>POST</td><td>/api/sensors/solar</td><td>power_kw, irradiance_wm2, panel_temp_c, efficiency_pct</td></tr>
        <tr><td>POST</td><td>/api/sensors/water</td><td>borehole_level_m, tank_level_pct, min_threshold_m</td></tr>
        <tr><td>POST</td><td>/api/sensors/irrigation</td><td>flow_rate_lpm, zone_a_moisture_pct, valve_01_open…</td></tr>
        <tr><td>POST</td><td>/api/sensors/weather</td><td>temperature_c, humidity_pct, pressure_hpa, wind_speed_ms…</td></tr>
        <tr><td>GET</td><td>/api/sensors/status</td><td>Check site status and last readings</td></tr>
      </tbody>
    </table>
  </div>
</div>
@endif

@endsection

@push('scripts')
<script>
function switchTab(tab, btn) {
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.d-tab').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + tab).classList.add('active');
  if (btn) btn.classList.add('active');
}

function copyKey() {
  const val = document.getElementById('api-key-val')?.textContent;
  if (val) { navigator.clipboard.writeText(val); alert('API Key copied!'); }
}

// Charts helpers
const N = 20;
function makeLabels() {
  return Array.from({length: N}, (_, i) => {
    const d = new Date(); d.setMinutes(d.getMinutes() - (N-1-i)*3);
    return d.getHours().toString().padStart(2,'0')+':'+d.getMinutes().toString().padStart(2,'0');
  });
}
function randData(base, range) { return Array.from({length:N}, () => +(base+(Math.random()-.5)*range).toFixed(2)); }
const labels = makeLabels();
const chartOpts = { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}},
  scales:{ x:{ticks:{font:{size:10},color:'#94a3b8',maxTicksLimit:6},grid:{color:'#f1f5f9'}},
            y:{ticks:{font:{size:10,family:'DM Mono'},color:'#94a3b8'},grid:{color:'#f1f5f9'}} } };

const mkLine = (id, base, range, color, fill=false) => {
  const ctx = document.getElementById(id);
  if (!ctx) return;
  new Chart(ctx, { type:'line', data:{ labels, datasets:[{
    data: randData(base,range), borderColor:color, backgroundColor:color+'22',
    borderWidth:2, pointRadius:0, tension:.4, fill
  }]}, options:chartOpts });
};

mkLine('d-chart-solar-main', 3.5, 3, '#1d6ed8', true);
mkLine('d-chart-panel-temp', 52, 10, '#b45309');
mkLine('d-chart-borehole', 8.5, 4, '#1d6ed8', true);
mkLine('d-chart-tank-detail', 75, 20, '#15803d', true);
mkLine('d-chart-flow', 12, 8, '#7c3aed', true);
mkLine('d-chart-temp-d', 33, 8, '#b45309', true);
mkLine('d-chart-pressure', 1012, 6, '#0891b2');
mkLine('d-chart-wind', 3.5, 3, '#15803d', true);

// Moisture multi-line
const mCtx = document.getElementById('d-chart-moisture');
if (mCtx) {
  new Chart(mCtx, { type:'line', data:{ labels, datasets:[
    {label:'Zone A',data:randData(62,20),borderColor:'#1d6ed8',borderWidth:2,pointRadius:0,tension:.4,fill:false},
    {label:'Zone B',data:randData(55,18),borderColor:'#15803d',borderWidth:2,pointRadius:0,tension:.4,fill:false},
    {label:'Zone C',data:randData(48,22),borderColor:'#b45309',borderWidth:2,pointRadius:0,tension:.4,fill:false},
  ]}, options:{...chartOpts, plugins:{legend:{display:false}}} });
  const leg = document.getElementById('legend-moisture');
  if(leg) leg.innerHTML = ['Zone A #1d6ed8','Zone B #15803d','Zone C #b45309'].map(s=>{
    const [n,c]=s.split(' ');
    return `<span class="legend-item"><span class="legend-dot" style="background:${c}"></span>${n}</span>`;
  }).join('');
}
</script>
@endpush

@extends('layouts.dashboard')

@section('page-title', 'All Sites')
@section('page-crumb', 'Overview')

@section('content')

{{-- Alert borehole --}}
@php
  $warnings = collect($sites ?? [])->filter(fn($s) => $s->latestWater()?->isWarning());
@endphp
@foreach($warnings as $ws)
<div class="alert-banner warn" id="alert-{{ $ws->id }}">
  <strong>⚠ {{ $ws->name }}:</strong>&nbsp;Borehole water level below threshold. Inspection recommended.
  <button class="ab-close" onclick="this.closest('.alert-banner').style.display='none'">×</button>
</div>
@endforeach

{{-- Section: Sites --}}
<div class="sec-header">
  <span class="sec-title">Installation Sites</span>
  <span style="font-size:12px;color:var(--muted)" id="last-updated">Updated: {{ now()->format('H:i:s') }} UTC</span>
</div>

<div class="cards-grid">
@forelse($sites ?? [] as $site)
  @php
    $solar  = $site->latestSolar();
    $water  = $site->latestWater();
    $weather= $site->latestWeather();
    $isWarn = $water?->isWarning();
    $statusClass = $isWarn ? 'warn-card' : '';
  @endphp
  <a href="{{ route('dashboard.site', $site) }}" class="site-card {{ $statusClass }}">
    <div class="card-head">
      <div class="card-name">{{ $site->name }}</div>
      <span class="country-chip">{{ strtoupper(substr($site->country, 0, 3)) }}</span>
      <span class="badge {{ $isWarn ? 'badge-warn' : 'badge-ok' }}" style="margin-left:auto">
        {{ $isWarn ? '⚠ Warning' : '✓ Normal' }}
      </span>
    </div>
    <div class="kpis">
      <div class="kpi">
        <div class="kpi-val">{{ $solar ? number_format($solar->power_kw, 1) : '—' }}</div>
        <div class="kpi-lbl">kW Output</div>
      </div>
      <div class="kpi">
        <div class="kpi-val {{ $isWarn ? 'warn' : '' }}">{{ $water ? number_format($water->borehole_level_m, 1) : '—' }}</div>
        <div class="kpi-lbl">Borehole m</div>
      </div>
      <div class="kpi">
        <div class="kpi-val">{{ $water ? $water->tank_level_pct . '%' : '—' }}</div>
        <div class="kpi-lbl">Tank Fill</div>
      </div>
      <div class="kpi">
        <div class="kpi-val">{{ $weather ? number_format($weather->temperature_c, 1) . '°' : '—' }}</div>
        <div class="kpi-lbl">Temp °C</div>
      </div>
    </div>
    <div class="sparkline-wrap">
      <canvas id="spark-{{ $site->id }}" style="width:100%;height:100%"></canvas>
    </div>
    <div class="statuses">
      <span class="badge badge-ok">☀ Solar</span>
      <span class="badge {{ $isWarn ? 'badge-warn' : 'badge-ok' }}">💧 Water</span>
      <span class="badge badge-ok">🌡 Weather</span>
      @if($site->capacity_kw)
        <span style="margin-left:auto;font-size:10px;color:var(--muted)">{{ $site->capacity_kw }} kWp · {{ number_format($site->area_m2) }} m²</span>
      @endif
    </div>
  </a>
@empty
  <div style="grid-column:1/-1;text-align:center;padding:48px;color:var(--muted)">
    <div style="font-size:32px;margin-bottom:12px">📡</div>
    <div style="font-weight:600;margin-bottom:6px">No sites yet</div>
    <div style="font-size:12px;margin-bottom:16px">Create your first monitoring site to get started</div>
    <a href="{{ route('sites.create') }}" class="btn btn-blue">+ New Site</a>
  </div>
@endforelse
</div>

{{-- Cross-site charts --}}
@if(($sites ?? collect())->count() > 0)
<div class="sec-header">
  <span class="sec-title">Cross-Site Trends</span>
</div>

<div class="charts-row">
  <div class="chart-card">
    <div class="cc-head">
      <div>
        <div class="cc-title">Solar Output (kW)</div>
        <div class="cc-sub">All sites · <span id="lbl-solar-range">last 1h</span></div>
      </div>
      <button class="dl-btn" onclick="exportData('csv')">⬇ CSV</button>
    </div>
    <div style="position:relative;height:180px"><canvas id="chart-solar"></canvas></div>
    <div class="chart-legend" id="legend-solar"></div>
  </div>
  <div class="chart-card">
    <div class="cc-head">
      <div>
        <div class="cc-title">Borehole Water Level (m)</div>
        <div class="cc-sub">All sites · <span id="lbl-water-range">last 1h</span></div>
      </div>
      <button class="dl-btn" onclick="exportData('csv')">⬇ CSV</button>
    </div>
    <div style="position:relative;height:180px"><canvas id="chart-water"></canvas></div>
    <div class="chart-legend" id="legend-water"></div>
  </div>
</div>

<div class="charts-row">
  <div class="chart-card">
    <div class="cc-head">
      <div>
        <div class="cc-title">Air Temperature (°C)</div>
        <div class="cc-sub">All sites · <span id="lbl-temp-range">last 1h</span></div>
      </div>
      <button class="dl-btn" onclick="exportData('csv')">⬇ CSV</button>
    </div>
    <div style="position:relative;height:160px"><canvas id="chart-temp"></canvas></div>
  </div>
  <div class="chart-card">
    <div class="cc-head">
      <div>
        <div class="cc-title">Tank Fill Level (%)</div>
        <div class="cc-sub">All sites · current</div>
      </div>
      <button class="dl-btn" onclick="exportData('csv')">⬇ CSV</button>
    </div>
    <div style="position:relative;height:160px"><canvas id="chart-tank"></canvas></div>
  </div>
</div>
@endif

@endsection

@push('scripts')
<script>
// Sparkline data from PHP (simple simulation if no history)
const SITE_COLORS = ['#1d6ed8','#15803d','#b45309','#7c3aed','#0891b2','#be123c'];
const siteIds    = @json(($sites ?? collect())->pluck('id')->values());
const siteNames  = @json(($sites ?? collect())->pluck('name')->values());

// Sparklines
siteIds.forEach((id, i) => {
  const ctx = document.getElementById('spark-' + id);
  if (!ctx) return;
  const pts = Array.from({length: 20}, () => 2 + Math.random() * 4);
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: pts.map((_,j) => j),
      datasets: [{
        data: pts,
        borderColor: SITE_COLORS[i % SITE_COLORS.length],
        borderWidth: 1.5,
        pointRadius: 0,
        tension: .4,
        fill: true,
        backgroundColor: SITE_COLORS[i % SITE_COLORS.length] + '18'
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, animation: false,
      plugins: {legend:{display:false},tooltip:{enabled:false}},
      scales: { x:{display:false}, y:{display:false} }
    }
  });
});

// Cross-site charts
function makeLabels(n) {
  return Array.from({length: n}, (_, i) => {
    const d = new Date(); d.setMinutes(d.getMinutes() - (n - 1 - i) * 3);
    return d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0');
  });
}

function makeDataset(name, color, base, range, n) {
  return {
    label: name,
    data: Array.from({length: n}, () => +(base + (Math.random() - .5) * range).toFixed(2)),
    borderColor: color,
    backgroundColor: color + '18',
    borderWidth: 2,
    pointRadius: 0,
    tension: .4,
    fill: false
  };
}

const commonOpts = (yLabel) => ({
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { ticks: { font: { size: 10 }, color: '#94a3b8', maxTicksLimit: 6 }, grid: { color: '#f1f5f9' } },
    y: { ticks: { font: { size: 10, family: 'DM Mono' }, color: '#94a3b8' }, grid: { color: '#f1f5f9' } }
  }
});

const N = 20;
const labels = makeLabels(N);

// Solar chart
if (document.getElementById('chart-solar')) {
  const datasets = siteNames.map((name, i) => makeDataset(name, SITE_COLORS[i], 3, 3, N));
  new Chart(document.getElementById('chart-solar'), { type:'line', data:{labels, datasets}, options: commonOpts('kW') });
  const leg = document.getElementById('legend-solar');
  siteNames.forEach((n, i) => {
    leg.innerHTML += `<span class="legend-item"><span class="legend-dot" style="background:${SITE_COLORS[i]}"></span>${n}</span>`;
  });
}

if (document.getElementById('chart-water')) {
  const datasets = siteNames.map((name, i) => makeDataset(name, SITE_COLORS[i], 8, 4, N));
  new Chart(document.getElementById('chart-water'), { type:'line', data:{labels, datasets}, options: commonOpts('m') });
  const leg = document.getElementById('legend-water');
  siteNames.forEach((n, i) => {
    leg.innerHTML += `<span class="legend-item"><span class="legend-dot" style="background:${SITE_COLORS[i]}"></span>${n}</span>`;
  });
}

if (document.getElementById('chart-temp')) {
  const datasets = siteNames.map((name, i) => makeDataset(name, SITE_COLORS[i], 33, 8, N));
  new Chart(document.getElementById('chart-temp'), { type:'line', data:{labels, datasets}, options: commonOpts('°C') });
}

if (document.getElementById('chart-tank')) {
  const names = siteNames.length ? siteNames : ['No data'];
  const vals  = names.map(() => +(40 + Math.random() * 55).toFixed(0));
  new Chart(document.getElementById('chart-tank'), {
    type: 'bar',
    data: {
      labels: names,
      datasets: [{ data: vals, backgroundColor: SITE_COLORS.slice(0, names.length), borderRadius: 6, borderSkipped: false }]
    },
    options: {
      ...commonOpts('%'),
      plugins: { legend: { display: false } },
      scales: { y: { min: 0, max: 100 } }
    }
  });
}

function onRangeChange(r) {
  ['solar','water','temp'].forEach(k => {
    const el = document.getElementById('lbl-' + k + '-range');
    if (el) el.textContent = 'last ' + r;
  });
}
</script>
@endpush

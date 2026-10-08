@extends('layouts.dashboard')
@section('page-title', 'All Sites')
@section('page-crumb', 'Overview')
@section('show-toolbar', '1')
@section('content')


@php
  $iconWarnTriangle = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';
  $iconCheckCircle = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
@endphp

{{-- Section: Sites --}}
<div class="sec-header">
  <span class="sec-title">Installation Sites</span>
  <span style="font-size:12px;color:var(--muted)">Updated: {{ now()->format('H:i:s') }} UTC</span>
</div>

<div class="cards-grid">
@forelse($sites ?? [] as $site)
  @php
    // KPI depuis sensor_readings
    $solarCat   = $site->activeCategories->firstWhere('slug', 'solar');
    $waterCat   = $site->activeCategories->firstWhere('slug', 'water');
    $weatherCat = $site->activeCategories->firstWhere('slug', 'weather');

    $kwParam    = $solarCat?->activeParameters->firstWhere('slug', 'solar_output');
    $bhParam    = $waterCat?->activeParameters->firstWhere('slug', 'borehole_level');
    $tankParam  = $waterCat?->activeParameters->firstWhere('slug', 'tank_fill_level');
    $tempParam  = $weatherCat?->activeParameters->firstWhere('slug', 'temperature');

    $kw   = $kwParam?->latestReading?->value;
    $bh   = $bhParam?->latestReading?->value;
    $tank = $tankParam?->latestReading?->value;
    $temp = $tempParam?->latestReading?->value;

    $isWarn = $bhParam?->isWarn($bh) ?? false;
  @endphp
  <a href="{{ route('dashboard.site', $site) }}" class="site-card {{ $isWarn ? 'warn-card' : '' }}">
    <div class="card-head">
      <div class="card-name">{{ $site->name }}</div>
      <span class="country-chip">{{ strtoupper(substr($site->country, 0, 3)) }}</span>
      <span class="badge {{ $isWarn ? 'badge-warn' : 'badge-ok' }}" style="margin-left:auto;display:inline-flex;align-items:center;gap:4px">
        {!! $isWarn ? $iconWarnTriangle : $iconCheckCircle !!} {{ $isWarn ? 'Warning' : 'Normal' }}
      </span>
    </div>
    <div class="kpis">
      <div class="kpi">
        <div class="kpi-val">{{ $kw !== null ? number_format((float)$kw, 1) : '—' }}</div>
        <div class="kpi-lbl">kW Output</div>
      </div>
      <div class="kpi">
        <div class="kpi-val {{ $isWarn ? 'warn' : '' }}">{{ $bh !== null ? number_format((float)$bh, 1) : '—' }}</div>
        <div class="kpi-lbl">Borehole m</div>
      </div>
      <div class="kpi">
        <div class="kpi-val">{{ $tank !== null ? $tank.'%' : '—' }}</div>
        <div class="kpi-lbl">Tank Fill</div>
      </div>
      <div class="kpi">
        <div class="kpi-val">{{ $temp !== null ? number_format((float)$temp, 1).'°' : '—' }}</div>
        <div class="kpi-lbl">Temp °C</div>
      </div>
    </div>
    <div class="sparkline-wrap">
      <canvas id="spark-{{ $site->id }}"></canvas>
    </div>
    <div class="statuses">
      @foreach($site->activeCategories->take(3) as $cat)
      <span class="badge badge-ok">{{ $cat->icon }} {{ $cat->name }}</span>
      @endforeach
      @if($site->capacity_kw)
        <span style="margin-left:auto;font-size:10px;color:var(--muted)">{{ $site->capacity_kw }} kWp · {{ number_format($site->area_m2) }} m²</span>
      @endif
    </div>
  </a>
@empty
  <div style="grid-column:1/-1;text-align:center;padding:48px;color:var(--muted)">
    <div style="margin-bottom:12px;color:var(--muted)">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9"></path><path d="M7.8 16.2c-2.3-2.3-2.3-6.1 0-8.5"></path><circle cx="12" cy="12" r="1.5"></circle><path d="M16.2 7.8c2.3 2.3 2.3 6.1 0 8.5"></path><path d="M19.1 4.9C23 8.8 23 15.1 19.1 19"></path></svg>
    </div>
    @if(auth()->user()->isAdmin())
      <div style="font-weight:600;margin-bottom:6px">No sites yet</div>
      <div style="font-size:12px;margin-bottom:16px">Create your first monitoring site to get started</div>
      <a href="{{ route('admin.sites.create') }}" class="btn btn-blue">+ New Site</a>
    @else
      <div style="font-weight:600;margin-bottom:6px">No sites assigned</div>
      <div style="font-size:12px;color:var(--muted)">Contact your administrator to get access to a monitoring site.</div>
    @endif
  </div>
@endforelse
</div>

{{-- Cross-site charts --}}
@if(($sites ?? collect())->count() > 0)
<div class="sec-header" style="margin-top:20px">
  <span class="sec-title">Cross-Site Trends</span>
</div>
<div class="charts-row">
  <div class="chart-card">
    <div class="cc-head">
      <div><div class="cc-title">Solar Output (kW)</div><div class="cc-sub">All sites · <span id="lbl-solar-range">last 1h</span></div></div>
    </div>
    <div style="position:relative;height:140px"><canvas id="chart-solar"></canvas></div>
    <div class="chart-legend" id="legend-solar"></div>
  </div>
  <div class="chart-card">
    <div class="cc-head">
      <div><div class="cc-title">Borehole Water Level (m)</div><div class="cc-sub">All sites · <span id="lbl-water-range">last 1h</span></div></div>
    </div>
    <div style="position:relative;height:140px"><canvas id="chart-water"></canvas></div>
    <div class="chart-legend" id="legend-water"></div>
  </div>
  <div class="chart-card">
    <div class="cc-head">
      <div><div class="cc-title">Air Temperature (°C)</div><div class="cc-sub">All sites · <span id="lbl-temp-range">last 1h</span></div></div>
    </div>
    <div style="position:relative;height:140px"><canvas id="chart-temp"></canvas></div>
  </div>
  <div class="chart-card">
    <div class="cc-head">
      <div><div class="cc-title">Tank Fill Level (%)</div><div class="cc-sub">All sites · current</div></div>
    </div>
    <div style="position:relative;height:140px"><canvas id="chart-tank"></canvas></div>
  </div>
</div>
@endif

@endsection

@push('scripts')
<script>
const COLORS = @json(\App\Models\SiteParameterGroup::palette());

@php
$sitesData = ($sites ?? collect())->map(fn($s) => [
  'id'         => $s->id,
  'name'       => $s->name,
  'slug'       => $s->slug,
  'categories' => $s->activeCategories->pluck('slug')->values(),
])->values();
@endphp
const SITES = @json($sitesData);

const opts = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { ticks: { font:{size:9}, color:'#94a3b8', maxTicksLimit:5 }, grid: { color:'#f1f5f9' } },
    y: { ticks: { font:{size:9,family:'DM Mono'}, color:'#94a3b8' }, grid: { color:'#f1f5f9' } }
  }
};

// Sparklines
SITES.forEach((site, i) => {
  const ctx = document.getElementById('spark-' + site.id);
  if (!ctx || !(site.categories || []).includes('solar')) return;
  fetch(`/dashboard/${site.slug}/solar/chart-data?hours=1`)
    .then(r => r.json())
    .then(json => {
      if (!json.success || !json.datasets?.length) return;
      const color = COLORS[i % COLORS.length];
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: json.labels,
          datasets: [{ data: json.datasets[0].data, borderColor: color,
            backgroundColor: color+'18', borderWidth:1.5, pointRadius:0, tension:.4, fill:true }]
        },
        options: { ...opts, scales: { x:{display:false}, y:{display:false} }, plugins:{legend:{display:false}} }
      });
    }).catch(err => console.error('Chart data fetch failed:', err));
});

// Cross-site data
const crossSolar = {}, crossWater = {}, crossTemp = {}, crossTank = {};

function loadCrossData(hours, from, to) {
  const query = (from && to) ? `from=${from}&to=${to}` : `hours=${hours}`;
  SITES.forEach((site, i) => {
    const cats = site.categories || [];

    if (cats.includes('solar')) {
      fetch(`/dashboard/${site.slug}/solar/chart-data?${query}`)
        .then(r => r.json()).then(json => {
          if (json.success && json.datasets?.length) {
            crossSolar[site.id] = { name: site.name, labels: json.labels, data: json.datasets[0]?.data || [] };
          }
          buildCrossChart('chart-solar', crossSolar, 'legend-solar');
        }).catch(err => console.error('Chart data fetch failed:', err));
    }

    if (cats.includes('water')) {
      fetch(`/dashboard/${site.slug}/water/chart-data?${query}`)
        .then(r => r.json()).then(json => {
          if (json.success && json.datasets?.length) {
            crossWater[site.id] = { name: site.name, labels: json.labels, data: json.datasets[0]?.data || [] };
            const latest = json.datasets[0]?.data;
            crossTank[site.id] = { name: site.name, data: latest?.[latest.length-1] || 0 };
          }
          buildCrossChart('chart-water', crossWater, 'legend-water');
          buildTankChart();
        }).catch(err => console.error('Chart data fetch failed:', err));
    }

    if (cats.includes('weather')) {
      fetch(`/dashboard/${site.slug}/weather/chart-data?${query}`)
        .then(r => r.json()).then(json => {
          if (json.success && json.datasets?.length) {
            crossTemp[site.id] = { name: site.name, labels: json.labels, data: json.datasets[0]?.data || [] };
          }
          buildCrossChart('chart-temp', crossTemp, null);
        }).catch(err => console.error('Chart data fetch failed:', err));
    }
  });
}

const chartInstances = {};
function buildCrossChart(id, data, legendId) {
  const ctx = document.getElementById(id);
  if (!ctx) return;
  const ids = Object.keys(data);
  if (!ids.length) return;
  if (chartInstances[id]) chartInstances[id].destroy();
  const allLabels = data[ids[0]]?.labels || [];
  const datasets = ids.map((sid, i) => ({
    label: data[sid].name,
    data: data[sid].data,
    borderColor: COLORS[i % COLORS.length],
    backgroundColor: COLORS[i % COLORS.length]+'18',
    borderWidth: 2, pointRadius: 0, tension: .4, fill: ids.length === 1,
  }));
  chartInstances[id] = new Chart(ctx, { type:'line', data:{ labels:allLabels, datasets }, options:{...opts,plugins:{legend:{display:false}}} });
  if (legendId) {
    const leg = document.getElementById(legendId);
    if (leg) leg.innerHTML = ids.map((sid,i) =>
      `<span class="legend-item"><span class="legend-dot" style="background:${COLORS[i%COLORS.length]}"></span>${data[sid].name}</span>`
    ).join('');
  }
}

function buildTankChart() {
  const ctx = document.getElementById('chart-tank');
  if (!ctx) return;
  const ids = Object.keys(crossTank);
  if (!ids.length) return;
  if (chartInstances['chart-tank']) chartInstances['chart-tank'].destroy();
  chartInstances['chart-tank'] = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ids.map(id => crossTank[id].name),
      datasets: [{ data: ids.map(id => crossTank[id].data),
        backgroundColor: COLORS.slice(0, ids.length), borderRadius: 6 }]
    },
    options: { ...opts, scales: { x: opts.scales.x, y: { ...opts.scales.y, min:0, max:100 } } }
  });
}

function onRangeChange(r, from, to) {
  const h = r==='7d'?168:r==='24h'?24:r==='6h'?6:1;
  const label = (r === 'custom' && from && to) ? `${from} → ${to}` : 'last '+r.toUpperCase();
  ['solar','water','temp'].forEach(k => {
    const el = document.getElementById('lbl-'+k+'-range');
    if (el) el.textContent = label;
  });
  loadCrossData(h, from, to);
}

loadCrossData(1);
</script>
@endpush
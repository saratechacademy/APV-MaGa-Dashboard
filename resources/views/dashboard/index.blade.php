@extends('layouts.dashboard')
@section('page-title', 'All Sites')
@section('page-crumb', 'Overview')
@section('show-toolbar', '1')
@section('content')

{{-- Everything on this page is derived from parameter metrics (config/overview.php,
     App\Services\SiteOverview): each site shows the metrics it actually has, and
     the cross-site charts compare one metric at a time, so titles and units are
     always those of the data plotted. --}}

@php
  $iconWarnTriangle = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';
  $iconCheckCircle = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
  $metricInfo = config('overview.metrics');
  $statusUi = [
    'normal'   => ['card' => '',          'badge' => 'badge-ok',   'label' => 'Normal'],
    'warning'  => ['card' => 'warn-card', 'badge' => 'badge-warn', 'label' => 'Warning'],
    'critical' => ['card' => 'err-card',  'badge' => 'badge-err',  'label' => 'Critical'],
  ];
@endphp

{{-- Section: Sites --}}
<div class="sec-header">
  <span class="sec-title">Installation Sites</span>
  <span style="font-size:12px;color:var(--muted)">Updated: {{ now()->format('H:i:s') }} UTC</span>
</div>

<div class="cards-grid">
@forelse($sites ?? [] as $site)
  @php
    $card = $cards[$site->id];
    $ui   = $statusUi[$card['status']];
  @endphp
  <a href="{{ route('dashboard.site', $site) }}" class="site-card {{ $ui['card'] }}">
    <div class="card-head">
      <div class="card-name">{{ $site->name }}</div>
      <span class="country-chip">{{ strtoupper(substr($site->country, 0, 3)) }}</span>
      <span class="badge {{ $ui['badge'] }}" style="margin-left:auto;display:inline-flex;align-items:center;gap:4px">
        {!! $card['status'] === 'normal' ? $iconCheckCircle : $iconWarnTriangle !!} {{ $ui['label'] }}
      </span>
    </div>
    @if($card['tiles'])
      <div class="kpis">
        @foreach($card['tiles'] as $tile)
          {{-- A stale tile keeps its last value, greyed, and says how old it is:
               a month-old soil moisture must not read as the current one. --}}
          <div class="kpi" title="{{ $metricInfo[$tile['metric']]['title'] }} — last reading {{ $tile['at']->format('d M Y H:i') }} UTC">
            <div class="kpi-val" style="{{ $tile['stale'] ? 'color:var(--muted)' : '' }}">{{ number_format($tile['value'], abs($tile['value']) >= 100 ? 0 : 1) }}<span style="font-size:10px;color:var(--muted);margin-left:2px">{{ $tile['unit'] }}</span></div>
            <div class="kpi-lbl">{{ $tile['label'] }}</div>
            @if($tile['stale'])
              <div style="font-size:9px;color:var(--amber);margin-top:1px;white-space:nowrap">{{ $tile['at']->diffForHumans(null, true, true) }} ago</div>
            @endif
          </div>
        @endforeach
      </div>
    @else
      <div class="kpis" style="grid-template-columns:1fr">
        <div class="kpi"><div class="kpi-lbl" style="padding:8px 0">No sensor data yet</div></div>
      </div>
    @endif
    <div class="sparkline-wrap" style="position:relative">
      <canvas id="spark-{{ $site->id }}"></canvas>
      @if($card['sparkline'])
        <span style="position:absolute;top:0;left:0;font-size:9px;color:var(--muted)">{{ $metricInfo[$card['sparkline']]['title'] }} · <span class="lbl-range">last 24H</span></span>
      @endif
    </div>
    <div class="statuses">
      @foreach($site->activeCategories as $cat)
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

{{-- Cross-site charts: one per metric that at least one site has --}}
@if(!empty($chartMetrics))
<div class="sec-header" style="margin-top:20px">
  <span class="sec-title">Cross-Site Trends</span>
</div>
<div class="charts-row">
  @foreach($chartMetrics as $metric)
  <div class="chart-card">
    <div class="cc-head">
      <div>
        <div class="cc-title">{{ $metricInfo[$metric]['title'] }} ({{ $metricInfo[$metric]['unit'] }})</div>
        <div class="cc-sub">Site average · <span class="lbl-range">last 24H</span></div>
      </div>
    </div>
    <div style="position:relative;height:140px"><canvas id="chart-{{ $metric }}"></canvas></div>
    <div class="chart-legend" id="legend-{{ $metric }}"></div>
  </div>
  @endforeach
</div>
@endif

@endsection

@push('scripts')
<script>
const COLORS = @json(\App\Models\SiteParameterGroup::palette());

// A site keeps the same colour on every chart, whichever sites a chart includes.
const SITE_COLOR = {};
@json(($sites ?? collect())->pluck('id')->values()).forEach((id, i) => { SITE_COLOR[id] = COLORS[i % COLORS.length]; });

const CHART_METRICS = @json($chartMetrics ?? []);

const opts = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { ticks: { font:{size:9}, color:'#94a3b8', maxTicksLimit:5 }, grid: { color:'#f1f5f9' } },
    y: { ticks: { font:{size:9,family:'DM Mono'}, color:'#94a3b8' }, grid: { color:'#f1f5f9' } }
  }
};

const chartInstances = {};

function draw(canvasId, config) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return;
  if (chartInstances[canvasId]) { chartInstances[canvasId].destroy(); delete chartInstances[canvasId]; }
  if (config) chartInstances[canvasId] = new Chart(ctx, config);
}

function loadOverview(query) {
  fetch(`{{ route('dashboard.overview-data') }}?${query}`)
    .then(r => r.json())
    .then(json => {
      if (!json.success) return;

      CHART_METRICS.forEach(metric => {
        const series = json.series[metric] || [];
        const legend = document.getElementById('legend-' + metric);

        if (!series.length) {
          draw('chart-' + metric, null);
          if (legend) legend.innerHTML = '<span class="legend-item">No data for selected period</span>';
          return;
        }

        draw('chart-' + metric, {
          type: 'line',
          data: {
            labels: json.labels,
            datasets: series.map(s => ({
              label: s.name, data: s.data,
              borderColor: SITE_COLOR[s.site_id], backgroundColor: SITE_COLOR[s.site_id] + '18',
              borderWidth: 2, pointRadius: 0, tension: .4, fill: series.length === 1, spanGaps: true,
            })),
          },
          options: { ...opts, plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } } },
        });

        if (legend) legend.innerHTML = series.map(s =>
          `<span class="legend-item"><span class="legend-dot" style="background:${SITE_COLOR[s.site_id]}"></span>${s.name}</span>`
        ).join('');
      });

      // Sparklines: each site's own headline metric, from the same response.
      Object.entries(json.sparklines || {}).forEach(([siteId, metric]) => {
        const own = (json.series[metric] || []).find(s => String(s.site_id) === String(siteId));
        draw('spark-' + siteId, own && {
          type: 'line',
          data: { labels: json.labels, datasets: [{ data: own.data, borderColor: SITE_COLOR[siteId],
            backgroundColor: SITE_COLOR[siteId] + '18', borderWidth: 1.5, pointRadius: 0, tension: .4, fill: true, spanGaps: true }] },
          options: { ...opts, scales: { x: { display: false }, y: { display: false } }, plugins: { legend: { display: false }, tooltip: { enabled: false } } },
        });
      });
    })
    .catch(err => console.error('Overview data fetch failed:', err));
}

// Top-bar 1H/6H/24H/7D/Custom (layouts/dashboard.blade.php's setRange) calls this.
function onRangeChange(r, from, to) {
  const custom = r === 'custom' && from && to;
  const hours  = { '1h': 1, '6h': 6, '24h': 24, '7d': 168 }[r] ?? 24;

  document.querySelectorAll('.lbl-range').forEach(el => {
    el.textContent = custom ? `${from} → ${to}` : 'last ' + r.toUpperCase();
  });

  loadOverview(custom ? `from=${from}&to=${to}` : `hours=${hours}`);
}

loadOverview('hours=24');
</script>
@endpush

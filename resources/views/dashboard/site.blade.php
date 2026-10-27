@extends('layouts.dashboard')

@section('page-title', $site->name)
@section('page-crumb', 'Site Detail')
@section('show-toolbar', '1')

@push('styles')
<style>
.detail-tabs-bar{display:flex;background:#fff;border-bottom:2px solid #e4e8ef;margin:-20px -20px 20px;padding:0 20px;overflow-x:auto;scrollbar-width:none}
/* The page padding is 12px on phones (layout): the bar follows it. */
@media (max-width: 640px){ .detail-tabs-bar{margin:-12px -12px 16px;padding:0 12px} }
.d-tab{font-family:'DM Sans',sans-serif !important;font-size:13px;font-weight:500;padding:11px 15px;border:0 !important;border-bottom:3px solid transparent !important;background:none !important;cursor:pointer;color:#64748b;margin-bottom:-2px;transition:color .15s,border-color .15s;white-space:nowrap;flex-shrink:0;outline:none !important;box-shadow:none !important}
.d-tab.active{color:#1d6ed8 !important;border-bottom:3px solid #1d6ed8 !important}
.d-tab:hover:not(.active){color:#0d1321}
.tab-panel{display:none}.tab-panel.active{display:block}
.d-kpis{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:14px}
.badge-nominal{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:500;padding:3px 9px;border-radius:20px;background:var(--green-bg);color:var(--green);border:1px solid var(--green-bd)}
.badge-nominal::before{content:'';width:6px;height:6px;border-radius:50%;background:var(--green);display:inline-block}
.badge-warn-sm{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:500;padding:3px 9px;border-radius:20px;background:var(--amber-bg);color:var(--amber);border:1px solid var(--amber-bd)}
.badge-warn-sm::before{content:'';width:6px;height:6px;border-radius:50%;background:var(--amber);display:inline-block}
.badge-critical-sm{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:500;padding:3px 9px;border-radius:20px;background:var(--red-bg);color:var(--red);border:1px solid var(--red-bd)}
.badge-critical-sm::before{content:'';width:6px;height:6px;border-radius:50%;background:var(--red);display:inline-block}
.chart-section{margin-bottom:14px}
.chart-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);padding:16px}
.cc-head{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px}
/* A chart opens large over the page when clicked (see expandChart). */
.chart-section canvas{cursor:zoom-in}
/* Series legend above a chart, as HTML: wraps instead of eating the plot's
   height, and a click hides or shows a series (see htmlLegend). */
.chart-legend{display:flex;flex-wrap:wrap;gap:2px 14px;margin:-4px 0 8px}
.lg-item{display:inline-flex;align-items:center;gap:6px;border:0;background:none;padding:2px 0;font-family:'DM Sans',sans-serif;font-size:11px;color:var(--muted);cursor:pointer}
.lg-item:hover{color:var(--text)}
.lg-item.off{opacity:.45;text-decoration:line-through}
.lg-swatch{display:inline-block;width:16px;height:0;border-top-width:2px}
.chart-section.is-expanded{position:fixed;inset:24px;z-index:1001;display:flex;flex-direction:column;overflow:auto}
.chart-section.is-expanded canvas{cursor:default}
.chart-section.is-expanded .chart-area{flex:1;min-height:0;height:auto!important}
.chart-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:1000}
.chart-close{display:none;margin-left:auto;border:1px solid var(--border);background:var(--surface);border-radius:6px;padding:3px 10px;font-size:12px;color:var(--muted);cursor:pointer}
.chart-section.is-expanded .chart-close{display:inline-block}
@media (max-width:640px){.chart-section.is-expanded{inset:8px}}
.cc-title{font-size:13px;font-weight:600}
.charts-2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.charts-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}
.raw-table{width:100%;border-collapse:collapse;font-size:12px}
.raw-table th{background:var(--bg);border:1px solid var(--border);padding:7px 10px;text-align:left;font-weight:600;font-size:11px;color:var(--muted)}
.raw-table td{border:1px solid var(--border);padding:6px 10px;font-family:'DM Mono',monospace;font-size:11.5px}
.raw-table tr:hover td{background:#f8fafc}
.ag-input{font-family:'DM Sans',sans-serif;font-size:13px;padding:7px 10px;border:1px solid var(--border);border-radius:7px;background:var(--bg);color:var(--text);width:100%}
.ag-input:focus{outline:none;border-color:var(--blue);background:#fff}
.ag-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
textarea.ag-input{resize:vertical;min-height:60px}
select.ag-input{cursor:pointer}
.api-key-box{background:#0f1929;border:1px solid #1e2d45;border-radius:9px;padding:12px 16px;display:flex;align-items:center;gap:10px;margin-bottom:12px}
.api-key-val{font-family:'DM Mono',monospace;font-size:13px;color:#93c5fd;flex:1;word-break:break-all}
.btn-copy{font-size:11px;padding:4px 10px;border:1px solid #1e2d45;background:#1a2640;color:#94a3b8;border-radius:6px;cursor:pointer;font-family:'DM Sans',sans-serif}
.btn-copy:hover{background:#1e3254;color:#fff}
.dl-btn{font-family:'DM Sans',sans-serif;font-size:11px;padding:3px 10px;border:1px solid var(--border);background:var(--surface);border-radius:5px;cursor:pointer;color:var(--muted)}
.dl-btn:hover{background:var(--bg)}
.dl-row{display:flex;gap:5px;flex-shrink:0}

/* Actuator switch card */
.switch-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);padding:12px 14px;min-width:140px;max-width:180px;flex:1}
.switch-row{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:8px}
.toggle{position:relative;display:inline-block;width:38px;height:22px;flex-shrink:0}
.toggle input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;cursor:pointer;inset:0;background-color:#cbd5e1;transition:.2s;border-radius:22px}
.toggle-slider:before{position:absolute;content:"";height:16px;width:16px;left:3px;bottom:3px;background-color:#fff;transition:.2s;border-radius:50%}
.toggle input:checked + .toggle-slider{background-color:var(--green)}
.toggle input:checked + .toggle-slider:before{transform:translateX(16px)}
.toggle input:disabled + .toggle-slider{opacity:.5;cursor:not-allowed}
.sync-pill{font-size:10px;font-weight:500;padding:2px 7px;border-radius:20px;display:inline-flex;align-items:center;gap:4px}
.sync-ok{background:var(--green-bg);color:var(--green);border:1px solid var(--green-bd)}
.sync-pending{background:var(--amber-bg);color:var(--amber);border:1px solid var(--amber-bd)}
.sync-unknown{background:var(--bg);color:var(--muted);border:1px solid var(--border)}
.sync-stale{background:var(--amber-bg);color:var(--amber);border:1px solid var(--amber-bd)}
.badge-nodata{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:500;padding:3px 9px;border-radius:20px;background:var(--bg);color:var(--muted);border:1px dashed var(--border)}
.badge-nodata::before{content:'';width:6px;height:6px;border-radius:50%;background:var(--muted);display:inline-block}

/* Parameter groups + standalone cards share one packed grid: a group box spans
   as many columns as it needs, ungrouped cards fill in a single column each,
   and dense packing lets everything flow onto the same rows instead of one
   block per group. */
.param-groups-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));grid-auto-flow:row dense;gap:10px;margin-bottom:14px;align-items:start}
.param-group-box{border-radius:var(--r);padding:10px;min-width:0}
.pg-head{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;display:flex;align-items:center;gap:6px;margin-bottom:8px}
.pg-dot{width:8px;height:8px;border-radius:50%;display:inline-block;flex-shrink:0}
.pg-cards{display:flex;flex-wrap:wrap;gap:10px}
@media (max-width: 560px){
  .param-groups-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
  .param-group-box{grid-column:1 / -1 !important}
  .param-groups-grid > [style*="max-width:150px"]{max-width:none !important}
}


/* Actuator confirm modal */
.actuator-modal-overlay{display:none;position:fixed;inset:0;background:rgba(13,19,33,.5);z-index:2000;align-items:center;justify-content:center}
.actuator-modal-overlay.open{display:flex}
.actuator-modal{background:var(--surface);border-radius:var(--r);box-shadow:0 12px 32px rgba(0,0,0,.18);padding:24px;width:100%;max-width:340px;text-align:center}
.actuator-modal-icon{width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:22px;font-weight:700}
.actuator-modal-icon.on{background:var(--green-bg);color:var(--green)}
.actuator-modal-icon.off{background:var(--bg);color:var(--muted)}
.actuator-modal-title{font-size:15px;font-weight:600;margin-bottom:6px}
.actuator-modal-text{font-size:13px;color:var(--muted);margin-bottom:20px;line-height:1.5}
.actuator-modal-actions{display:flex;gap:10px}
.actuator-modal-actions button{flex:1;padding:9px;border-radius:8px;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:600;cursor:pointer;border:1px solid var(--border)}
.actuator-modal-cancel{background:var(--surface);color:var(--text)}
.actuator-modal-cancel:hover{background:var(--bg)}
.actuator-modal-confirm{background:var(--blue);color:#fff;border-color:var(--blue)}
.actuator-modal-confirm:hover{background:#1a5fc0}
.actuator-modal-confirm.danger{background:var(--red);border-color:var(--red)}
.actuator-modal-confirm.danger:hover{background:#9f0f30}
</style>
@endpush

@section('content')

@php
  $categories = $site->activeCategories ?? collect();
  $firstSlug  = $categories->first()?->slug ?? 'raw';
  $COLORS = \App\Models\SiteParameterGroup::palette();
  // The role held on this site decides, not the account's general role.
  $canWrite   = auth()->user()->canWriteTo($site);
  $canControl = $canWrite;
@endphp

{{-- TABS --}}
<div class="detail-tabs-bar">
  @forelse($categories as $cat)
    <button onclick="switchTab('{{ $cat->slug }}',this)" data-tab="{{ $cat->slug }}"
      style="font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;padding:11px 16px;border:none;border-bottom:{{ $loop->first ? '3px solid #1d6ed8' : '3px solid transparent' }};background:none;cursor:pointer;color:{{ $loop->first ? '#1d6ed8' : '#64748b' }};margin-bottom:-2px;white-space:nowrap;flex-shrink:0;outline:none;transition:color .15s">
      {{ $cat->icon }} {{ $cat->name }}
    </button>
  @empty
    <button onclick="switchTab('raw',this)" data-tab="raw"
      style="font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;padding:11px 16px;border:none;border-bottom:3px solid #1d6ed8;background:none;cursor:pointer;color:#1d6ed8;margin-bottom:-2px;white-space:nowrap;flex-shrink:0;outline:none">
      Data
    </button>
  @endforelse
  <button onclick="switchTab('raw',this)" data-tab="raw"
    style="font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;padding:11px 16px;border:none;border-bottom:3px solid transparent;background:none;cursor:pointer;color:#64748b;margin-bottom:-2px;white-space:nowrap;flex-shrink:0;outline:none">
    Raw Data
  </button>
  <div style="margin-left:auto;display:flex">
    @if(($hasManual ?? false) && $canWrite)
    <button onclick="switchTab('manual-input',this)" data-tab="manual-input"
      style="font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;padding:11px 16px;border:none;border-bottom:3px solid transparent;background:none;cursor:pointer;color:#64748b;margin-bottom:-2px;white-space:nowrap;flex-shrink:0;outline:none">
      Manual Input
    </button>
    @endif
    @if(auth()->user()->isAdmin() || (auth()->id() === $site->user_id && $canWrite))
    <button onclick="switchTab('apikey',this)" data-tab="apikey"
      style="font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;padding:11px 16px;border:none;border-bottom:3px solid transparent;background:none;cursor:pointer;color:#64748b;margin-bottom:-2px;white-space:nowrap;flex-shrink:0;outline:none">
      API Key
    </button>
    @endif
  </div>
</div>

@if(session('success'))
<div style="background:var(--green-bg);border:1px solid var(--green-bd);color:var(--green);border-radius:7px;padding:8px 14px;margin-bottom:16px;font-size:13px">
  {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:var(--red-bg);border:1px solid var(--red-bd);color:var(--red);border-radius:7px;padding:8px 14px;margin-bottom:16px;font-size:13px">
  {{ session('error') }}
</div>
@endif

{{-- DYNAMIC CATEGORY TABS --}}
@foreach($categories as $catIndex => $category)
@php
  $params     = $category->activeParameters ?? collect();
  $dashParams = $params->where('show_on_dashboard', true)->values();
  // Au-delà de ce délai sans donnée capteur, un paramètre est "No data"/"Stale".
  $offlineThresholdMinutes = $category->offlineThresholdMinutes();
  $catColor   = $category->color ?? $COLORS[$catIndex % count($COLORS)];
  $readings = [];
  $readingsAt = [];
  $actuators = [];
  $latestSensorAt = null;
  foreach ($params as $param) {
    if ($param->input_type === 'manual') {
      $latest = $param->latestManualReading;
      if ($latest) $readings[$param->slug] = $latest->value;
    } else {
      $latest = $param->latestReading;
      if ($latest) {
        $readings[$param->slug] = $latest->value ?? $latest->value_text;
        $readingsAt[$param->slug] = $latest->read_at;
        if (!$latestSensorAt || $latest->read_at->gt($latestSensorAt)) {
          $latestSensorAt = $latest->read_at;
        }
      }
    }
    if ($param->isControllable()) {
      $actuators[$param->slug] = $param->actuatorCommand;
    }
  }

  $isOnline = $latestSensorAt && $latestSensorAt->diffInMinutes(now()) <= $offlineThresholdMinutes;
@endphp

<div class="tab-panel {{ $catIndex === 0 ? 'active' : '' }}" id="tab-{{ $category->slug }}">

  @php $awaiting = config("overview.awaiting.{$category->slug}"); @endphp
  @if($awaiting && !$params->contains('metric', $awaiting['metric']))
  <div class="awaiting-source" style="display:flex;gap:12px;align-items:flex-start;padding:14px 16px;margin-bottom:16px;border:1px dashed var(--border);border-radius:var(--r);background:var(--surface)">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--muted);flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    <div>
      <div style="font-size:13px;font-weight:600">{{ $awaiting['title'] }}</div>
      <div style="font-size:12px;color:var(--muted);margin-top:2px">{{ $awaiting['text'] }}</div>
    </div>
  </div>
  @endif

  @php
    $sensorDashParams = $dashParams->filter(fn($p) => ($p->input_type ?? 'sensor') === 'sensor');
    $manualDashParams = $dashParams->filter(fn($p) => ($p->input_type ?? 'sensor') === 'manual');
  @endphp

  @php
    // Un groupe d'un seul paramètre n'apporte rien visuellement : traité comme standalone.
    $sensorByGroup     = $sensorDashParams->filter(fn($p) => $p->site_parameter_group_id)->groupBy('site_parameter_group_id');
    $sensorGroups      = $sensorByGroup->filter(fn($g) => $g->count() > 1)
                          ->sortBy(fn($gParams) => $gParams->first()->group->sort_order ?? 0);
    $sensorStandalone  = $sensorDashParams->filter(fn($p) => !$p->site_parameter_group_id)
                          ->merge($sensorByGroup->filter(fn($g) => $g->count() <= 1)->flatMap(fn($g) => $g));
  @endphp

  @if($sensorDashParams->count() > 0)
    <div class="param-groups-grid">
      @foreach($sensorGroups as $gParams)
      @php
        $g = $gParams->first()->group; $gc = $g->color ?: '#94a3b8';
        $span = min(6, max(2, $gParams->count()));
      @endphp
      <div class="param-group-box" style="grid-column:span {{ $span }};border:1px solid {{ $gc }}40;background:{{ $gc }}0d">
        <div class="pg-head" style="color:{{ $gc }}">
          <span class="pg-dot" style="background:{{ $gc }}"></span>
          {{ $g->name }}
        </div>
        <div class="pg-cards">
          @foreach($gParams as $param)
            @include('dashboard.partials._sensor-param-card', ['param' => $param])
          @endforeach
        </div>
      </div>
      @endforeach

      @foreach($sensorStandalone as $param)
        @include('dashboard.partials._sensor-param-card', ['param' => $param])
      @endforeach
    </div>
  @endif

  @php
    $manualByGroup     = $manualDashParams->filter(fn($p) => $p->site_parameter_group_id)->groupBy('site_parameter_group_id');
    $manualGroups      = $manualByGroup->filter(fn($g) => $g->count() > 1)
                          ->sortBy(fn($gParams) => $gParams->first()->group->sort_order ?? 0);
    $manualStandalone  = $manualDashParams->filter(fn($p) => !$p->site_parameter_group_id)
                          ->merge($manualByGroup->filter(fn($g) => $g->count() <= 1)->flatMap(fn($g) => $g));
  @endphp

  @if($manualDashParams->count() > 0)
    <div class="param-groups-grid">
      @foreach($manualGroups as $gParams)
      @php
        $g = $gParams->first()->group; $gc = $g->color ?: '#94a3b8';
        $span = min(6, max(2, $gParams->count()));
      @endphp
      <div class="param-group-box" style="grid-column:span {{ $span }};border:1px solid {{ $gc }}40;background:{{ $gc }}0d">
        <div class="pg-head" style="color:{{ $gc }}">
          <span class="pg-dot" style="background:{{ $gc }}"></span>
          {{ $g->name }}
        </div>
        <div class="pg-cards">
          @foreach($gParams as $param)
            @include('dashboard.partials._manual-param-card', ['param' => $param])
          @endforeach
        </div>
      </div>
      @endforeach

      @foreach($manualStandalone as $param)
        @include('dashboard.partials._manual-param-card', ['param' => $param])
      @endforeach
    </div>
  @endif

  @php $allManualParams = $params->filter(fn($p) => ($p->input_type ?? 'sensor') === 'manual'); @endphp
  @if($allManualParams->count() > 0 && ($hasManual ?? false) && $canWrite)
  <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--blue-bg);border:1px solid var(--blue-bd);border-radius:8px;margin-bottom:14px;font-size:12px;color:var(--blue)">
    <span>{{ $allManualParams->count() }} manual parameter(s) in this category.</span>
    <button onclick="switchTab('manual-input', document.querySelector('[data-tab=manual-input]'))"
            style="margin-left:auto;font-family:'DM Sans',sans-serif;font-size:12px;font-weight:500;padding:4px 12px;border:1px solid var(--blue-bd);border-radius:6px;background:#fff;color:var(--blue);cursor:pointer">
      Go to Manual Input →
    </button>
  </div>
  @endif

  {{-- Charts admin --}}
  @php $adminCharts = $category->activeCharts ?? collect(); @endphp
  @if($adminCharts->count() > 0)
    @php
      $fullCharts  = $adminCharts->where('col_span', 'full');
      $halfCharts  = $adminCharts->where('col_span', 'half');
      $thirdCharts = $adminCharts->where('col_span', 'third');
    @endphp
    @foreach($fullCharts as $chart)
    <div class="chart-card chart-section">
      <div class="cc-head">
        <div>
          <div class="cc-title">{{ $chart->title }}</div>
          <div class="cc-static-legend" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:4px">
            @foreach($chart->parameters as $p)
            <span style="display:flex;align-items:center;gap:4px;font-size:11px;color:var(--muted)">
              <span style="width:10px;height:{{ $p->pivot->dashed ? '0' : '3' }}px;{{ $p->pivot->dashed ? 'border-top:2px dashed '.$p->pivot->color : 'background:'.$p->pivot->color }};display:inline-block;border-radius:2px"></span>
              {{ $p->name }}@if($p->unit) ({{ $p->unit }})@endif
            </span>
            @endforeach
          </div>
        </div>
      </div>
      <div class="chart-area" style="position:relative;height:{{ $chart->height }}px">
        <canvas id="admin-chart-{{ $chart->id }}" data-site="{{ $site->slug }}" data-category="{{ $category->slug }}"></canvas>
      </div>
    </div>
    @endforeach
    @if($halfCharts->count() > 0)
    <div class="charts-2">
      @foreach($halfCharts as $chart)
      <div class="chart-card chart-section">
        <div class="cc-head">
          <div class="cc-title">{{ $chart->title }}</div>
        </div>
        <div class="chart-area" style="position:relative;height:{{ $chart->height }}px">
          <canvas id="admin-chart-{{ $chart->id }}" data-site="{{ $site->slug }}" data-category="{{ $category->slug }}"></canvas>
        </div>
      </div>
      @endforeach
    </div>
    @endif
    @if($thirdCharts->count() > 0)
    <div class="charts-3">
      @foreach($thirdCharts as $chart)
      <div class="chart-card chart-section">
        <div class="cc-head">
          <div class="cc-title">{{ $chart->title }}</div>
        </div>
        <div class="chart-area" style="position:relative;height:{{ $chart->height }}px">
          <canvas id="admin-chart-{{ $chart->id }}" data-site="{{ $site->slug }}" data-category="{{ $category->slug }}"></canvas>
        </div>
      </div>
      @endforeach
    </div>
    @endif
  @else
  @php $chartParams = $params->where('data_type','!=','string')->where('data_type','!=','boolean')->where('data_type','!=','switch')->values(); @endphp
  @if($chartParams->count() > 0)
  <div class="chart-card chart-section">
    <div class="cc-head">
      <div><div class="cc-title">{{ $category->icon }} {{ $category->name }} — Trends</div></div>
    </div>
    <div class="chart-area" style="position:relative;height:220px">
      <canvas id="chart-main-{{ $category->slug }}"></canvas>
    </div>
  </div>
  @endif
  @endif

  {{-- Saved Records pour cette categorie --}}
  @include('dashboard.partials.manual-records', ['category' => $category, 'site' => $site])

</div>
@endforeach

{{-- MANUAL INPUT TAB --}}
<div class="tab-panel" id="tab-manual-input">
  <div style="margin-bottom:18px">
    <div style="font-size:16px;font-weight:600">Manual Data Entry</div>
    <div style="font-size:12px;color:var(--muted);margin-top:3px">Enter field measurements for all categories</div>
  </div>
  @if(session('success'))
  <div style="background:var(--green-bg);border:1px solid var(--green-bd);color:var(--green);border-radius:7px;padding:8px 14px;margin-bottom:16px;font-size:13px">
    {{ session('success') }}
  </div>
  @endif
  @if($errors->any())
  <div style="background:var(--red-bg);border:1px solid var(--red-bd);color:var(--red);border-radius:7px;padding:8px 14px;margin-bottom:16px;font-size:13px">
    @foreach($errors->all() as $error)
      <div>{{ $error }}</div>
    @endforeach
  </div>
  @endif
  @foreach($site->activeCategories as $manCat)
  @php
    $manParams     = $manCat->activeParameters->where('input_type', 'manual');
    $manGrouped    = $manParams->filter(fn($p) => !empty($p->group_name))->groupBy('group_name');
    $manStandalone = $manParams->filter(fn($p) => empty($p->group_name));
    $totalCards    = $manGrouped->count() + $manStandalone->count();
    $manIndex      = 0;
    if($manParams->count() === 0) continue;
  @endphp
  <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;margin-top:20px">
    <span style="font-size:18px">{{ $manCat->icon }}</span>
    <span style="font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--muted)">{{ $manCat->name }}</span>
    <hr style="flex:1;border:none;border-top:1px solid var(--border)">
  </div>
  <div style="display:grid;grid-template-columns:repeat({{ min($totalCards, 4) }},1fr);gap:14px;margin-bottom:14px">
    @foreach($manGrouped as $gName => $gParams)
    <div class="chart-card" style="border-left:3px solid {{ $manCat->color ?? 'var(--blue)' }}">
      <div style="margin-bottom:12px">
        <div class="cc-title">{{ $gName }}</div>
        <div style="font-size:11px;color:var(--muted);margin-top:2px">{{ $gParams->count() }} parameter(s)</div>
      </div>
      <form method="POST" action="{{ route('manual-readings.store', $site) }}" onsubmit="this.querySelector('button[type=submit]').disabled=true">
        @csrf
        <div style="margin-bottom:10px">
          <label style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">Date *</label>
          <input type="date" name="reading_date" value="{{ date('Y-m-d') }}"
                 style="width:100%;margin-top:4px;font-family:'DM Sans',sans-serif;font-size:13px;padding:7px 10px;border:1px solid var(--border);border-radius:7px;background:var(--bg);color:var(--text)" required>
        </div>
        <div style="display:grid;grid-template-columns:minmax(0,3fr) minmax(0,2fr);gap:8px;margin-bottom:10px">
          <div>
            <label style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">Plot</label>
            <select name="zone" class="form-input" style="margin-top:4px">
              <option value="">Not specified</option>
              @foreach(config('analysis.plots') as $plotKey => $plotLabel)
              <option value="{{ $plotKey }}" @selected(old('zone') === $plotKey)>{{ $plotLabel }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">Plant</label>
            <input type="text" name="plant" value="{{ old('plant') }}" maxlength="30" placeholder="e.g. 3" class="form-input" style="margin-top:4px">
          </div>
        </div>
        @foreach($gParams as $p)
        @php $pi = $manIndex++; @endphp
        <div style="margin-bottom:8px">
          <label style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">
            {{ $p->name }}@if($p->unit) ({{ $p->unit }})@endif
          </label>
          <input type="hidden" name="readings[{{ $pi }}][param_id]" value="{{ $p->id }}">
          @if($p->data_type === 'boolean')
            <select name="readings[{{ $pi }}][value]" style="width:100%;margin-top:4px;font-family:'DM Sans',sans-serif;font-size:13px;padding:7px 10px;border:1px solid var(--border);border-radius:7px;background:var(--bg);color:var(--text)">
              <option value="">—</option><option value="1">Yes</option><option value="0">No</option>
            </select>
          @elseif($p->data_type === 'string')
            <input type="text" name="readings[{{ $pi }}][value]" placeholder="Enter value..."
                   style="width:100%;margin-top:4px;font-family:'DM Sans',sans-serif;font-size:13px;padding:7px 10px;border:1px solid var(--border);border-radius:7px;background:var(--bg);color:var(--text)">
          @else
            <input type="number" name="readings[{{ $pi }}][value]" step="any" placeholder="0"
                   @if($p->min_value !== null) min="{{ $p->min_value }}" @endif
                   @if($p->max_value !== null) max="{{ $p->max_value }}" @endif
                   style="width:100%;margin-top:4px;font-family:'DM Mono',monospace;font-size:18px;font-weight:500;padding:8px 10px;border:1px solid var(--border);border-radius:7px;background:var(--bg);color:var(--text)">
          @endif
        </div>
        @endforeach
        <div style="margin-top:10px">
          <input type="text" name="notes" placeholder="Notes (optional)..."
                 style="width:100%;font-family:'DM Sans',sans-serif;font-size:12px;padding:6px 10px;border:1px solid var(--border);border-radius:7px;background:var(--bg);color:var(--text);margin-bottom:8px">
          <button type="submit" class="btn btn-blue" style="width:100%;justify-content:center">+ Save {{ $gName }}</button>
        </div>
      </form>
    </div>
    @endforeach
    @foreach($manStandalone as $p)
    @php $pi = $manIndex++; @endphp
    <div class="chart-card" style="border-left:3px solid var(--muted)">
      <div style="margin-bottom:12px">
        <div class="cc-title">{{ $p->name }}</div>
        @if($p->unit)<div style="font-size:11px;color:var(--muted);margin-top:2px">{{ $p->unit }}</div>@endif
      </div>
      <form method="POST" action="{{ route('manual-readings.store', $site) }}" onsubmit="this.querySelector('button[type=submit]').disabled=true">
        @csrf
        <div style="margin-bottom:10px">
          <label style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">Date *</label>
          <input type="date" name="reading_date" value="{{ date('Y-m-d') }}"
                 style="width:100%;margin-top:4px;font-family:'DM Sans',sans-serif;font-size:13px;padding:7px 10px;border:1px solid var(--border);border-radius:7px;background:var(--bg);color:var(--text)" required>
        </div>
        <div style="display:grid;grid-template-columns:minmax(0,3fr) minmax(0,2fr);gap:8px;margin-bottom:10px">
          <div>
            <label style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">Plot</label>
            <select name="zone" class="form-input" style="margin-top:4px">
              <option value="">Not specified</option>
              @foreach(config('analysis.plots') as $plotKey => $plotLabel)
              <option value="{{ $plotKey }}" @selected(old('zone') === $plotKey)>{{ $plotLabel }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">Plant</label>
            <input type="text" name="plant" value="{{ old('plant') }}" maxlength="30" placeholder="e.g. 3" class="form-input" style="margin-top:4px">
          </div>
        </div>
        <input type="hidden" name="readings[{{ $pi }}][param_id]" value="{{ $p->id }}">
        @if($p->data_type === 'boolean')
          <select name="readings[{{ $pi }}][value]" style="width:100%;font-family:'DM Sans',sans-serif;font-size:13px;padding:7px 10px;border:1px solid var(--border);border-radius:7px;background:var(--bg);color:var(--text);margin-bottom:10px">
            <option value="">—</option><option value="1">Yes</option><option value="0">No</option>
          </select>
        @elseif($p->data_type === 'string')
          <input type="text" name="readings[{{ $pi }}][value]" placeholder="Enter value..."
                 style="width:100%;font-family:'DM Sans',sans-serif;font-size:13px;padding:7px 10px;border:1px solid var(--border);border-radius:7px;background:var(--bg);color:var(--text);margin-bottom:10px">
        @else
          <input type="number" name="readings[{{ $pi }}][value]" step="any" placeholder="0"
                 @if($p->min_value !== null) min="{{ $p->min_value }}" @endif
                 @if($p->max_value !== null) max="{{ $p->max_value }}" @endif
                 style="width:100%;font-family:'DM Mono',monospace;font-size:22px;font-weight:500;padding:8px 10px;border:1px solid var(--border);border-radius:7px;background:var(--bg);color:var(--text);margin-bottom:10px">
        @endif
        <input type="text" name="notes" placeholder="Notes..."
               style="width:100%;font-family:'DM Sans',sans-serif;font-size:12px;padding:6px 10px;border:1px solid var(--border);border-radius:7px;background:var(--bg);color:var(--text);margin-bottom:8px">
        <button type="submit" class="btn btn-blue" style="width:100%;justify-content:center">+ Save</button>
      </form>
    </div>
    @endforeach
  </div>
  @endforeach
</div>

{{-- RAW DATA TAB --}}
<div class="tab-panel" id="tab-raw">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
    <div style="display:flex;gap:6px" id="raw-period-btns">
      @foreach(['1h'=>'1H','6h'=>'6H','24h'=>'24H','7d'=>'7D','30d'=>'30D'] as $val=>$lbl)
      <button onclick="setRawPeriod('{{ $val }}', this)" data-period="{{ $val }}"
        style="font-family:'DM Sans',sans-serif;font-size:12px;font-weight:500;padding:5px 12px;border-radius:6px;cursor:pointer;border:1px solid {{ $val==='24h' ? '#1d6ed8' : 'var(--border)' }};background:{{ $val==='24h' ? '#1d6ed8' : 'var(--surface)' }};color:{{ $val==='24h' ? '#fff' : 'var(--muted)' }}">
        {{ $lbl }}
      </button>
      @endforeach
    </div>
    <div style="display:flex;gap:8px">
      <a href="/export/{{ $site->slug }}/all/csv?hours=24" id="btn-all-csv" class="btn" style="text-decoration:none">All CSV</a>
      <a href="/export/{{ $site->slug }}/all/excel?hours=24" id="btn-all-excel" class="btn btn-blue" style="text-decoration:none">All Excel</a>
    </div>
  </div>

  <div id="raw-data-container">
    @php
      $rawFrom    = now()->subHour();
      $rawPerPage = 20;
      $rawPage    = 1;
    @endphp
    @foreach($categories as $category)
    @php
      $params       = $category->activeParameters ?? collect();
      $sensorParams = $params->where('input_type', 'sensor');
      $manualParams = $params->where('input_type', 'manual');
      $sensorReadings = \App\Models\SensorReading::where('site_id', $site->id)
        ->whereIn('site_parameter_id', $sensorParams->pluck('id'))
        ->where('read_at', '>=', $rawFrom)
        ->orderBy('read_at', 'desc')->get();
      $sensorGrouped = $sensorReadings->groupBy(fn($r) => $r->read_at->format('Y-m-d H:i:s'));
      $manualReadings = \App\Models\ManualReading::where('site_id', $site->id)
        ->whereIn('site_parameter_id', $manualParams->pluck('id'))
        ->where('reading_date', '>=', $rawFrom)
        ->orderBy('reading_date', 'desc')->get();
      $manualGrouped = $manualReadings->groupBy(fn($r) => $r->reading_date->format('Y-m-d H:i:s'));
      $totalSensor = $sensorGrouped->count();
      $totalManual = $manualGrouped->count();
      $hasData     = $totalSensor > 0 || $totalManual > 0;
      $totalPages  = max(1, (int) ceil(max($totalSensor, $totalManual) / $rawPerPage));
      $sensorPaged = $sensorGrouped->take($rawPerPage);
      $manualPaged = $manualGrouped->take($rawPerPage);
    @endphp
    @if($hasData)
    @include('dashboard.partials.raw-table', compact('category','sensorParams','manualParams','sensorPaged','manualPaged','totalSensor','totalManual','totalPages','rawPage'))
    @endif
    @endforeach
  </div>

  {{-- Manual Records par categorie --}}
  @foreach($categories as $rawCat)
  @php
    $hasGroups = $rawCat->activeParameters
      ->where('input_type','manual')
      ->filter(fn($p) => !empty($p->group_name))->isNotEmpty();
  @endphp
  @if($hasGroups)
  <div style="margin-top:20px;display:flex;align-items:center;gap:8px;margin-bottom:-6px">
    <span style="font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px">{{ $rawCat->icon }} {{ $rawCat->name }}</span>
    <hr style="flex:1;border:none;border-top:1px solid var(--border)">
  </div>
  @include('dashboard.partials.manual-records', ['category' => $rawCat, 'site' => $site])
  @endif
  @endforeach

</div>

{{-- API KEY TAB --}}
@if(auth()->user()->isAdmin() || (auth()->id() === $site->user_id && $canWrite))
<div class="tab-panel" id="tab-apikey">
  <div class="chart-card" style="margin-bottom:14px">
    <div class="cc-title" style="margin-bottom:10px">API Key — {{ $site->name }}</div>
    <div class="api-key-box">
      <div class="api-key-val" id="api-key-val">{{ $site->api_key }}</div>
      <button class="btn-copy" onclick="copyKey()">Copy</button>
    </div>
    <div style="font-size:12px;color:var(--muted)">
      Use in the <code style="font-family:'DM Mono',monospace;background:var(--bg);padding:1px 5px;border-radius:4px">X-API-Key</code> header when posting sensor data.
    </div>
  </div>
  <div class="chart-card">
    <div class="cc-title" style="margin-bottom:10px">Dynamic Endpoints</div>
    <div style="overflow-x:auto">
    <table class="raw-table">
      <thead><tr><th>Method</th><th>Endpoint</th><th>Description</th></tr></thead>
      <tbody>
        <tr><td>POST</td><td>/api/sensors/{{ $site->slug }}/{category}</td><td>Send sensor readings</td></tr>
        <tr><td>GET</td><td>/api/sensors/{{ $site->slug }}/{category}/latest</td><td>Get latest readings</td></tr>
        <tr><td>GET</td><td>/api/sensors/{{ $site->slug }}/status</td><td>Check all categories status</td></tr>
        <tr><td>GET</td><td>/api/commands/{{ $site->slug }}/{category}</td><td>Get actuator commands (switches)</td></tr>
      </tbody>
    </table>
    </div>
    <div style="margin-top:14px">
      <div style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:8px">Available categories & parameters:</div>
      @foreach($categories as $cat)
      <div style="margin-bottom:8px;padding:10px 12px;background:var(--bg);border-radius:7px;border:1px solid var(--border)">
        <div style="font-size:12px;font-weight:600;margin-bottom:4px">{{ $cat->slug }}</div>
        <div style="display:flex;flex-wrap:wrap;gap:6px">
          @foreach($cat->activeParameters->where('input_type','sensor') as $p)
          <code style="font-family:'DM Mono',monospace;font-size:11px;background:var(--surface);padding:2px 7px;border-radius:4px;border:1px solid var(--border);{{ $p->isControllable() ? 'border-color:var(--blue-bd);color:var(--blue)' : '' }}">{{ $p->slug }}{{ $p->isControllable() ? ' (switch)' : '' }}</code>
          @endforeach
        </div>
      </div>
      @endforeach
    </div>
  </div>
</div>
@endif

{{-- Actuator confirm modal --}}
<div class="actuator-modal-overlay" id="actuator-modal-overlay">
  <div class="actuator-modal">
    <div class="actuator-modal-icon" id="actuator-modal-icon">!</div>
    <div class="actuator-modal-title" id="actuator-modal-title">Confirm action</div>
    <div class="actuator-modal-text" id="actuator-modal-text"></div>
    <div class="actuator-modal-actions">
      <button class="actuator-modal-cancel" onclick="closeActuatorModal(false)">Cancel</button>
      <button class="actuator-modal-confirm" id="actuator-modal-confirm" onclick="closeActuatorModal(true)">Confirm</button>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
const COLORS = @json(\App\Models\SiteParameterGroup::palette());
const SITE_SLUG = '{{ $site->slug }}';
let HOURS = {{ match($range ?? '1h') { '6h' => 6, '24h' => 24, '7d' => 168, '30d' => 720, default => 1 } }};

function switchTab(tab, btn) {
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('[data-tab]').forEach(b => {
    b.style.borderBottom = '3px solid transparent';
    b.style.color = '#64748b';
  });
  const panel = document.getElementById('tab-' + tab);
  if (panel) panel.classList.add('active');
  const activeBtn = btn || document.querySelector('[data-tab="' + tab + '"]');
  if (activeBtn) {
    activeBtn.style.borderBottom = '3px solid #1d6ed8';
    activeBtn.style.color = '#1d6ed8';
    // On narrow screens the tab bar scrolls: bring the chosen tab into view.
    activeBtn.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' });
  }
  const cat = (typeof catCharts !== 'undefined') ? catCharts.find(c => c.slug === tab) : null;
  if (cat) setTimeout(() => { buildMainChart(cat); buildLegend(cat); }, 60);

  // The Raw Data tab's initial content is server-rendered for "last 1h" only
  // (no upper bound refresh happens otherwise) — refetch with whichever
  // period is currently selected so switching to this tab never shows a
  // stale snapshot from page load.
  if (tab === 'raw' && typeof fetchRawData === 'function') fetchRawData();
}

function copyKey() {
  const val = document.getElementById('api-key-val')?.textContent?.trim();
  if (val) { navigator.clipboard.writeText(val); alert('API Key copied!'); }
}

let actuatorPendingCheckbox = null;

function openActuatorModal(checkbox, paramName, newState, isOnline) {
  actuatorPendingCheckbox = checkbox;

  const isOn = newState === 1;
  const icon    = document.getElementById('actuator-modal-icon');
  const title   = document.getElementById('actuator-modal-title');
  const text    = document.getElementById('actuator-modal-text');
  const confirm = document.getElementById('actuator-modal-confirm');

  icon.textContent = isOn ? 'ON' : 'OFF';
  icon.className = 'actuator-modal-icon ' + (isOn ? 'on' : 'off');
  title.textContent = (isOn ? 'Turn ON ' : 'Turn OFF ') + paramName + '?';

  let msg = isOn
    ? `The device will activate "${paramName}" on its next check-in.`
    : `The device will deactivate "${paramName}" on its next check-in.`;

  if (!isOnline) {
    msg += ` Note: this device has not reported data recently and may be offline — the command will be applied once it reconnects.`;
  }
  text.textContent = msg;

  confirm.textContent = isOn ? 'Turn ON' : 'Turn OFF';
  confirm.className = 'actuator-modal-confirm' + (isOn ? '' : ' danger');

  document.getElementById('actuator-modal-overlay').classList.add('open');
}

function closeActuatorModal(confirmed) {
  const overlay = document.getElementById('actuator-modal-overlay');
  overlay.classList.remove('open');

  const checkbox = actuatorPendingCheckbox;
  actuatorPendingCheckbox = null;
  if (!checkbox) return;

  if (confirmed) {
    checkbox.closest('form').submit();
  } else {
    // Revert toggle to previous state
    checkbox.checked = !checkbox.checked;
  }
}

const baseOpts = {
  responsive:true, maintainAspectRatio:false,
  plugins:{legend:{display:false}},
  scales:{
    x:{ticks:{font:{size:10},color:'#94a3b8',maxTicksLimit:6},grid:{color:'#f1f5f9'}},
    y:{ticks:{font:{size:10,family:'DM Mono'},color:'#94a3b8'},grid:{color:'#f1f5f9'}}
  }
};

@php
$catChartsData = $categories->map(function($cat, $catIdx) use ($COLORS) {
  $params = $cat->activeParameters->where('data_type','!=','string')->where('data_type','!=','boolean')->where('data_type','!=','switch')->values();
  return [
    'slug'   => $cat->slug,
    'dbData' => [],
    'params' => $params->map(function($p, $i) use ($COLORS) {
      return [
        'slug'       => $p->slug,
        'name'       => $p->name,
        'unit'       => $p->unit,
        'color'      => $COLORS[$i % count($COLORS)],
        'show'       => (bool)$p->show_on_dashboard,
        'input_type' => $p->input_type ?? 'sensor',
      ];
    })->values()
  ];
})->values();
@endphp
const catCharts = @json($catChartsData);
const activeParams = {};

// Custom date range picked from the topbar's "Custom" popover overrides HOURS
// when set (see onRangeChange below); cleared whenever a preset (1H/6H/...) is picked.
// Named distinctly from layouts/dashboard.blade.php's own customFrom/customTo
// (used there only for exportData()) since both scripts share the page's global scope.
let siteCustomFrom = null, siteCustomTo = null;
function dateRangeQuery() {
  return (siteCustomFrom && siteCustomTo) ? `from=${siteCustomFrom}&to=${siteCustomTo}` : `hours=${HOURS}`;
}

// Every chart of a category draws from the same chart-data response: one
// request shared by all of them (a category can have seven charts), reused for
// a few seconds so a refresh costs one request per category, not per chart.
const chartDataRequests = {};
function fetchCategoryData(catSlug) {
  const url = `/dashboard/${SITE_SLUG}/${catSlug}/chart-data?${dateRangeQuery()}`;
  const hit = chartDataRequests[url];
  if (hit && Date.now() - hit.at < 5000) return hit.promise;
  const promise = fetch(url).then(r => r.json());
  chartDataRequests[url] = { at: Date.now(), promise };
  return promise;
}

// A line drawn across a long silence (power cut, gateway down) reads as
// measurements that were never taken. Segments spanning far more time than the
// series' usual interval — at least two hours — are left blank instead.
function gapSegments(times, values) {
  const seen = [];
  values.forEach((v, i) => { if (v !== null && v !== undefined) seen.push(i); });
  const steps = seen.slice(1).map((idx, n) => times[idx] - times[seen[n]]).sort((a, b) => a - b);
  const usual = steps.length ? steps[Math.floor(steps.length / 2)] : 0;
  const limit = Math.max(2 * 3600, 6 * usual);

  // Chart.js asks about each pair of neighbouring slots, empty ones included,
  // so the silence has to be marked slot by slot: every slot after a reading
  // and up to the next one, when those two readings are too far apart.
  const silent = [];
  for (let n = 1; n < seen.length; n++) {
    if (times[seen[n]] - times[seen[n - 1]] > limit) {
      for (let i = seen[n - 1] + 1; i <= seen[n]; i++) silent[i] = true;
    }
  }
  const hidden = ctx => silent[ctx.p1DataIndex] ? 'transparent' : undefined;
  return { borderColor: hidden, backgroundColor: hidden };
}

async function loadCategoryData(cat) {
  try {
    const json = await fetchCategoryData(cat.slug);
    cat.dbTimes = json.timestamps || [];
    if (json.success && json.datasets && json.datasets.length > 0) {
      const lbls = json.labels;
      cat.dbData = lbls.map((t, i) => {
        const row = { t };
        json.datasets.forEach(ds => { row[ds.slug] = ds.data[i] ?? null; });
        return row;
      });
    } else { cat.dbData = []; cat.lastReadingAt = json.last_reading_at; }
  } catch(e) { cat.dbData = []; }
  buildMainChart(cat);
  buildLegend(cat);
}

catCharts.forEach(cat => {
  activeParams[cat.slug] = {};
  cat.params.forEach(p => { activeParams[cat.slug][p.slug] = p.show; });
  loadCategoryData(cat);
});

@if(session('active_tab'))
setTimeout(() => {
  const tab = '{{ session("active_tab") }}';
  const btn = document.querySelector('[data-tab="' + tab + '"]');
  switchTab(tab, btn);
}, 150);
@endif

// "No data" hides the canvas rather than replacing it: replacing it removed
// the canvas for good, so picking a longer range (24H, 7D...) could never
// draw the chart again.
function showNoData(ctx, empty, lastReadingAt) {
  let msg = ctx.parentElement.querySelector('.no-data-msg');
  if (empty && !msg) {
    msg = document.createElement('div');
    msg.className = 'no-data-msg';
    msg.style.cssText = 'display:flex;flex-direction:column;gap:4px;align-items:center;justify-content:center;height:100%;color:var(--muted);font-size:13px;text-align:center';
    ctx.parentElement.appendChild(msg);
  }
  if (empty) {
    const last = lastReadingAt
      ? 'Last reading: ' + new Date(lastReadingAt).toLocaleString(undefined, { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit', timeZone: 'UTC' }) + ' UTC'
      : 'No reading received yet';
    msg.innerHTML = '<span>No data for selected period</span><span style="font-size:11px">' + last + '</span>';
  }
  if (msg) msg.style.display = empty ? 'flex' : 'none';
  ctx.style.display = empty ? 'none' : '';
}

function buildMainChart(cat) {
  const ctx = document.getElementById('chart-main-' + cat.slug);
  if (!ctx) return;
  if (ctx._chartInstance) { ctx._chartInstance.destroy(); ctx._chartInstance = null; }
  const activeList = cat.params.filter(p => activeParams[cat.slug][p.slug]);
  if (activeList.length === 0) { ctx._chartInstance = null; return; }
  const dbData = cat.dbData || [];
  if (!dbData.length) {
    showNoData(ctx, true, cat.lastReadingAt);
    return;
  }
  showNoData(ctx, false);
  const chartLabels = dbData.map(r => r.t);
  let needSecondAxis = false, secParam = null;
  if (activeList.length > 1) {
    const maxes = activeList.map(p => Math.max(...dbData.map(r => r[p.slug] ?? 0).filter(v => v !== null)));
    const maxVal = Math.max(...maxes), minVal = Math.min(...maxes.filter(v => v > 0));
    if (maxVal / (minVal || 1) > 8) { needSecondAxis = true; secParam = activeList[maxes.indexOf(maxVal)]; }
  }
  const datasets = activeList.map(p => {
    const isSecondary = needSecondAxis && secParam && p.slug === secParam.slug;
    const values = dbData.map(r => r[p.slug] !== undefined ? r[p.slug] : null);
    return {
      label: p.name + (p.unit ? ' ('+p.unit+')' : ''),
      data: values, segment: gapSegments(cat.dbTimes || [], values),
      borderColor: p.color, backgroundColor: p.color + '15',
      borderWidth: 2, pointRadius: dbData.length <= 20 ? 3 : 0,
      pointHoverRadius: 5, tension: .4, fill: activeList.length === 1,
      yAxisID: isSecondary ? 'y2' : 'y', borderDash: isSecondary ? [5,4] : [], spanGaps: true,
    };
  });
  const scales = { x: baseOpts.scales.x, y: { ...baseOpts.scales.y, position: 'left' } };
  if (needSecondAxis && secParam) {
    scales.y2 = { position:'right', ticks:{font:{size:10,family:'DM Mono'},color:'#94a3b8'}, grid:{drawOnChartArea:false} };
  }
  ctx._chartInstance = new Chart(ctx, {
    type: 'line', data: { labels: chartLabels, datasets },
    options: { ...baseOpts, scales, plugins: { ...baseOpts.plugins, tooltip: { mode:'index', intersect:false } } }
  });
}

function buildLegend(cat) {
  const leg = document.getElementById('legend-' + cat.slug);
  if (!leg) return;
  leg.innerHTML = cat.params.filter(p => activeParams[cat.slug][p.slug])
    .map(p => `<span style="display:flex;align-items:center;gap:4px;font-size:11px;color:var(--muted)">
      <span style="width:10px;height:3px;background:${p.color};display:inline-block;border-radius:2px"></span>
      ${esc(p.name)}${p.unit ? ' ('+esc(p.unit)+')' : ''}</span>`).join('');
}

// Legend above a chart: each series' colour and line style, and its name
// without the part all of them share ("APV 1 Shadow – Moisture (%)" in a
// "Soil moisture (%)" chart reads "APV 1 Shadow"). A click hides or shows the
// series. Full-width charts list their series in their header instead.
function htmlLegend(canvas, chart) {
  const card = canvas.closest('.chart-section');
  if (!card || card.querySelector('.cc-static-legend')) return;
  let box = card.querySelector('.chart-legend');
  if (!box) {
    box = document.createElement('div');
    box.className = 'chart-legend';
    card.querySelector('.chart-area').before(box);
  }
  const names = shortLabels(chart.data.datasets.map(d => d.label));
  box.innerHTML = chart.data.datasets.map((d, i) =>
    `<button type="button" class="lg-item" data-i="${i}" title="${esc(d.label)}">` +
    `<span class="lg-swatch" style="border-top-style:${d.borderDash && d.borderDash.length ? 'dashed' : 'solid'};border-top-color:${d.borderColor}"></span>${esc(names[i])}</button>`
  ).join('');
  box.querySelectorAll('.lg-item').forEach(item => item.addEventListener('click', () => {
    const i = +item.dataset.i;
    const shown = chart.isDatasetVisible(i);
    chart.setDatasetVisibility(i, !shown);
    item.classList.toggle('off', shown);
    chart.update();
  }));
}

function shortLabels(labels) {
  const cut = labels.map(l => l.lastIndexOf(' – '));
  const tails = labels.map((l, i) => cut[i] > 0 ? l.slice(cut[i]) : null);
  if (labels.length > 1 && tails.every(t => t && t === tails[0])) {
    return labels.map((l, i) => l.slice(0, cut[i]));
  }
  // Different quantities in one chart (wind and gusts): at least drop the
  // unit they share, which the chart title already gives.
  const units = labels.map(l => (l.match(/ \([^()]*\)$/) || [null])[0]);
  return labels.length > 1 && units.every(u => u && u === units[0])
    ? labels.map(l => l.slice(0, -units[0].length))
    : labels;
}

// Admin charts via fetch — one loader per configured chart, callable again
// whenever the top-bar time range changes (see onRangeChange below).
const adminChartLoaders = [];

function loadAdminChart(chartId, catSlug, paramConfigs, hasSecondAxis, chartType, showLegend) {
  const ctx = document.getElementById('admin-chart-' + chartId);
  if (!ctx) return;
  fetchCategoryData(catSlug)
    .then(json => {
      if (ctx._chartInstance) { ctx._chartInstance.destroy(); ctx._chartInstance = null; }
      if (!json.success || !json.datasets || !json.datasets.length) {
        showNoData(ctx, true, json.last_reading_at);
        return;
      }
      showNoData(ctx, false);
      let chartLabels = json.labels;
      const dataMap = {};
      json.datasets.forEach(ds => { dataMap[ds.slug] = ds.data; });

      // The response's axis has a slot for every reading of every parameter
      // of the category. A bar chart (a value per day) keeps only the slots
      // where one of its own series has a value, labelled by date; otherwise
      // each bar would be one slot among thousands — a hairline.
      if (chartType === 'bar' && json.timestamps) {
        const day   = ts => { const d = new Date(ts * 1000).toISOString(); return d.slice(8, 10) + '/' + d.slice(5, 7); };
        const days  = [];           // in order of appearance
        const byDay = {};           // slug => day => value
        let daily   = true;         // does every series have at most one value a day?
        const keep  = [];           // slots where one of this chart's series has a value
        json.timestamps.forEach((ts, i) => {
          let used = false;
          paramConfigs.forEach(p => {
            const v = (dataMap[p.slug] || [])[i];
            if (v == null) return;
            used = true;
            const d = day(ts);
            if (!days.includes(d)) days.push(d);
            if ((byDay[p.slug] ??= {})[d] !== undefined) daily = false;
            byDay[p.slug][d] = v;
          });
          if (used) keep.push(i);
        });

        if (daily) {
          // A value per day (daily totals): one slot per date, so series of
          // the same day line up whatever the time of their reading.
          chartLabels = days;
          paramConfigs.forEach(p => { dataMap[p.slug] = days.map(d => byDay[p.slug]?.[d] ?? null); });
        } else {
          // Ordinary readings drawn as bars: every reading keeps its bar.
          chartLabels = keep.map(i => json.labels[i]);
          paramConfigs.forEach(p => { if (dataMap[p.slug]) dataMap[p.slug] = keep.map(i => dataMap[p.slug][i]); });
        }
        json = { ...json, timestamps: [] };
      }

      const blank = Array(chartLabels.length).fill(null);
      const datasets = paramConfigs.map(p => ({
        label: p.label, data: dataMap[p.slug] || blank,
        segment: chartType === 'bar' ? undefined : gapSegments(json.timestamps || [], dataMap[p.slug] || blank),
        borderColor: p.color, backgroundColor: p.color + (chartType === 'bar' ? 'cc' : (p.fill ? '22' : '00')),
        borderWidth: 2, pointRadius: chartLabels.length <= 20 ? 3 : 0,
        tension: .4, fill: p.fill, borderDash: p.dashed ? [5,4] : [], yAxisID: p.axis, spanGaps: true,
      }));
      const scales = { x: baseOpts.scales.x, y: { ...baseOpts.scales.y, position: 'left' } };
      if (hasSecondAxis) scales.y2 = { position:'right', ticks:{font:{size:10,family:'DM Mono'},color:'#94a3b8'}, grid:{drawOnChartArea:false} };
      ctx._chartInstance = new Chart(ctx, {
        type: chartType,
        data: { labels: chartLabels, datasets },
        options: { ...baseOpts, scales }
      });
      if (showLegend) htmlLegend(ctx, ctx._chartInstance);
    }).catch(() => {});
}

@foreach($site->activeCategories ?? [] as $cat)
  @foreach($cat->activeCharts ?? [] as $chart)
  adminChartLoaders.push(() => loadAdminChart(
    {{ $chart->id }},
    '{{ $cat->slug }}',
    [
      @foreach($chart->parameters as $p)
      { slug: @json($p->slug), label: @json($p->name . ($p->unit ? " ({$p->unit})" : '')), color: '{{ $p->pivot->color }}', fill: {{ $p->pivot->fill ? 'true' : 'false' }}, dashed: {{ $p->pivot->dashed ? 'true' : 'false' }}, axis: '{{ $p->pivot->axis === "right" ? "y2" : "y" }}' },
      @endforeach
    ],
    {{ $chart->parameters->where('pivot.axis','right')->count() > 0 ? 'true' : 'false' }},
    '{{ $chart->chart_type === "area" ? "line" : $chart->chart_type }}',
    {{ $chart->show_legend ? 'true' : 'false' }}
  ));
  @endforeach
@endforeach
adminChartLoaders.forEach(load => load());

// A chart opens large over the page when clicked, with more dates on its
// axis; ✕, Esc or a click outside puts it back.
function expandChart(card) {
  if (card.classList.contains('is-expanded')) return;
  const backdrop = document.createElement('div');
  backdrop.className = 'chart-backdrop';
  backdrop.addEventListener('click', () => collapseChart(card));
  document.body.appendChild(backdrop);
  card._backdrop = backdrop;
  card.classList.add('is-expanded');
  resizeCharts(card, 14);
}

function collapseChart(card) {
  card.classList.remove('is-expanded');
  card._backdrop?.remove();
  resizeCharts(card, 6);
}

function resizeCharts(card, ticks) {
  card.querySelectorAll('canvas').forEach(c => {
    const chart = c._chartInstance;
    if (!chart) return;
    // A copy: every chart's x axis shares baseOpts.scales.x.
    chart.config.options.scales.x = { ...baseOpts.scales.x, ticks: { ...baseOpts.scales.x.ticks, maxTicksLimit: ticks } };
    // The canvas keeps its last size in pixels, which would hold the grid
    // column at the enlarged width after closing: let it shrink, then let
    // Chart.js measure the container once the layout has settled.
    c.style.width = '0px';
    c.style.height = '0px';
    requestAnimationFrame(() => { chart.resize(); chart.update('none'); });
  });
}

document.querySelectorAll('.chart-section').forEach(card => {
  const close = document.createElement('button');
  close.type = 'button';
  close.className = 'chart-close';
  close.textContent = '✕ Close';
  close.addEventListener('click', e => { e.stopPropagation(); collapseChart(card); });
  (card.querySelector('.cc-head') || card).appendChild(close);
  card.querySelector('.chart-area')?.addEventListener('click', () => expandChart(card));
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.chart-section.is-expanded').forEach(collapseChart);
});

// Top-bar 1H/6H/24H/7D buttons (layouts/dashboard.blade.php's setRange) call this.
function onRangeChange(r, from, to) {
  if (r === 'custom' && from && to) {
    siteCustomFrom = from; siteCustomTo = to;
  } else {
    siteCustomFrom = null; siteCustomTo = null;
    const hoursMap = { '1h': 1, '6h': 6, '24h': 24, '7d': 168 };
    HOURS = hoursMap[r] ?? 1;
  }
  // Reload every tab's data (not just the active one) so switching tabs
  // afterward shows the new range instead of a stale cached one.
  catCharts.forEach(cat => loadCategoryData(cat));
  adminChartLoaders.forEach(load => load());
}

let rawCurrentHours = 24, rawCurrentPage = 1;

function setRawPeriod(period, btn) {
  document.querySelectorAll('#raw-period-btns button').forEach(b => {
    b.style.background = 'var(--surface)'; b.style.color = 'var(--muted)'; b.style.borderColor = 'var(--border)';
  });
  btn.style.background = '#1d6ed8'; btn.style.color = '#fff'; btn.style.borderColor = '#1d6ed8';
  const map = {'1h':1,'6h':6,'24h':24,'7d':168,'30d':720};
  rawCurrentHours = map[period] || 1; rawCurrentPage = 1;
  const csvBtn = document.getElementById('btn-all-csv');
  const excelBtn = document.getElementById('btn-all-excel');
  if (csvBtn)   csvBtn.href   = `/export/${SITE_SLUG}/all/csv?hours=${rawCurrentHours}`;
  if (excelBtn) excelBtn.href = `/export/${SITE_SLUG}/all/excel?hours=${rawCurrentHours}`;
  fetchRawData();
}

// Phones get one list per sensor; the timestamp table is for wider screens.
const rawPhone = window.matchMedia('(max-width: 640px)');

function fetchRawData(page) {
  if (page) rawCurrentPage = page;
  const container = document.getElementById('raw-data-container');
  if (!container) return;
  container.innerHTML = '<div style="text-align:center;padding:30px;color:var(--muted);font-size:13px">Loading...</div>';
  const url = rawPhone.matches
    ? `/dashboard/sites/${SITE_SLUG}/raw-sensors?hours=${rawCurrentHours}`
    : `/dashboard/sites/${SITE_SLUG}/raw-data?hours=${rawCurrentHours}&page=${rawCurrentPage}`;
  fetch(url)
    .then(r => r.text()).then(html => { container.innerHTML = html; })
    .catch(() => { container.innerHTML = '<div style="text-align:center;padding:30px;color:var(--muted)">Error loading data</div>'; });
}

function rawSensorMore(btn, parameterId, before) {
  btn.disabled = true;
  btn.textContent = 'Loading...';
  fetch(`/dashboard/sites/${SITE_SLUG}/raw-sensors?hours=${rawCurrentHours}&parameter=${parameterId}&before=${encodeURIComponent(before)}`)
    .then(r => r.text())
    .then(html => { const item = btn.closest('li'); item.insertAdjacentHTML('beforebegin', html); item.remove(); })
    .catch(() => { btn.disabled = false; btn.textContent = 'Show older readings'; });
}

// The server renders the timestamp table; on a phone, replace it at once,
// and switch view when the screen crosses the breakpoint (rotation).
if (rawPhone.matches) fetchRawData();
rawPhone.addEventListener('change', () => fetchRawData(1));

// Auto-refresh: the configured charts too, not only the default "Trends" ones.
setInterval(() => {
  catCharts.forEach(cat => loadCategoryData(cat));
  adminChartLoaders.forEach(load => load());
}, 30000);
</script>
@endpush
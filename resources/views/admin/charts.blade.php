@extends('layouts.dashboard')
@section('page-title', $category->name . ' — Charts')
@section('page-crumb', 'Admin › ' . $site->name . ' › ' . $category->name . ' › Charts')

@push('styles')
<style>
.form-card{margin-bottom:14px}
.form-field{margin-bottom:10px}
.chart-item{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:16px;margin-bottom:10px}
.chart-item-head{display:flex;align-items:center;gap:10px;margin-bottom:10px}
.chart-title{font-size:14px;font-weight:600;flex:1}
.chart-meta{font-size:11px;color:var(--muted)}
.param-pill{display:inline-flex;align-items:center;gap:5px;font-size:11px;padding:3px 9px;border-radius:20px;border:1px solid var(--border);background:var(--bg);margin:2px}
.param-dot{width:8px;height:8px;border-radius:50%}
.param-check-row{display:flex;align-items:center;gap:10px;padding:8px 10px;border:1px solid var(--border);border-radius:7px;background:var(--bg);margin-bottom:6px}
.param-check-row input[type=checkbox]{accent-color:var(--blue);width:15px;height:15px;flex-shrink:0}
.param-check-row label{font-size:13px;flex:1;cursor:pointer}
.color-input{width:36px;height:28px;padding:2px 4px;border:1px solid var(--border);border-radius:5px;cursor:pointer}
.axis-sel{font-size:11px;padding:3px 6px;border:1px solid var(--border);border-radius:5px;background:var(--bg);cursor:pointer}
.toggle-row{display:flex;align-items:center;gap:8px;margin-bottom:8px}
.toggle-row input[type=checkbox]{accent-color:var(--blue);width:15px;height:15px}
.toggle-row label{font-size:12px;cursor:pointer;color:var(--muted)}
</style>
@endpush

@section('content')

@if(session('success'))
<div class="alert-banner" style="background:var(--green-bg);border-color:var(--green-bd);color:var(--green);margin-bottom:16px">
  {{ session('success') }}
  <button class="ab-close" onclick="this.closest('.alert-banner').style.display='none'" aria-label="Dismiss">×</button>
</div>
@endif

{{-- Breadcrumb --}}
<div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;font-size:12px;color:var(--muted)">
  <a href="{{ route('admin.sites') }}" style="color:var(--blue);text-decoration:none">Sites</a> ›
  <a href="{{ route('admin.categories', $site) }}" style="color:var(--blue);text-decoration:none">{{ $site->name }}</a> ›
  <a href="{{ route('admin.parameters', [$site, $category]) }}" style="color:var(--blue);text-decoration:none">{{ $category->icon }} {{ $category->name }}</a> ›
  <span>Charts</span>
</div>

<div style="display:grid;grid-template-columns:1fr 360px;gap:16px">

  {{-- Charts list --}}
  <div>
    <div class="sec-header">
      <span class="sec-title">Charts ({{ $charts->count() }})</span>
    </div>

    @forelse($charts as $chart)
    <div class="chart-item" style="{{ !$chart->is_active ? 'opacity:.6' : '' }}">
      <div class="chart-item-head">
        <div>
          <div class="chart-title">{{ $chart->title }}</div>
          <div class="chart-meta">
            {{ ucfirst($chart->chart_type) }} ·
            {{ $chart->col_span === 'full' ? 'Full width' : ($chart->col_span === 'half' ? 'Half width' : 'Third width') }} ·
            {{ $chart->height }}px height ·
            {{ $chart->parameters->count() }} parameter(s)
          </div>
        </div>
        <div style="display:flex;gap:5px">
          <form method="POST" action="{{ route('admin.charts.toggle', [$site, $category, $chart]) }}">
            @csrf
            <button type="submit" class="btn" style="font-size:11px;padding:3px 9px;{{ $chart->is_active ? 'color:var(--green)' : 'color:var(--muted)' }}">
              {{ $chart->is_active ? 'On' : 'Off' }}
            </button>
          </form>
          <form method="POST" action="{{ route('admin.charts.destroy', [$site, $category, $chart]) }}"
                onsubmit="return confirm('Delete chart {{ $chart->title }}?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-red" style="font-size:11px;padding:3px 9px">Delete</button>
          </form>
        </div>
      </div>

      {{-- Edit chart settings --}}
      <details style="margin-bottom:8px">
        <summary style="font-size:12px;color:var(--blue);cursor:pointer;list-style:none;display:flex;align-items:center;gap:5px">
          <span>Edit chart settings</span>
        </summary>
        <form method="POST" action="{{ route('admin.charts.update', [$site, $category, $chart]) }}" style="margin-top:12px">
          @csrf @method('PUT')
          <div class="form-field">
            <label class="form-label" for="chart-title-{{ $chart->id }}">Chart Title *</label>
            <input type="text" id="chart-title-{{ $chart->id }}" name="title" class="form-input" value="{{ $chart->title }}" required>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div class="form-field">
              <label class="form-label" for="chart-type-{{ $chart->id }}">Chart Type</label>
              <select name="chart_type" id="chart-type-{{ $chart->id }}" class="form-input">
                <option value="line" {{ $chart->chart_type==='line' ? 'selected' : '' }}>Line</option>
                <option value="bar"  {{ $chart->chart_type==='bar'  ? 'selected' : '' }}>Bar</option>
                <option value="area" {{ $chart->chart_type==='area' ? 'selected' : '' }}>Area (filled line)</option>
              </select>
            </div>
            <div class="form-field">
              <label class="form-label" for="chart-width-{{ $chart->id }}">Width</label>
              <select name="col_span" id="chart-width-{{ $chart->id }}" class="form-input">
                <option value="full"  {{ $chart->col_span==='full'  ? 'selected' : '' }}>Full width</option>
                <option value="half"  {{ $chart->col_span==='half'  ? 'selected' : '' }}>Half width</option>
                <option value="third" {{ $chart->col_span==='third' ? 'selected' : '' }}>One third</option>
              </select>
            </div>
          </div>
          <div class="form-field">
            <label class="form-label" for="chart-height-{{ $chart->id }}">Height (px)</label>
            <select name="height" id="chart-height-{{ $chart->id }}" class="form-input">
              <option value="160" {{ (int)$chart->height===160 ? 'selected' : '' }}>160px (compact)</option>
              <option value="220" {{ (int)$chart->height===220 ? 'selected' : '' }}>220px (standard)</option>
              <option value="300" {{ (int)$chart->height===300 ? 'selected' : '' }}>300px (large)</option>
            </select>
          </div>
          <div class="toggle-row" style="margin-top:10px">
            <input type="checkbox" name="show_legend" value="1" id="legend-{{ $chart->id }}" {{ $chart->show_legend ? 'checked' : '' }}>
            <label for="legend-{{ $chart->id }}">Show legend</label>
          </div>
          <div class="toggle-row">
            <input type="checkbox" name="dual_axis" value="1" id="dualaxis-{{ $chart->id }}" {{ $chart->dual_axis ? 'checked' : '' }}>
            <label for="dualaxis-{{ $chart->id }}">Force dual Y-axis</label>
          </div>
          <button type="submit" class="btn btn-blue" style="margin-top:8px;font-size:12px;padding:5px 14px">
            Save chart settings
          </button>
        </form>
      </details>

      {{-- Parameters in chart --}}
      @if($chart->parameters->count() > 0)
      <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:10px">
        @foreach($chart->parameters as $p)
        <span class="param-pill">
          <span class="param-dot" style="background:{{ $p->pivot->color }}"></span>
          {{ $p->name }}
          @if($p->unit)({{ $p->unit }})@endif
          @if($p->pivot->axis === 'right')<span style="font-size:9px;color:var(--muted)">R</span>@endif
          @if($p->pivot->dashed)<span style="font-size:9px;color:var(--muted)">- -</span>@endif
        </span>
        @endforeach
      </div>
      @endif

      {{-- Edit parameters form --}}
      <details>
        <summary style="font-size:12px;color:var(--blue);cursor:pointer;list-style:none;display:flex;align-items:center;gap:5px">
          <span>Edit parameters on this chart</span>
        </summary>
        <form method="POST" action="{{ route('admin.charts.updateParams', [$site, $category, $chart]) }}" style="margin-top:12px">
          @csrf
          @foreach($allParams as $param)
          @php
            $pivot = $chart->parameters->find($param->id)?->pivot;
            $checked = $pivot !== null;
          @endphp
          <div class="param-check-row">
            <input type="checkbox" name="params[{{ $param->id }}][selected]" value="1" id="p{{ $chart->id }}-{{ $param->id }}" {{ $checked ? 'checked' : '' }}>
            <label for="p{{ $chart->id }}-{{ $param->id }}">
              {{ $param->name }} @if($param->unit)<span style="color:var(--muted);font-size:11px">({{ $param->unit }})</span>@endif
            </label>
            <input type="color" name="params[{{ $param->id }}][color]" value="{{ $pivot?->color ?? '#1d6ed8' }}" aria-label="Line color for {{ $param->name }}" class="color-input">
            <select name="params[{{ $param->id }}][axis]" aria-label="Y-axis for {{ $param->name }}" class="axis-sel">
              <option value="left"  {{ ($pivot?->axis ?? 'left') === 'left'  ? 'selected' : '' }}>Left</option>
              <option value="right" {{ ($pivot?->axis ?? 'left') === 'right' ? 'selected' : '' }}>Right</option>
            </select>
            <label style="font-size:11px;display:flex;align-items:center;gap:4px;color:var(--muted);white-space:nowrap">
              <input type="checkbox" name="params[{{ $param->id }}][dashed]" value="1" {{ $pivot?->dashed ? 'checked' : '' }}>
              Dashed
            </label>
            <label style="font-size:11px;display:flex;align-items:center;gap:4px;color:var(--muted);white-space:nowrap">
              <input type="checkbox" name="params[{{ $param->id }}][fill]" value="1" {{ $pivot?->fill ? 'checked' : '' }}>
              Fill
            </label>
          </div>
          @endforeach
          <button type="submit" class="btn btn-blue" style="margin-top:8px;font-size:12px;padding:5px 14px">
            Save parameters
          </button>
        </form>
      </details>
    </div>
    @empty
    <div style="text-align:center;padding:40px;color:var(--muted);background:var(--surface);border:1px solid var(--border);border-radius:var(--r)">
      <div style="font-weight:600;margin-bottom:4px">No charts yet</div>
      <div style="font-size:12px">Create a chart using the form →</div>
    </div>
    @endforelse
  </div>

  {{-- Create chart form --}}
  <div>
    <div class="form-card">
      <div style="font-size:13px;font-weight:600;margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--border)">
        + New Chart
      </div>
      <form method="POST" action="{{ route('admin.charts.store', [$site, $category]) }}">
        @csrf
        <div class="form-field">
          <label class="form-label" for="new-chart-title">Chart Title *</label>
          <input type="text" id="new-chart-title" name="title" class="form-input" placeholder="e.g. Power Output" required>
        </div>
        <div class="form-field">
          <label class="form-label" for="new-chart-type">Chart Type</label>
          <select name="chart_type" id="new-chart-type" class="form-input">
            <option value="line">Line</option>
            <option value="bar">Bar</option>
            <option value="area">Area (filled line)</option>
          </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <div class="form-field">
            <label class="form-label" for="new-chart-width">Width</label>
            <select name="col_span" id="new-chart-width" class="form-input">
              <option value="full">Full width</option>
              <option value="half">Half width</option>
              <option value="third">One third</option>
            </select>
          </div>
          <div class="form-field">
            <label class="form-label" for="new-chart-height">Height (px)</label>
            <select name="height" id="new-chart-height" class="form-input">
              <option value="160">160px (compact)</option>
              <option value="220" selected>220px (standard)</option>
              <option value="300">300px (large)</option>
            </select>
          </div>
        </div>

        <div style="font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;margin-top:4px">
          Parameters to display
        </div>
        @foreach($allParams as $param)
        <div class="param-check-row">
          <input type="checkbox" name="params[{{ $param->id }}][selected]" value="1" id="new-{{ $param->id }}">
          <label for="new-{{ $param->id }}" style="flex:1">
            {{ $param->name }}
            @if($param->unit)<span style="color:var(--muted);font-size:11px">({{ $param->unit }})</span>@endif
          </label>
          <input type="color" name="params[{{ $param->id }}][color]" value="{{ ['#15803d','#1d6ed8','#7c3aed','#b45309','#0891b2','#be123c'][$loop->index % 6] }}" aria-label="Line color for {{ $param->name }}" class="color-input">
          <select name="params[{{ $param->id }}][axis]" aria-label="Y-axis for {{ $param->name }}" class="axis-sel">
            <option value="left">Left</option>
            <option value="right">Right</option>
          </select>
          <label style="font-size:11px;display:flex;align-items:center;gap:4px;color:var(--muted)">
            <input type="checkbox" name="params[{{ $param->id }}][dashed]" value="1"> Dashed
          </label>
          <label style="font-size:11px;display:flex;align-items:center;gap:4px;color:var(--muted)">
            <input type="checkbox" name="params[{{ $param->id }}][fill]" value="1"> Fill
          </label>
        </div>
        @endforeach

        <div class="toggle-row" style="margin-top:10px">
          <input type="checkbox" name="show_legend" value="1" id="show_legend" checked>
          <label for="show_legend">Show legend</label>
        </div>
        <div class="toggle-row">
          <input type="checkbox" name="dual_axis" value="1" id="dual_axis">
          <label for="dual_axis">Force dual Y-axis</label>
        </div>

        <button type="submit" class="btn btn-blue" style="width:100%;justify-content:center;margin-top:8px">
          + Create Chart
        </button>
      </form>
    </div>
  </div>

</div>
@endsection
@extends('layouts.dashboard')
@section('page-title', $category->name . ' Parameters')
@section('page-crumb', 'Admin › ' . $site->name . ' › ' . $category->name)

@push('styles')
<style>
.form-field{margin-bottom:10px}
.form-grid2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.section-title{font-size:13px;font-weight:600;margin-bottom:4px;padding-bottom:0;border-bottom:none}
.section-hint{font-size:11px;color:var(--muted);margin-bottom:14px}

.param-row{background:var(--surface);border:1px solid var(--border);border-radius:9px;padding:14px 16px;margin-bottom:8px;transition:box-shadow .15s}
.param-row:hover{box-shadow:var(--shadow-md)}
.param-row.inactive{opacity:.55}
.param-main{display:flex;align-items:center;gap:12px}
.param-name{font-size:13px;font-weight:600;flex:1}
.param-meta{font-size:11px;color:var(--muted);margin-top:2px}
.param-unit{font-family:'DM Mono',monospace;font-size:12px;background:var(--bg);border:1px solid var(--border);padding:2px 8px;border-radius:5px;color:var(--text)}
.param-type{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;padding:2px 7px;border-radius:4px;background:var(--blue-bg);color:var(--blue);border:1px solid var(--blue-bd)}
.param-actions{display:flex;gap:5px;flex-shrink:0;flex-wrap:wrap}
.edit-form{display:none;margin-top:12px;padding-top:12px;border-top:1px solid var(--border)}

/* Group cards */
.group-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:10px;margin-bottom:16px}
.group-card{border:1px solid var(--border);border-radius:9px;padding:12px;background:var(--surface);transition:box-shadow .15s,opacity .15s;cursor:grab}
.group-card:hover{box-shadow:var(--shadow-md)}
.group-card.dragging{opacity:.4}
.group-card.drag-over{border-color:var(--blue);box-shadow:0 0 0 2px var(--blue-bg)}
.group-card-top{display:flex;align-items:flex-start;gap:6px}
.group-card-head{display:flex;align-items:center;gap:8px;flex:1;min-width:0}
.group-swatch{width:14px;height:14px;border-radius:4px;border:none;padding:0;cursor:pointer;flex-shrink:0}
.group-name-input{flex:1;min-width:0;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:600;border:none;background:transparent;color:var(--text);padding:2px 0;cursor:text}
.group-name-input:focus{outline:none;border-bottom:1px solid var(--blue)}
.group-count{font-size:11px;color:var(--muted);margin-top:6px}

/* Icon-only buttons: consistent, minimal, no emoji */
.icon-btn{display:inline-flex;align-items:center;justify-content:center;border:none;background:none;cursor:pointer;padding:5px;border-radius:6px;color:var(--muted);line-height:0;flex-shrink:0}
.icon-btn:hover{background:var(--bg);color:var(--text)}
.icon-btn.danger:hover{background:var(--red-bg);color:var(--red)}
.group-reorder-btns{display:flex;gap:2px}
.group-reorder-btns button:disabled{opacity:.3;cursor:default}
.group-reorder-btns button:disabled:hover{background:none;color:var(--muted)}
</style>
@endpush

@php
  $iconEdit        = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>';
  $iconCheck       = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
  $iconCircle      = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><circle cx="12" cy="12" r="9"></circle></svg>';
  $iconTrash       = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>';
  $iconChevronLeft  = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>';
  $iconChevronRight = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>';

  // Keep the groups panel open across a redirect if the action that just ran
  // came from it (its forms are the only ones that submit a "color" field).
  $groupsPanelOpen = (session('success') && stripos(session('success'), 'group') !== false) || old('color') !== null;
@endphp

@section('content')

@if(session('success'))
<div class="alert-banner" style="background:var(--green-bg);border-color:var(--green-bd);color:var(--green);margin-bottom:16px">
  {{ session('success') }}
  <button class="ab-close" onclick="this.closest('.alert-banner').style.display='none'" aria-label="Dismiss">×</button>
</div>
@endif

@if($errors->any())
<div class="alert-banner" style="background:var(--red-bg);border-color:var(--red-bd);color:var(--red);margin-bottom:16px">
  @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
  <button class="ab-close" onclick="this.closest('.alert-banner').style.display='none'" aria-label="Dismiss">×</button>
</div>
@endif

{{-- Breadcrumb --}}
<div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;font-size:12px;color:var(--muted)">
  <a href="{{ route('admin.sites') }}" style="color:var(--blue);text-decoration:none">Sites</a>
  <span>›</span>
  <a href="{{ route('admin.categories', $site) }}" style="color:var(--blue);text-decoration:none">{{ $site->name }}</a>
  <span>›</span>
  <span>{{ $category->name }}</span>
  <span>›</span>
  <span>Parameters</span>
  <button type="button" class="btn btn-blue" style="margin-left:auto;font-size:11px;padding:4px 12px"
          onclick="toggleEditForm('groups-panel')">
    Manage Groups ({{ $groups->count() }})
  </button>
  <a href="{{ route('admin.charts', [$site, $category]) }}" class="btn btn-blue" style="font-size:11px;padding:4px 12px">
    Manage Charts
  </a>
</div>

{{-- Groups management --}}
<div class="form-card" id="groups-panel" style="display:{{ $groupsPanelOpen ? 'block' : 'none' }}">
  <div class="section-title">Groups</div>
  <div class="section-hint">Parameters sharing a group are boxed together on the dashboard.</div>

  @if($groups->count() > 0)
  <div class="group-grid" id="group-grid">
    @foreach($groups as $group)
    <div class="group-card" draggable="true" data-group-id="{{ $group->id }}">
      <div class="group-card-top">
        <form method="POST" action="{{ route('admin.groups.update', [$site, $category, $group]) }}" class="group-card-head">
          @csrf @method('PUT')
          <input type="color" name="color" value="{{ $group->color ?: '#94a3b8' }}" onchange="this.form.requestSubmit()"
                 title="Change color" aria-label="Change color for group {{ $group->name }}" class="group-swatch">
          <input type="text" name="name" value="{{ $group->name }}" title="Rename"
                 aria-label="Rename group {{ $group->name }}"
                 class="group-name-input" onchange="this.form.requestSubmit()">
        </form>
        <form method="POST" action="{{ route('admin.groups.destroy', [$site, $category, $group]) }}"
              onsubmit="return confirm('Delete group &quot;{{ $group->name }}&quot;? Its {{ $group->parameters_count }} {{ Str::plural('parameter', $group->parameters_count) }} will become ungrouped.')">
          @csrf @method('DELETE')
          <button type="submit" class="icon-btn danger" title="Delete group">{!! $iconTrash !!}</button>
        </form>
      </div>
      <div style="display:flex;align-items:center;justify-content:space-between;margin-top:6px">
        <div class="group-count">{{ $group->parameters_count }} {{ Str::plural('parameter', $group->parameters_count) }}</div>
        <div class="group-reorder-btns">
          <button type="button" class="icon-btn" data-move="left" aria-label="Move {{ $group->name }} earlier" title="Move earlier">{!! $iconChevronLeft !!}</button>
          <button type="button" class="icon-btn" data-move="right" aria-label="Move {{ $group->name }} later" title="Move later">{!! $iconChevronRight !!}</button>
        </div>
      </div>
    </div>
    @endforeach
  </div>
  <div class="section-hint" style="margin:-8px 0 14px">Drag a card, or use the ‹ › buttons, to reorder groups.</div>
  @else
  <div style="font-size:12px;color:var(--muted);margin-bottom:16px">No groups yet — create one below, or set it directly when adding or editing a parameter.</div>
  @endif

  <form method="POST" action="{{ route('admin.groups.store', [$site, $category]) }}" style="display:flex;gap:8px;align-items:center">
    @csrf
    <input type="color" name="color" value="{{ \App\Models\SiteParameterGroup::nextPaletteColor($groups->count()) }}" aria-label="New group color" style="width:34px;height:34px;border:1px solid var(--border);border-radius:7px;padding:2px;cursor:pointer;flex-shrink:0">
    <input type="text" name="name" class="form-input" placeholder="New group name..." aria-label="New group name" style="max-width:220px" required>
    <button type="submit" class="btn btn-blue" style="font-size:12px;padding:6px 14px;white-space:nowrap">Add Group</button>
  </form>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:16px">

  {{-- Parameters list --}}
  <div>
    <div class="sec-header">
      <span class="sec-title">Parameters ({{ $parameters->count() }})</span>
      <span style="font-size:11px;color:var(--muted)">
        <span style="color:var(--green)">●</span> Active &nbsp;
        <span style="color:var(--muted)">●</span> Inactive
      </span>
    </div>

    @forelse($parameters as $param)
    <div class="param-row {{ !$param->is_active ? 'inactive' : '' }}">
      <div class="param-main">
        <div style="width:8px;height:8px;border-radius:50%;background:{{ $param->is_active ? 'var(--green)' : '#cbd5e1' }};flex-shrink:0"></div>
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <span class="param-name">{{ $param->name }}</span>
            @if($param->unit)<span class="param-unit">{{ $param->unit }}</span>@endif
            <span class="param-type">{{ $param->data_type }}</span>
            @php $isManual = ($param->input_type ?? 'sensor') === 'manual'; @endphp
            <span style="font-size:10px;font-weight:600;padding:2px 7px;border-radius:4px;
              {{ $isManual ? 'background:var(--blue-bg);color:var(--blue);border:1px solid var(--blue-bd)' : 'background:var(--green-bg);color:var(--green);border:1px solid var(--green-bd)' }}">
              {{ $isManual ? 'Manual' : 'Sensor' }}
            </span>
            @if($param->show_on_dashboard)
              <span style="font-size:10px;background:var(--amber-bg);color:var(--amber);border:1px solid var(--amber-bd);padding:2px 7px;border-radius:4px">Dashboard</span>
            @endif
            @if($param->data_type === 'switch')
              <span style="font-size:10px;background:var(--blue-bg);color:var(--blue);border:1px solid var(--blue-bd);padding:2px 7px;border-radius:4px">
                {{ $param->isControllable() ? 'Controllable' : 'Switch (readonly)' }}
              </span>
            @endif
          </div>
          <div class="param-meta">
            slug: {{ $param->slug }}
            @if($param->group)
              · <span style="display:inline-flex;align-items:center;gap:3px">
                  <span style="width:7px;height:7px;border-radius:50%;background:{{ $param->group->color ?: '#94a3b8' }};display:inline-block"></span>
                  {{ $param->group->name }}
                </span>
            @endif
            @if($param->min_value !== null || $param->max_value !== null)
              · range: {{ $param->min_value ?? '−∞' }}–{{ $param->max_value ?? '∞' }} {{ $param->unit }}
            @endif
            @if($param->warning_threshold) · warning: {{ $param->warning_threshold }} {{ $param->unit }} @endif
            @if($param->critical_threshold) · critical: {{ $param->critical_threshold }} {{ $param->unit }} @endif
          </div>
        </div>
        <div class="param-actions">
          <button type="button" class="btn" style="font-size:11px;padding:3px 9px"
            onclick="toggleEditForm('pedit-{{ $param->id }}')">
            {!! $iconEdit !!} Edit
          </button>
          <form method="POST" action="{{ route('admin.parameters.toggle', [$site, $category, $param]) }}">
            @csrf
            <button type="submit" class="btn" style="font-size:11px;padding:3px 9px;{{ $param->is_active ? 'color:var(--green)' : 'color:var(--muted)' }}">
              {!! $param->is_active ? $iconCheck : $iconCircle !!} {{ $param->is_active ? 'Active' : 'Inactive' }}
            </button>
          </form>
          <form method="POST" action="{{ route('admin.parameters.destroy', [$site, $category, $param]) }}"
                onsubmit="return confirm('Delete parameter {{ $param->name }}?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-red" style="font-size:11px;padding:3px 9px">{!! $iconTrash !!} Delete</button>
          </form>
        </div>
      </div>

      {{-- Edit form --}}
      <div class="edit-form" id="pedit-{{ $param->id }}">
        <form method="POST" action="{{ route('admin.parameters.update', [$site, $category, $param]) }}">
          @csrf @method('PUT')
          <div class="form-grid2">
            <div class="form-field">
              <label class="form-label" for="pname-{{ $param->id }}">Name *</label>
              <input type="text" id="pname-{{ $param->id }}" name="name" class="form-input" value="{{ $param->name }}" required>
            </div>
            <div class="form-field">
              <label class="form-label" for="punit-{{ $param->id }}">Unit</label>
              <input type="text" id="punit-{{ $param->id }}" name="unit" class="form-input" value="{{ $param->unit }}" placeholder="kW, %, °C…">
            </div>
          </div>
          <div class="form-grid2">
            <div class="form-field">
              <label class="form-label" for="pdatatype-{{ $param->id }}">Data Type</label>
              <select name="data_type" id="pdatatype-{{ $param->id }}" class="form-input" onchange="onDataTypeChange(this, 'ctrl-{{ $param->id }}', 'input-type-{{ $param->id }}')">
                <option value="float"   {{ $param->data_type==='float'   ? 'selected' : '' }}>Float</option>
                <option value="integer" {{ $param->data_type==='integer' ? 'selected' : '' }}>Integer</option>
                <option value="boolean" {{ $param->data_type==='boolean' ? 'selected' : '' }}>Boolean</option>
                <option value="string"  {{ $param->data_type==='string'  ? 'selected' : '' }}>String</option>
                <option value="switch"  {{ $param->data_type==='switch'  ? 'selected' : '' }}>Switch (ON/OFF)</option>
              </select>
            </div>
            <div class="form-field">
              <label class="form-label" for="input-type-{{ $param->id }}">Input Type</label>
              <select name="input_type" id="input-type-{{ $param->id }}" class="form-input" {{ $param->data_type==='switch' ? 'disabled' : '' }}>
                <option value="sensor" {{ ($param->input_type ?? 'sensor')==='sensor' ? 'selected' : '' }}>Sensor</option>
                <option value="manual" {{ ($param->input_type ?? 'sensor')==='manual' ? 'selected' : '' }}>Manual</option>
              </select>
              <input type="hidden" name="input_type" value="sensor" id="input-type-{{ $param->id }}-hidden" {{ $param->data_type==='switch' ? '' : 'disabled' }}>
              <span id="input-type-{{ $param->id }}-note" style="font-size:11px;color:var(--muted);margin-top:2px;display:{{ $param->data_type==='switch' ? 'block' : 'none' }}">Switches are always API-driven (sensor).</span>
            </div>
          </div>
          <div class="form-field" id="ctrl-{{ $param->id }}" style="display:{{ $param->data_type==='switch' ? 'block' : 'none' }}">
            <label class="form-label" for="pcontrol-{{ $param->id }}">Control Type</label>
            <select name="control_type" id="pcontrol-{{ $param->id }}" class="form-input">
              <option value="readonly"     {{ ($param->control_type ?? 'readonly')==='readonly'     ? 'selected' : '' }}>Readonly (status display only)</option>
              <option value="controllable" {{ ($param->control_type ?? 'readonly')==='controllable' ? 'selected' : '' }}>Controllable (toggle ON/OFF from dashboard)</option>
            </select>
            <span style="font-size:11px;color:var(--muted);margin-top:2px;display:block">
              Controllable switches show an ON/OFF toggle on the dashboard and let agents send remote commands (e.g. valves, pumps, fans).
            </span>
          </div>
          <div class="form-grid2">
            <div class="form-field">
              <label class="form-label" for="pgroup-{{ $param->id }}">Group</label>
              <select name="site_parameter_group_id" id="pgroup-{{ $param->id }}" class="form-input">
                <option value="">— No group —</option>
                @foreach($groups as $g)
                  <option value="{{ $g->id }}" {{ $param->site_parameter_group_id == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="form-grid2">
            <div class="form-field">
              <label class="form-label" for="pmin-{{ $param->id }}">Min Value</label>
              <input type="number" id="pmin-{{ $param->id }}" name="min_value" step="any" class="form-input" value="{{ $param->min_value }}" placeholder="Optional">
            </div>
            <div class="form-field">
              <label class="form-label" for="pmax-{{ $param->id }}">Max Value</label>
              <input type="number" id="pmax-{{ $param->id }}" name="max_value" step="any" class="form-input" value="{{ $param->max_value }}" placeholder="Optional">
            </div>
          </div>
          <div class="form-grid2">
            <div class="form-field">
              <label class="form-label" for="pwarn-{{ $param->id }}">Warning Threshold</label>
              <input type="number" id="pwarn-{{ $param->id }}" name="warning_threshold" step="any" class="form-input" value="{{ $param->warning_threshold }}" placeholder="Optional">
            </div>
            <div class="form-field">
              <label class="form-label" for="pcrit-{{ $param->id }}">Critical Threshold</label>
              <input type="number" id="pcrit-{{ $param->id }}" name="critical_threshold" step="any" class="form-input" value="{{ $param->critical_threshold }}" placeholder="Optional">
            </div>
          </div>
          <div class="form-field">
            <label class="form-label" for="pdirection-{{ $param->id }}">Alert Direction</label>
            <select name="threshold_direction" id="pdirection-{{ $param->id }}" class="form-input">
              <option value="below" {{ $param->threshold_direction !== 'above' ? 'selected' : '' }}>Below threshold is bad (tank level, borehole level...)</option>
              <option value="above" {{ $param->threshold_direction === 'above' ? 'selected' : '' }}>Above threshold is bad (temperature, pressure...)</option>
            </select>
          </div>
          <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:var(--bg);border:1px solid var(--border);border-radius:7px;margin-bottom:10px">
            <input type="checkbox" name="show_on_dashboard" id="dash-{{ $param->id }}" value="1" {{ $param->show_on_dashboard ? 'checked' : '' }} style="accent-color:var(--blue);width:15px;height:15px">
            <label for="dash-{{ $param->id }}" style="font-size:12px;cursor:pointer">Show on dashboard</label>
          </div>
          <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-blue" style="font-size:12px;padding:5px 14px">Save</button>
            <button type="button" class="btn" style="font-size:12px;padding:5px 14px" onclick="toggleEditForm('pedit-{{ $param->id }}')">Cancel</button>
          </div>
        </form>
      </div>
    </div>
    @empty
    <div style="text-align:center;padding:40px;color:var(--muted);background:var(--surface);border:1px solid var(--border);border-radius:var(--r)">
      <div style="font-weight:600;margin-bottom:4px">No parameters yet</div>
      <div style="font-size:12px">Add parameters using the form on the right.</div>
    </div>
    @endforelse
  </div>

  {{-- Add parameter form --}}
  <div>
    <div class="form-card">
      <div class="section-title" style="padding-bottom:8px;border-bottom:1px solid var(--border);margin-bottom:14px">
        Add Parameter
      </div>
      <form method="POST" action="{{ route('admin.parameters.store', [$site, $category]) }}">
        @csrf
        <div class="form-field">
          <label class="form-label" for="new-param-name">Parameter Name *</label>
          <input type="text" id="new-param-name" name="name" class="form-input" placeholder="e.g. Solar output" required>
        </div>
        <div class="form-grid2">
          <div class="form-field">
            <label class="form-label" for="new-param-unit">Unit</label>
            <input type="text" id="new-param-unit" name="unit" class="form-input" placeholder="kW, %, °C…">
          </div>
          <div class="form-field">
            <label class="form-label" for="new-param-datatype">Data Type *</label>
            <select name="data_type" id="new-param-datatype" class="form-input" required onchange="onDataTypeChange(this, 'ctrl-new', 'input-type-new')">
              <option value="float">Float (decimal)</option>
              <option value="integer">Integer</option>
              <option value="boolean">Boolean (on/off)</option>
              <option value="string">String (text)</option>
              <option value="switch">Switch (ON/OFF)</option>
            </select>
          </div>
        </div>
        <div class="form-field">
          <label class="form-label" for="input-type-new">Input Type *</label>
          <select name="input_type" id="input-type-new" class="form-input" required>
            <option value="sensor">Sensor — automatic via API / ESP32</option>
            <option value="manual">Manual — entered by agent</option>
          </select>
          <input type="hidden" name="input_type" value="sensor" id="input-type-new-hidden" disabled>
          <span id="input-type-new-note" style="font-size:11px;color:var(--muted);margin-top:2px;display:none">Switches are always API-driven (sensor).</span>
        </div>
        <div class="form-field" id="ctrl-new" style="display:none">
          <label class="form-label" for="new-param-control">Control Type</label>
          <select name="control_type" id="new-param-control" class="form-input">
            <option value="readonly">Readonly (status display only)</option>
            <option value="controllable">Controllable (toggle ON/OFF from dashboard)</option>
          </select>
          <span style="font-size:11px;color:var(--muted);margin-top:2px;display:block">
            Controllable switches show an ON/OFF toggle on the dashboard and let agents send remote commands (e.g. valves, pumps, fans).
          </span>
        </div>
        <div class="form-field">
          <label class="form-label" for="new-param-group">Group</label>
          <select name="site_parameter_group_id" id="new-param-group" class="form-input">
            <option value="">— No group —</option>
            @foreach($groups as $g)
              <option value="{{ $g->id }}">{{ $g->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-grid2">
          <div class="form-field">
            <label class="form-label" for="new-param-min">Min Value</label>
            <input type="number" id="new-param-min" name="min_value" step="any" class="form-input" placeholder="Optional">
          </div>
          <div class="form-field">
            <label class="form-label" for="new-param-max">Max Value</label>
            <input type="number" id="new-param-max" name="max_value" step="any" class="form-input" placeholder="Optional">
          </div>
        </div>
        <div class="form-grid2">
          <div class="form-field">
            <label class="form-label" for="new-param-warning">Warning</label>
            <input type="number" id="new-param-warning" name="warning_threshold" step="any" class="form-input" placeholder="Optional">
          </div>
          <div class="form-field">
            <label class="form-label" for="new-param-critical">Critical</label>
            <input type="number" id="new-param-critical" name="critical_threshold" step="any" class="form-input" placeholder="Optional">
          </div>
        </div>
        <div class="form-field">
          <label class="form-label" for="new-param-direction">Alert Direction</label>
          <select name="threshold_direction" id="new-param-direction" class="form-input">
            <option value="below">Below threshold is bad (tank level, borehole level...)</option>
            <option value="above">Above threshold is bad (temperature, pressure...)</option>
          </select>
        </div>
        <div style="display:flex;align-items:center;gap:8px;padding:10px 12px;background:var(--bg);border:1px solid var(--border);border-radius:7px;margin-bottom:12px">
          <input type="checkbox" name="show_on_dashboard" id="show_dash" value="1" checked style="accent-color:var(--blue);width:15px;height:15px">
          <label for="show_dash" style="font-size:12px;cursor:pointer">Show on dashboard</label>
        </div>
        <button type="submit" class="btn btn-blue" style="width:100%;justify-content:center">
          Add Parameter
        </button>
      </form>
    </div>

    {{-- Quick add defaults --}}
    @php $defaultParams = \App\Models\SiteParameter::defaultsFor($category->slug); @endphp
    @if(count($defaultParams) > 0)
    <div class="form-card" style="background:var(--bg)">
      <div style="font-size:12px;font-weight:600;margin-bottom:10px;color:var(--muted)">QUICK ADD DEFAULTS</div>
      @foreach($defaultParams as $def)
        @php $exists = $parameters->where('slug', $def['slug'])->count() > 0; @endphp
        @if(!$exists)
        <form method="POST" action="{{ route('admin.parameters.store', [$site, $category]) }}" style="margin-bottom:6px">
          @csrf
          <input type="hidden" name="name"              value="{{ $def['name'] }}">
          <input type="hidden" name="unit"              value="{{ $def['unit'] ?? '' }}">
          <input type="hidden" name="data_type"         value="{{ $def['data_type'] }}">
          <input type="hidden" name="input_type"        value="{{ $def['input_type'] ?? 'sensor' }}">
          <input type="hidden" name="group_name"        value="{{ $def['group_name'] ?? '' }}">
          <input type="hidden" name="show_on_dashboard" value="{{ ($def['show_on_dashboard'] ?? true) ? '1' : '0' }}">
          @if(isset($def['warning_threshold']))
          <input type="hidden" name="warning_threshold" value="{{ $def['warning_threshold'] }}">
          @endif
          @if(isset($def['threshold_direction']))
          <input type="hidden" name="threshold_direction" value="{{ $def['threshold_direction'] }}">
          @endif
          <button type="submit" class="btn" style="width:100%;justify-content:flex-start;font-size:12px;gap:8px">
            <span>{{ $def['name'] }}</span>
            <span style="margin-left:auto;color:var(--muted)">{{ $def['unit'] ?? $def['data_type'] }}</span>
          </button>
        </form>
        @else
        <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 12px;border-radius:7px;font-size:12px;color:var(--muted);background:var(--surface);border:1px solid var(--border);margin-bottom:6px">
          <span>{{ $def['name'] }}</span>
          <span>Added</span>
        </div>
        @endif
      @endforeach
    </div>
    @endif
  </div>

</div>
@endsection

@push('scripts')
<script>
// Drag-and-drop reordering for group cards.
(function() {
  const grid = document.getElementById('group-grid');
  if (!grid) return;

  let dragged = null;

  grid.addEventListener('dragstart', function(e) {
    const card = e.target.closest('.group-card');
    if (!card) return;
    // Let normal interaction (typing, clicking, color picking) through —
    // only start a card drag when the gesture began on the card itself.
    if (e.target.closest('input, button, select, textarea')) {
      e.preventDefault();
      return;
    }
    dragged = card;
    card.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
  });

  grid.addEventListener('dragend', function() {
    if (dragged) dragged.classList.remove('dragging');
    dragged = null;
    grid.querySelectorAll('.group-card.drag-over').forEach(el => el.classList.remove('drag-over'));
  });

  grid.addEventListener('dragover', function(e) {
    if (!dragged) return;
    e.preventDefault();
    const card = e.target.closest('.group-card');
    if (!card || card === dragged) return;
    e.dataTransfer.dropEffect = 'move';
    grid.querySelectorAll('.group-card.drag-over').forEach(el => { if (el !== card) el.classList.remove('drag-over'); });
    card.classList.add('drag-over');
    const rect = card.getBoundingClientRect();
    card.dataset.insertBefore = (e.clientX - rect.left) < rect.width / 2 ? '1' : '0';
  });

  grid.addEventListener('drop', function(e) {
    if (!dragged) return;
    e.preventDefault();
    const card = e.target.closest('.group-card');
    if (!card || card === dragged) return;
    card.classList.remove('drag-over');
    if (card.dataset.insertBefore === '1') {
      grid.insertBefore(dragged, card);
    } else {
      grid.insertBefore(dragged, card.nextSibling);
    }
    saveGroupOrder();
  });

  function saveGroupOrder() {
    const order = [...grid.querySelectorAll('.group-card')].map(el => el.dataset.groupId);
    fetch('{{ route('admin.groups.reorder', [$site, $category]) }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({ order }),
    });
  }

  // Keyboard/mouse-friendly alternative to the drag-and-drop above: moves a
  // card one slot earlier/later in the grid and persists the same way.
  function updateMoveButtonsState() {
    const cards = [...grid.querySelectorAll('.group-card')];
    cards.forEach((card, i) => {
      const left  = card.querySelector('[data-move="left"]');
      const right = card.querySelector('[data-move="right"]');
      if (left)  left.disabled  = i === 0;
      if (right) right.disabled = i === cards.length - 1;
    });
  }

  grid.addEventListener('click', function(e) {
    const btn = e.target.closest('[data-move]');
    if (!btn) return;
    const card = btn.closest('.group-card');
    if (!card) return;
    if (btn.dataset.move === 'left' && card.previousElementSibling) {
      grid.insertBefore(card, card.previousElementSibling);
    } else if (btn.dataset.move === 'right' && card.nextElementSibling) {
      grid.insertBefore(card.nextElementSibling, card);
    } else {
      return;
    }
    updateMoveButtonsState();
    saveGroupOrder();
    btn.focus();
  });

  updateMoveButtonsState();
})();

function toggleEditForm(id) {
  const el = document.getElementById(id);
  if (el) el.style.display = el.style.display === 'block' ? 'none' : 'block';
}

function toggleControlType(select, ctrlId) {
  const el = document.getElementById(ctrlId);
  if (!el) return;
  el.style.display = select.value === 'switch' ? 'block' : 'none';
}

function onDataTypeChange(select, ctrlId, inputTypeBaseId) {
  const isSwitch = select.value === 'switch';

  // Show/hide Control Type field
  toggleControlType(select, ctrlId);

  // Input Type: force "sensor" (disabled) when Switch is selected
  const inputSelect = document.getElementById(inputTypeBaseId);
  const inputHidden = document.getElementById(inputTypeBaseId + '-hidden');
  const note        = document.getElementById(inputTypeBaseId + '-note');

  if (inputSelect) inputSelect.disabled = isSwitch;
  if (inputHidden) inputHidden.disabled = !isSwitch;
  if (note) note.style.display = isSwitch ? 'block' : 'none';
}
</script>
@endpush

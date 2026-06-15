@extends('layouts.dashboard')
@section('page-title', $category->name . ' Parameters')
@section('page-crumb', 'Admin › ' . $site->name . ' › ' . $category->name)

@push('styles')
<style>
.form-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);padding:20px;margin-bottom:16px}
.form-field{display:flex;flex-direction:column;gap:4px;margin-bottom:10px}
.form-label{font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px}
.form-input{font-family:'DM Sans',sans-serif;font-size:13px;padding:8px 12px;border:1px solid var(--border);border-radius:7px;background:var(--bg);color:var(--text);width:100%;transition:border-color .15s}
.form-input:focus{outline:none;border-color:var(--blue);background:#fff}
.form-grid2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
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
</style>
@endpush

@section('content')

@if(session('success'))
<div class="alert-banner" style="background:var(--green-bg);border-color:var(--green-bd);color:var(--green);margin-bottom:16px">
  ✓ {{ session('success') }}
  <button class="ab-close" onclick="this.closest('.alert-banner').style.display='none'">×</button>
</div>
@endif

{{-- Breadcrumb --}}
<div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;font-size:12px;color:var(--muted)">
  <a href="{{ route('admin.sites') }}" style="color:var(--blue);text-decoration:none">Sites</a>
  <span>›</span>
  <a href="{{ route('admin.categories', $site) }}" style="color:var(--blue);text-decoration:none">{{ $site->name }}</a>
  <span>›</span>
  <span>{{ $category->icon }} {{ $category->name }}</span>
  <span>›</span>
  <span>Parameters</span>
  <a href="{{ route('admin.charts', [$site, $category]) }}" class="btn btn-blue" style="margin-left:auto;font-size:11px;padding:4px 12px">
    📊 Manage Charts
  </a>
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
              {{ $isManual ? '✏ Manual' : '📡 Sensor' }}
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
            @if($param->group_name) · group: {{ $param->group_name }} @endif
            @if($param->warning_threshold) · ⚠ warn: {{ $param->warning_threshold }} {{ $param->unit }} @endif
          </div>
        </div>
        <div class="param-actions">
          <button type="button" class="btn" style="font-size:11px;padding:3px 9px;background:var(--blue-bg);color:var(--blue);border-color:var(--blue-bd)"
            onclick="toggleEditForm('pedit-{{ $param->id }}')">
            ✏ Edit
          </button>
          <form method="POST" action="{{ route('admin.parameters.toggle', [$site, $category, $param]) }}">
            @csrf
            <button type="submit" class="btn" style="font-size:11px;padding:3px 9px;{{ $param->is_active ? 'color:var(--green)' : 'color:var(--muted)' }}">
              {{ $param->is_active ? '✓ On' : '○ Off' }}
            </button>
          </form>
          <form method="POST" action="{{ route('admin.parameters.destroy', [$site, $category, $param]) }}"
                onsubmit="return confirm('Delete parameter {{ $param->name }}?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-red" style="font-size:11px;padding:3px 9px">✕</button>
          </form>
        </div>
      </div>

      {{-- Edit form --}}
      <div class="edit-form" id="pedit-{{ $param->id }}">
        <form method="POST" action="{{ route('admin.parameters.update', [$site, $category, $param]) }}">
          @csrf @method('PUT')
          <div class="form-grid2">
            <div class="form-field">
              <label class="form-label">Name *</label>
              <input type="text" name="name" class="form-input" value="{{ $param->name }}" required>
            </div>
            <div class="form-field">
              <label class="form-label">Unit</label>
              <input type="text" name="unit" class="form-input" value="{{ $param->unit }}" placeholder="kW, %, °C…">
            </div>
          </div>
          <div class="form-grid2">
            <div class="form-field">
              <label class="form-label">Data Type</label>
              <select name="data_type" class="form-input" onchange="onDataTypeChange(this, 'ctrl-{{ $param->id }}', 'input-type-{{ $param->id }}')">
                <option value="float"   {{ $param->data_type==='float'   ? 'selected' : '' }}>Float</option>
                <option value="integer" {{ $param->data_type==='integer' ? 'selected' : '' }}>Integer</option>
                <option value="boolean" {{ $param->data_type==='boolean' ? 'selected' : '' }}>Boolean</option>
                <option value="string"  {{ $param->data_type==='string'  ? 'selected' : '' }}>String</option>
                <option value="switch"  {{ $param->data_type==='switch'  ? 'selected' : '' }}>Switch (ON/OFF)</option>
              </select>
            </div>
            <div class="form-field">
              <label class="form-label">Input Type</label>
              <select name="input_type" id="input-type-{{ $param->id }}" class="form-input" {{ $param->data_type==='switch' ? 'disabled' : '' }}>
                <option value="sensor" {{ ($param->input_type ?? 'sensor')==='sensor' ? 'selected' : '' }}>📡 Sensor</option>
                <option value="manual" {{ ($param->input_type ?? 'sensor')==='manual' ? 'selected' : '' }}>✏ Manual</option>
              </select>
              <input type="hidden" name="input_type" value="sensor" id="input-type-{{ $param->id }}-hidden" {{ $param->data_type==='switch' ? '' : 'disabled' }}>
              <span id="input-type-{{ $param->id }}-note" style="font-size:11px;color:var(--muted);margin-top:2px;display:{{ $param->data_type==='switch' ? 'block' : 'none' }}">Switches are always API-driven (sensor).</span>
            </div>
          </div>
          <div class="form-field" id="ctrl-{{ $param->id }}" style="display:{{ $param->data_type==='switch' ? 'block' : 'none' }}">
            <label class="form-label">Control Type</label>
            <select name="control_type" class="form-input">
              <option value="readonly"     {{ ($param->control_type ?? 'readonly')==='readonly'     ? 'selected' : '' }}>Readonly (status display only)</option>
              <option value="controllable" {{ ($param->control_type ?? 'readonly')==='controllable' ? 'selected' : '' }}>Controllable (toggle ON/OFF from dashboard)</option>
            </select>
            <span style="font-size:11px;color:var(--muted);margin-top:2px;display:block">
              Controllable switches show an ON/OFF toggle on the dashboard and let agents send remote commands (e.g. valves, pumps, fans).
            </span>
          </div>
          <div class="form-grid2">
            <div class="form-field">
              <label class="form-label">Group Name</label>
              <input type="text" name="group_name" class="form-input" value="{{ $param->group_name }}" placeholder="e.g. Yield Data">
            </div>
            <div class="form-field">
              <label class="form-label">⚠ Warning Threshold</label>
              <input type="number" name="warning_threshold" step="any" class="form-input" value="{{ $param->warning_threshold }}">
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:var(--bg);border:1px solid var(--border);border-radius:7px;margin-bottom:10px">
            <input type="checkbox" name="show_on_dashboard" id="dash-{{ $param->id }}" value="1" {{ $param->show_on_dashboard ? 'checked' : '' }} style="accent-color:var(--blue);width:15px;height:15px">
            <label for="dash-{{ $param->id }}" style="font-size:12px;cursor:pointer">Show on dashboard</label>
          </div>
          <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-blue" style="font-size:12px;padding:5px 14px">💾 Save</button>
            <button type="button" class="btn" style="font-size:12px;padding:5px 14px" onclick="toggleEditForm('pedit-{{ $param->id }}')">Cancel</button>
          </div>
        </form>
      </div>
    </div>
    @empty
    <div style="text-align:center;padding:40px;color:var(--muted);background:var(--surface);border:1px solid var(--border);border-radius:var(--r)">
      <div style="font-size:28px;margin-bottom:8px">⚙</div>
      <div style="font-weight:600;margin-bottom:4px">No parameters yet</div>
      <div style="font-size:12px">Add parameters using the form →</div>
    </div>
    @endforelse
  </div>

  {{-- Add parameter form --}}
  <div>
    <div class="form-card">
      <div style="font-size:13px;font-weight:600;margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--border)">
        + Add Parameter
      </div>
      <form method="POST" action="{{ route('admin.parameters.store', [$site, $category]) }}">
        @csrf
        <div class="form-field">
          <label class="form-label">Parameter Name *</label>
          <input type="text" name="name" class="form-input" placeholder="e.g. Solar output" required>
        </div>
        <div class="form-grid2">
          <div class="form-field">
            <label class="form-label">Unit</label>
            <input type="text" name="unit" class="form-input" placeholder="kW, %, °C…">
          </div>
          <div class="form-field">
            <label class="form-label">Data Type *</label>
            <select name="data_type" class="form-input" required onchange="onDataTypeChange(this, 'ctrl-new', 'input-type-new')">
              <option value="float">Float (decimal)</option>
              <option value="integer">Integer</option>
              <option value="boolean">Boolean (on/off)</option>
              <option value="string">String (text)</option>
              <option value="switch">Switch (ON/OFF)</option>
            </select>
          </div>
        </div>
        <div class="form-field">
          <label class="form-label">Input Type *</label>
          <select name="input_type" id="input-type-new" class="form-input" required>
            <option value="sensor">📡 Sensor — automatic via API / ESP32</option>
            <option value="manual">✏ Manual — entered by agent</option>
          </select>
          <input type="hidden" name="input_type" value="sensor" id="input-type-new-hidden" disabled>
          <span id="input-type-new-note" style="font-size:11px;color:var(--muted);margin-top:2px;display:none">Switches are always API-driven (sensor).</span>
        </div>
        <div class="form-field" id="ctrl-new" style="display:none">
          <label class="form-label">Control Type</label>
          <select name="control_type" class="form-input">
            <option value="readonly">Readonly (status display only)</option>
            <option value="controllable">Controllable (toggle ON/OFF from dashboard)</option>
          </select>
          <span style="font-size:11px;color:var(--muted);margin-top:2px;display:block">
            Controllable switches show an ON/OFF toggle on the dashboard and let agents send remote commands (e.g. valves, pumps, fans).
          </span>
        </div>
        <div class="form-field">
          <label class="form-label">Group Name</label>
          <input type="text" name="group_name" class="form-input" placeholder="e.g. Yield Data" list="existing-groups">
          <datalist id="existing-groups">
            @foreach($parameters->pluck('group_name')->filter()->unique() as $g)
              <option value="{{ $g }}">
            @endforeach
          </datalist>
        </div>
        <div class="form-grid2">
          <div class="form-field">
            <label class="form-label">⚠ Warning</label>
            <input type="number" name="warning_threshold" step="any" class="form-input" placeholder="Optional">
          </div>
          <div class="form-field">
            <label class="form-label">🔴 Critical</label>
            <input type="number" name="critical_threshold" step="any" class="form-input" placeholder="Optional">
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;padding:10px 12px;background:var(--bg);border:1px solid var(--border);border-radius:7px;margin-bottom:12px">
          <input type="checkbox" name="show_on_dashboard" id="show_dash" value="1" checked style="accent-color:var(--blue);width:15px;height:15px">
          <label for="show_dash" style="font-size:12px;cursor:pointer">Show on dashboard</label>
        </div>
        <button type="submit" class="btn btn-blue" style="width:100%;justify-content:center">
          + Add Parameter
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
          <input type="hidden" name="show_on_dashboard" value="{{ ($def['show_on_dashboard'] ?? true) ? '1' : '0' }}">
          @if(isset($def['warning_threshold']))
          <input type="hidden" name="warning_threshold" value="{{ $def['warning_threshold'] }}">
          @endif
          <button type="submit" class="btn" style="width:100%;justify-content:flex-start;font-size:12px;gap:8px">
            <span>{{ $def['name'] }}</span>
            <span style="margin-left:auto;color:var(--muted)">
              {{ ($def['input_type'] ?? 'sensor') === 'manual' ? '✏' : '📡' }}
              {{ $def['unit'] ?? $def['data_type'] }}
            </span>
          </button>
        </form>
        @else
        <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 12px;border-radius:7px;font-size:12px;color:var(--muted);background:var(--surface);border:1px solid var(--border);margin-bottom:6px">
          <span>{{ $def['name'] }}</span>
          <span style="color:var(--green)">✓</span>
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
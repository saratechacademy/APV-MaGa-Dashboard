{{-- Renders one live-value card for a sensor-input parameter. Expects $param plus the
     enclosing category scope: $readings, $readingsAt, $offlineThresholdMinutes, $actuators,
     $canControl, $isOnline, $site. --}}
@php
  $val = $readings[$param->slug] ?? null;
  $paramAt = $readingsAt[$param->slug] ?? null;
  $paramOnline = $paramAt && $paramAt->diffInMinutes(now()) <= $offlineThresholdMinutes;
  $isOutOfRange = $param->isOutOfRange($val);
  $isCritical = $param->isCritical($val);
  $isWarn = $param->isWarn($val);
  $isSwitch = $param->data_type === 'switch';
  $isBool = $param->data_type === 'boolean';
  // Pour un switch readonly, on affiche ON/OFF comme un boolean
  $displayVal = ($isBool || $isSwitch) ? ($val ? 'ON' : 'OFF') : ($val !== null ? (is_numeric($val) ? number_format((float)$val, $param->data_type === 'integer' ? 0 : 1) : $val) : '—');
@endphp

@if($isSwitch && $param->isControllable())
  @php
    $cmd = $actuators[$param->slug] ?? null;
    $desired  = $cmd?->desired_state ?? 0;
    $reported = $cmd?->reported_state;
    $synced   = $cmd?->isSynced();
  @endphp
  <div class="switch-card">
    <div style="font-size:11px;color:var(--muted);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:2px" title="{{ $param->name }}">{{ $param->name }}</div>
    <div class="switch-row">
      <span style="font-family:'DM Mono',monospace;font-size:14px;font-weight:600;color:{{ $desired ? 'var(--green)' : 'var(--muted)' }}">
        {{ $desired ? 'ON' : 'OFF' }}
      </span>
      <form method="POST" action="{{ route('actuators.toggle', [$site, $param]) }}" style="display:flex" onsubmit="return false">
        @csrf
        <input type="hidden" name="state" value="{{ $desired ? 0 : 1 }}">
        <label class="toggle">
          <input type="checkbox" {{ $desired ? 'checked' : '' }}
                 {{ $canControl ? '' : 'disabled' }}
                 aria-label="Toggle {{ $param->name }}"
                 onchange="openActuatorModal(this, '{{ addslashes($param->name) }}', {{ $desired ? 0 : 1 }}, {{ $isOnline ? 'true' : 'false' }})">
          <span class="toggle-slider"></span>
        </label>
      </form>
    </div>
    <div style="margin-top:6px">
      @if($synced === true && !$isOnline)
        <span class="sync-pill sync-stale" title="Last report {{ $cmd->reported_at?->diffForHumans() }}">Stale</span>
      @elseif($synced === true)
        <span class="sync-pill sync-ok">Synced</span>
      @elseif($synced === false)
        <span class="sync-pill sync-pending">Pending…</span>
      @else
        <span class="sync-pill sync-unknown">No device report</span>
      @endif
    </div>
  </div>
@elseif($isSwitch)
  {{-- Readonly switch: ON/OFF state reported automatically by the sensor, toggle disabled --}}
  <div class="switch-card">
    <div style="font-size:11px;color:var(--muted);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:2px" title="{{ $param->name }}">{{ $param->name }}</div>
    <div class="switch-row">
      <span style="font-family:'DM Mono',monospace;font-size:14px;font-weight:600;color:{{ $val === null ? 'var(--muted)' : ($val ? 'var(--green)' : 'var(--muted)') }}">
        {{ $val === null ? '—' : ($val ? 'ON' : 'OFF') }}
      </span>
      <label class="toggle">
        <input type="checkbox" {{ $val ? 'checked' : '' }} disabled aria-label="{{ $param->name }} state (read-only)">
        <span class="toggle-slider"></span>
      </label>
    </div>
    <div style="margin-top:6px" title="{{ $paramAt ? 'Last data: '.$paramAt->diffForHumans() : '' }}">
      @if($val === null)
        <span class="badge-nodata">No data</span>
      @elseif(!$paramOnline)
        <span class="sync-pill sync-stale">Stale ({{ $paramAt?->diffForHumans(null, true) ?? 'unknown' }})</span>
      @else
        <span class="sync-pill sync-unknown">Auto (sensor)</span>
      @endif
    </div>
  </div>
@else
  @php
    $noData = $val === null;
    $isStale = !$noData && !$paramOnline;
    $isAlert = $isOutOfRange || $isCritical;
    $cardBd = $noData ? 'var(--border)' : ($isAlert ? 'var(--red-bd)' : ($isWarn ? 'var(--amber-bd)' : ($isStale ? 'var(--amber-bd)' : 'var(--border)')));
    $valColor = $noData ? 'var(--muted)' : ($isAlert ? 'var(--red)' : ($isWarn ? 'var(--amber)' : ($isStale ? 'var(--amber)' : ($isSwitch && $val ? 'var(--green)' : 'var(--text)'))));
  @endphp
  <div style="background:var(--surface);border:1px solid {{ $cardBd }};{{ $noData ? 'border-style:dashed;' : '' }}border-radius:var(--r);box-shadow:var(--shadow);padding:12px 14px;min-width:110px;max-width:150px;flex:1">
    <div style="font-family:'DM Mono',monospace;font-size:20px;font-weight:500;line-height:1.1;color:{{ $valColor }}">
      {{ $displayVal }}
      @if($param->unit && !$isBool && !$isSwitch)<span style="font-size:12px;color:var(--muted)"> {{ $param->unit }}</span>@endif
    </div>
    <div style="font-size:11px;color:var(--muted);margin-top:4px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden" title="{{ $param->name }}">{{ $param->name }}</div>
    <div style="margin-top:6px" title="{{ $paramAt ? 'Last data: '.$paramAt->diffForHumans() : '' }}">
      @if($noData)
        <span class="badge-nodata">No data</span>
      @elseif($isStale)
        <span class="badge-warn-sm">Stale ({{ $paramAt?->diffForHumans(null, true) ?? 'unknown' }})</span>
      @elseif($isOutOfRange)
        <span class="badge-critical-sm">Out of range</span>
      @elseif($isCritical)
        <span class="badge-critical-sm">Critical</span>
      @elseif($isWarn)
        <span class="badge-warn-sm">Warning</span>
      @elseif($isBool)
        <span class="badge-nominal">{{ $val ? 'Open' : 'Closed' }}</span>
      @elseif($isSwitch)
        <span class="badge-nominal">{{ $val ? 'ON' : 'OFF' }}</span>
      @else
        <span class="badge-nominal">Sensor</span>
      @endif
    </div>
  </div>
@endif

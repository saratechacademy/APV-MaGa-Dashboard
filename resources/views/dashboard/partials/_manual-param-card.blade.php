{{-- Renders one read-only "latest manual value" card. Expects $param plus $readings from the enclosing category scope. --}}
@php
  $manVal = $readings[$param->slug] ?? null;
  $manOutOfRange = $param->isOutOfRange($manVal);
  $manCritical = $param->isCritical($manVal);
  $manWarn = $param->isWarn($manVal);
  $manAlert = $manOutOfRange || $manCritical;
  $manBd = $manAlert ? 'var(--red-bd)' : ($manWarn ? 'var(--amber-bd)' : 'var(--blue-bd)');
  $manValColor = $manAlert ? 'var(--red)' : ($manWarn ? 'var(--amber)' : 'var(--text)');
@endphp
<div style="background:var(--surface);border:1px dashed {{ $manBd }};border-radius:var(--r);box-shadow:var(--shadow);padding:12px 14px;min-width:110px;max-width:150px;flex:1">
  <div style="font-family:'DM Mono',monospace;font-size:20px;font-weight:500;line-height:1.1;color:{{ $manValColor }}">
    {{ $manVal ?? '—' }}
    @if($param->unit)<span style="font-size:12px;color:var(--muted)"> {{ $param->unit }}</span>@endif
  </div>
  <div style="font-size:11px;color:var(--muted);margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $param->name }}</div>
  <div style="margin-top:6px">
    @if($manOutOfRange)
      <span class="badge-critical-sm">Out of range</span>
    @elseif($manCritical)
      <span class="badge-critical-sm">Critical</span>
    @elseif($manWarn)
      <span class="badge-warn-sm">Warning</span>
    @else
      <span style="display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:500;padding:2px 7px;border-radius:20px;background:var(--blue-bg);color:var(--blue);border:1px solid var(--blue-bd)">
        Manual
      </span>
    @endif
  </div>
</div>

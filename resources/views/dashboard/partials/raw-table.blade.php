@php
  // Recuperer le slug du site depuis la categorie ou la session
  $siteSlug = $category->site->slug ?? session('current_site_slug', '');
@endphp
<div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);margin-bottom:14px;overflow:hidden">
  <div style="display:flex;align-items:center;justify-content:space-between;padding:11px 16px;border-bottom:1px solid var(--border);background:var(--bg)">
    <span style="font-size:13px;font-weight:600">{{ $category->icon }} {{ $category->name }}</span>
    <div style="display:flex;align-items:center;gap:12px">
      <span style="font-size:11px;color:var(--muted)">{{ $totalSensor + $totalManual }} records</span>
      <div class="dl-row">
        <a href="/export/{{ $siteSlug }}/{{ $category->slug }}/csv?hours={{ request('hours', 1) }}"
           class="dl-btn" style="text-decoration:none">⬇ CSV</a>
        <a href="/export/{{ $siteSlug }}/{{ $category->slug }}/excel?hours={{ request('hours', 1) }}"
           class="dl-btn" style="text-decoration:none">⬇ XLS</a>
      </div>
    </div>
  </div>
  <div style="overflow-x:auto">
    <table class="raw-table">
      <thead>
        <tr>
          <th style="white-space:nowrap">Timestamp</th>
          @foreach($sensorParams as $p)
          <th>{{ $p->name }}@if($p->unit) <span style="font-weight:400;color:var(--muted)">({{ $p->unit }})</span>@endif</th>
          @endforeach
          @foreach($manualParams->where('data_type','!=','string') as $p)
          <th>{{ $p->name }}@if($p->unit) <span style="font-weight:400;color:var(--muted)">({{ $p->unit }})</span>@endif</th>
          @endforeach
          <th>Type</th>
        </tr>
      </thead>
      <tbody>
        @foreach($sensorPaged as $timestamp => $rows)
        <tr>
          <td style="white-space:nowrap;color:var(--muted);font-size:11px">{{ $timestamp }}</td>
          @foreach($sensorParams as $p)
          @php $r = $rows->firstWhere('site_parameter_id', $p->id); @endphp
          <td>{{ $r ? ($r->value ?? $r->value_text ?? '—') : '—' }}</td>
          @endforeach
          @foreach($manualParams->where('data_type','!=','string') as $p)
          <td>—</td>
          @endforeach
          <td><span style="font-size:10px;padding:2px 7px;border-radius:20px;background:var(--green-bg);color:var(--green);border:1px solid var(--green-bd)">📡 Sensor</span></td>
        </tr>
        @endforeach

        @foreach($manualPaged as $timestamp => $rows)
        <tr>
          <td style="white-space:nowrap;color:var(--muted);font-size:11px">{{ $timestamp }}</td>
          @foreach($sensorParams as $p)
          <td>—</td>
          @endforeach
          @foreach($manualParams->where('data_type','!=','string') as $p)
          @php $r = $rows->firstWhere('site_parameter_id', $p->id); @endphp
          <td>{{ $r ? ($r->value ?? '—') : '—' }}</td>
          @endforeach
          <td><span style="font-size:10px;padding:2px 7px;border-radius:20px;background:var(--blue-bg);color:var(--blue);border:1px solid var(--blue-bd)">✏ Manual</span></td>
        </tr>
        @endforeach

        @if($totalSensor === 0 && $totalManual === 0)
        <tr><td colspan="20" style="text-align:center;padding:16px;color:var(--muted)">No data for selected period</td></tr>
        @endif
      </tbody>
    </table>
  </div>

  {{-- Pagination --}}
  @if($totalPages > 1)
  <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 16px;border-top:1px solid var(--border);background:var(--bg)">
    <span style="font-size:12px;color:var(--muted)">Page {{ $rawPage }} of {{ $totalPages }}</span>
    <div style="display:flex;gap:6px">
      @if($rawPage > 1)
      <button onclick="fetchRawData({{ $rawPage - 1 }})"
        style="font-family:'DM Sans',sans-serif;font-size:12px;padding:4px 10px;border:1px solid var(--border);border-radius:5px;background:var(--surface);color:var(--text);cursor:pointer">
        ← Prev
      </button>
      @endif
      @for($p = max(1,$rawPage-2); $p <= min($totalPages,$rawPage+2); $p++)
      <button onclick="fetchRawData({{ $p }})"
        style="font-family:'DM Sans',sans-serif;font-size:12px;padding:4px 10px;border:1px solid {{ $p===$rawPage ? '#1d6ed8' : 'var(--border)' }};border-radius:5px;background:{{ $p===$rawPage ? '#1d6ed8' : 'var(--surface)' }};color:{{ $p===$rawPage ? '#fff' : 'var(--text)' }};cursor:pointer">
        {{ $p }}
      </button>
      @endfor
      @if($rawPage < $totalPages)
      <button onclick="fetchRawData({{ $rawPage + 1 }})"
        style="font-family:'DM Sans',sans-serif;font-size:12px;padding:4px 10px;border:1px solid var(--border);border-radius:5px;background:var(--surface);color:var(--text);cursor:pointer">
        Next →
      </button>
      @endif
    </div>
  </div>
  @endif
</div>
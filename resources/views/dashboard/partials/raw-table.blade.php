@php
  // Recuperer le slug du site depuis la categorie ou la session
  $siteSlug = $category->site->slug ?? session('current_site_slug', '');
  $iconSensor = '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9"></path><path d="M7.8 16.2c-2.3-2.3-2.3-6.1 0-8.5"></path><circle cx="12" cy="12" r="1"></circle><path d="M16.2 7.8c2.3 2.3 2.3 6.1 0 8.5"></path><path d="M19.1 4.9C23 8.8 23 15.1 19.1 19"></path></svg>';
  $iconManual = '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>';
@endphp
<div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);margin-bottom:14px;overflow:hidden">
  <div style="display:flex;align-items:center;justify-content:space-between;padding:11px 16px;border-bottom:1px solid var(--border);background:var(--bg)">
    <span style="font-size:13px;font-weight:600">{{ $category->icon }} {{ $category->name }}</span>
    <div style="display:flex;align-items:center;gap:12px">
      <span style="font-size:11px;color:var(--muted)">{{ $totalSensor + $totalManual }} records</span>
      <div class="dl-row">
        <a href="/export/{{ $siteSlug }}/{{ $category->slug }}/csv?hours={{ request('hours', 1) }}"
           class="dl-btn" style="text-decoration:none;display:inline-flex;align-items:center;gap:4px">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
          CSV
        </a>
        <a href="/export/{{ $siteSlug }}/{{ $category->slug }}/excel?hours={{ request('hours', 1) }}"
           class="dl-btn" style="text-decoration:none;display:inline-flex;align-items:center;gap:4px">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
          XLS
        </a>
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
          @if($p->data_type === 'switch')
            <td>{{ $r ? (($r->value ?? $r->value_text) ? 'ON' : 'OFF') : '—' }}</td>
          @else
            <td>{{ $r ? ($r->value ?? $r->value_text ?? '—') : '—' }}</td>
          @endif
          @endforeach
          @foreach($manualParams->where('data_type','!=','string') as $p)
          <td>—</td>
          @endforeach
          <td><span style="font-size:10px;padding:2px 7px;border-radius:20px;background:var(--green-bg);color:var(--green);border:1px solid var(--green-bd);display:inline-flex;align-items:center;gap:3px">{!! $iconSensor !!} Sensor</span></td>
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
          <td><span style="font-size:10px;padding:2px 7px;border-radius:20px;background:var(--blue-bg);color:var(--blue);border:1px solid var(--blue-bd);display:inline-flex;align-items:center;gap:3px">{!! $iconManual !!} Manual</span></td>
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
      <button onclick="fetchRawData({{ $rawPage - 1 }})" class="btn" style="font-size:12px;padding:4px 10px">
        ← Prev
      </button>
      @endif
      @for($p = max(1,$rawPage-2); $p <= min($totalPages,$rawPage+2); $p++)
      <button onclick="fetchRawData({{ $p }})" class="btn {{ $p===$rawPage ? 'btn-blue' : '' }}" style="font-size:12px;padding:4px 10px">
        {{ $p }}
      </button>
      @endfor
      @if($rawPage < $totalPages)
      <button onclick="fetchRawData({{ $rawPage + 1 }})" class="btn" style="font-size:12px;padding:4px 10px">
        Next →
      </button>
      @endif
    </div>
  </div>
  @endif
</div>
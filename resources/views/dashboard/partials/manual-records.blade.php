@php
  $manualParams  = $category->activeParameters->where('input_type', 'manual');
  $groupedParams = $manualParams->filter(fn($p) => !empty($p->group_name))->groupBy('group_name');
  if ($groupedParams->isEmpty()) return;

  // Calculer le count par groupe
  $groupCounts = [];
  foreach ($groupedParams as $gName => $params) {
    $paramIds = $params->pluck('id');
    $readings = \App\Models\ManualReading::where('site_id', $site->id)
      ->whereIn('site_parameter_id', $paramIds)
      ->orderBy('reading_date', 'desc')
      ->orderBy('created_at', 'desc')
      ->get();
    $rows = $readings->groupBy(fn($r) =>
      \Carbon\Carbon::parse($r->reading_date)->format('Y-m-d') . '||' .
      $r->created_at->format('Y-m-d H:i')
    );
    $groupCounts[$gName] = $rows->count();
  }
  $firstGroup = $groupedParams->keys()->first();
  $firstCount = $groupCounts[$firstGroup] ?? 0;
@endphp

@php $uid = $category->slug . '-' . uniqid(); @endphp

<div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);margin-top:14px;overflow:hidden">

  {{-- Header --}}
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:11px 16px;border-bottom:1px solid var(--border);background:var(--bg)">
    <div style="display:flex;align-items:center;gap:10px">
      <span style="font-size:13px;font-weight:600">📋 Saved Records</span>
      <span id="rec-count-{{ $uid }}" style="font-size:11px;padding:2px 8px;border-radius:20px;background:var(--blue-bg);color:var(--blue);border:1px solid var(--blue-bd)">{{ $firstCount }} record(s)</span>
    </div>
    <div style="display:flex;align-items:center;gap:8px">
      <select id="rec-filter-{{ $uid }}"
        onchange="switchGroup{{ str_replace('-','_',$uid) }}(this.value)"
        style="font-family:'DM Sans',sans-serif;font-size:12px;padding:5px 10px;border:1px solid var(--border);border-radius:6px;background:var(--surface);color:var(--text);cursor:pointer">
        @foreach($groupCounts as $gName => $gCount)
        <option value="{{ $gName }}" data-count="{{ $gCount }}">{{ $gName }}</option>
        @endforeach
      </select>
      <a id="rec-csv-{{ $uid }}" href="/export/{{ $site->slug }}/{{ $category->slug }}/group/{{ rawurlencode($firstGroup) }}/csv"
         style="font-family:'DM Sans',sans-serif;font-size:11px;padding:4px 10px;border:1px solid var(--border);background:var(--surface);border-radius:5px;color:var(--muted);text-decoration:none">⬇ CSV</a>
      <a id="rec-xls-{{ $uid }}" href="/export/{{ $site->slug }}/{{ $category->slug }}/group/{{ rawurlencode($firstGroup) }}/excel"
         style="font-family:'DM Sans',sans-serif;font-size:11px;padding:4px 10px;border:1px solid var(--blue-bd);background:var(--blue-bg);border-radius:5px;color:var(--blue);text-decoration:none">⬇ XLS</a>
    </div>
  </div>

  {{-- Une section par groupe --}}
  @foreach($groupedParams as $gName => $params)
  @php
    $paramIds = $params->pluck('id');
    $readings = \App\Models\ManualReading::where('site_id', $site->id)
      ->whereIn('site_parameter_id', $paramIds)
      ->orderBy('reading_date', 'desc')
      ->orderBy('created_at', 'desc')
      ->get();
    $rows = $readings->groupBy(fn($r) =>
      \Carbon\Carbon::parse($r->reading_date)->format('Y-m-d') . '||' .
      $r->created_at->format('Y-m-d H:i')
    );
  @endphp
  <div id="rec-body-{{ $uid }}-{{ Str::slug($gName) }}"
       style="display:{{ $loop->first ? 'block' : 'none' }}">
    @if($rows->isEmpty())
    <div style="padding:28px;text-align:center;color:var(--muted);font-size:13px">No records yet for {{ $gName }}</div>
    @else
    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch">
      <table style="width:100%;border-collapse:collapse;font-size:12px;min-width:400px">
        <thead>
          <tr style="background:var(--bg)">
            <th style="padding:8px 14px;text-align:left;font-size:11px;font-weight:600;color:var(--muted);white-space:nowrap;border-bottom:1px solid var(--border)">Date</th>
            @foreach($params as $param)
            <th style="padding:8px 14px;text-align:left;font-size:11px;font-weight:600;color:var(--muted);white-space:nowrap;border-bottom:1px solid var(--border)">
              {{ $param->name }}@if($param->unit) ({{ $param->unit }})@endif
            </th>
            @endforeach
            <th style="padding:8px 14px;text-align:left;font-size:11px;font-weight:600;color:var(--muted);border-bottom:1px solid var(--border)">Notes</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rows as $key => $rowReadings)
          @php $date = explode('||', $key)[0]; @endphp
          <tr style="border-top:1px solid var(--border)"
              onmouseover="this.style.background='var(--bg)'"
              onmouseout="this.style.background=''">
            <td style="padding:9px 14px;white-space:nowrap;font-size:11px;color:var(--muted)">{{ $date }}</td>
            @foreach($params as $param)
            @php
              $r   = $rowReadings->firstWhere('site_parameter_id', $param->id);
              $val = $r?->value ?? '—';
            @endphp
            <td style="padding:9px 14px;font-size:12px;color:{{ $val === '—' ? 'var(--muted)' : 'var(--text)' }}">{{ $val }}</td>
            @endforeach
            <td style="padding:9px 14px;font-size:11px;color:var(--muted);font-style:italic">
              {{ $rowReadings->whereNotNull('notes')->first()?->notes ?? '—' }}
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div style="padding:7px 16px;border-top:1px solid var(--border);background:var(--bg);font-size:11px;color:var(--muted)">
      Showing {{ $rows->count() }} record(s) · New entries appear immediately above
    </div>
    @endif
  </div>
  @endforeach
</div>

@php
  $groupSlugs = [];
  foreach($groupedParams->keys() as $gName) {
    $groupSlugs[$gName] = Str::slug($gName);
  }
@endphp
<script>
function switchGroup{{ str_replace('-','_',$uid) }}(group) {
  var uid      = '{{ $uid }}';
  var siteSlug = '{{ $site->slug }}';
  var catSlug  = '{{ $category->slug }}';
  var groups   = @json($groupSlugs);

  // Show/hide tables
  Object.keys(groups).forEach(function(g) {
    var el = document.getElementById('rec-body-' + uid + '-' + groups[g]);
    if (el) el.style.display = (g === group) ? 'block' : 'none';
  });

  // Update count
  var sel = document.getElementById('rec-filter-' + uid);
  var cnt = document.getElementById('rec-count-' + uid);
  if (sel && cnt) {
    var opt = sel.options[sel.selectedIndex];
    cnt.textContent = (opt ? opt.getAttribute('data-count') : '0') + ' record(s)';
  }

  // Update export links
  var encodedGroup = encodeURIComponent(group);
  var csvEl  = document.getElementById('rec-csv-' + uid);
  var xlsEl  = document.getElementById('rec-xls-' + uid);
  if (csvEl) csvEl.href = '/export/' + siteSlug + '/' + catSlug + '/group/' + encodedGroup + '/csv';
  if (xlsEl) xlsEl.href = '/export/' + siteSlug + '/' + catSlug + '/group/' + encodedGroup + '/excel';
}
</script>
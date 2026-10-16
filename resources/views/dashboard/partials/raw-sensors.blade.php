{{-- Raw data on phones: one expandable entry per parameter, newest readings first. --}}
<style>
.rs-cat{margin-bottom:16px}
.rs-cat-head{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin:0 2px 8px}
.rs-item{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);margin-bottom:8px;overflow:hidden}
.rs-item summary{list-style:none;cursor:pointer;padding:12px 14px;display:grid;grid-template-columns:1fr auto;gap:2px 10px;align-items:baseline}
.rs-item summary::-webkit-details-marker{display:none}
.rs-name{font-size:13px;font-weight:600;min-width:0;overflow-wrap:anywhere}
.rs-latest{font-family:'DM Mono',monospace;font-size:15px;font-weight:500;white-space:nowrap}
.rs-unit{font-size:11px;color:var(--muted);margin-left:2px}
.rs-meta{grid-column:1 / -1;font-size:11px;color:var(--muted)}
.rs-item[open] summary{border-bottom:1px solid var(--border);background:var(--bg)}
.rs-item.rs-empty{opacity:.6}
.rs-rows{list-style:none;margin:0;padding:0}
.rs-row{display:flex;justify-content:space-between;gap:12px;padding:9px 14px;border-bottom:1px solid var(--border);font-size:13px}
.rs-row:last-child{border-bottom:none}
.rs-row time{color:var(--muted);font-size:12px}
.rs-row span{font-family:'DM Mono',monospace}
.rs-more{padding:10px 14px}
.rs-more .btn{width:100%;justify-content:center}
</style>

@forelse($sections as $section)
  @if(collect($section['entries'])->isNotEmpty())
  <section class="rs-cat">
    <h3 class="rs-cat-head">{{ $section['category']->icon }} {{ $section['category']->name }}</h3>
    @foreach($section['entries'] as $entry)
      @php
        $parameter = $entry['parameter'];
        $latest    = $entry['rows'][0] ?? null;
      @endphp
      <details class="rs-item {{ $latest ? '' : 'rs-empty' }}">
        <summary>
          <span class="rs-name">{{ $parameter->name }}</span>
          <span class="rs-latest">{{ $latest['value'] ?? '—' }}@if($latest && $parameter->unit)<span class="rs-unit">{{ $parameter->unit }}</span>@endif</span>
          <span class="rs-meta">
            @if($latest)
              {{ $entry['count'] }} {{ Str::plural('reading', $entry['count']) }} · last {{ $latest['at']->format('d M H:i') }} UTC
            @else
              No reading in this period
            @endif
            @if($parameter->input_type === 'manual') · manual entry @endif
          </span>
        </summary>
        @if($latest)
          <ul class="rs-rows">
            @include('dashboard.partials.raw-sensor-rows', ['parameter' => $parameter, 'rows' => $entry['rows'], 'more' => $entry['more']])
          </ul>
        @endif
      </details>
    @endforeach
  </section>
  @endif
@empty
  <div style="text-align:center;padding:30px;color:var(--muted);font-size:13px">No data for selected period</div>
@endforelse

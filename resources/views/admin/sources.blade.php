@extends('layouts.dashboard')
@section('page-title', 'Data Sources')
@section('page-crumb', 'Admin')
@section('content')

@foreach(['success' => 'green', 'error' => 'red'] as $flash => $color)
  @if(session($flash))
  <div class="alert-banner" style="background:var(--{{ $color }}-bg);border-color:var(--{{ $color }}-bd);color:var(--{{ $color }});margin-bottom:16px">
    {{ session($flash) }}
    <button class="ab-close" onclick="this.closest('.alert-banner').style.display='none'" aria-label="Dismiss">×</button>
  </div>
  @endif
@endforeach
@if($errors->any())
  <div class="alert-banner" style="background:var(--red-bg);border-color:var(--red-bd);color:var(--red);margin-bottom:16px">{{ $errors->first() }}</div>
@endif

<p style="font-size:13px;color:var(--muted);margin:0 0 18px;max-width:760px">
  What the ThingsBoard and LI-COR accounts expose, and which site each source feeds.
  Link a source to a site here — or create the site for it — and its parameters and data are created by the next sync.
  To unlink a source, clear the corresponding field in <a href="{{ route('admin.sites') }}">Sites → Edit</a>.
</p>

@php
  $sections = [
    ['source' => 'thingsboard', 'title' => 'ThingsBoard', 'unit' => 'Device prefix', 'column' => 'thingsboard_prefix', 'items' => $thingsboard, 'error' => $thingsboardError],
    ['source' => 'licor',       'title' => 'LI-COR (HOBOnet)', 'unit' => 'Station', 'column' => 'licor_device_serial', 'items' => $licor, 'error' => $licorError],
  ];
@endphp

@foreach($sections as $section)
<div class="sec-header" style="{{ $loop->first ? '' : 'margin-top:24px' }}">
  <span class="sec-title">{{ $section['title'] }} ({{ count($section['items']) }})</span>
</div>

@if($section['error'])
  <div class="alert-banner" style="background:var(--amber-bg);border-color:var(--amber-bd);color:var(--amber)">
    Could not list the {{ $section['title'] }} sources: {{ $section['error'] }}
  </div>
@elseif(!$section['items'])
  <div style="font-size:13px;color:var(--muted)">Nothing exposed by this account.</div>
@else
<div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;box-shadow:var(--shadow)">
  <table class="raw-table" style="font-size:13px">
    <thead><tr><th>{{ $section['unit'] }}</th><th>Contains</th><th>Feeds site</th></tr></thead>
    <tbody>
    @foreach($section['items'] as $item)
      @php
        $linked    = $sites->firstWhere($section['column'], $item['key']);
        $available = $sites->filter(fn ($s) => blank($s->{$section['column']}));
        $formId    = $section['source'] . '-' . $loop->index;
      @endphp
      <tr>
        <td><strong>{{ $item['label'] }}</strong>@if($item['label'] !== $item['key'])<div style="font-size:11px;color:var(--muted)">{{ $item['key'] }}</div>@endif</td>
        <td>{{ $item['detail'] }}</td>
        <td>
          @if($linked)
            <span class="badge badge-ok">{{ $linked->name }}</span>
          @else
            <form method="POST" action="{{ route('admin.sources.link') }}" style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
              @csrf
              <input type="hidden" name="source" value="{{ $section['source'] }}">
              <input type="hidden" name="key" value="{{ $item['key'] }}">
              <select name="site_id" class="form-input" style="width:auto;min-width:190px" aria-label="Site for {{ $item['label'] }}"
                      onchange="document.getElementById('{{ $formId }}-new').style.display = this.value ? 'none' : 'flex'">
                <option value="">+ Create a new site…</option>
                @foreach($available as $s)
                  <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
              </select>
              <span id="{{ $formId }}-new" style="display:flex;gap:6px;flex-wrap:wrap">
                <input type="text" name="name" class="form-input" style="width:190px" placeholder="Site name" value="{{ $item['label'] }}" aria-label="New site name">
                <select name="country" class="form-input" style="width:auto" aria-label="New site country">
                  <option value="">Country…</option>
                  <x-country-options />
                </select>
              </span>
              <button type="submit" class="btn btn-blue" style="font-size:12px;padding:5px 12px">Link</button>
            </form>
          @endif
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>
@endif
@endforeach

@endsection

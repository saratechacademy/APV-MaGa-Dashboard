@foreach($rows as $row)
  <li class="rs-row">
    <time datetime="{{ $row['at']->toIso8601String() }}">{{ $row['at']->format('d M Y H:i') }}@if(!empty($row['plot']))<br><small>{{ $row['plot'] }}</small>@endif</time>
    <span>{{ $row['value'] ?? '—' }}@if($row['value'] !== null && $parameter->unit) {{ $parameter->unit }}@endif</span>
  </li>
@endforeach
@if($more && count($rows))
  <li class="rs-more">
    <button type="button" class="btn" onclick="rawSensorMore(this, {{ $parameter->id }}, @js(end($rows)['at']->toIso8601String()))">Show older readings</button>
  </li>
@endif

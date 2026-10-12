{{-- <option>s for a country <select>, from config/countries.php.
     A stored value that isn't in the list (an older spelling) is kept as an
     extra selected option, so saving the form doesn't silently change it. --}}
@props(['selected' => null, 'flags' => false])
@php $countries = config('countries'); @endphp
@if(filled($selected) && !isset($countries[$selected]))
  <option value="{{ $selected }}" selected>{{ $selected }}</option>
@endif
@foreach($countries as $name => $info)
  <option value="{{ $name }}" @selected($selected === $name)>{{ $flags ? $info['flag'] . ' ' : '' }}{{ $name }}</option>
@endforeach

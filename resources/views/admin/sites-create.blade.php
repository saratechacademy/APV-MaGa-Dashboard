@extends('layouts.dashboard')
@section('page-title', 'New Site')
@section('page-crumb', 'Admin › Sites')


@section('content')

@if($errors->any())
<div style="background:var(--red-bg);border:1px solid var(--red-bd);border-radius:8px;padding:12px 16px;margin-bottom:16px;color:var(--red)">
  @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
</div>
@endif

<form method="POST" action="{{ route('admin.sites.store') }}">
@csrf

<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px">

  {{-- Left column --}}
  <div>
    <div class="form-card">
      <div class="section-title">Site Information</div>
      <div class="form-grid">
        <div class="form-field full">
          <label class="form-label" for="site-name">Site Name *</label>
          <input type="text" id="site-name" name="name" class="form-input {{ $errors->has('name') ? 'error' : '' }}"
                 value="{{ old('name') }}" placeholder="e.g. Fass Agrivoltaic Site" required>
        </div>

        <div class="form-field">
          <label class="form-label" for="site-country">Country *</label>
          <select id="site-country" name="country" class="form-input" required>
            <option value="">Select country...</option>
              <x-country-options :selected="old('country')" />
          </select>
        </div>

        <div class="form-field">
          <label class="form-label" for="site-user">Assigned Agent *</label>
          <select id="site-user" name="user_id" class="form-input" required>
            <option value="">Select agent...</option>
            @forelse($agents as $agent)
              <option value="{{ $agent->id }}" {{ old('user_id')==$agent->id ? 'selected':'' }}>
                {{ $agent->name }} — {{ ucfirst($agent->role) }}
              </option>
            @empty
              <option value="" disabled>No agents available — create users first</option>
            @endforelse
          </select>
          @if($agents->isEmpty())
          <span class="form-hint" style="color:var(--amber)">
            No active agents found. <a href="{{ route('admin.users') }}" style="color:var(--blue)">Create a user first</a>.
          </span>
          @endif
        </div>

        <div class="form-field">
          <label class="form-label" for="site-latitude">Latitude</label>
          <input type="number" id="site-latitude" name="latitude" step="0.0001" class="form-input"
                 value="{{ old('latitude') }}" placeholder="e.g. 13.4549">
        </div>
        <div class="form-field">
          <label class="form-label" for="site-longitude">Longitude</label>
          <input type="number" id="site-longitude" name="longitude" step="0.0001" class="form-input"
                 value="{{ old('longitude') }}" placeholder="e.g. -16.5782">
        </div>
        <div class="form-field">
          <label class="form-label" for="site-capacity">Installed Capacity (kWp)</label>
          <input type="number" id="site-capacity" name="capacity_kw" step="0.1" class="form-input"
                 value="{{ old('capacity_kw') }}" placeholder="e.g. 5.5">
        </div>
        <div class="form-field">
          <label class="form-label" for="site-area">Farmland Area (m²)</label>
          <input type="number" id="site-area" name="area_m2" step="1" class="form-input"
                 value="{{ old('area_m2') }}" placeholder="e.g. 1200">
        </div>
        <div class="form-field full">
          <label class="form-label" for="site-description">Description</label>
          <textarea id="site-description" name="description" class="form-input" placeholder="Site description, objectives...">{{ old('description') }}</textarea>
        </div>
      </div>
    </div>
  </div>

  {{-- Right column --}}
  <div>
    <div class="form-card">
      <div class="section-title">Default Categories & Parameters</div>
      <div class="checkbox-row" style="margin-bottom:14px">
        <input type="checkbox" name="create_default_categories" id="defaults" value="1" checked>
        <div>
          <label for="defaults" style="font-size:13px;font-weight:500">Auto-create all categories</label>
          <div class="hint">Creates Solar, Water, Irrigation, Weather and Agriculture with their default parameters</div>
        </div>
      </div>
      <div style="background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:12px">
        @foreach(\App\Models\SiteCategory::defaults() as $cat)
        <div style="display:flex;align-items:center;gap:8px;padding:5px 0;font-size:12px;{{ !$loop->last ? 'border-bottom:1px solid var(--border);' : '' }}">
          <span>{{ $cat['icon'] }}</span>
          <span style="font-weight:500">{{ $cat['name'] }}</span>
          <span style="margin-left:auto;color:var(--muted)">
            {{ count(\App\Models\SiteParameter::defaultsFor($cat['slug'])) }} params
          </span>
        </div>
        @endforeach
      </div>
    </div>

    <div class="form-card" style="background:var(--bg)">
      <div class="section-title">ℹ About the API Key</div>
      <p style="font-size:12px;color:var(--muted);line-height:1.6">
        An API key will be generated automatically when the site is created.
        The agent can use it to post sensor data from ESP32 / Arduino devices.
      </p>
      <div style="margin-top:10px;font-size:11px;color:var(--muted);font-family:'DM Mono',monospace;background:var(--surface);border:1px solid var(--border);padding:8px 10px;border-radius:6px">
        apv-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
      </div>
    </div>

    <div style="display:flex;gap:8px">
      <a href="{{ route('admin.sites') }}" class="btn" style="flex:1;justify-content:center">Cancel</a>
      <button type="submit" class="btn btn-blue" style="flex:2;justify-content:center">Create Site</button>
    </div>
  </div>

</div>
</form>
@endsection
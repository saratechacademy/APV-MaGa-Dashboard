@extends('layouts.dashboard')
@section('page-title', 'Edit Site')
@section('page-crumb', 'Admin › Sites')

@section('content')

@if($errors->any())
<div style="background:var(--red-bg);border:1px solid var(--red-bd);border-radius:8px;padding:12px 16px;margin-bottom:16px;color:var(--red)">
  @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
</div>
@endif

<form method="POST" action="{{ route('admin.sites.update', $site) }}">
@csrf
@method('PUT')

<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px">

  {{-- Left column --}}
  <div>
    <div class="form-card">
      <div class="section-title">Site Information</div>
      <div class="form-grid">
        <div class="form-field full">
          <label class="form-label" for="site-name">Site Name *</label>
          <input type="text" id="site-name" name="name" class="form-input {{ $errors->has('name') ? 'error' : '' }}"
                 value="{{ old('name', $site->name) }}" required>
        </div>

        <div class="form-field">
          <label class="form-label" for="site-country">Country *</label>
          <select id="site-country" name="country" class="form-input" required>
            <option value="">Select country...</option>
              <x-country-options :selected="old('country', $site->country)" />
          </select>
        </div>

        <div class="form-field">
          <label class="form-label" for="site-user">Assigned Agent *</label>
          <select id="site-user" name="user_id" class="form-input" required>
            <option value="">Select agent...</option>
            @forelse($agents as $agent)
              <option value="{{ $agent->id }}" {{ old('user_id',$site->user_id)==$agent->id ? 'selected':'' }}>
                {{ $agent->name }} — {{ ucfirst($agent->role) }}
              </option>
            @empty
              <option value="" disabled>No agents available — create users first</option>
            @endforelse
          </select>
        </div>

        <div class="form-field">
          <label class="form-label" for="site-status">Status *</label>
          <select id="site-status" name="status" class="form-input" required>
            <option value="active"      {{ old('status',$site->status)=='active'      ? 'selected':'' }}>Active</option>
            <option value="inactive"    {{ old('status',$site->status)=='inactive'    ? 'selected':'' }}>Inactive</option>
            <option value="maintenance" {{ old('status',$site->status)=='maintenance' ? 'selected':'' }}>Maintenance</option>
          </select>
        </div>

        <div class="form-field">
          <label class="form-label" for="site-latitude">Latitude</label>
          <input type="number" id="site-latitude" name="latitude" step="0.0001" class="form-input"
                 value="{{ old('latitude', $site->latitude) }}" placeholder="e.g. 13.4549">
        </div>
        <div class="form-field">
          <label class="form-label" for="site-longitude">Longitude</label>
          <input type="number" id="site-longitude" name="longitude" step="0.0001" class="form-input"
                 value="{{ old('longitude', $site->longitude) }}" placeholder="e.g. -16.5782">
        </div>
        <div class="form-field">
          <label class="form-label" for="site-capacity">Installed Capacity (kWp)</label>
          <input type="number" id="site-capacity" name="capacity_kw" step="0.1" class="form-input"
                 value="{{ old('capacity_kw', $site->capacity_kw) }}" placeholder="e.g. 5.5">
        </div>
        <div class="form-field">
          <label class="form-label" for="site-area">Farmland Area (m²)</label>
          <input type="number" id="site-area" name="area_m2" step="1" class="form-input"
                 value="{{ old('area_m2', $site->area_m2) }}" placeholder="e.g. 1200">
        </div>
        <div class="form-field">
          <label class="form-label" for="site-thingsboard-prefix">ThingsBoard device prefix</label>
          <input type="text" id="site-thingsboard-prefix" name="thingsboard_prefix" maxlength="50" class="form-input"
                 value="{{ old('thingsboard_prefix', $site->thingsboard_prefix) }}" placeholder="e.g. UTG — leave empty if not on ThingsBoard">
        </div>
        <div class="form-field">
          <label class="form-label" for="site-licor-serial">LI-COR station serial</label>
          <input type="text" id="site-licor-serial" name="licor_device_serial" maxlength="50" class="form-input"
                 value="{{ old('licor_device_serial', $site->licor_device_serial) }}" placeholder="e.g. 22490036 — leave empty if no HOBOnet station">
        </div>
        <div class="form-field">
          <label class="form-label" for="site-tank-radius">Water tank radius (cm)</label>
          <input type="number" id="site-tank-radius" name="tank_radius_cm" step="0.1" min="0" class="form-input"
                 value="{{ old('tank_radius_cm', $site->tank_radius_cm) }}" placeholder="e.g. 180 — leave empty if unknown">
          <span style="font-size:11px;color:var(--muted);margin-top:2px;display:block">Used to turn the measured water height into a volume. Without it, no tank volume is computed.</span>
        </div>
        <div class="form-field">
          <label class="form-label" for="site-tank-count">Number of tanks</label>
          <input type="number" id="site-tank-count" name="tank_count" step="1" min="1" class="form-input"
                 value="{{ old('tank_count', $site->tank_count ?? 1) }}">
        </div>
        <div class="form-field full">
          <label class="form-label" for="site-licor-locations">LI-COR sensor locations</label>
          <textarea id="site-licor-locations" name="licor_locations" class="form-input" rows="4"
                    placeholder="One sensor per line, e.g.&#10;22261662 = Reference&#10;22261664 = Under PV">{{ old('licor_locations', collect($site->licor_locations ?? [])->map(fn ($location, $serial) => "$serial = $location")->implode("\n")) }}</textarea>
          <span style="font-size:11px;color:var(--muted);margin-top:2px;display:block">Sensor serial (without the “-1”, “-2” channel) = where it stands. Saving renames this site's LI-COR parameters accordingly (“Reference – PAR”); parameters you renamed yourself are kept.</span>
        </div>
        <div class="form-field full">
          <label class="form-label" for="site-description">Description</label>
          <textarea id="site-description" name="description" class="form-input" placeholder="Site description, objectives...">{{ old('description', $site->description) }}</textarea>
        </div>
      </div>
    </div>
  </div>

  {{-- Right column --}}
  <div>
    <div class="form-card" style="background:var(--bg)">
      <div class="section-title" style="display:flex;align-items:center;gap:6px">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="5.5"></circle><path d="m21 2-9.6 9.6"></path><path d="m15.5 7.5 3 3L22 7l-3-3"></path></svg>
        API Key
      </div>
      <p style="font-size:12px;color:var(--muted);line-height:1.6">
        The API key is fixed at creation and cannot be changed here — devices already
        configured with it would stop reporting. Manage categories and parameters from
        the Categories page.
      </p>
      <div style="margin-top:10px;font-size:11px;color:var(--muted);font-family:'DM Mono',monospace;background:var(--surface);border:1px solid var(--border);padding:8px 10px;border-radius:6px;word-break:break-all">
        {{ $site->api_key }}
      </div>
    </div>

    <div class="form-card">
      <a href="{{ route('admin.categories', $site) }}" class="btn" style="width:100%;justify-content:center;margin-bottom:8px">Manage Categories</a>
      <a href="{{ route('admin.sites.users', $site) }}" class="btn" style="width:100%;justify-content:center">Manage Users</a>
    </div>

    <div style="display:flex;gap:8px">
      <a href="{{ route('admin.sites') }}" class="btn" style="flex:1;justify-content:center">Cancel</a>
      <button type="submit" class="btn btn-blue" style="flex:2;justify-content:center">Save Changes</button>
    </div>
  </div>

</div>
</form>
@endsection

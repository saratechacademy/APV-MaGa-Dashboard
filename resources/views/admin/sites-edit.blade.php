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
            <optgroup label="── West Africa ──">
              <option value="Benin"         {{ old('country',$site->country)=='Benin'         ? 'selected':'' }}>Benin</option>
              <option value="Burkina Faso"  {{ old('country',$site->country)=='Burkina Faso'  ? 'selected':'' }}>Burkina Faso</option>
              <option value="Cape Verde"    {{ old('country',$site->country)=='Cape Verde'    ? 'selected':'' }}>Cape Verde</option>
              <option value="Ivory Coast"   {{ old('country',$site->country)=='Ivory Coast'   ? 'selected':'' }}>Ivory Coast</option>
              <option value="Gambia"        {{ old('country',$site->country)=='Gambia'        ? 'selected':'' }}>Gambia</option>
              <option value="Ghana"         {{ old('country',$site->country)=='Ghana'         ? 'selected':'' }}>Ghana</option>
              <option value="Guinea"        {{ old('country',$site->country)=='Guinea'        ? 'selected':'' }}>Guinea</option>
              <option value="Guinea-Bissau" {{ old('country',$site->country)=='Guinea-Bissau' ? 'selected':'' }}>Guinea-Bissau</option>
              <option value="Liberia"       {{ old('country',$site->country)=='Liberia'       ? 'selected':'' }}>Liberia</option>
              <option value="Mali"          {{ old('country',$site->country)=='Mali'          ? 'selected':'' }}>Mali</option>
              <option value="Mauritania"    {{ old('country',$site->country)=='Mauritania'    ? 'selected':'' }}>Mauritania</option>
              <option value="Niger"         {{ old('country',$site->country)=='Niger'         ? 'selected':'' }}>Niger</option>
              <option value="Nigeria"       {{ old('country',$site->country)=='Nigeria'       ? 'selected':'' }}>Nigeria</option>
              <option value="Senegal"       {{ old('country',$site->country)=='Senegal'       ? 'selected':'' }}>Senegal</option>
              <option value="Sierra Leone"  {{ old('country',$site->country)=='Sierra Leone'  ? 'selected':'' }}>Sierra Leone</option>
              <option value="Togo"          {{ old('country',$site->country)=='Togo'          ? 'selected':'' }}>Togo</option>
            </optgroup>
            <optgroup label="── Central Africa ──">
              <option value="Cameroon"      {{ old('country',$site->country)=='Cameroon'      ? 'selected':'' }}>Cameroon</option>
              <option value="Central African Republic" {{ old('country',$site->country)=='Central African Republic' ? 'selected':'' }}>Central African Republic</option>
              <option value="Chad"          {{ old('country',$site->country)=='Chad'          ? 'selected':'' }}>Chad</option>
              <option value="Congo"         {{ old('country',$site->country)=='Congo'         ? 'selected':'' }}>Congo</option>
              <option value="DRC"           {{ old('country',$site->country)=='DRC'           ? 'selected':'' }}>DR Congo</option>
              <option value="Equatorial Guinea" {{ old('country',$site->country)=='Equatorial Guinea' ? 'selected':'' }}>Equatorial Guinea</option>
              <option value="Gabon"         {{ old('country',$site->country)=='Gabon'         ? 'selected':'' }}>Gabon</option>
              <option value="Sao Tome and Principe" {{ old('country',$site->country)=='Sao Tome and Principe' ? 'selected':'' }}>São Tomé and Príncipe</option>
            </optgroup>
            <optgroup label="── East Africa ──">
              <option value="Burundi"       {{ old('country',$site->country)=='Burundi'       ? 'selected':'' }}>Burundi</option>
              <option value="Comoros"       {{ old('country',$site->country)=='Comoros'       ? 'selected':'' }}>Comoros</option>
              <option value="Djibouti"      {{ old('country',$site->country)=='Djibouti'      ? 'selected':'' }}>Djibouti</option>
              <option value="Eritrea"       {{ old('country',$site->country)=='Eritrea'       ? 'selected':'' }}>Eritrea</option>
              <option value="Ethiopia"      {{ old('country',$site->country)=='Ethiopia'      ? 'selected':'' }}>Ethiopia</option>
              <option value="Kenya"         {{ old('country',$site->country)=='Kenya'         ? 'selected':'' }}>Kenya</option>
              <option value="Madagascar"    {{ old('country',$site->country)=='Madagascar'    ? 'selected':'' }}>Madagascar</option>
              <option value="Malawi"        {{ old('country',$site->country)=='Malawi'        ? 'selected':'' }}>Malawi</option>
              <option value="Mauritius"     {{ old('country',$site->country)=='Mauritius'     ? 'selected':'' }}>Mauritius</option>
              <option value="Rwanda"        {{ old('country',$site->country)=='Rwanda'        ? 'selected':'' }}>Rwanda</option>
              <option value="Seychelles"    {{ old('country',$site->country)=='Seychelles'    ? 'selected':'' }}>Seychelles</option>
              <option value="Somalia"       {{ old('country',$site->country)=='Somalia'       ? 'selected':'' }}>Somalia</option>
              <option value="South Sudan"   {{ old('country',$site->country)=='South Sudan'   ? 'selected':'' }}>South Sudan</option>
              <option value="Tanzania"      {{ old('country',$site->country)=='Tanzania'      ? 'selected':'' }}>Tanzania</option>
              <option value="Uganda"        {{ old('country',$site->country)=='Uganda'        ? 'selected':'' }}>Uganda</option>
            </optgroup>
            <optgroup label="── North Africa ──">
              <option value="Algeria"       {{ old('country',$site->country)=='Algeria'       ? 'selected':'' }}>Algeria</option>
              <option value="Egypt"         {{ old('country',$site->country)=='Egypt'         ? 'selected':'' }}>Egypt</option>
              <option value="Libya"         {{ old('country',$site->country)=='Libya'         ? 'selected':'' }}>Libya</option>
              <option value="Morocco"       {{ old('country',$site->country)=='Morocco'       ? 'selected':'' }}>Morocco</option>
              <option value="Sudan"         {{ old('country',$site->country)=='Sudan'         ? 'selected':'' }}>Sudan</option>
              <option value="Tunisia"       {{ old('country',$site->country)=='Tunisia'       ? 'selected':'' }}>Tunisia</option>
              <option value="Western Sahara" {{ old('country',$site->country)=='Western Sahara' ? 'selected':'' }}>Western Sahara</option>
            </optgroup>
            <optgroup label="── Southern Africa ──">
              <option value="Angola"        {{ old('country',$site->country)=='Angola'        ? 'selected':'' }}>Angola</option>
              <option value="Botswana"      {{ old('country',$site->country)=='Botswana'      ? 'selected':'' }}>Botswana</option>
              <option value="Eswatini"      {{ old('country',$site->country)=='Eswatini'      ? 'selected':'' }}>Eswatini</option>
              <option value="Lesotho"       {{ old('country',$site->country)=='Lesotho'       ? 'selected':'' }}>Lesotho</option>
              <option value="Mozambique"    {{ old('country',$site->country)=='Mozambique'    ? 'selected':'' }}>Mozambique</option>
              <option value="Namibia"       {{ old('country',$site->country)=='Namibia'       ? 'selected':'' }}>Namibia</option>
              <option value="South Africa"  {{ old('country',$site->country)=='South Africa'  ? 'selected':'' }}>South Africa</option>
              <option value="Zambia"        {{ old('country',$site->country)=='Zambia'        ? 'selected':'' }}>Zambia</option>
              <option value="Zimbabwe"      {{ old('country',$site->country)=='Zimbabwe'      ? 'selected':'' }}>Zimbabwe</option>
            </optgroup>
            <option value="Other"           {{ old('country',$site->country)=='Other'         ? 'selected':'' }}>Other</option>
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

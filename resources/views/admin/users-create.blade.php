@extends('layouts.dashboard')
@section('page-title', 'New User')
@section('page-crumb', 'Admin › Users')


@section('content')

@if($errors->any())
<div style="background:var(--red-bg);border:1px solid var(--red-bd);border-radius:8px;padding:12px 16px;margin-bottom:16px;color:var(--red)">
  @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
</div>
@endif

<form method="POST" action="{{ route('admin.users.store') }}">
@csrf

<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px">

  {{-- Left column --}}
  <div>
    <div class="form-card">
      <div class="section-title">User Information</div>
      <div class="form-grid">

        <div class="form-field full">
          <label class="form-label" for="user-name">Full Name *</label>
          <input type="text" id="user-name" name="name" class="form-input {{ $errors->has('name') ? 'error' : '' }}"
                 value="{{ old('name') }}" placeholder="e.g. John Doe" required>
        </div>

        <div class="form-field full">
          <label class="form-label" for="user-email">Email Address *</label>
          <input type="email" id="user-email" name="email" class="form-input {{ $errors->has('email') ? 'error' : '' }}"
                 value="{{ old('email') }}" placeholder="e.g. john@example.com" required>
        </div>

        <div class="form-field">
          <label class="form-label" for="user-password">Password *</label>
          <input type="password" id="user-password" name="password" class="form-input {{ $errors->has('password') ? 'error' : '' }}"
                 placeholder="Min. 8 characters" required>
        </div>

        <div class="form-field">
          <label class="form-label" for="user-password-confirm">Confirm Password *</label>
          <input type="password" id="user-password-confirm" name="password_confirmation" class="form-input"
                 placeholder="Repeat password" required>
        </div>

        <div class="form-field">
          <label class="form-label" for="user-role">Role *</label>
          <select id="user-role" name="role" class="form-input {{ $errors->has('role') ? 'error' : '' }}" required>
            <option value="">Select role...</option>
            <option value="agent"        {{ old('role')=='agent'        ? 'selected':'' }}>Agent — Manages sites</option>
            <option value="observateur"  {{ old('role')=='observateur'  ? 'selected':'' }}>Observer — Read only</option>
            <option value="admin"        {{ old('role')=='admin'        ? 'selected':'' }}>Admin — Full access</option>
          </select>
        </div>

        <div class="form-field">
          <label class="form-label" for="user-status">Status *</label>
          <select id="user-status" name="status" class="form-input" required>
            <option value="active"    {{ old('status','active')=='active'    ? 'selected':'' }}>Active</option>
            <option value="pending"   {{ old('status')=='pending'   ? 'selected':'' }}>Pending</option>
            <option value="suspended" {{ old('status')=='suspended' ? 'selected':'' }}>Suspended</option>
          </select>
        </div>

        <div class="form-field">
          <label class="form-label" for="user-organisation">Organisation</label>
          <input type="text" id="user-organisation" name="organisation" class="form-input"
                 value="{{ old('organisation') }}" placeholder="e.g. APV-MaGa Project">
        </div>

        <div class="form-field">
          <label class="form-label" for="user-country">Country</label>
          <select id="user-country" name="country" class="form-input">
            <option value="">Select country...</option>
            <optgroup label="── West Africa ──">
              <option value="Benin"         {{ old('country')=='Benin'         ? 'selected':'' }}>Benin</option>
              <option value="Burkina Faso"  {{ old('country')=='Burkina Faso'  ? 'selected':'' }}>Burkina Faso</option>
              <option value="Cape Verde"    {{ old('country')=='Cape Verde'    ? 'selected':'' }}>Cape Verde</option>
              <option value="Ivory Coast"   {{ old('country')=='Ivory Coast'   ? 'selected':'' }}>Ivory Coast</option>
              <option value="Gambia"        {{ old('country')=='Gambia'        ? 'selected':'' }}>Gambia</option>
              <option value="Ghana"         {{ old('country')=='Ghana'         ? 'selected':'' }}>Ghana</option>
              <option value="Guinea"        {{ old('country')=='Guinea'        ? 'selected':'' }}>Guinea</option>
              <option value="Guinea-Bissau" {{ old('country')=='Guinea-Bissau' ? 'selected':'' }}>Guinea-Bissau</option>
              <option value="Liberia"       {{ old('country')=='Liberia'       ? 'selected':'' }}>Liberia</option>
              <option value="Mali"          {{ old('country')=='Mali'          ? 'selected':'' }}>Mali</option>
              <option value="Mauritania"    {{ old('country')=='Mauritania'    ? 'selected':'' }}>Mauritania</option>
              <option value="Niger"         {{ old('country')=='Niger'         ? 'selected':'' }}>Niger</option>
              <option value="Nigeria"       {{ old('country')=='Nigeria'       ? 'selected':'' }}>Nigeria</option>
              <option value="Senegal"       {{ old('country')=='Senegal'       ? 'selected':'' }}>Senegal</option>
              <option value="Sierra Leone"  {{ old('country')=='Sierra Leone'  ? 'selected':'' }}>Sierra Leone</option>
              <option value="Togo"          {{ old('country')=='Togo'          ? 'selected':'' }}>Togo</option>
            </optgroup>
            <optgroup label="── Central Africa ──">
              <option value="Cameroon"      {{ old('country')=='Cameroon'      ? 'selected':'' }}>Cameroon</option>
              <option value="Central African Republic" {{ old('country')=='Central African Republic' ? 'selected':'' }}>Central African Republic</option>
              <option value="Chad"          {{ old('country')=='Chad'          ? 'selected':'' }}>Chad</option>
              <option value="Congo"         {{ old('country')=='Congo'         ? 'selected':'' }}>Congo</option>
              <option value="DRC"           {{ old('country')=='DRC'           ? 'selected':'' }}>DR Congo</option>
              <option value="Equatorial Guinea" {{ old('country')=='Equatorial Guinea' ? 'selected':'' }}>Equatorial Guinea</option>
              <option value="Gabon"         {{ old('country')=='Gabon'         ? 'selected':'' }}>Gabon</option>
              <option value="Sao Tome and Principe" {{ old('country')=='Sao Tome and Principe' ? 'selected':'' }}>São Tomé and Príncipe</option>
            </optgroup>
            <optgroup label="── East Africa ──">
              <option value="Burundi"       {{ old('country')=='Burundi'       ? 'selected':'' }}>Burundi</option>
              <option value="Comoros"       {{ old('country')=='Comoros'       ? 'selected':'' }}>Comoros</option>
              <option value="Djibouti"      {{ old('country')=='Djibouti'      ? 'selected':'' }}>Djibouti</option>
              <option value="Eritrea"       {{ old('country')=='Eritrea'       ? 'selected':'' }}>Eritrea</option>
              <option value="Ethiopia"      {{ old('country')=='Ethiopia'      ? 'selected':'' }}>Ethiopia</option>
              <option value="Kenya"         {{ old('country')=='Kenya'         ? 'selected':'' }}>Kenya</option>
              <option value="Madagascar"    {{ old('country')=='Madagascar'    ? 'selected':'' }}>Madagascar</option>
              <option value="Malawi"        {{ old('country')=='Malawi'        ? 'selected':'' }}>Malawi</option>
              <option value="Mauritius"     {{ old('country')=='Mauritius'     ? 'selected':'' }}>Mauritius</option>
              <option value="Rwanda"        {{ old('country')=='Rwanda'        ? 'selected':'' }}>Rwanda</option>
              <option value="Seychelles"    {{ old('country')=='Seychelles'    ? 'selected':'' }}>Seychelles</option>
              <option value="Somalia"       {{ old('country')=='Somalia'       ? 'selected':'' }}>Somalia</option>
              <option value="South Sudan"   {{ old('country')=='South Sudan'   ? 'selected':'' }}>South Sudan</option>
              <option value="Tanzania"      {{ old('country')=='Tanzania'      ? 'selected':'' }}>Tanzania</option>
              <option value="Uganda"        {{ old('country')=='Uganda'        ? 'selected':'' }}>Uganda</option>
            </optgroup>
            <optgroup label="── North Africa ──">
              <option value="Algeria"       {{ old('country')=='Algeria'       ? 'selected':'' }}>Algeria</option>
              <option value="Egypt"         {{ old('country')=='Egypt'         ? 'selected':'' }}>Egypt</option>
              <option value="Libya"         {{ old('country')=='Libya'         ? 'selected':'' }}>Libya</option>
              <option value="Morocco"       {{ old('country')=='Morocco'       ? 'selected':'' }}>Morocco</option>
              <option value="Sudan"         {{ old('country')=='Sudan'         ? 'selected':'' }}>Sudan</option>
              <option value="Tunisia"       {{ old('country')=='Tunisia'       ? 'selected':'' }}>Tunisia</option>
              <option value="Western Sahara" {{ old('country')=='Western Sahara' ? 'selected':'' }}>Western Sahara</option>
            </optgroup>
            <optgroup label="── Southern Africa ──">
              <option value="Angola"        {{ old('country')=='Angola'        ? 'selected':'' }}>Angola</option>
              <option value="Botswana"      {{ old('country')=='Botswana'      ? 'selected':'' }}>Botswana</option>
              <option value="Eswatini"      {{ old('country')=='Eswatini'      ? 'selected':'' }}>Eswatini</option>
              <option value="Lesotho"       {{ old('country')=='Lesotho'       ? 'selected':'' }}>Lesotho</option>
              <option value="Mozambique"    {{ old('country')=='Mozambique'    ? 'selected':'' }}>Mozambique</option>
              <option value="Namibia"       {{ old('country')=='Namibia'       ? 'selected':'' }}>Namibia</option>
              <option value="South Africa"  {{ old('country')=='South Africa'  ? 'selected':'' }}>South Africa</option>
              <option value="Zambia"        {{ old('country')=='Zambia'        ? 'selected':'' }}>Zambia</option>
              <option value="Zimbabwe"      {{ old('country')=='Zimbabwe'      ? 'selected':'' }}>Zimbabwe</option>
            </optgroup>
            <option value="Other"           {{ old('country')=='Other'         ? 'selected':'' }}>Other</option>
          </select>
        </div>

      </div>
    </div>
  </div>

  {{-- Right column --}}
  <div>
    <div class="form-card" style="background:var(--bg)">
      <div class="section-title" style="display:flex;align-items:center;gap:6px">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        Role Description
      </div>
      <div style="font-size:12px;color:var(--muted);line-height:1.8">
        <div style="margin-bottom:10px">
          <span style="font-weight:600;color:var(--blue)">Agent</span><br>
          Can manage assigned sites, view data, enter manual readings.
        </div>
        <div style="margin-bottom:10px">
          <span style="font-weight:600;color:var(--green)">Observer</span><br>
          Read-only access to assigned sites. Cannot edit data.
        </div>
        <div>
          <span style="font-weight:600;color:var(--amber)">Admin</span><br>
          Full access to all sites, users and settings.
        </div>
      </div>
    </div>

    <div class="form-card" style="background:var(--bg)">
      <div class="section-title" style="display:flex;align-items:center;gap:6px">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
        Account Activation
      </div>
      <p style="font-size:12px;color:var(--muted);line-height:1.6">
        Setting status to <strong>Active</strong> gives immediate access.<br>
        <strong>Pending</strong> requires admin approval.<br>
        The user will log in with the email and password you set.
      </p>
    </div>

    <div style="display:flex;gap:8px">
      <a href="{{ route('admin.users') }}" class="btn" style="flex:1;justify-content:center">Cancel</a>
      <button type="submit" class="btn btn-blue" style="flex:2;justify-content:center">Create User</button>
    </div>
  </div>

</div>
</form>
@endsection
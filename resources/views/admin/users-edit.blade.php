@extends('layouts.dashboard')
@section('page-title', 'Edit User')
@section('page-crumb', 'Admin › Users')


@section('content')

@if($errors->any())
<div style="background:var(--red-bg);border:1px solid var(--red-bd);border-radius:8px;padding:12px 16px;margin-bottom:16px;color:var(--red)">
  @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
</div>
@endif

<form method="POST" action="{{ route('admin.users.update', $user) }}">
@csrf
@method('PUT')

<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px">

  {{-- Left column --}}
  <div>
    <div class="form-card">
      <div class="section-title">User Information</div>
      <div class="form-grid">

        <div class="form-field full">
          <label class="form-label" for="user-name">Full Name *</label>
          <input type="text" id="user-name" name="name" class="form-input {{ $errors->has('name') ? 'error' : '' }}"
                 value="{{ old('name', $user->name) }}" required>
        </div>

        <div class="form-field full">
          <label class="form-label" for="user-email">Email Address *</label>
          <input type="email" id="user-email" name="email" class="form-input {{ $errors->has('email') ? 'error' : '' }}"
                 value="{{ old('email', $user->email) }}" required>
        </div>

        <div class="form-field">
          <label class="form-label" for="user-password">New Password</label>
          <input type="password" id="user-password" name="password" class="form-input {{ $errors->has('password') ? 'error' : '' }}"
                 placeholder="Leave blank to keep current">
        </div>

        <div class="form-field">
          <label class="form-label" for="user-password-confirm">Confirm New Password</label>
          <input type="password" id="user-password-confirm" name="password_confirmation" class="form-input"
                 placeholder="Leave blank to keep current">
        </div>

        <div class="form-field">
          <label class="form-label" for="user-role">Role *</label>
          <select id="user-role" name="role" class="form-input {{ $errors->has('role') ? 'error' : '' }}" required>
            <option value="agent"        {{ old('role',$user->role)=='agent'        ? 'selected':'' }}>Agent — Manages sites</option>
            <option value="observateur"  {{ old('role',$user->role)=='observateur'  ? 'selected':'' }}>Observer — Read only</option>
            <option value="admin"        {{ old('role',$user->role)=='admin'        ? 'selected':'' }}>Admin — Full access</option>
          </select>
        </div>

        <div class="form-field">
          <label class="form-label" for="user-status">Status *</label>
          <select id="user-status" name="status" class="form-input" required>
            <option value="active"    {{ old('status',$user->status)=='active'    ? 'selected':'' }}>Active</option>
            <option value="pending"   {{ old('status',$user->status)=='pending'   ? 'selected':'' }}>Pending</option>
            <option value="suspended" {{ old('status',$user->status)=='suspended' ? 'selected':'' }}>Suspended</option>
          </select>
        </div>

        <div class="form-field">
          <label class="form-label" for="user-organisation">Organisation</label>
          <input type="text" id="user-organisation" name="organisation" class="form-input"
                 value="{{ old('organisation', $user->organisation) }}" placeholder="e.g. APV-MaGa Project">
        </div>

        <div class="form-field">
          <label class="form-label" for="user-country">Country</label>
          <select id="user-country" name="country" class="form-input">
            <option value="">Select country...</option>
            <optgroup label="── West Africa ──">
              <option value="Benin"         {{ old('country',$user->country)=='Benin'         ? 'selected':'' }}>Benin</option>
              <option value="Burkina Faso"  {{ old('country',$user->country)=='Burkina Faso'  ? 'selected':'' }}>Burkina Faso</option>
              <option value="Cape Verde"    {{ old('country',$user->country)=='Cape Verde'    ? 'selected':'' }}>Cape Verde</option>
              <option value="Ivory Coast"   {{ old('country',$user->country)=='Ivory Coast'   ? 'selected':'' }}>Ivory Coast</option>
              <option value="Gambia"        {{ old('country',$user->country)=='Gambia'        ? 'selected':'' }}>Gambia</option>
              <option value="Ghana"         {{ old('country',$user->country)=='Ghana'         ? 'selected':'' }}>Ghana</option>
              <option value="Guinea"        {{ old('country',$user->country)=='Guinea'        ? 'selected':'' }}>Guinea</option>
              <option value="Guinea-Bissau" {{ old('country',$user->country)=='Guinea-Bissau' ? 'selected':'' }}>Guinea-Bissau</option>
              <option value="Liberia"       {{ old('country',$user->country)=='Liberia'       ? 'selected':'' }}>Liberia</option>
              <option value="Mali"          {{ old('country',$user->country)=='Mali'          ? 'selected':'' }}>Mali</option>
              <option value="Mauritania"    {{ old('country',$user->country)=='Mauritania'    ? 'selected':'' }}>Mauritania</option>
              <option value="Niger"         {{ old('country',$user->country)=='Niger'         ? 'selected':'' }}>Niger</option>
              <option value="Nigeria"       {{ old('country',$user->country)=='Nigeria'       ? 'selected':'' }}>Nigeria</option>
              <option value="Senegal"       {{ old('country',$user->country)=='Senegal'       ? 'selected':'' }}>Senegal</option>
              <option value="Sierra Leone"  {{ old('country',$user->country)=='Sierra Leone'  ? 'selected':'' }}>Sierra Leone</option>
              <option value="Togo"          {{ old('country',$user->country)=='Togo'          ? 'selected':'' }}>Togo</option>
            </optgroup>
            <optgroup label="── Central Africa ──">
              <option value="Cameroon"      {{ old('country',$user->country)=='Cameroon'      ? 'selected':'' }}>Cameroon</option>
              <option value="Central African Republic" {{ old('country',$user->country)=='Central African Republic' ? 'selected':'' }}>Central African Republic</option>
              <option value="Chad"          {{ old('country',$user->country)=='Chad'          ? 'selected':'' }}>Chad</option>
              <option value="Congo"         {{ old('country',$user->country)=='Congo'         ? 'selected':'' }}>Congo</option>
              <option value="DRC"           {{ old('country',$user->country)=='DRC'           ? 'selected':'' }}>DR Congo</option>
              <option value="Equatorial Guinea" {{ old('country',$user->country)=='Equatorial Guinea' ? 'selected':'' }}>Equatorial Guinea</option>
              <option value="Gabon"         {{ old('country',$user->country)=='Gabon'         ? 'selected':'' }}>Gabon</option>
              <option value="Sao Tome and Principe" {{ old('country',$user->country)=='Sao Tome and Principe' ? 'selected':'' }}>São Tomé and Príncipe</option>
            </optgroup>
            <optgroup label="── East Africa ──">
              <option value="Burundi"       {{ old('country',$user->country)=='Burundi'       ? 'selected':'' }}>Burundi</option>
              <option value="Comoros"       {{ old('country',$user->country)=='Comoros'       ? 'selected':'' }}>Comoros</option>
              <option value="Djibouti"      {{ old('country',$user->country)=='Djibouti'      ? 'selected':'' }}>Djibouti</option>
              <option value="Eritrea"       {{ old('country',$user->country)=='Eritrea'       ? 'selected':'' }}>Eritrea</option>
              <option value="Ethiopia"      {{ old('country',$user->country)=='Ethiopia'      ? 'selected':'' }}>Ethiopia</option>
              <option value="Kenya"         {{ old('country',$user->country)=='Kenya'         ? 'selected':'' }}>Kenya</option>
              <option value="Madagascar"    {{ old('country',$user->country)=='Madagascar'    ? 'selected':'' }}>Madagascar</option>
              <option value="Malawi"        {{ old('country',$user->country)=='Malawi'        ? 'selected':'' }}>Malawi</option>
              <option value="Mauritius"     {{ old('country',$user->country)=='Mauritius'     ? 'selected':'' }}>Mauritius</option>
              <option value="Rwanda"        {{ old('country',$user->country)=='Rwanda'        ? 'selected':'' }}>Rwanda</option>
              <option value="Seychelles"    {{ old('country',$user->country)=='Seychelles'    ? 'selected':'' }}>Seychelles</option>
              <option value="Somalia"       {{ old('country',$user->country)=='Somalia'       ? 'selected':'' }}>Somalia</option>
              <option value="South Sudan"   {{ old('country',$user->country)=='South Sudan'   ? 'selected':'' }}>South Sudan</option>
              <option value="Tanzania"      {{ old('country',$user->country)=='Tanzania'      ? 'selected':'' }}>Tanzania</option>
              <option value="Uganda"        {{ old('country',$user->country)=='Uganda'        ? 'selected':'' }}>Uganda</option>
            </optgroup>
            <optgroup label="── North Africa ──">
              <option value="Algeria"       {{ old('country',$user->country)=='Algeria'       ? 'selected':'' }}>Algeria</option>
              <option value="Egypt"         {{ old('country',$user->country)=='Egypt'         ? 'selected':'' }}>Egypt</option>
              <option value="Libya"         {{ old('country',$user->country)=='Libya'         ? 'selected':'' }}>Libya</option>
              <option value="Morocco"       {{ old('country',$user->country)=='Morocco'       ? 'selected':'' }}>Morocco</option>
              <option value="Sudan"         {{ old('country',$user->country)=='Sudan'         ? 'selected':'' }}>Sudan</option>
              <option value="Tunisia"       {{ old('country',$user->country)=='Tunisia'       ? 'selected':'' }}>Tunisia</option>
              <option value="Western Sahara" {{ old('country',$user->country)=='Western Sahara' ? 'selected':'' }}>Western Sahara</option>
            </optgroup>
            <optgroup label="── Southern Africa ──">
              <option value="Angola"        {{ old('country',$user->country)=='Angola'        ? 'selected':'' }}>Angola</option>
              <option value="Botswana"      {{ old('country',$user->country)=='Botswana'      ? 'selected':'' }}>Botswana</option>
              <option value="Eswatini"      {{ old('country',$user->country)=='Eswatini'      ? 'selected':'' }}>Eswatini</option>
              <option value="Lesotho"       {{ old('country',$user->country)=='Lesotho'       ? 'selected':'' }}>Lesotho</option>
              <option value="Mozambique"    {{ old('country',$user->country)=='Mozambique'    ? 'selected':'' }}>Mozambique</option>
              <option value="Namibia"       {{ old('country',$user->country)=='Namibia'       ? 'selected':'' }}>Namibia</option>
              <option value="South Africa"  {{ old('country',$user->country)=='South Africa'  ? 'selected':'' }}>South Africa</option>
              <option value="Zambia"        {{ old('country',$user->country)=='Zambia'        ? 'selected':'' }}>Zambia</option>
              <option value="Zimbabwe"      {{ old('country',$user->country)=='Zimbabwe'      ? 'selected':'' }}>Zimbabwe</option>
            </optgroup>
            <option value="Other"           {{ old('country',$user->country)=='Other'         ? 'selected':'' }}>Other</option>
          </select>
        </div>

      </div>
    </div>
  </div>

  {{-- Right column --}}
  <div>
    <div class="form-card" style="background:var(--bg)">
      <div class="section-title">Role Description</div>
      <div style="font-size:12px;color:var(--muted);line-height:1.8">
        <div style="margin-bottom:10px">
          <span style="font-weight:600;color:var(--blue)">Agent</span><br>
          Can manage assigned sites, view data, enter manual readings.
        </div>
        <div style="margin-bottom:10px">
          <span style="font-weight:600;color:var(--green)">Observer</span><br>
          Read-only access to assigned sites. Can view and export data.
        </div>
        <div>
          <span style="font-weight:600;color:var(--amber)">Admin</span><br>
          Full access to all sites, users and settings.
        </div>
      </div>
    </div>

    <div class="form-card" style="background:var(--bg)">
      <div class="section-title">Site Assignments</div>
      <p style="font-size:12px;color:var(--muted);line-height:1.6">
        Assign this user to specific sites from each site's <strong>Users</strong> page in Admin Panel → Sites.
      </p>
    </div>

    <div style="display:flex;gap:8px">
      <a href="{{ route('admin.users') }}" class="btn" style="flex:1;justify-content:center">Cancel</a>
      <button type="submit" class="btn btn-blue" style="flex:2;justify-content:center">Save Changes</button>
    </div>
  </div>

</div>
</form>
@endsection

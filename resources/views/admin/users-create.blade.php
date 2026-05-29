@extends('layouts.dashboard')
@section('page-title', 'New User')
@section('page-crumb', 'Admin › Users')

@push('styles')
<style>
.form-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);padding:24px;margin-bottom:16px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.form-field{display:flex;flex-direction:column;gap:5px}
.form-field.full{grid-column:1/-1}
.form-label{font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px}
.form-input{font-family:'DM Sans',sans-serif;font-size:13px;padding:8px 12px;border:1px solid var(--border);border-radius:7px;background:var(--bg);color:var(--text);width:100%;transition:border-color .15s}
.form-input:focus{outline:none;border-color:var(--blue);background:#fff}
.form-input.error{border-color:var(--red)}
select.form-input{cursor:pointer}
.form-hint{font-size:11px;color:var(--muted);margin-top:2px}
.section-title{font-size:13px;font-weight:600;margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--border)}
</style>
@endpush

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
      <div class="section-title">👤 User Information</div>
      <div class="form-grid">

        <div class="form-field full">
          <label class="form-label">Full Name *</label>
          <input type="text" name="name" class="form-input {{ $errors->has('name') ? 'error' : '' }}"
                 value="{{ old('name') }}" placeholder="e.g. John Doe" required>
        </div>

        <div class="form-field full">
          <label class="form-label">Email Address *</label>
          <input type="email" name="email" class="form-input {{ $errors->has('email') ? 'error' : '' }}"
                 value="{{ old('email') }}" placeholder="e.g. john@example.com" required>
        </div>

        <div class="form-field">
          <label class="form-label">Password *</label>
          <input type="password" name="password" class="form-input {{ $errors->has('password') ? 'error' : '' }}"
                 placeholder="Min. 8 characters" required>
        </div>

        <div class="form-field">
          <label class="form-label">Confirm Password *</label>
          <input type="password" name="password_confirmation" class="form-input"
                 placeholder="Repeat password" required>
        </div>

        <div class="form-field">
          <label class="form-label">Role *</label>
          <select name="role" class="form-input {{ $errors->has('role') ? 'error' : '' }}" required>
            <option value="">Select role...</option>
            <option value="agent"        {{ old('role')=='agent'        ? 'selected':'' }}>Agent — Manages sites</option>
            <option value="observateur"  {{ old('role')=='observateur'  ? 'selected':'' }}>Observer — Read only</option>
            <option value="admin"        {{ old('role')=='admin'        ? 'selected':'' }}>Admin — Full access</option>
          </select>
        </div>

        <div class="form-field">
          <label class="form-label">Status *</label>
          <select name="status" class="form-input" required>
            <option value="active"    {{ old('status','active')=='active'    ? 'selected':'' }}>Active</option>
            <option value="pending"   {{ old('status')=='pending'   ? 'selected':'' }}>Pending</option>
            <option value="suspended" {{ old('status')=='suspended' ? 'selected':'' }}>Suspended</option>
          </select>
        </div>

        <div class="form-field">
          <label class="form-label">Organisation</label>
          <input type="text" name="organisation" class="form-input"
                 value="{{ old('organisation') }}" placeholder="e.g. APV-MaGa Project">
        </div>

        <div class="form-field">
          <label class="form-label">Country</label>
          <select name="country" class="form-input">
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
              <option value="Chad"          {{ old('country')=='Chad'          ? 'selected':'' }}>Chad</option>
              <option value="Congo"         {{ old('country')=='Congo'         ? 'selected':'' }}>Congo</option>
              <option value="DRC"           {{ old('country')=='DRC'           ? 'selected':'' }}>DR Congo</option>
            </optgroup>
            <optgroup label="── East Africa ──">
              <option value="Ethiopia"      {{ old('country')=='Ethiopia'      ? 'selected':'' }}>Ethiopia</option>
              <option value="Kenya"         {{ old('country')=='Kenya'         ? 'selected':'' }}>Kenya</option>
              <option value="Rwanda"        {{ old('country')=='Rwanda'        ? 'selected':'' }}>Rwanda</option>
              <option value="Tanzania"      {{ old('country')=='Tanzania'      ? 'selected':'' }}>Tanzania</option>
              <option value="Uganda"        {{ old('country')=='Uganda'        ? 'selected':'' }}>Uganda</option>
            </optgroup>
            <optgroup label="── North Africa ──">
              <option value="Algeria"       {{ old('country')=='Algeria'       ? 'selected':'' }}>Algeria</option>
              <option value="Egypt"         {{ old('country')=='Egypt'         ? 'selected':'' }}>Egypt</option>
              <option value="Morocco"       {{ old('country')=='Morocco'       ? 'selected':'' }}>Morocco</option>
              <option value="Tunisia"       {{ old('country')=='Tunisia'       ? 'selected':'' }}>Tunisia</option>
            </optgroup>
            <optgroup label="── Southern Africa ──">
              <option value="Angola"        {{ old('country')=='Angola'        ? 'selected':'' }}>Angola</option>
              <option value="Mozambique"    {{ old('country')=='Mozambique'    ? 'selected':'' }}>Mozambique</option>
              <option value="South Africa"  {{ old('country')=='South Africa'  ? 'selected':'' }}>South Africa</option>
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
      <div class="section-title">ℹ Role Description</div>
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
      <div class="section-title">📧 Account Activation</div>
      <p style="font-size:12px;color:var(--muted);line-height:1.6">
        Setting status to <strong>Active</strong> gives immediate access.<br>
        <strong>Pending</strong> requires admin approval.<br>
        The user will log in with the email and password you set.
      </p>
    </div>

    <div style="display:flex;gap:8px">
      <a href="{{ route('admin.users') }}" class="btn" style="flex:1;justify-content:center">Cancel</a>
      <button type="submit" class="btn btn-blue" style="flex:2;justify-content:center">✓ Create User</button>
    </div>
  </div>

</div>
</form>
@endsection
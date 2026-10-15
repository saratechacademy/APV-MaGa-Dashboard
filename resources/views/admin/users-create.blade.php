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
            <option value="{{ \App\Models\User::ROLE_AGENT }}" @selected(old('role') == \App\Models\User::ROLE_AGENT)>Agent — Manages sites</option>
            <option value="{{ \App\Models\User::ROLE_OBSERVER }}" @selected(old('role') == \App\Models\User::ROLE_OBSERVER)>Observer — Read only</option>
            <option value="{{ \App\Models\User::ROLE_ADMIN }}" @selected(old('role') == \App\Models\User::ROLE_ADMIN)>Admin — Full access</option>
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
          <span class="form-label">E-mail alerts</span>
          <input type="hidden" name="receives_alerts" value="0">
          <label style="display:flex;align-items:flex-start;gap:8px;font-size:13px;cursor:pointer">
            <input type="checkbox" id="user-receives-alerts" name="receives_alerts" value="1" @checked(old('receives_alerts', old('role') !== \App\Models\User::ROLE_OBSERVER))
                   style="accent-color:var(--blue);width:15px;height:15px;margin-top:2px;flex-shrink:0">
            <span>Receive offline alerts for the sites this user can access<br>
              <span style="font-size:11px;color:var(--muted)">For maintenance staff. Leave unticked for students and other readers.</span></span>
          </label>
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
              <x-country-options :selected="old('country')" />
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
@push('scripts')
<script>
// Ticked for administrators and agents, unticked for observers, until the
// admin sets it by hand.
(() => {
  const role = document.getElementById('user-role');
  const alerts = document.getElementById('user-receives-alerts');
  let touched = false;
  alerts.addEventListener('change', () => { touched = true; });
  role.addEventListener('change', () => {
    if (!touched) alerts.checked = role.value !== @json(\App\Models\User::ROLE_OBSERVER);
  });
})();
</script>
@endpush

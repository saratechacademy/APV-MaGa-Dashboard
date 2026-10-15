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
            <option value="{{ \App\Models\User::ROLE_AGENT }}" @selected(old('role', $user->role) == \App\Models\User::ROLE_AGENT)>Agent — Manages sites</option>
            <option value="{{ \App\Models\User::ROLE_OBSERVER }}" @selected(old('role', $user->role) == \App\Models\User::ROLE_OBSERVER)>Observer — Read only</option>
            <option value="{{ \App\Models\User::ROLE_ADMIN }}" @selected(old('role', $user->role) == \App\Models\User::ROLE_ADMIN)>Admin — Full access</option>
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
          <span class="form-label">E-mail alerts</span>
          <input type="hidden" name="receives_alerts" value="0">
          <label style="display:flex;align-items:flex-start;gap:8px;font-size:13px;cursor:pointer">
            <input type="checkbox" id="user-receives-alerts" name="receives_alerts" value="1" @checked(old('receives_alerts', $user->receives_alerts))
                   style="accent-color:var(--blue);width:15px;height:15px;margin-top:2px;flex-shrink:0">
            <span>Receive offline alerts for the sites this user can access<br>
              <span style="font-size:11px;color:var(--muted)">For maintenance staff. Leave unticked for students and other readers.</span></span>
          </label>
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
              <x-country-options :selected="old('country', $user->country)" />
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

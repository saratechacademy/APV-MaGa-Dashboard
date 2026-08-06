@extends('layouts.dashboard')
@section('page-title', 'My Profile')
@section('page-crumb', 'Account Settings')

@push('styles')
<style>
.section-title{font-size:14px;font-weight:600;margin-bottom:4px;padding-bottom:0;border-bottom:none}
.section-sub{font-size:12px;color:var(--muted);margin-bottom:16px}
</style>
@endpush

@section('content')

<div style="max-width:720px">

  {{-- Profile Info --}}
  <div class="form-card">
    <div class="section-title" style="display:flex;align-items:center;gap:6px">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
      Profile Information
    </div>
    <div class="section-sub">Update your name, email and contact details</div>

    @if(session('status') === 'profile-updated')
    <div style="background:var(--green-bg);border:1px solid var(--green-bd);color:var(--green);border-radius:7px;padding:8px 12px;margin-bottom:14px;font-size:12px">✓ Profile updated successfully.</div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}">
      @csrf @method('PATCH')
      <div class="form-grid">
        <div class="form-field full">
          <label class="form-label" for="profile-name">Full Name</label>
          <input type="text" id="profile-name" name="name" class="form-input" value="{{ old('name', $user->name) }}" required>
          @error('name')<span style="font-size:11px;color:var(--red)">{{ $message }}</span>@enderror
        </div>
        <div class="form-field full">
          <label class="form-label" for="profile-email">Email Address</label>
          <input type="email" id="profile-email" name="email" class="form-input" value="{{ old('email', $user->email) }}" required>
          @error('email')<span style="font-size:11px;color:var(--red)">{{ $message }}</span>@enderror
        </div>
        <div class="form-field">
          <label class="form-label" for="profile-organisation">Organisation</label>
          <input type="text" id="profile-organisation" name="organisation" class="form-input" value="{{ old('organisation', $user->organisation) }}" placeholder="e.g. UTG, IER…">
        </div>
        <div class="form-field">
          <label class="form-label" for="profile-country">Country</label>
          <select name="country" id="profile-country" class="form-input">
            <option value="">Select…</option>
            <option value="Gambia"  {{ $user->country=='Gambia'  ? 'selected':'' }}>🇬🇲 Gambia</option>
            <option value="Mali"    {{ $user->country=='Mali'    ? 'selected':'' }}>🇲🇱 Mali</option>
            <option value="Senegal" {{ $user->country=='Senegal' ? 'selected':'' }}>🇸🇳 Senegal</option>
            <option value="Other"   {{ $user->country=='Other'   ? 'selected':'' }}>Other</option>
          </select>
        </div>
        <div class="form-field full">
          <label class="form-label" for="profile-phone">Phone</label>
          <input type="tel" id="profile-phone" name="phone" class="form-input" value="{{ old('phone', $user->phone) }}" placeholder="+220 …">
        </div>
      </div>
      <div style="margin-top:16px;display:flex;align-items:center;gap:10px">
        <button type="submit" class="btn btn-blue">Save Changes</button>
        <span style="font-size:12px;color:var(--muted)">Role: <strong>{{ ucfirst($user->role) }}</strong> · Status: <span style="color:{{ $user->status==='active' ? 'var(--green)' : 'var(--amber)' }}">{{ ucfirst($user->status) }}</span></span>
      </div>
    </form>
  </div>

  {{-- Change Password --}}
  <div class="form-card">
    <div class="section-title" style="display:flex;align-items:center;gap:6px">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
      Change Password
    </div>
    <div class="section-sub">Use a strong, unique password</div>

    @if(session('status') === 'password-updated')
    <div style="background:var(--green-bg);border:1px solid var(--green-bd);color:var(--green);border-radius:7px;padding:8px 12px;margin-bottom:14px;font-size:12px">✓ Password updated successfully.</div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
      @csrf @method('PUT')
      <div class="form-grid">
        <div class="form-field full">
          <label class="form-label" for="profile-current-password">Current Password</label>
          <input type="password" id="profile-current-password" name="current_password" class="form-input" autocomplete="current-password">
          @error('current_password')<span style="font-size:11px;color:var(--red)">{{ $message }}</span>@enderror
        </div>
        <div class="form-field">
          <label class="form-label" for="profile-new-password">New Password</label>
          <input type="password" id="profile-new-password" name="password" class="form-input" autocomplete="new-password">
          @error('password')<span style="font-size:11px;color:var(--red)">{{ $message }}</span>@enderror
        </div>
        <div class="form-field">
          <label class="form-label" for="profile-password-confirm">Confirm New Password</label>
          <input type="password" id="profile-password-confirm" name="password_confirmation" class="form-input" autocomplete="new-password">
        </div>
      </div>
      <div style="margin-top:16px">
        <button type="submit" class="btn btn-blue">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
          Update Password
        </button>
      </div>
    </form>
  </div>



</div>

@endsection
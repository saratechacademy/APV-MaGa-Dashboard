@extends('layouts.dashboard')
@section('page-title', 'My Profile')
@section('page-crumb', 'Account Settings')

@push('styles')
<style>
.form-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);padding:24px;margin-bottom:16px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.form-field{display:flex;flex-direction:column;gap:5px}
.form-field.full{grid-column:1/-1}
.form-label{font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px}
.form-input{font-family:'DM Sans',sans-serif;font-size:13px;padding:9px 12px;border:1px solid var(--border);border-radius:7px;background:var(--bg);color:var(--text);width:100%;transition:border-color .15s}
.form-input:focus{outline:none;border-color:var(--blue);background:#fff}
.section-title{font-size:14px;font-weight:600;margin-bottom:4px}
.section-sub{font-size:12px;color:var(--muted);margin-bottom:16px}
</style>
@endpush

@section('content')

<div style="max-width:720px">

  {{-- Profile Info --}}
  <div class="form-card">
    <div class="section-title">👤 Profile Information</div>
    <div class="section-sub">Update your name, email and contact details</div>

    @if(session('status') === 'profile-updated')
    <div style="background:var(--green-bg);border:1px solid var(--green-bd);color:var(--green);border-radius:7px;padding:8px 12px;margin-bottom:14px;font-size:12px">✓ Profile updated successfully.</div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}">
      @csrf @method('PATCH')
      <div class="form-grid">
        <div class="form-field full">
          <label class="form-label">Full Name</label>
          <input type="text" name="name" class="form-input" value="{{ old('name', $user->name) }}" required>
          @error('name')<span style="font-size:11px;color:var(--red)">{{ $message }}</span>@enderror
        </div>
        <div class="form-field full">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-input" value="{{ old('email', $user->email) }}" required>
          @error('email')<span style="font-size:11px;color:var(--red)">{{ $message }}</span>@enderror
        </div>
        <div class="form-field">
          <label class="form-label">Organisation</label>
          <input type="text" name="organisation" class="form-input" value="{{ old('organisation', $user->organisation) }}" placeholder="e.g. UTG, IER…">
        </div>
        <div class="form-field">
          <label class="form-label">Country</label>
          <select name="country" class="form-input">
            <option value="">Select…</option>
            <option value="Gambia"  {{ $user->country=='Gambia'  ? 'selected':'' }}>🇬🇲 Gambia</option>
            <option value="Mali"    {{ $user->country=='Mali'    ? 'selected':'' }}>🇲🇱 Mali</option>
            <option value="Senegal" {{ $user->country=='Senegal' ? 'selected':'' }}>🇸🇳 Senegal</option>
            <option value="Other"   {{ $user->country=='Other'   ? 'selected':'' }}>Other</option>
          </select>
        </div>
        <div class="form-field full">
          <label class="form-label">Phone</label>
          <input type="tel" name="phone" class="form-input" value="{{ old('phone', $user->phone) }}" placeholder="+220 …">
        </div>
      </div>
      <div style="margin-top:16px;display:flex;align-items:center;gap:10px">
        <button type="submit" class="btn btn-blue">✓ Save Changes</button>
        <span style="font-size:12px;color:var(--muted)">Role: <strong>{{ ucfirst($user->role) }}</strong> · Status: <span style="color:{{ $user->status==='active' ? 'var(--green)' : 'var(--amber)' }}">{{ ucfirst($user->status) }}</span></span>
      </div>
    </form>
  </div>

  {{-- Change Password --}}
  <div class="form-card">
    <div class="section-title">🔒 Change Password</div>
    <div class="section-sub">Use a strong, unique password</div>

    @if(session('status') === 'password-updated')
    <div style="background:var(--green-bg);border:1px solid var(--green-bd);color:var(--green);border-radius:7px;padding:8px 12px;margin-bottom:14px;font-size:12px">✓ Password updated successfully.</div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
      @csrf @method('PUT')
      <div class="form-grid">
        <div class="form-field full">
          <label class="form-label">Current Password</label>
          <input type="password" name="current_password" class="form-input" autocomplete="current-password">
          @error('current_password')<span style="font-size:11px;color:var(--red)">{{ $message }}</span>@enderror
        </div>
        <div class="form-field">
          <label class="form-label">New Password</label>
          <input type="password" name="password" class="form-input" autocomplete="new-password">
          @error('password')<span style="font-size:11px;color:var(--red)">{{ $message }}</span>@enderror
        </div>
        <div class="form-field">
          <label class="form-label">Confirm New Password</label>
          <input type="password" name="password_confirmation" class="form-input" autocomplete="new-password">
        </div>
      </div>
      <div style="margin-top:16px">
        <button type="submit" class="btn btn-blue">🔒 Update Password</button>
      </div>
    </form>
  </div>



</div>

@endsection
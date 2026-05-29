@extends('layouts.dashboard')
@section('page-title', 'Admin Dashboard')
@section('page-crumb', 'Administration')

@section('content')

@if(session('success'))
<div class="alert-banner" style="background:var(--green-bg);border-color:var(--green-bd);color:var(--green);margin-bottom:16px">
  ✓ {{ session('success') }}
  <button class="ab-close" onclick="this.closest('.alert-banner').style.display='none'">×</button>
</div>
@endif

{{-- Stats --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px">
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:18px 20px;box-shadow:var(--shadow)">
    <div style="font-family:'DM Mono',monospace;font-size:28px;font-weight:500;color:#7c3aed">{{ $totalUsers }}</div>
    <div style="font-size:12px;color:var(--muted);margin-top:3px">Total Users</div>
  </div>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:18px 20px;box-shadow:var(--shadow)">
    <div style="font-family:'DM Mono',monospace;font-size:28px;font-weight:500;color:var(--amber)">{{ $pendingUsers->count() }}</div>
    <div style="font-size:12px;color:var(--muted);margin-top:3px">Pending Approval</div>
  </div>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:18px 20px;box-shadow:var(--shadow)">
    <div style="font-family:'DM Mono',monospace;font-size:28px;font-weight:500;color:var(--green)">{{ $activeUsers }}</div>
    <div style="font-size:12px;color:var(--muted);margin-top:3px">Active Users</div>
  </div>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:18px 20px;box-shadow:var(--shadow)">
    <div style="font-family:'DM Mono',monospace;font-size:28px;font-weight:500;color:var(--blue)">{{ $totalSites }}</div>
    <div style="font-size:12px;color:var(--muted);margin-top:3px">Total Sites</div>
  </div>
</div>

{{-- Quick links --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px">
  <a href="{{ route('admin.users') }}" style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:14px 16px;text-decoration:none;color:var(--text);display:flex;align-items:center;gap:10px;transition:box-shadow .15s" onmouseover="this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.boxShadow=''">
    <span style="font-size:20px">👥</span>
    <div><div style="font-weight:600;font-size:13px">Manage Users</div><div style="font-size:11px;color:var(--muted)">Approve, suspend, delete</div></div>
    <span style="margin-left:auto;color:var(--muted)">→</span>
  </a>
  <a href="{{ route('admin.sites') }}" style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:14px 16px;text-decoration:none;color:var(--text);display:flex;align-items:center;gap:10px;transition:box-shadow .15s" onmouseover="this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.boxShadow=''">
    <span style="font-size:20px">📡</span>
    <div><div style="font-weight:600;font-size:13px">Manage Sites</div><div style="font-size:11px;color:var(--muted)">Create, configure, delete</div></div>
    <span style="margin-left:auto;color:var(--muted)">→</span>
  </a>
  <a href="{{ route('dashboard') }}" style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:14px 16px;text-decoration:none;color:var(--text);display:flex;align-items:center;gap:10px;transition:box-shadow .15s" onmouseover="this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.boxShadow=''">
    <span style="font-size:20px">📊</span>
    <div><div style="font-weight:600;font-size:13px">View Dashboard</div><div style="font-size:11px;color:var(--muted)">Monitoring overview</div></div>
    <span style="margin-left:auto;color:var(--muted)">→</span>
  </a>
  <a href="{{ route('profile.edit') }}" style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:14px 16px;text-decoration:none;color:var(--text);display:flex;align-items:center;gap:10px;transition:box-shadow .15s" onmouseover="this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.boxShadow=''">
    <span style="font-size:20px">👤</span>
    <div><div style="font-weight:600;font-size:13px">My Profile</div><div style="font-size:11px;color:var(--muted)">Name, email, password</div></div>
    <span style="margin-left:auto;color:var(--muted)">→</span>
  </a>
</div>

{{-- Pending users --}}
<div class="sec-header">
  <span class="sec-title">Pending Approvals ({{ $pendingUsers->count() }})</span>
  <a href="{{ route('admin.users') }}" class="btn" style="font-size:12px;padding:4px 12px">All Users →</a>
</div>

<div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);overflow-x:auto;box-shadow:var(--shadow)">
  @if($pendingUsers->isEmpty())
  <div style="text-align:center;padding:40px;color:var(--muted)">
    <div style="font-size:28px;margin-bottom:8px">✅</div>
    <div style="font-weight:600">No pending approvals</div>
    <div style="font-size:12px;margin-top:4px">All users have been reviewed</div>
  </div>
  @else
  <table class="raw-table" style="font-size:13px;min-width:600px">
    <thead>
      <tr>
        <th>Name</th><th>Email</th><th>Organisation</th><th>Country</th><th>Registered</th><th>Action</th>
      </tr>
    </thead>
    <tbody>
      @foreach($pendingUsers as $user)
      <tr>
        <td style="font-weight:600">{{ $user->name }}</td>
        <td style="color:var(--muted)">{{ $user->email }}</td>
        <td>{{ $user->organisation ?? '—' }}</td>
        <td>{{ $user->country ?? '—' }}</td>
        <td style="color:var(--muted)">{{ $user->created_at->format('d/m/Y') }}</td>
        <td>
          <div style="display:flex;gap:6px;align-items:center">
            <form method="POST" action="{{ route('admin.users.approve', $user) }}">
              @csrf
              <button type="submit" class="btn" style="font-size:11px;padding:4px 10px;background:var(--green-bg);color:var(--green);border-color:var(--green-bd)">✓ Approve</button>
            </form>
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">
              @csrf @method('DELETE')
              <button type="submit" class="btn btn-red" style="font-size:11px;padding:4px 10px">✕ Reject</button>
            </form>
          </div>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif
</div>

@endsection
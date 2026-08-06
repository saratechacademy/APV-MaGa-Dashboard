@extends('layouts.dashboard')
@section('page-title', 'User Management')
@section('page-crumb', 'Admin')

@section('content')

@if(session('success'))
<div class="alert-banner" style="background:var(--green-bg);border-color:var(--green-bd);color:var(--green);margin-bottom:16px">
  {{ session('success') }}
  <button class="ab-close" onclick="this.closest('.alert-banner').style.display='none'" aria-label="Dismiss">×</button>
</div>
@endif

@if(session('error'))
<div class="alert-banner" style="background:var(--red-bg);border-color:var(--red-bd);color:var(--red);margin-bottom:16px">
  {{ session('error') }}
  <button class="ab-close" onclick="this.closest('.alert-banner').style.display='none'" aria-label="Dismiss">×</button>
</div>
@endif

<div class="sec-header">
  <span class="sec-title">All Users ({{ $users->count() }})</span>
 <div style="display:flex;gap:8px">
    <a href="{{ route('admin.dashboard') }}" class="btn" style="font-size:12px;padding:4px 12px">← Admin Dashboard</a>
    <a href="{{ route('admin.users.create') }}" class="btn btn-blue" style="font-size:12px;padding:4px 12px">+ Add User</a>
  </div>
</div>

<div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;box-shadow:var(--shadow)">
  <table class="raw-table" style="font-size:13px">
    <thead>
      <tr>
        <th>Name</th><th>Email</th><th>Role</th><th>Organisation</th><th>Country</th><th>Status</th><th>Sites</th><th>Registered</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse($users as $user)
      <tr>
        <td style="font-weight:600">{{ $user->name }}</td>
        <td style="color:var(--muted)">{{ $user->email }}</td>
        <td>
          <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:4px;
            background:{{ $user->role==='admin' ? 'var(--blue-bg)' : ($user->role==='agent' ? 'var(--green-bg)' : 'var(--bg)') }};
            color:{{ $user->role==='admin' ? 'var(--blue)' : ($user->role==='agent' ? 'var(--green)' : 'var(--muted)') }};
            border:1px solid {{ $user->role==='admin' ? 'var(--blue-bd)' : ($user->role==='agent' ? 'var(--green-bd)' : 'var(--border)') }}">
            {{ ucfirst($user->role) }}
          </span>
        </td>
        <td>{{ $user->organisation ?? '—' }}</td>
        <td>{{ $user->country ?? '—' }}</td>
        <td>
          <span class="badge {{ $user->status==='active' ? 'badge-ok' : ($user->status==='pending' ? 'badge-warn' : 'badge-err') }}">
            {{ ucfirst($user->status) }}
          </span>
        </td>
        <td style="font-family:'DM Mono',monospace">{{ $user->sites()->count() }}</td>
        <td style="color:var(--muted)">{{ $user->created_at->format('d/m/Y') }}</td>
        <td>
          <div style="display:flex;gap:5px;flex-wrap:wrap">
            <a href="{{ route('admin.users.edit', $user) }}" class="btn" style="font-size:11px;padding:3px 8px;background:var(--blue-bg);color:var(--blue);border-color:var(--blue-bd)">Edit</a>
            @if($user->status === 'pending')
              <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                @csrf
                <button type="submit" class="btn" style="font-size:11px;padding:3px 8px;background:var(--green-bg);color:var(--green);border-color:var(--green-bd)">Approve</button>
              </form>
            @endif
            @if($user->status === 'active' && $user->role !== 'admin')
              <form method="POST" action="{{ route('admin.users.suspend', $user) }}">
                @csrf
                <button type="submit" class="btn" style="font-size:11px;padding:3px 8px;background:var(--amber-bg);color:var(--amber);border-color:var(--amber-bd)">Suspend</button>
              </form>
            @endif
            @if($user->status === 'suspended')
              <form method="POST" action="{{ route('admin.users.activate', $user) }}">
                @csrf
                <button type="submit" class="btn" style="font-size:11px;padding:3px 8px;background:var(--green-bg);color:var(--green);border-color:var(--green-bd)">Activate</button>
              </form>
            @endif
            @if($user->role !== 'admin')
              <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                    onsubmit="return confirm('Delete user {{ $user->name }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-red" style="font-size:11px;padding:3px 8px">Delete</button>
              </form>
            @endif
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="9" style="text-align:center;padding:30px;color:var(--muted)">No users found</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

@endsection
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
  <table class="raw-table admin-table" style="font-size:13px">
    <thead>
      <tr>
        <th>Name</th><th class="col-opt">Email</th><th class="col-sm">Role</th><th class="col-low">Organisation</th><th class="col-low">Country</th><th>Status</th><th class="col-opt">Alerts</th><th class="col-opt">Sites</th><th class="col-low">Registered</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse($users as $user)
      <tr>
        <td style="font-weight:600">{{ $user->name }}</td>
        <td class="col-opt" style="color:var(--muted)">{{ $user->email }}</td>
        <td class="col-sm">
          <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:4px;
            background:{{ $user->isAdmin() ? 'var(--blue-bg)' : ($user->isAgent() ? 'var(--green-bg)' : 'var(--bg)') }};
            color:{{ $user->isAdmin() ? 'var(--blue)' : ($user->isAgent() ? 'var(--green)' : 'var(--muted)') }};
            border:1px solid {{ $user->isAdmin() ? 'var(--blue-bd)' : ($user->isAgent() ? 'var(--green-bd)' : 'var(--border)') }}">
            {{ \App\Models\User::ROLES[$user->role] ?? ucfirst($user->role) }}
          </span>
        </td>
        <td class="col-low">{{ $user->organisation ?? '—' }}</td>
        <td class="col-low">{{ $user->country ?? '—' }}</td>
        <td>
          <span class="badge {{ $user->status==='active' ? 'badge-ok' : ($user->status==='pending' ? 'badge-warn' : 'badge-err') }}">
            {{ ucfirst($user->status) }}
          </span>
        </td>
        <td class="col-opt" style="color:{{ $user->receives_alerts ? 'var(--text)' : 'var(--muted)' }}" title="Offline alerts by e-mail">{{ $user->receives_alerts ? 'Yes' : '—' }}</td>
        {{-- Sites the user can actually open: an admin sees every site; anyone
             else, the ones they own or are assigned to (each counted once). --}}
        <td class="col-opt" style="font-family:'DM Mono',monospace" title="{{ $user->isAdmin() ? 'Administrators have access to every site' : 'Sites this user owns or is assigned to' }}">
          {{ $user->isAdmin() ? 'All' : $user->sites->pluck('id')->merge($user->assignedSites->pluck('id'))->unique()->count() }}
        </td>
        <td class="col-low" style="color:var(--muted)">{{ $user->created_at->format('d/m/Y') }}</td>
        <td>
          <div style="display:flex;gap:5px;flex-wrap:wrap">
            <a href="{{ route('admin.users.edit', $user) }}" class="btn" style="font-size:11px;padding:3px 8px;background:var(--blue-bg);color:var(--blue);border-color:var(--blue-bd)">Edit</a>
            @if($user->status === 'pending')
              <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                @csrf
                <button type="submit" class="btn" style="font-size:11px;padding:3px 8px;background:var(--green-bg);color:var(--green);border-color:var(--green-bd)">Approve</button>
              </form>
            @endif
            @if($user->status === 'active' && !$user->isAdmin())
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
            @if(!$user->isAdmin())
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
      <tr><td colspan="10" style="text-align:center;padding:30px;color:var(--muted)">No users found</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

@endsection
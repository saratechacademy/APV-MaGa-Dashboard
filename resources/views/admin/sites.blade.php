@extends('layouts.dashboard')
@section('page-title', 'Sites Management')
@section('page-crumb', 'Admin')
@section('content')
@if(session('success'))
<div class="alert-banner" style="background:var(--green-bg);border-color:var(--green-bd);color:var(--green);margin-bottom:16px">
  {{ session('success') }}
  <button class="ab-close" onclick="this.closest('.alert-banner').style.display='none'" aria-label="Dismiss">×</button>
</div>
@endif
<div class="sec-header">
  <span class="sec-title">All Sites ({{ $sites->count() }})</span>
  <a href="{{ route('admin.sites.create') }}" class="btn btn-blue">+ New Site</a>
</div>
<div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;box-shadow:var(--shadow)">
  <table class="raw-table" style="font-size:13px">
    <thead>
      <tr>
        <th>Site</th><th>Country</th><th>Agent</th><th>Capacity</th><th>Area</th><th>Status</th><th>Categories</th><th>Users</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse($sites as $site)
      <tr>
        <td>
          <div style="font-weight:600">{{ $site->name }}</div>
          <div style="font-size:11px;color:var(--muted);font-family:'DM Mono',monospace">{{ $site->api_key ? substr($site->api_key,0,16).'…' : '—' }}</div>
        </td>
        <td>{{ $site->country }}</td>
        <td>
          @if($site->user)
            <div style="font-weight:500">{{ $site->user->name }}</div>
            <div style="font-size:11px;color:var(--muted)">{{ $site->user->email }}</div>
          @else
            <span style="color:var(--muted)">—</span>
          @endif
        </td>
        <td>{{ $site->capacity_kw ? $site->capacity_kw.' kWp' : '—' }}</td>
        <td>{{ $site->area_m2 ? number_format($site->area_m2).' m²' : '—' }}</td>
        <td>
          <span class="badge {{ $site->status === 'active' ? 'badge-ok' : 'badge-warn' }}">
            {{ ucfirst($site->status) }}
          </span>
        </td>
        <td>
          <a href="{{ route('admin.categories', $site) }}" class="btn" style="font-size:11px;padding:4px 10px">Categories</a>
        </td>
        <td>
          <a href="{{ route('admin.sites.users', $site) }}" class="btn" style="font-size:11px;padding:4px 10px">Users</a>
        </td>
        <td>
          <div style="display:flex;gap:6px;align-items:center">
            <a href="{{ route('dashboard.site', $site) }}" class="btn" style="font-size:11px;padding:4px 10px">View</a>
            <a href="{{ route('admin.sites.edit', $site) }}" class="btn" style="font-size:11px;padding:4px 10px">Edit</a>
            <form method="POST" action="{{ route('admin.sites.duplicate', $site) }}" onsubmit="return confirm('Duplicate site {{ $site->name }}? Categories, parameters and charts will be copied.')">
              @csrf
              <button type="submit" class="btn" style="font-size:11px;padding:4px 10px">⧉ Duplicate</button>
            </form>
            <form method="POST" action="{{ route('admin.sites.destroy', $site) }}" onsubmit="return confirm('Delete site {{ $site->name }}?')">
              @csrf @method('DELETE')
              <button type="submit" class="btn btn-red" style="font-size:11px;padding:4px 10px">Delete</button>
            </form>
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="9" style="text-align:center;padding:30px;color:var(--muted)">No sites yet — <a href="{{ route('admin.sites.create') }}" style="color:var(--blue)">create one</a></td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
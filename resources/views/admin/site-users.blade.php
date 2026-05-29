@extends('layouts.dashboard')
@section('page-title', $site->name)
@section('page-crumb', 'Admin › Sites › Users')

@push('styles')
<style>
.form-input{font-family:'DM Sans',sans-serif;font-size:13px;padding:8px 12px;border:1px solid var(--border);border-radius:7px;background:var(--bg);color:var(--text);width:100%;transition:border-color .15s}
.form-input:focus{outline:none;border-color:var(--blue);background:#fff}
select.form-input{cursor:pointer}
</style>
@endpush

@section('content')

@if(session('success'))
<div style="background:var(--green-bg);border:1px solid var(--green-bd);color:var(--green);border-radius:8px;padding:10px 16px;margin-bottom:16px;font-size:13px">
  ✓ {{ session('success') }}
  <button onclick="this.closest('div').remove()" style="float:right;background:none;border:none;cursor:pointer;color:inherit;font-size:16px">×</button>
</div>
@endif

@if(session('error'))
<div style="background:var(--red-bg);border:1px solid var(--red-bd);color:var(--red);border-radius:8px;padding:10px 16px;margin-bottom:16px;font-size:13px">
  ✗ {{ session('error') }}
  <button onclick="this.closest('div').remove()" style="float:right;background:none;border:none;cursor:pointer;color:inherit;font-size:16px">×</button>
</div>
@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start">

  {{-- Liste des utilisateurs assignés --}}
  <div>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);overflow:hidden">
      <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <div>
          <div style="font-size:14px;font-weight:600">👥 Assigned Users</div>
          <div style="font-size:11px;color:var(--muted);margin-top:2px">{{ $site->name }}</div>
        </div>
        <span style="font-size:12px;padding:3px 10px;background:var(--blue-bg);color:var(--blue);border:1px solid var(--blue-bd);border-radius:20px">
          {{ $assignedUsers->count() }} user(s)
        </span>
      </div>

      @if($assignedUsers->count() > 0)
      <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead>
          <tr style="background:var(--bg)">
            <th style="padding:8px 16px;text-align:left;font-size:11px;font-weight:600;color:var(--muted)">Name</th>
            <th style="padding:8px 12px;text-align:left;font-size:11px;font-weight:600;color:var(--muted)">Role</th>
            <th style="padding:8px 12px;text-align:center;font-size:11px;font-weight:600;color:var(--muted)">Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach($assignedUsers as $u)
          <tr style="border-top:1px solid var(--border)">
            <td style="padding:10px 16px">
              <div style="font-weight:600">{{ $u->name }}</div>
              <div style="font-size:11px;color:var(--muted)">{{ $u->email }}</div>
            </td>
            <td style="padding:10px 12px">
              <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:4px;
                background:{{ $u->pivot->role === 'agent' ? 'var(--green-bg)' : 'var(--bg)' }};
                color:{{ $u->pivot->role === 'agent' ? 'var(--green)' : 'var(--muted)' }};
                border:1px solid {{ $u->pivot->role === 'agent' ? 'var(--green-bd)' : 'var(--border)' }}">
                {{ ucfirst($u->pivot->role) }}
              </span>
            </td>
            <td style="padding:10px 12px;text-align:center">
              <form method="POST" action="{{ route('admin.sites.users.remove', [$site, $u]) }}"
                    onsubmit="return confirm('Remove {{ $u->name }} from this site?')">
                @csrf @method('DELETE')
                <button type="submit"
                  style="font-family:'DM Sans',sans-serif;font-size:11px;padding:3px 10px;border:1px solid var(--red-bd);background:var(--red-bg);color:var(--red);border-radius:5px;cursor:pointer">
                  ✕ Remove
                </button>
              </form>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
      @else
      <div style="padding:32px;text-align:center;color:var(--muted);font-size:13px">
        <div style="font-size:24px;margin-bottom:8px">👤</div>
        No users assigned to this site yet.
      </div>
      @endif
    </div>
  </div>

  {{-- Formulaire d'ajout --}}
  <div>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);padding:20px">
      <div style="font-size:14px;font-weight:600;margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid var(--border)">
        ➕ Add User to Site
      </div>

      <form method="POST" action="{{ route('admin.sites.users.add', $site) }}">
        @csrf

        <div style="margin-bottom:14px">
          <label style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px">
            User *
          </label>
          <select name="user_id" class="form-input" required>
            <option value="">Select a user...</option>
            @foreach($availableUsers as $u)
            <option value="{{ $u->id }}">{{ $u->name }} — {{ ucfirst($u->role) }}</option>
            @endforeach
          </select>
          @if($availableUsers->isEmpty())
          <div style="font-size:11px;color:var(--amber);margin-top:4px">
            ⚠ All active users are already assigned to this site.
          </div>
          @endif
        </div>

        <div style="margin-bottom:18px">
          <label style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px">
            Role on this site *
          </label>
          <select name="role" class="form-input" required>
            <option value="agent">Agent — Can enter manual data</option>
            <option value="observateur">Observer — Read only</option>
          </select>
        </div>

        <button type="submit" class="btn btn-blue" style="width:100%;justify-content:center"
          {{ $availableUsers->isEmpty() ? 'disabled' : '' }}>
          ➕ Add to Site
        </button>
      </form>
    </div>

    {{-- Info --}}
    <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--r);padding:14px 16px;margin-top:12px;font-size:12px;color:var(--muted);line-height:1.7">
      <strong style="color:var(--text)">ℹ Role on site:</strong><br>
      <strong>Agent</strong> — Views data, enters manual readings, accesses API key.<br>
      <strong>Observer</strong> — Views data and exports only. No manual input or API key.
    </div>

    <div style="margin-top:12px">
      <a href="{{ route('admin.sites') }}" class="btn" style="width:100%;justify-content:center;text-decoration:none">
        ← Back to Sites
      </a>
    </div>
  </div>

</div>
@endsection
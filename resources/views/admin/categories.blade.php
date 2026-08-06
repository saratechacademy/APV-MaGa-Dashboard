@extends('layouts.dashboard')
@section('page-title', $site->name)
@section('page-crumb', 'Admin › Sites › Categories')

@push('styles')
<style>
.cat-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);padding:16px;margin-bottom:10px}
.cat-row{display:flex;align-items:center;gap:14px}
.cat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.cat-info{flex:1;min-width:0}
.cat-name{font-size:14px;font-weight:600}
.cat-meta{font-size:11px;color:var(--muted);margin-top:2px}
.cat-actions{display:flex;gap:6px;flex-shrink:0;flex-wrap:wrap}
.toggle-on{background:var(--green-bg);color:var(--green);border-color:var(--green-bd)}
.toggle-off{background:var(--red-bg);color:var(--red);border-color:var(--red-bd)}
.edit-form{display:none;margin-top:12px;padding-top:12px;border-top:1px solid var(--border)}
</style>
@endpush

@section('content')

@if(session('success'))
<div class="alert-banner" style="background:var(--green-bg);border-color:var(--green-bd);color:var(--green);margin-bottom:16px">
  {{ session('success') }}
  <button class="ab-close" onclick="this.closest('.alert-banner').style.display='none'" aria-label="Dismiss">×</button>
</div>
@endif

{{-- Breadcrumb --}}
<div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;font-size:12px;color:var(--muted)">
  <a href="{{ route('admin.sites') }}" style="color:var(--blue);text-decoration:none">Sites</a>
  <span>›</span>
  <span style="color:var(--text);font-weight:500">{{ $site->name }}</span>
  <span>›</span>
  <span>Categories</span>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:16px">

  {{-- Categories list --}}
  <div>
    <div class="sec-header">
      <span class="sec-title">Categories ({{ $categories->count() }})</span>
    </div>

    @forelse($categories as $cat)
    <div class="cat-card" style="{{ !$cat->is_active ? 'opacity:.6' : '' }}">
      <div class="cat-row">
        <div class="cat-icon" style="background:{{ $cat->color ?? '#1d6ed8' }}20;border:1px solid {{ $cat->color ?? '#1d6ed8' }}40">
          {{ $cat->icon ?? '⊞' }}
        </div>
        <div class="cat-info">
          <div class="cat-name">{{ $cat->name }}</div>
          <div class="cat-meta">{{ $cat->parameters_count }} parameters · slug: {{ $cat->slug }}</div>
          @if($cat->description)
            <div class="cat-meta" style="margin-top:2px">{{ $cat->description }}</div>
          @endif
        </div>
        <div class="cat-actions">
          <a href="{{ route('admin.parameters', [$site, $cat]) }}" class="btn" style="font-size:11px;padding:4px 10px">
            Parameters
          </a>
          <button type="button" class="btn" style="font-size:11px;padding:4px 10px;background:var(--blue-bg);color:var(--blue);border-color:var(--blue-bd)"
            onclick="toggleEditForm('edit-{{ $cat->id }}')">
            Edit
          </button>
          <form method="POST" action="{{ route('admin.categories.toggle', [$site, $cat]) }}">
            @csrf
            <button type="submit" class="btn {{ $cat->is_active ? 'toggle-on' : 'toggle-off' }}" style="font-size:11px;padding:4px 10px">
              {{ $cat->is_active ? 'On' : 'Off' }}
            </button>
          </form>
          <form method="POST" action="{{ route('admin.categories.destroy', [$site, $cat]) }}"
                onsubmit="return confirm('Delete category {{ $cat->name }} and all its parameters?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-red" style="font-size:11px;padding:4px 10px">Delete</button>
          </form>
        </div>
      </div>

      {{-- Edit form (hidden by default) --}}
      <div class="edit-form" id="edit-{{ $cat->id }}">
        <form method="POST" action="{{ route('admin.categories.update', [$site, $cat]) }}">
          @csrf @method('PUT')
          <div style="display:grid;grid-template-columns:1fr 80px 80px;gap:10px;margin-bottom:10px">
            <div class="form-field">
              <label class="form-label" for="cat-name-{{ $cat->id }}">Name *</label>
              <input type="text" id="cat-name-{{ $cat->id }}" name="name" class="form-input" value="{{ $cat->name }}" required>
            </div>
            <div class="form-field">
              <label class="form-label" for="cat-icon-{{ $cat->id }}">Icon</label>
              <input type="text" id="cat-icon-{{ $cat->id }}" name="icon" class="form-input" value="{{ $cat->icon }}" maxlength="5">
            </div>
            <div class="form-field">
              <label class="form-label" for="cat-color-{{ $cat->id }}">Color</label>
              <input type="color" id="cat-color-{{ $cat->id }}" name="color" class="form-input" value="{{ $cat->color ?? '#1d6ed8' }}" style="padding:4px 8px;height:38px;cursor:pointer">
            </div>
          </div>
          <div class="form-field" style="margin-bottom:10px">
            <label class="form-label" for="cat-description-{{ $cat->id }}">Description</label>
            <input type="text" id="cat-description-{{ $cat->id }}" name="description" class="form-input" value="{{ $cat->description }}" placeholder="Optional">
          </div>
          <div class="form-field" style="margin-bottom:10px;max-width:220px">
            <label class="form-label" for="cat-offline-{{ $cat->id }}">Offline threshold (minutes)</label>
            <input type="number" id="cat-offline-{{ $cat->id }}" name="offline_threshold_minutes" class="form-input" min="1" step="1"
                   value="{{ $cat->offline_threshold_minutes ?? 5 }}">
            <span style="font-size:11px;color:var(--muted);margin-top:2px;display:block">
              A parameter shows "No data"/"Stale" if no sensor reading arrives within this delay.
            </span>
          </div>
          <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-blue" style="font-size:12px;padding:5px 14px">Save</button>
            <button type="button" class="btn" style="font-size:12px;padding:5px 14px" onclick="toggleEditForm('edit-{{ $cat->id }}')">Cancel</button>
          </div>
        </form>
      </div>
    </div>
    @empty
    <div style="text-align:center;padding:40px;color:var(--muted);background:var(--surface);border:1px solid var(--border);border-radius:var(--r)">
      <div style="font-weight:600;margin-bottom:4px">No categories yet</div>
      <div style="font-size:12px">Add a category using the form →</div>
    </div>
    @endforelse
  </div>

  {{-- Add category form --}}
  <div>
    <div class="form-card">
      <div style="font-size:13px;font-weight:600;margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--border)">
        + Add Category
      </div>
      <form method="POST" action="{{ route('admin.categories.store', $site) }}">
        @csrf
        <div style="display:flex;flex-direction:column;gap:10px">
          <div class="form-field">
            <label class="form-label" for="new-cat-name">Category Name *</label>
            <input type="text" id="new-cat-name" name="name" class="form-input" placeholder="e.g. Solar" required>
          </div>
          <div class="form-grid">
            <div class="form-field">
              <label class="form-label" for="new-cat-icon">Icon (emoji)</label>
              <input type="text" id="new-cat-icon" name="icon" class="form-input" placeholder="☀" maxlength="5">
            </div>
            <div class="form-field">
              <label class="form-label" for="new-cat-color">Color</label>
              <input type="color" id="new-cat-color" name="color" class="form-input" value="#1d6ed8" style="padding:4px 8px;height:38px;cursor:pointer">
            </div>
          </div>
          <div class="form-field">
            <label class="form-label" for="new-cat-description">Description</label>
            <input type="text" id="new-cat-description" name="description" class="form-input" placeholder="Optional description">
          </div>
          <div class="form-field">
            <label class="form-label" for="new-cat-offline">Offline threshold (minutes)</label>
            <input type="number" id="new-cat-offline" name="offline_threshold_minutes" class="form-input" min="1" step="1" value="5">
            <span style="font-size:11px;color:var(--muted);margin-top:2px;display:block">
              A parameter shows "No data"/"Stale" if no sensor reading arrives within this delay.
            </span>
          </div>
          <button type="submit" class="btn btn-blue" style="width:100%;justify-content:center;margin-top:4px">
            + Add Category
          </button>
        </div>
      </form>
    </div>

    {{-- Quick add defaults --}}
    <div class="form-card" style="background:var(--bg)">
      <div style="font-size:12px;font-weight:600;margin-bottom:10px;color:var(--muted)">QUICK ADD DEFAULTS</div>
      @foreach(\App\Models\SiteCategory::defaults() as $def)
        @php $exists = $categories->where('slug', $def['slug'])->count() > 0; @endphp
        @if(!$exists)
        <form method="POST" action="{{ route('admin.categories.store', $site) }}" style="margin-bottom:6px">
          @csrf
          <input type="hidden" name="name"  value="{{ $def['name'] }}">
          <input type="hidden" name="icon"  value="{{ $def['icon'] }}">
          <input type="hidden" name="color" value="{{ $def['color'] }}">
          <button type="submit" class="btn" style="width:100%;justify-content:flex-start;font-size:12px;gap:8px">
            <span>{{ $def['icon'] }}</span> Add {{ $def['name'] }}
          </button>
        </form>
        @else
        <div style="display:flex;align-items:center;gap:8px;padding:7px 12px;border-radius:7px;font-size:12px;color:var(--muted);background:var(--surface);border:1px solid var(--border);margin-bottom:6px">
          <span>{{ $def['icon'] }}</span> {{ $def['name'] }} <span style="margin-left:auto;color:var(--green)">Added</span>
        </div>
        @endif
      @endforeach
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
function toggleEditForm(id) {
  const el = document.getElementById(id);
  if (el) el.style.display = el.style.display === 'block' ? 'none' : 'block';
}
</script>
@endpush
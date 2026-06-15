<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>APV-MaGa — @yield('page-title', 'Dashboard')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --sidebar-w:224px;--topbar-h:56px;
  --sidebar-bg:#0f1929;--sidebar-hover:#16243a;--sidebar-active:rgba(34,197,94,.12);
  --bg:#f0f3f8;--surface:#fff;--border:#e4e8ef;
  --text:#0d1321;--muted:#64748b;
  --green:#15803d;--green-bg:#f0fdf4;--green-bd:#bbf7d0;
  --blue:#1d6ed8;--blue-bg:#eff6ff;--blue-bd:#bfdbfe;
  --amber:#b45309;--amber-bg:#fffbeb;--amber-bd:#fde68a;
  --red:#be123c;--red-bg:#fff1f2;--red-bd:#fecdd3;
  --r:10px;--shadow:0 1px 3px rgba(0,0,0,.06),0 1px 2px rgba(0,0,0,.04);
  --shadow-md:0 4px 12px rgba(0,0,0,.08);
}
body{font-family:'DM Sans',-apple-system,sans-serif;background:var(--bg);color:var(--text);font-size:14px;line-height:1.5;height:100vh;overflow:hidden;display:flex}

/* SIDEBAR — light, green accents */
.sidebar{width:var(--sidebar-w);min-width:var(--sidebar-w);background:var(--sidebar-bg);color:#e2e8f0;display:flex;flex-direction:column;height:100vh;overflow-y:auto;overflow-x:hidden;transition:transform .25s ease;z-index:1000}
.sb-logo{padding:16px 16px 12px;border-bottom:1px solid #1e2d45}
.sb-logo-name{font-weight:800;font-size:17px;color:#4ade80;letter-spacing:-.3px}
.sb-logo-sub{font-size:11px;color:#7c8ba1;margin-top:1px}
.sb-section{padding:14px 10px 6px}
.sb-label{font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#7c8ba1;padding:0 6px;margin-bottom:4px}
.sb-item{display:flex;align-items:center;gap:8px;padding:7px 8px;border-radius:7px;cursor:pointer;font-size:13px;color:#cbd5e1;transition:background .15s,color .15s;user-select:none;white-space:nowrap;text-decoration:none;border-left:3px solid transparent}
.sb-item:hover{background:var(--sidebar-hover);color:#f8fafc}
.sb-item.active{background:var(--sidebar-active);color:#4ade80;font-weight:600;border-left-color:#22c55e}
.sb-icon{font-size:14px;width:16px;text-align:center;flex-shrink:0}
.site-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
.d-ok{background:#4ade80}.d-warn{background:#fbbf24}.d-err{background:#f87171}
.sb-divider{border:none;border-top:1px solid #1e2d45;margin:4px 10px}
.sb-user{padding:12px 14px;border-top:1px solid #1e2d45;margin-top:auto;display:flex;align-items:center;gap:8px}
.sb-avatar{width:28px;height:28px;border-radius:50%;background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.25);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#4ade80;flex-shrink:0}
.sb-user-info{min-width:0}
.sb-user-name{font-size:12px;font-weight:600;color:#e2e8f0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sb-user-role{font-size:10px;color:#475569}

/* OVERLAY mobile */
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999}

/* MAIN */
.main{flex:1;display:flex;flex-direction:column;height:100vh;overflow:hidden;min-width:0}
.topbar{height:var(--topbar-h);background:var(--surface);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 20px;gap:8px;flex-shrink:0;overflow:hidden;min-width:0}
.tb-title{font-size:15px;font-weight:600;white-space:nowrap;flex-shrink:0}
.tb-crumb{font-size:12px;color:var(--muted);white-space:nowrap;flex-shrink:0}
.live-badge{display:flex;align-items:center;gap:5px;background:var(--green-bg);color:var(--green);border:1px solid var(--green-bd);border-radius:20px;padding:3px 10px;font-size:12px;font-weight:500}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}
.live-dot{width:6px;height:6px;border-radius:50%;background:var(--green);animation:pulse 2s infinite;flex-shrink:0}
.sync-time{font-size:12px;color:var(--muted)}
.tr-group{display:flex;background:var(--bg);border:1px solid var(--border);border-radius:7px;padding:2px;gap:1px;flex-shrink:0}
.tr-btn{font-family:'DM Sans',sans-serif;font-size:12px;font-weight:500;padding:4px 10px;border:none;background:transparent;border-radius:5px;cursor:pointer;color:var(--muted);transition:all .15s}
.tr-btn.active{background:var(--surface);color:var(--text);box-shadow:0 1px 2px rgba(0,0,0,.08)}
.tb-right{margin-left:auto;display:flex;gap:8px;align-items:center;flex-shrink:0}
.btn{font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;padding:6px 14px;border:1px solid var(--border);background:var(--surface);border-radius:7px;cursor:pointer;color:var(--text);transition:background .15s;text-decoration:none;display:inline-flex;align-items:center;gap:5px}
.btn:hover{background:var(--bg)}
.btn-blue{background:var(--blue);color:#fff;border-color:var(--blue)}
.btn-blue:hover{background:#1a5fc0}
.btn-red{background:var(--red-bg);color:var(--red);border-color:var(--red-bd)}
.btn-red:hover{background:#ffe4e6}

/* Hamburger button */
.btn-hamburger{display:none;background:none;border:none;cursor:pointer;padding:6px;color:var(--text);font-size:20px;flex-shrink:0;line-height:1}

/* CONTENT */
.content{flex:1;overflow-y:auto;padding:20px}
.alert-banner{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:var(--r);font-size:13px;margin-bottom:16px;border:1px solid}
.alert-banner.warn{background:var(--amber-bg);border-color:var(--amber-bd);color:var(--amber)}
.ab-close{margin-left:auto;cursor:pointer;opacity:.5;font-size:18px;line-height:1;background:none;border:none;color:inherit}
.ab-close:hover{opacity:1}
.sec-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
.sec-title{font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.6px}

/* CARDS */
.cards-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px}
.site-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);padding:16px;cursor:pointer;transition:box-shadow .2s,transform .15s;position:relative;overflow:hidden;text-decoration:none;display:block;color:inherit}
.site-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--green)}
.site-card.warn-card::before{background:var(--amber)}
.site-card.err-card::before{background:var(--red)}
.site-card:hover{box-shadow:var(--shadow-md);transform:translateY(-1px)}
.card-head{display:flex;align-items:center;gap:8px;margin-bottom:14px}
.card-name{font-size:15px;font-weight:600}
.country-chip{font-size:10px;font-weight:600;padding:2px 7px;border-radius:4px;background:var(--bg);color:var(--muted);border:1px solid var(--border)}
.badge{display:inline-flex;align-items:center;gap:3px;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;border:1px solid}
.badge-ok{background:var(--green-bg);color:var(--green);border-color:var(--green-bd)}
.badge-warn{background:var(--amber-bg);color:var(--amber);border-color:var(--amber-bd)}
.badge-err{background:var(--red-bg);color:var(--red);border-color:var(--red-bd)}
.kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:12px}
.kpi{padding:8px 6px;background:var(--bg);border-radius:7px;text-align:center}
.kpi-val{font-family:'DM Mono',monospace;font-size:17px;font-weight:500;line-height:1.1}
.kpi-val.warn{color:var(--amber)}
.kpi-lbl{font-size:10px;color:var(--muted);margin-top:2px;white-space:nowrap}
.sparkline-wrap{height:52px;margin-bottom:10px}
.statuses{display:flex;gap:6px;flex-wrap:wrap}

/* CHARTS */
.charts-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px}
.chart-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);padding:16px}
.cc-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
.cc-title{font-size:13px;font-weight:600}
.cc-sub{font-size:11px;color:var(--muted)}
.chart-legend{display:flex;gap:12px;flex-wrap:wrap;margin-top:8px}
.legend-item{display:flex;align-items:center;gap:5px;font-size:11px;color:var(--muted)}
.legend-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.dl-btn{font-family:'DM Sans',sans-serif;font-size:11px;font-weight:500;padding:3px 10px;border:1px solid var(--border);background:var(--surface);border-radius:5px;cursor:pointer;color:var(--muted);transition:all .15s}
.dl-btn:hover{background:var(--bg);color:var(--text)}

/* RAW TABLE */
.raw-table{width:100%;border-collapse:collapse;font-size:12px}
.raw-table th{background:var(--bg);border:1px solid var(--border);padding:7px 10px;text-align:left;font-weight:600;font-size:11px;color:var(--muted);white-space:nowrap}
.raw-table td{border:1px solid var(--border);padding:6px 10px;font-family:'DM Mono',monospace;font-size:11.5px}
.raw-table tr:hover td{background:#f8fafc}

/* SCROLLBAR */
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:3px}

/* ═══════════════════════════════════════
   RESPONSIVE — TABLET (≤900px)
═══════════════════════════════════════ */
@media (max-width: 900px) {
  .charts-row{grid-template-columns:1fr}
  .cards-grid{grid-template-columns:1fr}
  .kpis{grid-template-columns:repeat(2,1fr)}
  .sync-time{display:none}
  .tb-crumb{display:none}
}

/* ═══════════════════════════════════════
   RESPONSIVE — MOBILE (≤640px)
═══════════════════════════════════════ */
@media (max-width: 640px) {
  /* Sidebar off-canvas */
  body{overflow:hidden}
  .sidebar{
    position:fixed;
    left:0;top:0;bottom:0;
    transform:translateX(-100%);
    z-index:1000;
    width:260px;
    min-width:260px;
  }
  .sidebar.open{transform:translateX(0)}
  .sidebar-overlay.open{display:block}

  /* Hamburger visible */
  .btn-hamburger{display:flex}

  /* Topbar compact */
  .topbar{padding:0 12px;gap:6px;height:52px}
  .tb-title{font-size:14px}
  .live-badge{padding:3px 8px;font-size:11px}
  .tr-group{display:none}
  .tb-right .btn:first-child{display:none} /* hide CSV on mobile */
  .tb-right .btn-blue{font-size:12px;padding:5px 10px}

  /* Content */
  .content{padding:12px}

  /* Cards */
  .cards-grid{grid-template-columns:1fr;gap:12px}
  .kpis{grid-template-columns:repeat(2,1fr);gap:6px}
  .kpi-val{font-size:15px}
  .charts-row{grid-template-columns:1fr;gap:12px}

  /* Tables scroll */
  .raw-table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}

  /* Site card compact */
  .site-card{padding:12px}
  .card-name{font-size:14px}
  .sparkline-wrap{height:40px}

  /* Chart height reduced */
  .chart-card canvas{max-height:180px}

  /* Sec header */
  .sec-header{flex-wrap:wrap;gap:6px}

  /* Admin grid responsive */
  .ag-row{grid-template-columns:1fr !important}

  /* Tabs scroll */
  .detail-tabs-bar, [style*="overflow-x:auto"]{
    -webkit-overflow-scrolling:touch;
  }
}

/* ═══════════════════════════════════════
   RESPONSIVE ADMIN & GLOBAL GRIDS
═══════════════════════════════════════ */
@media (max-width: 900px) {
  [style*="grid-template-columns:repeat(4,1fr)"],
  [style*="grid-template-columns: repeat(4, 1fr)"] {
    grid-template-columns: repeat(2, 1fr) !important;
  }
  [style*="grid-template-columns:repeat(3,1fr)"],
  [style*="grid-template-columns: repeat(3, 1fr)"] {
    grid-template-columns: repeat(2, 1fr) !important;
  }
}
@media (max-width: 640px) {
  [style*="grid-template-columns:repeat(4,1fr)"],
  [style*="grid-template-columns: repeat(4, 1fr)"],
  [style*="grid-template-columns:repeat(3,1fr)"],
  [style*="grid-template-columns: repeat(3, 1fr)"],
  [style*="grid-template-columns:repeat("] {
    grid-template-columns: 1fr !important;
  }
  .charts-2, .charts-3 { grid-template-columns: 1fr !important; }
  .sec-header { flex-direction: column; align-items: flex-start !important; gap: 8px; }
  .content [style*="overflow:hidden"] { overflow-x: auto !important; }
  [style*="display:flex"][style*="gap:24px"],
  [style*="display:flex"][style*="gap: 24px"] { flex-direction: column !important; }
}

/* ═══════════════════════════════════════
   RESPONSIVE ADMIN TABLES & GRIDS
═══════════════════════════════════════ */

/* Admin stat grids */
.admin-stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-bottom: 20px; }
.admin-links { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 20px; }

/* Table wrapper */
.table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: var(--r); }
.table-wrap table { min-width: 600px; }

@media (max-width: 900px) {
  .admin-stats { grid-template-columns: repeat(2,1fr); }
  .admin-links { grid-template-columns: repeat(2,1fr); }
}

@media (max-width: 640px) {
  .admin-stats { grid-template-columns: repeat(2,1fr); gap: 10px; }
  .admin-links { grid-template-columns: 1fr; gap: 8px; }

  /* Force all inline grids to stack */
  .content > div[style*="grid-template-columns:repeat(4"],
  .content > div[style*="grid-template-columns: repeat(4"] {
    grid-template-columns: repeat(2, 1fr) !important;
  }
  .content > div[style*="grid-template-columns:repeat(3"],
  .content > div[style*="grid-template-columns: repeat(3"] {
    grid-template-columns: 1fr !important;
  }

  /* All table containers scroll */
  .content > div[style*="overflow:hidden"] {
    overflow-x: auto !important;
  }
  .content table {
    min-width: 550px;
  }

  /* Sec header stack */
  .sec-header {
    flex-direction: column !important;
    align-items: flex-start !important;
    gap: 8px !important;
  }
  .sec-header > div {
    flex-wrap: wrap;
  }

  /* Help responsive */
  .help-layout {
    flex-direction: column !important;
  }
  .help-nav {
    width: 100% !important;
    position: relative !important;
    top: 0 !important;
  }

  /* Forms */
  .content form .ag-row {
    grid-template-columns: 1fr !important;
  }

  /* Topbar hide crumb */
  .tb-crumb { display: none; }
  .sync-time { display: none; }
}
</style>

@stack('styles')

<style>
.detail-tabs-bar .d-tab {
  border-bottom: 2px solid transparent !important;
  margin-bottom: -2px !important;
}
.detail-tabs-bar .d-tab.active {
  color: #1d6ed8 !important;
  border-bottom: 2px solid #1d6ed8 !important;
}
</style>

</head>
<body>

<!-- OVERLAY mobile -->
<div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sb-logo" style="display:flex;align-items:center;justify-content:space-between">
    <div>
      <div class="sb-logo-name">APV-MaGa</div>
      <div class="sb-logo-sub">Agrivoltaic Monitoring Platform</div>
    </div>
    <button onclick="closeSidebar()" style="display:none;background:none;border:none;color:#475569;font-size:18px;cursor:pointer;padding:4px" id="btn-close-sidebar">✕</button>
  </div>

  @php
    $currentSite = request()->route('site');
    $streamSite  = $currentSite ?? ($sites ?? collect())->first();
  @endphp

  <div class="sb-section">
    <div class="sb-label">Sites</div>
    <a href="{{ route('dashboard') }}" onclick="closeSidebar()"
       class="sb-item {{ request()->routeIs('dashboard') && !request()->route('site') ? 'active' : '' }}">
      <span class="sb-icon">⊞</span> All Sites
    </a>
    @foreach($sites ?? [] as $s)
    <a href="{{ route('dashboard.site', $s) }}" onclick="closeSidebar()"
       class="sb-item {{ request()->route('site')?->id == $s->id ? 'active' : '' }}">
      <span class="site-dot {{ $s->status === 'active' ? 'd-ok' : 'd-warn' }}"></span>
      {{ $s->name }} — {{ strtoupper(substr($s->country, 0, 3)) }}
    </a>
    @endforeach
  </div>

  <div class="sb-section">
    @if(auth()->user()->isAdmin())
    <a href="{{ route('admin.dashboard') }}" onclick="closeSidebar()" class="sb-item {{ request()->is('admin*') ? 'active' : '' }}">
      <span class="sb-icon">⚙</span> Admin Panel
    </a>
    @endif
    <a href="{{ route('help') }}" onclick="closeSidebar()" class="sb-item {{ request()->routeIs('help') ? 'active' : '' }}">
      <span class="sb-icon">📖</span> Help
    </a>
    @if(auth()->user()->isAdmin())
    <a href="{{ route('docs') }}" onclick="closeSidebar()" class="sb-item {{ request()->routeIs('docs') ? 'active' : '' }}">
      <span class="sb-icon">🧩</span> Docs
    </a>
    @endif
  </div>

  <div class="sb-user" style="flex-direction:column;align-items:stretch;padding:0;margin-top:auto">
    <div style="display:flex;align-items:center;gap:8px;padding:12px 14px">
      <div class="sb-avatar">{{ substr(auth()->user()->name, 0, 1) }}</div>
      <div class="sb-user-info">
        <div class="sb-user-name">{{ auth()->user()->name }}</div>
        <div class="sb-user-role">{{ ucfirst(auth()->user()->role) }}</div>
      </div>
      <form method="POST" action="{{ route('logout') }}" style="margin-left:auto">
        @csrf
        <button type="submit" style="background:none;border:none;padding:4px;cursor:pointer;color:#475569;font-size:14px" title="Logout">⏻</button>
      </form>
    </div>
    <div style="padding:8px 14px 10px;font-size:10px;color:#7c8ba1;text-align:center;line-height:1.7;border-top:1px solid #1e2d45">
      © 2026 APV-MaGa · Developed by <span style="color:#4ade80;font-weight:600">Saratech</span><br>All rights reserved.
    </div>
  </div>
</aside>

<!-- MAIN -->
<div class="main">
  <!-- TOPBAR -->
  <div class="topbar">
    <!-- Hamburger mobile -->
    <button class="btn-hamburger" onclick="openSidebar()" aria-label="Menu">☰</button>

    <span class="tb-title" id="tb-title">@yield('page-title', 'All Sites')</span>
    <span class="tb-crumb" id="tb-crumb">@yield('page-crumb', 'Overview')</span>
    <div class="live-badge"><span class="live-dot"></span> Live</div>
    <span class="sync-time" id="sync-time">--:--:--</span>
    <div class="tr-group">
      <button class="tr-btn active" data-range="1h" onclick="setRange('1h',this)">1H</button>
      <button class="tr-btn" data-range="6h" onclick="setRange('6h',this)">6H</button>
      <button class="tr-btn" data-range="24h" onclick="setRange('24h',this)">24H</button>
      <button class="tr-btn" data-range="7d" onclick="setRange('7d',this)">7D</button>
    </div>
    <div class="tb-right">
      <button class="btn" onclick="exportData('csv')">⬇ CSV</button>
      <button class="btn btn-blue" onclick="exportData('excel')">⬇ Excel</button>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="content">
    @yield('content')
  </div>
</div>

<script>
// Sidebar mobile
function openSidebar() {
  document.getElementById('sidebar').classList.add('open');
  document.getElementById('sidebar-overlay').classList.add('open');
  document.getElementById('btn-close-sidebar').style.display = 'block';
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.remove('open');
  document.getElementById('btn-close-sidebar').style.display = 'none';
}

// Clock
function updateClock(){
  const now = new Date();
  document.getElementById('sync-time').textContent = now.toTimeString().slice(0,8) + ' UTC';
}
setInterval(updateClock, 1000);
updateClock();

// Time range
let currentRange = '1h';
function setRange(r, btn) {
  currentRange = r;
  document.querySelectorAll('.tr-btn').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  if (typeof onRangeChange === 'function') onRangeChange(r);
}

function exportData(format) {
  const hours = currentRange === '7d' ? 168 : currentRange === '24h' ? 24 : currentRange === '6h' ? 6 : 1;
  const siteSlug = (typeof SITE_SLUG !== 'undefined') ? SITE_SLUG : null;
  if (siteSlug) {
    window.location.href = format === 'csv'
      ? `/export/${siteSlug}/all/csv?hours=${hours}`
      : `/export/${siteSlug}/all/excel?hours=${hours}`;
  } else {
    window.location.href = format === 'csv'
      ? `/export/all-sites/csv?hours=${hours}`
      : `/export/all-sites/excel?hours=${hours}`;
  }
}
</script>
@stack('scripts')
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>APV-MaGa — @yield('page-title', 'Dashboard')</title>
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="alternate icon" href="{{ asset('favicon.ico') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"
        integrity="sha384-e6nUZLBkQ86NJ6TVVKAeSaK8jWa3NhkYWZFomE39AvDbQWeie9PlQqM3pmYW5d1g"
        crossorigin="anonymous"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --sidebar-w:224px;--topbar-h:56px;
  --sidebar-bg:#0f1929;--sidebar-hover:#16243a;--sidebar-active:rgba(34,197,94,.12);
  --bg:#f0f3f8;--surface:#fff;--border:#e4e8ef;
  --text:#0d1321;--muted:#5b6b84;
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
.sb-icon{font-size:14px;width:16px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0}
.site-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
.d-ok{background:#4ade80}.d-warn{background:#fbbf24}.d-err{background:#f87171}
.site-avatar{position:relative;width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0}
.site-avatar .status-dot{position:absolute;bottom:-1px;right:-1px;width:7px;height:7px;border-radius:50%;border:2px solid var(--sidebar-bg)}
.site-name-col{display:flex;flex-direction:column;min-width:0;flex:1;line-height:1.25}
.site-name-col .site-name{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.site-country-badge{margin-left:auto;font-size:9px;font-weight:700;letter-spacing:.3px;color:#7c8ba1;background:rgba(255,255,255,.06);padding:2px 6px;border-radius:8px;flex-shrink:0}
.sb-item.active .site-country-badge{color:#4ade80;background:rgba(34,197,94,.12)}
.sb-divider{border:none;border-top:1px solid #1e2d45;margin:4px 10px}
.sb-user{padding:12px 14px;border-top:1px solid #1e2d45;margin-top:auto;display:flex;align-items:center;gap:8px}
.sb-avatar{width:28px;height:28px;border-radius:50%;background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.25);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#4ade80;flex-shrink:0}
.sb-user-info{min-width:0}
.sb-user-name{font-size:12px;font-weight:600;color:#e2e8f0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sb-user-role{font-size:10px;color:#7c8ba1}

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
.range-chip{display:none;align-items:center;gap:6px;background:var(--blue-bg);color:var(--blue);border:1px solid var(--blue-bd);border-radius:20px;padding:3px 6px 3px 10px;font-size:12px;font-weight:500;flex-shrink:0;white-space:nowrap}
.range-chip.visible{display:inline-flex}
.range-chip button{background:none;border:none;cursor:pointer;color:var(--blue);font-size:14px;line-height:1;padding:2px 4px;border-radius:50%;display:flex;align-items:center;justify-content:center}
.range-chip button:hover{background:rgba(29,110,216,.15)}
.tb-right{margin-left:auto;display:flex;gap:8px;align-items:center;flex-shrink:0}
.btn{font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;padding:6px 14px;border:1px solid var(--border);background:var(--surface);border-radius:7px;cursor:pointer;color:var(--text);transition:background .15s;text-decoration:none;display:inline-flex;align-items:center;gap:5px}
.btn:hover{background:var(--bg)}
.btn-blue{background:var(--blue);color:#fff;border-color:var(--blue)}
.btn-blue:hover{background:#1a5fc0}
.btn-red{background:var(--red-bg);color:var(--red);border-color:var(--red-bd)}
.btn-red:hover{background:#ffe4e6}

/* Shared admin form styling — used by every admin/*.blade.php create/edit page
   instead of each view repeating its own near-identical copy. */
.form-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);padding:20px;margin-bottom:16px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.form-field{display:flex;flex-direction:column;gap:4px}
.form-field.full{grid-column:1/-1}
.form-label{font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px}
.form-input{font-family:'DM Sans',sans-serif;font-size:13px;padding:8px 12px;border:1px solid var(--border);border-radius:7px;background:var(--bg);color:var(--text);width:100%;transition:border-color .15s}
.form-input:focus{outline:none;border-color:var(--blue);background:#fff}
.form-input.error{border-color:var(--red)}
select.form-input{cursor:pointer}
textarea.form-input{resize:vertical;min-height:70px}
.form-hint{font-size:11px;color:var(--muted);margin-top:2px}
.section-title{font-size:13px;font-weight:600;margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--border)}
.checkbox-row{display:flex;align-items:center;gap:8px;padding:12px 14px;background:var(--bg);border:1px solid var(--border);border-radius:8px}
.checkbox-row input[type=checkbox]{accent-color:var(--blue);width:16px;height:16px;cursor:pointer}
.checkbox-row label{font-size:13px;font-weight:500;cursor:pointer}
.checkbox-row .hint{font-size:11px;color:var(--muted);margin-top:1px}

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
  .range-chip{display:none !important}
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
/* Tables that overflow their card (inline overflow:hidden) get a horizontal
   scrollbar instead of being silently clipped — needed from tablet widths
   up, not just phones, so this stays outside the max-width:640px block. */
.content [style*="overflow:hidden"] { overflow-x: auto; }

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
    <button onclick="closeSidebar()" style="display:none;background:none;border:none;color:#475569;font-size:18px;cursor:pointer;padding:4px" id="btn-close-sidebar" aria-label="Close menu" title="Close menu">✕</button>
  </div>

  @php
    $currentSite = request()->route('site');
    $streamSite  = $currentSite ?? ($sites ?? collect())->first();
    $iconSettings = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>';
    $iconHelp     = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';
    $iconDocs     = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="7 5 2 12 7 19"></polyline><line x1="14" y1="4" x2="10" y2="20"></line><polyline points="17 5 22 12 17 19"></polyline></svg>';
  @endphp

  <div class="sb-section">
    <div class="sb-label">Sites</div>
    <a href="{{ route('dashboard') }}" onclick="closeSidebar()"
       class="sb-item {{ request()->routeIs('dashboard') && !request()->route('site') ? 'active' : '' }}">
      <span class="sb-icon">⊞</span> All Sites
    </a>
    @php $sitePalette = \App\Models\SiteParameterGroup::palette(); @endphp
    @foreach($sites ?? [] as $s)
    <a href="{{ route('dashboard.site', $s) }}" onclick="closeSidebar()"
       class="sb-item {{ request()->route('site')?->id == $s->id ? 'active' : '' }}">
      <span class="site-avatar" style="background:{{ $sitePalette[$s->id % count($sitePalette)] }}">
        {{ strtoupper(substr($s->name, 0, 1)) }}
        <span class="status-dot {{ $s->status === 'active' ? 'd-ok' : 'd-warn' }}"></span>
      </span>
      <span class="site-name-col">
        <span class="site-name">{{ $s->name }}</span>
      </span>
      <span class="site-country-badge">{{ strtoupper(substr($s->country, 0, 3)) }}</span>
    </a>
    @endforeach
  </div>

  <div class="sb-section">
    @if(auth()->user()->isAdmin())
    <a href="{{ route('admin.dashboard') }}" onclick="closeSidebar()" class="sb-item {{ request()->is('admin*') ? 'active' : '' }}">
      <span class="sb-icon">{!! $iconSettings !!}</span> Admin Panel
    </a>
    @endif
    <a href="{{ route('help') }}" onclick="closeSidebar()" class="sb-item {{ request()->routeIs('help') ? 'active' : '' }}">
      <span class="sb-icon">{!! $iconHelp !!}</span> Help
    </a>
    @if(auth()->user()->isAdmin())
    <a href="{{ route('docs') }}" onclick="closeSidebar()" class="sb-item {{ request()->routeIs('docs') ? 'active' : '' }}">
      <span class="sb-icon">{!! $iconDocs !!}</span> Docs
    </a>
    @endif
  </div>

  <div class="sb-user" style="flex-direction:column;align-items:stretch;padding:0;margin-top:auto">
    <div style="display:flex;align-items:center;gap:8px;padding:12px 14px">
      <div class="sb-avatar" title="{{ auth()->user()->name }} — {{ auth()->user()->email }}">{{ substr(auth()->user()->name, 0, 1) }}</div>
      <div class="sb-user-info" title="{{ auth()->user()->name }} — {{ auth()->user()->email }}">
        <div class="sb-user-name">{{ auth()->user()->name }}</div>
        <div class="sb-user-role">{{ ucfirst(auth()->user()->role) }}</div>
      </div>
      <form method="POST" action="{{ route('logout') }}" style="margin-left:auto">
        @csrf
        <button type="submit" style="background:none;border:none;padding:4px;cursor:pointer;color:#475569;font-size:14px" title="Logout">⏻</button>
      </form>
    </div>
    <div style="padding:6px 14px 8px;font-size:9px;color:#475569;text-align:center;border-top:1px solid #1e2d45">
      © 2026 APV-MaGa · <span style="color:#64748b">Saratech</span>
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
    @hasSection('show-toolbar')
    <div class="live-badge"><span class="live-dot"></span> Live</div>
    <span class="sync-time" id="sync-time">--:--:--</span>
    <div class="tr-group" style="position:relative">
      <button class="tr-btn active" data-range="1h" onclick="setRange('1h',this)">1H</button>
      <button class="tr-btn" data-range="6h" onclick="setRange('6h',this)">6H</button>
      <button class="tr-btn" data-range="24h" onclick="setRange('24h',this)">24H</button>
      <button class="tr-btn" data-range="7d" onclick="setRange('7d',this)">7D</button>
      <button class="tr-btn" id="custom-range-btn" onclick="toggleCustomRange(event)">Custom</button>
      <div id="custom-range-popover" style="display:none;position:fixed;background:var(--surface);border:1px solid var(--border);border-radius:8px;box-shadow:var(--shadow-md);padding:12px;z-index:1100;min-width:200px">
        <div style="display:flex;flex-direction:column;gap:6px">
          <label for="custom-range-from" style="font-size:10px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">From</label>
          <input type="date" id="custom-range-from" style="font-family:'DM Sans',sans-serif;font-size:13px;padding:6px 8px;border:1px solid var(--border);border-radius:6px;color:var(--text);background:var(--bg)">
          <label for="custom-range-to" style="font-size:10px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--muted);margin-top:2px">To</label>
          <input type="date" id="custom-range-to" style="font-family:'DM Sans',sans-serif;font-size:13px;padding:6px 8px;border:1px solid var(--border);border-radius:6px;color:var(--text);background:var(--bg)">
          <button type="button" class="btn btn-blue" style="margin-top:6px;justify-content:center;font-size:12px;padding:6px" onclick="applyCustomRange()">Apply</button>
        </div>
      </div>
    </div>
    <div id="active-range-chip" class="range-chip">
      <span id="active-range-chip-text"></span>
      <button type="button" onclick="clearCustomRange()" aria-label="Clear custom range" title="Clear custom range">✕</button>
    </div>
    <div class="tb-right">
      <button class="btn" onclick="exportData('csv')">⬇ CSV</button>
      <button class="btn btn-blue" onclick="exportData('excel')">⬇ Excel</button>
    </div>
    @endif
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
  const el = document.getElementById('sync-time');
  if (!el) return;
  const now = new Date();
  el.textContent = now.toTimeString().slice(0,8) + ' UTC';
}
setInterval(updateClock, 1000);
updateClock();

// Time range
let currentRange = '1h';
let customFrom = null, customTo = null;

function setRange(r, btn) {
  currentRange = r;
  customFrom = null; customTo = null;
  document.querySelectorAll('.tr-btn').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  document.getElementById('active-range-chip')?.classList.remove('visible');
  if (typeof onRangeChange === 'function') onRangeChange(r);
}

function toggleCustomRange(e) {
  e.stopPropagation();
  const pop = document.getElementById('custom-range-popover');
  const btn = document.getElementById('custom-range-btn');
  if (!pop || !btn) return;
  if (pop.style.display === 'block') {
    pop.style.display = 'none';
    return;
  }
  const rect = btn.getBoundingClientRect();
  pop.style.display = 'block';
  const popWidth = pop.offsetWidth || 200;
  let left = rect.right - popWidth;
  if (left < 8) left = 8;
  pop.style.top = (rect.bottom + 6) + 'px';
  pop.style.left = left + 'px';
}

document.addEventListener('click', (e) => {
  const pop = document.getElementById('custom-range-popover');
  const btn = document.getElementById('custom-range-btn');
  if (pop && pop.style.display === 'block' && !pop.contains(e.target) && e.target !== btn) {
    pop.style.display = 'none';
  }
});

function applyCustomRange() {
  const from = document.getElementById('custom-range-from')?.value;
  const to   = document.getElementById('custom-range-to')?.value;
  if (!from || !to) { alert('Please pick both a start and end date.'); return; }
  if (from > to) { alert('The start date must be before the end date.'); return; }

  currentRange = 'custom';
  customFrom = from; customTo = to;
  document.querySelectorAll('.tr-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('custom-range-btn')?.classList.add('active');
  document.getElementById('custom-range-popover').style.display = 'none';

  const chipText = document.getElementById('active-range-chip-text');
  if (chipText) {
    const fmt = d => new Date(d + 'T00:00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    chipText.textContent = `${fmt(from)} → ${fmt(to)}`;
  }
  document.getElementById('active-range-chip')?.classList.add('visible');

  if (typeof onRangeChange === 'function') onRangeChange('custom', from, to);
}

function clearCustomRange() {
  const fromInput = document.getElementById('custom-range-from');
  const toInput   = document.getElementById('custom-range-to');
  if (fromInput) fromInput.value = '';
  if (toInput)   toInput.value = '';
  const defaultBtn = document.querySelector('.tr-btn[data-range]');
  setRange(defaultBtn?.dataset.range || '1h', defaultBtn);
}

function exportData(format) {
  const siteSlug = (typeof SITE_SLUG !== 'undefined') ? SITE_SLUG : null;
  const query = (currentRange === 'custom' && customFrom && customTo)
    ? `from=${customFrom}&to=${customTo}`
    : `hours=${currentRange === '7d' ? 168 : currentRange === '24h' ? 24 : currentRange === '6h' ? 6 : 1}`;

  if (siteSlug) {
    window.location.href = format === 'csv'
      ? `/export/${siteSlug}/all/csv?${query}`
      : `/export/${siteSlug}/all/excel?${query}`;
  } else {
    window.location.href = format === 'csv'
      ? `/export/all-sites/csv?${query}`
      : `/export/all-sites/excel?${query}`;
  }
}
</script>
@stack('scripts')
</body>
</html>
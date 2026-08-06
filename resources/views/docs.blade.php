@extends('layouts.dashboard')
@section('page-title', 'Technical Documentation')
@section('page-crumb', 'Developer Guide')

@push('styles')
<style>
.help-nav{display:flex;flex-direction:column;gap:2px}
.help-nav-item{display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:7px;cursor:pointer;font-size:13px;color:var(--muted);transition:all .15s;border:none;background:none;text-align:left;width:100%}
.help-nav-item:hover{background:var(--bg);color:var(--text)}
.help-nav-item.active{background:var(--blue-bg);color:var(--blue);font-weight:600}
.help-nav-item .nav-num{width:22px;height:22px;border-radius:50%;background:var(--bg);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0}
.help-nav-item.active .nav-num{background:var(--blue);color:#fff}

.help-section{display:none}.help-section.active{display:block}
.help-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);padding:24px;margin-bottom:16px}
.help-h1{font-size:22px;font-weight:700;color:var(--text);margin-bottom:6px;display:flex;align-items:center;gap:10px}
.help-h2{font-size:15px;font-weight:600;color:var(--text);margin:20px 0 8px;display:flex;align-items:center;gap:8px}
.help-h2::before{content:'';width:3px;height:16px;background:var(--blue);border-radius:2px;display:inline-block}
.help-h3{font-size:13px;font-weight:600;color:var(--muted);margin:14px 0 6px;text-transform:uppercase;letter-spacing:.4px}
.help-p{font-size:13px;color:var(--text);line-height:1.7;margin-bottom:10px}
.help-code{font-family:'DM Mono',monospace;font-size:12px;background:#0f1929;color:#93c5fd;padding:12px 16px;border-radius:8px;margin:8px 0;overflow-x:auto;white-space:pre}
.help-code-inline{font-family:'DM Mono',monospace;font-size:11px;background:var(--bg);color:var(--blue);padding:2px 6px;border-radius:4px;border:1px solid var(--border)}
.help-table{width:100%;border-collapse:collapse;font-size:12px;margin:10px 0}
.help-table th{background:var(--blue);color:#fff;padding:8px 12px;text-align:left;font-weight:600;font-size:11px}
.help-table td{border:1px solid var(--border);padding:8px 12px}
.help-table tr:nth-child(even) td{background:var(--bg)}
.help-steps{counter-reset:steps;list-style:none;padding:0}
.help-steps li{counter-increment:steps;display:flex;align-items:flex-start;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px}
.help-steps li:last-child{border-bottom:none}
.help-steps li::before{content:counter(steps);width:24px;height:24px;border-radius:50%;background:var(--blue);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;margin-top:1px}
.help-badge{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:500;padding:2px 8px;border-radius:20px}
.badge-sensor{background:var(--green-bg);color:var(--green);border:1px solid var(--green-bd)}
.badge-manual{background:var(--blue-bg);color:var(--blue);border:1px solid var(--blue-bd)}
.badge-admin{background:var(--amber-bg);color:var(--amber);border:1px solid var(--amber-bd)}
.help-tip{display:flex;gap:10px;padding:12px 14px;background:var(--blue-bg);border:1px solid var(--blue-bd);border-radius:8px;font-size:12px;color:var(--blue);margin:10px 0}
.help-warn{display:flex;gap:10px;padding:12px 14px;background:var(--amber-bg);border:1px solid var(--amber-bd);border-radius:8px;font-size:12px;color:var(--amber);margin:10px 0}
.lang-toggle{display:flex;gap:4px;background:var(--bg);border:1px solid var(--border);border-radius:7px;padding:3px}
.lang-btn{font-family:'DM Sans',sans-serif;font-size:12px;font-weight:500;padding:4px 12px;border:none;border-radius:5px;cursor:pointer;color:var(--muted);background:transparent;transition:all .15s}
.lang-btn.active{background:var(--blue);color:#fff}
.endpoint-row{display:flex;gap:10px;align-items:center;padding:10px 0;border-bottom:1px solid var(--border);font-size:12px;flex-wrap:wrap}
.endpoint-row:last-child{border-bottom:none}
.method-badge{font-family:'DM Mono',monospace;font-size:10px;font-weight:700;padding:3px 8px;border-radius:4px;flex-shrink:0}
.method-post{background:#dcfce7;color:#15803d}
.method-get{background:#dbeafe;color:#1d6ed8}
.method-put{background:#fef3c7;color:#b45309}
.method-delete{background:#fee2e2;color:#be123c}
.tree{font-family:'DM Mono',monospace;font-size:12px;background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:14px 16px;line-height:1.8;white-space:pre;overflow-x:auto}
.tree .dir{color:var(--blue);font-weight:600}
.tree .file{color:var(--text)}
.tree .comment{color:var(--muted)}
</style>
@endpush

@section('content')

@php
  $lang = request('lang', 'fr');
  $iconWarning = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';
@endphp

<div style="display:grid;grid-template-columns:220px 1fr;gap:16px;align-items:start">

  {{-- Sidebar navigation --}}
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);padding:12px;position:sticky;top:20px">

    {{-- Language toggle --}}
    <div style="margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid var(--border)">
      <div style="font-size:10px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Language</div>
      <div class="lang-toggle">
        <button class="lang-btn {{ $lang==='fr' ? 'active' : '' }}" onclick="setLang('fr')">FR</button>
        <button class="lang-btn {{ $lang==='en' ? 'active' : '' }}" onclick="setLang('en')">EN</button>
      </div>
    </div>

    {{-- Navigation --}}
    <div class="help-nav" id="help-nav">
      @php
        $sections = $lang === 'fr' ? [
          ['1', 'Architecture'],
          ['2', 'Installation'],
          ['3', 'Base de données'],
          ['4', 'API Reference'],
          ['5', 'Guide Développeur'],
          ['6', 'Déploiement'],
          ['7', 'ESP32 / IoT'],
        ] : [
          ['1', 'Architecture'],
          ['2', 'Installation'],
          ['3', 'Database'],
          ['4', 'API Reference'],
          ['5', 'Developer Guide'],
          ['6', 'Deployment'],
          ['7', 'ESP32 / IoT'],
        ];
      @endphp
      @foreach($sections as $i => $s)
      <button class="help-nav-item {{ $i===0 ? 'active' : '' }}" onclick="showSection({{ $i }}, this)">
        <span class="nav-num">{{ $s[0] }}</span>
        <span>{{ $s[1] }}</span>
      </button>
      @endforeach
    </div>

    {{-- Back to Help --}}
    <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--border)">
      <a href="{{ route('help') }}"
         style="display:flex;align-items:center;gap:6px;padding:8px 10px;border-radius:7px;border:1px solid var(--border);background:var(--bg);color:var(--text);text-decoration:none;font-size:12px;font-weight:500;transition:all .15s"
         onmouseover="this.style.background='var(--surface)'" onmouseout="this.style.background='var(--bg)'">
         {{ $lang==='fr' ? 'Manuel Utilisateur' : 'User Manual' }}
      </a>
    </div>
  </div>

  {{-- Content --}}
  <div>

    {{-- SECTION 1 — ARCHITECTURE --}}
    <div class="help-section active" id="section-0">
      <div class="help-card">
        <div class="help-h1">{{ $lang==='fr' ? 'Architecture du projet' : 'Project Architecture' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          APV-MaGa est une application <strong>Laravel 12</strong> avec templates Blade, conçue autour d'un modèle de
          données <strong>dynamique</strong> : les sites, catégories, paramètres et graphiques sont configurables
          depuis l'interface d'administration, sans modification du code.
          @else
          APV-MaGa is a <strong>Laravel 12</strong> application using Blade templates, built around a
          <strong>dynamic</strong> data model: sites, categories, parameters and charts are configurable
          from the admin interface without code changes.
          @endif
        </p>

        <div class="help-h2">{{ $lang==='fr' ? 'Stack technique' : 'Technology Stack' }}</div>
        <table class="help-table">
          <thead><tr><th>{{ $lang==='fr' ? 'Composant' : 'Component' }}</th><th>{{ $lang==='fr' ? 'Technologie' : 'Technology' }}</th></tr></thead>
          <tbody>
            @php
            $stack = [
              ['Framework', 'Laravel 12 (PHP 8.2 / 8.3)'],
              [$lang==='fr' ? 'Vues' : 'Views', 'Blade templates'],
              [$lang==='fr' ? 'Base de données (prod)' : 'Database (prod)', 'MySQL 8'],
              [$lang==='fr' ? 'Base de données (dev)' : 'Database (dev)', 'SQLite'],
              [$lang==='fr' ? 'Graphiques' : 'Charts', 'Chart.js 4'],
              [$lang==='fr' ? 'Export' : 'Export', 'Maatwebsite/Excel (CSV / XLSX)'],
              [$lang==='fr' ? 'Authentification' : 'Authentication', 'Laravel Breeze (session-based)'],
              [$lang==='fr' ? 'Hébergement' : 'Hosting', 'Hostinger VPS + AAPanel + Nginx'],
              ['SSL', "Let's Encrypt"],
            ];
            @endphp
            @foreach($stack as $row)
            <tr><td><strong>{{ $row[0] }}</strong></td><td>{{ $row[1] }}</td></tr>
            @endforeach
          </tbody>
        </table>

        <div class="help-h2">{{ $lang==='fr' ? 'Flux de données' : 'Data Flow' }}</div>
        <div style="display:flex;align-items:center;justify-content:center;gap:8px;padding:20px;background:var(--bg);border-radius:8px;flex-wrap:wrap">
          @foreach(['ESP32 / Agent terrain', '→', 'API / Formulaire', '→', 'SensorReading / ManualReading', '→', 'Dashboard + Export'] as $item)
          @if($item === '→')
          <span style="color:var(--muted);font-size:18px">→</span>
          @else
          <div style="padding:8px 14px;background:var(--surface);border:1px solid var(--border);border-radius:7px;font-size:12px;font-weight:500">{{ $item }}</div>
          @endif
          @endforeach
        </div>

        <div class="help-h2">{{ $lang==='fr' ? 'Structure des dossiers' : 'Folder Structure' }}</div>
        <div class="tree"><span class="dir">app/</span>
├── <span class="dir">Models/</span>
│   ├── <span class="file">Site.php</span> <span class="comment">{{ $lang==='fr' ? '// Sites agrivoltaïques' : '// Agrivoltaic sites' }}</span>
│   ├── <span class="file">SiteCategory.php</span> <span class="comment">{{ $lang==='fr' ? '// Catégories (Solar, Water...)' : '// Categories (Solar, Water...)' }}</span>
│   ├── <span class="file">SiteParameter.php</span> <span class="comment">{{ $lang==='fr' ? '// Paramètres + defaultsFor()' : '// Parameters + defaultsFor()' }}</span>
│   ├── <span class="file">SiteChart.php</span> <span class="comment">{{ $lang==='fr' ? '// Configuration des graphiques' : '// Chart configuration' }}</span>
│   ├── <span class="file">SensorReading.php</span> <span class="comment">{{ $lang==='fr' ? '// Données capteurs (JSON)' : '// Sensor data (JSON)' }}</span>
│   ├── <span class="file">ManualReading.php</span> <span class="comment">{{ $lang==='fr' ? '// Saisies manuelles' : '// Manual entries' }}</span>
│   └── <span class="file">User.php</span> <span class="comment">{{ $lang==='fr' ? '// Utilisateurs + assignedSites()' : '// Users + assignedSites()' }}</span>
├── <span class="dir">Http/Controllers/</span>
│   ├── <span class="file">DashboardController.php</span>
│   ├── <span class="file">AdminController.php</span> <span class="comment">{{ $lang==='fr' ? '// CRUD sites/catégories/paramètres' : '// Sites/categories/parameters CRUD' }}</span>
│   ├── <span class="file">ExportController.php</span> <span class="comment">{{ $lang==='fr' ? '// Export CSV / Excel' : '// CSV / Excel export' }}</span>
│   └── <span class="dir">Api/</span>
│   └── <span class="file">ApiController.php</span> <span class="comment">{{ $lang==='fr' ? '// API ESP32 dynamique' : '// Dynamic ESP32 API' }}</span>
├── <span class="dir">Exports/</span>
│   └── <span class="file">ArrayExport.php</span>
└── <span class="dir">Providers/</span>
    └── <span class="file">AppServiceProvider.php</span> <span class="comment">{{ $lang==='fr' ? '// Partage $sites à toutes les vues' : '// Shares $sites to all views' }}</span>

<span class="dir">resources/views/</span>
├── <span class="dir">layouts/</span><span class="file">dashboard.blade.php</span> <span class="comment">{{ $lang==='fr' ? '// Layout principal (responsive)' : '// Main layout (responsive)' }}</span>
├── <span class="dir">dashboard/</span>
│   ├── <span class="file">index.blade.php</span> <span class="comment">{{ $lang==='fr' ? '// All Sites' : '// All Sites' }}</span>
│   ├── <span class="file">site.blade.php</span> <span class="comment">{{ $lang==='fr' ? '// Détail d\'un site' : '// Site detail' }}</span>
│   └── <span class="dir">partials/</span><span class="file">manual-records.blade.php</span>
├── <span class="dir">admin/</span> <span class="comment">{{ $lang==='fr' ? '// Pages Admin Panel' : '// Admin Panel pages' }}</span>
└── <span class="dir">auth/</span><span class="file">login.blade.php</span>

<span class="dir">routes/</span>
├── <span class="file">web.php</span> <span class="comment">{{ $lang==='fr' ? '// Routes dashboard + admin' : '// Dashboard + admin routes' }}</span>
└── <span class="file">api.php</span> <span class="comment">{{ $lang==='fr' ? '// Routes API ESP32' : '// ESP32 API routes' }}</span></div>

      </div>
    </div>

    {{-- SECTION 2 — INSTALLATION --}}
    <div class="help-section" id="section-1">
      <div class="help-card">
        <div class="help-h1">{{ $lang==='fr' ? 'Installation & Configuration' : 'Installation & Configuration' }}</div>

        <div class="help-h2">{{ $lang==='fr' ? 'Prérequis' : 'Requirements' }}</div>
        <table class="help-table">
          <tbody>
            @foreach(['PHP >= 8.2', 'Composer', 'Node.js + NPM', 'MySQL 8 ('.($lang==='fr'?'production':'production').') / SQLite ('.($lang==='fr'?'développement':'development').')', 'Extension PHP : pdo_sqlite ou pdo_mysql, mbstring, fileinfo'] as $req)
            <tr><td>{{ $req }}</td></tr>
            @endforeach
          </tbody>
        </table>

        <div class="help-h2">{{ $lang==='fr' ? 'Installation locale (Windows / PowerShell)' : 'Local Installation (Windows / PowerShell)' }}</div>
        <ol class="help-steps">
          @php
          $steps = $lang==='fr' ? [
            'Cloner ou copier le projet dans <span class="help-code-inline">C:\Projects\apv-maga</span>',
            'Installer les dépendances PHP : <span class="help-code-inline">composer install</span>',
            'Copier <span class="help-code-inline">.env.example</span> vers <span class="help-code-inline">.env</span>',
            'Générer la clé d\'application : <span class="help-code-inline">php artisan key:generate</span>',
            'Créer la base SQLite : <span class="help-code-inline">touch database/database.sqlite</span> (ou créer le fichier manuellement)',
            'Lancer les migrations : <span class="help-code-inline">php artisan migrate</span>',
            'Créer le compte admin : <span class="help-code-inline">php artisan db:seed --class=AdminSeeder</span>',
            'Démarrer le serveur : <span class="help-code-inline">php artisan serve</span>',
          ] : [
            'Clone or copy the project into <span class="help-code-inline">C:\Projects\apv-maga</span>',
            'Install PHP dependencies: <span class="help-code-inline">composer install</span>',
            'Copy <span class="help-code-inline">.env.example</span> to <span class="help-code-inline">.env</span>',
            'Generate the app key: <span class="help-code-inline">php artisan key:generate</span>',
            'Create the SQLite database: <span class="help-code-inline">touch database/database.sqlite</span> (or create the file manually)',
            'Run migrations: <span class="help-code-inline">php artisan migrate</span>',
            'Create the admin account: <span class="help-code-inline">php artisan db:seed --class=AdminSeeder</span>',
            'Start the server: <span class="help-code-inline">php artisan serve</span>',
          ];
          @endphp
          @foreach($steps as $step)
          <li>{!! $step !!}</li>
          @endforeach
        </ol>

        <div class="help-h2">{{ $lang==='fr' ? 'Variables d\'environnement clés (.env)' : 'Key Environment Variables (.env)' }}</div>
        <div class="help-code">APP_NAME=APV-MaGa
APP_ENV=local
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxx
APP_URL=http://127.0.0.1:8000

# Production (MySQL)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=apvmaga_user
DB_USERNAME=apvmaga_user
DB_PASSWORD=********

# Local dev (SQLite)
# DB_CONNECTION=sqlite</div>

        <div class="help-h2">{{ $lang==='fr' ? 'Comptes par défaut' : 'Default Accounts' }}</div>
        <table class="help-table">
          <thead><tr><th>{{ $lang==='fr' ? 'Rôle' : 'Role' }}</th><th>Email</th><th>{{ $lang==='fr' ? 'Mot de passe' : 'Password' }}</th></tr></thead>
          <tbody>
            <tr><td><strong>Admin</strong></td><td><span class="help-code-inline">admin@apvmaga.com</span></td><td><span class="help-code-inline">apvmaga@2026</span></td></tr>
          </tbody>
        </table>

        <div class="help-warn">
          <span style="flex-shrink:0;display:flex;align-items:center;margin-top:1px">{!! $iconWarning !!}</span>
          <span>{{ $lang==='fr' ? 'Changez ce mot de passe immédiatement après la mise en production.' : 'Change this password immediately after going to production.' }}</span>
        </div>

        <div class="help-h2">{{ $lang==='fr' ? 'Commandes utiles' : 'Useful Commands' }}</div>
        <div class="help-code"># {{ $lang==='fr' ? 'Vider les caches après modification du code' : 'Clear caches after code changes' }}
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# {{ $lang==='fr' ? 'Réinitialiser complètement la base de données' : 'Completely reset the database' }}
php artisan migrate:fresh --force
php artisan db:seed --class=DatabaseSeeder --force

# {{ $lang==='fr' ? 'Lister toutes les routes' : 'List all routes' }}
php artisan route:list

# {{ $lang==='fr' ? 'Ouvrir une console interactive' : 'Open an interactive console' }}
php artisan tinker</div>
      </div>
    </div>

    {{-- SECTION 3 — DATABASE --}}
    <div class="help-section" id="section-2">
      <div class="help-card">
        <div class="help-h1">{{ $lang==='fr' ? 'Base de données' : 'Database' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Le schéma repose sur un modèle <strong>dynamique</strong> : un site possède plusieurs catégories,
          chaque catégorie possède plusieurs paramètres, et chaque paramètre génère des lectures (sensor ou manual).
          @else
          The schema is based on a <strong>dynamic</strong> model: a site has many categories,
          each category has many parameters, and each parameter generates readings (sensor or manual).
          @endif
        </p>

        <div class="help-h2">{{ $lang==='fr' ? 'Schéma relationnel' : 'Relational Schema' }}</div>
        <div class="tree">sites (1) ──── (N) site_categories (1) ──── (N) site_parameter_groups
  │                                                        │
  │                                                        └── (N) site_parameters
  │                                                                 │
  │                                                                 ├── (N) sensor_readings
  │                                                                 ├── (N) manual_readings
  │                                                                 └── (1) actuator_commands
  │
  ├── (N) site_charts ──── (N) site_chart_parameters
  │
  └── (N) site_user ──── (N) users</div>

        <div class="help-h2">{{ $lang==='fr' ? 'Tables principales' : 'Main Tables' }}</div>
        <table class="help-table">
          <thead><tr><th>{{ $lang==='fr' ? 'Table' : 'Table' }}</th><th>{{ $lang==='fr' ? 'Description' : 'Description' }}</th><th>{{ $lang==='fr' ? 'Colonnes clés' : 'Key Columns' }}</th></tr></thead>
          <tbody>
            @php
            $tables = $lang==='fr' ? [
              ['sites', 'Sites agrivoltaïques', 'name, slug, country, api_key, capacity_kw, area_m2, status'],
              ['site_categories', 'Catégories d\'un site (Solar, Water...)', 'site_id, name, slug, icon, color, is_active, sort_order, offline_threshold_minutes'],
              ['site_parameter_groups', 'Groupes de paramètres d\'une catégorie', 'site_category_id, name, color, sort_order'],
              ['site_parameters', 'Paramètres d\'une catégorie', 'site_category_id, site_parameter_group_id, name, slug, unit, data_type, input_type, control_type, min_value, max_value, warning_threshold, critical_threshold, threshold_direction (below/above), show_on_dashboard'],
              ['sensor_readings', 'Données reçues via l\'API', 'site_id, site_parameter_id, value, value_text, read_at'],
              ['manual_readings', 'Saisies manuelles par les agents', 'site_id, site_parameter_id, value, reading_date (datetime), notes, user_id, created_at'],
              ['actuator_commands', 'Commandes pour les switches controllable', 'site_id, site_parameter_id, desired_state, reported_state, reported_at, updated_by'],
              ['site_charts', 'Configuration des graphiques', 'site_category_id, title, chart_type (line/bar/area), col_span (full/half/third), height, show_legend, dual_axis'],
              ['site_chart_parameters', 'Séries de données d\'un graphique', 'site_chart_id, site_parameter_id, color, axis, dashed, fill, sort_order'],
              ['users', 'Comptes utilisateurs', 'name, email, password, role, status, country'],
              ['site_user', 'Pivot utilisateurs sites', 'site_id, user_id, role (agent/observateur)'],
            ] : [
              ['sites', 'Agrivoltaic sites', 'name, slug, country, api_key, capacity_kw, area_m2, status'],
              ['site_categories', 'Categories of a site (Solar, Water...)', 'site_id, name, slug, icon, color, is_active, sort_order, offline_threshold_minutes'],
              ['site_parameter_groups', 'Parameter groups within a category', 'site_category_id, name, color, sort_order'],
              ['site_parameters', 'Parameters of a category', 'site_category_id, site_parameter_group_id, name, slug, unit, data_type, input_type, control_type, min_value, max_value, warning_threshold, critical_threshold, threshold_direction (below/above), show_on_dashboard'],
              ['sensor_readings', 'Data received via the API', 'site_id, site_parameter_id, value, value_text, read_at'],
              ['manual_readings', 'Manual entries by field agents', 'site_id, site_parameter_id, value, reading_date (datetime), notes, user_id, created_at'],
              ['actuator_commands', 'Commands for controllable switches', 'site_id, site_parameter_id, desired_state, reported_state, reported_at, updated_by'],
              ['site_charts', 'Chart configuration', 'site_category_id, title, chart_type (line/bar/area), col_span (full/half/third), height, show_legend, dual_axis'],
              ['site_chart_parameters', 'Data series of a chart', 'site_chart_id, site_parameter_id, color, axis, dashed, fill, sort_order'],
              ['users', 'User accounts', 'name, email, password, role, status, country'],
              ['site_user', 'Pivot users sites', 'site_id, user_id, role (agent/observateur)'],
            ];
            @endphp
            @foreach($tables as $row)
            <tr>
              <td><span class="help-code-inline">{{ $row[0] }}</span></td>
              <td>{{ $row[1] }}</td>
              <td style="font-size:11px;color:var(--muted)">{{ $row[2] }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>

        <div class="help-h2">{{ $lang==='fr' ? 'Valeurs possibles — data_type / input_type' : 'Possible Values — data_type / input_type' }}</div>
        <table class="help-table">
          <thead><tr><th>{{ $lang==='fr' ? 'Champ' : 'Field' }}</th><th>{{ $lang==='fr' ? 'Valeurs' : 'Values' }}</th><th>{{ $lang==='fr' ? 'Description' : 'Description' }}</th></tr></thead>
          <tbody>
            @php
            $vals = $lang==='fr' ? [
              ['data_type', 'float', 'Nombre décimal (ex: 4.2 kW)'],
              ['data_type', 'integer', 'Nombre entier (ex: 12)'],
              ['data_type', 'string', 'Texte libre (ex: type de culture, état de santé)'],
              ['data_type', 'boolean', 'Oui / Non (rarement utilisé — préférer integer pour les graphiques)'],
              ['data_type', 'switch', 'État ON/OFF (0/1) — affiché comme un toggle. Voir control_type ci-dessous.'],
              ['input_type', 'sensor', 'Données envoyées automatiquement via l\'API ESP32. Obligatoire si data_type=switch.'],
              ['input_type', 'manual', 'Données saisies par un agent via le formulaire Manual Input'],
              ['control_type', 'readonly', 'Switch en lecture seule : ON/OFF reflète directement la dernière valeur capteur ("Auto (sensor)")'],
              ['control_type', 'controllable', 'Switch pilotable depuis le dashboard : toggle + commande envoyée via actuator_commands'],
            ] : [
              ['data_type', 'float', 'Decimal number (e.g. 4.2 kW)'],
              ['data_type', 'integer', 'Whole number (e.g. 12)'],
              ['data_type', 'string', 'Free text (e.g. crop type, health status)'],
              ['data_type', 'boolean', 'Yes / No (rarely used — prefer integer for charts)'],
              ['data_type', 'switch', 'ON/OFF state (0/1) — displayed as a toggle. See control_type below.'],
              ['input_type', 'sensor', 'Data sent automatically via the ESP32 API. Required if data_type=switch.'],
              ['input_type', 'manual', 'Data entered by an agent via the Manual Input form'],
              ['control_type', 'readonly', 'Read-only switch: ON/OFF directly reflects the latest sensor value ("Auto (sensor)")'],
              ['control_type', 'controllable', 'Switch controllable from the dashboard: toggle + command sent via actuator_commands'],
            ];
            @endphp
            @foreach($vals as $row)
            <tr>
              <td><span class="help-code-inline">{{ $row[0] }}</span></td>
              <td><span class="help-code-inline">{{ $row[1] }}</span></td>
              <td>{{ $row[2] }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>

        <div class="help-h2">{{ $lang==='fr' ? 'Relations Eloquent principales' : 'Main Eloquent Relations' }}</div>
        <div class="help-code">// Site.php
public function categories() { return $this->hasMany(SiteCategory::class); }
public function activeCategories(){ return $this->categories()->where('is_active', true); }
public function users() { return $this->belongsToMany(User::class, 'site_user')
                                       ->withPivot('role'); }

// SiteCategory.php
public function parameters() { return $this->hasMany(SiteParameter::class); }
public function activeParameters() { return $this->parameters()->where('is_active', true); }
public function charts() { return $this->hasMany(SiteChart::class); }

// SiteParameter.php
public function category() { return $this->belongsTo(SiteCategory::class, 'site_category_id'); }
public function manualReadings() { return $this->hasMany(ManualReading::class); }
public function actuatorCommand() { return $this->hasOne(ActuatorCommand::class); }
public function isControllable() { return ($this->control_type ?? 'readonly') === 'controllable'; }

// ActuatorCommand.php
public function site() { return $this->belongsTo(Site::class); }
public function parameter() { return $this->belongsTo(SiteParameter::class, 'site_parameter_id'); }
public function isSynced(): ?bool {
    if ($this->reported_state === null) return null;
    return $this->desired_state === $this->reported_state;
}

// User.php
public function assignedSites() { return $this->belongsToMany(Site::class, 'site_user')
                                     ->withPivot('role'); }</div>

        <div class="help-tip">
          <span>
            @if($lang==='fr')
            <strong>Important :</strong> pour le partage des sites accessibles dans <span class="help-code-inline">AppServiceProvider</span>, on fusionne <span class="help-code-inline">assignedSites()</span> (pivot) avec les sites créés directement par l'utilisateur (<span class="help-code-inline">user_id</span>), afin de couvrir les deux cas.
            @else
            <strong>Important:</strong> when sharing accessible sites in <span class="help-code-inline">AppServiceProvider</span>, we merge <span class="help-code-inline">assignedSites()</span> (pivot) with sites directly owned by the user (<span class="help-code-inline">user_id</span>), to cover both cases.
            @endif
          </span>
        </div>
      </div>
    </div>

    {{-- SECTION 4 — API REFERENCE --}}
    <div class="help-section" id="section-3">
      <div class="help-card">
        <div class="help-h1">{{ $lang==='fr' ? 'Référence API' : 'API Reference' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Toutes les routes API sont définies dans <span class="help-code-inline">routes/api.php</span> et préfixées par <span class="help-code-inline">/api</span>. L'authentification se fait via le header <span class="help-code-inline">X-API-Key</span>, propre à chaque site.
          @else
          All API routes are defined in <span class="help-code-inline">routes/api.php</span> and prefixed with <span class="help-code-inline">/api</span>. Authentication is done via the <span class="help-code-inline">X-API-Key</span> header, unique to each site.
          @endif
        </p>

        <div class="help-h2">{{ $lang==='fr' ? 'Authentification' : 'Authentication' }}</div>
        <div class="help-code">X-API-Key: apv-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx</div>
        <p class="help-p">
          @if($lang==='fr')
          Implémentée dans <span class="help-code-inline">ApiController::authenticate()</span> :
          @else
          Implemented in <span class="help-code-inline">ApiController::authenticate()</span>:
          @endif
        </p>
        <div class="help-code">private function authenticate(Request $request)
{
    $apiKey = $request->header('X-API-Key');
    if (!$apiKey) return null;
    return Site::where('api_key', $apiKey)->first();
}</div>

        <div class="help-h2">{{ $lang==='fr' ? 'Endpoints — Capteurs (sensor)' : 'Endpoints — Sensors' }}</div>
        <div style="background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:14px">
          <div class="endpoint-row">
            <span class="method-badge method-post">POST</span>
            <span class="help-code-inline">/api/sensors/{site}/{category}</span>
            <span style="color:var(--muted);font-size:11px">{{ $lang==='fr' ? 'Enregistrer une lecture capteur' : 'Store a sensor reading' }}</span>
          </div>
          <div class="endpoint-row">
            <span class="method-badge method-get">GET</span>
            <span class="help-code-inline">/api/sensors/{site}/{category}/latest</span>
            <span style="color:var(--muted);font-size:11px">{{ $lang==='fr' ? 'Dernière lecture de la catégorie' : 'Latest reading for the category' }}</span>
          </div>
          <div class="endpoint-row">
            <span class="method-badge method-get">GET</span>
            <span class="help-code-inline">/api/sensors/{site}/status</span>
            <span style="color:var(--muted);font-size:11px">{{ $lang==='fr' ? 'Statut de toutes les catégories' : 'Status of all categories' }}</span>
          </div>
          <div class="endpoint-row">
            <span class="method-badge method-get">GET</span>
            <span class="help-code-inline">/api/sensors/{site}/{category}/history</span>
            <span style="color:var(--muted);font-size:11px">{{ $lang==='fr' ? 'Lectures brutes sur une période (max 90j)' : 'Raw readings over a date range (max 90 days)' }}</span>
          </div>
          <div class="endpoint-row">
            <span class="method-badge method-get">GET</span>
            <span class="help-code-inline">/api/sensors/{site}/{category}/stats</span>
            <span style="color:var(--muted);font-size:11px">{{ $lang==='fr' ? 'Agrégats (avg/min/max/sum) par période' : 'Aggregates (avg/min/max/sum) per period' }}</span>
          </div>
        </div>

        <div class="help-h3">POST /api/sensors/{site}/{category}</div>
        <p class="help-p">
          @if($lang==='fr')
          <span class="help-code-inline">{site}</span> = slug du site (ex: <span class="help-code-inline">fass-6a15122aee52e</span>).
          <span class="help-code-inline">{category}</span> = slug de la catégorie (ex: <span class="help-code-inline">solar</span>, <span class="help-code-inline">weather</span>...).
          Le corps JSON doit contenir une clé par paramètre actif de type <span class="help-code-inline">sensor</span>.
          @else
          <span class="help-code-inline">{site}</span> = site slug (e.g. <span class="help-code-inline">fass-6a15122aee52e</span>).
          <span class="help-code-inline">{category}</span> = category slug (e.g. <span class="help-code-inline">solar</span>, <span class="help-code-inline">weather</span>...).
          The JSON body must contain one key per active <span class="help-code-inline">sensor</span> parameter.
          @endif
        </p>

        <div class="help-h3">{{ $lang==='fr' ? 'Exemple de requête' : 'Request Example' }}</div>
        <div class="help-code">curl -X POST https://apvmaga.saratechniger.com/api/sensors/fass-6a15122aee52e/solar \
  -H "Content-Type: application/json" \
  -H "X-API-Key: apv-6AJgzjBQKLij27vznGOCPaf2ASoEp9mp" \
  -d '{
    "solar_output": 4.2,
    "solar_irradiance": 850,
    "panel_temperature": 42.5,
    "system_efficiency": 87.3
  }'</div>

        <div class="help-h3">{{ $lang==='fr' ? 'PowerShell (Windows)' : 'PowerShell (Windows)' }}</div>
        <div class="help-code">Invoke-RestMethod -Uri "https://apvmaga.saratechniger.com/api/sensors/fass-6a15122aee52e/solar" `
  -Method POST `
  -Headers @{"Content-Type"="application/json";"X-API-Key"="apv-6AJgzjBQKLij27vznGOCPaf2ASoEp9mp"} `
  -Body '{"solar_output":4.2,"solar_irradiance":850,"panel_temperature":42.5,"system_efficiency":87.3}'</div>

        <div class="help-h3">{{ $lang==='fr' ? 'Réponse (succès)' : 'Response (success)' }}</div>
        <div class="help-code">{
  "success": true,
  "site": "Fass Agrivoltaic Site",
  "category": "Solar",
  "stored": {
    "solar_output": 4.2,
    "solar_irradiance": 850,
    "panel_temperature": 42.5,
    "system_efficiency": 87.3
  },
  "errors": [],
  "read_at": "2026-06-13T01:42:37.157566Z"
}</div>

        <div class="help-h3">{{ $lang==='fr' ? 'Réponses d\'erreur' : 'Error Responses' }}</div>
        <table class="help-table">
          <thead><tr><th>{{ $lang==='fr' ? 'Code HTTP' : 'HTTP Code' }}</th><th>{{ $lang==='fr' ? 'Cause' : 'Cause' }}</th></tr></thead>
          <tbody>
            @php
            $errs = $lang==='fr' ? [
              ['401', 'Clé API manquante ou invalide (header X-API-Key)'],
              ['404', 'Site ou catégorie introuvable / inactive (slug incorrect)'],
              ['422', 'Champ(s) invalide(s) — voir le tableau "errors" dans la réponse'],
            ] : [
              ['401', 'Missing or invalid API key (X-API-Key header)'],
              ['404', 'Site or category not found / inactive (wrong slug)'],
              ['422', 'Invalid field(s) — see the "errors" array in the response'],
            ];
            @endphp
            @foreach($errs as $row)
            <tr><td><span class="method-badge method-delete">{{ $row[0] }}</span></td><td>{{ $row[1] }}</td></tr>
            @endforeach
          </tbody>
        </table>

        <div class="help-h3">GET /api/sensors/{site}/{category}/latest</div>
        <div class="help-code">curl https://apvmaga.saratechniger.com/api/sensors/fass-6a15122aee52e/solar/latest \
  -H "X-API-Key: apv-6AJgzjBQKLij27vznGOCPaf2ASoEp9mp"</div>
        <div class="help-code">{
  "category": "Solar",
  "reading": {
    "solar_output": 4.2,
    "solar_irradiance": 850,
    "panel_temperature": 42.5,
    "system_efficiency": 87.3
  },
  "received_at": "2026-06-13T01:42:37.157566Z"
}</div>

        <div class="help-h3">GET /api/sensors/{site}/status</div>
        <div class="help-code">curl https://apvmaga.saratechniger.com/api/sensors/fass-6a15122aee52e/status \
  -H "X-API-Key: apv-6AJgzjBQKLij27vznGOCPaf2ASoEp9mp"</div>
        <div class="help-code">{
  "site": "Fass Agrivoltaic Site",
  "categories": {
    "solar": { "active": true, "last_reading_at": "2026-06-13T01:42:37Z" },
    "weather": { "active": true, "last_reading_at": "2026-06-13T01:42:39Z" },
    "agriculture": { "active": true, "last_reading_at": null }
  }
}</div>

        <div class="help-h2">{{ $lang==='fr' ? 'Endpoints — Historique & Statistiques (recherche)' : 'Endpoints — History & Statistics (research)' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Ces deux endpoints permettent d'extraire des données sur une période, sans passer par l'export CSV du dashboard — utiles pour analyse statistique (Python/pandas, R, Jupyter) ou comparaisons inter-sites.
          @else
          These two endpoints let you extract data over a date range without going through the dashboard's CSV export — useful for statistical analysis (Python/pandas, R, Jupyter) or cross-site comparisons.
          @endif
        </p>

        <div class="help-h3">GET /api/sensors/{site}/{category}/history</div>
        <p class="help-p">
          @if($lang==='fr')
          Retourne les lectures brutes au format <strong>"long"/tidy</strong> (1 ligne = 1 paramètre × 1 horodatage), triées par date croissante, paginées.
          @else
          Returns raw readings in <strong>"long"/tidy</strong> format (1 row = 1 parameter × 1 timestamp), sorted ascending by date, paginated.
          @endif
        </p>
        <table class="help-table">
          <thead><tr><th>{{ $lang==='fr' ? 'Paramètre' : 'Parameter' }}</th><th>{{ $lang==='fr' ? 'Type' : 'Type' }}</th><th>{{ $lang==='fr' ? 'Description' : 'Description' }}</th></tr></thead>
          <tbody>
            @php
            $hist = $lang==='fr' ? [
              ['from', 'date (optionnel)', 'Début de la période. Défaut : 24h avant "to".'],
              ['to', 'date (optionnel)', 'Fin de la période. Défaut : maintenant.'],
              ['params', 'string (optionnel)', 'Liste de slugs séparés par virgule, ex: solar_output,solar_irradiance. Défaut : tous les paramètres sensor de la catégorie.'],
              ['page', 'integer (optionnel)', 'Numéro de page. Défaut : 1.'],
              ['per_page', 'integer (optionnel)', 'Lignes par page, max 5000. Défaut : 500.'],
            ] : [
              ['from', 'date (optional)', 'Start of the range. Default: 24h before "to".'],
              ['to', 'date (optional)', 'End of the range. Default: now.'],
              ['params', 'string (optional)', 'Comma-separated slugs, e.g. solar_output,solar_irradiance. Default: all sensor parameters in the category.'],
              ['page', 'integer (optional)', 'Page number. Default: 1.'],
              ['per_page', 'integer (optional)', 'Rows per page, max 5000. Default: 500.'],
            ];
            @endphp
            @foreach($hist as $row)
            <tr>
              <td><span class="help-code-inline">{{ $row[0] }}</span></td>
              <td>{{ $row[1] }}</td>
              <td>{{ $row[2] }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>

        <div class="help-code">curl "https://apvmaga.saratechniger.com/api/sensors/fass-6a15122aee52e/solar/history?from=2026-05-01&to=2026-05-08&params=solar_output,solar_irradiance" \
  -H "X-API-Key: apv-6AJgzjBQKLij27vznGOCPaf2ASoEp9mp"</div>
        <div class="help-code">{
  "success": true,
  "site": "Fass Agrivoltaic Site",
  "category": "Solar",
  "from": "2026-05-01T00:00:00.000000Z",
  "to": "2026-05-08T00:00:00.000000Z",
  "page": 1,
  "per_page": 500,
  "total": 1344,
  "total_pages": 3,
  "data": [
    { "parameter": "solar_output", "name": "Solar output", "unit": "kW", "value": 4.2, "read_at": "2026-05-01T06:00:12Z" },
    { "parameter": "solar_irradiance", "name": "Solar irradiance", "unit": "W/m²", "value": 850, "read_at": "2026-05-01T06:00:12Z" }
  ]
}</div>

        <div class="help-tip">
          <span>
            @if($lang==='fr')
            <strong>Plage maximale : 90 jours.</strong> Au-delà, l'API retourne une erreur 422 et suggère d'utiliser <span class="help-code-inline">/stats</span> pour les analyses long terme.
            @else
            <strong>Maximum range: 90 days.</strong> Beyond that, the API returns a 422 error and suggests using <span class="help-code-inline">/stats</span> for long-term analysis.
            @endif
          </span>
        </div>

        <div class="help-h3">GET /api/sensors/{site}/{category}/stats</div>
        <p class="help-p">
          @if($lang==='fr')
          Retourne des agrégats (moyenne/min/max/somme/nombre) par paramètre numérique, groupés par période (heure/jour/semaine/mois). Idéal pour visualiser des tendances long terme sans télécharger toutes les lectures brutes.
          @else
          Returns aggregates (average/min/max/sum/count) per numeric parameter, grouped by period (hour/day/week/month). Ideal for visualizing long-term trends without downloading all raw readings.
          @endif
        </p>
        <table class="help-table">
          <thead><tr><th>{{ $lang==='fr' ? 'Paramètre' : 'Parameter' }}</th><th>{{ $lang==='fr' ? 'Type' : 'Type' }}</th><th>{{ $lang==='fr' ? 'Description' : 'Description' }}</th></tr></thead>
          <tbody>
            @php
            $stats = $lang==='fr' ? [
              ['period', 'hour / day / week / month', 'Granularité du regroupement. Défaut : day.'],
              ['from', 'date (optionnel)', 'Début de la période. Défaut : 30 jours avant "to".'],
              ['to', 'date (optionnel)', 'Fin de la période. Défaut : maintenant.'],
              ['params', 'string (optionnel)', 'Liste de slugs séparés par virgule. Défaut : tous les paramètres float/integer/switch de la catégorie.'],
            ] : [
              ['period', 'hour / day / week / month', 'Bucket granularity. Default: day.'],
              ['from', 'date (optional)', 'Start of the range. Default: 30 days before "to".'],
              ['to', 'date (optional)', 'End of the range. Default: now.'],
              ['params', 'string (optional)', 'Comma-separated slugs. Default: all float/integer/switch parameters in the category.'],
            ];
            @endphp
            @foreach($stats as $row)
            <tr>
              <td><span class="help-code-inline">{{ $row[0] }}</span></td>
              <td>{{ $row[1] }}</td>
              <td>{{ $row[2] }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>

        <div class="help-code">curl "https://apvmaga.saratechniger.com/api/sensors/fass-6a15122aee52e/solar/stats?period=day&from=2026-05-01&to=2026-05-31&params=solar_output" \
  -H "X-API-Key: apv-6AJgzjBQKLij27vznGOCPaf2ASoEp9mp"</div>
        <div class="help-code">{
  "success": true,
  "site": "Fass Agrivoltaic Site",
  "category": "Solar",
  "period": "day",
  "from": "2026-05-01T00:00:00.000000Z",
  "to": "2026-05-31T00:00:00.000000Z",
  "stats": {
    "solar_output": {
      "name": "Solar output",
      "unit": "kW",
      "series": [
        { "period": "2026-05-01", "count": 96, "avg": 3.8, "min": 0, "max": 5.1, "sum": 364.8 },
        { "period": "2026-05-02", "count": 96, "avg": 4.1, "min": 0, "max": 5.3, "sum": 393.6 }
      ]
    }
  }
}</div>

        <table class="help-table">
          <thead><tr><th>{{ $lang==='fr' ? 'period' : 'period' }}</th><th>{{ $lang==='fr' ? 'Plage maximale' : 'Maximum range' }}</th></tr></thead>
          <tbody>
            <tr><td><span class="help-code-inline">hour</span></td><td>7 {{ $lang==='fr' ? 'jours' : 'days' }}</td></tr>
            <tr><td><span class="help-code-inline">day</span></td><td>365 {{ $lang==='fr' ? 'jours' : 'days' }}</td></tr>
            <tr><td><span class="help-code-inline">week</span></td><td>730 {{ $lang==='fr' ? 'jours' : 'days' }}</td></tr>
            <tr><td><span class="help-code-inline">month</span></td><td>1825 {{ $lang==='fr' ? 'jours' : 'days' }}</td></tr>
          </tbody>
        </table>

        <div class="help-tip">
          <span>
            @if($lang==='fr')
            Les paramètres <span class="help-code-inline">switch</span> sont inclus dans <span class="help-code-inline">/stats</span> : <span class="help-code-inline">avg</span> représente alors le <strong>taux d'activation</strong> sur la période (ex: 0.42 = activé 42% du temps).
            @else
            <span class="help-code-inline">switch</span> parameters are included in <span class="help-code-inline">/stats</span>: <span class="help-code-inline">avg</span> then represents the <strong>activation rate</strong> over the period (e.g. 0.42 = on 42% of the time).
            @endif
          </span>
        </div>

        <div class="help-h2">{{ $lang==='fr' ? 'Endpoints — Actionneurs (switch commands)' : 'Endpoints — Actuators (switch commands)' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Pour les paramètres <span class="help-code-inline">data_type=switch</span> avec <span class="help-code-inline">control_type=controllable</span> (vannes, pompes, ventilateurs...), un cycle de commande/rapport permet au dashboard de piloter un appareil à distance.
          @else
          For parameters with <span class="help-code-inline">data_type=switch</span> and <span class="help-code-inline">control_type=controllable</span> (valves, pumps, fans...), a command/report cycle lets the dashboard remotely control a device.
          @endif
        </p>
        <div style="background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:14px">
          <div class="endpoint-row">
            <span class="method-badge method-get">GET</span>
            <span class="help-code-inline">/api/commands/{site}/{category}</span>
            <span style="color:var(--muted);font-size:11px">{{ $lang==='fr' ? 'État souhaité (desired_state) de chaque switch controllable' : 'Desired state of each controllable switch' }}</span>
          </div>
        </div>

        <div class="help-h3">GET /api/commands/{site}/{category}</div>
        <div class="help-code">curl https://apvmaga.saratechniger.com/api/commands/fass-6a15122aee52e/irrigation \
  -H "X-API-Key: apv-6AJgzjBQKLij27vznGOCPaf2ASoEp9mp"</div>
        <div class="help-code">{
  "success": true,
  "site": "Fass Agrivoltaic Site",
  "category": "Irrigation",
  "commands": {
    "valve_1": {
      "name": "Valve 1",
      "desired_state": 1,
      "reported_state": 0,
      "updated_at": "2026-06-14T09:12:03Z"
    },
    "cooling_fan": {
      "name": "Cooling fan",
      "desired_state": 0,
      "reported_state": 0,
      "updated_at": "2026-06-13T22:00:11Z"
    }
  }
}</div>

        <div class="help-h2">{{ $lang==='fr' ? 'Cycle complet (commande -> action -> rapport)' : 'Full cycle (command -> action -> report)' }}</div>
        <ol class="help-steps">
          @php
          $cycle = $lang==='fr' ? [
            'Un admin/agent active le toggle "Valve 1" sur le dashboard -> <span class="help-code-inline">desired_state = 1</span> est enregistré dans <span class="help-code-inline">actuator_commands</span>',
            'L\'ESP32 interroge périodiquement <span class="help-code-inline">GET /api/commands/{site}/{category}</span> (toutes les 10-30s)',
            'S\'il détecte <span class="help-code-inline">desired_state=1</span> différent de l\'état physique actuel, il active le relais correspondant',
            'À l\'itération suivante, l\'ESP32 envoie son état réel via <span class="help-code-inline">POST /api/sensors/{site}/{category}</span> (ex: <span class="help-code-inline">valve_1: 1</span>)',
            'L\'API met automatiquement à jour <span class="help-code-inline">reported_state</span> et <span class="help-code-inline">reported_at</span> dans <span class="help-code-inline">actuator_commands</span>',
            'Le dashboard compare <span class="help-code-inline">desired_state</span> et <span class="help-code-inline">reported_state</span> -> badge <strong>Synced</strong> (égaux), <strong>Pending…</strong> (différents) ou <strong>No device report</strong> (jamais rapporté)',
          ] : [
            'An admin/agent turns ON the "Valve 1" toggle on the dashboard -> <span class="help-code-inline">desired_state = 1</span> is saved in <span class="help-code-inline">actuator_commands</span>',
            'The ESP32 periodically polls <span class="help-code-inline">GET /api/commands/{site}/{category}</span> (every 10-30s)',
            'If it detects <span class="help-code-inline">desired_state=1</span> differs from the current physical state, it activates the corresponding relay',
            'On the next iteration, the ESP32 sends its real state via <span class="help-code-inline">POST /api/sensors/{site}/{category}</span> (e.g. <span class="help-code-inline">valve_1: 1</span>)',
            'The API automatically updates <span class="help-code-inline">reported_state</span> and <span class="help-code-inline">reported_at</span> in <span class="help-code-inline">actuator_commands</span>',
            'The dashboard compares <span class="help-code-inline">desired_state</span> and <span class="help-code-inline">reported_state</span> -> badge <strong>Synced</strong> (equal), <strong>Pending…</strong> (different) or <strong>No device report</strong> (never reported)',
          ];
          @endphp
          @foreach($cycle as $step)
          <li>{!! $step !!}</li>
          @endforeach
        </ol>

        <div class="help-tip">
          <span>
            @if($lang==='fr')
            <strong>Important :</strong> il n'existe pas d'endpoint séparé pour "confirmer" une commande — le rapport d'état se fait via le <span class="help-code-inline">POST /api/sensors</span> habituel. Le paramètre switch doit simplement être inclus dans le payload JSON comme n'importe quel autre capteur.
            @else
            <strong>Important:</strong> there is no separate endpoint to "confirm" a command — state reporting happens via the regular <span class="help-code-inline">POST /api/sensors</span> call. The switch parameter just needs to be included in the JSON payload like any other sensor.
            @endif
          </span>
        </div>

        <div class="help-h2">{{ $lang==='fr' ? 'Trouver le slug et la clé API d\'un site' : 'Finding a site\'s slug and API key' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Dans le dashboard, ouvrez le site puis l'onglet <span class="help-code-inline"> API Key</span>. Cette page liste : la clé API, les 3 endpoints dynamiques, et tous les paramètres <span class="help-code-inline">sensor</span> attendus par catégorie (avec leur slug exact).
          @else
          In the dashboard, open the site then the <span class="help-code-inline"> API Key</span> tab. This page lists: the API key, the 3 dynamic endpoints, and all <span class="help-code-inline">sensor</span> parameters expected per category (with their exact slug).
          @endif
        </p>

        <div class="help-warn">
          <span style="flex-shrink:0;display:flex;align-items:center;margin-top:1px">{!! $iconWarning !!}</span>
          <span>
            @if($lang==='fr')
            Les clés JSON envoyées doivent correspondre <strong>exactement</strong> aux slugs des paramètres configurés (sensible à la casse). Les clés inconnues sont ignorées ; les clés manquantes apparaissent dans <span class="help-code-inline">errors</span> mais n'empêchent pas l'enregistrement des autres champs.
            @else
            The JSON keys sent must <strong>exactly</strong> match the configured parameter slugs (case-sensitive). Unknown keys are ignored; missing keys appear in <span class="help-code-inline">errors</span> but don't prevent other fields from being stored.
            @endif
          </span>
        </div>
      </div>
    </div>

    {{-- SECTION 5 — DEVELOPER GUIDE --}}
    <div class="help-section" id="section-4">
      <div class="help-card">
        <div class="help-h1">{{ $lang==='fr' ? 'Guide Développeur' : 'Developer Guide' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Cette section explique comment étendre la plateforme : ajouter un site, une catégorie par défaut, un type de paramètre, ou un nouveau type d'export.
          @else
          This section explains how to extend the platform: adding a site, a default category, a parameter type, or a new export type.
          @endif
        </p>

        <div class="help-h2">{{ $lang==='fr' ? '1. Ajouter un nouveau site' : '1. Adding a new site' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Via l'interface : <span class="help-code-inline">Admin Panel Sites + New Site</span>. La clé API (<span class="help-code-inline">api_key</span>) et le slug sont générés automatiquement à la création (voir <span class="help-code-inline">AdminController@storeSite</span>).
          @else
          Via the UI: <span class="help-code-inline">Admin Panel Sites + New Site</span>. The API key (<span class="help-code-inline">api_key</span>) and slug are generated automatically on creation (see <span class="help-code-inline">AdminController@storeSite</span>).
          @endif
        </p>
        <div class="help-code">// Génération du slug + clé API (AdminController)
$slug = Str::slug($request->name) . '-' . Str::random(13);
$apiKey = 'apv-' . Str::random(32);</div>

        <div class="help-h2">{{ $lang==='fr' ? '2. Catégories et paramètres par défaut' : '2. Default categories & parameters' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Les définitions par défaut vivent dans deux endroits :
          @else
          Default definitions live in two places:
          @endif
        </p>
        <table class="help-table">
          <thead><tr><th>{{ $lang==='fr' ? 'Fichier' : 'File' }}</th><th>{{ $lang==='fr' ? 'Méthode' : 'Method' }}</th><th>{{ $lang==='fr' ? 'Rôle' : 'Role' }}</th></tr></thead>
          <tbody>
            <tr>
              <td><span class="help-code-inline">SiteCategory.php</span></td>
              <td><span class="help-code-inline">defaults()</span></td>
              <td>{{ $lang==='fr' ? 'Liste des catégories par défaut (Solar, Water, Irrigation, Weather, Agriculture) avec icône et couleur, proposées via "Quick Add Defaults".' : 'List of default categories (Solar, Water, Irrigation, Weather, Agriculture) with icon and color, offered via "Quick Add Defaults".' }}</td>
            </tr>
            <tr>
              <td><span class="help-code-inline">SiteParameter.php</span></td>
              <td><span class="help-code-inline">defaultsFor($categorySlug)</span></td>
              <td>{{ $lang==='fr' ? 'Liste des paramètres par défaut pour chaque catégorie (name, slug, unit, data_type, input_type, show_on_dashboard, group_name, warning_threshold).' : 'List of default parameters for each category (name, slug, unit, data_type, input_type, show_on_dashboard, group_name, warning_threshold).' }}</td>
            </tr>
          </tbody>
        </table>

        <div class="help-h3">{{ $lang==='fr' ? 'Ajouter un nouveau paramètre par défaut' : 'Adding a new default parameter' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Ajoutez une entrée au tableau correspondant dans <span class="help-code-inline">defaultsFor()</span> :
          @else
          Add an entry to the corresponding array in <span class="help-code-inline">defaultsFor()</span>:
          @endif
        </p>
        <div class="help-code">'irrigation' => [
    // ... existing parameters
    [
        'name' => 'Soil pH',
        'slug' => 'soil_ph',
        'unit' => 'pH',
        'data_type' => 'float',
        'input_type' => 'sensor',
        'show_on_dashboard' => true,
        'warning_threshold' => 5.5,
    ],
],</div>

        <div class="help-tip">
          <span>
            @if($lang==='fr')
            <strong>Règle d'or :</strong> jamais <span class="help-code-inline">data_type => 'boolean'</span> pour un paramètre qui doit apparaître sur un graphique — utilisez <span class="help-code-inline">'integer'</span> (0/1). Les booléens sont exclus des graphiques de tendance.
            @else
            <strong>Golden rule:</strong> never use <span class="help-code-inline">data_type => 'boolean'</span> for a parameter that should appear on a chart — use <span class="help-code-inline">'integer'</span> (0/1) instead. Booleans are excluded from trend charts.
            @endif
          </span>
        </div>

        <div class="help-h2">{{ $lang==='fr' ? '3. Ajouter/modifier des catégories & paramètres (CRUD Admin)' : '3. Adding/editing categories & parameters (Admin CRUD)' }}</div>
        <table class="help-table">
          <thead><tr><th>{{ $lang==='fr' ? 'Action' : 'Action' }}</th><th>{{ $lang==='fr' ? 'Route' : 'Route' }}</th><th>{{ $lang==='fr' ? 'Méthode Controller' : 'Controller Method' }}</th></tr></thead>
          <tbody>
            @php
            $crud = [
              ['POST', 'admin/sites/{site}/categories', 'storeCategory'],
              ['PUT', 'admin/sites/{site}/categories/{category}', 'updateCategory'],
              ['POST', 'admin/sites/{site}/categories/{category}/toggle', 'toggleCategory'],
              ['DELETE', 'admin/sites/{site}/categories/{category}', 'destroyCategory'],
              ['POST', '.../categories/{category}/parameters', 'storeParameter'],
              ['PUT', '.../categories/{category}/parameters/{parameter}', 'updateParameter'],
              ['POST', '.../parameters/{parameter}/toggle', 'toggleParameter'],
              ['DELETE', '.../parameters/{parameter}', 'destroyParameter'],
            ];
            @endphp
            @foreach($crud as $row)
            <tr>
              <td><span class="method-badge method-{{ strtolower($row[0])==='delete'?'delete':(strtolower($row[0])==='put'?'put':(strtolower($row[0])==='post'?'post':'get')) }}">{{ $row[0] }}</span></td>
              <td><span class="help-code-inline">{{ $row[1] }}</span></td>
              <td><span class="help-code-inline">AdminController@{{ $row[2] }}</span></td>
            </tr>
            @endforeach
          </tbody>
        </table>

        <div class="help-h2">{{ $lang==='fr' ? '4. Graphiques (SiteChart)' : '4. Charts (SiteChart)' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Chaque <span class="help-code-inline">SiteChart</span> appartient à une catégorie et possède plusieurs <span class="help-code-inline">SiteChartParameter</span> (séries). Si aucun graphique n'est configuré pour une catégorie, le dashboard génère automatiquement un graphique avec tous les paramètres numériques actifs.
          @else
          Each <span class="help-code-inline">SiteChart</span> belongs to a category and has many <span class="help-code-inline">SiteChartParameter</span> (series). If no chart is configured for a category, the dashboard automatically generates one chart with all active numeric parameters.
          @endif
        </p>
        <table class="help-table">
          <thead><tr><th>{{ $lang==='fr' ? 'Champ' : 'Field' }}</th><th>{{ $lang==='fr' ? 'Valeurs' : 'Values' }}</th></tr></thead>
          <tbody>
            <tr><td><span class="help-code-inline">type</span></td><td>line / bar / area</td></tr>
            <tr><td><span class="help-code-inline">width</span></td><td>full / half / third</td></tr>
            <tr><td><span class="help-code-inline">axis</span> ({{ $lang==='fr'?'paramètre':'parameter' }})</td><td>left / right</td></tr>
            <tr><td><span class="help-code-inline">dashed</span> / <span class="help-code-inline">fill</span></td><td>boolean</td></tr>
          </tbody>
        </table>

        <div class="help-h2">{{ $lang==='fr' ? '5. Export CSV / Excel' : '5. CSV / Excel Export' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Toute la logique d'export vit dans <span class="help-code-inline">ExportController.php</span>, avec <span class="help-code-inline">App\Exports\ArrayExport</span> (FromArray, WithTitle, WithStyles, ShouldAutoSize) pour générer dynamiquement des fichiers Excel.
          @else
          All export logic lives in <span class="help-code-inline">ExportController.php</span>, with <span class="help-code-inline">App\Exports\ArrayExport</span> (FromArray, WithTitle, WithStyles, ShouldAutoSize) to dynamically generate Excel files.
          @endif
        </p>
        <table class="help-table">
          <thead><tr><th>{{ $lang==='fr' ? 'Route' : 'Route' }}</th><th>{{ $lang==='fr' ? 'Portée' : 'Scope' }}</th></tr></thead>
          <tbody>
            @php
            $exp = $lang==='fr' ? [
              ['/export/{site}/{category}/csv|excel', 'Une catégorie'],
              ['/export/{site}/all/csv|excel', 'Toutes les catégories du site'],
              ['/export/all-sites/csv|excel', 'Tous les sites'],
              ['/export/{site}/{category}/group/{group}/csv|excel', 'Un groupe de paramètres manuels (Saved Records)'],
            ] : [
              ['/export/{site}/{category}/csv|excel', 'One category'],
              ['/export/{site}/all/csv|excel', 'All categories of the site'],
              ['/export/all-sites/csv|excel', 'All sites'],
              ['/export/{site}/{category}/group/{group}/csv|excel', 'A manual parameter group (Saved Records)'],
            ];
            @endphp
            @foreach($exp as $row)
            <tr><td><span class="help-code-inline">{{ $row[0] }}</span></td><td>{{ $row[1] }}</td></tr>
            @endforeach
          </tbody>
        </table>
        <div class="help-warn">
          <span style="flex-shrink:0;display:flex;align-items:center;margin-top:1px">{!! $iconWarning !!}</span>
          <span>{{ $lang==='fr' ? 'Utilisez toujours rawurlencode() (et non urlencode()) pour les noms de groupes dans les URLs d\'export — sinon les espaces deviennent des "+" au lieu de "%20".' : 'Always use rawurlencode() (not urlencode()) for group names in export URLs — otherwise spaces become "+" instead of "%20".' }}</span>
        </div>

        <div class="help-h2">{{ $lang==='fr' ? '5.1 Période — trait ResolvesDateRange' : '5.1 Date range — ResolvesDateRange trait' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Le trait <span class="help-code-inline">app/Http/Controllers/Concerns/ResolvesDateRange.php</span> (partagé par <span class="help-code-inline">DashboardController</span> et <span class="help-code-inline">ExportController</span>) résout la période à partir de la query string : si <span class="help-code-inline">from</span>/<span class="help-code-inline">to</span> sont présents, ils sont parsés (<span class="help-code-inline">startOfDay()</span> / <span class="help-code-inline">endOfDay()</span>) ; sinon un fallback sur <span class="help-code-inline">hours</span> (défaut par endpoint) est utilisé. Toutes les requêtes utilisent ensuite <span class="help-code-inline">whereBetween($col, [$from, $to])</span> — jamais <span class="help-code-inline">where('col', '>=', $from)</span> seul, pour garantir une borne supérieure.
          @else
          The <span class="help-code-inline">app/Http/Controllers/Concerns/ResolvesDateRange.php</span> trait (shared by <span class="help-code-inline">DashboardController</span> and <span class="help-code-inline">ExportController</span>) resolves the period from the query string: if <span class="help-code-inline">from</span>/<span class="help-code-inline">to</span> are present they're parsed (<span class="help-code-inline">startOfDay()</span> / <span class="help-code-inline">endOfDay()</span>); otherwise it falls back to <span class="help-code-inline">hours</span> (per-endpoint default). All queries then use <span class="help-code-inline">whereBetween($col, [$from, $to])</span> — never a lone <span class="help-code-inline">where('col', '>=', $from)</span> — to guarantee an upper bound.
          @endif
        </p>
        <table class="help-table">
          <thead><tr><th>{{ $lang==='fr' ? 'Endpoint' : 'Endpoint' }}</th><th>{{ $lang==='fr' ? 'Paramètres' : 'Params' }}</th></tr></thead>
          <tbody>
            <tr><td><span class="help-code-inline">/dashboard/{site}/{category}/chart-data</span></td><td><span class="help-code-inline">hours</span> {{ $lang==='fr'?'ou':'or' }} <span class="help-code-inline">from</span>+<span class="help-code-inline">to</span></td></tr>
            <tr><td><span class="help-code-inline">/dashboard/sites/{site}/raw-data</span></td><td><span class="help-code-inline">hours</span> {{ $lang==='fr'?'ou':'or' }} <span class="help-code-inline">from</span>+<span class="help-code-inline">to</span></td></tr>
            <tr><td><span class="help-code-inline">/export/{site}/{category}/csv|excel</span></td><td><span class="help-code-inline">hours</span> {{ $lang==='fr'?'ou':'or' }} <span class="help-code-inline">from</span>+<span class="help-code-inline">to</span></td></tr>
            <tr><td><span class="help-code-inline">/export/{site}/all/csv|excel</span></td><td><span class="help-code-inline">hours</span> {{ $lang==='fr'?'ou':'or' }} <span class="help-code-inline">from</span>+<span class="help-code-inline">to</span></td></tr>
            <tr><td><span class="help-code-inline">/export/all-sites/csv|excel</span></td><td><span class="help-code-inline">hours</span> {{ $lang==='fr'?'ou':'or' }} <span class="help-code-inline">from</span>+<span class="help-code-inline">to</span></td></tr>
          </tbody>
        </table>
        <p class="help-p">
          @if($lang==='fr')
          Côté UI, le bouton <span class="help-code-inline">Custom</span> de la topbar (<span class="help-code-inline">layouts/dashboard.blade.php</span>) ouvre un popover avec deux <span class="help-code-inline">&lt;input type="date"&gt;</span> ; <span class="help-code-inline">applyCustomRange()</span> stocke les valeurs dans <span class="help-code-inline">customFrom</span>/<span class="help-code-inline">customTo</span>, affiche un badge de filtre actif (<span class="help-code-inline">#active-range-chip</span>, croix pour l'effacer via <span class="help-code-inline">clearCustomRange()</span>) et appelle <span class="help-code-inline">onRangeChange('custom', from, to)</span>, défini séparément sur chaque page (<span class="help-code-inline">dashboard/index.blade.php</span> et <span class="help-code-inline">dashboard/site.blade.php</span>) pour recharger graphiques et données. <span class="help-code-inline">exportData()</span> construit la query string (<span class="help-code-inline">from</span>/<span class="help-code-inline">to</span> ou <span class="help-code-inline">hours</span>) en conséquence.
          @else
          On the UI side, the topbar's <span class="help-code-inline">Custom</span> button (<span class="help-code-inline">layouts/dashboard.blade.php</span>) opens a popover with two <span class="help-code-inline">&lt;input type="date"&gt;</span> fields; <span class="help-code-inline">applyCustomRange()</span> stores the values in <span class="help-code-inline">customFrom</span>/<span class="help-code-inline">customTo</span>, shows an active-filter chip (<span class="help-code-inline">#active-range-chip</span>, with a × to clear via <span class="help-code-inline">clearCustomRange()</span>) and calls <span class="help-code-inline">onRangeChange('custom', from, to)</span>, defined separately on each page (<span class="help-code-inline">dashboard/index.blade.php</span> and <span class="help-code-inline">dashboard/site.blade.php</span>) to reload charts and data. <span class="help-code-inline">exportData()</span> builds the query string (<span class="help-code-inline">from</span>/<span class="help-code-inline">to</span> or <span class="help-code-inline">hours</span>) accordingly.
          @endif
        </p>
        <div class="help-warn">
          <span style="flex-shrink:0;display:flex;align-items:center;margin-top:1px">{!! $iconWarning !!}</span>
          <span>{{ $lang==='fr' ? 'La popover #custom-range-popover est en position:fixed (positionnée en JS via getBoundingClientRect du bouton) — pas position:absolute — car .topbar a overflow:hidden et couperait un élément positionné en absolute qui dépasse sa hauteur.' : 'The #custom-range-popover uses position:fixed (positioned in JS via the button\'s getBoundingClientRect) rather than position:absolute — .topbar has overflow:hidden, which would clip an absolutely-positioned element extending past its height.' }}</span>
        </div>

        <div class="help-h2">{{ $lang==='fr' ? '6. Layout responsive' : '6. Responsive layout' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Tout le style global est dans <span class="help-code-inline">layouts/dashboard.blade.php</span>. La sidebar devient un menu off-canvas sous 640px (classe <span class="help-code-inline">.sidebar.open</span> + <span class="help-code-inline">#sidebar-overlay</span>, bouton ). Les pages admin utilisent les mêmes media queries (grids <span class="help-code-inline">repeat(4,1fr)</span> 2 1 colonne).
          @else
          All global styling lives in <span class="help-code-inline">layouts/dashboard.blade.php</span>. The sidebar becomes an off-canvas menu below 640px (class <span class="help-code-inline">.sidebar.open</span> + <span class="help-code-inline">#sidebar-overlay</span>, button). Admin pages use the same media queries (grids <span class="help-code-inline">repeat(4,1fr)</span> 2 1 column).
          @endif
        </p>

        <div class="help-h2">{{ $lang==='fr' ? '7. Permissions & rôles' : '7. Permissions & roles' }}</div>
        <table class="help-table">
          <thead><tr><th>{{ $lang==='fr' ? 'Rôle' : 'Role' }}</th><th>{{ $lang==='fr' ? 'Portée' : 'Scope' }}</th><th>{{ $lang==='fr' ? 'Droits' : 'Rights' }}</th></tr></thead>
          <tbody>
            @php
            $roles = $lang==='fr' ? [
              ['admin', 'Tous les sites', 'Dashboard, Manual Input, API Key, Admin Panel (CRUD complet)'],
              ['agent', 'Sites assignés (pivot site_user)', 'Dashboard, Manual Input, API Key'],
              ['observer', 'Sites assignés (pivot site_user)', 'Dashboard (lecture + export) uniquement'],
            ] : [
              ['admin', 'All sites', 'Dashboard, Manual Input, API Key, Admin Panel (full CRUD)'],
              ['agent', 'Assigned sites (site_user pivot)', 'Dashboard, Manual Input, API Key'],
              ['observer', 'Assigned sites (site_user pivot)', 'Dashboard (view + export) only'],
            ];
            @endphp
            @foreach($roles as $row)
            <tr>
              <td><span class="help-code-inline">{{ $row[0] }}</span></td>
              <td>{{ $row[1] }}</td>
              <td>{{ $row[2] }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
        <p class="help-p">
          @if($lang==='fr')
          La vérification se fait via <span class="help-code-inline">auth()->user()->isAdmin()</span> et le trait <span class="help-code-inline">AuthorizesSiteAccess</span> (partagé par les contrôleurs Dashboard, Export, Actuator et Manual Reading), en croisant <span class="help-code-inline">assignedSites()</span> (pivot) et les sites créés par l'utilisateur. Le même trait expose <span class="help-code-inline">accessibleSiteIds()</span> pour scoper l'export "tous les sites" aux seuls sites autorisés (tous pour un admin).
          @else
          Checks are done via <span class="help-code-inline">auth()->user()->isAdmin()</span> and the <span class="help-code-inline">AuthorizesSiteAccess</span> trait (shared by the Dashboard, Export, Actuator and Manual Reading controllers), intersecting <span class="help-code-inline">assignedSites()</span> (pivot) with sites owned by the user. The same trait exposes <span class="help-code-inline">accessibleSiteIds()</span> to scope the "all sites" export to only the sites a user may access (every site for an admin).
          @endif
        </p>

        <div class="help-h2">{{ $lang==='fr' ? '8. Switches & actionneurs (vannes, pompes, ventilateurs)' : '8. Switches & actuators (valves, pumps, fans)' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Un paramètre devient un <strong>switch pilotable</strong> en combinant <span class="help-code-inline">data_type='switch'</span> et <span class="help-code-inline">control_type='controllable'</span> (voir Admin → Parameters, le champ Control Type n'apparaît que si Data Type = Switch). <span class="help-code-inline">input_type</span> est alors forcé à <span class="help-code-inline">sensor</span> côté backend, même si le formulaire est manipulé.
          @else
          A parameter becomes a <strong>controllable switch</strong> by combining <span class="help-code-inline">data_type='switch'</span> and <span class="help-code-inline">control_type='controllable'</span> (see Admin → Parameters; the Control Type field only appears when Data Type = Switch). <span class="help-code-inline">input_type</span> is then forced to <span class="help-code-inline">sensor</span> on the backend, even if the form is tampered with.
          @endif
        </p>

        <table class="help-table">
          <thead><tr><th>{{ $lang==='fr' ? 'control_type' : 'control_type' }}</th><th>{{ $lang==='fr' ? 'Affichage dashboard' : 'Dashboard display' }}</th><th>{{ $lang==='fr' ? 'Source de vérité' : 'Source of truth' }}</th></tr></thead>
          <tbody>
            @php
            $ctrl = $lang==='fr' ? [
              ['readonly', 'Toggle visuel désactivé, ON/OFF + badge "Auto (sensor)"', 'Dernier sensor_reading (champ $val)'],
              ['controllable', 'Toggle cliquable + modal de confirmation + pill Synced/Pending/No device report', 'actuator_commands (desired_state vs reported_state)'],
            ] : [
              ['readonly', 'Disabled visual toggle, ON/OFF + "Auto (sensor)" badge', 'Latest sensor_reading ($val)'],
              ['controllable', 'Clickable toggle + confirmation modal + Synced/Pending/No device report pill', 'actuator_commands (desired_state vs reported_state)'],
            ];
            @endphp
            @foreach($ctrl as $row)
            <tr>
              <td><span class="help-code-inline">{{ $row[0] }}</span></td>
              <td>{{ $row[1] }}</td>
              <td style="font-size:11px;color:var(--muted)">{{ $row[2] }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>

        <div class="help-h3">{{ $lang==='fr' ? 'Fichiers impliqués' : 'Files involved' }}</div>
        <table class="help-table">
          <tbody>
            @php
            $files = [
              ['app/Models/ActuatorCommand.php', $lang==='fr' ? 'Modèle + isSynced()' : 'Model + isSynced()'],
              ['app/Http/Controllers/ActuatorController.php', $lang==='fr' ? 'Route web toggle (POST dashboard/site/{site}/actuators/{parameter}/toggle)' : 'Web toggle route (POST dashboard/site/{site}/actuators/{parameter}/toggle)'],
              ['app/Http/Controllers/Api/ApiController.php', $lang==='fr' ? 'commands() + mise à jour reported_state dans store()' : 'commands() + reported_state update inside store()'],
              ['resources/views/dashboard/site.blade.php', $lang==='fr' ? 'Cartes switch-card, modal de confirmation, JS openActuatorModal/closeActuatorModal' : 'switch-card cards, confirmation modal, openActuatorModal/closeActuatorModal JS'],
              ['resources/views/admin/parameters.blade.php', $lang==='fr' ? 'Champs Data Type=Switch / Control Type, JS onDataTypeChange' : 'Data Type=Switch / Control Type fields, onDataTypeChange JS'],
            ];
            @endphp
            @foreach($files as $row)
            <tr><td><span class="help-code-inline">{{ $row[0] }}</span></td><td style="font-size:12px">{{ $row[1] }}</td></tr>
            @endforeach
          </tbody>
        </table>

        <div class="help-h2">{{ $lang==='fr' ? '9. Détection "No data" / "Stale" (appareils hors-ligne)' : '9. "No data" / "Stale" detection (offline devices)' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Chaque <span class="help-code-inline">SiteCategory</span> a un champ <span class="help-code-inline">offline_threshold_minutes</span> (défaut: 5, modifiable via Admin → Categories → Edit). Dans <span class="help-code-inline">site.blade.php</span>, pour chaque paramètre sensor on calcule l'âge de la dernière lecture (<span class="help-code-inline">$readingsAt[$slug]</span>) et on compare à ce seuil.
          @else
          Each <span class="help-code-inline">SiteCategory</span> has an <span class="help-code-inline">offline_threshold_minutes</span> field (default: 5, editable via Admin → Categories → Edit). In <span class="help-code-inline">site.blade.php</span>, for each sensor parameter we compute the age of the latest reading (<span class="help-code-inline">$readingsAt[$slug]</span>) and compare it to this threshold.
          @endif
        </p>

        <table class="help-table">
          <thead><tr><th>{{ $lang==='fr' ? 'État' : 'State' }}</th><th>{{ $lang==='fr' ? 'Condition' : 'Condition' }}</th><th>{{ $lang==='fr' ? 'Affichage' : 'Display' }}</th></tr></thead>
          <tbody>
            @php
            $states = $lang==='fr' ? [
              ['No data', '$val === null (jamais de sensor_reading)', 'Badge gris pointillé "No data"'],
              ['Stale', '$val existe mais $paramAt trop ancien', 'Badge orange "Stale (Xm)" avec tooltip "Last data: X ago"'],
              ['Online', '$val existe et $paramAt récent', 'Affichage normal (valeur + badge habituel)'],
            ] : [
              ['No data', '$val === null (no sensor_reading ever)', 'Dashed gray "No data" badge'],
              ['Stale', '$val exists but $paramAt is too old', 'Amber "Stale (Xm)" badge with "Last data: X ago" tooltip'],
              ['Online', '$val exists and $paramAt is recent', 'Normal display (value + usual badge)'],
            ];
            @endphp
            @foreach($states as $row)
            <tr>
              <td><span class="help-code-inline">{{ $row[0] }}</span></td>
              <td style="font-size:11px;color:var(--muted)">{{ $row[1] }}</td>
              <td>{{ $row[2] }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>

        <div class="help-tip">
          <span>
            @if($lang==='fr')
            Pour les switches controllable, le même seuil (au niveau catégorie) s'applique à <span class="help-code-inline">reported_at</span> : si <span class="help-code-inline">desired == reported</span> mais que le rapport date de trop longtemps, le pill affiche <strong>"Stale"</strong> au lieu de <strong>"Synced"</strong> — l'appareil a peut-être perdu la connexion depuis.
            @else
            For controllable switches, the same category-level threshold applies to <span class="help-code-inline">reported_at</span>: if <span class="help-code-inline">desired == reported</span> but the report is too old, the pill shows <strong>"Stale"</strong> instead of <strong>"Synced"</strong> — the device may have lost connection since.
            @endif
          </span>
        </div>
      </div>
    </div>

    {{-- SECTION 6 — DEPLOYMENT --}}
    <div class="help-section" id="section-5">
      <div class="help-card">
        <div class="help-h1">{{ $lang==='fr' ? 'Déploiement (VPS / AAPanel)' : 'Deployment (VPS / AAPanel)' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          La plateforme est hébergée sur un VPS Hostinger géré via <strong>AAPanel</strong> (Nginx + PHP 8.3 + MySQL).
          @else
          The platform is hosted on a Hostinger VPS managed via <strong>AAPanel</strong> (Nginx + PHP 8.3 + MySQL).
          @endif
        </p>

        <div class="help-h2">{{ $lang==='fr' ? 'Informations serveur' : 'Server Info' }}</div>
        <table class="help-table">
          <tbody>
            <tr><td><strong>URL</strong></td><td><span class="help-code-inline">https://apvmaga.saratechniger.com</span></td></tr>
            <tr><td><strong>{{ $lang==='fr'?'Panneau':'Panel' }}</strong></td><td>AAPanel</td></tr>
            <tr><td><strong>{{ $lang==='fr'?'Chemin du projet':'Project path' }}</strong></td><td><span class="help-code-inline">/www/wwwroot/apvmaga</span></td></tr>
            <tr><td><strong>PHP</strong></td><td>8.3</td></tr>
            <tr><td><strong>{{ $lang==='fr'?'Base de données':'Database' }}</strong></td><td>MySQL — <span class="help-code-inline">apvmaga_user</span></td></tr>
            <tr><td><strong>SSL</strong></td><td>{{ $lang==='fr' ? "Let's Encrypt (auto-renouvelé)" : "Let's Encrypt (auto-renewed)" }}</td></tr>
          </tbody>
        </table>

        <div class="help-h2">{{ $lang==='fr' ? 'Procédure de mise à jour' : 'Update Procedure' }}</div>
        <ol class="help-steps">
          @php
          $deploy = $lang==='fr' ? [
            'En local, installer les dépendances de production : <span class="help-code-inline">composer install --optimize-autoloader --no-dev</span>',
            'Compresser le projet (sans node_modules) : <span class="help-code-inline">Compress-Archive -Path C:\Projects\apv-maga\* -DestinationPath apvmaga-update.zip -Force</span>',
            'AAPanel Files naviguer vers <span class="help-code-inline">/www/wwwroot/apvmaga</span>',
            'Uploader le ZIP puis l\'extraire (écrase les fichiers existants)',
            'Supprimer les anciens fichiers ZIP pour libérer de l\'espace',
            'Dans le terminal VPS, vider les caches : <span class="help-code-inline">php artisan view:clear && php artisan cache:clear && php artisan config:clear</span>',
            'Si les routes/migrations ont changé : <span class="help-code-inline">php artisan route:clear</span> et <span class="help-code-inline">php artisan migrate --force</span>',
            'Recharger le site et vérifier visuellement',
          ] : [
            'Locally, install production dependencies: <span class="help-code-inline">composer install --optimize-autoloader --no-dev</span>',
            'Compress the project (excluding node_modules): <span class="help-code-inline">Compress-Archive -Path C:\Projects\apv-maga\* -DestinationPath apvmaga-update.zip -Force</span>',
            'AAPanel Files navigate to <span class="help-code-inline">/www/wwwroot/apvmaga</span>',
            'Upload the ZIP then extract it (overwrites existing files)',
            'Delete old ZIP files to free up space',
            'In the VPS terminal, clear caches: <span class="help-code-inline">php artisan view:clear && php artisan cache:clear && php artisan config:clear</span>',
            'If routes/migrations changed: <span class="help-code-inline">php artisan route:clear</span> and <span class="help-code-inline">php artisan migrate --force</span>',
            'Reload the site and check visually',
          ];
          @endphp
          @foreach($deploy as $step)
          <li>{!! $step !!}</li>
          @endforeach
        </ol>

        <div class="help-h2">{{ $lang==='fr' ? 'Pièges connus (AAPanel)' : 'Known Pitfalls (AAPanel)' }}</div>
        <table class="help-table">
          <thead><tr><th>{{ $lang==='fr' ? 'Problème' : 'Issue' }}</th><th>{{ $lang==='fr' ? 'Solution' : 'Fix' }}</th></tr></thead>
          <tbody>
            @php
            $pitfalls = $lang==='fr' ? [
              ['Nginx ne redémarre pas (listen 443 quic non supporté)', 'Retirer la directive "listen 443 quic;" du fichier vhost Nginx, puis recharger avec kill -HUP (pas systemctl).'],
              ['Erreur "open_basedir" empêchant l\'accès à vendor/autoload.php', 'Désactiver "Anti-XSS Attack (Open Basedir)" dans les réglages du site AAPanel.'],
              ['Tables cache / cache_locks manquantes (MariaDB)', 'Le mot "key" est réservé en MariaDB — entourer de backticks lors de la création manuelle des tables.'],
              ['Mots de passe / clés contenant 1 vs l, 0 vs O', 'Vérifier caractère par caractère en cas d\'erreur 401 sur l\'API — confusions fréquentes dans les clés générées.'],
            ] : [
              ['Nginx fails to restart (listen 443 quic unsupported)', 'Remove the "listen 443 quic;" directive from the Nginx vhost file, then reload with kill -HUP (not systemctl).'],
              ['"open_basedir" error blocking access to vendor/autoload.php', 'Disable "Anti-XSS Attack (Open Basedir)" in the AAPanel site settings.'],
              ['Missing cache / cache_locks tables (MariaDB)', 'The word "key" is reserved in MariaDB — wrap it in backticks when creating tables manually.'],
              ['Passwords / keys with 1 vs l, 0 vs O', 'Check character by character on a 401 API error — frequent confusion in generated keys.'],
            ];
            @endphp
            @foreach($pitfalls as $row)
            <tr><td>{{ $row[0] }}</td><td>{{ $row[1] }}</td></tr>
            @endforeach
          </tbody>
        </table>

        <div class="help-h2">{{ $lang==='fr' ? 'Réinitialiser les données (démo / tests)' : 'Resetting data (demo / testing)' }}</div>
        <div class="help-code">cd /www/wwwroot/apvmaga
php artisan migrate:fresh --force
php artisan db:seed --class=DatabaseSeeder --force</div>
        <div class="help-warn">
          <span style="flex-shrink:0;display:flex;align-items:center;margin-top:1px">{!! $iconWarning !!}</span>
          <span>{{ $lang==='fr' ? 'Cette commande supprime TOUTES les données (sites, lectures, utilisateurs sauf admin). À utiliser uniquement en phase de test.' : 'This command deletes ALL data (sites, readings, users except admin). Use only during testing phase.' }}</span>
        </div>
      </div>
    </div>

    {{-- SECTION 7 — ESP32 / IOT --}}
    <div class="help-section" id="section-6">
      <div class="help-card">
        <div class="help-h1">{{ $lang==='fr' ? 'Intégration ESP32 / IoT' : 'ESP32 / IoT Integration' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Chaque ESP32 (ou Arduino + module WiFi) peut envoyer périodiquement ses lectures vers l'API. Un ESP32 peut couvrir une ou plusieurs catégories du même site.
          @else
          Each ESP32 (or Arduino + WiFi module) can periodically send its readings to the API. One ESP32 can cover one or several categories of the same site.
          @endif
        </p>

        <div class="help-h2">{{ $lang==='fr' ? 'Code Arduino / ESP32 (HTTPClient)' : 'Arduino / ESP32 Code (HTTPClient)' }}</div>
        <div class="help-code">#include &lt;WiFi.h&gt;
#include &lt;HTTPClient.h&gt;

const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";

const char* apiUrl = "https://apvmaga.saratechniger.com/api/sensors/fass-6a15122aee52e/solar";
const char* apiKey = "apv-6AJgzjBQKLij27vznGOCPaf2ASoEp9mp";

void setup() {
  Serial.begin(115200);
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi connected");
}

void loop() {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(apiUrl);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("X-API-Key", apiKey);

    // {{ $lang==='fr' ? 'Lire les capteurs ici (exemple)' : 'Read sensors here (example)' }}
    float output = readSolarOutput();
    float irradiance = readIrradiance();
    float panelTemp = readPanelTemperature();
    float efficiency = computeEfficiency();

    String body = "{";
    body += "\"solar_output\":" + String(output, 2) + ",";
    body += "\"solar_irradiance\":" + String(irradiance, 0) + ",";
    body += "\"panel_temperature\":" + String(panelTemp, 1) + ",";
    body += "\"system_efficiency\":" + String(efficiency, 1);
    body += "}";

    int httpCode = http.POST(body);
    Serial.printf("HTTP %d: %s\n", httpCode, http.getString().c_str());
    http.end();
  }

  delay(60000); // {{ $lang==='fr' ? 'Envoi toutes les 60 secondes' : 'Send every 60 seconds' }}
}</div>

        <div class="help-h2">{{ $lang==='fr' ? 'Code Arduino / ESP32 — Avec actionneurs (vannes, pompes...)' : 'Arduino / ESP32 Code — With actuators (valves, pumps...)' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Pour une catégorie contenant des paramètres <span class="help-code-inline">data_type=switch</span> + <span class="help-code-inline">control_type=controllable</span> (ex: Irrigation avec <span class="help-code-inline">valve_1</span>, <span class="help-code-inline">cooling_fan</span>), la boucle <span class="help-code-inline">loop()</span> fait deux choses supplémentaires : <strong>1)</strong> récupérer l'état souhaité via <span class="help-code-inline">GET /api/commands</span> et piloter le relais, <strong>2)</strong> inclure l'état réel du relais dans le <span class="help-code-inline">POST /api/sensors</span> habituel.
          @else
          For a category containing <span class="help-code-inline">data_type=switch</span> + <span class="help-code-inline">control_type=controllable</span> parameters (e.g. Irrigation with <span class="help-code-inline">valve_1</span>, <span class="help-code-inline">cooling_fan</span>), the <span class="help-code-inline">loop()</span> does two extra things: <strong>1)</strong> fetch the desired state via <span class="help-code-inline">GET /api/commands</span> and drive the relay, <strong>2)</strong> include the relay's real state in the regular <span class="help-code-inline">POST /api/sensors</span> call.
          @endif
        </p>
        <div class="help-code">const char* commandsUrl = "https://apvmaga.saratechniger.com/api/commands/fass-6a15122aee52e/irrigation";
const char* sensorsUrl  = "https://apvmaga.saratechniger.com/api/sensors/fass-6a15122aee52e/irrigation";
const char* apiKey = "apv-6AJgzjBQKLij27vznGOCPaf2ASoEp9mp";

#define VALVE1_PIN 26
#define FAN_PIN    27

void loop() {
  if (WiFi.status() == WL_CONNECTED) {

    // 1) {{ $lang==='fr' ? 'Récupérer les commandes (desired_state) et piloter les relais' : 'Fetch commands (desired_state) and drive the relays' }}
    HTTPClient httpCmd;
    httpCmd.begin(commandsUrl);
    httpCmd.addHeader("X-API-Key", apiKey);
    if (httpCmd.GET() == 200) {
      String payload = httpCmd.getString();
      // {{ $lang==='fr' ? 'parser le JSON et lire commands.valve_1.desired_state, commands.cooling_fan.desired_state' : 'parse JSON and read commands.valve_1.desired_state, commands.cooling_fan.desired_state' }}
      int valve1Desired = parseDesiredState(payload, "valve_1");
      int fanDesired    = parseDesiredState(payload, "cooling_fan");
      digitalWrite(VALVE1_PIN, valve1Desired ? HIGH : LOW);
      digitalWrite(FAN_PIN,    fanDesired    ? HIGH : LOW);
    }
    httpCmd.end();

    // 2) {{ $lang==='fr' ? 'Lire les capteurs + l\'état réel des relais' : 'Read sensors + the relays\' real state' }}
    float soilMoisture = readSoilMoisture();
    int valve1State = digitalRead(VALVE1_PIN);
    int fanState    = digitalRead(FAN_PIN);

    HTTPClient httpData;
    httpData.begin(sensorsUrl);
    httpData.addHeader("Content-Type", "application/json");
    httpData.addHeader("X-API-Key", apiKey);

    String body = "{";
    body += "\"soil_moisture\":" + String(soilMoisture, 1) + ",";
    body += "\"valve_1\":" + String(valve1State) + ",";
    body += "\"cooling_fan\":" + String(fanState);
    body += "}";

    httpData.POST(body);
    httpData.end();
  }

  delay(15000); // {{ $lang==='fr' ? 'Cycle complet toutes les 15 secondes' : 'Full cycle every 15 seconds' }}
}</div>

        <div class="help-tip">
          <span>
            @if($lang==='fr')
            La latence "Pending…" affichée sur le dashboard correspond exactement à ce délai de <span class="help-code-inline">delay(15000)</span> — c'est le temps entre le clic sur le toggle et la prochaine itération de l'ESP32.
            @else
            The "Pending…" status shown on the dashboard corresponds exactly to this <span class="help-code-inline">delay(15000)</span> — it's the time between clicking the toggle and the ESP32's next iteration.
            @endif
          </span>
        </div>

        <div class="help-h2">{{ $lang==='fr' ? 'Bonnes pratiques' : 'Best Practices' }}</div>
        <table class="help-table">
          <thead><tr><th>{{ $lang==='fr' ? 'Pratique' : 'Practice' }}</th><th>{{ $lang==='fr' ? 'Pourquoi' : 'Why' }}</th></tr></thead>
          <tbody>
            @php
            $bp = $lang==='fr' ? [
              ['Intervalle 30-60 s', 'Suffisant pour le suivi agrivoltaïque, évite de surcharger l\'API.'],
              ['Vérifier le code HTTP', '200/201 = succès, 401 = clé API invalide, 422 = champ(s) incorrect(s).'],
              ['Un slug = une clé JSON', 'Les slugs sont visibles dans l\'onglet API Key du site (sensible à la casse).'],
              ['Reconnexion WiFi automatique', 'Utiliser WiFi.status() pour relancer la connexion si perdue.'],
              ['Watchdog / deep sleep', 'Pour les installations alimentées par batterie/solaire, utiliser le deep sleep ESP32 entre les envois.'],
              ['Intervalle &lt; seuil offline', 'L\'intervalle d\'envoi doit être inférieur au "Offline threshold" de la catégorie (Admin → Categories → Edit), sinon les paramètres affichent "Stale" en permanence.'],
            ] : [
              ['30-60 s interval', 'Sufficient for agrivoltaic monitoring, avoids overloading the API.'],
              ['Check the HTTP code', '200/201 = success, 401 = invalid API key, 422 = incorrect field(s).'],
              ['One slug = one JSON key', 'Slugs are visible in the site\'s API Key tab (case-sensitive).'],
              ['Automatic WiFi reconnection', 'Use WiFi.status() to reconnect if the connection drops.'],
              ['Watchdog / deep sleep', 'For battery/solar-powered setups, use ESP32 deep sleep between sends.'],
              ['Interval &lt; offline threshold', 'The send interval must be shorter than the category\'s "Offline threshold" (Admin → Categories → Edit), otherwise parameters permanently show "Stale".'],
            ];
            @endphp
            @foreach($bp as $row)
            <tr><td><strong>{{ $row[0] }}</strong></td><td>{{ $row[1] }}</td></tr>
            @endforeach
          </tbody>
        </table>

        <div class="help-h2">{{ $lang==='fr' ? 'Plusieurs ESP32 sur un même site' : 'Multiple ESP32 on the same site' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Chaque ESP32 peut envoyer vers une catégorie différente avec la <strong>même clé API</strong> (celle du site). Exemple :
          @else
          Each ESP32 can send to a different category using the <strong>same API key</strong> (the site's key). Example:
          @endif
        </p>
        <table class="help-table">
          <tbody>
            <tr><td><strong>ESP32 #1</strong></td><td> <span class="help-code-inline">/api/sensors/fass-xxx/solar</span></td></tr>
            <tr><td><strong>ESP32 #2</strong></td><td> <span class="help-code-inline">/api/sensors/fass-xxx/weather</span></td></tr>
            <tr><td><strong>ESP32 #3</strong></td><td> <span class="help-code-inline">/api/sensors/fass-xxx/irrigation</span></td></tr>
          </tbody>
        </table>

        <div class="help-h2">{{ $lang==='fr' ? 'Script de test multi-sites (PowerShell)' : 'Multi-site test script (PowerShell)' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Le dépôt fournit <span class="help-code-inline">test-api.ps1</span> qui simule plusieurs ESP32 en envoyant des données aléatoires toutes les 10 secondes pour un ou plusieurs sites — utile pour valider la configuration avant le déploiement matériel.
          @else
          The repo provides <span class="help-code-inline">test-api.ps1</span> which simulates several ESP32 by sending random data every 10 seconds for one or more sites — useful to validate configuration before hardware deployment.
          @endif
        </p>
        <div class="help-code">cd "C:\Users\&lt;user&gt;\Downloads"
.\test-api.ps1</div>

        <div class="help-tip">
          <span>
            @if($lang==='fr')
            Avant de brancher un vrai capteur, testez son endpoint avec <span class="help-code-inline">curl</span> ou Postman pour confirmer le format JSON attendu, puis intégrez le code Arduino.
            @else
            Before wiring a real sensor, test its endpoint with <span class="help-code-inline">curl</span> or Postman to confirm the expected JSON format, then integrate the Arduino code.
            @endif
          </span>
        </div>
      </div>
    </div>

  </div>
</div>

@endsection

@push('scripts')
<script>
function showSection(idx, btn) {
  document.querySelectorAll('.help-section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.help-nav-item').forEach(b => b.classList.remove('active'));
  document.getElementById('section-' + idx).classList.add('active');
  btn.classList.add('active');
}

function setLang(lang) {
  window.location.href = '?lang=' + lang;
}
</script>
@endpush
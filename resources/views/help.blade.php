@extends('layouts.dashboard')
@section('page-title', 'Help & Documentation')
@section('page-crumb', 'User Manual')

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
.faq-item{border:1px solid var(--border);border-radius:8px;margin-bottom:8px;overflow:hidden}
.faq-q{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;cursor:pointer;font-size:13px;font-weight:500;background:var(--surface);transition:background .15s}
.faq-q:hover{background:var(--bg)}
.faq-a{display:none;padding:12px 16px;font-size:13px;color:var(--muted);background:var(--bg);line-height:1.7;border-top:1px solid var(--border)}
.faq-item.open .faq-a{display:block}
.faq-item.open .faq-q{background:var(--blue-bg);color:var(--blue)}
.lang-toggle{display:flex;gap:4px;background:var(--bg);border:1px solid var(--border);border-radius:7px;padding:3px}
.lang-btn{font-family:'DM Sans',sans-serif;font-size:12px;font-weight:500;padding:4px 12px;border:none;border-radius:5px;cursor:pointer;color:var(--muted);background:transparent;transition:all .15s}
.lang-btn.active{background:var(--blue);color:#fff}
.role-card{display:flex;align-items:flex-start;gap:12px;padding:14px;border-radius:8px;border:1px solid var(--border);margin-bottom:8px}
.role-icon{width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.endpoint-row{display:flex;gap:10px;align-items:center;padding:10px 0;border-bottom:1px solid var(--border);font-size:12px}
.endpoint-row:last-child{border-bottom:none}
.method-badge{font-family:'DM Mono',monospace;font-size:10px;font-weight:700;padding:3px 8px;border-radius:4px;flex-shrink:0}
.method-post{background:#dcfce7;color:#15803d}
.method-get{background:#dbeafe;color:#1d6ed8}
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
          ['1', 'Introduction'],
          ['2', 'Connexion & Rôles'],
          ['3', 'Dashboard'],
          ['4', 'Détail d\'un Site'],
          ['5', 'Saisie Manuelle'],
          ['6', 'Raw Data & Export'],
          ['7', 'API ESP32'],
          ['8', 'Administration'],
          ['9', 'Sources & Alertes'],
          ['10', 'FAQ'],
        ] : [
          ['1', 'Introduction'],
          ['2', 'Login & Roles'],
          ['3', 'Dashboard'],
          ['4', 'Site Detail'],
          ['5', 'Manual Input'],
          ['6', 'Raw Data & Export'],
          ['7', 'ESP32 API'],
          ['8', 'Administration'],
          ['9', 'Sources & Alerts'],
          ['10', 'FAQ'],
        ];
      @endphp
      @foreach($sections as $i => $s)
      <button class="help-nav-item {{ $i===0 ? 'active' : '' }}" onclick="showSection({{ $i }}, this)">
        <span class="nav-num">{{ $s[0] }}</span>
        <span>{{ $s[1] }}</span>
      </button>
      @endforeach
    </div>

    {{-- PDF Download --}}
    <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--border)">
      <div style="font-size:10px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">
        {{ $lang==='fr' ? 'Télécharger' : 'Download' }}
      </div>
      <a href="/docs/apv-maga-manual-{{ $lang }}.pdf" target="_blank"
         style="display:flex;align-items:center;gap:6px;padding:8px 10px;border-radius:7px;border:1px solid var(--border);background:var(--bg);color:var(--text);text-decoration:none;font-size:12px;font-weight:500;transition:all .15s"
         onmouseover="this.style.background='var(--surface)'" onmouseout="this.style.background='var(--bg)'">
        {{ $lang==='fr' ? 'Manuel PDF' : 'PDF Manual' }} ({{ strtoupper($lang) }})
      </a>
    </div>
  </div>

  {{-- Content --}}
  <div>

    {{-- SECTION 1 — INTRODUCTION --}}
    <div class="help-section active" id="section-0">
      <div class="help-card">
        <div class="help-h1">{{ $lang==='fr' ? 'Introduction à APV-MaGa' : 'Introduction to APV-MaGa' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          APV-MaGa est un tableau de bord de monitoring développé par <strong>Saratech</strong> pour la collecte
          et la surveillance en temps réel des installations agrivoltaïques, partout dans le monde.
          <br><br>
          La plateforme récupère automatiquement, toutes les 10 minutes, les mesures des capteurs connectés à <strong>ThingsBoard</strong> (humidité du sol, vannes, débit, cuve) et des stations <strong>LI-COR / HOBOnet</strong> (météo, PAR). Elle accepte aussi les données d'appareils IoT (ESP32, Arduino) via une
          API REST dynamique. Elle permet également la saisie manuelle par les agents de terrain pour les paramètres
          importants non collectés par les capteurs — tels que la taille des plantes, le nombre de feuilles,
          le rendement de récolte en kg ou en valeur monétaire.
          <br><br>
          APV-MaGa est conçue pour s'adapter à tous les contextes : gestion d'un site unique ou centralisation
          de plusieurs sites répartis sur différents pays, avec une interface multi-utilisateurs et multi-rôles.
          @else
          APV-MaGa is a monitoring dashboard developed by <strong>Saratech</strong> for the collection
          and real-time surveillance of agrivoltaic installations, anywhere in the world.
          <br><br>
          Every 10 minutes the platform automatically collects the measurements of the sensors connected to <strong>ThingsBoard</strong> (soil moisture, valves, flow, tank) and of the <strong>LI-COR / HOBOnet</strong> stations (weather, PAR). It also accepts data from IoT devices (ESP32, Arduino) via a
          dynamic REST API. It also allows manual entry by field agents for important parameters not collected
          by sensors — such as plant height, leaf count, harvest yield in kg or monetary value.
          <br><br>
          APV-MaGa is designed to adapt to all contexts: managing a single site or centralizing multiple sites
          across different countries, with a multi-user and multi-role interface.
          @endif
        </p>

        <div class="help-h2">{{ $lang==='fr' ? 'Fonctionnalités principales' : 'Key Features' }}</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          @php
          $features = $lang==='fr' ? [
            ['Temps réel', 'Affichage actualisé automatiquement, sans recharger la page'],
            ['Sources automatiques', 'ThingsBoard, LI-COR (HOBOnet) et API REST pour ESP32'],
            ['Graphiques', 'Visualisation Chart.js avec filtre de période'],
            ['Saisie manuelle', 'Formulaires structurés pour les données terrain'],
            ['Export', 'CSV et Excel par catégorie ou site complet'],
            ['Multi-utilisateurs', 'Rôles Admin, Agent, Observateur'],
            ['Alertes e-mail', 'Message quand une partie d\'un site cesse d\'envoyer des données'],
            ['Valeurs calculées', 'Volume de la cuve, consommation d\'eau journalière'],
          ] : [
            ['Real-time', 'Display refreshed automatically, without reloading the page'],
            ['Automatic sources', 'ThingsBoard, LI-COR (HOBOnet) and REST API for ESP32'],
            ['Charts', 'Chart.js visualization with period filter'],
            ['Manual entry', 'Structured forms for field data'],
            ['Export', 'CSV and Excel by category or full site'],
            ['Multi-user', 'Admin, Agent, Observer roles'],
            ['E-mail alerts', 'A message when part of a site stops sending data'],
            ['Computed values', 'Tank volume, daily water use'],
          ];
          @endphp
          @foreach($features as $f)
          <div style="display:flex;gap:10px;padding:12px;background:var(--bg);border-radius:8px;border:1px solid var(--border)">
            <div>
              <div style="font-size:12px;font-weight:600;margin-bottom:2px">{{ $f[0] }}</div>
              <div style="font-size:11px;color:var(--muted)">{{ $f[1] }}</div>
            </div>
          </div>
          @endforeach
        </div>

        <div class="help-h2">{{ $lang==='fr' ? 'Architecture du système' : 'System Architecture' }}</div>
        <div style="display:flex;align-items:center;justify-content:center;gap:8px;padding:20px;background:var(--bg);border-radius:8px;flex-wrap:wrap">
          @foreach(['ThingsBoard · LI-COR · ESP32', '→', $lang==='fr' ? 'Synchronisation / API' : 'Sync / API', '→', $lang==='fr' ? 'Base de données' : 'Database', '→', $lang==='fr' ? 'Dashboard & alertes' : 'Dashboard & alerts'] as $item)
          @if($item === '→')
          <span style="color:var(--muted);font-size:18px">→</span>
          @else
          <div style="padding:8px 14px;background:var(--surface);border:1px solid var(--border);border-radius:7px;font-size:12px;font-weight:500">{{ $item }}</div>
          @endif
          @endforeach
        </div>
      </div>
    </div>

    {{-- SECTION 2 — LOGIN & ROLES --}}
    <div class="help-section" id="section-1">
      <div class="help-card">
        <div class="help-h1">{{ $lang==='fr' ? 'Connexion & Rôles' : 'Login & Roles' }}</div>

        <div class="help-h2">{{ $lang==='fr' ? 'Connexion' : 'Login' }}</div>
        <p class="help-p">{{ $lang==='fr' ? 'Accédez à la plateforme via votre navigateur et entrez vos identifiants.' : 'Access the platform via your browser and enter your credentials.' }}</p>
        <div class="help-code">{{ url('/login') }}</div>
        <p class="help-p">{{ $lang==='fr' ? 'Pas encore de compte ? La page d\'inscription enregistre une demande : le compte reste en attente jusqu\'à son approbation par un administrateur, et vous recevez un e-mail dès qu\'il est activé.' : 'No account yet? The registration page records a request: the account stays pending until an administrator approves it, and you get an e-mail as soon as it is activated.' }}</p>

        <div class="help-h2">{{ $lang==='fr' ? 'Rôles utilisateurs' : 'User Roles' }}</div>
        <div class="role-card" style="background:var(--blue-bg);border-color:var(--blue-bd)">
          <div>
            <div style="font-size:13px;font-weight:600;color:var(--blue)">Admin</div>
            <div style="font-size:12px;color:var(--muted);margin-top:2px">
              {{ $lang==='fr' ? 'Accès complet — gestion sites, utilisateurs, catégories, paramètres, graphiques' : 'Full access — manage sites, users, categories, parameters, charts' }}
            </div>
          </div>
        </div>
        <div class="role-card" style="background:var(--green-bg);border-color:var(--green-bd)">
          <div>
            <div style="font-size:13px;font-weight:600;color:var(--green)">Agent</div>
            <div style="font-size:12px;color:var(--muted);margin-top:2px">
              {{ $lang==='fr' ? 'Sites assignés — consultation, saisie manuelle, export CSV/Excel' : 'Assigned sites — view, manual entry, CSV/Excel export' }}
            </div>
          </div>
        </div>
        <div class="role-card">
          <div>
            <div style="font-size:13px;font-weight:600">{{ $lang==='fr' ? 'Observateur' : 'Observer' }}</div>
            <div style="font-size:12px;color:var(--muted);margin-top:2px">
              {{ $lang==='fr' ? 'Sites assignés — consultation et export uniquement' : 'Assigned sites — view and export only' }}
            </div>
          </div>
        </div>
        <div class="help-tip">
          <span>{{ $lang==='fr' ? 'Le rôle Agent ou Observateur se choisit aussi site par site : une même personne peut saisir des données sur un site et seulement consulter un autre.' : 'The Agent or Observer role is also chosen site by site: the same person can enter data on one site and only view another.' }}</span>
        </div>
      </div>
    </div>

    {{-- SECTION 3 — DASHBOARD --}}
    <div class="help-section" id="section-2">
      <div class="help-card">
        <div class="help-h1">{{ $lang==='fr' ? 'Dashboard — Vue d\'ensemble' : 'Dashboard — Overview' }}</div>
        <p class="help-p">{{ $lang==='fr' ? 'La page d\'accueil affiche tous vos sites avec leurs indicateurs clés en temps réel.' : 'The home page displays all your sites with their real-time key indicators.' }}</p>

        <div class="help-h2">{{ $lang==='fr' ? 'Éléments de l\'interface' : 'Interface Elements' }}</div>
        <table class="help-table">
          <thead><tr><th>{{ $lang==='fr' ? 'Élément' : 'Element' }}</th><th>{{ $lang==='fr' ? 'Description' : 'Description' }}</th></tr></thead>
          <tbody>
            @php
            $ui = $lang==='fr' ? [
              ['Sidebar', 'Liste des sites avec statut coloré (vert=actif, orange=alerte). Sur ordinateur elle se réduit aux icônes ; le choix est mémorisé.'],
              ['Badge Live', 'Vert = des données arrivent ; orange = la dernière donnée est trop ancienne ; gris = aucune donnée. Survolez-le pour voir l\'heure de la dernière lecture.'],
              ['Topbar', 'Titre, horloge UTC, filtre période 1H/6H/24H/7D/Custom (dates précises), boutons CSV/Excel'],
              ['Carte de site', 'Indicateurs disponibles pour le site (humidité du sol, cuve, météo, PAR…) et son statut : Normal, Warning, Critical, No recent data ou No data'],
              ['Sparkline', 'Mini-graphique de l\'indicateur principal du site'],
              ['Cross-Site Trends', 'Graphiques comparatifs entre tous les sites'],
            ] : [
              ['Sidebar', 'Sites list with colored status (green=active, orange=alert). On a computer it collapses to icons; the choice is remembered.'],
              ['Live badge', 'Green = data is arriving; orange = the latest data is too old; grey = no data. Hover it to see the time of the last reading.'],
              ['Topbar', 'Title, UTC clock, period filter 1H/6H/24H/7D/Custom (exact dates), CSV/Excel buttons'],
              ['Site Card', 'Indicators available for the site (soil moisture, tank, weather, PAR…) and its status: Normal, Warning, Critical, No recent data or No data'],
              ['Sparkline', 'Mini-chart of the site\'s main indicator'],
              ['Cross-Site Trends', 'Comparative charts across all sites'],
            ];
            @endphp
            @foreach($ui as $row)
            <tr><td><strong>{{ $row[0] }}</strong></td><td>{{ $row[1] }}</td></tr>
            @endforeach
          </tbody>
        </table>

        <div class="help-tip">
          <span>{{ $lang==='fr' ? 'L\'affichage se rafraîchit automatiquement sans recharger la page : chaque minute ici, toutes les 30 secondes sur la page d\'un site.' : 'The display refreshes automatically without reloading the page: every minute here, every 30 seconds on a site\'s page.' }}</span>
        </div>

        <div class="help-h2">{{ $lang==='fr' ? 'Période personnalisée' : 'Custom Date Range' }}</div>
        <p class="help-p">
          {{ $lang==='fr'
            ? 'En plus des boutons 1H/6H/24H/7D, le bouton "Custom" ouvre un sélecteur de dates (début/fin) pour consulter n\'importe quelle période passée sur les graphiques, les données brutes et les exports. Une fois appliquée, la période choisie s\'affiche sous forme de badge bleu (ex. "Jul 1 → Jul 5") à côté des boutons — cliquez sur la croix (✕) du badge pour revenir à la période par défaut.'
            : 'In addition to the 1H/6H/24H/7D buttons, the "Custom" button opens a date picker (start/end) to view any past period across charts, raw data, and exports. Once applied, the chosen period shows as a blue badge (e.g. "Jul 1 → Jul 5") next to the buttons — click the badge\'s × to clear it and return to the default period.' }}
        </p>
      </div>
    </div>

    {{-- SECTION 4 — SITE DETAIL --}}
    <div class="help-section" id="section-3">
      <div class="help-card">
        <div class="help-h1">{{ $lang==='fr' ? 'Détail d\'un Site' : 'Site Detail' }}</div>
        <p class="help-p">{{ $lang==='fr' ? 'Cliquez sur un site dans la sidebar pour accéder à son tableau de bord détaillé.' : 'Click on a site in the sidebar to access its detailed dashboard.' }}</p>

        <div class="help-h2">{{ $lang==='fr' ? 'Onglets disponibles' : 'Available Tabs' }}</div>
        @php
        $tabs = $lang==='fr' ? [
          ['Solar, Water...', 'Onglets par catégorie configurée dans l\'Admin Panel'],
          ['Raw Data', 'Données brutes groupées par horodatage avec filtre et pagination'],
          ['Manual Input', 'Formulaires de saisie manuelle (si paramètres manuels configurés)'],
          ['API Key', 'Clé API et documentation des endpoints pour ESP32'],
        ] : [
          ['Solar, Water...', 'Tabs per category configured in the Admin Panel'],
          ['Raw Data', 'Raw data grouped by timestamp with filter and pagination'],
          ['Manual Input', 'Manual entry forms (if manual parameters configured)'],
          ['API Key', 'API key and endpoint documentation for ESP32'],
        ];
        @endphp
        <table class="help-table">
          <thead><tr><th>{{ $lang==='fr' ? 'Onglet' : 'Tab' }}</th><th>{{ $lang==='fr' ? 'Contenu' : 'Content' }}</th></tr></thead>
          <tbody>
            @foreach($tabs as $tab)
            <tr><td><span class="help-code-inline">{{ $tab[0] }}</span></td><td>{{ $tab[1] }}</td></tr>
            @endforeach
          </tbody>
        </table>

        <div class="help-h2">KPI Cards</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
          <div style="padding:12px;border-radius:8px;border:1px solid var(--green-bd);background:var(--green-bg)">
            <div style="font-size:11px;font-weight:600;color:var(--green);margin-bottom:4px">Sensor</div>
            <div style="font-size:12px;color:var(--muted)">{{ $lang==='fr' ? 'Dernière valeur reçue du capteur ESP32' : 'Latest value received from ESP32 sensor' }}</div>
          </div>
          <div style="padding:12px;border-radius:8px;border:1px solid var(--blue-bd);background:var(--blue-bg)">
            <div style="font-size:11px;font-weight:600;color:var(--blue);margin-bottom:4px">Manual</div>
            <div style="font-size:12px;color:var(--muted)">{{ $lang==='fr' ? 'Dernière valeur saisie manuellement' : 'Latest manually entered value' }}</div>
          </div>
        </div>

        <div class="help-h2">{{ $lang==='fr' ? 'Statut des données (No data / Stale)' : 'Data status (No data / Stale)' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Chaque carte indique aussi la fraîcheur de la donnée. Si un paramètre n'a jamais reçu de donnée, ou si la dernière donnée est trop ancienne, un badge le signale directement sur la carte :
          @else
          Each card also shows how fresh its data is. If a parameter has never received data, or if the latest data is too old, a badge appears directly on the card:
          @endif
        </p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
          <div style="padding:12px;border-radius:8px;border:1px dashed var(--border);background:var(--bg)">
            <div style="font-size:11px;font-weight:600;color:var(--muted);margin-bottom:4px">No data</div>
            <div style="font-size:12px;color:var(--muted)">{{ $lang==='fr' ? 'Aucune donnée n\'a jamais été reçue pour ce paramètre' : 'No data has ever been received for this parameter' }}</div>
          </div>
          <div style="padding:12px;border-radius:8px;border:1px solid var(--amber-bd);background:var(--amber-bg)">
            <div style="font-size:11px;font-weight:600;color:var(--amber);margin-bottom:4px">Stale (Xm)</div>
            <div style="font-size:12px;color:var(--muted)">{{ $lang==='fr' ? 'Une donnée existe mais date de plus longtemps que le seuil de la catégorie (60 min pour les capteurs ThingsBoard, 12 h pour les stations LI-COR, 5 min sinon)' : 'Data exists but is older than the category\'s threshold (60 min for ThingsBoard sensors, 12 h for LI-COR stations, 5 min otherwise)' }}</div>
          </div>
          <div style="padding:12px;border-radius:8px;border:1px solid var(--amber-bd);background:var(--amber-bg)">
            <div style="font-size:11px;font-weight:600;color:var(--amber);margin-bottom:4px">Warning</div>
            <div style="font-size:12px;color:var(--muted)">{{ $lang==='fr' ? 'La valeur a franchi le "Warning threshold" configuré sur le paramètre' : 'The value has crossed the parameter\'s configured "Warning threshold"' }}</div>
          </div>
          <div style="padding:12px;border-radius:8px;border:1px solid var(--red-bd);background:var(--red-bg)">
            <div style="font-size:11px;font-weight:600;color:var(--red);margin-bottom:4px">Critical / Out of range</div>
            <div style="font-size:12px;color:var(--muted)">{{ $lang==='fr' ? 'La valeur a franchi le "Critical threshold", ou sort de la plage Min/Max configurée' : 'The value has crossed the "Critical threshold", or falls outside the configured Min/Max range' }}</div>
          </div>
        </div>
        <div class="help-tip">
          <span>{{ $lang==='fr' ? 'Survolez le badge pour voir l\'horodatage exact de la dernière donnée reçue ("Last data: X ago"). Le seuil "Stale" est configurable par catégorie dans Admin Panel → Categories → Edit. Les seuils Warning/Critical et la plage Min/Max se configurent par paramètre dans Admin Panel → Parameters.' : 'Hover the badge to see the exact timestamp of the latest data ("Last data: X ago"). The "Stale" threshold is configurable per category in Admin Panel → Categories → Edit. Warning/Critical thresholds and the Min/Max range are configured per parameter in Admin Panel → Parameters.' }}</span>
        </div>
        <div class="help-tip">
          <span>{{ $lang==='fr' ? 'Paramètres groupés : sur le dashboard, les paramètres partageant un même groupe (configuré dans Admin Panel → Parameters → Manage Groups) s\'affichent encadrés ensemble dans une boîte colorée, plutôt qu\'en cartes séparées.' : 'Grouped parameters: on the dashboard, parameters sharing the same group (configured in Admin Panel → Parameters → Manage Groups) are displayed boxed together in a colored panel, instead of as separate cards.' }}</span>
        </div>

        <div class="help-h2">{{ $lang==='fr' ? 'Switches & actionneurs (vannes, pompes, ventilateurs)' : 'Switches & actuators (valves, pumps, fans)' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Certains paramètres (type <span class="help-code-inline">Switch</span>) s'affichent comme un interrupteur ON/OFF au lieu d'une valeur numérique. Il existe deux variantes :
          @else
          Some parameters (type <span class="help-code-inline">Switch</span>) display as an ON/OFF toggle instead of a numeric value. There are two variants:
          @endif
        </p>

        <div class="role-card" style="background:var(--bg)">
          <div>
            <div style="font-size:13px;font-weight:600">{{ $lang==='fr' ? 'Lecture seule (readonly)' : 'Read-only' }}</div>
            <div style="font-size:12px;color:var(--muted);margin-top:2px">
              @if($lang==='fr')
              L'interrupteur est grisé et <strong>non cliquable</strong>. Son état ON/OFF reflète automatiquement la dernière donnée envoyée par le capteur. Badge : <strong>"Auto (sensor)"</strong>.
              @else
              The toggle is grayed out and <strong>not clickable</strong>. Its ON/OFF state automatically reflects the latest data sent by the sensor. Badge: <strong>"Auto (sensor)"</strong>.
              @endif
            </div>
          </div>
        </div>
        <div class="role-card" style="background:var(--blue-bg);border-color:var(--blue-bd)">
          <div>
            <div style="font-size:13px;font-weight:600;color:var(--blue)">{{ $lang==='fr' ? 'Pilotable (controllable)' : 'Controllable' }}</div>
            <div style="font-size:12px;color:var(--muted);margin-top:2px">
              @if($lang==='fr')
              L'interrupteur est <strong>cliquable</strong> (Admin et Agent uniquement — pas Observateur). Cliquer ouvre une fenêtre de confirmation, puis envoie une commande à l'appareil.
              @else
              The toggle is <strong>clickable</strong> (Admin and Agent only — not Observer). Clicking opens a confirmation popup, then sends a command to the device.
              @endif
            </div>
          </div>
        </div>

        <div class="help-h3">{{ $lang==='fr' ? 'Activer/désactiver un switch pilotable' : 'Turning a controllable switch on/off' }}</div>
        <ol class="help-steps">
          @php
          $switchSteps = $lang==='fr' ? [
            'Cliquez sur l\'interrupteur sur la carte du paramètre (ex: "Valve 1")',
            'Une fenêtre de confirmation s\'affiche : "Turn ON/OFF [nom] ?"',
            'Cliquez sur <strong>Confirm</strong> pour envoyer la commande, ou <strong>Cancel</strong> pour annuler (l\'interrupteur revient à son état précédent)',
            'L\'appareil applique le changement à sa prochaine connexion (quelques secondes à quelques minutes selon l\'appareil)',
          ] : [
            'Click the toggle on the parameter\'s card (e.g. "Valve 1")',
            'A confirmation popup appears: "Turn ON/OFF [name]?"',
            'Click <strong>Confirm</strong> to send the command, or <strong>Cancel</strong> to abort (the toggle reverts to its previous state)',
            'The device applies the change on its next check-in (a few seconds to a few minutes depending on the device)',
          ];
          @endphp
          @foreach($switchSteps as $step)
          <li>{!! $step !!}</li>
          @endforeach
        </ol>

        <div class="help-h3">{{ $lang==='fr' ? 'Indicateurs de synchronisation' : 'Sync indicators' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Sous chaque switch pilotable, un badge indique si la commande a bien été appliquée par l'appareil :
          @else
          Below each controllable switch, a badge shows whether the command was actually applied by the device:
          @endif
        </p>
        <table class="help-table">
          <thead><tr><th>{{ $lang==='fr' ? 'Badge' : 'Badge' }}</th><th>{{ $lang==='fr' ? 'Signification' : 'Meaning' }}</th></tr></thead>
          <tbody>
            @php
            $sync = $lang==='fr' ? [
              ['Synced', 'L\'appareil a confirmé le même état que celui demandé.'],
              ['Pending…', 'La commande a été envoyée mais l\'appareil n\'a pas encore confirmé — attendez sa prochaine connexion.'],
              ['No device report', 'Aucune donnée n\'a jamais été reçue de l\'appareil pour ce paramètre — impossible de confirmer.'],
              ['Stale', 'L\'état confirmé correspond à la commande, mais le dernier rapport date de trop longtemps — l\'appareil est peut-être hors ligne.'],
            ] : [
              ['Synced', 'The device confirmed the same state that was requested.'],
              ['Pending…', 'The command was sent but the device hasn\'t confirmed yet — wait for its next check-in.'],
              ['No device report', 'No data has ever been received from the device for this parameter — cannot confirm.'],
              ['Stale', 'The confirmed state matches the command, but the last report is too old — the device may be offline.'],
            ];
            @endphp
            @foreach($sync as $row)
            <tr><td><span class="help-code-inline">{{ $row[0] }}</span></td><td>{{ $row[1] }}</td></tr>
            @endforeach
          </tbody>
        </table>

        <div class="help-warn">
          <span style="flex-shrink:0;display:flex;align-items:center;margin-top:1px">{!! $iconWarning !!}</span>
          <span>{{ $lang==='fr' ? 'Si l\'appareil semble hors ligne, la fenêtre de confirmation affiche un avertissement avant d\'envoyer la commande — celle-ci sera quand même mise en attente et appliquée dès la reconnexion.' : 'If the device appears offline, the confirmation popup shows a warning before sending the command — it will still be queued and applied once the device reconnects.' }}</span>
        </div>
      </div>
    </div>

    {{-- SECTION 5 — MANUAL INPUT --}}
    <div class="help-section" id="section-4">
      <div class="help-card">
        <div class="help-h1">{{ $lang==='fr' ? 'Saisie Manuelle' : 'Manual Input' }}</div>
        <p class="help-p">{{ $lang==='fr' ? 'Permet aux agents de saisir des données de terrain qui ne proviennent pas de capteurs automatiques.' : 'Allows agents to enter field data that does not come from automatic sensors.' }}</p>

        <div class="help-h2">{{ $lang==='fr' ? 'Comment saisir des données' : 'How to Enter Data' }}</div>
        <ol class="help-steps">
          @php
          $steps = $lang==='fr' ? [
            'Cliquez sur l\'onglet <span class="help-code-inline">Manual Input</span> dans la barre d\'onglets',
            'Sélectionnez la <strong>date</strong> de la mesure terrain',
            'Entrez les <strong>valeurs numériques</strong> pour chaque paramètre',
            'Ajoutez des <strong>notes</strong> optionnelles pour contextualiser',
            'Cliquez sur <span class="help-code-inline">+ Save</span> pour enregistrer',
            'La page se recharge automatiquement sur <strong>Manual Input</strong>',
            'Les <strong>KPI cards</strong> se mettent à jour immédiatement',
          ] : [
            'Click the <span class="help-code-inline">Manual Input</span> tab in the tab bar',
            'Select the <strong>date</strong> of the field measurement',
            'Enter <strong>numeric values</strong> for each parameter',
            'Add optional <strong>notes</strong> for context',
            'Click <span class="help-code-inline">+ Save</span> to save',
            'The page automatically reloads on <strong>Manual Input</strong>',
            '<strong>KPI cards</strong> update immediately',
          ];
          @endphp
          @foreach($steps as $step)
          <li>{!! $step !!}</li>
          @endforeach
        </ol>

        <div class="help-warn">
          <span style="flex-shrink:0;display:flex;align-items:center;margin-top:1px">{!! $iconWarning !!}</span>
          <span>{{ $lang==='fr' ? 'L\'onglet Manual Input n\'apparaît que si le site possède au moins un paramètre configuré avec input_type = manual.' : 'The Manual Input tab only appears if the site has at least one parameter configured with input_type = manual.' }}</span>
        </div>
      </div>
    </div>

    {{-- SECTION 6 — RAW DATA & EXPORT --}}
    <div class="help-section" id="section-5">
      <div class="help-card">
        <div class="help-h1">Raw Data & Export</div>

        <div class="help-h2">{{ $lang==='fr' ? 'Fonctionnalités Raw Data' : 'Raw Data Features' }}</div>
        <table class="help-table">
          <thead><tr><th>{{ $lang==='fr' ? 'Fonctionnalité' : 'Feature' }}</th><th>{{ $lang==='fr' ? 'Description' : 'Description' }}</th></tr></thead>
          <tbody>
            @php
            $rd = $lang==='fr' ? [
              ['Filtre période', '1H, 6H, 24H, 7D, 30D — sans recharger la page'],
              ['Groupement', '1 ligne par horodatage d\'envoi capteur'],
              ['Badge type', 'Sensor (vert) ou Manual (bleu)'],
              ['Pagination', '20 enregistrements par page'],
              ['Export par catégorie', 'CSV ou XLS pour chaque catégorie'],
            ] : [
              ['Period filter', '1H, 6H, 24H, 7D, 30D — without reloading the page'],
              ['Grouping', '1 row per sensor send timestamp'],
              ['Type badge', 'Sensor (green) or Manual (blue)'],
              ['Pagination', '20 records per page'],
              ['Per-category export', 'CSV or XLS for each category'],
            ];
            @endphp
            @foreach($rd as $row)
            <tr><td><strong>{{ $row[0] }}</strong></td><td>{{ $row[1] }}</td></tr>
            @endforeach
          </tbody>
        </table>

        <div class="help-h2">{{ $lang==='fr' ? 'Options d\'export' : 'Export Options' }}</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          @php
          $exports = $lang==='fr' ? [
            ['CSV', 'Catégorie', 'Données d\'une catégorie au format CSV (UTF-8)'],
            ['XLS', 'Catégorie', 'Données d\'une catégorie en Excel (headers colorés)'],
            ['All CSV', 'Site', 'Toutes les catégories en un fichier CSV'],
            ['All Excel', 'Site', 'Toutes les catégories en feuilles Excel séparées'],
          ] : [
            ['CSV', 'Category', 'Category data as CSV (UTF-8)'],
            ['XLS', 'Category', 'Category data as Excel (colored headers)'],
            ['All CSV', 'Site', 'All categories in one CSV file'],
            ['All Excel', 'Site', 'All categories as separate Excel sheets'],
          ];
          @endphp
          @foreach($exports as $e)
          <div style="padding:12px;border:1px solid var(--border);border-radius:8px;background:var(--bg)">
            <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px">
              <span style="font-size:12px;font-weight:700;color:var(--blue)">{{ $e[0] }}</span>
              <span style="font-size:10px;padding:1px 6px;border-radius:4px;background:var(--blue-bg);color:var(--blue)">{{ $e[1] }}</span>
            </div>
            <div style="font-size:11px;color:var(--muted)">{{ $e[2] }}</div>
          </div>
          @endforeach
        </div>

        <div class="help-tip">
          <span>{{ $lang==='fr' ? 'Les boutons CSV/Excel de la topbar exportent selon la période active (1H/6H/24H/7D ou la période personnalisée choisie via "Custom").' : 'The topbar\'s CSV/Excel buttons export according to the active period (1H/6H/24H/7D or the custom range chosen via "Custom").' }}</span>
        </div>

        <div class="help-h2">{{ $lang==='fr' ? '6.3 Saved Records' : '6.3 Saved Records' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          La table <strong>Saved Records</strong> apparait dans l'onglet Raw Data et dans chaque onglet de catégorie
          ayant des paramètres manuels groupés. Elle permet de visualiser les saisies par groupe avec des colonnes dynamiques.
          @else
          The <strong>Saved Records</strong> table appears in the Raw Data tab and in each category tab
          with grouped manual parameters. It displays entries by group with dynamic columns.
          @endif
        </p>
        <table class="help-table">
          <thead><tr>
            <th>{{ $lang==='fr' ? 'Élément' : 'Element' }}</th>
            <th>{{ $lang==='fr' ? 'Description' : 'Description' }}</th>
          </tr></thead>
          <tbody>
            @php
            $sr = $lang==='fr' ? [
              ['Filtre groupe', 'Dropdown pour afficher un groupe spécifique (ex: Yield Data, Plant Measurements)'],
              ['Compteur', 'Nombre d\'enregistrements pour le groupe sélectionné'],
              ['Colonnes dynamiques', 'Les colonnes correspondent aux paramètres du groupe sélectionné'],
              ['CSV / XLS', 'Exporte uniquement les données du groupe actuellement sélectionné'],
            ] : [
              ['Group filter', 'Dropdown to display a specific group (e.g. Yield Data, Plant Measurements)'],
              ['Counter', 'Number of records for the selected group'],
              ['Dynamic columns', 'Columns correspond to the parameters of the selected group'],
              ['CSV / XLS', 'Exports only the data for the currently selected group'],
            ];
            @endphp
            @foreach($sr as $row)
            <tr><td><strong>{{ $row[0] }}</strong></td><td>{{ $row[1] }}</td></tr>
            @endforeach
          </tbody>
        </table>

      </div>
    </div>

    {{-- SECTION 7 — API ESP32 --}}
    <div class="help-section" id="section-6">
      <div class="help-card">
        <div class="help-h1">{{ $lang==='fr' ? 'API ESP32' : 'ESP32 API' }}</div>
        <p class="help-p">{{ $lang==='fr' ? 'API REST dynamique pour connecter vos capteurs ESP32/Arduino. Les capteurs ThingsBoard et les stations LI-COR n\'en ont pas besoin : ils sont synchronisés automatiquement (voir « Sources & Alertes »).' : 'Dynamic REST API for connecting your ESP32/Arduino sensors. ThingsBoard sensors and LI-COR stations do not need it: they are synchronised automatically (see "Sources & Alerts").' }}</p>

        <div class="help-h2">{{ $lang==='fr' ? 'Endpoints disponibles' : 'Available Endpoints' }}</div>
        <div style="background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:14px">
          <div class="endpoint-row">
            <span class="method-badge method-post">POST</span>
            <span class="help-code-inline">/api/sensors/{site}/{category}</span>
            <span style="color:var(--muted);font-size:11px">{{ $lang==='fr' ? 'Envoyer des données' : 'Send data' }}</span>
          </div>
          <div class="endpoint-row">
            <span class="method-badge method-get">GET</span>
            <span class="help-code-inline">/api/sensors/{site}/{category}/latest</span>
            <span style="color:var(--muted);font-size:11px">{{ $lang==='fr' ? 'Dernière lecture' : 'Latest reading' }}</span>
          </div>
          <div class="endpoint-row">
            <span class="method-badge method-get">GET</span>
            <span class="help-code-inline">/api/sensors/{site}/status</span>
            <span style="color:var(--muted);font-size:11px">{{ $lang==='fr' ? 'Statut global' : 'Global status' }}</span>
          </div>
          <div class="endpoint-row">
            <span class="method-badge method-get">GET</span>
            <span class="help-code-inline">/api/commands/{site}/{category}</span>
            <span style="color:var(--muted);font-size:11px">{{ $lang==='fr' ? 'Commandes des switches (vannes, pompes...)' : 'Switch commands (valves, pumps...)' }}</span>
          </div>
          <div class="endpoint-row">
            <span class="method-badge method-get">GET</span>
            <span class="help-code-inline">/api/sensors/{site}/{category}/history</span>
            <span style="color:var(--muted);font-size:11px">{{ $lang==='fr' ? 'Données brutes sur une période (recherche)' : 'Raw data over a date range (research)' }}</span>
          </div>
          <div class="endpoint-row">
            <span class="method-badge method-get">GET</span>
            <span class="help-code-inline">/api/sensors/{site}/{category}/stats</span>
            <span style="color:var(--muted);font-size:11px">{{ $lang==='fr' ? 'Statistiques agrégées (moyenne, min, max...)' : 'Aggregated statistics (average, min, max...)' }}</span>
          </div>
        </div>

        <div class="help-h2">{{ $lang==='fr' ? 'Authentification' : 'Authentication' }}</div>
        <div class="help-code">X-API-Key: apv-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx</div>
        <div class="help-tip">
          <span>{{ $lang==='fr' ? 'La clé se transmet uniquement dans cet en-tête ; elle n\'est pas acceptée dans l\'URL. Utilisez toujours une adresse en https://.' : 'The key is only accepted in this header, never in the URL. Always use an https:// address.' }}</span>
        </div>

        <div class="help-h2">{{ $lang==='fr' ? 'Exemple de requête' : 'Request Example' }}</div>
        <div class="help-code">POST /api/sensors/your-site-slug/solar
Content-Type: application/json
X-API-Key: apv-xxxxxxxxxxxxxxxxxx

{
  "solar_output": 4.2,
  "solar_irradiance": 890,
  "panel_temperature": 54.1
}</div>

        <div class="help-h2">{{ $lang==='fr' ? 'Code Arduino/ESP32' : 'Arduino/ESP32 Code' }}</div>
        <div class="help-code">HTTPClient http;
http.begin("https://domain.com/api/sensors/site-slug/solar");
http.addHeader("X-API-Key", "apv-xxxxxxxxx");
http.addHeader("Content-Type", "application/json");

String body = "{\"solar_output\":" + String(power) +
              ",\"solar_irradiance\":" + String(irr) + "}";
int code = http.POST(body);
http.end();</div>

        <div class="help-h2">{{ $lang==='fr' ? 'Commandes des switches (vannes, pompes...)' : 'Switch commands (valves, pumps...)' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Si la catégorie contient des paramètres <span class="help-code-inline">data_type=switch</span> et <span class="help-code-inline">control_type=controllable</span> (ex: <span class="help-code-inline">valve_1</span>), l'ESP32 doit interroger cet endpoint régulièrement pour savoir quel état appliquer :
          @else
          If the category contains <span class="help-code-inline">data_type=switch</span> and <span class="help-code-inline">control_type=controllable</span> parameters (e.g. <span class="help-code-inline">valve_1</span>), the ESP32 must poll this endpoint regularly to know which state to apply:
          @endif
        </p>
        <div class="help-code">GET /api/commands/your-site-slug/irrigation
X-API-Key: apv-xxxxxxxxxxxxxxxxxx

{
  "commands": {
    "valve_1": { "desired_state": 1, "reported_state": 0 }
  }
}</div>
        <p class="help-p">
          @if($lang==='fr')
          L'ESP32 active le relais selon <span class="help-code-inline">desired_state</span>, puis renvoie son état réel (<span class="help-code-inline">valve_1: 1</span>) dans le prochain <span class="help-code-inline">POST /api/sensors/...</span> — cela met automatiquement à jour <span class="help-code-inline">reported_state</span>.
          @else
          The ESP32 sets the relay according to <span class="help-code-inline">desired_state</span>, then sends its real state (<span class="help-code-inline">valve_1: 1</span>) in the next <span class="help-code-inline">POST /api/sensors/...</span> — this automatically updates <span class="help-code-inline">reported_state</span>.
          @endif
        </p>

        <div class="help-tip">
          <span>{{ $lang==='fr' ? 'Les slugs des paramètres doivent correspondre exactement à ceux configurés dans Admin Panel → Parameters.' : 'Parameter slugs must exactly match those configured in Admin Panel → Parameters.' }}</span>
        </div>

        <div class="help-h2">{{ $lang==='fr' ? 'Recherche & Analyse — données historiques' : 'Research & Analysis — historical data' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Pour les chercheurs et analyses statistiques, deux endpoints permettent d'extraire des données sur une période, sans passer par l'export CSV du dashboard.
          @else
          For researchers and statistical analysis, two endpoints let you extract data over a date range without using the dashboard's CSV export.
          @endif
        </p>

        <div class="help-h3">{{ $lang==='fr' ? 'Données brutes sur une période' : 'Raw data over a date range' }}</div>
        <div class="help-code">GET /api/sensors/{site}/{category}/history?from=2026-05-01&to=2026-05-08
X-API-Key: apv-xxxxxxxxxxxxxxxxxx</div>
        <p class="help-p">
          @if($lang==='fr')
          Retourne chaque lecture (1 ligne = 1 paramètre + 1 horodatage), idéal pour importer dans Python/pandas ou R. Maximum 90 jours par requête ; au-delà, utilisez les statistiques agrégées ci-dessous.
          @else
          Returns each reading (1 row = 1 parameter + 1 timestamp), ideal for importing into Python/pandas or R. Maximum 90 days per request; beyond that, use the aggregated statistics below.
          @endif
        </p>

        <div class="help-h3">{{ $lang==='fr' ? 'Statistiques agrégées (tendances long terme)' : 'Aggregated statistics (long-term trends)' }}</div>
        <div class="help-code">GET /api/sensors/{site}/{category}/stats?period=day&from=2026-05-01&to=2026-05-31
X-API-Key: apv-xxxxxxxxxxxxxxxxxx</div>
        <p class="help-p">
          @if($lang==='fr')
          Retourne, pour chaque paramètre numérique, la moyenne/min/max/somme regroupées par <span class="help-code-inline">period</span> (<span class="help-code-inline">hour</span>, <span class="help-code-inline">day</span>, <span class="help-code-inline">week</span> ou <span class="help-code-inline">month</span>). Pratique pour visualiser une tendance sur plusieurs mois sans télécharger toutes les lectures.
          @else
          Returns, for each numeric parameter, the average/min/max/sum grouped by <span class="help-code-inline">period</span> (<span class="help-code-inline">hour</span>, <span class="help-code-inline">day</span>, <span class="help-code-inline">week</span>, or <span class="help-code-inline">month</span>). Useful for visualizing a trend over several months without downloading every reading.
          @endif
        </p>

        <div class="help-tip">
          <span>
            @if($lang==='fr')
            Ajoutez <span class="help-code-inline">&params=solar_output,solar_irradiance</span> pour limiter aux paramètres voulus (sinon tous les paramètres sont inclus).
            @else
            Add <span class="help-code-inline">&params=solar_output,solar_irradiance</span> to limit to specific parameters (otherwise all parameters are included).
            @endif
          </span>
        </div>
      </div>
    </div>

    {{-- SECTION 8 — ADMINISTRATION --}}
    <div class="help-section" id="section-7">
      <div class="help-card">
        <div class="help-h1">Administration</div>
        <div class="help-warn">
          <span style="flex-shrink:0;display:flex;align-items:center;margin-top:1px">{!! $iconWarning !!}</span>
          <span>{{ $lang==='fr' ? 'Cette section est réservée aux administrateurs.' : 'This section is for administrators only.' }}</span>
        </div>

        <div class="help-h2">{{ $lang==='fr' ? 'Gestion des Utilisateurs' : 'User Management' }}</div>
        <p class="help-p">Admin Panel → {{ $lang==='fr' ? 'Users' : 'Users' }}</p>
        <table class="help-table">
          <thead><tr><th>{{ $lang==='fr' ? 'Action' : 'Action' }}</th><th>{{ $lang==='fr' ? 'Description' : 'Description' }}</th></tr></thead>
          <tbody>
            @php
            $ua = $lang==='fr' ? [
              ['+ Add User', 'Créer un utilisateur avec rôle, statut et pays'],
              ['Approve', 'Approuver un utilisateur en attente d\'activation ; il en est informé par e-mail. Chaque nouvelle demande d\'inscription est signalée par e-mail aux administrateurs.'],
              ['Suspend', 'Suspendre temporairement l\'accès'],
              ['Activate', 'Réactiver un utilisateur suspendu'],
              ['Delete', 'Supprimer définitivement. Le dernier administrateur actif ne peut être ni supprimé, ni suspendu, ni rétrogradé.'],
            ] : [
              ['+ Add User', 'Create a user with role, status and country'],
              ['Approve', 'Approve a user pending activation; they are told by e-mail. Each new registration request is e-mailed to the administrators.'],
              ['Suspend', 'Temporarily suspend access'],
              ['Activate', 'Reactivate a suspended user'],
              ['Delete', 'Permanently delete. The last active administrator cannot be deleted, suspended or demoted.'],
            ];
            @endphp
            @foreach($ua as $row)
            <tr><td><span class="help-code-inline">{{ $row[0] }}</span></td><td>{{ $row[1] }}</td></tr>
            @endforeach
          </tbody>
        </table>

        <div class="help-h2">{{ $lang==='fr' ? '8.2 Utilisateurs par Site' : '8.2 Site Users' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Admin Panel → Sites → <span class="help-code-inline">Users</span> — Gérez les utilisateurs de chaque site.
          Un utilisateur peut être assigné à plusieurs sites avec des rôles différents.
          @else
          Admin Panel → Sites → <span class="help-code-inline">Users</span> — Manage users for each site.
          A user can be assigned to multiple sites with different roles.
          @endif
        </p>
        <table class="help-table">
          <thead><tr>
            <th>{{ $lang==='fr' ? 'Action' : 'Action' }}</th>
            <th>{{ $lang==='fr' ? 'Description' : 'Description' }}</th>
          </tr></thead>
          <tbody>
            @php
            $su = $lang==='fr' ? [
              ['Add User', 'Assigner un utilisateur au site avec un rôle (Agent ou Observateur)'],
              ['Remove', 'Retirer un utilisateur — il perd immédiatement l\'acces au site'],
              ['Agent', 'Consulte, saisit des données manuelles, accede a la cle API'],
              ['Observateur', 'Consultation et export uniquement — pas de saisie ni API Key'],
            ] : [
              ['Add User', 'Assign a user to the site with a role (Agent or Observer)'],
              ['Remove', 'Remove a user — they immediately lose access to the site'],
              ['Agent', 'Views, enters manual data, accesses API key'],
              ['Observer', 'View and export only — no manual input or API Key'],
            ];
            @endphp
            @foreach($su as $row)
            <tr><td><span class="help-code-inline">{{ $row[0] }}</span></td><td>{{ $row[1] }}</td></tr>
            @endforeach
          </tbody>
        </table>

        <div class="help-h2">{{ $lang==='fr' ? '8.3 Gestion des Sites' : '8.3 Site Management' }}</div>
        <p class="help-p">{{ $lang==='fr' ? 'Admin Panel → Sites → + New Site' : 'Admin Panel → Sites → + New Site' }}</p>
        <div class="help-tip">
          <span>{{ $lang==='fr' ? 'Cochez "Auto-create all categories" pour générer automatiquement Solar, Water, Irrigation, Weather et Agriculture avec leurs paramètres par défaut.' : 'Check "Auto-create all categories" to automatically generate Solar, Water, Irrigation, Weather and Agriculture with their default parameters.' }}</span>
        </div>

        <table class="help-table">
          <thead><tr>
            <th>{{ $lang==='fr' ? 'Action' : 'Action' }}</th>
            <th>{{ $lang==='fr' ? 'Description' : 'Description' }}</th>
          </tr></thead>
          <tbody>
            <tr>
              <td><strong>Edit</strong></td>
              <td>{{ $lang==='fr' ? 'Modifie le nom, pays, agent assigné, statut, coordonnées, capacité, surface et description du site, ainsi que ses sources de données (préfixe ThingsBoard, station LI-COR, emplacement des capteurs) et les dimensions de sa cuve. La clé API reste inchangée pour ne pas casser les appareils IoT déjà configurés.' : 'Updates the site\'s name, country, assigned agent, status, coordinates, capacity, area and description, as well as its data sources (ThingsBoard prefix, LI-COR station, sensor locations) and its tank dimensions. The API key stays unchanged so already-configured IoT devices keep working.' }}</td>
            </tr>
            <tr>
              <td><strong>Duplicate</strong></td>
              <td>{{ $lang==='fr' ? 'Crée une copie complète du site (catégories, paramètres, groupes de paramètres et graphiques) avec un nouveau nom, un nouveau slug et une nouvelle clé API. Pratique pour déployer un nouveau site ayant la même structure qu\'un site existant. Aucune donnée historique (lectures, commandes d\'actionneurs) n\'est copiée.' : 'Creates a full copy of the site (categories, parameters, parameter groups and charts) with a new name, slug and API key. Useful for rolling out a new site with the same structure as an existing one. No historical data (readings, actuator commands) is copied.' }}</td>
            </tr>
          </tbody>
        </table>

        <div class="help-h2">{{ $lang==='fr' ? 'Sources de données' : 'Data sources' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Admin Panel → <span class="help-code-inline">Data sources</span> liste ce que les comptes ThingsBoard et LI-COR exposent, et le site alimenté par chaque source. Un clic relie une source à un site existant ou crée le site ; ses catégories, paramètres et données apparaissent à la synchronisation suivante (10 minutes au plus). Une source ne peut alimenter qu'un seul site.
          <br>Dans Admin Panel → Sites, un badge par site indique l'état de chaque synchronisation : <strong>ok</strong>, <strong>late</strong> (aucune synchronisation réussie récemment) ou <strong>failed</strong> (survolez-le pour lire l'erreur).
          @else
          Admin Panel → <span class="help-code-inline">Data sources</span> lists what the ThingsBoard and LI-COR accounts expose, and the site each source feeds. One click links a source to an existing site or creates the site; its categories, parameters and data appear at the next sync (10 minutes at most). A source can feed one site only.
          <br>In Admin Panel → Sites, a badge per site shows the state of each sync: <strong>ok</strong>, <strong>late</strong> (no successful sync recently) or <strong>failed</strong> (hover it to read the error).
          @endif
        </p>

        <div class="help-h2">{{ $lang==='fr' ? '8.4 Catégories & Paramètres' : '8.4 Categories & Parameters' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Les paramètres définissent les données collectées par chaque catégorie. Chaque paramètre correspond à une mesure spécifique (ex: température, niveau d'eau, rendement agricole).
          <br>Chemin : <span class="help-code-inline">Admin Panel → Sites → Categories → Parameters → + Add Parameter</span>
          @else
          Parameters define the data collected by each category. Each parameter corresponds to a specific measurement (e.g. temperature, water level, agricultural yield).
          <br>Path: <span class="help-code-inline">Admin Panel → Sites → Categories → Parameters → + Add Parameter</span>
          @endif
        </p>

        <div class="help-tip">
          <span>
            @if($lang==='fr')
            <strong>Seuil "No data" / "Stale" :</strong> en cliquant sur <span class="help-code-inline">Edit</span> sur une catégorie, vous pouvez régler le champ <strong>"Offline threshold (minutes)"</strong> (5 par défaut ; 60 pour les catégories alimentées par ThingsBoard et 720 pour celles alimentées par LI-COR, réglés automatiquement à la liaison de la source). Si un capteur de cette catégorie n'envoie rien pendant plus longtemps que ce délai, ses paramètres affichent "Stale" sur le dashboard.
            @else
            <strong>"No data" / "Stale" threshold:</strong> click <span class="help-code-inline">Edit</span> on a category to set the <strong>"Offline threshold (minutes)"</strong> field (5 by default; 60 for categories fed by ThingsBoard and 720 for those fed by LI-COR, set automatically when the source is linked). If a sensor in this category sends nothing for longer than this delay, its parameters show "Stale" on the dashboard.
            @endif
          </span>
        </div>

        <table class="help-table">
          <thead><tr>
            <th>{{ $lang==='fr' ? 'Champ' : 'Field' }}</th>
            <th>{{ $lang==='fr' ? 'Valeurs possibles' : 'Possible Values' }}</th>
            <th>{{ $lang==='fr' ? 'Description' : 'Description' }}</th>
          </tr></thead>
          <tbody>
            @php
            $params = $lang==='fr' ? [
              ['Nom', 'Texte libre', 'Nom affiché sur les KPI cards et les graphiques (ex: Température ambiante)'],
              ['Slug', 'snake_case unique', 'Identifiant utilisé dans l\'API ESP32 comme clé JSON (ex: temperature_c). Doit être unique dans la catégorie.'],
              ['Unité', 'Texte libre', 'Unité de mesure affichée (ex: °C, kW, %, m, kg). Apparait dans les KPI cards et les exports.'],
              ['Type de saisie', 'sensor / manual', 'sensor = données envoyées automatiquement par ESP32 via API. manual = données saisies manuellement par un agent. Forcé à sensor si Type de données = Switch.'],
              ['Type de données', 'float / integer / string / boolean / switch', 'float = nombre décimal (ex: 4.2 kW). integer = nombre entier (ex: 12 plants). string = texte (ex: Bon état). boolean = Oui/Non. switch = interrupteur ON/OFF (vanne, pompe, ventilateur...).'],
              ['Control type', 'readonly / controllable', 'Visible uniquement si Type de données = Switch. readonly = affichage automatique selon le capteur. controllable = interrupteur cliquable depuis le dashboard.'],
              ['Afficher sur dashboard', 'Oui / Non', 'Si activé, le paramètre apparait comme KPI card en haut de l\'onglet avec sa dernière valeur.'],
              ['Afficher sur graphique', 'Oui / Non', 'Si activé, le paramètre est inclus dans les graphiques de tendance de la catégorie.'],
              ['Min / Max', 'Nombre (optionnel)', 'Plage de valeurs physiquement valides. Une valeur en dehors déclenche le badge "Out of range" (rouge).'],
              ['Warning / Critical threshold', 'Nombre (optionnel)', 'Deux seuils d\'alerte indépendants — badge orange (Warning) et rouge (Critical) sur la carte du paramètre.'],
              ['Alert direction', 'below / above', 'below (par défaut) = alerte quand la valeur descend au seuil ou en dessous (ex: niveau de réservoir). above = alerte quand la valeur monte au seuil ou au-dessus (ex: température).'],
              ['Group', 'Sélection dans une liste', 'Rattache le paramètre à un groupe géré dans Admin Panel → Parameters → Manage Groups (voir ci-dessous). Les paramètres d\'un même groupe s\'affichent encadrés ensemble sur le dashboard, et regroupés dans un même formulaire sur Manual Input.'],
            ] : [
              ['Name', 'Free text', 'Name displayed on KPI cards and charts (e.g. Ambient Temperature)'],
              ['Slug', 'Unique snake_case', 'Identifier used in ESP32 API as JSON key (e.g. temperature_c). Must be unique within the category.'],
              ['Unit', 'Free text', 'Measurement unit displayed (e.g. °C, kW, %, m, kg). Appears on KPI cards and exports.'],
              ['Input type', 'sensor / manual', 'sensor = data sent automatically by ESP32 via API. manual = data entered manually by a field agent. Forced to sensor if Data type = Switch.'],
              ['Data type', 'float / integer / string / boolean / switch', 'float = decimal number (e.g. 4.2 kW). integer = whole number (e.g. 12 plants). string = text (e.g. Good condition). boolean = Yes/No. switch = ON/OFF toggle (valve, pump, fan...).'],
              ['Control type', 'readonly / controllable', 'Only visible if Data type = Switch. readonly = display automatically follows the sensor. controllable = clickable toggle from the dashboard.'],
              ['Show on dashboard', 'Yes / No', 'If enabled, the parameter appears as a KPI card at the top of the tab with its latest value.'],
              ['Show on chart', 'Yes / No', 'If enabled, the parameter is included in the category trend charts.'],
              ['Min / Max', 'Number (optional)', 'Physically valid value range. A value outside it triggers the "Out of range" badge (red).'],
              ['Warning / Critical threshold', 'Number (optional)', 'Two independent alert levels — orange (Warning) and red (Critical) badge on the parameter\'s card.'],
              ['Alert direction', 'below / above', 'below (default) = alert when the value drops to/under the threshold (e.g. tank level). above = alert when the value climbs to/over the threshold (e.g. temperature).'],
              ['Group', 'Selected from a list', 'Attaches the parameter to a group managed in Admin Panel → Parameters → Manage Groups (see below). Parameters in the same group are boxed together on the dashboard, and grouped into one form on Manual Input.'],
            ];
            @endphp
            @foreach($params as $row)
            <tr>
              <td><strong>{{ $row[0] }}</strong></td>
              <td><span class="help-code-inline">{{ $row[1] }}</span></td>
              <td>{{ $row[2] }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>

        <div class="help-tip">
          <span>
            @if($lang==='fr')
            <strong>Exemple :</strong> Pour mesurer la taille des plantes manuellement : Slug = <span class="help-code-inline">plant_height</span>, Type = <span class="help-code-inline">float</span>, Unité = <span class="help-code-inline">cm</span>, Input type = <span class="help-code-inline">manual</span>, Groupe = <span class="help-code-inline">Mesures végétales</span>.
            @else
            <strong>Example:</strong> To manually measure plant height: Slug = <span class="help-code-inline">plant_height</span>, Type = <span class="help-code-inline">float</span>, Unit = <span class="help-code-inline">cm</span>, Input type = <span class="help-code-inline">manual</span>, Group = <span class="help-code-inline">Plant Measurements</span>.
            @endif
          </span>
        </div>

        <div class="help-h2">{{ $lang==='fr' ? 'Gestion des Groupes de Paramètres' : 'Managing Parameter Groups' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Chemin : <span class="help-code-inline">Admin Panel → Sites → Categories → Parameters → Manage Groups</span>. Ce panneau (replié par défaut, cliquez sur le bouton pour l'ouvrir) permet de créer, renommer, recolorer, réordonner et supprimer les groupes d'une catégorie.
          @else
          Path: <span class="help-code-inline">Admin Panel → Sites → Categories → Parameters → Manage Groups</span>. This panel (collapsed by default — click the button to open it) lets you create, rename, recolor, reorder and delete a category's groups.
          @endif
        </p>
        <table class="help-table">
          <thead><tr>
            <th>{{ $lang==='fr' ? 'Action' : 'Action' }}</th>
            <th>{{ $lang==='fr' ? 'Comment' : 'How' }}</th>
          </tr></thead>
          <tbody>
            <tr>
              <td>{{ $lang==='fr' ? 'Créer' : 'Create' }}</td>
              <td>{{ $lang==='fr' ? 'Saisir un nom et choisir une couleur (une couleur distincte est proposée automatiquement) dans le formulaire en bas du panneau.' : 'Enter a name and pick a color (a distinct color is suggested automatically) in the form at the bottom of the panel.' }}</td>
            </tr>
            <tr>
              <td>{{ $lang==='fr' ? 'Renommer / recolorer' : 'Rename / recolor' }}</td>
              <td>{{ $lang==='fr' ? 'Modifier directement le nom ou la couleur sur la carte du groupe — sauvegarde automatique.' : 'Edit the name or color directly on the group\'s card — saves automatically.' }}</td>
            </tr>
            <tr>
              <td>{{ $lang==='fr' ? 'Réordonner' : 'Reorder' }}</td>
              <td>{{ $lang==='fr' ? 'Glisser-déposer une carte de groupe pour changer l\'ordre d\'affichage sur le dashboard.' : 'Drag and drop a group\'s card to change its display order on the dashboard.' }}</td>
            </tr>
            <tr>
              <td>{{ $lang==='fr' ? 'Supprimer' : 'Delete' }}</td>
              <td>{{ $lang==='fr' ? 'Le groupe est supprimé mais ses paramètres ne le sont pas — ils redeviennent simplement "sans groupe".' : 'The group is removed but its parameters are not deleted — they simply become "ungrouped" again.' }}</td>
            </tr>
          </tbody>
        </table>
        <div class="help-tip">
          <span>{{ $lang==='fr' ? 'Un groupe contenant un seul paramètre s\'affiche comme une carte normale, sans encadré — l\'encadré coloré n\'apparaît qu\'à partir de 2 paramètres partageant le même groupe.' : 'A group containing a single parameter displays as a normal card, without a box — the colored box only appears once 2 or more parameters share the same group.' }}</span>
        </div>

        <div class="help-h2">{{ $lang==='fr' ? '8.5 Création des Graphiques' : '8.5 Creating Charts' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Les graphiques sont configurés par l'administrateur pour chaque catégorie. Ils permettent de visualiser l'évolution des paramètres dans le temps.
          <br>Chemin : <span class="help-code-inline">Admin Panel → Sites → Categories → Charts → + Add Chart</span>
          @else
          Charts are configured by the administrator for each category. They allow visualizing parameter evolution over time.
          <br>Path: <span class="help-code-inline">Admin Panel → Sites → Categories → Charts → + Add Chart</span>
          @endif
        </p>

        <div class="help-h3">{{ $lang==='fr' ? 'Configuration du graphique' : 'Chart Configuration' }}</div>
        <table class="help-table">
          <thead><tr>
            <th>{{ $lang==='fr' ? 'Champ' : 'Field' }}</th>
            <th>{{ $lang==='fr' ? 'Valeurs' : 'Values' }}</th>
            <th>{{ $lang==='fr' ? 'Description' : 'Description' }}</th>
          </tr></thead>
          <tbody>
            @php
            $charts = $lang==='fr' ? [
              ['Titre', 'Texte libre', 'Titre affiché en haut du graphique (ex: Production solaire vs Irradiance)'],
              ['Type', 'line / bar / area', 'line = courbe lisse. bar = barres verticales, adaptées aux totaux journaliers (pluie, consommation d\'eau). area = courbe avec zone remplie sous la courbe.'],
              ['Largeur', 'full / half / third', 'full = pleine largeur. half = 2 graphiques côte à côte. third = 3 graphiques côte à côte.'],
              ['Hauteur', 'Pixels (ex: 220)', 'Hauteur du graphique en pixels. 220px recommandé pour half/third, 280px pour full.'],
              ['Afficher légende', 'Oui / Non', 'Affiche ou masque la légende des séries de données sous le graphique.'],
            ] : [
              ['Title', 'Free text', 'Title displayed at the top of the chart (e.g. Solar Output vs Irradiance)'],
              ['Type', 'line / bar / area', 'line = smooth curve. bar = vertical bars, suited to daily totals (rain, water use). area = curve with filled zone below.'],
              ['Width', 'full / half / third', 'full = full width. half = 2 charts side by side. third = 3 charts side by side.'],
              ['Height', 'Pixels (e.g. 220)', 'Chart height in pixels. 220px recommended for half/third, 280px for full.'],
              ['Show legend', 'Yes / No', 'Show or hide the data series legend below the chart.'],
            ];
            @endphp
            @foreach($charts as $row)
            <tr>
              <td><strong>{{ $row[0] }}</strong></td>
              <td><span class="help-code-inline">{{ $row[1] }}</span></td>
              <td>{{ $row[2] }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>

        <div class="help-h3">{{ $lang==='fr' ? 'Ajout des paramètres au graphique' : 'Adding Parameters to Chart' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Après avoir créé le graphique, cliquez sur <span class="help-code-inline">+ Add Parameter</span> pour y ajouter des séries de données. Chaque paramètre ajouté devient une courbe ou barre sur le graphique.
          @else
          After creating the chart, click <span class="help-code-inline">+ Add Parameter</span> to add data series. Each added parameter becomes a curve or bar on the chart.
          @endif
        </p>
        <table class="help-table">
          <thead><tr>
            <th>{{ $lang==='fr' ? 'Option' : 'Option' }}</th>
            <th>{{ $lang==='fr' ? 'Valeurs' : 'Values' }}</th>
            <th>{{ $lang==='fr' ? 'Description' : 'Description' }}</th>
          </tr></thead>
          <tbody>
            @php
            $cp = $lang==='fr' ? [
              ['Paramètre', 'Liste déroulante', 'Sélectionnez le paramètre à afficher (doit être float ou integer).'],
              ['Couleur', 'Code hex (ex: #1d6ed8)', 'Couleur de la courbe ou barre. Choisissez des couleurs contrastées pour différencier les séries.'],
              ['Axe', 'left / right', 'left = axe Y gauche (principal). right = axe Y droit (secondaire). Utilisez right quand deux paramètres ont des échelles très différentes (ex: kW et %).'],
              ['Pointillé', 'Oui / Non', 'Si activé, la courbe s\'affiche en pointillés. Utile pour une valeur de référence ou un seuil théorique.'],
              ['Remplissage', 'Oui / Non', 'Si activé, la zone sous la courbe est colorée (effet area). Recommandé uniquement pour une seule série par graphique.'],
            ] : [
              ['Parameter', 'Dropdown list', 'Select the parameter to display (must be float or integer).'],
              ['Color', 'Hex code (e.g. #1d6ed8)', 'Curve or bar color. Choose contrasting colors to differentiate series.'],
              ['Axis', 'left / right', 'left = left Y axis (main). right = right Y axis (secondary). Use right when two parameters have very different scales (e.g. kW and %).'],
              ['Dashed', 'Yes / No', 'If enabled, the curve displays as dashed. Useful for a reference value or theoretical threshold.'],
              ['Fill', 'Yes / No', 'If enabled, the area below the curve is colored (area effect). Recommended for only one series per chart.'],
            ];
            @endphp
            @foreach($cp as $row)
            <tr>
              <td><strong>{{ $row[0] }}</strong></td>
              <td><span class="help-code-inline">{{ $row[1] }}</span></td>
              <td>{{ $row[2] }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>

        <div class="help-tip">
          <span>
            @if($lang==='fr')
            <strong>Exemple :</strong> Graphique "Production & Irradiance" — ajoutez <span class="help-code-inline">solar_output</span> (bleu, axe gauche, remplissage activé) et <span class="help-code-inline">solar_irradiance</span> (orange, axe droit, pointillé), type line, largeur full.
            @else
            <strong>Example:</strong> "Output & Irradiance" chart — add <span class="help-code-inline">solar_output</span> (blue, left axis, fill enabled) and <span class="help-code-inline">solar_irradiance</span> (orange, right axis, dashed), line type, full width.
            @endif
          </span>
        </div>

        <div class="help-warn">
          <span style="flex-shrink:0;display:flex;align-items:center;margin-top:1px">{!! $iconWarning !!}</span>
          <span>
            @if($lang==='fr')
            Si aucun graphique n'est configuré pour une catégorie, un graphique automatique est généré avec tous les paramètres numériques de la catégorie.
            @else
            If no chart is configured for a category, an automatic chart is generated with all numeric parameters of the category.
            @endif
          </span>
        </div>
      </div>
    </div>

    {{-- SECTION 9 — DATA SOURCES & ALERTS --}}
    <div class="help-section" id="section-8">
      <div class="help-card">
        <div class="help-h1">{{ $lang==='fr' ? 'Sources de données & Alertes' : 'Data Sources & Alerts' }}</div>
        <p class="help-p">{{ $lang==='fr' ? 'D\'où viennent les données affichées, avec quel délai, et quand la plateforme vous prévient.' : 'Where the displayed data comes from, how fresh it is, and when the platform warns you.' }}</p>

        <div class="help-h2">{{ $lang==='fr' ? 'D\'où viennent les données' : 'Where the data comes from' }}</div>
        <table class="help-table">
          <thead><tr><th>Source</th><th>{{ $lang==='fr' ? 'Mesures' : 'Measurements' }}</th><th>{{ $lang==='fr' ? 'Délai' : 'Delay' }}</th></tr></thead>
          <tbody>
            @php
            $sources = $lang==='fr' ? [
              ['ThingsBoard', 'Humidité du sol, vannes, débit, niveau et turbidité de la cuve', 'Récupérées automatiquement toutes les 10 minutes. Les capteurs émettent toutes les 15 à 20 minutes.'],
              ['LI-COR (HOBOnet)', 'Station météo (température, humidité, vent, pluie, pression) et capteurs PAR', 'Les stations téléversent leurs mesures par lots, toutes les 6 à 10 heures : les données apparaissent avec ce retard, à leur horodatage d\'origine. Valeurs par pas de 10 minutes.'],
              ['API REST (ESP32)', 'Tout paramètre de type sensor', 'Immédiat, à chaque envoi de l\'appareil.'],
              ['Saisie manuelle', 'Mesures de terrain (plantes, récolte…)', 'À l\'enregistrement du formulaire.'],
            ] : [
              ['ThingsBoard', 'Soil moisture, valves, flow, tank level and turbidity', 'Collected automatically every 10 minutes. The sensors report every 15 to 20 minutes.'],
              ['LI-COR (HOBOnet)', 'Weather station (temperature, humidity, wind, rain, pressure) and PAR sensors', 'Stations upload their measurements in batches, every 6 to 10 hours: data appears with that delay, at its original timestamp. Values in 10-minute steps.'],
              ['REST API (ESP32)', 'Any sensor-type parameter', 'Immediate, each time the device sends.'],
              ['Manual entry', 'Field measurements (plants, harvest…)', 'When the form is saved.'],
            ];
            @endphp
            @foreach($sources as $row)
            <tr><td><strong>{{ $row[0] }}</strong></td><td>{{ $row[1] }}</td><td>{{ $row[2] }}</td></tr>
            @endforeach
          </tbody>
        </table>

        <div class="help-h2">{{ $lang==='fr' ? 'Valeurs calculées' : 'Computed values' }}</div>
        <table class="help-table">
          <tbody>
            <tr>
              <td><strong>{{ $lang==='fr' ? 'Volume de la cuve' : 'Tank volume' }}</strong></td>
              <td>{{ $lang==='fr' ? 'Calculé à partir de la hauteur d\'eau mesurée et des dimensions de la cuve du site (rayon et nombre de cuves, dans Admin Panel → Sites → Edit). Sans dimensions, aucun volume n\'est affiché plutôt qu\'un volume faux.' : 'Computed from the measured water height and the site\'s own tank (radius and number of tanks, in Admin Panel → Sites → Edit). Without dimensions, no volume is shown rather than a wrong one.' }}</td>
            </tr>
            <tr>
              <td><strong>Daily water use</strong></td>
              <td>{{ $lang==='fr' ? 'Consommation d\'eau de chaque journée, calculée à partir du compteur de débit et affichée en barres.' : 'Water used each day, computed from the flow meter and shown as bars.' }}</td>
            </tr>
          </tbody>
        </table>

        <div class="help-tip">
          <span>{{ $lang==='fr' ? 'Une courbe interrompue signifie qu\'aucune donnée n\'a été reçue pendant cet intervalle : la plateforme ne relie pas les points de part et d\'autre d\'une coupure.' : 'A broken line means no data was received during that interval: the platform does not join the points on either side of a gap.' }}</span>
        </div>

        <div class="help-h2">{{ $lang==='fr' ? 'Alertes e-mail' : 'E-mail alerts' }}</div>
        <p class="help-p">
          @if($lang==='fr')
          Quand une catégorie d'un site (Irrigation, Water, Weather…) n'envoie plus rien au-delà de son seuil <strong>Offline threshold</strong>, un e-mail part ; un second annonce le retour des données.
          @else
          When a category of a site (Irrigation, Water, Weather…) sends nothing for longer than its <strong>Offline threshold</strong>, an e-mail goes out; a second one announces that data is back.
          @endif
        </p>
        <table class="help-table">
          <tbody>
            @php
            $alerts = $lang==='fr' ? [
              ['Qui est prévenu', 'Les administrateurs actifs, les utilisateurs actifs affectés au site et son propriétaire, s\'ils ont la case « Receive offline alerts » cochée (Admin Panel → Users). Cochée par défaut pour les administrateurs et les agents, décochée pour les observateurs.'],
              ['Fréquence', 'Un message à l\'arrêt, un au retour. Rien n\'est répété pendant la panne. Les catégories d\'un même site qui changent ensemble partagent un seul message.'],
              ['Capteur isolé', 'Un capteur qui se tait alors que le reste de sa catégorie émet encore (un débitmètre en panne parmi des sondes qui fonctionnent) est aussi signalé, après trois fois le seuil de la catégorie : 3 h pour ThingsBoard, 36 h pour LI-COR. Les coupures plus courtes sont fréquentes sur les liaisons radio et ne sont pas signalées.'],
              ['Non signalé', 'Une catégorie qui n\'a jamais envoyé de données, une catégorie ou un site désactivé, les paramètres saisis à la main, et les silences dus à une panne de liaison entre la plateforme et ThingsBoard ou LI-COR.'],
            ] : [
              ['Who is told', 'Active administrators, the active users assigned to the site, and its owner, when their "Receive offline alerts" box is ticked (Admin Panel → Users). Ticked by default for administrators and agents, unticked for observers.'],
              ['How often', 'One message when it stops, one when it resumes. Nothing is repeated during the outage. Categories of one site that change together share one message.'],
              ['Single sensor', 'A sensor that goes quiet while the rest of its category still reports (a dead flow meter among working probes) is announced too, after three times the category threshold: 3 h for ThingsBoard, 36 h for LI-COR. Shorter gaps are routine on radio links and are not announced.'],
              ['Not announced', 'A category that never sent data, a deactivated category or site, manually entered parameters, and silences caused by the platform itself not reaching ThingsBoard or LI-COR.'],
            ];
            @endphp
            @foreach($alerts as $row)
            <tr><td><strong>{{ $row[0] }}</strong></td><td>{{ $row[1] }}</td></tr>
            @endforeach
          </tbody>
        </table>
        <div class="help-warn">
          <span style="flex-shrink:0;display:flex;align-items:center;margin-top:1px">{!! $iconWarning !!}</span>
          <span>{{ $lang==='fr' ? 'Les dépassements de seuil de valeur (Warning / Critical) s\'affichent sur les cartes mais ne sont pas envoyés par e-mail.' : 'Value thresholds (Warning / Critical) show on the cards but are not sent by e-mail.' }}</span>
        </div>
      </div>
    </div>

    {{-- SECTION 10 — FAQ --}}
    <div class="help-section" id="section-9">
      <div class="help-card">
        <div class="help-h1">FAQ</div>

        @php
        $faqs = $lang==='fr' ? [
          ['Mon capteur envoie des données mais elles n\'apparaissent pas ?',
           'Vérifiez que le slug de catégorie et paramètre correspondent exactement à ceux de l\'Admin Panel. La clé API doit être celle du bon site. Consultez l\'onglet API Key du site.'],
          ['Comment ajouter une nouvelle catégorie ?',
           'Admin Panel → Sites → cliquez sur "Categories" → "+ Add Category". Ajoutez ensuite les paramètres via "Parameters".'],
          ['Les graphiques affichent "No data" ?',
           'Aucune donnée n\'a été reçue pour la période sélectionnée. Changez la période (1H → 24H → 7D) ou envoyez des données via l\'API.'],
          ['L\'onglet Manual Input n\'apparaît pas ?',
           'L\'onglet n\'apparaît que si le site possède au moins un paramètre avec input_type = manual. Configurez-le dans Admin Panel → Parameters.'],
          ['Comment connecter plusieurs ESP32 à un même site ?',
           'Chaque ESP32 peut envoyer vers une catégorie différente avec la même clé API. ESP32-1 → /solar, ESP32-2 → /water, etc.'],
          ['Comment changer mon mot de passe ?',
           'Cliquez sur votre nom en bas de la sidebar → My Profile → Change Password.'],
          ['Les données manuelles s\'affichent-elles sur les graphiques ?',
           'Oui, si le paramètre a data_type = float ou integer. Les données manuelles apparaissent dans les graphiques de tendance et dans le Raw Data.'],
          ['La table Saved Records ne s\'affiche pas ?',
           'La table apparait uniquement si la catégorie a des paramètres manuels avec un Nom de groupe. Configurez le champ Nom de groupe dans Admin Panel → Parameters.'],
          ['Comment assigner plusieurs utilisateurs à un site ?',
           'Admin Panel → Sites → bouton Users → Add User. Vous pouvez assigner autant d\'utilisateurs que nécessaire avec des rôles différents.'],
          ['J\'ai cliqué sur un switch mais rien ne se passe sur le terrain ?',
           'La commande est enregistrée immédiatement, mais l\'appareil ne l\'applique qu\'à sa prochaine connexion (badge "Pending…"). Si le badge reste "Pending…" longtemps ou passe à "No device report"/"Stale", l\'appareil est probablement hors ligne ou mal configuré.'],
          ['Quelle est la différence entre un switch "readonly" et "controllable" ?',
           'readonly = simple voyant ON/OFF, non cliquable, reflète l\'état du capteur. controllable = interrupteur cliquable qui envoie une commande à l\'appareil, avec un badge de synchronisation (Synced/Pending/Stale).'],
          ['Une carte affiche "No data" ou "Stale", que faire ?',
           '"No data" = aucune donnée n\'a jamais été reçue pour ce paramètre — vérifiez le slug et la clé API. "Stale" = une donnée existe mais est plus ancienne que le seuil configuré — vérifiez que l\'ESP32 envoie bien à l\'intervalle attendu, ou ajustez le seuil dans Admin Panel → Categories → Edit.'],
          ['Les données météo ou PAR ont plusieurs heures de retard ?',
           'C\'est normal : les stations LI-COR (HOBOnet) téléversent leurs mesures par lots, toutes les 6 à 10 heures. Les données apparaissent à ce moment-là, à leur horodatage d\'origine. Une lecture n\'est marquée "Stale" qu\'au-delà de 12 heures.'],
          ['Une courbe est interrompue sur un graphique ?',
           'Aucune donnée n\'a été reçue pendant cet intervalle (capteur, alimentation ou réseau du site). La plateforme ne relie pas les points de part et d\'autre de la coupure.'],
          ['Je ne reçois pas les alertes e-mail ?',
           'Les alertes sont envoyées aux administrateurs et aux utilisateurs affectés au site concerné dont la case « Receive offline alerts » est cochée. Vérifiez votre adresse dans My Profile, votre affectation au site auprès d\'un administrateur, et le dossier des indésirables.'],
        ] : [
          ['My sensor sends data but it doesn\'t appear?',
           'Check that category and parameter slugs match exactly those in the Admin Panel. The API key must be for the correct site. Check the site\'s API Key tab.'],
          ['How to add a new category?',
           'Admin Panel → Sites → click "Categories" → "+ Add Category". Then add parameters via "Parameters".'],
          ['Charts show "No data"?',
           'No data has been received for the selected period. Change the period (1H → 24H → 7D) or send data via the API.'],
          ['The Manual Input tab doesn\'t appear?',
           'The tab only appears if the site has at least one parameter with input_type = manual. Configure it in Admin Panel → Parameters.'],
          ['How to connect multiple ESP32 to the same site?',
           'Each ESP32 can send to a different category using the same API key. ESP32-1 → /solar, ESP32-2 → /water, etc.'],
          ['How to change my password?',
           'Click your name at the bottom of the sidebar → My Profile → Change Password.'],
          ['Do manual data appear on charts?',
           'Yes, if the parameter has data_type = float or integer. Manual data appears in trend charts and in Raw Data.'],
          ['The Saved Records table does not appear?',
           'The Saved Records table only appears if the category has manual parameters with a group name. Configure the Group name field in Admin Panel → Parameters.'],
          ['How to assign multiple users to a site?',
           'Admin Panel → Sites → click the Users button for the site → Add User. You can assign as many users as needed with different roles (Agent or Observer).'],
          ['I clicked a switch but nothing happens on site?',
           'The command is saved immediately, but the device only applies it on its next check-in ("Pending…" badge). If the badge stays "Pending…" for a long time or shows "No device report"/"Stale", the device is likely offline or misconfigured.'],
          ['What\'s the difference between a "readonly" and "controllable" switch?',
           'readonly = simple ON/OFF indicator, not clickable, reflects the sensor\'s state. controllable = clickable toggle that sends a command to the device, with a sync badge (Synced/Pending/Stale).'],
          ['A card shows "No data" or "Stale" — what should I do?',
           '"No data" = no data has ever been received for this parameter — check the slug and API key. "Stale" = data exists but is older than the configured threshold — check that the ESP32 sends at the expected interval, or adjust the threshold in Admin Panel → Categories → Edit.'],
          ['Weather or PAR data is several hours late?',
           'This is expected: LI-COR (HOBOnet) stations upload their measurements in batches, every 6 to 10 hours. The data appears at that point, at its original timestamp. A reading is only marked "Stale" past 12 hours.'],
          ['A line is broken on a chart?',
           'No data was received during that interval (sensor, power or network at the site). The platform does not join the points on either side of the gap.'],
          ['I do not receive the e-mail alerts?',
           'Alerts go to the administrators and to the users assigned to the site concerned whose "Receive offline alerts" box is ticked. Check your address in My Profile, your assignment to the site with an administrator, and your spam folder.'],
        ];
        @endphp

        @foreach($faqs as $i => $faq)
        <div class="faq-item" id="faq-{{ $i }}">
          <div class="faq-q" onclick="toggleFaq({{ $i }})">
            <span>{{ $faq[0] }}</span>
            <span style="font-size:18px;transition:transform .2s" id="faq-arrow-{{ $i }}">+</span>
          </div>
          <div class="faq-a">{{ $faq[1] }}</div>
        </div>
        @endforeach
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

function toggleFaq(idx) {
  const item = document.getElementById('faq-' + idx);
  const arrow = document.getElementById('faq-arrow-' + idx);
  const isOpen = item.classList.contains('open');
  document.querySelectorAll('.faq-item').forEach(i => {
    i.classList.remove('open');
    i.querySelector('[id^=faq-arrow]').textContent = '+';
  });
  if (!isOpen) {
    item.classList.add('open');
    arrow.textContent = '×';
  }
}
</script>
@endpush
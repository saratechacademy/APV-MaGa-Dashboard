@extends('layouts.dashboard')
@section('page-title', 'Analysis')
@section('page-crumb', 'Compare zones, sites, periods and parameters')

@push('styles')
<style>
.an-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);padding:16px;margin-bottom:14px}
.an-controls{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;align-items:end}
.an-seg{display:flex;flex-wrap:wrap;background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:3px;gap:2px}
.an-seg button{flex:1;border:none;background:transparent;border-radius:6px;padding:8px 10px;font:500 13px 'DM Sans',sans-serif;color:var(--muted);cursor:pointer;white-space:nowrap}
.an-seg button.active{background:var(--surface);color:var(--text);box-shadow:var(--shadow)}
.an-sites{display:flex;flex-wrap:wrap;gap:6px}
.an-sites label{display:flex;align-items:center;gap:6px;border:1px solid var(--border);border-radius:20px;padding:6px 12px;font-size:13px;cursor:pointer;background:var(--bg)}
.an-sites input{accent-color:var(--blue)}
.an-presets{display:flex;gap:6px}
.an-presets button{flex:1}
.an-about{font-size:12px;color:var(--muted);line-height:1.6;margin-top:12px}
.an-error{background:var(--amber-bg);border:1px solid var(--amber-bd);color:var(--amber);border-radius:8px;padding:10px 12px;font-size:13px;margin-bottom:14px}
.an-actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;margin-bottom:12px}
/* Phones: the two downloads side by side, "New analysis" on its own row below. */
@media (max-width:640px){
  .an-actions{display:grid;grid-template-columns:1fr 1fr}
  .an-actions .btn{justify-content:center}
  #an-reset{grid-column:1 / -1;order:3;margin-right:0 !important}
}
.an-block{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);padding:16px;margin-bottom:14px}
.an-hint{font-size:11px;color:var(--muted);margin-top:4px}
.an-hide{display:none !important}
</style>
@endpush

@section('content')
@if(!$catalog && !$parameters)
  <div class="an-card" style="text-align:center;color:var(--muted);font-size:13px;padding:30px">
    None of your sites has data that can be compared yet.
  </div>
@else
<div class="an-card">
  <div class="form-field">
    <span class="form-label">Compare</span>
    <div class="an-seg" role="group" aria-label="Comparison mode">
      <button type="button" data-mode="site" class="active">Zones of a site</button>
      <button type="button" data-mode="sites">Sites</button>
      <button type="button" data-mode="periods">Two periods</button>
      <button type="button" data-mode="free">Any parameters</button>
    </div>
  </div>

  <div class="an-controls" style="margin-top:12px">
    <div class="form-field" data-show="site periods">
      <label class="form-label" for="an-site">Site</label>
      <select id="an-site" class="form-input"></select>
    </div>
    <div class="form-field" data-show="site sites periods">
      <label class="form-label" for="an-quantity">Quantity</label>
      <select id="an-quantity" class="form-input"></select>
    </div>
    <div class="form-field" data-show="sites periods">
      <label class="form-label" for="an-zone" id="an-zone-label">Zone</label>
      <select id="an-zone" class="form-input"></select>
    </div>
  </div>

  <div class="form-field" data-show="sites" style="margin-top:12px">
    <span class="form-label">Sites (the first one ticked is the baseline)</span>
    <div class="an-sites" id="an-sites"></div>
  </div>

  <div class="an-controls" data-show="free" style="margin-top:12px">
    @for($i = 0; $i < 4; $i++)
    <div class="form-field">
      <label class="form-label" for="an-param-{{ $i }}">Parameter {{ $i + 1 }}{{ $i < 2 ? ' *' : '' }}</label>
      <select id="an-param-{{ $i }}" class="form-input an-param"></select>
    </div>
    @endfor
  </div>
  <div class="an-hint" data-show="free">Parameters 1 and 2 are also related to each other (correlation). Up to two different units are shown, each on its own axis.</div>

  <div class="an-controls" style="margin-top:12px">
    <div class="form-field">
      <label class="form-label" for="an-from"><span data-show="periods">Period A — </span>From</label>
      <input type="date" id="an-from" class="form-input">
    </div>
    <div class="form-field">
      <label class="form-label" for="an-to"><span data-show="periods">Period A — </span>To</label>
      <input type="date" id="an-to" class="form-input">
    </div>
    <div class="form-field" data-show="periods">
      <label class="form-label" for="an-from2">Period B — From</label>
      <input type="date" id="an-from2" class="form-input">
    </div>
    <div class="form-field" data-show="periods">
      <label class="form-label" for="an-to2">Period B — To</label>
      <input type="date" id="an-to2" class="form-input">
    </div>
    <div class="form-field" data-show="site sites free">
      <span class="form-label">Period</span>
      <div class="an-presets">
        <button type="button" class="btn" data-days="7">7 days</button>
        <button type="button" class="btn" data-days="30">30 days</button>
        <button type="button" class="btn" data-days="90">90 days</button>
      </div>
    </div>
    <div class="form-field">
      <label class="form-label" for="an-step">Values</label>
      <select id="an-step" class="form-input">
        <option value="hour">Hourly</option>
        <option value="day">Daily</option>
      </select>
    </div>
    <div class="form-field">
      <button type="button" class="btn btn-blue" id="an-run" style="justify-content:center;width:100%">Analyse</button>
    </div>
  </div>

  <div class="an-about" id="an-about"></div>
</div>

<div id="an-error" class="an-error" style="display:none"></div>

<div id="an-results" style="display:none">
  <div class="an-actions">
    <button type="button" class="btn" id="an-reset" style="margin-right:auto">✕ New analysis</button>
    <a class="btn" id="an-csv" href="#">⬇ Data (CSV)</a>
    <a class="btn btn-blue" id="an-report" href="#" target="_blank" rel="noopener">⬇ Report (PDF)</a>
  </div>
  @include('analysis._results')
</div>
@endif
@endsection

@push('scripts')
@if($catalog || $parameters)
<script>
const AN_CATALOG = @json($catalog);
const AN_PARAMS = @json($parameters);
const AN_QUANTITIES = @json($quantities);
const AN_ZONES = @json($zones);
const AN_PLOTS = @json($plots);
const AN_URLS = { data: @json(route('analysis.data')), csv: @json(route('analysis.csv')), report: @json(route('analysis.report')), page: @json(route('analysis')) };

const byId = id => document.getElementById(id);
let mode = 'site';

function siteById(id) { return AN_CATALOG.find(s => String(s.id) === String(id)); }
function checkedSites() { return [...document.querySelectorAll('#an-sites input:checked')].map(i => i.value); }
function option(value, text, selected) { return `<option value="${value}" ${selected ? 'selected' : ''}>${anEsc(text)}</option>`; }
function isManual(q) { return !!(AN_QUANTITIES[q] && AN_QUANTITIES[q].manual); }
function placeLabel(z, manual) { return (manual ? AN_PLOTS[z] : AN_ZONES[z]) || z; }

// Quantities worth offering in the current mode.
function availableQuantities() {
  if (mode === 'site') {
    const s = siteById(byId('an-site').value);
    return s ? Object.keys(s.quantities).filter(q => s.quantities[q].length >= 2) : [];
  }
  if (mode === 'periods') {
    const s = siteById(byId('an-site').value);
    return s ? Object.keys(s.quantities) : [];
  }
  const counts = {};
  checkedSites().forEach(id => Object.keys(siteById(id)?.quantities || {}).forEach(q => counts[q] = (counts[q] || 0) + 1));
  return Object.keys(counts).filter(q => counts[q] >= 2);
}

function refreshQuantities(keep) {
  if (mode === 'free') { refreshAbout(); return; }
  const list = availableQuantities().filter(q => AN_QUANTITIES[q]);
  const current = keep || byId('an-quantity').value;
  byId('an-quantity').innerHTML = list.length
    ? list.map(q => option(q, AN_QUANTITIES[q].title + (isManual(q) ? ' (field measurement)' : ''), q === current)).join('')
    : option('', mode === 'sites' ? 'Tick at least two sites' : 'Nothing to compare on this site', true);
  refreshZones();
  refreshAbout();
}

// Zones (sensors) or plots (field measurements) the quantity is available in.
function refreshZones(keep) {
  if (mode !== 'sites' && mode !== 'periods') return;
  const q = byId('an-quantity').value;
  const places = new Set();
  const siteIds = mode === 'sites' ? checkedSites() : [byId('an-site').value];
  siteIds.forEach(id => (siteById(id)?.quantities[q] || []).forEach(z => places.add(z)));
  byId('an-zone-label').textContent = isManual(q) ? 'Plot' : 'Zone';
  const order = isManual(q) ? Object.keys(AN_PLOTS) : Object.keys(AN_ZONES);
  const fallback = mode === 'sites' && !isManual(q) ? 'reference' : 'any';
  const current = keep || byId('an-zone').value || fallback;
  byId('an-zone').innerHTML = order.filter(z => places.has(z)).map(z => option(z, placeLabel(z, isManual(q)), z === current)).join('')
    + option('any', isManual(q) ? 'All plots' : 'All zones (site average)', current === 'any' || !places.size);
}

function refreshAbout() {
  const q = mode === 'free' ? null : AN_QUANTITIES[byId('an-quantity').value];
  byId('an-about').textContent = q ? q.about : (mode === 'free' ? 'Choose two to four parameters from any of your sites. Use it to look at how quantities evolve together — for example soil moisture, irrigation and rain.' : '');
  byId('an-step').disabled = !!(q && q.only_daily);
  if (q && q.only_daily) byId('an-step').value = 'day';
}

function setMode(m) {
  mode = m;
  // Two periods: when A and B are still the same, A becomes the period just before B.
  if (m === 'periods' && byId('an-from').value === byId('an-from2').value && byId('an-from2').value) {
    const from2 = new Date(byId('an-from2').value + 'T00:00:00Z'), to2 = new Date(byId('an-to2').value + 'T00:00:00Z');
    const days = Math.round((to2 - from2) / 864e5) + 1;
    const toA = new Date(from2); toA.setUTCDate(toA.getUTCDate() - 1);
    const fromA = new Date(toA); fromA.setUTCDate(fromA.getUTCDate() - days + 1);
    byId('an-from').value = iso(fromA); byId('an-to').value = iso(toA);
  }
  document.querySelectorAll('.an-seg button').forEach(b => b.classList.toggle('active', b.dataset.mode === m));
  document.querySelectorAll('[data-show]').forEach(el => el.classList.toggle('an-hide', !el.dataset.show.split(' ').includes(m)));
  refreshQuantities();
}

function iso(d) { return d.toISOString().slice(0, 10); }

function setPeriod(days) {
  const to = new Date(), from = new Date();
  from.setUTCDate(to.getUTCDate() - days + 1);
  byId('an-to').value = iso(to);
  byId('an-from').value = iso(from);
  const q = AN_QUANTITIES[byId('an-quantity').value];
  if (!(q && q.only_daily)) byId('an-step').value = days > 14 ? 'day' : 'hour';
}

function query() {
  const p = new URLSearchParams({ mode, from: byId('an-from').value, to: byId('an-to').value, step: byId('an-step').value });
  if (mode !== 'free') p.set('quantity', byId('an-quantity').value);
  if (mode === 'site' || mode === 'periods') p.set('site', byId('an-site').value);
  if (mode === 'sites') checkedSites().forEach(id => p.append('sites[]', id));
  if (mode === 'sites' || mode === 'periods') p.set('zone', byId('an-zone').value);
  if (mode === 'periods') { p.set('from2', byId('an-from2').value); p.set('to2', byId('an-to2').value); }
  if (mode === 'free') document.querySelectorAll('.an-param').forEach(s => { if (s.value) p.append('params[]', s.value); });
  return p.toString();
}

async function run() {
  byId('an-error').style.display = 'none';
  byId('an-run').disabled = true; byId('an-run').textContent = 'Analysing…';
  const q = query();
  try {
    const res = await fetch(`${AN_URLS.data}?${q}`, { headers: { Accept: 'application/json' } });
    const body = await res.json();
    if (!res.ok) throw new Error(Object.values(body.errors || {}).flat()[0] || body.message || 'The analysis failed.');
    byId('an-results').style.display = '';
    renderAnalysis(body);
    byId('an-csv').href = `${AN_URLS.csv}?${q}`;
    byId('an-report').href = `${AN_URLS.report}?${q}&print=1`;
    history.replaceState(null, '', `${AN_URLS.page}?${q}`);
  } catch (e) {
    byId('an-results').style.display = 'none';
    byId('an-error').textContent = e.message;
    byId('an-error').style.display = '';
  } finally {
    byId('an-run').disabled = false; byId('an-run').textContent = 'Analyse';
  }
}

// ── controls ──
byId('an-site').innerHTML = AN_CATALOG.map(s => option(s.id, s.name)).join('');
byId('an-sites').innerHTML = AN_CATALOG.map(s => `<label><input type="checkbox" value="${s.id}"> ${anEsc(s.name)}</label>`).join('');
const siteNames = Object.fromEntries(AN_CATALOG.map(s => [s.id, s.name]));
const paramGroups = {};
AN_PARAMS.forEach(p => (paramGroups[`${siteNames[p.site] || 'Site ' + p.site} — ${p.category}`] ??= []).push(p));
const paramOptions = Object.entries(paramGroups).map(([g, ps]) => `<optgroup label="${anEsc(g)}">${ps.map(p => option(p.id, p.name)).join('')}</optgroup>`).join('');
document.querySelectorAll('.an-param').forEach(s => s.innerHTML = option('', '—') + paramOptions);

document.querySelectorAll('.an-seg button').forEach(b => b.addEventListener('click', () => setMode(b.dataset.mode)));
byId('an-site').addEventListener('change', () => refreshQuantities());
document.querySelectorAll('#an-sites input').forEach(i => i.addEventListener('change', () => refreshQuantities()));
byId('an-quantity').addEventListener('change', () => { refreshZones(); refreshAbout(); });
document.querySelectorAll('.an-presets button').forEach(b => b.addEventListener('click', () => setPeriod(+b.dataset.days)));
byId('an-run').addEventListener('click', run);

// Clears the results and goes back to a blank selection (nothing is deleted).
byId('an-reset').addEventListener('click', () => {
  anCharts.splice(0).forEach(c => c.destroy());
  byId('an-results').style.display = 'none';
  byId('an-error').style.display = 'none';
  document.querySelectorAll('.an-param').forEach(s => s.value = '');
  history.replaceState(null, '', AN_URLS.page);
  setMode('site');
  refreshQuantities(availableQuantities().includes('par') ? 'par' : null);
  setPeriod(7);
  byId('an-from2').value = byId('an-from').value;
  byId('an-to2').value = byId('an-to').value;
  document.querySelector('.content')?.scrollTo({ top: 0, behavior: 'smooth' });
});

// ── initial state: from the URL when it holds an analysis, otherwise defaults ──
const init = new URLSearchParams(location.search);
if (init.get('mode')) {
  if (init.get('site')) byId('an-site').value = init.get('site');
  init.getAll('sites[]').forEach(id => { const box = document.querySelector(`#an-sites input[value="${id}"]`); if (box) box.checked = true; });
  init.getAll('params[]').forEach((id, i) => { const sel = byId('an-param-' + i); if (sel) sel.value = id; });
  setMode(['site', 'sites', 'periods', 'free'].includes(init.get('mode')) ? init.get('mode') : 'site');
  refreshQuantities(init.get('quantity'));
  if (init.get('zone')) refreshZones(init.get('zone'));
  ['from', 'to', 'from2', 'to2'].forEach(k => byId('an-' + k).value = init.get(k) || '');
  byId('an-step').value = init.get('step') || 'hour';
  run();
} else {
  document.querySelectorAll('#an-sites input').forEach((i, n) => { if (n < 3) i.checked = true; });
  setMode('site');
  refreshQuantities(availableQuantities().includes('par') ? 'par' : null);
  setPeriod(7);
  // Period B defaults to the last 7 days (period A is set when the mode is chosen).
  byId('an-from2').value = byId('an-from').value;
  byId('an-to2').value = byId('an-to').value;
}
</script>
@endif
@endpush

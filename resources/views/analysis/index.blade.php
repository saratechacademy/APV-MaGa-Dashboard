@extends('layouts.dashboard')
@section('page-title', 'Analysis')
@section('page-crumb', 'Compare zones and sites')

@push('styles')
<style>
.an-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);padding:16px;margin-bottom:14px}
.an-controls{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;align-items:end}
.an-seg{display:flex;background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:3px;gap:2px}
.an-seg button{flex:1;border:none;background:transparent;border-radius:6px;padding:8px 10px;font:500 13px 'DM Sans',sans-serif;color:var(--muted);cursor:pointer}
.an-seg button.active{background:var(--surface);color:var(--text);box-shadow:var(--shadow)}
.an-sites{display:flex;flex-wrap:wrap;gap:6px}
.an-sites label{display:flex;align-items:center;gap:6px;border:1px solid var(--border);border-radius:20px;padding:6px 12px;font-size:13px;cursor:pointer;background:var(--bg)}
.an-sites input{accent-color:var(--blue)}
.an-presets{display:flex;gap:6px;margin-top:6px}
.an-presets button{flex:1}
.an-about{font-size:12px;color:var(--muted);line-height:1.6;margin-top:12px}
.an-error{background:var(--amber-bg);border:1px solid var(--amber-bd);color:var(--amber);border-radius:8px;padding:10px 12px;font-size:13px;margin-bottom:14px}
.an-actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;margin-bottom:12px}
.an-block{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow);padding:16px;margin-bottom:14px}
</style>
@endpush

@section('content')
@if(!$catalog)
  <div class="an-card" style="text-align:center;color:var(--muted);font-size:13px;padding:30px">
    None of your sites has sensor data that can be compared yet.
  </div>
@else
<div class="an-card">
  <div class="an-controls">
    <div class="form-field">
      <span class="form-label">Compare</span>
      <div class="an-seg" role="group" aria-label="Comparison mode">
        <button type="button" data-mode="site" class="active">Zones of a site</button>
        <button type="button" data-mode="sites">Sites</button>
      </div>
    </div>

    <div class="form-field" id="an-site-field">
      <label class="form-label" for="an-site">Site</label>
      <select id="an-site" class="form-input"></select>
    </div>

    <div class="form-field" id="an-quantity-field">
      <label class="form-label" for="an-quantity">Quantity</label>
      <select id="an-quantity" class="form-input"></select>
    </div>

    <div class="form-field" id="an-zone-field" style="display:none">
      <label class="form-label" for="an-zone">Zone</label>
      <select id="an-zone" class="form-input"></select>
    </div>
  </div>

  <div class="form-field" id="an-sites-field" style="display:none;margin-top:12px">
    <span class="form-label">Sites (the first one ticked is the baseline)</span>
    <div class="an-sites" id="an-sites"></div>
  </div>

  <div class="an-controls" style="margin-top:12px">
    <div class="form-field">
      <label class="form-label" for="an-from">From</label>
      <input type="date" id="an-from" class="form-input">
    </div>
    <div class="form-field">
      <label class="form-label" for="an-to">To</label>
      <input type="date" id="an-to" class="form-input">
    </div>
    <div class="form-field">
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
    <a class="btn" id="an-csv" href="#">⬇ Data (CSV)</a>
    <a class="btn btn-blue" id="an-report" href="#" target="_blank" rel="noopener">⬇ Report (PDF)</a>
  </div>
  @include('analysis._results')
</div>
@endif
@endsection

@push('scripts')
@if($catalog)
<script>
const AN_CATALOG = @json($catalog);
const AN_QUANTITIES = @json($quantities);
const AN_ZONES = @json($zones);
const AN_URLS = { data: @json(route('analysis.data')), csv: @json(route('analysis.csv')), report: @json(route('analysis.report')), page: @json(route('analysis')) };

const byId = id => document.getElementById(id);
let mode = 'site';

function siteById(id) { return AN_CATALOG.find(s => String(s.id) === String(id)); }
function checkedSites() { return [...document.querySelectorAll('#an-sites input:checked')].map(i => i.value); }
function option(value, text, selected) { return `<option value="${value}" ${selected ? 'selected' : ''}>${anEsc(text)}</option>`; }

// Quantities worth offering: measured in two zones of the site, or on two of the ticked sites.
function availableQuantities() {
  if (mode === 'site') {
    const s = siteById(byId('an-site').value);
    return s ? Object.keys(s.quantities).filter(q => s.quantities[q].length >= 2) : [];
  }
  const counts = {};
  checkedSites().forEach(id => Object.keys(siteById(id)?.quantities || {}).forEach(q => counts[q] = (counts[q] || 0) + 1));
  return Object.keys(counts).filter(q => counts[q] >= 2);
}

function refreshQuantities(keep) {
  const list = availableQuantities().filter(q => AN_QUANTITIES[q]);
  const current = keep || byId('an-quantity').value;
  byId('an-quantity').innerHTML = list.length
    ? list.map(q => option(q, AN_QUANTITIES[q].title, q === current)).join('')
    : option('', mode === 'site' ? 'Nothing to compare on this site' : 'Tick at least two sites', true);
  refreshZones();
  refreshAbout();
}

function refreshZones(keep) {
  if (mode !== 'sites') return;
  const q = byId('an-quantity').value;
  const zones = new Set();
  checkedSites().forEach(id => (siteById(id)?.quantities[q] || []).forEach(z => zones.add(z)));
  const current = keep || byId('an-zone').value || 'reference';
  byId('an-zone').innerHTML = Object.keys(AN_ZONES).filter(z => zones.has(z)).map(z => option(z, AN_ZONES[z], z === current)).join('')
    + option('any', 'All zones (site average)', current === 'any' || !zones.size);
}

function refreshAbout() {
  const q = AN_QUANTITIES[byId('an-quantity').value];
  byId('an-about').textContent = q ? q.about : '';
  byId('an-step').disabled = !!(q && q.only_daily);
  if (q && q.only_daily) byId('an-step').value = 'day';
}

function setMode(m) {
  mode = m;
  document.querySelectorAll('.an-seg button').forEach(b => b.classList.toggle('active', b.dataset.mode === m));
  byId('an-site-field').style.display = m === 'site' ? '' : 'none';
  byId('an-sites-field').style.display = m === 'sites' ? '' : 'none';
  byId('an-zone-field').style.display = m === 'sites' ? '' : 'none';
  refreshQuantities();
}

function setPeriod(days) {
  const to = new Date(), from = new Date();
  from.setUTCDate(to.getUTCDate() - days + 1);
  byId('an-to').value = to.toISOString().slice(0, 10);
  byId('an-from').value = from.toISOString().slice(0, 10);
  if (!(AN_QUANTITIES[byId('an-quantity').value] || {}).only_daily) byId('an-step').value = days > 14 ? 'day' : 'hour';
}

function query() {
  const p = new URLSearchParams({ mode, quantity: byId('an-quantity').value, from: byId('an-from').value, to: byId('an-to').value, step: byId('an-step').value });
  if (mode === 'site') p.set('site', byId('an-site').value);
  else { checkedSites().forEach(id => p.append('sites[]', id)); p.set('zone', byId('an-zone').value); }
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

// ── initial state: from the URL when it holds an analysis, otherwise defaults ──
byId('an-site').innerHTML = AN_CATALOG.map(s => option(s.id, s.name)).join('');
byId('an-sites').innerHTML = AN_CATALOG.map(s => `<label><input type="checkbox" value="${s.id}"> ${anEsc(s.name)}</label>`).join('');

document.querySelectorAll('.an-seg button').forEach(b => b.addEventListener('click', () => setMode(b.dataset.mode)));
byId('an-site').addEventListener('change', () => refreshQuantities());
document.querySelectorAll('#an-sites input').forEach(i => i.addEventListener('change', () => refreshQuantities()));
byId('an-quantity').addEventListener('change', () => { refreshZones(); refreshAbout(); });
document.querySelectorAll('.an-presets button').forEach(b => b.addEventListener('click', () => setPeriod(+b.dataset.days)));
byId('an-run').addEventListener('click', run);

const init = new URLSearchParams(location.search);
if (init.get('quantity')) {
  if (init.get('site')) byId('an-site').value = init.get('site');
  init.getAll('sites[]').forEach(id => { const box = document.querySelector(`#an-sites input[value="${id}"]`); if (box) box.checked = true; });
  setMode(init.get('mode') === 'sites' ? 'sites' : 'site');
  refreshQuantities(init.get('quantity'));
  if (init.get('zone')) refreshZones(init.get('zone'));
  byId('an-from').value = init.get('from') || ''; byId('an-to').value = init.get('to') || '';
  byId('an-step').value = init.get('step') || 'hour';
  run();
} else {
  document.querySelectorAll('#an-sites input').forEach((i, n) => { if (n < 3) i.checked = true; });
  setMode('site');
  const par = availableQuantities().includes('par') ? 'par' : null;
  refreshQuantities(par);
  setPeriod(7);
}
</script>
@endif
@endpush

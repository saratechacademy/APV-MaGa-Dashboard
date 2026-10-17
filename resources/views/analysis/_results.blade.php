{{-- Results of an analysis, shared by the page and the printable report.
     renderAnalysis(result, {animate}) fills the elements below. --}}
<style>
.an-findings{margin:0;padding-left:18px;font-size:13px;line-height:1.6}
.an-findings li{margin-bottom:6px}
.an-table{width:100%;border-collapse:collapse;font-size:12px}
.an-table th,.an-table td{border-bottom:1px solid var(--border,#e2e8f0);padding:7px 8px;text-align:right;white-space:nowrap}
.an-table th:first-child,.an-table td:first-child{text-align:left;white-space:normal}
.an-table th{font-size:11px;color:var(--muted,#64748b);font-weight:600}
.an-chart{position:relative;height:280px}
.an-chart-title{font-size:13px;font-weight:600;margin-bottom:2px}
.an-chart-sub{font-size:11px;color:var(--muted,#64748b);margin-bottom:8px}
.an-note{font-size:12px;color:var(--muted,#64748b);line-height:1.6}
.an-pos{color:#15803d}.an-neg{color:#b91c1c}
</style>

<div class="an-block">
  <div class="an-chart-title">Findings</div>
  <ul class="an-findings" id="an-findings"></ul>
</div>

<div class="an-block">
  <div class="an-chart-title">Summary</div>
  <div style="overflow-x:auto"><table class="an-table" id="an-table"></table></div>
</div>

<div class="an-block">
  <div class="an-chart-title" id="an-series-title">Time series</div>
  <div class="an-chart-sub" id="an-series-sub"></div>
  <div class="an-chart"><canvas id="an-series"></canvas></div>
</div>

<div class="an-block" id="an-gaps-block">
  <div class="an-chart-title">Difference from the baseline</div>
  <div class="an-chart-sub" id="an-gaps-sub"></div>
  <div class="an-chart"><canvas id="an-gaps"></canvas></div>
</div>

<div class="an-block" id="an-diurnal-block">
  <div class="an-chart-title">Average day</div>
  <div class="an-chart-sub">Mean value at each hour of the day (UTC) over the period: shows when in the day the series differ.</div>
  <div class="an-chart"><canvas id="an-diurnal"></canvas></div>
</div>

<div class="an-block">
  <div class="an-chart-title">Method and data quality</div>
  <div class="an-note" id="an-method"></div>
</div>

<script>
const AN_COLORS = ['#334155', '#16a34a', '#2563eb', '#d97706', '#9333ea', '#dc2626', '#0891b2'];
const anCharts = [];

function anFmt(v) {
  if (v === null || v === undefined) return '—';
  const a = Math.abs(v);
  return v.toLocaleString('en-GB', { maximumFractionDigits: a >= 100 ? 0 : (a >= 10 ? 1 : 2) });
}

function anEsc(s) {
  return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function renderAnalysis(r, opts = {}) {
  anCharts.splice(0).forEach(c => c.destroy());
  const unit = r.unit ? ' ' + r.unit : '';
  const daily = r.step === 'day';
  const fmtTime = ms => {
    const d = new Date(ms);
    return daily
      ? d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', timeZone: 'UTC' })
      : d.toLocaleString('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit', timeZone: 'UTC' });
  };
  const labels = r.labels.map(fmtTime);
  const base = r.series[0];

  document.getElementById('an-findings').innerHTML = r.findings.map(f => `<li>${anEsc(f)}</li>`).join('');

  const rows = r.series.map((s, i) => {
    const c = s.comparison;
    const diff = c ? `<span class="${c.difference >= 0 ? 'an-pos' : 'an-neg'}">${c.difference >= 0 ? '+' : '−'}${anFmt(Math.abs(c.difference))}</span>` + (c.percent !== null ? ` (${c.percent >= 0 ? '+' : '−'}${anFmt(Math.abs(c.percent))} %)` : '') : (i === 0 ? 'baseline' : '—');
    return `<tr><td><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:${AN_COLORS[i % AN_COLORS.length]};margin-right:6px"></span>${anEsc(s.label)}</td>
      <td>${s.sensors}</td><td>${anFmt(s.stats.mean)}</td><td>${anFmt(s.stats.min)}</td><td>${anFmt(s.stats.max)}</td>
      <td>${anFmt(s.stats.std)}</td><td>${s.stats.coverage} %</td><td>${diff}</td></tr>`;
  }).join('');
  document.getElementById('an-table').innerHTML =
    `<thead><tr><th>Series</th><th>Sensors</th><th>Mean${anEsc(unit)}</th><th>Min</th><th>Max</th><th>Std dev</th><th>Data available</th><th>vs ${anEsc(base.label)}</th></tr></thead><tbody>${rows}</tbody>`;

  document.getElementById('an-series-title').textContent = r.title + (r.unit ? ` (${r.unit})` : '');
  document.getElementById('an-series-sub').textContent = (daily ? 'Daily' : 'Hourly') + ' values, UTC. Breaks in a line are periods without data.';

  const common = { responsive: true, maintainAspectRatio: false, animation: opts.animate === false ? false : undefined,
    interaction: { mode: 'index', intersect: false },
    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
    scales: { x: { ticks: { maxTicksLimit: 8, maxRotation: 0, font: { size: 10 } } }, y: { ticks: { font: { size: 10 } } } } };

  anCharts.push(new Chart(document.getElementById('an-series'), {
    type: daily ? 'bar' : 'line',
    data: { labels, datasets: r.series.map((s, i) => ({ label: s.label, data: s.points, borderColor: AN_COLORS[i % AN_COLORS.length],
      backgroundColor: AN_COLORS[i % AN_COLORS.length] + (daily ? 'cc' : '22'), borderWidth: i === 0 ? 2 : 1.5, borderDash: i === 0 && !daily ? [5, 3] : [],
      pointRadius: 0, spanGaps: false, tension: 0.2 })) },
    options: common,
  }));

  const others = r.series.slice(1);
  document.getElementById('an-gaps-sub').textContent = `Each series minus ${base.label}${unit ? ', in' + unit : ''}. Above zero: higher than the baseline.`;
  anCharts.push(new Chart(document.getElementById('an-gaps'), {
    type: daily ? 'bar' : 'line',
    data: { labels, datasets: others.map((s, i) => ({ label: s.label + ' − ' + base.label, data: s.gaps, borderColor: AN_COLORS[(i + 1) % AN_COLORS.length],
      backgroundColor: AN_COLORS[(i + 1) % AN_COLORS.length] + (daily ? 'cc' : '22'), borderWidth: 1.5, pointRadius: 0, spanGaps: false, tension: 0.2 })) },
    options: common,
  }));

  const hasDiurnal = r.series.some(s => s.diurnal && s.diurnal.length);
  document.getElementById('an-diurnal-block').style.display = hasDiurnal ? '' : 'none';
  if (hasDiurnal) {
    anCharts.push(new Chart(document.getElementById('an-diurnal'), {
      type: 'line',
      data: { labels: [...Array(24).keys()].map(h => String(h).padStart(2, '0') + ':00'),
        datasets: r.series.map((s, i) => ({ label: s.label, data: s.diurnal, borderColor: AN_COLORS[i % AN_COLORS.length],
          backgroundColor: 'transparent', borderWidth: i === 0 ? 2 : 1.5, borderDash: i === 0 ? [5, 3] : [], pointRadius: 2, tension: 0.3 })) },
      options: common,
    }));
  }

  const bucket = daily ? 'day' : 'hour';
  const common_n = others.map(s => s.comparison ? `${s.label}: ${s.comparison.common}` : null).filter(Boolean).join(', ');
  document.getElementById('an-method').innerHTML = [
    `Values are ${daily ? 'daily' : 'hourly'} ${r.quantity === 'rain' || r.quantity === 'daily_water_use' ? 'totals' : 'averages'} of the sensors' readings, in UTC, from ${anEsc(fmtDate(r.from))} to ${anEsc(fmtDate(r.to, true))}.`,
    'A series made of several sensors is the average of those sensors, each counting equally.',
    `Differences from ${anEsc(base.label)} use only the ${bucket}s where both series have data (${anEsc(common_n || 'none')}).`,
    r.quantity === 'par' && daily ? 'Daily light integral = mean PAR over the day × 0.0864; days with missing hours under-estimate it.' : null,
    `"Data available" is the share of ${bucket}s of the period with at least one reading (${r.expected} ${bucket}s in total).`,
    anEsc(r.about),
  ].filter(Boolean).map(t => `<p style="margin:0 0 6px">${t}</p>`).join('');

  function fmtDate(iso, endExclusive) {
    const d = new Date(iso);
    if (endExclusive) d.setUTCDate(d.getUTCDate() - 1);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' });
  }
}
</script>

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

<div class="an-block" id="an-scatter-block">
  <div class="an-chart-title">Relationship</div>
  <div class="an-chart-sub" id="an-scatter-sub"></div>
  <div class="an-chart"><canvas id="an-scatter"></canvas></div>
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

function anDate(iso, endExclusive) {
  const d = new Date(iso);
  if (endExclusive) d.setUTCDate(d.getUTCDate() - 1);
  return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' });
}

function renderAnalysis(r, opts = {}) {
  anCharts.splice(0).forEach(c => c.destroy());
  const daily = r.step === 'day';
  const unitOf = s => s.unit ? ' ' + s.unit : '';
  const fmtTime = v => {
    if (r.relative) return daily ? `Day ${v + 1}` : `Day ${Math.floor(v / 24) + 1}, ${String(v % 24).padStart(2, '0')}:00`;
    const d = new Date(v);
    return daily
      ? d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', timeZone: 'UTC' })
      : d.toLocaleString('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit', timeZone: 'UTC' });
  };
  const labels = r.labels.map(fmtTime);
  const base = r.series[0];
  const color = i => AN_COLORS[i % AN_COLORS.length];
  // One vertical axis per unit (free comparison of different quantities).
  const axisOf = s => r.comparable ? 'y' : (r.units.indexOf(s.unit) === 0 ? 'y' : 'y1');

  document.getElementById('an-findings').innerHTML = r.findings.map(f => `<li>${anEsc(f)}</li>`).join('');

  const rows = r.series.map((s, i) => {
    const c = s.comparison;
    const diff = c ? `<span class="${c.difference >= 0 ? 'an-pos' : 'an-neg'}">${c.difference >= 0 ? '+' : '−'}${anFmt(Math.abs(c.difference))}</span>` + (c.percent !== null ? ` (${c.percent >= 0 ? '+' : '−'}${anFmt(Math.abs(c.percent))} %)` : '') : (i === 0 && r.comparable ? 'baseline' : '—');
    return `<tr><td><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:${color(i)};margin-right:6px"></span>${anEsc(s.label)}</td>
      ${r.comparable ? '' : `<td>${anEsc(s.unit || '—')}</td>`}
      <td>${s.sensors}</td><td>${anFmt(s.stats.mean)}</td><td>${anFmt(s.stats.min)}</td><td>${anFmt(s.stats.max)}</td>
      <td>${anFmt(s.stats.std)}</td><td>${s.stats.coverage} %</td>${r.comparable ? `<td>${diff}</td>` : ''}</tr>`;
  }).join('');
  document.getElementById('an-table').innerHTML =
    `<thead><tr><th>Series</th>${r.comparable ? '' : '<th>Unit</th>'}<th>Sensors</th><th>Mean${r.comparable ? anEsc(unitOf(base)) : ''}</th><th>Min</th><th>Max</th><th>Std dev</th><th>Data available</th>${r.comparable ? `<th>vs ${anEsc(base.label)}</th>` : ''}</tr></thead><tbody>${rows}</tbody>`;

  document.getElementById('an-series-title').textContent = r.comparable ? r.title + (r.unit ? ` (${r.unit})` : '') : 'Parameters compared';
  document.getElementById('an-series-sub').textContent = (daily ? 'Daily' : 'Hourly') + ' values, UTC'
    + (r.relative ? ', each period from its own first day' : '') + '. Breaks in a line are periods without data.'
    + (r.comparable ? '' : ' Left axis: ' + r.units[0] + (r.units[1] ? '; right axis: ' + r.units[1] : '') + '.');

  const common = { responsive: true, maintainAspectRatio: false, animation: opts.animate === false ? false : undefined,
    interaction: { mode: 'index', intersect: false },
    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
    scales: { x: { ticks: { maxTicksLimit: 8, maxRotation: 0, font: { size: 10 } } }, y: { ticks: { font: { size: 10 } } } } };
  const twoAxes = !r.comparable && r.units.length > 1;
  const seriesOptions = twoAxes
    ? { ...common, scales: { ...common.scales, y: { position: 'left', title: { display: true, text: r.units[0] }, ticks: { font: { size: 10 } } },
        y1: { position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: r.units[1] }, ticks: { font: { size: 10 } } } } }
    : common;

  anCharts.push(new Chart(document.getElementById('an-series'), {
    type: daily ? 'bar' : 'line',
    data: { labels, datasets: r.series.map((s, i) => ({ label: s.label, data: s.points, yAxisID: axisOf(s), borderColor: color(i),
      backgroundColor: color(i) + (daily ? 'cc' : '22'), borderWidth: i === 0 ? 2 : 1.5, borderDash: i === 0 && !daily && r.comparable ? [5, 3] : [],
      pointRadius: 0, spanGaps: false, tension: 0.2 })) },
    options: seriesOptions,
  }));

  const others = r.series.slice(1).filter(s => s.gaps);
  document.getElementById('an-gaps-block').style.display = others.length ? '' : 'none';
  if (others.length) {
    const gapUnit = base.unit === '%' ? 'percentage points' : base.unit;
    document.getElementById('an-gaps-sub').textContent = `Each series minus ${base.label}${gapUnit ? ', in ' + gapUnit : ''}. Above zero: higher than the baseline.`;
    anCharts.push(new Chart(document.getElementById('an-gaps'), {
      type: daily ? 'bar' : 'line',
      data: { labels, datasets: others.map(s => { const i = r.series.indexOf(s); return { label: s.label + ' − ' + base.label, data: s.gaps,
        borderColor: color(i), backgroundColor: color(i) + (daily ? 'cc' : '22'), borderWidth: 1.5, pointRadius: 0, spanGaps: false, tension: 0.2 }; }) },
      options: common,
    }));
  }

  const c = r.correlation;
  document.getElementById('an-scatter-block').style.display = c ? '' : 'none';
  if (c) {
    const xs = c.points.map(p => p[0]);
    const lo = Math.min(...xs), hi = Math.max(...xs);
    document.getElementById('an-scatter-sub').textContent =
      `Each point is one ${daily ? 'day' : 'hour'} with data for both. r = ${anFmt(c.r)} (n = ${c.n}); trend line: y = ${anFmt(c.slope)} × x ${c.intercept >= 0 ? '+' : '−'} ${anFmt(Math.abs(c.intercept))}.`;
    anCharts.push(new Chart(document.getElementById('an-scatter'), {
      type: 'scatter',
      data: { datasets: [
        { label: `${c.y} vs ${c.x}`, data: c.points.map(p => ({ x: p[0], y: p[1] })), backgroundColor: color(1) + '99', pointRadius: 2.5 },
        { type: 'line', label: 'Trend', data: [{ x: lo, y: c.slope * lo + c.intercept }, { x: hi, y: c.slope * hi + c.intercept }],
          borderColor: color(0), borderDash: [5, 3], pointRadius: 0, borderWidth: 1.5 },
      ] },
      options: { ...common, interaction: { mode: 'nearest', intersect: true },
        scales: { x: { type: 'linear', title: { display: true, text: c.x + (c.xUnit ? ` (${c.xUnit})` : '') }, ticks: { font: { size: 10 } } },
                  y: { title: { display: true, text: c.y + (c.yUnit ? ` (${c.yUnit})` : '') }, ticks: { font: { size: 10 } } } } },
    }));
  }

  const hasDiurnal = r.series.some(s => s.diurnal && s.diurnal.length);
  document.getElementById('an-diurnal-block').style.display = hasDiurnal ? '' : 'none';
  if (hasDiurnal) {
    anCharts.push(new Chart(document.getElementById('an-diurnal'), {
      type: 'line',
      data: { labels: [...Array(24).keys()].map(h => String(h).padStart(2, '0') + ':00'),
        datasets: r.series.map((s, i) => ({ label: s.label, data: s.diurnal, yAxisID: axisOf(s), borderColor: color(i),
          backgroundColor: 'transparent', borderWidth: i === 0 ? 2 : 1.5, borderDash: i === 0 && r.comparable ? [5, 3] : [], pointRadius: 2, tension: 0.3 })) },
      options: seriesOptions,
    }));
  }

  const bucket = daily ? 'day' : 'hour';
  const sums = ['rain', 'daily_water_use'].includes(r.quantity);
  const commonN = r.series.slice(1).map(s => s.comparison ? `${s.label}: ${s.comparison.common}` : null).filter(Boolean).join(', ');
  const periods = r.relative
    ? r.series.map(s => `${s.label.split(' (')[0]}: ${anDate(s.period[0])} to ${anDate(s.period[1], true)}`).join('; ')
    : `${anDate(r.from)} to ${anDate(r.to, true)}`;
  document.getElementById('an-method').innerHTML = [
    `Values are ${daily ? 'daily' : 'hourly'} ${sums ? 'totals' : 'averages'} of the readings, in UTC. Period${r.relative ? 's' : ''}: ${anEsc(periods)}.`,
    r.relative ? `Periods are compared ${bucket} by ${bucket}: ${bucket} 1 of period A against ${bucket} 1 of period B, and so on.` : null,
    'A series made of several sensors is the average of those sensors, each counting equally. Field measurements are the average of the entries of each day.',
    commonN ? `Differences from ${anEsc(base.label)} use only the ${bucket}s where both series have data (${anEsc(commonN)}).` : null,
    c ? `The correlation coefficient r (Pearson) ranges from −1 to 1; it is computed on the ${c.n} ${bucket}s where both series have data. It measures how closely the series move together, not causation.` : null,
    r.quantity === 'par' && daily ? 'Daily light integral = mean PAR over the day × 0.0864; days with missing hours under-estimate it.' : null,
    `"Data available" is the share of ${bucket}s of the period with at least one reading.`,
    r.about ? anEsc(r.about) : null,
  ].filter(Boolean).map(t => `<p style="margin:0 0 6px">${t}</p>`).join('');
}
</script>

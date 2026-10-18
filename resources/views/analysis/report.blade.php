<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $fileName }}</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
:root{--text:#0d1321;--muted:#64748b;--border:#e2e8f0;--blue:#1d6ed8}
*{box-sizing:border-box}
body{margin:0;background:#f1f5f9;font-family:'DM Sans',sans-serif;color:var(--text)}
.page{width:210mm;max-width:100%;margin:24px auto;background:#fff;padding:14mm 15mm;box-shadow:0 1px 4px rgba(0,0,0,.08)}
.head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #16a34a;padding-bottom:12px;margin-bottom:18px}
.brand{font-size:20px;font-weight:700;color:#16a34a}
.brand small{display:block;font-size:11px;font-weight:500;color:var(--muted)}
.meta{text-align:right;font-size:11px;color:var(--muted);line-height:1.6}
h1{font-size:19px;margin:0 0 4px}
.desc{font-size:13px;color:var(--muted);margin-bottom:18px}
.an-block{margin-bottom:20px;break-inside:avoid}
.toolbar{width:210mm;max-width:100%;margin:16px auto 0;display:flex;justify-content:flex-end;gap:8px}
.toolbar button{font:500 13px 'DM Sans',sans-serif;padding:8px 14px;border-radius:7px;border:1px solid var(--border);background:#fff;cursor:pointer}
.toolbar .primary{background:var(--blue);color:#fff;border-color:var(--blue)}
.foot{border-top:1px solid var(--border);margin-top:10px;padding-top:8px;font-size:10px;color:var(--muted);display:flex;justify-content:space-between}
@page{size:A4;margin:0}
body{-webkit-print-color-adjust:exact;print-color-adjust:exact}
@media print{
  body{background:#fff}
  .toolbar{display:none}
  .page{margin:0;padding:12mm 15mm 0;box-shadow:none}
  /* No page margin (it would print the browser's headers): each block brings its own top space. */
  .an-block{padding-top:6mm;margin-bottom:0}
}
@media (max-width:640px){ .page{margin:0;padding:20px 16px} .head{flex-direction:column;gap:8px} .meta{text-align:left} }
</style>
</head>
<body>
<div class="toolbar">
  <button type="button" onclick="window.close()">Close</button>
  <button type="button" class="primary" onclick="window.print()">Save as PDF / Print</button>
</div>

<div class="page">
  <div class="head">
    <div class="brand">APV-MaGa<small>Agrivoltaic monitoring platform</small></div>
    <div class="meta">
      Generated {{ $generatedAt->format('d M Y, H:i') }} UTC<br>
      by {{ auth()->user()->name }}<br>
      {{ config('app.url') }}
    </div>
  </div>

  <h1>{{ $result['title'] }}{{ $result['unit'] ? ' (' . $result['unit'] . ')' : '' }}</h1>
  <div class="desc">{{ $description }}</div>

  @include('analysis._results')

  <div class="foot">
    <span>APV-MaGa — analysis report</span>
    <span>Data: ThingsBoard, LI-COR Cloud and field entries, as stored on {{ $generatedAt->format('d M Y') }}</span>
  </div>
</div>

<script>
renderAnalysis(@json($result), { animate: false });
@if(request()->boolean('print'))
  // Let the charts draw, then open the print dialog ("Save as PDF").
  window.addEventListener('load', () => setTimeout(() => window.print(), 600));
@endif
</script>
</body>
</html>

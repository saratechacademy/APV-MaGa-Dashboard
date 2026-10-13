<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Concerns\AuthorizesSiteAccess;
use App\Http\Controllers\Concerns\ResolvesDateRange;
use App\Models\Site;
use App\Services\SiteOverview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use AuthorizesSiteAccess, ResolvesDateRange;

    // ── Index : tous les sites ─────────────────────────────────
    public function index(SiteOverview $overview)
    {
        $sites = $this->overviewSites();

        $cards = $sites->mapWithKeys(fn (Site $site) => [$site->id => [
            'tiles'     => $overview->tiles($site),
            'status'    => $overview->status($site),
            'sparkline' => $overview->sparklineMetric($site),
        ]]);

        $chartMetrics = $overview->chartMetrics($sites);
        $liveStatus   = $overview->liveStatus($sites);

        return view('dashboard.index', compact('sites', 'cards', 'chartMetrics', 'liveStatus'));
    }

    // ── Index : séries des graphiques comparatifs ──────────────
    // One request for the whole overview: every chart metric plus each site's
    // sparkline metric, all on the same bucketed time axis.
    public function overviewData(Request $request, SiteOverview $overview)
    {
        $sites = $this->overviewSites();
        [$from, $to] = $this->resolveDateRange($request, 24);

        $sparklines = $sites->mapWithKeys(fn (Site $site) => [$site->id => $overview->sparklineMetric($site)])->filter();
        $metrics    = array_values(array_unique(array_merge($overview->chartMetrics($sites), $sparklines->values()->all())));

        return response()->json(['success' => true, 'sparklines' => $sparklines]
            + $overview->series($sites, $metrics, $from, $to));
    }

    /** Active sites the current user may see, loaded for the overview. */
    private function overviewSites()
    {
        $user  = Auth::user();
        $query = Site::where('status', 'active')
            ->with(['activeCategories.activeParameters.latestReading'])
            ->latest();

        if (!$user->isAdmin()) {
            // Sites via table pivot + sites via user_id direct (legacy)
            $query->whereIn('id', array_unique(array_merge(
                $user->assignedSites()->pluck('sites.id')->toArray(),
                Site::where('user_id', $user->id)->pluck('id')->toArray(),
            )));
        }

        return $query->get();
    }

    // ── Site detail ────────────────────────────────────────────
    public function site(Site $site, Request $request, SiteOverview $overview)
    {
        $this->authorizeSiteAccess($site);

        $site->load([
            'activeCategories.activeParameters.group',
            'activeCategories.activeParameters.latestReading',
            'activeCategories.activeParameters.latestManualReading',
            'activeCategories.activeParameters.actuatorCommand',
            'activeCategories.activeCharts.parameters',
        ]);

        $hasManual = $site->activeCategories
            ->flatMap(fn($c) => $c->activeParameters)
            ->where('input_type', 'manual')
            ->count() > 0;

        $range      = $request->get('range', '24h');
        $chartData  = [];
        $liveStatus = $overview->liveStatus([$site]);

        return view('dashboard.site', compact('site', 'hasManual', 'chartData', 'range', 'liveStatus'));
    }

    // ── Chart data (JSON) ──────────────────────────────────────
    public function chartData(Request $request, string $site, string $category)
    {
        $site = Site::where('slug', $site)->orWhere('id', $site)->firstOrFail();
        $this->authorizeSiteAccess($site);
        [$from, $to] = $this->resolveDateRange($request, 24);

        $cat = $site->categories()
            ->where('slug', $category)
            ->where('is_active', true)
            ->first();

        if (!$cat) {
            return response()->json(['error' => 'Category not found'], 404);
        }

        $paramSlugs = $request->input('params', []);
        $parameters = $cat->activeParameters()
            ->when(!empty($paramSlugs), fn($q) => $q->whereIn('slug', $paramSlugs))
            ->get();

        // Each parameter can have a different number of readings in the window
        // (different report intervals, or a mix of sensor/manual data), so we
        // can't just zip their raw value arrays together — that silently plots
        // dataset B's values under dataset A's timestamps. Instead: build a
        // per-parameter [timestamp => value] map, take the union of every
        // timestamp seen across all parameters as the shared x-axis, then read
        // each dataset off that same axis (missing points become null — the
        // frontend already renders those as gaps via spanGaps: true).
        $seriesByParam = [];
        $timeLabels    = [];
        // Past two days a bare "14:20" no longer says which day it was.
        $sensorLabel   = $from->diffInHours($to) > 48 ? 'd/m H:i' : 'H:i';

        foreach ($parameters as $param) {
            if (in_array($param->data_type, ['string', 'boolean', 'switch'])) continue;

            if ($param->input_type === 'sensor') {
                $rows = \App\Models\SensorReading::where('site_id', $site->id)
                    ->where('site_parameter_id', $param->id)
                    ->whereBetween('read_at', [$from, $to])
                    ->orderBy('read_at')
                    ->get()
                    ->map(fn($r) => [
                        'key'   => $r->read_at->format('Y-m-d H:i'),
                        'label' => $r->read_at->format($sensorLabel),
                        'value' => $r->value,
                    ]);
            } else {
                $rows = \App\Models\ManualReading::where('site_id', $site->id)
                    ->where('site_parameter_id', $param->id)
                    ->whereBetween('reading_date', [$from, $to])
                    ->orderBy('reading_date')
                    ->get()
                    ->map(fn($r) => [
                        'key'   => $r->reading_date->format('Y-m-d H:i'),
                        'label' => $r->reading_date->format('d/m'),
                        'value' => is_numeric($r->value) ? (float) $r->value : null,
                    ])
                    ->filter(fn($r) => $r['value'] !== null)
                    ->values();
            }

            if ($rows->isEmpty()) continue;

            $map = [];
            foreach ($rows as $r) {
                $map[$r['key']] = $r['value'];
                $timeLabels[$r['key']] = $r['label'];
            }

            $seriesByParam[] = [
                'label' => $param->name . ($param->unit ? ' (' . $param->unit . ')' : ''),
                'slug'  => $param->slug,
                'map'   => $map,
            ];
        }

        ksort($timeLabels);

        // The axis has one slot per reading, so a two-day outage would take no
        // width at all and the curve would look continuous. Long silences —
        // far beyond the usual interval, and at least two hours — get empty
        // slots (at most 40 each) so they show as a blank stretch.
        $stamps = array_map(fn ($key) => strtotime($key), array_keys($timeLabels));
        $steps  = [];
        for ($i = 1; $i < count($stamps); $i++) {
            $steps[] = $stamps[$i] - $stamps[$i - 1];
        }
        if ($steps) {
            sort($steps);
            $usual = max(60, $steps[intdiv(count($steps), 2)]);
            $limit = max(2 * 3600, 6 * $usual);

            for ($i = 1; $i < count($stamps); $i++) {
                $gap = $stamps[$i] - $stamps[$i - 1];
                if ($gap <= $limit) {
                    continue;
                }
                $step = max($usual, (int) ceil($gap / 40));
                for ($t = $stamps[$i - 1] + $step; $t < $stamps[$i]; $t += $step) {
                    $timeLabels[date('Y-m-d H:i', $t)] ??= date($sensorLabel, $t);
                }
            }
            ksort($timeLabels);
        }
        $timeKeys = array_keys($timeLabels);
        $labels   = array_values($timeLabels);

        $datasets = array_map(fn($s) => [
            'label' => $s['label'],
            'slug'  => $s['slug'],
            'data'  => array_map(fn($key) => $s['map'][$key] ?? null, $timeKeys),
        ], $seriesByParam);

        // Empty window: say when the data stops, so "no data" reads as
        // "sensor silent since..." or "not uploaded yet" rather than a bug.
        $lastReadingAt = null;
        if (empty($datasets)) {
            $ids = $parameters->pluck('id');
            $lastReadingAt = collect([
                \App\Models\SensorReading::whereIn('site_parameter_id', $ids)->max('read_at'),
                \App\Models\ManualReading::whereIn('site_parameter_id', $ids)->max('reading_date'),
            ])->filter()->max();
        }

        return response()->json([
            'success'         => true,
            'labels'          => $labels,
            // Epoch seconds of each label: the axis is not linear in time (one
            // slot per reading), so the page needs these to see how long a
            // silence between two points really was.
            'timestamps'      => array_map(fn ($key) => \Illuminate\Support\Carbon::parse($key, config('app.timezone'))->getTimestamp(), $timeKeys),
            'datasets'        => $datasets,
            'from'            => $from->toISOString(),
            'to'              => $to->toISOString(),
            'last_reading_at' => $lastReadingAt ? \Illuminate\Support\Carbon::parse($lastReadingAt)->toISOString() : null,
        ]);
    }

    // ── Raw Data (HTML partial) ────────────────────────────────
    public function rawData(Request $request, string $site)
    {
        $site = Site::where('slug', $site)->orWhere('id', $site)->firstOrFail();
        $this->authorizeSiteAccess($site);

        [$from, $to] = $this->resolveDateRange($request, 24);
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = 20;

        $site->load(['activeCategories.activeParameters', 'activeCategories.site']);
        session(['current_site_slug' => $site->slug]);

        $html = '';

        foreach ($site->activeCategories as $category) {
            $params       = $category->activeParameters;
            $sensorParams = $params->where('input_type', 'sensor');
            $manualParams = $params->where('input_type', 'manual');

            // Only the timestamps of the requested page are read, then the
            // readings at those timestamps: a 30-day window holds hundreds of
            // thousands of readings, far too many to load and slice in PHP.
            $offset = ($page - 1) * $perPage;

            $sensorQuery = \App\Models\SensorReading::where('site_id', $site->id)
                ->whereIn('site_parameter_id', $sensorParams->pluck('id'))
                ->whereBetween('read_at', [$from, $to]);

            $totalSensor  = (clone $sensorQuery)->distinct()->count('read_at');
            $sensorStamps = (clone $sensorQuery)->toBase()->select('read_at')->distinct()
                ->orderByDesc('read_at')->skip($offset)->take($perPage)->pluck('read_at');
            $sensorPaged  = $sensorStamps->isEmpty() ? collect() : (clone $sensorQuery)
                ->whereIn('read_at', $sensorStamps)->orderByDesc('read_at')->get()
                ->groupBy(fn($r) => $r->read_at->format('Y-m-d H:i:s'));

            $manualQuery = \App\Models\ManualReading::where('site_id', $site->id)
                ->whereIn('site_parameter_id', $manualParams->pluck('id'))
                ->whereBetween('reading_date', [$from, $to]);

            $totalManual  = (clone $manualQuery)->distinct()->count('reading_date');
            $manualStamps = (clone $manualQuery)->toBase()->select('reading_date')->distinct()
                ->orderByDesc('reading_date')->skip($offset)->take($perPage)->pluck('reading_date');
            $manualPaged  = $manualStamps->isEmpty() ? collect() : (clone $manualQuery)
                ->whereIn('reading_date', $manualStamps)->orderByDesc('reading_date')->get()
                ->groupBy(fn($r) => $r->reading_date->format('Y-m-d H:i:s'));

            if ($totalSensor === 0 && $totalManual === 0) continue;

            $totalPages  = max(1, (int) ceil(max($totalSensor, $totalManual) / $perPage));
            $rawPage     = $page;

            $html .= view('dashboard.partials.raw-table', compact(
                'category', 'sensorParams', 'manualParams',
                'sensorPaged', 'manualPaged',
                'totalSensor', 'totalManual',
                'totalPages', 'rawPage'
            ))->render();
        }

        return $html ?: '<div style="text-align:center;padding:30px;color:var(--muted);font-size:13px">No data for selected period</div>';
    }
}
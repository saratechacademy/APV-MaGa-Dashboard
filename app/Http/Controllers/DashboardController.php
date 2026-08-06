<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Concerns\AuthorizesSiteAccess;
use App\Http\Controllers\Concerns\ResolvesDateRange;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use AuthorizesSiteAccess, ResolvesDateRange;

    // ── Index : tous les sites ─────────────────────────────────
    public function index()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            $sites = Site::with(['activeCategories.activeParameters.latestReading'])
                         ->where('status', 'active')->latest()->get();
        } else {
            // Sites via table pivot
            $pivotSiteIds = $user->assignedSites()->pluck('sites.id')->toArray();
            // Sites via user_id direct (legacy)
            $directSiteIds = Site::where('user_id', $user->id)->pluck('id')->toArray();
            // Fusionner les deux
            $allSiteIds = array_unique(array_merge($pivotSiteIds, $directSiteIds));
            $sites = Site::where('status', 'active')
                ->whereIn('id', $allSiteIds)
                ->with(['activeCategories.activeParameters.latestReading'])
                ->latest()
                ->get();
        }

        return view('dashboard.index', compact('sites'));
    }

    // ── Site detail ────────────────────────────────────────────
    public function site(Site $site, Request $request)
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

        $range     = $request->get('range', '1h');
        $chartData = [];

        return view('dashboard.site', compact('site', 'hasManual', 'chartData', 'range'));
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
                        'label' => $r->read_at->format('H:i'),
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
        $timeKeys = array_keys($timeLabels);
        $labels   = array_values($timeLabels);

        $datasets = array_map(fn($s) => [
            'label' => $s['label'],
            'slug'  => $s['slug'],
            'data'  => array_map(fn($key) => $s['map'][$key] ?? null, $timeKeys),
        ], $seriesByParam);

        return response()->json([
            'success'  => true,
            'labels'   => $labels,
            'datasets' => $datasets,
            'from'     => $from->toISOString(),
            'to'       => $to->toISOString(),
        ]);
    }

    // ── Raw Data (HTML partial) ────────────────────────────────
    public function rawData(Request $request, string $site)
    {
        $site = Site::where('slug', $site)->orWhere('id', $site)->firstOrFail();
        $this->authorizeSiteAccess($site);

        [$from, $to] = $this->resolveDateRange($request, 1);
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = 20;

        $site->load(['activeCategories.activeParameters', 'activeCategories.site']);
        session(['current_site_slug' => $site->slug]);

        $html = '';

        foreach ($site->activeCategories as $category) {
            $params       = $category->activeParameters;
            $sensorParams = $params->where('input_type', 'sensor');
            $manualParams = $params->where('input_type', 'manual');

            $sensorReadings = \App\Models\SensorReading::where('site_id', $site->id)
                ->whereIn('site_parameter_id', $sensorParams->pluck('id'))
                ->whereBetween('read_at', [$from, $to])
                ->orderBy('read_at', 'desc')
                ->get();
            $sensorGrouped = $sensorReadings->groupBy(fn($r) => $r->read_at->format('Y-m-d H:i:s'));

            $manualReadings = \App\Models\ManualReading::where('site_id', $site->id)
                ->whereIn('site_parameter_id', $manualParams->pluck('id'))
                ->whereBetween('reading_date', [$from, $to])
                ->orderBy('reading_date', 'desc')
                ->get();
            $manualGrouped = $manualReadings->groupBy(fn($r) => $r->reading_date->format('Y-m-d H:i:s'));

            $totalSensor = $sensorGrouped->count();
            $totalManual = $manualGrouped->count();

            if ($totalSensor === 0 && $totalManual === 0) continue;

            $totalPages  = max(1, (int) ceil(max($totalSensor, $totalManual) / $perPage));
            $offset      = ($page - 1) * $perPage;
            $sensorPaged = $sensorGrouped->slice($offset, $perPage);
            $manualPaged = $manualGrouped->slice($offset, $perPage);
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
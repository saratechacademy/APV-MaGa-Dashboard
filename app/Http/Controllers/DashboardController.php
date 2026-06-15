<?php
namespace App\Http\Controllers;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    // ── Helper : vérifie que l'utilisateur a accès au site ────
    protected function authorizeSite(Site $site): void
    {
        $user = Auth::user();
        if ($user->isAdmin()) return;

        // Vérifier via table pivot site_user
        $inPivot = $site->users()->where('users.id', $user->id)->exists();

        // Fallback : ancienne relation user_id directe
        $isDirect = $site->user_id === $user->id;

        if (!$inPivot && !$isDirect) {
            abort(403, 'Access denied.');
        }
    }

    // ── Index : tous les sites ─────────────────────────────────
    public function index()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            $sites = Site::with(['activeCategories.activeParameters'])
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
                ->with(['activeCategories.activeParameters'])
                ->latest()
                ->get();
        }

        return view('dashboard.index', compact('sites'));
    }

    // ── Site detail ────────────────────────────────────────────
    public function site(Site $site, Request $request)
    {
        $this->authorizeSite($site);

        $site->load([
            'activeCategories.activeParameters',
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
        $hours = $request->input('hours', 24);
        $from  = now()->subHours($hours);

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

        $labels   = [];
        $datasets = [];

        foreach ($parameters as $param) {
            if (in_array($param->data_type, ['string', 'boolean', 'switch'])) continue;

            if ($param->input_type === 'sensor') {
                $rows = \App\Models\SensorReading::where('site_id', $site->id)
                    ->where('site_parameter_id', $param->id)
                    ->where('read_at', '>=', $from)
                    ->orderBy('read_at')
                    ->get()
                    ->map(fn($r) => [
                        'value' => $r->value,
                        'time'  => $r->read_at->format('H:i'),
                    ]);
            } else {
                $rows = \App\Models\ManualReading::where('site_id', $site->id)
                    ->where('site_parameter_id', $param->id)
                    ->where('reading_date', '>=', $from->toDateString())
                    ->orderBy('reading_date')
                    ->get()
                    ->map(fn($r) => [
                        'value' => is_numeric($r->value) ? (float) $r->value : null,
                        'time'  => \Carbon\Carbon::parse($r->reading_date)->format('d/m'),
                    ])
                    ->filter(fn($r) => $r['value'] !== null)
                    ->values();
            }

            if ($rows->isEmpty()) continue;

            if (empty($labels)) {
                $labels = $rows->pluck('time')->toArray();
            }

            $datasets[] = [
                'label' => $param->name . ($param->unit ? ' (' . $param->unit . ')' : ''),
                'slug'  => $param->slug,
                'data'  => $rows->pluck('value')->toArray(),
            ];
        }

        return response()->json([
            'success'  => true,
            'labels'   => $labels,
            'datasets' => $datasets,
            'from'     => $from->toISOString(),
            'to'       => now()->toISOString(),
        ]);
    }

    // ── Raw Data (HTML partial) ────────────────────────────────
    public function rawData(Request $request, string $site)
    {
        $site = Site::where('slug', $site)->orWhere('id', $site)->firstOrFail();
        $this->authorizeSite($site);

        $hours   = (int) $request->input('hours', 1);
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = 20;
        $from    = now()->subHours($hours);

        $site->load(['activeCategories.activeParameters', 'activeCategories.site']);
        session(['current_site_slug' => $site->slug]);

        $html = '';

        foreach ($site->activeCategories as $category) {
            $params       = $category->activeParameters;
            $sensorParams = $params->where('input_type', 'sensor');
            $manualParams = $params->where('input_type', 'manual');

            $sensorReadings = \App\Models\SensorReading::where('site_id', $site->id)
                ->whereIn('site_parameter_id', $sensorParams->pluck('id'))
                ->where('read_at', '>=', $from)
                ->orderBy('read_at', 'desc')
                ->get();
            $sensorGrouped = $sensorReadings->groupBy(fn($r) => $r->read_at->format('Y-m-d H:i:s'));

            $manualReadings = \App\Models\ManualReading::where('site_id', $site->id)
                ->whereIn('site_parameter_id', $manualParams->pluck('id'))
                ->where('reading_date', '>=', $from)
                ->orderBy('reading_date', 'desc')
                ->get();
            $manualGrouped = $manualReadings->groupBy(fn($r) => \Carbon\Carbon::parse($r->reading_date)->format('Y-m-d H:i:s'));

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
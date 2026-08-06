<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\SensorReading;
use App\Models\ActuatorCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ApiController extends Controller
{
    // ─── Middleware : vérifier la clé API ───────────────────────────
    private function authenticate(Request $request, string $siteSlug): Site
    {
       $site = Site::where('slug', $siteSlug)->where('status', 'active')->first();

        if (!$site) {
            abort(response()->json(['success' => false, 'error' => 'Site not found.'], 404));
        }

        $key = $request->header('X-API-Key') ?? $request->query('api_key');

        if (!$key || !hash_equals($site->api_key, $key)) {
            abort(response()->json(['success' => false, 'error' => 'Invalid API key.'], 401));
        }

        return $site;
    }

    // ─── POST /api/sensors/{site}/{category} ────────────────────────
    public function store(Request $request, string $siteSlug, string $categorySlug)
    {
        $site = $this->authenticate($request, $siteSlug);

        $readAt = $request->input('read_at')
            ? Carbon::parse($request->input('read_at'))
            : now();

        // Trouver la catégorie
        $category = $site->categories()
            ->where('slug', $categorySlug)
            ->where('is_active', true)
            ->first();

        if (!$category) {
            return response()->json([
                'success'             => false,
                'error'               => "Category '{$categorySlug}' not found for this site.",
                'available_categories' => $site->activeCategories->pluck('slug'),
            ], 404);
        }

        // Enregistrer les lectures
        $stored = [];
        $errors = [];

        foreach ($request->except(['read_at']) as $paramSlug => $value) {
            $parameter = $category->parameters()
                ->where('slug', $paramSlug)
                ->where('is_active', true)
                ->where('input_type', 'sensor')
                ->first();

            if (!$parameter) {
                $errors[] = "Parameter '{$paramSlug}' not found or not a sensor parameter.";
                continue;
            }

            if (is_numeric($value)) {
                $numeric = (float) $value;
                if ($parameter->min_value !== null && $numeric < $parameter->min_value) {
                    $errors[] = "Parameter '{$paramSlug}' value {$value} is below the configured minimum ({$parameter->min_value}); reading rejected.";
                    continue;
                }
                if ($parameter->max_value !== null && $numeric > $parameter->max_value) {
                    $errors[] = "Parameter '{$paramSlug}' value {$value} is above the configured maximum ({$parameter->max_value}); reading rejected.";
                    continue;
                }
            }

            SensorReading::create([
                'site_id'           => $site->id,
                'site_parameter_id' => $parameter->id,
                'value'             => is_numeric($value) ? $value : null,
                'value_text'        => !is_numeric($value) ? (string) $value : null,
                'read_at'           => $readAt,
            ]);

            // Si ce paramètre est un actionneur contrôlable (switch), on enregistre
            // l'état RAPPORTÉ par l'ESP32 dans actuator_commands.reported_state.
            // Cela permet au dashboard de comparer "commandé" vs "réel" et de
            // détecter si la commande a bien été appliquée.
            if ($parameter->isControllable()) {
                $reportedState = ((int) $value) > 0 ? 1 : 0;

                ActuatorCommand::updateOrCreate(
                    ['site_parameter_id' => $parameter->id],
                    [
                        'site_id'        => $site->id,
                        'reported_state' => $reportedState,
                        'reported_at'    => $readAt,
                        // desired_state n'est pas modifié ici : il reste tel que défini
                        // par le dashboard. S'il n'existe pas encore, on l'initialise
                        // avec l'état rapporté (pas de commande en attente au départ).
                    ] + (ActuatorCommand::where('site_parameter_id', $parameter->id)->exists()
                            ? []
                            : ['desired_state' => $reportedState])
                );
            }

            $stored[$paramSlug] = $value;
        }

        return response()->json([
            'success'  => true,
            'site'     => $site->name,
            'category' => $category->name,
            'stored'   => $stored,
            'errors'   => $errors,
            'read_at'  => $readAt->toISOString(),
        ], 201);
    }

    // ─── GET /api/sensors/{site}/{category}/latest ──────────────────
    public function latest(Request $request, string $siteSlug, string $categorySlug)
    {
        $site = $this->authenticate($request, $siteSlug);

        $category = $site->categories()
            ->where('slug', $categorySlug)
            ->where('is_active', true)
            ->first();

        if (!$category) {
            return response()->json(['success' => false, 'error' => 'Category not found.'], 404);
        }

        $data = [];
        foreach ($category->activeParameters as $param) {
            $latest = SensorReading::where('site_parameter_id', $param->id)
                ->latest('read_at')
                ->first();

            $data[$param->slug] = [
                'name'    => $param->name,
                'value'   => $latest?->value ?? $latest?->value_text,
                'unit'    => $param->unit,
                'read_at' => $latest?->read_at?->toISOString(),
            ];
        }

        return response()->json([
            'success'  => true,
            'site'     => $site->name,
            'category' => $category->name,
            'data'     => $data,
        ]);
    }

    // ─── GET /api/sensors/{site}/status ─────────────────────────────
    public function status(Request $request, string $siteSlug)
    {
        $site = $this->authenticate($request, $siteSlug);

        $status = [];
        foreach ($site->activeCategories as $category) {
            $latest = SensorReading::where('site_id', $site->id)
                ->whereIn('site_parameter_id', $category->activeParameters->pluck('id'))
                ->latest('read_at')
                ->first();

            $status[$category->slug] = [
                'name'        => $category->name,
                'last_reading' => $latest?->read_at?->diffForHumans(),
                'read_at'     => $latest?->read_at?->toISOString(),
            ];
        }

        return response()->json([
            'success' => true,
            'site'    => $site->name,
            'status'  => $status,
        ]);
    }

    // ─── GET /api/commands/{site}/{category} ────────────────────────
    // L'ESP32 interroge cet endpoint périodiquement pour savoir quel état
    // (ON/OFF) appliquer à chaque relais/vanne/ventilateur configuré comme
    // "controllable" dans cette catégorie.
    public function commands(Request $request, string $siteSlug, string $categorySlug)
    {
        $site = $this->authenticate($request, $siteSlug);

        $category = $site->categories()
            ->where('slug', $categorySlug)
            ->where('is_active', true)
            ->first();

        if (!$category) {
            return response()->json(['success' => false, 'error' => 'Category not found.'], 404);
        }

        $commands = [];

        foreach ($category->activeParameters->where('control_type', 'controllable') as $param) {
            $cmd = ActuatorCommand::where('site_parameter_id', $param->id)->first();

            $commands[$param->slug] = [
                'name'           => $param->name,
                'desired_state'  => $cmd?->desired_state ?? 0,
                'reported_state' => $cmd?->reported_state,
                'updated_at'     => $cmd?->updated_at?->toISOString(),
            ];
        }

        return response()->json([
            'success'  => true,
            'site'     => $site->name,
            'category' => $category->name,
            'commands' => $commands,
        ]);
    }

    // ─── GET /api/sensors/{site}/{category}/history ─────────────────
    // Données brutes (format "long"/tidy : 1 ligne = 1 paramètre x 1 lecture),
    // paginées, sur une plage de dates. Pratique pour analyse statistique
    // (pandas, R) sans passer par l'export CSV du dashboard.
    public function history(Request $request, string $siteSlug, string $categorySlug)
    {
        $site = $this->authenticate($request, $siteSlug);

        $category = $site->categories()
            ->where('slug', $categorySlug)
            ->where('is_active', true)
            ->first();

        if (!$category) {
            return response()->json(['success' => false, 'error' => 'Category not found.'], 404);
        }

        $request->validate([
            'from'     => 'nullable|date',
            'to'       => 'nullable|date',
            'params'   => 'nullable|string',
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:5000',
        ]);

        // Consistent with ResolvesDateRange (used by the dashboard/export
        // endpoints): from/to are day boundaries, not bare midnight instants —
        // otherwise ?from=2026-08-01&to=2026-08-01 (a very natural "give me
        // that day" request) resolves to a zero-width window and silently
        // returns no data.
        $to   = $request->filled('to')   ? Carbon::parse($request->to)->endOfDay()   : now();
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : $to->copy()->subDay();

        if ($from->diffInDays($to) > 90) {
            return response()->json([
                'success' => false,
                'error'   => 'Date range cannot exceed 90 days. Use /stats for longer ranges.',
            ], 422);
        }

        $parameters = $category->activeParameters()->where('input_type', 'sensor');

        if ($request->filled('params')) {
            $slugs = array_map('trim', explode(',', $request->input('params')));
            $parameters = $parameters->whereIn('slug', $slugs);
        }

        $parameters = $parameters->get();
        $paramsById = $parameters->keyBy('id');

        $perPage = (int) $request->input('per_page', 500);
        $page    = (int) $request->input('page', 1);

        $query = SensorReading::whereIn('site_parameter_id', $parameters->pluck('id'))
            ->whereBetween('read_at', [$from, $to])
            ->orderBy('read_at');

        $total = $query->count();

        $rows = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        $data = $rows->map(function ($r) use ($paramsById) {
            $p = $paramsById[$r->site_parameter_id] ?? null;
            return [
                'parameter' => $p?->slug,
                'name'      => $p?->name,
                'unit'      => $p?->unit,
                'value'     => $r->value ?? $r->value_text,
                'read_at'   => $r->read_at->toISOString(),
            ];
        });

        return response()->json([
            'success'    => true,
            'site'       => $site->name,
            'category'   => $category->name,
            'from'       => $from->toISOString(),
            'to'         => $to->toISOString(),
            'page'       => $page,
            'per_page'   => $perPage,
            'total'      => $total,
            'total_pages' => (int) ceil($total / $perPage),
            'data'       => $data,
        ]);
    }

    // ─── GET /api/sensors/{site}/{category}/stats ───────────────────
    // Agrégats (avg/min/max/sum/count) par paramètre numérique, groupés
    // par heure/jour/semaine/mois. Pratique pour des tableaux de bord
    // de recherche ou des comparaisons inter-sites sans télécharger
    // toutes les lectures brutes.
    public function stats(Request $request, string $siteSlug, string $categorySlug)
    {
        $site = $this->authenticate($request, $siteSlug);

        $category = $site->categories()
            ->where('slug', $categorySlug)
            ->where('is_active', true)
            ->first();

        if (!$category) {
            return response()->json(['success' => false, 'error' => 'Category not found.'], 404);
        }

        $request->validate([
            'period'   => 'nullable|in:hour,day,week,month',
            'from'     => 'nullable|date',
            'to'       => 'nullable|date',
            'params'   => 'nullable|string',
        ]);

        $period = $request->input('period', 'day');
        // Same day-boundary normalization as history() above — see its comment.
        $to     = $request->filled('to')   ? Carbon::parse($request->to)->endOfDay()   : now();
        $from   = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : $to->copy()->subDays(30);

        $maxDays = ['hour' => 7, 'day' => 365, 'week' => 730, 'month' => 1825];
        if ($from->diffInDays($to) > $maxDays[$period]) {
            return response()->json([
                'success' => false,
                'error'   => "Date range too large for period={$period} (max {$maxDays[$period]} days).",
            ], 422);
        }

        $parameters = $category->activeParameters()
            ->where('input_type', 'sensor')
            ->whereIn('data_type', ['float', 'integer', 'switch']);

        if ($request->filled('params')) {
            $slugs = array_map('trim', explode(',', $request->input('params')));
            $parameters = $parameters->whereIn('slug', $slugs);
        }

        $parameters = $parameters->get();

        $stats = [];

        foreach ($parameters as $param) {
            $readings = SensorReading::where('site_parameter_id', $param->id)
                ->whereBetween('read_at', [$from, $to])
                ->whereNotNull('value')
                ->orderBy('read_at')
                ->get(['value', 'read_at']);

            $buckets = [];
            foreach ($readings as $r) {
                $bucketKey = match ($period) {
                    'hour'  => $r->read_at->format('Y-m-d H:00'),
                    'day'   => $r->read_at->format('Y-m-d'),
                    'week'  => $r->read_at->copy()->startOfWeek()->format('Y-m-d'),
                    'month' => $r->read_at->format('Y-m'),
                };
                $buckets[$bucketKey][] = (float) $r->value;
            }

            $series = [];
            foreach ($buckets as $bucketKey => $values) {
                $series[] = [
                    'period' => $bucketKey,
                    'count'  => count($values),
                    'avg'    => round(array_sum($values) / count($values), 3),
                    'min'    => min($values),
                    'max'    => max($values),
                    'sum'    => round(array_sum($values), 3),
                ];
            }

            $stats[$param->slug] = [
                'name'   => $param->name,
                'unit'   => $param->unit,
                'series' => $series,
            ];
        }

        return response()->json([
            'success'  => true,
            'site'     => $site->name,
            'category' => $category->name,
            'period'   => $period,
            'from'     => $from->toISOString(),
            'to'       => $to->toISOString(),
            'stats'    => $stats,
        ]);
    }
}
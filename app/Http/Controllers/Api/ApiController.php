<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\SensorReading;
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

        if (!$key || $key !== $site->api_key) {
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

            SensorReading::create([
                'site_id'           => $site->id,
                'site_parameter_id' => $parameter->id,
                'value'             => is_numeric($value) ? $value : null,
                'value_text'        => !is_numeric($value) ? (string) $value : null,
                'read_at'           => $readAt,
            ]);

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
}
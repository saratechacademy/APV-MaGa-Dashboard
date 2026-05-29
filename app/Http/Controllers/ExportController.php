<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Exports\SiteCategoryExport;
use App\Exports\CategorySheet;
use App\Exports\AllSitesExport;
use App\Models\SiteCategory;
use App\Models\SensorReading;
use App\Models\ManualReading;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExportController extends Controller
{
    private function authorizeSite(Site $site)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $site->user_id !== $user->id) {
            abort(403);
        }
    }

    // ── Export toutes catégories Excel ─────────────────────────
    public function allExcel(Request $request, string $site)
    {
        $site  = Site::where('slug', $site)->orWhere('id', $site)->firstOrFail();
        $this->authorizeSite($site);
        $site->load(['activeCategories.activeParameters']);

        $hours    = (int) $request->input('hours', 24);
        $filename = $site->slug . '_data_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new SiteCategoryExport($site, $hours), $filename);
    }

    // ── Export toutes catégories CSV ───────────────────────────
    public function allCsv(Request $request, string $site)
    {
        $site  = Site::where('slug', $site)->orWhere('id', $site)->firstOrFail();
        $this->authorizeSite($site);
        $site->load(['activeCategories.activeParameters']);

        $hours = (int) $request->input('hours', 24);
        $from  = now()->subHours($hours);

        $filename = $site->slug . '_data_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($site, $from) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            foreach ($site->activeCategories as $category) {
                $params       = $category->activeParameters;
                $sensorParams = $params->where('input_type', 'sensor');
                $manualParams = $params->where('input_type', 'manual')->where('data_type', '!=', 'string');

                // Section header
                fputcsv($handle, ['=== ' . $category->name . ' ===']);

                // Column headers
                $headers = ['Timestamp'];
                foreach ($sensorParams as $p) {
                    $headers[] = $p->name . ($p->unit ? ' (' . $p->unit . ')' : '');
                }
                foreach ($manualParams as $p) {
                    $headers[] = $p->name . ($p->unit ? ' (' . $p->unit . ')' : '');
                }
                $headers[] = 'Type';
                fputcsv($handle, $headers);

                // Sensor rows
                $sensorReadings = SensorReading::where('site_id', $site->id)
                    ->whereIn('site_parameter_id', $sensorParams->pluck('id'))
                    ->where('read_at', '>=', $from)
                    ->orderBy('read_at', 'desc')
                    ->get();

                foreach ($sensorReadings->groupBy(fn($r) => $r->read_at->format('Y-m-d H:i:s')) as $timestamp => $readings) {
                    $row = [$timestamp];
                    foreach ($sensorParams as $p) {
                        $r     = $readings->firstWhere('site_parameter_id', $p->id);
                        $row[] = $r ? ($r->value ?? $r->value_text ?? '') : '';
                    }
                    foreach ($manualParams as $p) {
                        $row[] = '';
                    }
                    $row[] = 'Sensor';
                    fputcsv($handle, $row);
                }

                // Manual rows
                $manualReadings = ManualReading::where('site_id', $site->id)
                    ->whereIn('site_parameter_id', $manualParams->pluck('id'))
                    ->where('reading_date', '>=', $from)
                    ->orderBy('reading_date', 'desc')
                    ->get();

                foreach ($manualReadings->groupBy(fn($r) => Carbon::parse($r->reading_date)->format('Y-m-d H:i:s')) as $timestamp => $readings) {
                    $row = [$timestamp];
                    foreach ($sensorParams as $p) {
                        $row[] = '';
                    }
                    foreach ($manualParams as $p) {
                        $r     = $readings->firstWhere('site_parameter_id', $p->id);
                        $row[] = $r ? ($r->value ?? '') : '';
                    }
                    $row[] = 'Manual';
                    fputcsv($handle, $row);
                }

                fputcsv($handle, []); // Ligne vide entre catégories
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Export une catégorie Excel ─────────────────────────────
    public function categoryExcel(Request $request, string $site, string $category)
    {
        $site  = Site::where('slug', $site)->orWhere('id', $site)->firstOrFail();
        $this->authorizeSite($site);
        $site->load(['activeCategories.activeParameters']);

        $cat = $site->activeCategories->firstWhere('slug', $category);
        if (!$cat) abort(404);

        $hours    = (int) $request->input('hours', 24);
        $from     = now()->subHours($hours);
        $filename = $site->slug . '_' . $category . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new CategorySheet($site, $cat, $from), $filename);
    }

    // ── Export une catégorie CSV ───────────────────────────────
    public function categoryCsv(Request $request, string $site, string $category)
    {
        $site  = Site::where('slug', $site)->orWhere('id', $site)->firstOrFail();
        $this->authorizeSite($site);
        $site->load(['activeCategories.activeParameters']);

        $cat = $site->activeCategories->firstWhere('slug', $category);
        if (!$cat) abort(404);

        $hours    = (int) $request->input('hours', 24);
        $from     = now()->subHours($hours);
        $filename = $site->slug . '_' . $category . '_' . now()->format('Ymd_His') . '.csv';

        $params       = $cat->activeParameters;
        $sensorParams = $params->where('input_type', 'sensor');
        $manualParams = $params->where('input_type', 'manual')->where('data_type', '!=', 'string');

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($site, $cat, $from, $sensorParams, $manualParams) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers
            $row = ['Timestamp'];
            foreach ($sensorParams as $p) {
                $row[] = $p->name . ($p->unit ? ' (' . $p->unit . ')' : '');
            }
            foreach ($manualParams as $p) {
                $row[] = $p->name . ($p->unit ? ' (' . $p->unit . ')' : '');
            }
            $row[] = 'Type';
            fputcsv($handle, $row);

            // Sensor rows
            $sensorReadings = SensorReading::where('site_id', $site->id)
                ->whereIn('site_parameter_id', $sensorParams->pluck('id'))
                ->where('read_at', '>=', $from)
                ->orderBy('read_at', 'desc')
                ->get();

            foreach ($sensorReadings->groupBy(fn($r) => $r->read_at->format('Y-m-d H:i:s')) as $timestamp => $readings) {
                $row = [$timestamp];
                foreach ($sensorParams as $p) {
                    $r     = $readings->firstWhere('site_parameter_id', $p->id);
                    $row[] = $r ? ($r->value ?? $r->value_text ?? '') : '';
                }
                foreach ($manualParams as $p) {
                    $row[] = '';
                }
                $row[] = 'Sensor';
                fputcsv($handle, $row);
            }

            // Manual rows
            $manualReadings = ManualReading::where('site_id', $site->id)
                ->whereIn('site_parameter_id', $manualParams->pluck('id'))
                ->where('reading_date', '>=', $from)
                ->orderBy('reading_date', 'desc')
                ->get();

            foreach ($manualReadings->groupBy(fn($r) => Carbon::parse($r->reading_date)->format('Y-m-d H:i:s')) as $timestamp => $readings) {
                $row = [$timestamp];
                foreach ($sensorParams as $p) {
                    $row[] = '';
                }
                foreach ($manualParams as $p) {
                    $r     = $readings->firstWhere('site_parameter_id', $p->id);
                    $row[] = $r ? ($r->value ?? '') : '';
                }
                $row[] = 'Manual';
                fputcsv($handle, $row);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Export tous les sites Excel ────────────────────────────
    public function allSitesExcel(Request $request)
    {
        $hours    = (int) $request->input('hours', 24);
        $filename = 'all_sites_data_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new AllSitesExport($hours), $filename);
    }

    // ── Export tous les sites CSV ──────────────────────────────
    public function allSitesCsv(Request $request)
    {
        $hours    = (int) $request->input('hours', 24);
        $filename = 'all_sites_data_' . now()->format('Ymd_His') . '.csv';

        $sites = Site::where('status', 'active')
            ->with(['activeCategories.activeParameters'])
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($sites, $hours) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            $from = now()->subHours($hours);

            foreach ($sites as $site) {
                foreach ($site->activeCategories as $category) {
                    $params       = $category->activeParameters;
                    $sensorParams = $params->where('input_type', 'sensor');
                    $manualParams = $params->where('input_type', 'manual')->where('data_type', '!=', 'string');

                    // Section header
                    fputcsv($handle, ['=== ' . $site->name . ' — ' . $category->name . ' ===']);

                    // Column headers
                    $row = ['Timestamp'];
                    foreach ($sensorParams as $p) {
                        $row[] = $p->name . ($p->unit ? ' (' . $p->unit . ')' : '');
                    }
                    foreach ($manualParams as $p) {
                        $row[] = $p->name . ($p->unit ? ' (' . $p->unit . ')' : '');
                    }
                    $row[] = 'Type';
                    fputcsv($handle, $row);

                    // Sensor rows
                    $sensorReadings = \App\Models\SensorReading::where('site_id', $site->id)
                        ->whereIn('site_parameter_id', $sensorParams->pluck('id'))
                        ->where('read_at', '>=', $from)
                        ->orderBy('read_at', 'desc')
                        ->get();

                    foreach ($sensorReadings->groupBy(fn($r) => $r->read_at->format('Y-m-d H:i:s')) as $timestamp => $readings) {
                        $row = [$timestamp];
                        foreach ($sensorParams as $p) {
                            $r     = $readings->firstWhere('site_parameter_id', $p->id);
                            $row[] = $r ? ($r->value ?? $r->value_text ?? '') : '';
                        }
                        foreach ($manualParams as $p) {
                            $row[] = '';
                        }
                        $row[] = 'Sensor';
                        fputcsv($handle, $row);
                    }

                    // Manual rows
                    $manualReadings = \App\Models\ManualReading::where('site_id', $site->id)
                        ->whereIn('site_parameter_id', $manualParams->pluck('id'))
                        ->where('reading_date', '>=', $from)
                        ->orderBy('reading_date', 'desc')
                        ->get();

                    foreach ($manualReadings->groupBy(fn($r) => Carbon::parse($r->reading_date)->format('Y-m-d H:i:s')) as $timestamp => $readings) {
                        $row = [$timestamp];
                        foreach ($sensorParams as $p) {
                            $row[] = '';
                        }
                        foreach ($manualParams as $p) {
                            $r     = $readings->firstWhere('site_parameter_id', $p->id);
                            $row[] = $r ? ($r->value ?? '') : '';
                        }
                        $row[] = 'Manual';
                        fputcsv($handle, $row);
                    }

                    fputcsv($handle, []); // Ligne vide
                }
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Export groupe manuel CSV ───────────────────────────────
    public function groupCsv(Request $request, string $site, string $category, string $group)
    {
        $site     = Site::where('slug', $site)->orWhere('id', $site)->firstOrFail();
        $cat      = $site->categories()->where('slug', $category)->firstOrFail();
        $cat->load('activeParameters');
        $params   = $cat->activeParameters
            ->where('input_type', 'manual')
            ->filter(fn($p) => $p->group_name === $group)
            ->values();

        $filename = Str::slug($site->name).'-'.$category.'-'.Str::slug($group).'-'.now()->format('Ymd').'.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($site, $params) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            $row = ['Date'];
            foreach ($params as $p) {
                $row[] = $p->name . ($p->unit ? ' ('.$p->unit.')' : '');
            }
            $row[] = 'Notes';
            fputcsv($handle, $row);

            $readings = \App\Models\ManualReading::where('site_id', $site->id)
                ->whereIn('site_parameter_id', $params->pluck('id'))
                ->orderBy('reading_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            $rows = $readings->groupBy(fn($r) =>
                \Carbon\Carbon::parse($r->reading_date)->format('Y-m-d') . '||' .
                $r->created_at->format('Y-m-d H:i')
            );

            foreach ($rows as $key => $rowReadings) {
                $date = explode('||', $key)[0];
                $row  = [$date];
                foreach ($params as $p) {
                    $r     = $rowReadings->firstWhere('site_parameter_id', $p->id);
                    $row[] = $r?->value ?? '';
                }
                $row[] = $rowReadings->whereNotNull('notes')->first()?->notes ?? '';
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Export groupe manuel Excel ─────────────────────────────
    public function groupExcel(Request $request, string $site, string $category, string $group)
    {
        $site     = Site::where('slug', $site)->orWhere('id', $site)->firstOrFail();
        $cat      = $site->categories()->where('slug', $category)->firstOrFail();
        $cat->load('activeParameters');
        $params   = $cat->activeParameters
            ->where('input_type', 'manual')
            ->filter(fn($p) => $p->group_name === $group)
            ->values();

        $filename = Str::slug($site->name).'-'.$category.'-'.Str::slug($group).'-'.now()->format('Ymd').'.xlsx';

        $readings = \App\Models\ManualReading::where('site_id', $site->id)
            ->whereIn('site_parameter_id', $params->pluck('id'))
            ->orderBy('reading_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $rows = $readings->groupBy(fn($r) =>
            \Carbon\Carbon::parse($r->reading_date)->format('Y-m-d') . '||' .
            $r->created_at->format('Y-m-d H:i')
        );

        // Build array for Excel
        $headers = ['Date'];
        foreach ($params as $p) {
            $headers[] = $p->name . ($p->unit ? ' ('.$p->unit.')' : '');
        }
        $headers[] = 'Notes';

        $data = [$headers];
        foreach ($rows as $key => $rowReadings) {
            $date = explode('||', $key)[0];
            $row  = [$date];
            foreach ($params as $p) {
                $r     = $rowReadings->firstWhere('site_parameter_id', $p->id);
                $row[] = $r?->value ?? '';
            }
            $row[] = $rowReadings->whereNotNull('notes')->first()?->notes ?? '';
            $data[] = $row;
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ArrayExport($data, $group),
            $filename
        );
    }


}
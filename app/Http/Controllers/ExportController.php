<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesSiteAccess;
use App\Http\Controllers\Concerns\ResolvesDateRange;
use App\Models\Site;
use App\Exports\SiteCategoryExport;
use App\Exports\CategorySheet;
use App\Exports\AllSitesExport;
use App\Models\SiteCategory;
use App\Models\SensorReading;
use App\Models\ManualReading;
use App\Support\SafeExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExportController extends Controller
{
    use AuthorizesSiteAccess, ResolvesDateRange;

    /**
     * Writes one category's sensor+manual readings to an open CSV handle:
     * optional "=== label ===" section header, a column-header row, then one
     * row per timestamp (sensor first, then manual). Shared by allCsv,
     * categoryCsv and allSitesCsv so the CSV shape can't drift between them.
     * A section label also triggers the trailing blank line used to visually
     * separate categories when several blocks are written to the same file.
     */
    private function writeCsvCategoryBlock($handle, Site $site, SiteCategory $category, Carbon $from, Carbon $to, ?string $sectionLabel = null): void
    {
        $params       = $category->activeParameters;
        $sensorParams = $params->where('input_type', 'sensor');
        $manualParams = $params->where('input_type', 'manual')->where('data_type', '!=', 'string');

        if ($sectionLabel !== null) {
            fputcsv($handle, SafeExport::row(['=== ' . $sectionLabel . ' ===']));
        }

        $headerRow = ['Timestamp'];
        foreach ($sensorParams as $p) {
            $headerRow[] = $p->name . ($p->unit ? ' (' . $p->unit . ')' : '');
        }
        foreach ($manualParams as $p) {
            $headerRow[] = $p->name . ($p->unit ? ' (' . $p->unit . ')' : '');
        }
        $headerRow[] = 'Type';
        fputcsv($handle, SafeExport::row($headerRow));

        $sensorReadings = SensorReading::where('site_id', $site->id)
            ->whereIn('site_parameter_id', $sensorParams->pluck('id'))
            ->whereBetween('read_at', [$from, $to])
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
            fputcsv($handle, SafeExport::row($row));
        }

        $manualReadings = ManualReading::where('site_id', $site->id)
            ->whereIn('site_parameter_id', $manualParams->pluck('id'))
            ->whereBetween('reading_date', [$from, $to])
            ->orderBy('reading_date', 'desc')
            ->get();

        foreach ($manualReadings->groupBy(fn($r) => $r->reading_date->format('Y-m-d H:i:s')) as $timestamp => $readings) {
            $row = [$timestamp];
            foreach ($sensorParams as $p) {
                $row[] = '';
            }
            foreach ($manualParams as $p) {
                $r     = $readings->firstWhere('site_parameter_id', $p->id);
                $row[] = $r ? ($r->value ?? '') : '';
            }
            $row[] = 'Manual';
            fputcsv($handle, SafeExport::row($row));
        }

        if ($sectionLabel !== null) {
            fputcsv($handle, []); // Ligne vide entre catégories
        }
    }

    // ── Export toutes catégories Excel ─────────────────────────
    public function allExcel(Request $request, string $site)
    {
        $site  = Site::where('slug', $site)->orWhere('id', $site)->firstOrFail();
        $this->authorizeSiteAccess($site);
        $site->load(['activeCategories.activeParameters']);

        [$from, $to] = $this->resolveDateRange($request, 24);
        $filename = $site->slug . '_data_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new SiteCategoryExport($site, $from, $to), $filename);
    }

    // ── Export toutes catégories CSV ───────────────────────────
    public function allCsv(Request $request, string $site)
    {
        $site  = Site::where('slug', $site)->orWhere('id', $site)->firstOrFail();
        $this->authorizeSiteAccess($site);
        $site->load(['activeCategories.activeParameters']);

        [$from, $to] = $this->resolveDateRange($request, 24);

        $filename = $site->slug . '_data_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($site, $from, $to) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            foreach ($site->activeCategories as $category) {
                $this->writeCsvCategoryBlock($handle, $site, $category, $from, $to, $category->name);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Export une catégorie Excel ─────────────────────────────
    public function categoryExcel(Request $request, string $site, string $category)
    {
        $site  = Site::where('slug', $site)->orWhere('id', $site)->firstOrFail();
        $this->authorizeSiteAccess($site);
        $site->load(['activeCategories.activeParameters']);

        $cat = $site->activeCategories->firstWhere('slug', $category);
        if (!$cat) abort(404);

        [$from, $to] = $this->resolveDateRange($request, 24);
        $filename = $site->slug . '_' . $category . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new CategorySheet($site, $cat, $from, $to), $filename);
    }

    // ── Export une catégorie CSV ───────────────────────────────
    public function categoryCsv(Request $request, string $site, string $category)
    {
        $site  = Site::where('slug', $site)->orWhere('id', $site)->firstOrFail();
        $this->authorizeSiteAccess($site);
        $site->load(['activeCategories.activeParameters']);

        $cat = $site->activeCategories->firstWhere('slug', $category);
        if (!$cat) abort(404);

        [$from, $to] = $this->resolveDateRange($request, 24);
        $filename = $site->slug . '_' . $category . '_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($site, $cat, $from, $to) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            $this->writeCsvCategoryBlock($handle, $site, $cat, $from, $to);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Export tous les sites Excel ────────────────────────────
    public function allSitesExcel(Request $request)
    {
        // Tout utilisateur connecté peut exporter — mais uniquement les sites
        // auxquels il a accès (les admins n'ont pas de filtre : null = tous).
        $siteIds  = $this->accessibleSiteIds();
        [$from, $to] = $this->resolveDateRange($request, 24);
        $filename = 'all_sites_data_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new AllSitesExport($from, $to, $siteIds), $filename);
    }

    // ── Export tous les sites CSV ──────────────────────────────
    public function allSitesCsv(Request $request)
    {
        $siteIds  = $this->accessibleSiteIds();
        [$from, $to] = $this->resolveDateRange($request, 24);
        $filename = 'all_sites_data_' . now()->format('Ymd_His') . '.csv';

        $sites = Site::where('status', 'active')
            ->when($siteIds !== null, fn ($q) => $q->whereIn('id', $siteIds))
            ->with(['activeCategories.activeParameters'])
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($sites, $from, $to) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            foreach ($sites as $site) {
                foreach ($site->activeCategories as $category) {
                    $this->writeCsvCategoryBlock($handle, $site, $category, $from, $to, $site->name . ' — ' . $category->name);
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
        $this->authorizeSiteAccess($site);
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

        // Saved Records is a running ledger — default is the full history (unlike
        // the other exports' rolling "last N hours"), but from/to narrow it down
        // when explicitly requested.
        $from = $request->filled('from') ? \Carbon\Carbon::parse($request->from)->startOfDay() : null;
        $to   = $request->filled('to')   ? \Carbon\Carbon::parse($request->to)->endOfDay()     : null;

        $callback = function() use ($site, $params, $from, $to) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            $row = ['Date'];
            foreach ($params as $p) {
                $row[] = $p->name . ($p->unit ? ' ('.$p->unit.')' : '');
            }
            $row[] = 'Notes';
            fputcsv($handle, SafeExport::row($row));

            $readings = \App\Models\ManualReading::where('site_id', $site->id)
                ->whereIn('site_parameter_id', $params->pluck('id'))
                ->when($from && $to, fn($q) => $q->whereBetween('reading_date', [$from, $to]))
                ->orderBy('reading_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            $rows = $readings->groupBy(fn($r) =>
                $r->reading_date->format('Y-m-d') . '||' .
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
                fputcsv($handle, SafeExport::row($row));
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Export groupe manuel Excel ─────────────────────────────
    public function groupExcel(Request $request, string $site, string $category, string $group)
    {
        $site     = Site::where('slug', $site)->orWhere('id', $site)->firstOrFail();
        $this->authorizeSiteAccess($site);
        $cat      = $site->categories()->where('slug', $category)->firstOrFail();
        $cat->load('activeParameters');
        $params   = $cat->activeParameters
            ->where('input_type', 'manual')
            ->filter(fn($p) => $p->group_name === $group)
            ->values();

        $filename = Str::slug($site->name).'-'.$category.'-'.Str::slug($group).'-'.now()->format('Ymd').'.xlsx';

        $from = $request->filled('from') ? \Carbon\Carbon::parse($request->from)->startOfDay() : null;
        $to   = $request->filled('to')   ? \Carbon\Carbon::parse($request->to)->endOfDay()     : null;

        $readings = \App\Models\ManualReading::where('site_id', $site->id)
            ->whereIn('site_parameter_id', $params->pluck('id'))
            ->when($from && $to, fn($q) => $q->whereBetween('reading_date', [$from, $to]))
            ->orderBy('reading_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $rows = $readings->groupBy(fn($r) =>
            $r->reading_date->format('Y-m-d') . '||' .
            $r->created_at->format('Y-m-d H:i')
        );

        // Build array for Excel
        $headers = ['Date'];
        foreach ($params as $p) {
            $headers[] = $p->name . ($p->unit ? ' ('.$p->unit.')' : '');
        }
        $headers[] = 'Notes';

        $data = [SafeExport::row($headers)];
        foreach ($rows as $key => $rowReadings) {
            $date = explode('||', $key)[0];
            $row  = [$date];
            foreach ($params as $p) {
                $r     = $rowReadings->firstWhere('site_parameter_id', $p->id);
                $row[] = $r?->value ?? '';
            }
            $row[] = $rowReadings->whereNotNull('notes')->first()?->notes ?? '';
            $data[] = SafeExport::row($row);
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ArrayExport($data, $group),
            $filename
        );
    }


}
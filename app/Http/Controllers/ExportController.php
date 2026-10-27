<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesSiteAccess;
use App\Http\Controllers\Concerns\ResolvesDateRange;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SensorReading;
use App\Models\ManualReading;
use App\Support\SafeExport;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Support\XlsxStreamWriter;

class ExportController extends Controller
{
    use AuthorizesSiteAccess, ResolvesDateRange;

    /**
     * Emits one category's sensor+manual readings as rows: a column-header
     * row, then one row per timestamp (sensor first, then manual). Shared by
     * every CSV and Excel export so the file shape can't drift between them.
     */
    private function eachCategoryRow(Site $site, SiteCategory $category, Carbon $from, Carbon $to, callable $emit): void
    {
        $params       = $category->activeParameters;
        $sensorParams = $params->where('input_type', 'sensor');
        $manualParams = $params->where('input_type', 'manual');

        $headerRow = ['Timestamp'];
        foreach ($sensorParams as $p) {
            $headerRow[] = $p->name . ($p->unit ? ' (' . $p->unit . ')' : '');
        }
        foreach ($manualParams as $p) {
            $headerRow[] = $p->name . ($p->unit ? ' (' . $p->unit . ')' : '');
        }
        // Where a field measurement was taken; empty on sensor rows.
        $plotColumns = $manualParams->isNotEmpty();
        if ($plotColumns) {
            array_push($headerRow, 'Plot', 'Plant');
        }
        $headerRow[] = 'Type';
        $emit(SafeExport::row($headerRow));
        $plots = config('analysis.plots');

        // Rows are streamed straight from the database, one timestamp at a
        // time: a long window holds far more readings than fit in memory.
        $writeRows = function ($query, string $dateColumn, $ownParams, int $leadingBlanks, int $trailingBlanks, string $type) use ($emit, $plotColumns, $plots) {
            $timestamp = null;
            $values    = [];
            $place     = $plotColumns ? ['', ''] : [];

            // Built once: this runs for every row of a long export, and going
            // through the parameter models each time was most of its cost.
            $ids      = $ownParams->pluck('id')->all();
            $leading  = array_fill(0, $leadingBlanks, '');
            $trailing = array_fill(0, $trailingBlanks, '');

            $flush = function () use (&$timestamp, &$values, &$place, $emit, $ids, $leading, $trailing, $type) {
                if ($timestamp === null) {
                    return;
                }
                $row = [$timestamp, ...$leading];
                foreach ($ids as $id) {
                    $row[] = $values[$id] ?? '';
                }
                $emit(SafeExport::row([...$row, ...$trailing, ...$place, $type]));
                $values = [];
            };

            // Plain rows rather than models: a long export reads hundreds of
            // thousands of readings, and hydrating each one is most of the time.
            $columns  = $type === 'Sensor'
                ? [$dateColumn, 'site_parameter_id', 'value', 'value_text']
                : [$dateColumn, 'site_parameter_id', 'value', 'zone', 'plant'];
            $readings = $query->toBase()->select($columns)
                ->orderBy($dateColumn, 'desc')->orderBy('id')->cursor();
            foreach ($readings as $reading) {
                $at = substr((string) $reading->{$dateColumn}, 0, 19);
                if ($at !== $timestamp) {
                    $flush();
                    $timestamp = $at;
                    if ($type === 'Manual') {
                        $place = [$plots[$reading->zone] ?? (string) $reading->zone, (string) $reading->plant];
                    }
                }
                $values[$reading->site_parameter_id] ??= $reading->value ?? $reading->value_text ?? '';
            }
            $flush();
        };

        $writeRows(
            SensorReading::where('site_id', $site->id)
                ->whereIn('site_parameter_id', $sensorParams->pluck('id'))
                ->whereBetween('read_at', [$from, $to]),
            'read_at', $sensorParams, 0, $manualParams->count(), 'Sensor',
        );

        $writeRows(
            ManualReading::where('site_id', $site->id)
                ->whereIn('site_parameter_id', $manualParams->pluck('id'))
                ->whereBetween('reading_date', [$from, $to]),
            'reading_date', $manualParams, $sensorParams->count(), 0, 'Manual',
        );
    }

    /**
     * Writes one category to an open CSV handle, optionally under a
     * "=== label ===" section header followed by a blank line, so several
     * categories can share one file.
     */
    private function writeCsvCategoryBlock($handle, Site $site, SiteCategory $category, Carbon $from, Carbon $to, ?string $sectionLabel = null): void
    {
        if ($sectionLabel !== null) {
            fputcsv($handle, SafeExport::row(['=== ' . $sectionLabel . ' ===']));
        }

        $this->eachCategoryRow($site, $category, $from, $to, fn (array $row) => fputcsv($handle, $row));

        if ($sectionLabel !== null) {
            fputcsv($handle, []); // Ligne vide entre catégories
        }
    }

    private function streamCsv(string $filename, callable $write)
    {
        return response()->stream(function () use ($write) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            $write($handle);
            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Excel workbook with one sheet per [sheet name, site, category]. Rows go
     * straight to a temporary file as they are read: building the workbook in
     * memory ran out of memory past about a week of ThingsBoard data.
     */
    private function downloadXlsx(string $filename, iterable $sheets, Carbon $from, Carbon $to)
    {
        $writer = new XlsxStreamWriter();
        $used   = [];

        foreach ($sheets as [$name, $site, $category]) {
            $writer->addSheet($this->sheetName($name, $used));

            $isHeader = true;
            $this->eachCategoryRow($site, $category, $from, $to, function (array $row) use ($writer, &$isHeader) {
                $writer->addRow($row, $isHeader);
                $isHeader = false;
            });
        }

        $path = tempnam(sys_get_temp_dir(), 'apv-export-');
        $writer->save($path);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /** Excel sheet names: at most 31 characters, none of []:*?/\, unique. */
    private function sheetName(string $name, array &$used): string
    {
        $base = mb_substr(trim(str_replace(['[', ']', ':', '*', '?', '/', '\\'], ' ', $name)), 0, 31) ?: 'Sheet';
        $candidate = $base;
        for ($n = 2; in_array(mb_strtolower($candidate), $used, true); $n++) {
            $candidate = mb_substr($base, 0, 31 - strlen(" ($n)")) . " ($n)";
        }
        $used[] = mb_strtolower($candidate);

        return $candidate;
    }

    private function exportSite(string $site): Site
    {
        $site = Site::where('slug', $site)->orWhere('id', $site)->firstOrFail();
        $this->authorizeSiteAccess($site);
        $site->load(['activeCategories.activeParameters']);

        return $site;
    }

    private function exportSites(): \Illuminate\Support\Collection
    {
        // Tout utilisateur connecté peut exporter — mais uniquement les sites
        // auxquels il a accès (les admins n'ont pas de filtre : null = tous).
        $siteIds = $this->accessibleSiteIds();

        return Site::where('status', 'active')
            ->when($siteIds !== null, fn ($q) => $q->whereIn('id', $siteIds))
            ->with(['activeCategories.activeParameters'])
            ->get();
    }

    // ── Export toutes catégories Excel ─────────────────────────
    public function allExcel(Request $request, string $site)
    {
        $site = $this->exportSite($site);
        [$from, $to] = $this->resolveDateRange($request, 24);

        $sheets = $site->activeCategories->map(fn ($c) => [$c->name, $site, $c]);

        return $this->downloadXlsx($site->slug . '_data_' . now()->format('Ymd_His') . '.xlsx', $sheets, $from, $to);
    }

    // ── Export toutes catégories CSV ───────────────────────────
    public function allCsv(Request $request, string $site)
    {
        $site = $this->exportSite($site);
        [$from, $to] = $this->resolveDateRange($request, 24);

        return $this->streamCsv($site->slug . '_data_' . now()->format('Ymd_His') . '.csv', function ($handle) use ($site, $from, $to) {
            foreach ($site->activeCategories as $category) {
                $this->writeCsvCategoryBlock($handle, $site, $category, $from, $to, $category->name);
            }
        });
    }

    // ── Export une catégorie Excel ─────────────────────────────
    public function categoryExcel(Request $request, string $site, string $category)
    {
        $site = $this->exportSite($site);
        $cat  = $site->activeCategories->firstWhere('slug', $category) ?? abort(404);
        [$from, $to] = $this->resolveDateRange($request, 24);

        return $this->downloadXlsx($site->slug . '_' . $category . '_' . now()->format('Ymd_His') . '.xlsx', [[$cat->name, $site, $cat]], $from, $to);
    }

    // ── Export une catégorie CSV ───────────────────────────────
    public function categoryCsv(Request $request, string $site, string $category)
    {
        $site = $this->exportSite($site);
        $cat  = $site->activeCategories->firstWhere('slug', $category) ?? abort(404);
        [$from, $to] = $this->resolveDateRange($request, 24);

        return $this->streamCsv($site->slug . '_' . $category . '_' . now()->format('Ymd_His') . '.csv', function ($handle) use ($site, $cat, $from, $to) {
            $this->writeCsvCategoryBlock($handle, $site, $cat, $from, $to);
        });
    }

    // ── Export tous les sites Excel ────────────────────────────
    public function allSitesExcel(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request, 24);

        $sheets = [];
        foreach ($this->exportSites() as $site) {
            foreach ($site->activeCategories as $category) {
                // "Fass - Solar": the site part is cut first to fit 31 characters.
                $sheets[] = [mb_substr($site->name, 0, 15) . ' - ' . $category->name, $site, $category];
            }
        }

        return $this->downloadXlsx('all_sites_data_' . now()->format('Ymd_His') . '.xlsx', $sheets, $from, $to);
    }

    // ── Export tous les sites CSV ──────────────────────────────
    public function allSitesCsv(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request, 24);
        $sites = $this->exportSites();

        return $this->streamCsv('all_sites_data_' . now()->format('Ymd_His') . '.csv', function ($handle) use ($sites, $from, $to) {
            foreach ($sites as $site) {
                foreach ($site->activeCategories as $category) {
                    $this->writeCsvCategoryBlock($handle, $site, $category, $from, $to, $site->name . ' — ' . $category->name);
                }
            }
        });
    }

    /**
     * Rows of a group's saved records, as on the Saved Records table: one row
     * per record (date, plot, plant), newest first. Saved Records is a running
     * ledger — the default is the full history (unlike the other exports'
     * rolling "last N hours"), but from/to narrow it down when requested.
     */
    private function groupRows(Request $request, string $site, string $category, string $group): array
    {
        $site = Site::where('slug', $site)->orWhere('id', $site)->firstOrFail();
        $this->authorizeSiteAccess($site);
        $cat = $site->categories()->where('slug', $category)->firstOrFail();
        $cat->load('activeParameters');
        $params = $cat->activeParameters
            ->where('input_type', 'manual')
            ->filter(fn ($p) => $p->group_name === $group)
            ->values();

        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : null;
        $to   = $request->filled('to')   ? Carbon::parse($request->to)->endOfDay()     : null;

        $readings = ManualReading::where('site_id', $site->id)
            ->whereIn('site_parameter_id', $params->pluck('id'))
            ->when($from && $to, fn ($q) => $q->whereBetween('reading_date', [$from, $to]))
            ->orderBy('reading_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $header = ['Date', 'Plot', 'Plant'];
        foreach ($params as $p) {
            $header[] = $p->name . ($p->unit ? ' (' . $p->unit . ')' : '');
        }
        $header[] = 'Notes';

        $rows = [SafeExport::row($header)];
        foreach ($readings->groupBy(fn ($r) => $r->recordKey()) as $record) {
            $first = $record->first();
            $row   = [$first->reading_date->format('Y-m-d'), $first->plotLabel() ?? '', $first->plant ?? ''];
            foreach ($params as $p) {
                $row[] = $record->firstWhere('site_parameter_id', $p->id)?->value ?? '';
            }
            $row[] = $record->whereNotNull('notes')->first()?->notes ?? '';
            $rows[] = SafeExport::row($row);
        }

        $filename = Str::slug($site->name) . '-' . $category . '-' . Str::slug($group) . '-' . now()->format('Ymd');

        return [$filename, $rows];
    }

    // ── Export groupe manuel CSV ───────────────────────────────
    public function groupCsv(Request $request, string $site, string $category, string $group)
    {
        [$filename, $rows] = $this->groupRows($request, $site, $category, $group);

        return $this->streamCsv($filename . '.csv', function ($handle) use ($rows) {
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
        });
    }

    // ── Export groupe manuel Excel ─────────────────────────────
    public function groupExcel(Request $request, string $site, string $category, string $group)
    {
        [$filename, $rows] = $this->groupRows($request, $site, $category, $group);

        $writer = new XlsxStreamWriter();
        $used   = [];
        $writer->addSheet($this->sheetName($group, $used));
        foreach ($rows as $i => $row) {
            $writer->addRow($row, $i === 0);
        }

        $path = tempnam(sys_get_temp_dir(), 'apv-export-');
        $writer->save($path);

        return response()->download($path, $filename . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }
}

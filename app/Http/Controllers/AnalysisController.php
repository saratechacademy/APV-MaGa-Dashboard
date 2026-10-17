<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesSiteAccess;
use App\Models\Site;
use App\Models\SiteParameter;
use App\Services\AnalysisService;
use App\Support\SafeExport;
use App\Support\Zones;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Analysis page: compare one quantity between the zones of a site
 * (reference plot vs under the panels…) or between sites, over a period,
 * with charts, plain-language findings, a CSV of the data and a printable
 * report.
 */
class AnalysisController extends Controller
{
    use AuthorizesSiteAccess;

    public function __construct(private readonly AnalysisService $analysis) {}

    public function index()
    {
        return view('analysis.index', [
            'catalog'    => $this->catalog(),
            'quantities' => collect(config('analysis.quantities'))->map(fn ($q) => ['title' => $q['title'], 'about' => $q['about'], 'only_daily' => $q['only_daily'] ?? false]),
            'zones'      => Zones::LABELS,
        ]);
    }

    public function data(Request $request)
    {
        return response()->json($this->run($request));
    }

    public function csv(Request $request)
    {
        $result = $this->run($request);
        $name   = 'apvmaga-analysis-' . $result['quantity'] . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($result) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['# ' . $result['title'] . ($result['unit'] ? " ({$result['unit']})" : '') . ', ' . ($result['step'] === 'day' ? 'daily' : 'hourly') . ' values, ' . $result['from'] . ' to ' . $result['to'] . ' (UTC)']);
            fputcsv($out, array_merge(['time_utc'], array_column($result['series'], 'label')));
            foreach ($result['labels'] as $i => $ms) {
                fputcsv($out, array_merge(
                    [gmdate($result['step'] === 'day' ? 'Y-m-d' : 'Y-m-d H:i', intdiv($ms, 1000))],
                    array_map(fn ($s) => SafeExport::cell($s['points'][$i] ?? ''), $result['series']),
                ));
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function report(Request $request)
    {
        $result = $this->run($request);

        return view('analysis.report', [
            'result'      => $result,
            'description' => $this->describe($request, $result),
            'generatedAt' => now(),
        ]);
    }

    /** Validates the request, builds the series and runs the comparison. */
    private function run(Request $request): array
    {
        $data = $request->validate([
            'mode'     => 'required|in:site,sites',
            'quantity' => ['required', 'string', function ($attr, $value, $fail) {
                if (!config("analysis.quantities.{$value}")) {
                    $fail('This quantity cannot be analysed.');
                }
            }],
            'site'     => 'required_if:mode,site|nullable|integer',
            'sites'    => 'required_if:mode,sites|nullable|array|min:2',
            'sites.*'  => 'integer',
            'zone'     => 'nullable|string|max:30',
            'from'     => 'required|date',
            'to'       => 'required|date|after_or_equal:from',
            'step'     => 'required|in:hour,day',
        ]);

        $from = Carbon::parse($data['from'], 'UTC')->startOfDay();
        $to   = Carbon::parse($data['to'], 'UTC')->addDay()->startOfDay();
        if ($from->diffInDays($to) > config('analysis.max_days')) {
            throw ValidationException::withMessages(['to' => 'The period is limited to ' . config('analysis.max_days') . ' days.']);
        }

        $series = $data['mode'] === 'site'
            ? $this->zoneSeries($this->accessibleSite($data['site']), $data['quantity'], $data['zone'] ?? null)
            : $this->siteSeries($data['sites'], $data['quantity'], $data['zone'] ?? Zones::REFERENCE);

        if (count($series) < 2) {
            throw ValidationException::withMessages(['quantity' => $data['mode'] === 'site'
                ? 'This site measures this quantity in fewer than two zones: there is nothing to compare.'
                : 'Fewer than two of the chosen sites measure this quantity in this zone.']);
        }

        return $this->analysis->compare($data['quantity'], $series, $from, $to, $data['step']);
    }

    /** One series per zone of the site, the reference first. */
    private function zoneSeries(Site $site, string $quantity, ?string $onlyZones): array
    {
        $wanted = $onlyZones ? explode(',', $onlyZones) : null;

        return $this->parameters([$site->id], $quantity)
            ->filter(fn ($p) => $p->zone && (!$wanted || in_array($p->zone, $wanted, true)))
            ->groupBy('zone')
            ->sortBy(fn ($group, $zone) => array_search($zone, array_keys(Zones::LABELS), true))
            ->map(fn ($group, $zone) => ['label' => Zones::label($zone), 'parameters' => $group])
            ->values()->all();
    }

    /** One series per site, in the given zone ('any' for all sensors of the site). */
    private function siteSeries(array $siteIds, string $quantity, string $zone): array
    {
        $series = [];
        foreach ($siteIds as $id) {
            $site = $this->accessibleSite($id);
            $parameters = $this->parameters([$site->id], $quantity)
                ->filter(fn ($p) => $zone === 'any' || $p->zone === $zone);

            if ($parameters->isNotEmpty()) {
                $series[] = ['label' => $site->name, 'parameters' => $parameters];
            }
        }

        return $series;
    }

    /** Active sensor parameters of active categories measuring the quantity. */
    private function parameters(array $siteIds, string $quantity): Collection
    {
        return SiteParameter::query()
            ->where('is_active', true)
            ->where('input_type', 'sensor')
            ->whereNotNull('metric')
            ->whereHas('category', fn ($q) => $q->whereIn('site_id', $siteIds)->where('is_active', true))
            ->with('category')
            ->get()
            ->filter(fn ($p) => $p->quantity() === $quantity)
            ->values();
    }

    private function accessibleSite($id): Site
    {
        $site = Site::where('status', 'active')->findOrFail($id);
        $this->authorizeSiteAccess($site);

        return $site;
    }

    /**
     * What the user can analyse: per site, each quantity with the zones it
     * is measured in.
     *
     * @return array<int, array{id: int, name: string, quantities: array<string, string[]>}>
     */
    private function catalog(): array
    {
        $ids   = $this->accessibleSiteIds();
        $sites = Site::where('status', 'active')->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))->orderBy('name')->get();
        $known = array_keys(config('analysis.quantities'));

        $all = SiteParameter::query()
            ->where('is_active', true)->where('input_type', 'sensor')->whereNotNull('metric')
            ->whereHas('category', fn ($q) => $q->whereIn('site_id', $sites->pluck('id'))->where('is_active', true))
            ->with('category:id,site_id')
            ->get();

        return $sites->map(function ($site) use ($all, $known) {
            $quantities = [];
            foreach ($all->filter(fn ($p) => $p->category->site_id === $site->id) as $p) {
                $q = $p->quantity();
                if (in_array($q, $known, true)) {
                    $quantities[$q] ??= [];
                    if ($p->zone && !in_array($p->zone, $quantities[$q], true)) {
                        $quantities[$q][] = $p->zone;
                    }
                }
            }

            return ['id' => $site->id, 'name' => $site->name, 'quantities' => $quantities];
        })->filter(fn ($s) => $s['quantities'])->values()->all();
    }

    /** One line describing the selection, for the report header. */
    private function describe(Request $request, array $result): string
    {
        $sites = $request->input('mode') === 'site'
            ? Site::find($request->input('site'))?->name
            : Site::whereIn('id', (array) $request->input('sites'))->orderBy('name')->pluck('name')->join(', ');

        $what = $request->input('mode') === 'site'
            ? "{$result['title']} by zone at {$sites}"
            : "{$result['title']} between {$sites} (" . ($request->input('zone', Zones::REFERENCE) === 'any' ? 'all zones' : Zones::label($request->input('zone', Zones::REFERENCE))) . ')';

        return $what . ', ' . ($result['step'] === 'day' ? 'daily' : 'hourly') . ' values';
    }
}

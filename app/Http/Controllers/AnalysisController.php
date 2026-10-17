<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesSiteAccess;
use App\Models\ManualReading;
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
 * Analysis page: compare one quantity between the zones of a site, between
 * sites, between two periods, or any 2 to 4 parameters together, with
 * charts, plain-language findings, a CSV of the data and a printable report.
 */
class AnalysisController extends Controller
{
    use AuthorizesSiteAccess;

    public function __construct(private readonly AnalysisService $analysis) {}

    public function index()
    {
        $catalog = $this->catalog();

        return view('analysis.index', [
            'catalog'    => $catalog['sites'],
            'parameters' => $catalog['parameters'],
            'quantities' => collect(config('analysis.quantities'))->map(fn ($q) => [
                'title' => $q['title'], 'about' => $q['about'], 'only_daily' => $q['only_daily'] ?? false, 'manual' => ($q['source'] ?? null) === 'manual',
            ]),
            'zones'      => Zones::LABELS,
            'plots'      => config('analysis.plots'),
        ]);
    }

    public function data(Request $request)
    {
        return response()->json($this->run($request));
    }

    public function csv(Request $request)
    {
        $result = $this->run($request);
        $name   = 'apvmaga-analysis-' . ($result['quantity'] ?? 'parameters') . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($result) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['# ' . $result['title'] . ($result['unit'] ? " ({$result['unit']})" : '') . ', '
                . ($result['step'] === 'day' ? 'daily' : 'hourly') . ' values, UTC']);
            fputcsv($out, array_merge([$result['relative'] ? ($result['step'] === 'day' ? 'day_of_period' : 'hour_of_period') : 'time_utc'],
                array_map(fn ($s) => $s['label'] . ($result['unit'] === null && $s['unit'] ? " ({$s['unit']})" : ''), $result['series'])));
            foreach ($result['labels'] as $i => $label) {
                $time = $result['relative'] ? $label + 1 : gmdate($result['step'] === 'day' ? 'Y-m-d' : 'Y-m-d H:i', intdiv($label, 1000));
                fputcsv($out, array_merge([$time], array_map(fn ($s) => SafeExport::cell($s['points'][$i] ?? ''), $result['series'])));
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
            'mode'     => 'required|in:site,sites,periods,free',
            'quantity' => ['required_unless:mode,free', 'nullable', 'string', function ($attr, $value, $fail) {
                if ($value && !config("analysis.quantities.{$value}")) {
                    $fail('This quantity cannot be analysed.');
                }
            }],
            'site'     => 'required_if:mode,site|required_if:mode,periods|nullable|integer',
            'sites'    => 'required_if:mode,sites|nullable|array|min:2',
            'sites.*'  => 'integer',
            'params'   => 'required_if:mode,free|nullable|array|min:2|max:4',
            'params.*' => 'integer',
            'zone'     => 'nullable|string|max:30',
            'from'     => 'required|date',
            'to'       => 'required|date|after_or_equal:from',
            'from2'    => 'required_if:mode,periods|nullable|date',
            'to2'      => 'required_if:mode,periods|nullable|date|after_or_equal:from2',
            'step'     => 'required|in:hour,day',
        ]);

        [$from, $to] = $this->window($data['from'], $data['to'], 'to');

        $series = match ($data['mode']) {
            'site'    => $this->zoneSeries($this->accessibleSite($data['site']), $data['quantity'], $data['zone'] ?? null),
            'sites'   => $this->siteSeries($data['sites'], $data['quantity'], $data['zone'] ?? Zones::REFERENCE),
            'periods' => $this->periodSeries($this->accessibleSite($data['site']), $data['quantity'], $data['zone'] ?? 'any',
                [$from, $to], $this->window($data['from2'], $data['to2'], 'to2')),
            'free'    => $this->parameterSeries($data['params']),
        };

        if (count($series) < 2) {
            throw ValidationException::withMessages(['quantity' => match ($data['mode']) {
                'site'    => $this->isManual($data['quantity'])
                    ? 'No field measurements of this kind were recorded on two different plots of this site: there is nothing to compare. Choose the plot when entering them.'
                    : 'This site measures this quantity in fewer than two zones: there is nothing to compare.',
                'sites'   => 'Fewer than two of the chosen sites measure this quantity in this zone.',
                'periods' => 'This site does not measure this quantity in this zone.',
                'free'    => 'Choose at least two parameters.',
            }]);
        }

        return $this->analysis->compare($series, $from, $to, $data['step'], correlate: $data['mode'] === 'free');
    }

    /** @return array{0: Carbon, 1: Carbon} whole UTC days, end exclusive */
    private function window(string $from, string $to, string $field): array
    {
        $start = Carbon::parse($from, 'UTC')->startOfDay();
        $end   = Carbon::parse($to, 'UTC')->addDay()->startOfDay();
        if ($start->diffInDays($end) > config('analysis.max_days')) {
            throw ValidationException::withMessages([$field => 'A period is limited to ' . config('analysis.max_days') . ' days.']);
        }

        return [$start, $end];
    }

    private function isManual(string $quantity): bool
    {
        return (config("analysis.quantities.{$quantity}.source") ?? null) === 'manual';
    }

    /**
     * One series per zone of the site, the reference first. Field
     * measurements are split by the plot they were recorded on.
     */
    private function zoneSeries(Site $site, string $quantity, ?string $onlyZones): array
    {
        $wanted     = $onlyZones ? explode(',', $onlyZones) : null;
        $parameters = $this->parameters([$site->id], $quantity);

        if ($this->isManual($quantity)) {
            return collect($this->recordedPlots($parameters))
                ->filter(fn ($zone) => !$wanted || in_array($zone, $wanted, true))
                ->map(fn ($zone) => ['label' => config('analysis.plots')[$zone] ?? Zones::label($zone), 'parameters' => $parameters, 'quantity' => $quantity, 'zone' => $zone])
                ->values()->all();
        }

        return $parameters
            ->filter(fn ($p) => $p->zone && (!$wanted || in_array($p->zone, $wanted, true)))
            ->groupBy('zone')
            ->sortBy(fn ($group, $zone) => array_search($zone, array_keys(Zones::LABELS), true))
            ->map(fn ($group, $zone) => ['label' => Zones::label($zone), 'parameters' => $group, 'quantity' => $quantity])
            ->values()->all();
    }

    /** One series per site, in the given zone or plot ('any' for the whole site). */
    private function siteSeries(array $siteIds, string $quantity, string $zone): array
    {
        $series = [];
        foreach ($siteIds as $id) {
            $site = $this->accessibleSite($id);
            if ($entry = $this->placeSeries($site, $quantity, $zone, $site->name)) {
                $series[] = $entry;
            }
        }

        return $series;
    }

    /** The same place over two periods; series are aligned on the day since each period's start. */
    private function periodSeries(Site $site, string $quantity, string $zone, array $a, array $b): array
    {
        $fmt   = fn (array $w) => $w[0]->format('d M Y') . ' – ' . $w[1]->copy()->subDay()->format('d M Y');
        $place = $this->placeSeries($site, $quantity, $zone, '');
        if (!$place) {
            return [];
        }

        return [
            ['label' => 'Period A (' . $fmt($a) . ')', 'from' => $a[0], 'to' => $a[1]] + $place,
            ['label' => 'Period B (' . $fmt($b) . ')', 'from' => $b[0], 'to' => $b[1]] + $place,
        ];
    }

    /** One place: a site's parameters for a quantity, restricted to a zone (or plot). */
    private function placeSeries(Site $site, string $quantity, string $zone, string $label): ?array
    {
        $parameters = $this->parameters([$site->id], $quantity);

        if ($this->isManual($quantity)) {
            return $parameters->isEmpty() ? null
                : ['label' => $label, 'parameters' => $parameters, 'quantity' => $quantity, 'zone' => $zone === 'any' ? null : $zone];
        }

        $parameters = $parameters->filter(fn ($p) => $zone === 'any' || $p->zone === $zone)->values();

        return $parameters->isEmpty() ? null : ['label' => $label, 'parameters' => $parameters, 'quantity' => $quantity];
    }

    /** Free comparison: each chosen parameter is its own series. */
    private function parameterSeries(array $ids): array
    {
        $series = [];
        foreach (array_unique($ids) as $id) {
            $parameter = SiteParameter::with('category.site')->where('is_active', true)->findOrFail($id);
            $site = $this->accessibleSite($parameter->category->site_id);
            $quantity = $parameter->quantity();

            $series[] = [
                'label'      => "{$site->name} – {$parameter->name}",
                'parameters' => collect([$parameter]),
                'quantity'   => config("analysis.quantities.{$quantity}") ? $quantity : ($parameter->input_type === 'manual' ? 'other_manual' : 'other'),
            ];
        }

        return $series;
    }

    /** Active parameters of active categories measuring the quantity (sensor or field entry, as the quantity says). */
    private function parameters(array $siteIds, string $quantity): Collection
    {
        return SiteParameter::query()
            ->where('is_active', true)
            ->where('input_type', $this->isManual($quantity) ? 'manual' : 'sensor')
            ->whereNotNull('metric')
            ->whereHas('category', fn ($q) => $q->whereIn('site_id', $siteIds)->where('is_active', true))
            ->get()
            ->filter(fn ($p) => $p->quantity() === $quantity)
            ->values();
    }

    /** Plots that field measurements of these parameters were recorded on, reference first. */
    private function recordedPlots(Collection $parameters): array
    {
        if ($parameters->isEmpty()) {
            return [];
        }

        $recorded = ManualReading::whereIn('site_parameter_id', $parameters->pluck('id'))
            ->whereNotNull('zone')->distinct()->pluck('zone')->all();

        return array_values(array_filter(array_keys(config('analysis.plots')), fn ($z) => in_array($z, $recorded, true)));
    }

    private function accessibleSite($id): Site
    {
        $site = Site::where('status', 'active')->findOrFail($id);
        $this->authorizeSiteAccess($site);

        return $site;
    }

    /**
     * What the user can analyse: per site, each quantity with the zones (or
     * recorded plots) it is available in; and every numeric parameter, for
     * the free comparison.
     */
    private function catalog(): array
    {
        $ids   = $this->accessibleSiteIds();
        $sites = Site::where('status', 'active')->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))->orderBy('name')->get();
        $known = config('analysis.quantities');

        $all = SiteParameter::query()
            ->where('is_active', true)
            ->whereIn('data_type', ['float', 'integer'])
            ->whereHas('category', fn ($q) => $q->whereIn('site_id', $sites->pluck('id'))->where('is_active', true))
            ->with('category:id,site_id,name')
            ->orderBy('site_category_id')->orderBy('sort_order')
            ->get();

        $plots = ManualReading::whereIn('site_parameter_id', $all->where('input_type', 'manual')->pluck('id'))
            ->whereNotNull('zone')->select('site_parameter_id', 'zone')->distinct()->get()
            ->groupBy('site_parameter_id')->map(fn ($rows) => $rows->pluck('zone')->all());

        $siteEntries = $sites->map(function ($site) use ($all, $known, $plots) {
            $quantities = [];
            foreach ($all->filter(fn ($p) => $p->category->site_id === $site->id && $p->metric) as $p) {
                $q = $p->quantity();
                if (!isset($known[$q]) || (($known[$q]['source'] ?? 'sensor') === 'manual') !== ($p->input_type === 'manual')) {
                    continue;
                }
                $quantities[$q] ??= [];
                $zones = $p->input_type === 'manual' ? ($plots[$p->id] ?? []) : array_filter([$p->zone]);
                $quantities[$q] = array_values(array_unique(array_merge($quantities[$q], $zones)));
            }

            return ['id' => $site->id, 'name' => $site->name, 'quantities' => $quantities];
        })->filter(fn ($s) => $s['quantities'])->values()->all();

        $parameters = $all->map(fn ($p) => [
            'id'       => $p->id,
            'site'     => $p->category->site_id,
            'category' => $p->category->name,
            'name'     => $p->name . ($p->unit ? " ({$p->unit})" : ''),
        ])->values()->all();

        return ['sites' => $siteEntries, 'parameters' => $parameters];
    }

    /** One line describing the selection, for the report header. */
    private function describe(Request $request, array $result): string
    {
        $mode = $request->input('mode');
        $zone = $request->input('zone', $mode === 'periods' ? 'any' : Zones::REFERENCE);
        $zoneText = $zone === 'any' ? 'all zones' : (config('analysis.plots')[$zone] ?? Zones::label($zone));
        $step = $result['step'] === 'day' ? 'daily' : 'hourly';

        return match ($mode) {
            'site'    => "{$result['title']} by zone at " . Site::find($request->input('site'))?->name . ", {$step} values",
            'sites'   => "{$result['title']} between " . Site::whereIn('id', (array) $request->input('sites'))->orderBy('name')->pluck('name')->join(', ') . " ({$zoneText}), {$step} values",
            'periods' => "{$result['title']} at " . Site::find($request->input('site'))?->name . " ({$zoneText}), two periods compared day by day, {$step} values",
            default   => 'Free comparison of ' . count($result['series']) . " parameters, {$step} values",
        };
    }
}

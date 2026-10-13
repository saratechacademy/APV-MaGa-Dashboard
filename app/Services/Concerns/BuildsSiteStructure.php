<?php

namespace App\Services\Concerns;

use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteParameter;
use App\Models\SiteParameterGroup;
use Illuminate\Support\Str;

/**
 * Category / group / chart creation shared by the data-source syncs
 * (ThingsBoard, LI-COR), so every source lays out a site the same way.
 * Everything is find-or-create: once created it belongs to the admin.
 */
trait BuildsSiteStructure
{
    protected function ensureCategory(Site $site, string $slug, int $offlineThresholdMinutes): SiteCategory
    {
        $defaults = collect(SiteCategory::defaults())->firstWhere('slug', $slug);

        return $site->categories()->firstOrCreate(['slug' => $slug], [
            'name'                      => $defaults['name'] ?? Str::headline($slug),
            'icon'                      => $defaults['icon'] ?? null,
            'color'                     => $defaults['color'] ?? null,
            'offline_threshold_minutes' => $offlineThresholdMinutes,
            'is_active'                 => true,
            'sort_order'                => $site->categories()->count(),
        ]);
    }

    protected function ensureGroup(SiteCategory $category, string $name): SiteParameterGroup
    {
        $count = $category->parameterGroups()->count();

        return SiteParameterGroup::firstOrCreate(
            ['site_category_id' => $category->id, 'name' => $name],
            ['color' => SiteParameterGroup::nextPaletteColor($count), 'sort_order' => $count],
        );
    }

    /**
     * Without a configured chart the dashboard falls back to one "Trends"
     * chart plotting every parameter of the category on a single axis —
     * unreadable once units differ. Only done when the parameter is created,
     * so charts stay the admin's to rearrange afterwards.
     */
    protected function addToChart(SiteCategory $category, string $title, SiteParameter $parameter, bool $dashed = false, string $type = 'line'): void
    {
        $chart = $category->charts()->firstOrCreate(['title' => $title], [
            'chart_type'  => $type,
            'col_span'    => 'half',
            'show_legend' => true,
            'is_active'   => true,
            'sort_order'  => $category->charts()->count(),
        ]);

        $position = $chart->chartParameters()->count();

        $chart->chartParameters()->create([
            'site_parameter_id' => $parameter->id,
            'color'             => SiteParameterGroup::nextPaletteColor($position),
            'axis'              => 'left',
            'dashed'            => $dashed,
            // Bars are drawn with the series' fill colour.
            'fill'              => $type === 'bar',
            'sort_order'        => $position,
        ]);
    }
}

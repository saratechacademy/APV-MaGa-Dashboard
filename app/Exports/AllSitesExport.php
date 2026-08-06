<?php

namespace App\Exports;

use App\Models\Site;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AllSitesExport implements WithMultipleSheets
{
    public function __construct(
        protected Carbon  $from,
        protected ?Carbon $to = null,
        protected ?array  $siteIds = null
    ) {
        $this->to ??= now();
    }

    public function sheets(): array
    {
        $sheets = [];

        $sites = Site::where('status', 'active')
            ->when($this->siteIds !== null, fn ($q) => $q->whereIn('id', $this->siteIds))
            ->with(['activeCategories.activeParameters'])
            ->get();

        foreach ($sites as $site) {
            foreach ($site->activeCategories as $category) {
                $sheets[] = new AllSitesCategorySheet($site, $category, $this->from, $this->to);
            }
        }

        return $sheets;
    }
}
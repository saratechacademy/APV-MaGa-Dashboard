<?php

namespace App\Exports;

use App\Models\Site;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AllSitesExport implements WithMultipleSheets
{
    public function __construct(
        protected int $hours = 24
    ) {}

    public function sheets(): array
    {
        $sheets = [];
        $from   = now()->subHours($this->hours);

        $sites = Site::where('status', 'active')
            ->with(['activeCategories.activeParameters'])
            ->get();

        foreach ($sites as $site) {
            foreach ($site->activeCategories as $category) {
                $sheets[] = new AllSitesCategorySheet($site, $category, $from);
            }
        }

        return $sheets;
    }
}
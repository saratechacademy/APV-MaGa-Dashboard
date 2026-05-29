<?php

namespace App\Exports;

use App\Models\Site;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Carbon\Carbon;
use App\Exports\CategorySheet;

class SiteCategoryExport implements WithMultipleSheets
{
    public function __construct(
        protected Site $site,
        protected int  $hours = 24
    ) {}

    public function sheets(): array
    {
        $sheets = [];
        $from   = now()->subHours($this->hours);

        foreach ($this->site->activeCategories as $category) {
            $sheets[] = new CategorySheet($this->site, $category, $from);
        }

        return $sheets;
    }
}
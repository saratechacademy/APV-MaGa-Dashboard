<?php

namespace App\Exports;

use App\Models\Site;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Carbon\Carbon;
use App\Exports\CategorySheet;

class SiteCategoryExport implements WithMultipleSheets
{
    public function __construct(
        protected Site    $site,
        protected Carbon  $from,
        protected ?Carbon $to = null
    ) {
        $this->to ??= now();
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->site->activeCategories as $category) {
            $sheets[] = new CategorySheet($this->site, $category, $this->from, $this->to);
        }

        return $sheets;
    }
}
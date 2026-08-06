<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\SiteParameterGroup;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Groups created before auto-coloring existed default to gray, which makes
        // the dashboard look flat. Give each of them a palette color, ordered by
        // sort_order within its category so neighboring groups don't clash.
        $groups = DB::table('site_parameter_groups')
            ->whereNull('color')
            ->orderBy('site_category_id')
            ->orderBy('sort_order')
            ->get();

        $indexPerCategory = [];

        foreach ($groups as $group) {
            $i = $indexPerCategory[$group->site_category_id] ?? 0;

            DB::table('site_parameter_groups')
                ->where('id', $group->id)
                ->update(['color' => SiteParameterGroup::nextPaletteColor($i)]);

            $indexPerCategory[$group->site_category_id] = $i + 1;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Colors were auto-assigned; nothing meaningful to revert to.
    }
};

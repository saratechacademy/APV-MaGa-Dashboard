<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('site_parameters', function (Blueprint $table) {
            $table->foreignId('site_parameter_group_id')->nullable()->after('group_name')
                  ->constrained('site_parameter_groups')->nullOnDelete();
        });

        // Backfill: turn existing free-text group_name values into real, per-category
        // group rows so admins can manage (rename/reorder/color) what they already typed.
        $existingGroups = DB::table('site_parameters')
            ->select('site_category_id', 'group_name')
            ->whereNotNull('group_name')
            ->where('group_name', '!=', '')
            ->distinct()
            ->orderBy('site_category_id')
            ->get();

        $sortOrders = [];

        foreach ($existingGroups as $row) {
            $sortOrders[$row->site_category_id] = ($sortOrders[$row->site_category_id] ?? -1) + 1;

            $groupId = DB::table('site_parameter_groups')->insertGetId([
                'site_category_id' => $row->site_category_id,
                'name'             => $row->group_name,
                'sort_order'       => $sortOrders[$row->site_category_id],
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            DB::table('site_parameters')
                ->where('site_category_id', $row->site_category_id)
                ->where('group_name', $row->group_name)
                ->update(['site_parameter_group_id' => $groupId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_parameters', function (Blueprint $table) {
            $table->dropForeign(['site_parameter_group_id']);
            $table->dropColumn('site_parameter_group_id');
        });
    }
};

<?php

namespace App\Http\Controllers\Concerns;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Single source of truth for "what time window did the user ask for?" across
 * the dashboard (charts, raw data) and every export endpoint. Supports the
 * existing rolling-window shortcut (?hours=24) alongside an explicit custom
 * range (?from=2026-03-03&to=2026-03-10) picked from the topbar's date inputs.
 * Explicit dates win when both are present.
 */
trait ResolvesDateRange
{
    protected function resolveDateRange(Request $request, int $defaultHours = 24): array
    {
        if ($request->filled('from') || $request->filled('to')) {
            $to   = $request->filled('to')   ? Carbon::parse($request->to)->endOfDay()     : now();
            $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : $to->copy()->subDays(30);

            return [$from, $to];
        }

        $hours = (int) $request->input('hours', $defaultHours);
        $to    = now();

        return [$to->copy()->subHours($hours), $to];
    }
}

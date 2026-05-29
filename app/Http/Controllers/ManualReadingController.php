<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\SiteParameter;
use App\Models\ManualReading;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManualReadingController extends Controller
{
    public function store(Request $request, Site $site)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $site->user_id !== $user->id) {
            abort(403);
        }

        $request->validate([
            'readings'              => 'required|array',
            'readings.*.param_id'   => 'required|exists:site_parameters,id',
            'readings.*.value'      => 'required',
            'reading_date'          => 'required|date',
            'notes'                 => 'nullable|string',
        ]);

        // Date + heure exacte de la saisie
        $readingDateTime = \Carbon\Carbon::parse($request->reading_date)
            ->setTimeFrom(now());

        foreach ($request->readings as $reading) {
            if (isset($reading['value']) && $reading['value'] !== '') {
                ManualReading::create([
                    'site_id'           => $site->id,
                    'site_parameter_id' => $reading['param_id'],
                    'user_id'           => $user->id,
                    'value'             => $reading['value'],
                    'reading_date'      => $readingDateTime,
                    'notes'             => $request->notes,
                ]);
            }
        }

        return redirect()->route('dashboard.site', $site)
                         ->with('success', 'Readings saved successfully.')
                         ->with('active_tab', 'manual-input');
    }

    public function history(Site $site, SiteParameter $parameter)
    {
        $readings = ManualReading::where('site_id', $site->id)
                                 ->where('site_parameter_id', $parameter->id)
                                 ->with('user')
                                 ->latest('reading_date')
                                 ->paginate(20);

        return response()->json($readings);
    }
}
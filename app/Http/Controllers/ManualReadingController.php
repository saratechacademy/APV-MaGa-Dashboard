<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesSiteAccess;
use App\Models\Site;
use App\Models\SiteParameter;
use App\Models\ManualReading;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ManualReadingController extends Controller
{
    use AuthorizesSiteAccess;

    public function store(Request $request, Site $site)
    {
        $this->authorizeSiteAccess($site);

        $user = Auth::user();
        if ($user->isObservateur()) {
            abort(403, 'Observers cannot submit manual readings.');
        }

        // Only parameters that actually belong to one of this site's categories
        // are valid targets — otherwise a param_id from another site (guessed
        // or seen elsewhere) could be submitted against this $site->id.
        $siteParameters = SiteParameter::whereIn(
            'site_category_id',
            $site->categories()->pluck('id')
        )->where('input_type', 'manual')->get()->keyBy('id');

        $validator = Validator::make($request->all(), [
            'readings'              => 'required|array',
            'readings.*.param_id'   => ['required', Rule::in($siteParameters->keys())],
            // Nullable, not required: the form lets an agent fill in only some
            // of a group's fields and leave the rest blank — those blank rows
            // are silently skipped below, not treated as validation failures.
            'readings.*.value'      => 'nullable',
            'reading_date'          => 'required|date',
            'notes'                 => 'nullable|string',
        ]);

        $validator->after(function ($validator) use ($request, $siteParameters) {
            foreach ($request->readings as $i => $reading) {
                if (!isset($reading['value']) || $reading['value'] === '') {
                    continue;
                }

                $param = $siteParameters->get($reading['param_id']);
                if (!$param) {
                    continue; // already rejected by the Rule::in above
                }

                if (in_array($param->data_type, ['float', 'integer'], true) && !is_numeric($reading['value'])) {
                    $validator->errors()->add("readings.{$i}.value", "{$param->name} must be a number.");
                    continue;
                }

                if (is_numeric($reading['value'])) {
                    $value = (float) $reading['value'];
                    if ($param->min_value !== null && $value < $param->min_value) {
                        $validator->errors()->add("readings.{$i}.value", "{$param->name} must be at least {$param->min_value}.");
                    }
                    if ($param->max_value !== null && $value > $param->max_value) {
                        $validator->errors()->add("readings.{$i}.value", "{$param->name} must be at most {$param->max_value}.");
                    }
                }
            }
        });
        $validator->validate();

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
        $this->authorizeSiteAccess($site);

        $readings = ManualReading::where('site_id', $site->id)
                                 ->where('site_parameter_id', $parameter->id)
                                 ->with('user')
                                 ->latest('reading_date')
                                 ->paginate(20);

        return response()->json($readings);
    }
}
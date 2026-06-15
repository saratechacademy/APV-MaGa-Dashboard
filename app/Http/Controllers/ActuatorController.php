<?php

namespace App\Http\Controllers;

use App\Models\ActuatorCommand;
use App\Models\Site;
use App\Models\SiteParameter;
use Illuminate\Http\Request;

class ActuatorController extends Controller
{
    /**
     * Toggle (or set) the desired state of a controllable switch parameter.
     * Called from the dashboard (Solar/Water/Irrigation tabs) via a small form/button.
     */
    public function toggle(Request $request, Site $site, SiteParameter $parameter)
    {
        // Sécurité : le paramètre doit appartenir à ce site et être controllable
        if ($parameter->category->site_id !== $site->id) {
            abort(404);
        }

        if (!$parameter->isControllable()) {
            return back()->with('error', "{$parameter->name} is not a controllable parameter.");
        }

        $request->validate([
            'state' => 'required|in:0,1',
        ]);

        ActuatorCommand::updateOrCreate(
            ['site_parameter_id' => $parameter->id],
            [
                'site_id'       => $site->id,
                'desired_state' => (int) $request->input('state'),
                'updated_by'    => auth()->id(),
            ]
        );

        $stateLabel = $request->input('state') === '1' ? 'ON' : 'OFF';

        // Rester sur l'onglet de la catégorie du paramètre concerné
        // (sinon la page revient sur le premier onglet, ex: Solar)
        return back()
            ->with('success', "{$parameter->name} set to {$stateLabel}. The device will apply this on its next check-in.")
            ->with('active_tab', $parameter->category->slug);
    }
}
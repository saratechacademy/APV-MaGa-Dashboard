<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class SiteController extends Controller
{
    // Liste les sites de l'utilisateur connecté
    public function index()
    {
        $sites = Auth::user()->isAdmin()
            ? Site::with('user')->latest()->get()
            : Auth::user()->sites()->latest()->get();

        return view('sites.index', compact('sites'));
    }

    // Formulaire de création
    public function create()
    {
        return view('sites.create');
    }

    // Enregistrer un nouveau site
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'country'     => 'required|string|max:100',
            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',
            'capacity_kw' => 'nullable|numeric|min:0',
            'area_m2'     => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        Site::create([
            'user_id'     => Auth::id(),
            'name'        => $request->name,
            'slug'        => Str::slug($request->name) . '-' . uniqid(),
            'country'     => $request->country,
            'latitude'    => $request->latitude,
            'longitude'   => $request->longitude,
            'capacity_kw' => $request->capacity_kw,
            'area_m2'     => $request->area_m2,
            'description' => $request->description,
            'status'      => 'active',
        ]);

        return redirect()->route('sites.index')
            ->with('success', 'Site créé avec succès !');
    }

    // Voir un site
    public function show(Site $site)
    {
        $this->authorizeSite($site);

        $site->load([
            'latestSolar',
            'latestWater',
            'latestWeather',
        ]);

        return view('sites.show', compact('site'));
    }

    // Formulaire d'édition
    public function edit(Site $site)
    {
        $this->authorizeSite($site);
        return view('sites.edit', compact('site'));
    }

    // Mettre à jour un site
    public function update(Request $request, Site $site)
    {
        $this->authorizeSite($site);

        $request->validate([
            'name'        => 'required|string|max:100',
            'country'     => 'required|string|max:100',
            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',
            'capacity_kw' => 'nullable|numeric|min:0',
            'area_m2'     => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'status'      => 'required|in:active,inactive,maintenance',
        ]);

        $site->update($request->only([
            'name', 'country', 'latitude', 'longitude',
            'capacity_kw', 'area_m2', 'description', 'status',
        ]));

        return redirect()->route('sites.show', $site)
            ->with('success', 'Site mis à jour avec succès !');
    }

    // Supprimer un site
    public function destroy(Site $site)
    {
        $this->authorizeSite($site);
        $site->delete();
        return redirect()->route('sites.index')
            ->with('success', 'Site supprimé.');
    }

    // Vérifier que l'utilisateur peut accéder à ce site
    private function authorizeSite(Site $site)
    {
        if (!Auth::user()->isAdmin() && $site->user_id !== Auth::id()) {
            abort(403, 'Accès non autorisé à ce site.');
        }
    }
}
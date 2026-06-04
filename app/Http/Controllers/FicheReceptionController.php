<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parcelle;
use App\Models\FicheReception;
use Inertia\Inertia;
use App\Models\Setting;
use App\Models\Enqueteur;

class FicheReceptionController extends Controller
{
    public function index()
    {
    return Inertia::render('fiche/Reception', [
        'parcelles'          => Parcelle::select('id', 'num')->orderBy('num')->get(),
        'fiches'             => FicheReception::with('lignes.parcelle')
                                    ->latest()
                                    ->paginate(20),
        'poids_par_caissette'=> (float) Setting::get('poids_par_caissette', 0),
        'enqueteurs' => Enqueteur::with('user')
            ->where('is_active', true)
            ->get()
            ->map(fn($item) => [
                'id'     => $item->id,
                'nom'    => $item->user->lastname,
                'prenom' => $item->user->firstname,
                'poste'  => $item->poste,
            ]),
    ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'agent_name'             => 'max:255',
            'fiche_number'           => 'nullable|string|max:255',
            'poids_par_caissette'    => 'nullable|numeric|min:0',
            'lignes'                 => 'required|array|min:1',
            'lignes.*.parcelle_id'   => 'nullable|exists:parcelles,id',
            'lignes.*.voiture'       => 'nullable|string|max:255',
            'lignes.*.commune'       => 'nullable|string|max:255',
            'lignes.*.caissette'     => 'nullable|integer|min:0',
            'lignes.*.collecte'      => 'nullable|date',
            'lignes.*.depart_champ'  => 'nullable|date',
            'lignes.*.retour_station'=> 'nullable|date',
        ]);

        // Persister le poids pour les prochaines fiches
        if ($data['poids_par_caissette'] !== null) {
            Setting::set('poids_par_caissette', $data['poids_par_caissette']);
        }

        $fiche = FicheReception::create([
            'agent_name'          => $data['agent_name'],
            'fiche_number'        => $data['fiche_number'],
            'poids_par_caissette' => $data['poids_par_caissette'],
        ]);

        foreach ($data['lignes'] as $ligne) {
            $fiche->lignes()->create($ligne);
        }

        return back()->with('success', 'Fiche enregistrée.');
    }

    public function update(Request $request, FicheReception $reception)
    {
        $data = $request->validate([
            'agent_name'             => 'nullable|max:255',
            'fiche_number'           => 'nullable|string|max:255',
            'poids_par_caissette'    => 'nullable|numeric|min:0',
            'lignes'                 => 'required|array|min:1',
            'lignes.*.parcelle_id'   => 'nullable|exists:parcelles,id',
            'lignes.*.voiture'       => 'nullable|string|max:255',
            'lignes.*.commune'       => 'nullable|string|max:255',
            'lignes.*.caissette'     => 'nullable|integer|min:0',
            'lignes.*.collecte'      => 'nullable|date',
            'lignes.*.depart_champ'  => 'nullable|date',
            'lignes.*.retour_station'=> 'nullable|date',
        ]);

        if ($data['poids_par_caissette'] !== null) {
            Setting::set('poids_par_caissette', $data['poids_par_caissette']);
        }

        $reception->update([
            'agent_name'          => $data['agent_name'],
            'fiche_number'        => $data['fiche_number'],
            'poids_par_caissette' => $data['poids_par_caissette'],
        ]);

        $reception->lignes()->delete();
        foreach ($data['lignes'] as $ligne) {
            $reception->lignes()->create($ligne);
        }

        return back()->with('success', 'Fiche mise à jour.');
    }

    public function destroy(FicheReception $reception)
    {
        $reception->lignes()->delete();
        $reception->delete();
        return back()->with('success', 'Fiche supprimée.');
    }
}

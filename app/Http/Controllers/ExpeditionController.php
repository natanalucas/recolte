<?php

namespace App\Http\Controllers;

namespace App\Http\Controllers;

use App\Models\Expedition;
use App\Models\Paletisation;
use App\Models\TypeCertification;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Enqueteur;

class ExpeditionController extends Controller
{
    public function index()
    {
        return Inertia::render('fiche/Expedition', [
            'enqueteurs' => Enqueteur::with('user')
            ->where('is_active', true)
            ->get()
            ->map(fn($item) => [
                'id'     => $item->id,
                'nom'    => $item->user->lastname,
                'prenom' => $item->user->firstname,
                'poste'  => $item->poste,
            ]),
            'expeditions' => Expedition::with('palettes.paletisation.typeCertification')->latest()->get(),
            'availablePalettes' => Paletisation::with('typeCertification')->get(),
            'certifications' => TypeCertification::select('id', 'nom')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'enqueteur_id'    => 'nullable|exists:enqueteurs,id',
            'fiche_number' => 'nullable|string|max:255',
            'conteneur' => 'nullable|string',
            'immatriculation' => 'nullable|string',
            'proprete_conteneur' => 'required|in:propre,sale',
            'proprete_camion' => 'required|in:propre,sale',
            'debut_empotage' => 'nullable|date',
            'fin_empotage' => 'nullable|date',
            'depart_station' => 'nullable|date',
            'arrivee_port' => 'nullable|date',
            'bateau' => 'nullable|string',
            'bon_livraison' => 'nullable|string',
            'observations' => 'nullable|string',
            'palettes' => 'required|array|min:1',
            'palettes.*.paletisation_id' => 'required|exists:paletisations,id',
        ]);

        $expedition = Expedition::create($validated);

        foreach ($validated['palettes'] as $palette) {
            $expedition->palettes()->create([
                'paletisation_id' => $palette['paletisation_id']
            ]);
        }

        return redirect()->back()->with('success', 'Fiche d\'expédition créée avec succès.');
    }

    public function update(Request $request, Expedition $expedition)
    {
        $validated = $request->validate([
            // ... (mêmes validations que store)
            'palettes' => 'required|array|min:1',
            'palettes.*.paletisation_id' => 'required|exists:paletisations,id',
        ]);

        $expedition->update($validated);
        
        // Synchronisation simple des palettes associées
        $expedition->palettes()->delete();
        foreach ($validated['palettes'] as $palette) {
            $expedition->palettes()->create([
                'paletisation_id' => $palette['paletisation_id']
            ]);
        }

        return redirect()->back()->with('success', 'Fiche d\'expédition mise à jour.');
    }

    public function destroy(Expedition $expedition)
    {
        $expedition->delete();
        return redirect()->back()->with('success', 'Fiche d\'expédition supprimée.');
    }
}

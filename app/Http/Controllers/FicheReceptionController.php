<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parcelle;
use App\Models\FicheReception;
use Inertia\Inertia;
use App\Models\Setting;
use App\Models\Enqueteur;
use App\Services\Pdf\FicheReceptionPdfService;

class FicheReceptionController extends Controller
{
    public function __construct(
        private FicheReceptionPdfService $ficheReceptionPdfService
    ) {}

    public function index()
    {
        return Inertia::render('fiche/Reception', [
            'parcelles'          => Parcelle::select('id', 'num')->orderBy('num')->get(),
            'fiches'             => FicheReception::with('parcelle', 'enqueteur')
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
            'enqueteur_id'           => 'nullable|exists:enqueteurs,id',
            'poids_par_caissette'    => 'nullable|numeric|min:0',
            'parcelle_id'            => 'nullable|exists:parcelles,id',
            'voiture'                => 'nullable|string|max:255',
            'commune'                => 'nullable|string|max:255',
            'caissette'              => 'nullable|integer|min:0',
            'pourcentage_dechet'     => 'nullable|numeric|min:0|max:100',
            'collecte'               => 'nullable|date',
            'depart_champ'           => 'nullable|date',
            'retour_station'         => 'nullable|date',
        ]);

        // Mémoriser le poids pour les prochaines fiches
        if ($data['poids_par_caissette'] !== null) {
            Setting::set('poids_par_caissette', $data['poids_par_caissette']);
        }

        $fiche = FicheReception::create($data);

        return back()->with('success', 'Fiche enregistrée.');
    }

    public function update(Request $request, FicheReception $reception)
    {
        $data = $request->validate([
            'enqueteur_id'           => 'nullable|exists:enqueteurs,id',
            'poids_par_caissette'    => 'nullable|numeric|min:0',
            'parcelle_id'            => 'nullable|exists:parcelles,id',
            'voiture'                => 'nullable|string|max:255',
            'commune'                => 'nullable|string|max:255',
            'caissette'              => 'nullable|integer|min:0',
            'pourcentage_dechet'     => 'nullable|numeric|min:0|max:100',
            'collecte'               => 'nullable|date',
            'depart_champ'           => 'nullable|date',
            'retour_station'         => 'nullable|date',
        ]);

        if ($data['poids_par_caissette'] !== null) {
            Setting::set('poids_par_caissette', $data['poids_par_caissette']);
        }

        $reception->update($data);

        return back()->with('success', 'Fiche mise à jour.');
    }

    public function destroy(FicheReception $reception)
    {
        $reception->delete();
        return back()->with('success', 'Fiche supprimée.');
    }

    public function exportPdf(FicheReception $ficheReception)
    {
        return $this->ficheReceptionPdfService->download($ficheReception);
    }
}
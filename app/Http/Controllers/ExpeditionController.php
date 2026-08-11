<?php

namespace App\Http\Controllers;

use App\Models\Expedition;
use App\Models\Paletisation;
use App\Models\TypeCertification;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Enqueteur;
use App\Models\ExpeditionPalette;
use App\Models\Societe;

class ExpeditionController extends Controller
{
    private function getUserSocieteId(): ?int
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return null;
        }

        if ($user->isManager()) {
            return Societe::where('manager_id', $user->id)->value('id');
        }

        return $user->societe_id;
    }

    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        $isManager = $user->isManager();
        $isEnqueteur = !$isAdmin && !$isManager;

        $societeId = $this->getUserSocieteId();

        // Enquêteurs (filtrés)
        $enqueteursQuery = Enqueteur::with('user')->where('is_active', true);
        if ($isManager) {
            $enqueteursQuery->whereHas('user', fn($q) => $q->where('societe_id', $societeId));
        }
        $enqueteurs = $enqueteursQuery->get()->map(fn($e) => [
            'id'     => $e->id,
            'nom'    => $e->user->lastname,
            'prenom' => $e->user->firstname,
            'poste'  => $e->poste,
        ]);

        // IDs des palettes déjà utilisées
        $usedPaletteIds = ExpeditionPalette::pluck('paletisation_id')->unique()->toArray();

        // Palettes disponibles (avec leurs lots et certifications)
        $availablePalettesQuery = Paletisation::with(['lots.certifications']);
        if (!$isAdmin) {
            $availablePalettesQuery->where('societe_id', $societeId);
        }
        $availablePalettes = $availablePalettesQuery->get();

        // Expéditions (avec leurs palettes, lots et certifications)
        $expeditionsQuery = Expedition::with(['palettes.paletisation.lots.certifications', 'enqueteur.user']);
        if (!$isAdmin) {
            $expeditionsQuery->whereHas('palettes.paletisation', fn($q) => $q->where('societe_id', $societeId));
        }
        $expeditions = $expeditionsQuery->latest()->get();

        // Certifications (pour légende)
        $certifications = TypeCertification::select('id', 'nom')->get();

        // Sociétés (pour admin)
        $societes = $isAdmin ? Societe::select('id', 'nom')->get() : [];

        // Enquêteur courant
        $currentEnqueteur = null;
        if ($isEnqueteur) {
            $currentEnqueteur = Enqueteur::where('user_id', $user->id)->first();
        }

        return Inertia::render('fiche/Expedition', [
            'enqueteurs'           => $enqueteurs,
            'expeditions'          => $expeditions,
            'availablePalettes'    => $availablePalettes,
            'certifications'       => $certifications,
            'usedPaletteIds'       => $usedPaletteIds,
            'societes'             => $societes,
            'isAdmin'              => $isAdmin,
            'isManager'            => $isManager,
            'currentEnqueteurId'   => $currentEnqueteur?->id,
            'currentEnqueteurLabel'=> $currentEnqueteur
                ? trim($currentEnqueteur->user->firstname . ' ' . $currentEnqueteur->user->lastname)
                : null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'enqueteur_id'    => 'nullable|exists:enqueteurs,id',
            'fiche_number'    => 'nullable|string|max:255',
            'conteneur'       => 'nullable|string',
            'immatriculation' => 'nullable|string',
            'proprete_conteneur' => 'required|in:propre,sale',
            'proprete_camion'    => 'required|in:propre,sale',
            'debut_empotage'     => 'nullable|date',
            'fin_empotage'       => 'nullable|date',
            'depart_station'     => 'nullable|date',
            'arrivee_port'       => 'nullable|date',
            'bateau'             => 'nullable|string',
            'bon_livraison'      => 'nullable|string',
            'observations'       => 'nullable|string',
            'palettes'           => 'required|array|min:1',
            'palettes.*.paletisation_id' => 'required|exists:paletisations,id',
        ]);

        // Vérification des droits sur les palettes (multi‑société)
        $this->assertPalettesAllowed($validated['palettes']);

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
            'enqueteur_id'         => 'nullable|exists:enqueteurs,id',
            'fiche_number'         => 'nullable|string|max:255',
            'conteneur'            => 'nullable|string',
            'immatriculation'      => 'nullable|string',
            'proprete_conteneur'   => 'required|in:propre,sale',
            'proprete_camion'      => 'required|in:propre,sale',
            'debut_empotage'       => 'nullable|date',
            'fin_empotage'         => 'nullable|date',
            'depart_station'       => 'nullable|date',
            'arrivee_port'         => 'nullable|date',
            'bateau'               => 'nullable|string',
            'bon_livraison'        => 'nullable|string',
            'observations'         => 'nullable|string',
            'palettes'             => 'required|array|min:1',
            'palettes.*.paletisation_id' => 'required|exists:paletisations,id',
        ]);

        $this->assertPalettesAllowed($validated['palettes']);

        $expedition->update($validated);

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

    // ─── Helper ──────────────────────────────────────────────

    private function assertPalettesAllowed(array $palettes): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;

        $societeId = $this->getUserSocieteId();
        if (!$societeId) {
            abort(403, "Vous n'êtes associé à aucune société.");
        }

        $paletteIds = array_column($palettes, 'paletisation_id');
        $allowed = Paletisation::whereIn('id', $paletteIds)
            ->where('societe_id', $societeId)
            ->count() === count($paletteIds);

        abort_unless($allowed, 403, "Certaines palettes sélectionnées n'appartiennent pas à votre société.");
    }
}
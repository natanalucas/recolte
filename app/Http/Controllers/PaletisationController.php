<?php

namespace App\Http\Controllers;

use App\Models\CodeTraca;
use App\Models\Paletisation;
use App\Models\Triage;
use App\Models\TypeCertification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Enqueteur;

class PaletisationController extends Controller
{
    /**
     * Affiche la fiche de palettisation (chargement initial via Inertia).
     */
    public function index(Request $request): Response
    {
        $ficheNumber = $request->query('fiche_number');

        $paletisations = Paletisation::with(['lots.codeTraca', 'typeCertification'])
            ->when($ficheNumber, fn ($query) => $query->where('fiche_number', $ficheNumber))
            ->orderBy('id')
            ->get();

        return Inertia::render('fiche/Paletisation', [
            'enqueteurs' => Enqueteur::with('user')
            ->where('is_active', true)
            ->get()
            ->map(fn($item) => [
                'id'     => $item->id,
                'nom'    => $item->user->lastname,
                'prenom' => $item->user->firstname,
                'poste'  => $item->poste,
            ]),
            'ficheNumber'        => $ficheNumber,
            'paletisations'      => $paletisations,
            'typeCertifications' => TypeCertification::select('id', 'nom')->get(),
            'souragesCodes'      => CodeTraca::select('id', 'code')->whereNotNull('code')->distinct()->get(),
            // Nb. de cartons déjà comptés au triage pour chaque code de traçabilité,
            // utilisé côté front pour pré-remplir le champ "Nb. Cartons" d'un lot.
            'triageCartons'      => Triage::select('code_traca_id')
                ->selectRaw('SUM(nombre) as nombre')
                ->whereNotNull('code_traca_id')
                ->groupBy('code_traca_id')
                ->get(),
        ]);
    }

    /**
     * Crée une nouvelle ligne de palette (avec ses 3 lots).
     * Appelé via Inertia router.post() depuis le bouton "Ajouter une palette".
     * Le redirect back() renvoie vers index() qui recharge les props à jour
     * (la nouvelle palette se trouve à la fin de `paletisations`).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request);

        DB::transaction(function () use ($validated, $request) {
            $paletisation = Paletisation::create([
                'enqueteur_id'          => $request['enqueteur_id'] ?? null,
                'fiche_number'          => $validated['fiche_number'] ?? null,
                'num_palette'           => $validated['num_palette'] ?? null,
                'type_carton'           => $validated['type_carton'] ?? null,
                'type_certification_id' => $validated['type_certification_id'] ?? null,
                'debut'                 => $validated['debut'] ?? null,
                'fin'                   => $validated['fin'] ?? null,
            ]);

            $this->syncLots($paletisation, $validated['lots'] ?? []);
        });

        return back();
    }

    /**
     * Met à jour une ligne de palette existante (en-tête + ses 3 lots).
     * Appelé via Inertia router.put(), débouncé côté front à chaque modification de champ.
     */
    public function update(Request $request, Paletisation $paletisation): RedirectResponse
    {
        $validated = $this->validatedData($request);

        DB::transaction(function () use ($validated, $paletisation) {
            $paletisation->update([
                'num_palette'           => $validated['num_palette'] ?? null,
                'type_carton'           => $validated['type_carton'] ?? null,
                'type_certification_id' => $validated['type_certification_id'] ?? null,
                'debut'                 => $validated['debut'] ?? null,
                'fin'                   => $validated['fin'] ?? null,
            ]);

            $this->syncLots($paletisation, $validated['lots'] ?? []);
        });

        return back();
    }

    /**
     * Supprime une ligne de palette (les 3 lots sont supprimés en cascade).
     * Appelé via Inertia router.delete().
     */
    public function destroy(Paletisation $paletisation): RedirectResponse
    {
        $paletisation->delete();

        return back();
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'enqueteur_id'          => 'nullable|exists:enqueteurs,id', 
            'fiche_number'          => ['nullable', 'string', 'max:50'],
            'num_palette'           => ['nullable', 'string', 'max:50'],
            'type_carton'           => ['nullable', 'in:2,5.5'],
            'type_certification_id' => ['nullable', 'exists:type_certifications,id'],
            'debut'                 => ['nullable', 'date'],
            'fin'                   => ['nullable', 'date', 'after_or_equal:debut'],
            'lots'                  => ['array', 'max:3'],
            'lots.*.code_traca_id'  => ['nullable', 'exists:code_traca,id'],
            'lots.*.nb_cartons'     => ['nullable', 'integer', 'min:0'],
        ]);
    }

    /**
     * Crée/mets à jour les 3 lots (lot 1, 2, 3) d'une palette en une seule passe.
     */
    private function syncLots(Paletisation $paletisation, array $lots): void
    {
        foreach ([1, 2, 3] as $lotNumber) {
            $lot = $lots[$lotNumber - 1] ?? null;

            $paletisation->lots()->updateOrCreate(
                ['lot_number' => $lotNumber],
                [
                    'code_traca_id' => $lot['code_traca_id'] ?? null,
                    'nb_cartons'    => $lot['nb_cartons'] ?? null,
                ]
            );
        }
    }
}
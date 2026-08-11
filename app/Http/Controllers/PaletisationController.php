<?php

namespace App\Http\Controllers;

use App\Models\CodeTraca;
use App\Models\Paletisation;
use App\Models\PaletisationLot;
use App\Models\Triage;
use App\Models\TypeCertification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Enqueteur;
use App\Models\Societe;

class PaletisationController extends Controller
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

    public function index(Request $request): Response
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        $isManager = $user->isManager();
        $isEnqueteur = !$isAdmin && !$isManager;

        $societeId = $this->getUserSocieteId();

        $ficheNumber = $request->query('fiche_number');

        // ── Enquêteurs ──────────────────────────────────────────
        $enqueteursQuery = Enqueteur::with('user')
            ->where('is_active', true);

        if ($isManager) {
            $enqueteursQuery->whereHas('user', fn($q) => $q->where('societe_id', $societeId));
        }

        $enqueteurs = $enqueteursQuery->get()->map(fn($e) => [
            'id'         => $e->id,
            'nom'        => $e->user->lastname,
            'prenom'     => $e->user->firstname,
            'poste'      => $e->poste,
            'societe_id' => $e->user->societe_id,
        ]);

        // ── Certifications ──────────────────────────────────────
        $certificationsQuery = TypeCertification::orderBy('nom');
        if (!$isAdmin) {
            if ($societeId) {
                $certificationsQuery->where('societe_id', $societeId);
            } else {
                $certificationsQuery->whereRaw('1=0');
            }
        }
        $typeCertifications = $certificationsQuery->get();

        // ── Codes de traçabilité ──────────────────────────────
        $codesQuery = CodeTraca::select('id', 'code', 'societe_id')
            ->whereNotNull('code')
            ->distinct();
        if (!$isAdmin) {
            $codesQuery->where('societe_id', $societeId);
        }
        $souragesCodes = $codesQuery->get();

        // ── Sociétés (pour admin) ──────────────────────────────
        $societes = $isAdmin ? Societe::select('id', 'nom')->get() : [];

        // ── Enquêteur courant (si enquêteur) ──────────────────
        $currentEnqueteur = null;
        if ($isEnqueteur) {
            $currentEnqueteur = Enqueteur::where('user_id', $user->id)->first();
        }

        // ── Paletisations (avec lots et leurs certifications) ──
        $paletisationsQuery = Paletisation::with([
            'lots.codeTraca',
            'lots.certifications'  // ← nouvelle relation
        ])
            ->when($ficheNumber, fn($q) => $q->where('fiche_number', $ficheNumber));

        if (!$isAdmin) {
            $paletisationsQuery->where('societe_id', $societeId);
        }

        $paletisations = $paletisationsQuery->orderBy('id')->get();

        // ── Cartons du triage ──────────────────────────────────
        $triageCartons = Triage::select('code_traca_id')
            ->selectRaw('SUM(nombre) as nombre')
            ->whereNotNull('code_traca_id')
            ->groupBy('code_traca_id')
            ->get();

        return Inertia::render('fiche/Paletisation', [
            'enqueteurs'           => $enqueteurs,
            'ficheNumber'          => $ficheNumber,
            'paletisations'        => $paletisations,
            'typeCertifications'   => $typeCertifications,
            'souragesCodes'        => $souragesCodes,
            'triageCartons'        => $triageCartons,
            'societes'             => $societes,
            'isAdmin'              => $isAdmin,
            'isManager'            => $isManager,
            'currentEnqueteurId'   => $currentEnqueteur?->id,
            'currentEnqueteurLabel'=> $currentEnqueteur
                ? trim($currentEnqueteur->user->firstname . ' ' . $currentEnqueteur->user->lastname)
                : null,
        ]);
    }

    /**
     * Détermine le nombre max de cartons par lot selon le poids.
     */
    private function getMaxCartonsPerLot(string $type_carton): int
    {
        return $type_carton === '2' ? 440 : 180; // 2kg → 440, 5.5kg → 180
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request);

        // Résoudre l'enquêteur
        $enqueteurId = $this->resolveEnqueteurId($validated['enqueteur_id'] ?? null);
        $validated['enqueteur_id'] = $enqueteurId;

        // Résoudre la société
        $explicitSocieteId = $validated['societe_id'] ?? null;
        $societeId = $this->resolveSocieteId($enqueteurId, $explicitSocieteId);
        $validated['societe_id'] = $societeId;

        // Vérifier les codes traça et certifications par lot
        $this->assertLotsAllowed($validated['lots'] ?? [], $societeId);
        foreach ($validated['lots'] as $lot) {
            $this->assertCertificationsAllowed($lot['certifications'] ?? [], $societeId);
        }

        // Répartition automatique si un lot dépasse le max
        $type_carton = $validated['type_carton'];
        $maxCartons = $this->getMaxCartonsPerLot($type_carton);
        $baseNumPalette = $validated['num_palette'];

        // On va construire une liste de palettes à créer
        $palettesToCreate = [];

        // Pour chaque lot reçu (3 max)
        foreach ($validated['lots'] as $index => $lotData) {
            $lotNumber = $index + 1; // 1,2,3
            $codeTracaId = $lotData['code_traca_id'];
            $nbCartons = $lotData['nb_cartons'] ?? 0;
            $certifications = $lotData['certifications'] ?? [];

            if ($nbCartons === 0 || !$codeTracaId) {
                continue; // lot vide
            }

            if ($nbCartons > $maxCartons) {
                // Répartir : première palette avec maxCartons, deuxième avec le reste
                $remaining = $nbCartons - $maxCartons;
                // Créer un lot pour la première palette
                $palettesToCreate[] = [
                    'num_palette' => $baseNumPalette,
                    'lots' => [
                        $lotNumber => [
                            'code_traca_id' => $codeTracaId,
                            'nb_cartons' => $maxCartons,
                            'certifications' => $certifications,
                        ]
                    ]
                ];
                // Créer une nouvelle palette pour le restant
                $palettesToCreate[] = [
                    'num_palette' => (string)($baseNumPalette + 1), // incrément
                    'lots' => [
                        $lotNumber => [
                            'code_traca_id' => $codeTracaId,
                            'nb_cartons' => $remaining,
                            'certifications' => $certifications,
                        ]
                    ]
                ];
            } else {
                // Pas de dépassement, on ajoute le lot à la palette de base
                // On cherche si une palette avec le numéro de base existe déjà dans la liste
                $found = false;
                foreach ($palettesToCreate as &$palette) {
                    if ($palette['num_palette'] === $baseNumPalette) {
                        $palette['lots'][$lotNumber] = [
                            'code_traca_id' => $codeTracaId,
                            'nb_cartons' => $nbCartons,
                            'certifications' => $certifications,
                        ];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $palettesToCreate[] = [
                        'num_palette' => $baseNumPalette,
                        'lots' => [
                            $lotNumber => [
                                'code_traca_id' => $codeTracaId,
                                'nb_cartons' => $nbCartons,
                                'certifications' => $certifications,
                            ]
                        ]
                    ];
                }
            }
        }

        // Maintenant, créer les palettes en base
        DB::transaction(function () use ($palettesToCreate, $validated) {
            foreach ($palettesToCreate as $paletteData) {
                $paletisation = Paletisation::create([
                    'enqueteur_id'  => $validated['enqueteur_id'],
                    'fiche_number'  => $validated['fiche_number'],
                    'num_palette'   => $paletteData['num_palette'],
                    'type_carton'   => $validated['type_carton'],
                    'debut'         => $validated['debut'],
                    'fin'           => $validated['fin'],
                    'societe_id'    => $validated['societe_id'],
                ]);

                foreach ($paletteData['lots'] as $lotNumber => $lot) {
                    $paletisationLot = $paletisation->lots()->create([
                        'lot_number'    => $lotNumber,
                        'code_traca_id' => $lot['code_traca_id'],
                        'nb_cartons'    => $lot['nb_cartons'],
                    ]);

                    // Associer les certifications au lot
                    if (!empty($lot['certifications'])) {
                        $paletisationLot->certifications()->sync($lot['certifications']);
                    }
                }
            }
        });

        return back();
    }

    public function update(Request $request, Paletisation $paletisation): RedirectResponse
    {
        // Mise à jour simplifiée : on ne gère pas la répartition automatique en édition,
        // on se contente de mettre à jour les lots existants.
        $validated = $this->validatedData($request);

        $explicitSocieteId = $validated['societe_id'] ?? null;
        $societeId = $this->resolveSocieteId($paletisation->enqueteur_id, $explicitSocieteId);
        $validated['societe_id'] = $societeId;

        $this->assertLotsAllowed($validated['lots'] ?? [], $societeId);
        foreach ($validated['lots'] as $lot) {
            $this->assertCertificationsAllowed($lot['certifications'] ?? [], $societeId);
        }

        DB::transaction(function () use ($validated, $paletisation) {
            $paletisation->update([
                'num_palette' => $validated['num_palette'],
                'type_carton' => $validated['type_carton'],
                'debut'       => $validated['debut'],
                'fin'         => $validated['fin'],
                'societe_id'  => $validated['societe_id'],
            ]);

            // Synchroniser les lots (3 lots max)
            foreach ([1, 2, 3] as $lotNumber) {
                $lotData = $validated['lots'][$lotNumber - 1] ?? null;
                if (empty($lotData) || is_null($lotData['code_traca_id']) || is_null($lotData['nb_cartons'])) {
                    // Supprimer le lot s'il est vide
                    $paletisation->lots()->where('lot_number', $lotNumber)->delete();
                    continue;
                }

                $paletisationLot = $paletisation->lots()->updateOrCreate(
                    ['lot_number' => $lotNumber],
                    [
                        'code_traca_id' => $lotData['code_traca_id'],
                        'nb_cartons'    => $lotData['nb_cartons'],
                    ]
                );

                // Synchroniser les certifications du lot
                if (isset($lotData['certifications'])) {
                    $paletisationLot->certifications()->sync($lotData['certifications']);
                } else {
                    $paletisationLot->certifications()->detach();
                }
            }
        });

        return back();
    }

    public function destroy(Paletisation $paletisation): RedirectResponse
    {
        $paletisation->delete();
        return back();
    }

    // ─── Validation ─────────────────────────────────────────────────────

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'enqueteur_id'          => 'nullable|exists:enqueteurs,id',
            'societe_id'            => 'nullable|exists:societes,id',
            'fiche_number'          => ['nullable', 'string', 'max:50'],
            'num_palette'           => ['required', 'numeric'],  // ← numérique
            'type_carton'           => ['required', 'in:2,5.5'],
            'debut'                 => ['nullable', 'date'],
            'fin'                   => ['nullable', 'date', 'after_or_equal:debut'],
            'lots'                  => ['array', 'max:3'],
            'lots.*.code_traca_id'  => ['nullable', 'exists:code_traca,id'],
            'lots.*.nb_cartons'     => ['nullable', 'integer', 'min:0'],
            'lots.*.certifications' => ['nullable', 'array'],
            'lots.*.certifications.*' => ['exists:type_certifications,id'],
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    private function resolveEnqueteurId(?int $submittedId): ?int
    {
        $user = auth()->user();

        if ($user->isAdmin() || $user->isManager()) {
            return $submittedId;
        }

        return Enqueteur::where('user_id', $user->id)->value('id');
    }

    private function resolveSocieteId(?int $enqueteurId, ?int $explicitSocieteId = null): int
    {
        $user = auth()->user();

        if ($user->isAdmin() && $explicitSocieteId) {
            return $explicitSocieteId;
        }

        if ($enqueteurId) {
            $enqueteur = Enqueteur::with('user')->find($enqueteurId);
            if ($enqueteur && $enqueteur->user && $enqueteur->user->societe_id) {
                return $enqueteur->user->societe_id;
            }
        }

        $societeId = $this->getUserSocieteId();
        if ($societeId) {
            return $societeId;
        }

        throw new \Exception("Impossible de déterminer la société pour cette palette.");
    }

    private function assertLotsAllowed(array $lots, int $societeId): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) {
            return;
        }

        foreach ($lots as $lot) {
            if (!empty($lot['code_traca_id'])) {
                $allowed = CodeTraca::whereKey($lot['code_traca_id'])
                    ->where('societe_id', $societeId)
                    ->exists();

                abort_unless($allowed, 403, "Un code de traçabilité utilisé n'appartient pas à votre société.");
            }
        }
    }

    private function assertCertificationsAllowed(array $certIds, int $societeId): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) {
            return;
        }

        foreach ($certIds as $certId) {
            $allowed = TypeCertification::whereKey($certId)
                ->where('societe_id', $societeId)
                ->exists();

            abort_unless($allowed, 403, "Une certification utilisée n'appartient pas à votre société.");
        }
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Soufrage;
use App\Models\Operateur;
use App\Models\RAQT;
use Illuminate\Http\Request;
use App\Http\Requests\SoufrageRequest;
use Inertia\Inertia;
use App\Models\Parcelle;
use App\Models\Enqueteur;
use App\Models\CodeTraca;
use App\Models\TypeCertification;
use App\Models\FicheReception;
use App\Models\Societe;
use Illuminate\Support\Facades\DB;

class SoufrageController extends Controller
{
    /**
     * Retourne l'ID de la société associée à l'utilisateur connecté.
     */
    private function getUserSocieteId(): ?int
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return null;
        }

        if ($user->isManager()) {
            return Societe::where('manager_id', $user->id)->value('id');
        }

        // Enquêteur
        return $user->societe_id;
    }

    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        $isManager = $user->isManager();
        $isEnqueteur = !$isAdmin && !$isManager;

        $societeId = $this->getUserSocieteId();

        // ── Parcelles ──────────────────────────────────────
        $parcellesQuery = Parcelle::select('id', 'num', 'producteur_id')
            ->with('producteur:id,nom,prenom,societe_id')
            ->orderBy('num');

        if (!$isAdmin) {
            $parcellesQuery->whereHas('producteur', fn($q) => $q->where('societe_id', $societeId));
        }
        $parcelles = $parcellesQuery->get();

        // ── Réceptions ─────────────────────────────────────
        $receptionsQuery = FicheReception::orderBy('id');
        if (!$isAdmin) {
            $receptionsQuery->where('societe_id', $societeId);
        }
        $receptions = $receptionsQuery->get(['id', 'fiche_number', 'caissette', 'parcelle_id'])
            ->map(fn($r) => [
                'id'              => $r->id,
                'fiche_number'    => $r->fiche_number,
                'caissette_total' => $r->caissette,
                'parcelle_id'     => $r->parcelle_id,
            ]);

        // ── Enquêteurs ─────────────────────────────────────
        $enqueteursQuery = Enqueteur::with('user')->where('is_active', true);
        if ($isManager) {
            $enqueteursQuery->whereHas('user', fn($q) => $q->where('societe_id', $societeId));
        }
        // Admin voit tous les enquêteurs
        $enqueteurs = $enqueteursQuery->get()->map(fn($e) => [
            'id'         => $e->id,
            'nom'        => $e->user->lastname,
            'prenom'     => $e->user->firstname,
            'poste'      => $e->poste,
            'societe_id' => $e->user->societe_id,
        ]);

        // ── Codes de traçabilité ────────────────────────────
        $souragesCodesQuery = CodeTraca::select('id', 'code', 'societe_id')
            ->whereNotNull('code')
            ->distinct();
        if (!$isAdmin) {
            $souragesCodesQuery->where('societe_id', $societeId);
        }
        $souragesCodes = $souragesCodesQuery->get();

        // ── Sociétés (pour admin) ─────────────────────────
        $societes = $isAdmin ? Societe::select('id', 'nom')->get() : [];

        // ── Enquêteur courant (si enquêteur) ──────────────
        $currentEnqueteur = null;
        if ($isEnqueteur) {
            $currentEnqueteur = Enqueteur::where('user_id', $user->id)->first();
        }

        // ── Soufrages ───────────────────────────────────────
        $soufragesQuery = Soufrage::with([
            'operateur',
            'raqt',
            'parcelle',
            'reception',
            'enqueteur.user',
            'codeTraca'
        ])->latest();

        if (!$isAdmin) {
            $soufragesQuery->where('societe_id', $societeId);
        }
        $soufrages = $soufragesQuery->paginate(20);

        // On ajoute 'code' à chaque élément pour faciliter l'affichage
        $soufrages->getCollection()->transform(function ($item) {
            $item->code = $item->codeTraca->code ?? '';
            return $item;
        });

        return Inertia::render('fiche/Soufrage', [
            'certifications'    => TypeCertification::orderBy('nom')->get(),
            'enqueteurs'        => $enqueteurs,
            'operateurs'        => Operateur::orderBy('nom')->get(['id', 'nom', 'prenom', 'travail']),
            'raqts'             => Raqt::orderBy('nom')->get(['id', 'nom', 'prenom']),
            'soufrages'         => $soufrages,
            'parcelles'         => $parcelles,
            'receptions'        => $receptions,
            'souragesCodes'     => $souragesCodes,
            'societes'          => $societes,
            'isAdmin'           => $isAdmin,
            'isManager'         => $isManager,
            'currentEnqueteurId' => $currentEnqueteur?->id,
            'currentEnqueteurLabel' => $currentEnqueteur
                ? trim($currentEnqueteur->user->firstname . ' ' . $currentEnqueteur->user->lastname)
                : null,
        ]);
    }

    public function store(SoufrageRequest $request)
    {
        $validatedData = $request->validated();

        // Résoudre l'enquêteur et la société
        $enqueteurId = $this->resolveEnqueteurId($validatedData['enqueteur_id'] ?? null);
        $validatedData['enqueteur_id'] = $enqueteurId;

        $explicitSocieteId = $validatedData['societe_id'] ?? null;
        $societeId = $this->resolveSocieteIdForFiche($enqueteurId, $explicitSocieteId);
        $validatedData['societe_id'] = $societeId;

        if (!empty($validatedData['parcelle_id'])) {
            $this->assertParcelleAllowed($validatedData['parcelle_id'], $societeId);
        }

        // Dans store()
        DB::transaction(function () use (&$validatedData, $societeId) {
            $code = $validatedData['code'] ?? null;
            $parcelleId = $validatedData['parcelle_id'] ?? null;

            if (empty($code)) {
                throw new \Exception("Le code de traçabilité est obligatoire.");
            }

            $codeTraca = CodeTraca::firstOrCreate(
                ['code' => $code],
                [
                    'parcelle_id' => $parcelleId,
                    'societe_id'  => $societeId, // <-- on stocke la société
                ]
            );

            // Mise à jour si parcelle ou société change
            $updateData = [];
            if ($parcelleId && $codeTraca->parcelle_id !== $parcelleId) {
                $updateData['parcelle_id'] = $parcelleId;
            }
            if ($societeId && $codeTraca->societe_id !== $societeId) {
                $updateData['societe_id'] = $societeId;
            }
            if (!empty($updateData)) {
                $codeTraca->update($updateData);
            }

            $validatedData['code_traca_id'] = $codeTraca->id;
            unset($validatedData['code']);

            Soufrage::create($validatedData);
        });

        return back()->with('success', 'Ligne enregistrée et associée au code de traçabilité.');
    }

    public function update(Request $request, Soufrage $soufrage)
    {
        $validatedData = $request->validate([
            'cycle'           => 'nullable|string|max:100',
            'box'             => 'nullable|numeric|max:50',
            'concent'         => 'nullable|string|max:100',
            'parcelle_id'     => 'nullable|exists:parcelles,id',
            'caissette'       => 'nullable|integer|min:0',
            'soufre'          => 'nullable|numeric|min:0',
            'debut'           => 'nullable|date',
            'fin'             => 'nullable|date',
            'operateur_id'    => 'nullable|exists:operateurs,id',
            'controle_raqt'   => 'nullable|boolean',
            'enqueteur_id'    => 'nullable|exists:enqueteurs,id',
            'reception_id'    => 'nullable|exists:fiche_receptions,id',
            'societe_id'      => 'nullable|exists:societes,id',
            'code'            => 'nullable|string|max:10|min:5',
        ]);

        $enqueteurId = $this->resolveEnqueteurId($validatedData['enqueteur_id'] ?? null);
        $validatedData['enqueteur_id'] = $enqueteurId;

        $explicitSocieteId = $validatedData['societe_id'] ?? null;
        $societeId = $this->resolveSocieteIdForFiche($enqueteurId, $explicitSocieteId);
        $validatedData['societe_id'] = $societeId;

        if (!empty($validatedData['parcelle_id'])) {
            $this->assertParcelleAllowed($validatedData['parcelle_id'], $societeId);
        }

        DB::transaction(function () use (&$validatedData, $soufrage, $societeId) {
            // Si un nouveau code est fourni, on le gère
            if (!empty($validatedData['code'])) {
                $code = $validatedData['code'];
                $parcelleId = $validatedData['parcelle_id'] ?? $soufrage->parcelle_id;

                $codeTraca = CodeTraca::firstOrCreate(
                    ['code' => $code],
                    [
                        'parcelle_id' => $parcelleId,
                        'societe_id'  => $societeId,
                    ]
                );

                $updateData = [];
                if ($parcelleId && $codeTraca->parcelle_id !== $parcelleId) {
                    $updateData['parcelle_id'] = $parcelleId;
                }
                if ($societeId && $codeTraca->societe_id !== $societeId) {
                    $updateData['societe_id'] = $societeId;
                }
                if (!empty($updateData)) {
                    $codeTraca->update($updateData);
                }

                $validatedData['code_traca_id'] = $codeTraca->id;
            }
            // Si on ne modifie pas le code, on ne touche pas à code_traca_id

            unset($validatedData['code']);

            $soufrage->update($validatedData);
        });

        return back()->with('success', 'Ligne mise à jour.');
    }

    public function destroy(Soufrage $soufrage)
    {
        $soufrage->delete();
        return back()->with('success', 'Ligne supprimée.');
    }

    // ─── Helpers ──────────────────────────────────────────

    private function resolveEnqueteurId(?int $submittedId): ?int
    {
        $user = auth()->user();

        if ($user->isAdmin() || $user->isManager()) {
            return $submittedId;
        }

        return Enqueteur::where('user_id', $user->id)->value('id');
    }

    private function resolveSocieteIdForFiche(?int $enqueteurId, ?int $explicitSocieteId = null): int
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

        throw new \Exception("Impossible de déterminer la société pour cette fiche.");
    }

    private function assertParcelleAllowed(int $parcelleId, int $societeId): void
    {
        $allowed = Parcelle::whereKey($parcelleId)
            ->whereHas('producteur', fn($q) => $q->where('societe_id', $societeId))
            ->exists();

        abort_unless($allowed, 403, "Cette parcelle n'appartient pas à la société sélectionnée.");
    }
}
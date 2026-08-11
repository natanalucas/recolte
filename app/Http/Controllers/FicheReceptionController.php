<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parcelle;
use App\Models\FicheReception;
use App\Models\Societe;
use Inertia\Inertia;
use App\Models\Setting;
use App\Models\Enqueteur;
use App\Models\User;
use App\Services\Pdf\FicheReceptionPdfService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class FicheReceptionController extends Controller
{
    public function __construct(
        private FicheReceptionPdfService $ficheReceptionPdfService
    ) {}

    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        $isManager = $user->isManager();
        $isEnqueteur = !$isAdmin && !$isManager;

        // Société de l'utilisateur (pour manager/enquêteur)
        $societeId = $this->getUserSocieteId();

        // ── Parcelles ──────────────────────────────────────
        $parcellesQuery = Parcelle::select('id', 'num', 'producteur_id')
            ->with('producteur:id,nom,prenom,societe_id')
            ->orderBy('num');

        if (!$isAdmin) {
            $parcellesQuery->whereHas('producteur', fn($q) => $q->where('societe_id', $societeId));
        }
        $parcelles = $parcellesQuery->get();

        // ── Enquêteurs ──────────────────────────────────────
        $enqueteursQuery = Enqueteur::with('user')
            ->where('is_active', true);

        if (!$isAdmin) {
            $enqueteursQuery->whereHas('user', fn($q) => $q->where('societe_id', $societeId));
        }
        $enqueteurs = $enqueteursQuery->get()->map(fn($e) => [
            'id'         => $e->id,
            'nom'        => $e->user->lastname,
            'prenom'     => $e->user->firstname,
            'poste'      => $e->poste,
            'societe_id' => $e->user->societe_id,
        ]);

        // ── Sociétés (pour l'admin) ────────────────────────
        $societes = $isAdmin ? Societe::select('id', 'nom')->get() : [];

        // ── Fiches ──────────────────────────────────────────
        $fichesQuery = FicheReception::with(['parcelle', 'enqueteur.user'])
            ->latest();

        if (!$isAdmin) {
            $fichesQuery->where('societe_id', $societeId);
        }
        $fiches = $fichesQuery->paginate(20);

        // ── Enquêteur courant (si enquêteur) ──────────────
        $currentEnqueteur = null;
        if ($isEnqueteur) {
            $currentEnqueteur = Enqueteur::where('user_id', $user->id)->first();
        }

        return Inertia::render('fiche/Reception', [
            'parcelles'               => $parcelles,
            'fiches'                  => $fiches,
            'poids_par_caissette'     => (float) Setting::get('poids_par_caissette', 0),
            'enqueteurs'              => $enqueteurs,
            'societes'                => $societes,
            'isAdmin'                 => $isAdmin,
            'isManager'               => $isManager,
            'currentEnqueteurId'      => $currentEnqueteur?->id,
            'currentEnqueteurLabel'   => $currentEnqueteur
                ? trim($currentEnqueteur->user->firstname . ' ' . $currentEnqueteur->user->lastname)
                : null,
            'userSocieteId' => $societeId, 
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        // Résoudre l'enquêteur
        $enqueteurId = $this->resolveEnqueteurId($data['enqueteur_id'] ?? null);
        $data['enqueteur_id'] = $enqueteurId;

        // Déterminer la société de la fiche
        // Pour l'admin, on peut recevoir un societe_id explicite
        $explicitSocieteId = $data['societe_id'] ?? null;
        $societeId = $this->resolveSocieteIdForFiche($enqueteurId, $explicitSocieteId);
        $data['societe_id'] = $societeId;

        // Vérifier la parcelle
        $this->assertParcelleAllowed($data['parcelle_id'] ?? null, $societeId);

        // Sauvegarder le poids global
        if ($data['poids_par_caissette'] !== null) {
            Setting::set('poids_par_caissette', $data['poids_par_caissette']);
        }

        FicheReception::create($data);

        return back()->with('success', 'Fiche enregistrée.');
    }

    public function update(Request $request, FicheReception $reception)
    {
        $data = $request->validate($this->rules());

        $enqueteurId = $this->resolveEnqueteurId($data['enqueteur_id'] ?? null);
        $data['enqueteur_id'] = $enqueteurId;

        $explicitSocieteId = $data['societe_id'] ?? null;
        $societeId = $this->resolveSocieteIdForFiche($enqueteurId, $explicitSocieteId);
        $data['societe_id'] = $societeId;

        $this->assertParcelleAllowed($data['parcelle_id'] ?? null, $societeId);

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

    /**
     * Règles de validation.
     */
    private function rules(): array
    {
        $user = auth()->user();
        $rules = [
            'poids_par_caissette'    => 'nullable|numeric|min:0',
            'parcelle_id'            => 'nullable|exists:parcelles,id',
            'voiture'                => 'nullable|string|max:255',
            'commune'                => 'nullable|string|max:255',
            'caissette'              => 'nullable|integer|min:0|max:250',
            'pourcentage_dechet'     => 'nullable|numeric|min:0|max:100',
            'collecte'               => 'nullable|date',
            'depart_champ'           => 'nullable|date',
            'retour_station'         => 'nullable|date',
            'calibre'                => 'nullable|string|max:255',
            'qualite_livraison'      => 'nullable|string|max:255',
        ];

        // Pour Admin et Manager, l'enquêteur est obligatoire
        if ($user->isAdmin() || $user->isManager()) {
            $rules['enqueteur_id'] = 'required|exists:enqueteurs,id';
        } else {
            $rules['enqueteur_id'] = 'nullable|exists:enqueteurs,id';
        }

        // Pour Admin uniquement, on autorise la sélection d'une société
        if ($user->isAdmin()) {
            $rules['societe_id'] = 'nullable|exists:societes,id';
        }

        return $rules;
    }

    /**
     * Résout l'ID de l'enquêteur.
     */
    private function resolveEnqueteurId(?int $submittedId): ?int
    {
        $user = auth()->user();

        if ($user->isAdmin() || $user->isManager()) {
            return $submittedId;
        }

        // Enquêteur : on force son propre ID
        return Enqueteur::where('user_id', $user->id)->value('id');
    }

    /**
     * Détermine le societe_id pour la fiche.
     * Si l'utilisateur est admin et a fourni un societe_id explicite, on l'utilise.
     * Sinon, on prend la société de l'enquêteur.
     * En dernier recours, pour manager ou enquêteur, on prend leur propre société.
     */
    // Dans resolveSocieteIdForFiche, on peut aussi utiliser getUserSocieteId en fallback
    private function resolveSocieteIdForFiche(?int $enqueteurId, ?int $explicitSocieteId = null): int
    {
        $user = auth()->user();

        if ($user->isAdmin() && $explicitSocieteId) {
            return $explicitSocieteId;
        }

        // 2. Enquêteur sélectionné -> sa société
        if ($enqueteurId) {
            $enqueteur = Enqueteur::with('user')->find($enqueteurId);
            if ($enqueteur && $enqueteur->user && $enqueteur->user->societe_id) {
                return $enqueteur->user->societe_id;
            }
        }

        // 3. Fallback : société de l'utilisateur connecté (manager ou enquêteur)
        $societeId = $this->getUserSocieteId();
        if ($societeId) {
            return $societeId;
        }

        // 4. Si on arrive ici, erreur
        throw new \Exception("Impossible de déterminer la société pour cette fiche.");
    }

    /**
     * Vérifie que la parcelle appartient bien à la société donnée.
     */
    private function assertParcelleAllowed(?int $parcelleId, int $societeId): void
    {
        if (!$parcelleId) {
            return;
        }

        $allowed = Parcelle::whereKey($parcelleId)
            ->whereHas('producteur', fn($q) => $q->where('societe_id', $societeId))
            ->exists();

        abort_unless($allowed, 403, "Cette parcelle n'appartient pas à la société sélectionnée.");
    }

    /**
     * Retourne l'ID de la société associée à l'utilisateur connecté.
     * - Admin : null
     * - Manager : via societes.manager_id
     * - Enquêteur : via users.societe_id
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

}
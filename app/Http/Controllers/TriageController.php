<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Triage;
use App\Models\TypeCertification;
use Inertia\Inertia;
use App\Models\Enqueteur;
use App\Models\CodeTraca;
use App\Models\Parcelle;
use App\Models\Societe;
use App\Models\FicheReception;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class TriageController extends Controller
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

    public function facture(Request $request, Triage $triage)
    {
        $validated = $request->validate([
            'prix_unitaire_caissette' => 'required|numeric|min:0',
            'prix_unitaire_produit'   => 'required|numeric|min:0',
            'numero_facture'          => 'required|string|max:100',
            'lieu'                    => 'required|string|max:150',
            'mode_paiement'           => 'required|in:Espèces,Mobile Money,Virement bancaire,Chèque',
            'observations'            => 'nullable|string|max:2000',
        ]);

        $triage->load('codeTraca.parcelle.producteur', 'codeTraca.societe', 'certifications');

        $codeTraca  = $triage->codeTraca;
        $parcelle   = $codeTraca?->parcelle;
        $producteur = $parcelle?->producteur;
        $societe    = $codeTraca?->societe;

        // ── Récupération de la fiche de réception liée ──
        $reception = null;
        if ($codeTraca?->reception_number) {
            $reception = FicheReception::where('fiche_number', $codeTraca->reception_number)->first();
        }

        $nbCaissettes   = (int) ($reception?->caissette ?? $triage->nombre ?? 0);
        $poidsUnitaire  = (float) ($reception?->poids_par_caissette ?? ($triage->type_carton === '2kg' ? 2 : 5.5));
        $kg             = $nbCaissettes * $poidsUnitaire;

        $totalCaissette = $validated['prix_unitaire_caissette'] * $nbCaissettes;
        $totalProduit   = $validated['prix_unitaire_produit']   * $kg;
        $total          = $totalCaissette + $totalProduit;

        $pdf = Pdf::loadView('pdf.facture', [
            'triage'                => $triage,
            'producteur'            => $producteur,
            'parcelle'              => $parcelle,
            'societe'               => $societe,
            'reception'             => $reception,
            'nbCaissettes'          => $nbCaissettes,
            'poidsUnitaire'         => $poidsUnitaire,
            'kg'                    => $kg,
            'prixUnitaireCaissette' => $validated['prix_unitaire_caissette'],
            'prixUnitaireProduit'   => $validated['prix_unitaire_produit'],
            'numeroFacture'         => $validated['numero_facture'],
            'lieu'                  => $validated['lieu'],
            'dateFacture'           => now(),
            'totalCaissette'        => $totalCaissette,
            'totalProduit'          => $totalProduit,
            'total'                 => $total,
            'modePaiement' => $validated['mode_paiement'],       // ← NOUVEAU
            'observations' => $validated['observations'] ?? null, // ← NOUVEAU
        ]);

        return $pdf->download("facture-litchi-{$triage->id}.pdf");
    }

    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        $isManager = $user->isManager();
        $isEnqueteur = !$isAdmin && !$isManager;

        $societeId = $this->getUserSocieteId();

        // ── Parcelles ──────────────────────────────────────────────
        $parcellesQuery = Parcelle::with('producteur:id,societe_id,nom,prenom')
            ->orderBy('num');
        if (!$isAdmin) {
            $parcellesQuery->whereHas('producteur', fn($q) => $q->where('societe_id', $societeId));
        }
        $parcelles = $parcellesQuery->get(['id', 'num', 'localisation', 'producteur_id']);

        // ── Enquêteurs ──────────────────────────────────────────────
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

        // ── Codes de traçabilité ──────────────────────────────────
        $codesQuery = CodeTraca::select('id', 'code', 'societe_id')
            ->whereNotNull('code')
            ->distinct();
        if (!$isAdmin) {
            $codesQuery->where('societe_id', $societeId);
        }
        $souragesCodes = $codesQuery->get();

        // ── Certifications ──────────────────────────────────────────
        $certificationsQuery = TypeCertification::orderBy('nom');
        if (!$isAdmin) {
            if ($societeId) {
                $certificationsQuery->where('societe_id', $societeId);
            } else {
                $certificationsQuery->whereRaw('1=0');
            }
        }
        $certifications = $certificationsQuery->get();

        // ── Sociétés (pour admin) ──────────────────────────────────
        $societes = $isAdmin ? Societe::select('id', 'nom')->get() : [];

        // ── Enquêteur courant ──────────────────────────────────────
        $currentEnqueteur = null;
        if ($isEnqueteur) {
            $currentEnqueteur = Enqueteur::where('user_id', $user->id)->first();
        }

        // ── Triages (avec la relation certifications) ──────────────
        $triagesQuery = Triage::with([
            'certifications',   // ← nouvelle relation
            'codeTraca.parcelle.producteur',
            'enqueteur.user'
        ])->latest();

        if (!$isAdmin) {
            $triagesQuery->where('societe_id', $societeId);
        }
        $triages = $triagesQuery->paginate(20);

        return Inertia::render('fiche/Triage', [
            'certifications'       => $certifications,
            'enqueteurs'           => $enqueteurs,
            'souragesCodes'        => $souragesCodes,
            'parcelles'            => $parcelles,
            'societes'             => $societes,
            'triages'              => $triages,
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
            'code_traca_id'              => 'required|exists:code_traca,id',
            'type_carton'                => 'required|in:2kg,5.5kg',
            'certifications'             => 'nullable|array',
            'certifications.*'           => 'exists:type_certifications,id',
            'debut'                      => 'nullable|date',
            'fin'                        => 'nullable|date',
            'tapis'                      => 'nullable|string',
            'nombre'                     => 'nullable|integer|min:0',
            'qualite'                    => 'required|integer|between:1,3',
            'enqueteur_id'               => 'nullable|exists:enqueteurs,id',
            'fiche_number'               => 'nullable|string',
            'societe_id'                 => 'nullable|exists:societes,id',
        ]);

        // Résoudre l'enquêteur
        $enqueteurId = $this->resolveEnqueteurId($validated['enqueteur_id'] ?? null);
        $validated['enqueteur_id'] = $enqueteurId;

        // Déterminer la société
        $explicitSocieteId = $validated['societe_id'] ?? null;
        $societeId = $this->resolveSocieteId($enqueteurId, $explicitSocieteId);
        $validated['societe_id'] = $societeId;

        // Vérifier le code traça
        $this->assertCodeTracaAllowed($validated['code_traca_id'], $societeId);

        // Vérifier que chaque certification (si présente) appartient à la société
        if (!empty($validated['certifications'])) {
            foreach ($validated['certifications'] as $certId) {
                $this->assertCertificationAllowed($certId, $societeId);
            }
        }

        $validated['tapis'] = json_decode($validated['tapis'] ?? '[]');

        DB::transaction(function () use ($validated) {
            $triage = Triage::create([
                'enqueteur_id'    => $validated['enqueteur_id'],
                'fiche_number'    => $validated['fiche_number'] ?? null,
                'code_traca_id'   => $validated['code_traca_id'],
                'type_carton'     => $validated['type_carton'],
                'debut'           => $validated['debut'],
                'fin'             => $validated['fin'],
                'tapis'           => $validated['tapis'],
                'nombre'          => $validated['nombre'],
                'qualite'         => $validated['qualite'],
                'societe_id'      => $validated['societe_id'],
            ]);

            // Synchronisation des certifications
            if (!empty($validated['certifications'])) {
                $triage->certifications()->sync($validated['certifications']);
            }
        });

        return back()->with('success', 'Ligne ajoutée.');
    }

    public function update(Request $request, Triage $triage)
    {
        $validated = $request->validate([
            'code_traca_id'              => 'required|exists:code_traca,id',
            'type_carton'                => 'required|in:2kg,5.5kg',
            'certifications'             => 'nullable|array',
            'certifications.*'           => 'exists:type_certifications,id',
            'debut'                      => 'nullable|date',
            'fin'                        => 'nullable|date',
            'tapis'                      => 'nullable|string',
            'nombre'                     => 'nullable|integer|min:0',
            'qualite'                    => 'required|integer|between:1,3',
            'societe_id'                 => 'nullable|exists:societes,id',
        ]);

        // Déterminer la société (si fournie explicitement pour admin)
        $explicitSocieteId = $validated['societe_id'] ?? null;
        $societeId = $this->resolveSocieteId($triage->enqueteur_id, $explicitSocieteId);
        $validated['societe_id'] = $societeId;

        $this->assertCodeTracaAllowed($validated['code_traca_id'], $societeId);
        if (!empty($validated['certifications'])) {
            foreach ($validated['certifications'] as $certId) {
                $this->assertCertificationAllowed($certId, $societeId);
            }
        }

        $validated['tapis'] = json_decode($validated['tapis'] ?? '[]');

        DB::transaction(function () use ($validated, $triage) {
            $triage->update([
                'code_traca_id' => $validated['code_traca_id'],
                'type_carton'   => $validated['type_carton'],
                'debut'         => $validated['debut'],
                'fin'           => $validated['fin'],
                'tapis'         => $validated['tapis'],
                'nombre'        => $validated['nombre'],
                'qualite'       => $validated['qualite'],
                'societe_id'    => $validated['societe_id'],
            ]);

            // Synchronisation des certifications
            if (isset($validated['certifications'])) {
                $triage->certifications()->sync($validated['certifications']);
            } else {
                $triage->certifications()->detach();
            }
        });

        return back()->with('success', 'Ligne mise à jour.');
    }

    public function destroy(Triage $triage)
    {
        // Les certifications seront supprimées automatiquement grâce au cascade on delete
        $triage->delete();
        return back()->with('success', 'Ligne supprimée.');
    }

    // ─── Helpers ──────────────────────────────────────────────────────

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

        throw new \Exception("Impossible de déterminer la société pour cette fiche.");
    }

    private function assertCodeTracaAllowed(int $codeTracaId, int $societeId): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) {
            return;
        }

        $allowed = CodeTraca::whereKey($codeTracaId)
            ->where('societe_id', $societeId)
            ->exists();

        abort_unless($allowed, 403, "Ce code de traçabilité n'appartient pas à votre société.");
    }

    private function assertCertificationAllowed(int $certificationId, int $societeId): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) {
            return;
        }

        $allowed = TypeCertification::whereKey($certificationId)
            ->where('societe_id', $societeId)
            ->exists();

        abort_unless($allowed, 403, "Cette certification n'appartient pas à votre société.");
    }
}
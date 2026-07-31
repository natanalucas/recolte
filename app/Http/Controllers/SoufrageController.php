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
use Illuminate\Support\Facades\DB;

class SoufrageController extends Controller
{
    public function index()
    {
        return Inertia::render('fiche/Soufrage', [
            'certifications' => TypeCertification::orderBy('nom')->get(),
            'enqueteurs' => Enqueteur::with('user')
                ->where('is_active', true)
                ->get()
                ->map(fn($item) => [
                    'id'     => $item->id,
                    'nom'    => $item->user->lastname,
                    'prenom' => $item->user->firstname,
                    'poste'  => $item->poste,
                ]),
            'operateurs' => Operateur::orderBy('nom')->get(['id', 'nom', 'prenom', 'travail']),
            'raqts'      => Raqt::orderBy('nom')->get(['id', 'nom', 'prenom']),
            // Note : Chargez aussi la relation 'parcelle' dans votre modèle Soufrage si nécessaire
            'soufrages' => Soufrage::with(['operateur', 'raqt', 'parcelle', 'reception']) 
                            ->whereYear('created_at', now()->year)
                            ->orderByDesc('created_at')
                            ->paginate(20),
            'parcelles'  => Parcelle::orderBy('num')->get(['id', 'num', 'localisation']),
            'receptions' => FicheReception::orderBy('id')
                ->get(['id', 'fiche_number', 'caissette', 'parcelle_id'])
                ->map(fn($r) => [
                    'id'              => $r->id,
                    'fiche_number'    => $r->fiche_number,
                    'caissette_total' => $r->caissette,       // champ direct
                    'parcelle_id'     => $r->parcelle_id,     // champ direct
                ]),
        ]);
    }

    public function store(SoufrageRequest $request)
    {
        $validatedData = $request->validated();

        DB::transaction(function () use (&$validatedData) { // Notez le & pour impacter $validatedData à l'extérieur
            $parcelleId = $validatedData['parcelle_id'] ?? '';
            $cycle = $validatedData['cycle'] ?? '';
            $codeTracaId = null;

            if ($parcelleId !== '' && $cycle !== '') {
                // On récupère le numéro de la parcelle pour générer le code combine
                $parcelleObj = Parcelle::find($parcelleId);
                $numParcelle = $parcelleObj ? $parcelleObj->num : '';

                if ($numParcelle !== '') {
                    $parcelleFormatee = str_pad((string)$numParcelle, 2, '0', STR_PAD_LEFT);
                    $deuxPremiersChiffresParcelle = substr($parcelleFormatee, 0, 2);
                    $codeCombine = $deuxPremiersChiffresParcelle . $cycle;

                    // Remplacez le bloc firstOrCreate par ceci :
                    $codeTraca = CodeTraca::updateOrCreate(
                        ['code' => $codeCombine],        // Condition de recherche
                        ['parcelle_id' => $parcelleId]   // Valeur à insérer ou mettre à jour
                    );
                    $codeTracaId = $codeTraca->id;
                }
            }

            if ($codeTracaId) {
                $validatedData['code_traca_id'] = $codeTracaId;
                Soufrage::create($validatedData);
            } else {
                throw new \Exception("Impossible de générer le code de traçabilité requis.");
            }
        });

        return back()->with('success', 'Ligne enregistrée et associée au code de traçabilité.');
    }

    public function update(Request $request, Soufrage $soufrage)
    {
        // Validation locale ou réutilisation d'une règle (adaptée pour le patch et le put)
        $validatedData = $request->validate([
            'cycle'           => 'nullable|string|max:100',
            'box'             => 'nullable|numeric|max:50',
            'concent'         => 'nullable|string|max:100',
            'parcelle_id'     => 'nullable|exists:parcelles,id', // Modifié
            'caissette'       => 'nullable|integer|min:0',
            'soufre'          => 'nullable|numeric|min:0',
            'debut'           => 'nullable|date',
            'fin'             => 'nullable|date',
            'operateur_id'    => 'nullable|exists:operateurs,id',
            'controle_raqt'   => 'nullable|boolean',
            'enqueteur_id'    => 'nullable|exists:enqueteurs,id', // Ajouté
            'reception_id'    => 'nullable|exists:fiche_receptions,id',
        ]);

        $parcelleId = $validatedData['parcelle_id'] ?? $soufrage->parcelle_id;
        $cycle = $validatedData['cycle'] ?? $soufrage->cycle;

        DB::transaction(function () use (&$validatedData, $soufrage, $parcelleId, $cycle) {
            $codeTracaId = null;

            if (!empty($parcelleId) && !empty($cycle)) {
                $parcelleObj = Parcelle::find($parcelleId);
                $numParcelle = $parcelleObj ? $parcelleObj->num : '';

                if ($numParcelle !== '') {
                    $parcelleFormatee = str_pad((string)$numParcelle, 2, '0', STR_PAD_LEFT);
                    $deuxPremiersChiffresParcelle = substr($parcelleFormatee, 0, 2);
                    $codeCombine = $deuxPremiersChiffresParcelle . $cycle;

                    // Remplacez le bloc firstOrCreate par ceci :
                    $codeTraca = CodeTraca::updateOrCreate(
                        ['code' => $codeCombine],        // Condition de recherche
                        ['parcelle_id' => $parcelleId]   // Valeur à insérer ou mettre à jour
                    );
                    $codeTracaId = $codeTraca->id;
                }
            }

            if ($codeTracaId) {
                $validatedData['code_traca_id'] = $codeTracaId;
            } else {
                throw new \Exception("Impossible de générer le code de traçabilité requis pour la mise à jour.");
            }

            $soufrage->update($validatedData);
        });

        return back()->with('success', 'Ligne mise à jour et code de traçabilité synchronisé.');
    }

    public function destroy(Soufrage $soufrage)
    {
        $soufrage->delete();
        return back()->with('success', 'Ligne supprimée.');
    }
}
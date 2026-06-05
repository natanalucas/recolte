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
            'soufrages' => Soufrage::with(['operateur', 'raqt'])
                            ->whereYear('created_at', now()->year)
                            ->orderByDesc('created_at')
                            ->paginate(20),
            'parcelles'  => Parcelle::orderBy('num')->get(['id', 'num', 'localisation']), // ← ajouter
        ]);
    }

    public function store(SoufrageRequest $request)
    {
        // On récupère les données validées
        $validatedData = $request->validated();

        DB::transaction(function () use ($validatedData) {
            
            $parcelle = $validatedData['parcelle'] ?? '';
            $cycle = $validatedData['cycle'] ?? '';

            // Initialisation de la variable qui contiendra l'ID du code traca
            $codeTracaId = null;

            if ($parcelle !== '' && $cycle !== '') {
                
                // Formatage de la parcelle sur 2 chiffres minimum
                $parcelleFormatee = str_pad((string)$parcelle, 2, '0', STR_PAD_LEFT);
                $deuxPremiersChiffresParcelle = substr($parcelleFormatee, 0, 2);

                // Combinaison (ex: "05" + "C3" => "05C3")
                $codeCombine = $deuxPremiersChiffresParcelle . $cycle;

                // 1. Insertion ou récupération du CodeTraca d'abord
                $codeTraca = CodeTraca::firstOrCreate([
                    'code' => $codeCombine
                ]);

                // On récupère l'ID généré ou existant
                $codeTracaId = $codeTraca->id;
            }

            // 2. Le Soufrage n'est créé QUE SI un code_traca_id a pu être correctement généré
            if ($codeTracaId) {
                
                // On ajoute ou remplace la clé par l'ID obtenu
                $validatedData['code_traca_id'] = $codeTracaId;

                // 3. Enregistrement final du Soufrage
                Soufrage::create($validatedData);
                
            } else {
                // Optionnel : Si pour une raison x ou y les données reçues n'ont pas permis 
                // de générer un code, on peut forcer l'annulation (Rollback) de la transaction
                throw new \Exception("Impossible de générer le code de traçabilité requis.");
            }
        });

        return back()->with('success', 'Ligne enregistrée et associée au code de traçabilité.');
    }

    // SoufrageController.php
    public function update(Request $request, Soufrage $soufrage)
    {
        // 1. Validation des données entrantes (note: l'UI envoie 'code' ou 'code_traca_id', 
        // mais nous allons nous baser sur parcelle et cycle pour le recalculer dynamiquement comme au store)
        $validatedData = $request->validate([
            'cycle'           => 'nullable|string|max:100',
            'box'             => 'nullable|string|max:50',
            'concent'         => 'nullable|string|max:100',
            'parcelle'        => 'nullable|string|max:50',
            'caissette'       => 'nullable|integer|min:0',
            'soufre'          => 'nullable|numeric|min:0',
            'debut'           => 'nullable|date',
            'fin'             => 'nullable|date',
            'operateur_id'    => 'nullable|exists:operateurs,id',
            'controle_raqt'   => 'nullable|boolean',
        ]);

        // Si la requête ne contient qu'une mise à jour partielle (ex: uniquement le toggle du contrôle RAQT)
        // on fusionne avec les valeurs existantes pour pouvoir recalculer ou préserver le code traçabilité
        $parcelle = $validatedData['parcelle'] ?? $soufrage->parcelle;
        $cycle = $validatedData['cycle'] ?? $soufrage->cycle;

        DB::transaction(function () use ($validatedData, $soufrage, $parcelle, $cycle) {
            
            $codeTracaId = null;

            if (!empty($parcelle) && !empty($cycle)) {
                // Formatage de la parcelle sur 2 chiffres minimum
                $parcelleFormatee = str_pad((string)$parcelle, 2, '0', STR_PAD_LEFT);
                $deuxPremiersChiffresParcelle = substr($parcelleFormatee, 0, 2);

                // Combinaison (ex: "05" + "003" => "05003")
                $codeCombine = $deuxPremiersChiffresParcelle . $cycle;

                // Insertion ou récupération du CodeTraca correspondant au nouveau calcul
                $codeTraca = CodeTraca::firstOrCreate([
                    'code' => $codeCombine
                ]);

                $codeTracaId = $codeTraca->id;
            }

            // Si on a réussi à obtenir un code_traca_id (ou si les valeurs actuelles le permettent)
            if ($codeTracaId) {
                $validatedData['code_traca_id'] = $codeTracaId;
            } else {
                // Si l'un des champs requis est devenu vide au point de ne plus pouvoir générer de code
                throw new \Exception("Impossible de générer le code de traçabilité requis pour la mise à jour.");
            }

            // Mise à jour finale de l'enregistrement Soufrage
            $soufrage->update($validatedData);
        });

        return back()->with('success', 'Ligne mise à jour et code de traçabilité synchronisé.');
    }

    // SoufrageController.php
    public function destroy(Soufrage $soufrage)
    {
        $soufrage->delete();
        return back()->with('success', 'Ligne supprimée.');
    }
}
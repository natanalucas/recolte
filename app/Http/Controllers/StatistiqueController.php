<?php

namespace App\Http\Controllers;

use App\Models\Producteur;
use App\Models\FicheReception;
use App\Models\Soufrage;
use App\Models\Triage;
use App\Models\PaletisationLot;
use App\Models\ExpeditionPalette;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StatistiqueController extends Controller
{
    private function formatDate($date): ?string
    {
        if (empty($date)) return null;
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d H:i');
        }
        try {
            return Carbon::parse($date)->format('Y-m-d H:i');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function index($slug = null)
    {
        // ---- Liste des producteurs (agrégats) ----
        $producteurs = Producteur::with(['parcelles.fichesReception']) // Relation à définir sur Parcelle
            ->get()
            ->map(function ($producteur) {
                $totalQuantite = 0;
                $dernierRetour = null;
                $qualites = [];

                foreach ($producteur->parcelles as $parcelle) {
                    // Utilisation de la relation fichesReception sur Parcelle
                    foreach ($parcelle->fichesReception as $fiche) {
                        $poids = $fiche->poids_par_caissette ?? 0;
                        $quantite = $fiche->caissette * $poids;
                        $totalQuantite += $quantite;

                        if ($fiche->retour_station && (!$dernierRetour || $fiche->retour_station > $dernierRetour)) {
                            $dernierRetour = $fiche->retour_station;
                        }

                        // Récupération qualité via soufrage
                        $soufrage = Soufrage::where('reception_id', $fiche->id)->first();
                        if ($soufrage && $soufrage->code_traca_id) {
                            $triage = Triage::where('code_traca_id', $soufrage->code_traca_id)->first();
                            if ($triage) {
                                $qualites[] = $triage->qualite;
                            }
                        }
                    }
                }

                return [
                    'id' => $producteur->id,
                    'nom' => $producteur->nom,
                    'prenom' => $producteur->prenom,
                    'total_quantite' => $totalQuantite,
                    'dernier_retour' => $this->formatDate($dernierRetour),
                    'qualite_moyenne' => count($qualites) ? round(array_sum($qualites) / count($qualites), 2) : null,
                ];
            });

        $totalGeneral = $producteurs->sum('total_quantite');

        // ---- Détails pour un producteur spécifique ----
        $producteurDetail = null;
        if ($slug) {
            $producteur = Producteur::with(['parcelles.fichesReception'])->find($slug);
            if ($producteur) {
                $parcours = [];

                // Récupérer toutes les fiches de réception pour les parcelles du producteur
                $fiches = FicheReception::whereIn('parcelle_id', $producteur->parcelles->pluck('id'))
                    ->with(['parcelle'])
                    ->orderBy('retour_station')
                    ->get();

                foreach ($fiches as $fiche) {
                    $poidsCaissette = $fiche->poids_par_caissette ?? 0;
                    $quantiteKg = $fiche->caissette * $poidsCaissette;

                    // 1. Soufrage
                    $soufrage = Soufrage::where('reception_id', $fiche->id)->first();
                    $codeTracaId = $soufrage ? $soufrage->code_traca_id : null;

                    // 2. Triages
                    $triages = [];
                    if ($codeTracaId) {
                        $triages = Triage::where('code_traca_id', $codeTracaId)->get()->map(function ($triage) {
                            return [
                                'tapis' => $triage->tapis,
                                'debut' => $this->formatDate($triage->debut),
                                'fin' => $this->formatDate($triage->fin),
                                'qualite' => $triage->qualite,
                                'nombre' => $triage->nombre,
                            ];
                        })->toArray();
                    }

                    // 3. Palettes avec leurs expéditions
                    $palettes = [];
                    if ($codeTracaId) {
                        $lotPalettes = PaletisationLot::where('code_traca_id', $codeTracaId)
                            ->with(['paletisation.typeCertification'])
                            ->get();
                        foreach ($lotPalettes as $lot) {
                            $pal = $lot->paletisation;
                            if ($pal) {
                                // Récupération de l'expédition associée
                                $expedition = null;
                                $expPal = ExpeditionPalette::where('paletisation_id', $pal->id)
                                    ->with('expedition')
                                    ->first();
                                if ($expPal && $expPal->expedition) {
                                    $e = $expPal->expedition;
                                    $expedition = [
                                        'immatriculation' => $e->immatriculation,
                                        'bateau' => $e->bateau,
                                        'debut_empotage' => $this->formatDate($e->debut_empotage),
                                        'fin_empotage' => $this->formatDate($e->fin_empotage),
                                        'depart_station' => $this->formatDate($e->depart_station),
                                        'arrivee_port' => $this->formatDate($e->arrivee_port),
                                    ];
                                }

                                $palettes[] = [
                                    'num_palette' => $pal->num_palette,
                                    'date_debut' => $this->formatDate($pal->debut),
                                    'date_fin' => $this->formatDate($pal->fin),
                                    'nb_cartons' => $lot->nb_cartons,
                                    'certification' => $pal->typeCertification ? $pal->typeCertification->nom : null,
                                    'expedition' => $expedition,
                                ];
                            }
                        }
                    }

                    $parcours[] = [
                        'date_livraison' => $this->formatDate($fiche->retour_station),
                        'quantite_kg' => $quantiteKg,
                        'parcelle' => $fiche->parcelle ? $fiche->parcelle->num : null,
                        'soufrage' => $soufrage ? [
                            'box' => $soufrage->box,
                            'debut' => $this->formatDate($soufrage->debut),
                            'fin' => $this->formatDate($soufrage->fin),
                            'concent' => $soufrage->concent,
                            'soufre' => $soufrage->soufre,
                            'code_traca' => $soufrage->codeTraca ? $soufrage->codeTraca->code : null,
                        ] : null,
                        'triages' => $triages,
                        'palettes' => $palettes,
                    ];
                }

                $totalProducteur = $fiches->sum(function ($fiche) {
                    return $fiche->caissette * ($fiche->poids_par_caissette ?? 0);
                });

                $producteurDetail = [
                    'id' => $producteur->id,
                    'nom' => $producteur->nom,
                    'prenom' => $producteur->prenom,
                    'parcours' => $parcours,
                    'totalProducteur' => $totalProducteur,
                ];
            }
        }

        return Inertia::render('statistiques/global', [
            'producteurs' => $producteurs,
            'totalGeneral' => $totalGeneral,
            'producteurDetail' => $producteurDetail,
        ]);
    }
}
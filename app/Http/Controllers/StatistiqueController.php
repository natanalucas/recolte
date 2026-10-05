<?php

namespace App\Http\Controllers;

use App\Models\CodeTraca;
use App\Models\FicheReception;
use App\Models\Paletisation;
use App\Models\Producteur;
use App\Models\Role;
use App\Models\Societe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StatistiqueController extends Controller
{
    private const QUALITE_MUR = 'mur';
    private const CALIBRE_MIN = 30;

    private const KG = 'COALESCE(fiche_receptions.caissette, 0) * COALESCE(fiche_receptions.poids_par_caissette, 0)';

    private function murSql(): string
    {
        return "LOWER(fiche_receptions.qualite_livraison) = '" . self::QUALITE_MUR . "'"
             . ' AND fiche_receptions.calibre >= ' . self::CALIBRE_MIN;
    }

    private function nonPaletiseSql(): string
    {
        return 'NOT EXISTS (SELECT 1 FROM code_traca c'
             . ' JOIN paletisation_lots l ON l.code_traca_id = c.id'
             . ' WHERE c.parcelle_id = fiche_receptions.parcelle_id'
             . ' AND c.reception_number = fiche_receptions.fiche_number)';
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $role = $user?->role?->slug;

        $societeModel = null;
        $societes     = null;
        $societeId    = null;

        if ($role === Role::ADMIN) {
            $societes  = $this->listeSocietes();
            $societeId = $request->query('societe') ? (int) $request->query('societe') : null;
        } elseif ($role === Role::ENQUETEUR) {
            $societeId = $user->societe_id;
        } elseif ($role === Role::MANAGER) {
            $societeId = Societe::where('manager_id', $user->id)->value('id');
        }

        if ($societeId) {
            $societeModel = Societe::find($societeId);
            if (!$societeModel) {
                $societeId = null;
            }
        }

        // Aucune société sélectionnée : vue "liste des sociétés" (admin)
        if (!$societeId || !$societeModel) {
            return Inertia::render('statistiques/global', [
                'isAdmin'          => $role === Role::ADMIN,
                'societes'         => $societes ?? [],
                'societe'          => null,
                'producteurs'      => [],
                'global'           => null,
                'producteurDetail' => null,
            ]);
        }

        $producteurId = $request->query('producteur') ? (int) $request->query('producteur') : null;

        // ---- Palettes par producteur (filtré société)
        $palettesParProducteur = DB::table('paletisation_lots as l')
            ->join('code_traca as c', 'c.id', '=', 'l.code_traca_id')
            ->join('parcelles as p', 'p.id', '=', 'c.parcelle_id')
            ->join('producteurs as pr', 'pr.id', '=', 'p.producteur_id')
            ->leftJoin('expedition_palettes as ep', 'ep.paletisation_id', '=', 'l.paletisation_id')
            ->where('pr.societe_id', $societeId)
            ->whereNull('pr.deleted_at')
            ->groupBy('p.producteur_id')
            ->selectRaw('p.producteur_id, COUNT(DISTINCT l.paletisation_id) as total, COUNT(DISTINCT ep.paletisation_id) as expediees')
            ->get()
            ->keyBy('producteur_id');

        // ---- Liste des producteurs de la société
        $fiches = FicheReception::query()
            ->where('fiche_receptions.societe_id', $societeId);

        $producteurs = (clone $fiches)
            ->join('parcelles', 'parcelles.id', '=', 'fiche_receptions.parcelle_id')
            ->join('producteurs', 'producteurs.id', '=', 'parcelles.producteur_id')
            ->whereNull('producteurs.deleted_at')
            ->groupBy('producteurs.id', 'producteurs.nom', 'producteurs.prenom')
            ->orderBy('producteurs.nom')
            ->selectRaw('producteurs.id, producteurs.nom, producteurs.prenom')
            ->selectRaw('SUM(' . self::KG . ') as total_kg')
            ->selectRaw('SUM(COALESCE(fiche_receptions.caissette, 0)) as nb_caissettes')
            ->selectRaw('SUM(CASE WHEN ' . $this->murSql() . ' THEN ' . self::KG . ' ELSE 0 END) as mur_kg')
            ->selectRaw('MAX(fiche_receptions.retour_station) as dernier_retour')
            ->selectRaw('COUNT(*) as nb_fiches')
            ->selectRaw('SUM(CASE WHEN ' . $this->nonPaletiseSql() . ' THEN 1 ELSE 0 END) as fiches_non_palettisees')
            ->get()
            ->map(function ($p) use ($palettesParProducteur) {
                $pal   = $palettesParProducteur[$p->id] ?? null;
                $total = (int) ($pal->total ?? 0);
                $exp   = (int) ($pal->expediees ?? 0);

                return [
                    'id'                     => $p->id,
                    'nom'                    => $p->nom,
                    'prenom'                 => $p->prenom,
                    'total_kg'               => (float) $p->total_kg,
                    'nb_caissettes'          => (int) $p->nb_caissettes,
                    'mur_kg'                 => (float) $p->mur_kg,
                    'dernier_retour'         => $p->dernier_retour,
                    'nb_fiches'              => (int) $p->nb_fiches,
                    'fiches_non_palettisees' => (int) $p->fiches_non_palettisees,
                    'palettes_expediees'     => $exp,
                    'palettes_non_expediees' => $total - $exp,
                ];
            });

        // ---- Indicateurs globaux
        $global = [
            'total_kg'               => round($producteurs->sum('total_kg'), 2),
            'mur_kg'                 => round($producteurs->sum('mur_kg'), 2),
            'nb_caissettes'          => $producteurs->sum('nb_caissettes'),
            'nb_producteurs'         => $producteurs->count(),
            'nb_fiches'              => $producteurs->sum('nb_fiches'),
            'fiches_non_palettisees' => $producteurs->sum('fiches_non_palettisees'),
            'palettes_expediees'     => Paletisation::query()
                ->where('societe_id', $societeId)
                ->whereHas('expeditionPalettes')
                ->count(),
            'palettes_non_expediees' => Paletisation::query()
                ->where('societe_id', $societeId)
                ->whereDoesntHave('expeditionPalettes')
                ->count(),
            'calibre_min'            => self::CALIBRE_MIN,
        ];

        return Inertia::render('statistiques/global', [
            'isAdmin'          => $role === Role::ADMIN,
            'societes'         => $societes ?? [],
            'societe'          => [
                'id'       => $societeModel->id,
                'nom'      => $societeModel->nom,
                'adresse'  => $societeModel->adresse,
                'phone'    => $societeModel->phone,
                'logo_url' => $societeModel->logo_url,
            ],
            'producteurs'      => $producteurs,
            'global'           => $global,
            'producteurDetail' => $producteurId ? $this->detail($producteurId, $societeId) : null,
        ]);
    }

    private function listeSocietes(): array
    {
        $societes = Societe::query()->orderBy('nom')->get();

        $counts = Producteur::query()
            ->whereIn('societe_id', $societes->pluck('id'))
            ->whereNull('deleted_at')
            ->groupBy('societe_id')
            ->selectRaw('societe_id, COUNT(*) as nb')
            ->pluck('nb', 'societe_id');

        $fiches = FicheReception::query()
            ->whereIn('societe_id', $societes->pluck('id'))
            ->groupBy('societe_id')
            ->selectRaw('societe_id, COUNT(*) as nb')
            ->pluck('nb', 'societe_id');

        return $societes->map(fn ($s) => [
            'id'             => $s->id,
            'nom'            => $s->nom,
            'adresse'        => $s->adresse,
            'phone'          => $s->phone,
            'logo_url'       => $s->logo_url,
            'nb_producteurs' => (int) ($counts[$s->id] ?? 0),
            'nb_fiches'      => (int) ($fiches[$s->id] ?? 0),
        ])->all();
    }

    private function detail(int $producteurId, $societeId): ?array
    {
        $producteur = Producteur::with([
            'parcelles.fichesReception' => fn ($q) => $q
                ->when($societeId, fn ($q) => $q->where('societe_id', $societeId))
                ->orderBy('retour_station'),
        ])
            ->where('societe_id', $societeId)
            ->find($producteurId);

        if (!$producteur) {
            return null;
        }

        $codes = CodeTraca::whereIn('parcelle_id', $producteur->parcelles->pluck('id'))
            ->with([
                'paletisationLots.certifications',
                'paletisationLots.paletisation.expeditionPalettes.expedition',
            ])
            ->get()
            ->groupBy(fn ($c) => $c->parcelle_id . '|' . $c->reception_number);

        $palettesTouchees  = collect();
        $palettesExpediees = collect();

        $parcelles = $producteur->parcelles->map(function ($parcelle) use ($codes, &$palettesTouchees, &$palettesExpediees) {
            $receptions = $parcelle->fichesReception->map(function ($f) use ($parcelle, $codes, &$palettesTouchees, &$palettesExpediees) {
                $kg  = (float) ($f->quantite_kg ?? 0);
                $mur = strtolower((string) $f->qualite_livraison) === self::QUALITE_MUR
                    && (float) $f->calibre >= self::CALIBRE_MIN;

                $lots = ($codes[$parcelle->id . '|' . $f->fiche_number] ?? collect())
                    ->flatMap->paletisationLots
                    ->sortBy('lot_number')
                    ->map(function ($lot) use (&$palettesTouchees, &$palettesExpediees) {
                        $pal = $lot->paletisation;
                        $exp = $pal?->expeditionPalettes->first()?->expedition;

                        if ($pal) {
                            $palettesTouchees->push($pal->id);
                            if ($exp) {
                                $palettesExpediees->push($pal->id);
                            }
                        }

                        return [
                            'num_palette'    => $pal?->num_palette,
                            'type_carton'    => $pal?->type_carton,
                            'lot_number'     => $lot->lot_number,
                            'nb_cartons'     => (int) $lot->nb_cartons,
                            'certifications' => $lot->certifications->pluck('nom')->values(),
                            'bateau'         => $exp?->bateau,
                            'expedie'        => (bool) $exp,
                        ];
                    })->values();

                return [
                    'id'              => $f->id,
                    'fiche_number'    => $f->fiche_number,
                    'retour_station'  => $f->retour_station,
                    'quantite_kg'     => $kg,
                    'caissettes'      => (int) $f->caissette,
                    'poids_caissette' => (float) $f->poids_par_caissette,
                    'qualite'         => $f->qualite_livraison,
                    'calibre'         => $f->calibre !== null ? (float) $f->calibre : null,
                    'mur_calibre'     => $mur,
                    'palettise'       => $lots->isNotEmpty(),
                    'lots'            => $lots,
                ];
            })->values();

            return [
                'id'                     => $parcelle->id,
                'num'                    => $parcelle->num,
                'total_kg'               => round($receptions->sum('quantite_kg'), 2),
                'mur_kg'                 => round($receptions->where('mur_calibre', true)->sum('quantite_kg'), 2),
                'nb_caissettes'          => $receptions->sum('caissettes'),
                'fiches_non_palettisees' => $receptions->where('palettise', false)->count(),
                'receptions'             => $receptions,
            ];
        })->filter(fn ($p) => count($p['receptions']) > 0)->values();

        return [
            'id'                     => $producteur->id,
            'nom'                    => $producteur->nom,
            'prenom'                 => $producteur->prenom,
            'total_kg'               => round($parcelles->sum('total_kg'), 2),
            'mur_kg'                 => round($parcelles->sum('mur_kg'), 2),
            'nb_caissettes'          => $parcelles->sum('nb_caissettes'),
            'palettes'               => $palettesTouchees->unique()->count(),
            'palettes_expediees'     => $palettesExpediees->unique()->count(),
            'palettes_non_expediees' => $palettesTouchees->unique()->count() - $palettesExpediees->unique()->count(),
            'fiches_non_palettisees' => $parcelles->sum('fiches_non_palettisees'),
            'nb_fiches'              => $parcelles->sum(fn ($p) => count($p['receptions'])),
            'parcelles'              => $parcelles,
        ];
    }
}
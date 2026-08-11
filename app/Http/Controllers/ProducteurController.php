<?php

namespace App\Http\Controllers;

use App\Models\Producteur;
use App\Models\User;
use App\Models\Poids;
use App\Models\Societe; // <- Ajout de l'import
use App\Http\Requests\ProducteurRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProducteurController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();

        // On prépare la requête
        $query = Producteur::with(['parcelles', 'controles', 'societe'])->latest();

        // Sécurité : Un manager ne voit QUE les producteurs de sa société
        if ($user->isManager()) {
            $query->where('societe_id', $user->societe_id);
        }

        $producteurs = $query->get()->map(fn($p) => $this->format($p));
        
        $kg = Poids::first()->kg ?? 150;

        // On charge les sociétés uniquement si c'est un Admin
        $societes = $isAdmin ? Societe::select('id', 'nom')->get() : [];

        return Inertia::render('producteurs/Liste', [
            'producteurs' => $producteurs,
            'kg' => $kg,
            'isAdmin' => $isAdmin,
            'societes' => $societes,
        ]);
    }

    public function store(ProducteurRequest $request): RedirectResponse
    {
        $user = auth()->user();
        
        // DÉTERMINATION DU SOCIETE_ID
        // Si c'est un Admin, on prend la valeur du formulaire. 
        // Sinon (Manager), on force l'ID de sa propre société.
        $societe_id = $user->isAdmin() ? $request->societe_id : $user->societe_id;

        DB::transaction(function () use ($request, $societe_id) {
            $producteur = Producteur::create([
                'nom'          => $request->nom,
                'prenom'       => $request->prenom,
                'phone'        => $request->telephone,
                'adresse'      => $request->adresse,
                'ggn'          => $request->ggn,
                'produit'      => $request->produit,
                'societe_id'   => $societe_id, // <- Ajout
            ]);

            foreach ($request->dates_controle ?? [] as $date) {
                $producteur->controles()->create(['date_controle' => $date]);
            }

            foreach ($request->parcelles ?? [] as $p) {
                $producteur->parcelles()->create([
                    'num'          => $p['num'],
                    'pieds'        => $p['pieds'],
                    'surface'      => $p['surface'],
                    'gps_lat'      => $p['gps_lat']      ?? null,
                    'gps_long'     => $p['gps_long']     ?? null,
                    'localisation' => $p['localisation'] ?? null,
                ]);
            }
        });

        return back()->with('success', 'Producteur enregistré avec succès.');
    }

    public function update(ProducteurRequest $request, Producteur $producteur): RedirectResponse
    {
        $user = auth()->user();
        $societe_id = $user->isAdmin() ? $request->societe_id : $user->societe_id;

        DB::transaction(function () use ($request, $producteur, $societe_id) {
            $producteur->update([
                'nom'          => $request->nom,
                'prenom'       => $request->prenom,
                'phone'        => $request->telephone,
                'adresse'      => $request->adresse,
                'ggn'          => $request->ggn,
                'produit'      => $request->produit,
                'societe_id'   => $societe_id, // <- Ajout
            ]);

            $producteur->controles()->delete();
            foreach ($request->dates_controle ?? [] as $date) {
                $producteur->controles()->create(['date_controle' => $date]);
            }

            $producteur->parcelles()->delete();
            foreach ($request->parcelles ?? [] as $p) {
                $producteur->parcelles()->create([
                    'num'          => $p['num'],
                    'pieds'        => $p['pieds'],
                    'surface'      => $p['surface'],
                    'gps_lat'      => $p['gps_lat']      ?? null,
                    'gps_long'     => $p['gps_long']     ?? null,
                    'localisation' => $p['localisation'] ?? null,
                ]);
            }
        });

        return back()->with('success', 'Producteur mis à jour.');
    }

    public function destroy(Producteur $producteur): RedirectResponse
    {
        DB::transaction(function () use ($producteur) {
            $producteur->parcelles()->delete();
            $producteur->controles()->delete();
            $producteur->delete();
        });

        return back()->with('success', 'Producteur supprimé.');
    }

    private function format(Producteur $p): array
    {
        return [
            'id'           => $p->id,
            'nom'          => $p->nom,
            'prenom'       => $p->prenom,
            'telephone'    => $p->phone,
            'adresse'      => $p->adresse,
            'ggn'          => $p->ggn,
            'produit'      => $p->produit,
            'societe_id'   => $p->societe_id, // <- Ajout
            'societe_nom'  => $p->societe ? $p->societe->nom : null, // <- Ajout pour l'affichage
            'datesControle' => $p->controles
                ->pluck('date_controle')
                ->map(fn($d) => $d->format('Y-m-d'))
                ->toArray(),
            'parcelles' => $p->parcelles->map(fn($parc) => [
                'num'          => $parc->num,
                'pieds'        => $parc->pieds,
                'surface'      => $parc->surface,
                'gps_lat'      => $parc->gps_lat,
                'gps_long'     => $parc->gps_long,
                'localisation' => $parc->localisation,
            ])->toArray(),
        ];
    }
}
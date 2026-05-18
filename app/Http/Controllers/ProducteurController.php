<?php

namespace App\Http\Controllers;

use App\Models\Producteur;
use App\Models\User;
use App\Http\Requests\ProducteurRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProducteurController extends Controller
{
    public function index(): Response
    {
        $producteurs = Producteur::with(['parcelles', 'controles'])
            ->latest()
            ->get()
            ->map(fn($p) => $this->format($p));

        return Inertia::render('producteurs/Liste', [
            'producteurs' => $producteurs,
        ]);
    }

    public function store(ProducteurRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $producteur = Producteur::create([
                'nom'          => $request->nom,
                'prenom'       => $request->prenom,
                'phone'        => $request->telephone,
                'adresse'      => $request->adresse,
                'ggn'          => $request->ggn,
                'produit'      => $request->produit,
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
        DB::transaction(function () use ($request, $producteur) {
            $producteur->update([
                'nom'          => $request->nom,
                'prenom'       => $request->prenom,
                'phone'        => $request->telephone,
                'adresse'      => $request->adresse,
                'ggn'          => $request->ggn,
                'produit'      => $request->produit,
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
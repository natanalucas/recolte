<?php

namespace App\Http\Controllers;

use App\Models\Operateur;
use App\Models\Societe;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OperateurController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();

        $query = Operateur::with('societe')->orderBy('nom');

        // Filtrage pour les managers
        if ($user->isManager()) {
            $query->where('societe_id', $user->societe_id);
        }

        $operateurs = $query->get();

        // Liste des sociétés uniquement si Admin
        $societes = $isAdmin ? Societe::select('id', 'nom')->get() : [];

        return Inertia::render('operateurs/Index', [
            'operateurs' => $operateurs,
            'isAdmin'    => $isAdmin,
            'societes'   => $societes,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();

        $rules = [
            'nom'     => 'required|string|max:100',
            'prenom'  => 'required|string|max:100',
            'travail' => 'required|in:jour,nuit',
        ];

        // Si admin, la sélection de la société est obligatoire dans la vue
        if ($isAdmin) {
            $rules['societe_id'] = 'required|exists:societes,id';
        }

        $validated = $request->validate($rules);

        // Détermination de la société : choisie par l'admin OU reliée automatiquement au manager
        $validated['societe_id'] = $isAdmin ? $request->societe_id : $user->societe_id;

        Operateur::create($validated);

        return back()->with('success', 'Opérateur ajouté avec succès.');
    }

    public function update(Request $request, Operateur $operateur)
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();

        $rules = [
            'nom'     => 'required|string|max:100',
            'prenom'  => 'required|string|max:100',
            'travail' => 'required|in:jour,nuit',
        ];

        if ($isAdmin) {
            $rules['societe_id'] = 'required|exists:societes,id';
        }

        $validated = $request->validate($rules);

        $validated['societe_id'] = $isAdmin ? $request->societe_id : $user->societe_id;

        $operateur->update($validated);

        return back()->with('success', 'Opérateur mis à jour.');
    }

    public function destroy(Operateur $operateur)
    {
        $operateur->delete();

        return back()->with('success', 'Opérateur supprimé.');
    }
}
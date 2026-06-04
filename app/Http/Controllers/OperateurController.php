<?php

namespace App\Http\Controllers;

use App\Models\Operateur;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OperateurController extends Controller
{
    public function index()
    {
        return Inertia::render('operateurs/Index', [
            'operateurs' => Operateur::orderBy('nom')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom'     => 'required|string|max:100',
            'prenom'  => 'required|string|max:100',
            'travail' => 'required|in:jour,nuit',
        ]);

        Operateur::create($request->only('nom', 'prenom', 'travail'));

        return back()->with('success', 'Opérateur ajouté avec succès.');
    }

    public function update(Request $request, Operateur $operateur)
    {
        $request->validate([
            'nom'     => 'required|string|max:100',
            'prenom'  => 'required|string|max:100',
            'travail' => 'required|in:jour,nuit',
        ]);

        $operateur->update($request->only('nom', 'prenom', 'travail'));

        return back()->with('success', 'Opérateur mis à jour.');
    }

    public function destroy(Operateur $operateur)
    {
        $operateur->delete();

        return back()->with('success', 'Opérateur supprimé.');
    }
}
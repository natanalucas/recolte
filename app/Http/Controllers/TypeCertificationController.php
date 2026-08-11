<?php

namespace App\Http\Controllers;

use App\Models\TypeCertification;
use App\Models\Societe; // N'oubliez pas l'import
use Illuminate\Http\Request;
use Inertia\Inertia;

class TypeCertificationController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();

        // Requête de base avec la relation
        $query = TypeCertification::with('societe')->latest();

        // Filtrage pour les managers
        if ($user->isManager()) {
            $query->where('societe_id', $user->societe_id);
        }

        // Formatage des données pour Vue
        $certifications = $query->get()->map(fn($c) => [
            'id' => $c->id,
            'nom' => $c->nom,
            'societe_id' => $c->societe_id,
            'societe_nom' => $c->societe ? $c->societe->nom : null,
        ]);

        // Chargement des sociétés si Admin
        $societes = $isAdmin ? Societe::select('id', 'nom')->get() : [];

        return Inertia::render('typeCertification/Index', [
            'certifications' => $certifications,
            'isAdmin' => $isAdmin,
            'societes' => $societes,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            // 'societe_id' n'est requis que si c'est un admin qui le soumet, mais on gère ça ci-dessous
        ]);

        $user = auth()->user();
        
        // Détermination sécurisée de la société
        $societe_id = $user->isAdmin() ? $request->societe_id : $user->societe_id;

        TypeCertification::create([
            'nom' => $request->nom,
            'societe_id' => $societe_id,
        ]);

        return back()->with('success', 'Type de certification créé avec succès.');
    }

    public function update(Request $request, TypeCertification $typeCertification)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
        ]);

        $user = auth()->user();
        $societe_id = $user->isAdmin() ? $request->societe_id : $user->societe_id;

        $typeCertification->update([
            'nom' => $request->nom,
            'societe_id' => $societe_id,
        ]);

        return back()->with('success', 'Type de certification mis à jour.');
    }

    public function destroy(TypeCertification $typeCertification)
    {
        $typeCertification->delete();
        return back()->with('success', 'Type de certification supprimé.');
    }
}
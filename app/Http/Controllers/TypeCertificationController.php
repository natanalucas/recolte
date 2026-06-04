<?php

namespace App\Http\Controllers;

use App\Models\TypeCertification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TypeCertificationController extends Controller
{
    public function index()
    {
        return Inertia::render('typeCertification/Index', [
            'certifications' => TypeCertification::orderBy('nom')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255|unique:type_certifications,nom',
        ], [
            'nom.unique' => 'Ce type de certification existe déjà.',
        ]);

        TypeCertification::create($request->only('nom'));

        return back()->with('success', 'Type de certification ajouté.');
    }

    public function update(Request $request, TypeCertification $typeCertification)
    {
        $request->validate([
            'nom' => 'required|string|max:255|unique:type_certifications,nom,' . $typeCertification->id,
        ], [
            'nom.unique' => 'Ce type de certification existe déjà.',
        ]);

        $typeCertification->update($request->only('nom'));

        return back()->with('success', 'Type de certification mis à jour.');
    }

    public function destroy(TypeCertification $typeCertification)
    {
        $typeCertification->delete();

        return back()->with('success', 'Type de certification supprimé.');
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Triage;
use App\Models\TypeCertification;
use Inertia\Inertia;
use App\Models\Enqueteur;
use App\Models\CodeTraca;

class TriageController extends Controller
{
    public function index()
    {
        return Inertia::render('fiche/Triage', [
            'enqueteurs' => Enqueteur::with('user')
            ->where('is_active', true)
            ->get()
            ->map(fn($item) => [
                'id'     => $item->id,
                'nom'    => $item->user->lastname,
                'prenom' => $item->user->firstname,
                'poste'  => $item->poste,
            ]),
            'certifications' => TypeCertification::orderBy('nom')->get(),
            'triages' => Triage::with([
                'certification', 
                'codeTraca.parcelle.producteur' // <-- Charge l'arbre de relations complet
            ])->latest()->get(),
            'souragesCodes'  => CodeTraca::select('id', 'code')->whereNotNull('code')->distinct()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'code_traca_id'          => 'required|max:100',
            'type_carton'            => 'required|in:2kg,5.5kg',
            'type_certification_id'  => 'nullable|exists:type_certifications,id',
            'debut'                  => 'nullable|date',
            'fin'                    => 'nullable|date',
            'tapis'                  => 'nullable|string',
            'nombre'                 => 'nullable|integer|min:0',
            'qualite'                => 'required|integer|between:1,3',
            // Remplacement de agent_name par enqueteur_id avec la validation adéquate
            'enqueteur_id'           => 'nullable|exists:enqueteurs,id', 
            'fiche_number'           => 'nullable|string',
        ]);
        
        $v['tapis'] = json_decode($v['tapis'] ?? '[]');
        
        Triage::create($v);
        
        return back()->with('success', 'Ligne ajoutée.');
    }

    public function update(Request $request, Triage $triage)
    {
        $v = $request->validate([
            'code_traca_id'                   => 'required|string|max:100',
            'type_carton'            => 'required|in:2kg,5.5kg',
            'type_certification_id'  => 'nullable|exists:type_certifications,id',
            'debut'                  => 'nullable|date',
            'fin'                    => 'nullable|date',
            'tapis'                  => 'nullable|string',
            'nombre'                 => 'nullable|integer|min:0',
            'qualite'                => 'required|integer|between:1,3',
        ]);
        $v['tapis'] = json_decode($v['tapis'] ?? '[]');
        $triage->update($v);
        return back()->with('success', 'Ligne mise à jour.');
    }

    public function destroy(Triage $triage)
    {
        $triage->delete();
        return back()->with('success', 'Ligne supprimée.');
    }
}
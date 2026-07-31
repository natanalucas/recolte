<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SoufrageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fiche_number'    => 'nullable|string|max:100',
            'raqt_id'         => 'nullable|exists:raqt,id',
            'lieu_traitement' => 'nullable|string|max:255',
            'cycle'           => 'nullable|string|max:100',
            'box'             => 'nullable|numeric|max:50', // Passé à nullable si jamais il n'est pas requis
            'concent'         => 'nullable|max:100',
            'parcelle_id'     => 'nullable|exists:parcelles,id', // Modifié
            'code_traca_id'   => 'nullable|string|max:10',
            'caissette'       => 'nullable|integer|min:0',
            'soufre'          => 'nullable|numeric|min:0',
            'debut'           => 'nullable|date',
            'fin'             => 'nullable|date|after_or_equal:debut',
            'operateur_id'    => 'nullable|exists:operateurs,id',
            'controle_raqt'   => 'boolean',
            'enqueteur_id'    => 'nullable|exists:enqueteurs,id', // Modifié
            'reception_id'    => 'nullable|exists:fiche_receptions,id'
        ];
    }

    public function messages(): array
    {
        return [
            'box.numeric'          => 'Le numéro de box doit être un nombre.',
            'box.max'              => 'Le box ne peut pas dépasser 50.',
            'parcelle_id.exists'   => 'La parcelle sélectionnée est invalide.', // Modifié
            'fin.date'             => 'La date de fin n\'est pas valide.',
            'fin.after_or_equal'   => 'La date de fin doit être supérieure ou égale à la date de début.',
            'caissette.integer'    => 'La quantité de caisettes doit être un entier.',
            'caissette.min'        => 'La quantité de caisettes ne peut pas être négative.',
            'soufre.numeric'       => 'La quantité de soufre doit être un nombre.',
            'soufre.min'           => 'La quantité de soufre ne peut pas être négative.',
            'operateur_id.exists'  => 'L\'opérateur sélectionné est invalide.',
            'raqt_id.exists'       => 'Le RAQT sélectionné est invalide.',
            'enqueteur_id.exists'  => 'L\'enquêteur sélectionné est invalide.', // Ajouté
        ];
    }
}
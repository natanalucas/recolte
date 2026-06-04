<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SoufrageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ne pas oublier de le passer à true
    }

    public function rules(): array
    {
        return [
            'fiche_number'    => 'nullable|string|max:100',
            'raqt_id'         => 'nullable|exists:raqt,id',
            'lieu_traitement' => 'nullable|string|max:255',
            'cycle'           => 'nullable|string|max:100',
            'box'             => 'numeric|max:50',
            'concent'         => 'nullable|string|max:100',
            'parcelle'        => 'numeric|max:50',
            'code_traca_id'   => 'nullable|string|max:10',
            'caissette'       => 'nullable|integer|min:0',
            'soufre'          => 'nullable|numeric|min:0',
            'debut'           => 'nullable|date',
            'fin'             => 'nullable|date|after_or_equal:debut',
            'operateur_id'    => 'nullable|exists:operateurs,id',
            'controle_raqt'   => 'boolean',
            'agent_name' => 'nullable|exists:users,id',
        ];
    }

    /**
     * Personnalisation des messages d'erreur en français
     */
    public function messages(): array
    {
        return [
            'box.numeric'          => 'Le numéro de box doit être un nombre.',
            'box.max'              => 'Le box ne peut pas dépasser 50.',
            'parcelle.numeric'     => 'La parcelle doit être un nombre.',
            'parcelle.max'         => 'La parcelle ne peut pas dépasser 50.',
            'code.numeric'         => 'Le code de traçabilité doit être un nombre.',
            'code.max'             => 'Le code ne peut pas dépasser 100.',
            'fin.date'             => 'La date de fin n\'est pas valide.',
            'fin.after_or_equal'   => 'La date de fin doit être supérieure ou égale à la date de début.',
            'caissette.integer'    => 'La quantité de caisettes doit être un entier.',
            'caissette.min'        => 'La quantité de caisettes ne peut pas être négative.',
            'soufre.numeric'       => 'La quantité de soufre doit être un nombre.',
            'soufre.min'           => 'La quantité de soufre ne peut pas être négative.',
            'operateur_id.exists'  => 'L\'opérateur sélectionné est invalide.',
            'raqt_id.exists'       => 'Le RAQT sélectionné est invalide.',
        ];
    }
}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProducteurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('producteur')?->user_id;

        return [
            // --- User ---
            'nom'       => ['required', 'string', 'max:100'],
            'prenom'    => ['required', 'string', 'max:100'],
            'phone'     => ['nullable', 'string', 'max:20'],

            // --- Producteur ---
            'ggn'       => [
                'nullable', 'string', 'max:50',
                Rule::unique('producteurs', 'ggn')
                    ->ignore($this->route('producteur')),
            ],
            'produit'   => ['required', 'string', 'max:100'],

            // --- Contrôles ---
            'dates_controle'   => ['nullable', 'array'],
            'dates_controle.*' => ['date'],

            // --- Parcelles ---
            'parcelles'                       => ['required', 'array', 'min:1'],
            'parcelles.*.num'                 => ['required', 'integer', 'min:1'],
            'parcelles.*.pieds'               => ['required', 'integer', 'min:0'],
            'parcelles.*.surface'             => ['required', 'numeric', 'min:0'],
            'parcelles.*.gps_lat'             => ['nullable', 'numeric', 'between:-90,90'],
            'parcelles.*.gps_long'            => ['nullable', 'numeric', 'between:-180,180'],
            'parcelles.*.localisation'    => ['nullable', 'string', 'max:150'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nom'                         => 'nom',
            'prenom'                      => 'prénom',
            'phone'                       => 'téléphone',
            'ggn'                         => 'GGN',
            'produit'                     => 'produit',
            'dates_controle'              => 'dates de contrôle',
            'dates_controle.*'            => 'date de contrôle',
            'parcelles'                   => 'parcelles',
            'parcelles.*.num'             => 'numéro de parcelle',
            'parcelles.*.pieds'           => 'nombre de pieds',
            'parcelles.*.surface'         => 'surface',
            'parcelles.*.gps_lat'         => 'latitude GPS',
            'parcelles.*.gps_long'        => 'longitude GPS',
            'parcelles.*.localisation'    => 'localisation',
        ];
    }
}
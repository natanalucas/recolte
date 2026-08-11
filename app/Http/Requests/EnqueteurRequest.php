<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class EnqueteurRequest extends FormRequest
{
    public function authorize(): bool
    {
        // On utilise la méthode de votre Trait corrigé
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules()
    {
        return [
            'nom'       => 'required|string|max:100',
            'prenom'    => 'required|string|max:100',
            'email'     => 'required|email|unique:users,email,' . ($this->enqueteur?->user_id ?? 'NULL'),
            'password'  => 'nullable|string|min:8',
            'poste'     => 'required|string|max:100',
            'travail'   => 'required|in:jour,nuit',
            'societe_id' => 'required_if:admin,true|exists:societes,id', // si admin, obligatoire
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'   => 'Cet email est déjà utilisé par un utilisateur.',
            'password.min'    => 'Le mot de passe doit faire au moins 6 caractères.',
            'nom.required'    => 'Le nom est obligatoire.',
            'poste.required'  => 'Le champ poste (job) est obligatoire.',
        ];
    }
}
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

    public function rules(): array
    {
        // On récupère l'ID de l'utilisateur lié à l'enquêteur en cours de modification
        // Si on est en création (store), cet ID sera null.
        $userId = $this->route('enqueteur') ? $this->route('enqueteur')->user_id : null;

        return [
            'nom'      => ['required', 'string', 'max:100'],
            'prenom'   => ['required', 'string', 'max:100'],
            'email'    => [
                'required', 
                'email', 
                // La magie est ici : unique:table,colonne,id_a_ignorer
                'unique:users,email,' . $userId 
            ],
            'password' => $this->isMethod('post') ? ['required', 'min:4'] : ['nullable', 'min:4'],
            'poste'    => ['required', 'string'],
            'travail'  => ['required', 'string'],
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
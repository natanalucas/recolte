<?php
// app/Http/Controllers/EnqueteurController.php

namespace App\Http\Controllers;

use App\Models\Enqueteur;
use App\Models\User;
use App\Models\Role;
use App\Http\Requests\EnqueteurRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EnqueteurController extends Controller
{
    public function index(): Response
    {
        $enqueteurs = Enqueteur::with('user') // On charge la relation user
            ->where('is_active', true) // Remplace "actif()" si c'est un scope
            ->get()
            ->map(function ($item) {
                return [
                    'id'       => $item->id,
                    'user_id'  => $item->user_id,
                    // On récupère les infos dans la table users
                    'nom'      => $item->user->lastname, 
                    'prenom'   => $item->user->firstname,
                    'email'    => $item->user->email,
                    // On récupère les infos dans la table interviewers
                    'poste'    => $item->poste,
                    'travail'  => $item->travail,
                ];
            });

        return Inertia::render('enqueteurs/Liste', [
            'enqueteurs' => $enqueteurs
        ]);
    }

    public function store(EnqueteurRequest $request)
    {
        try {
            $result = DB::transaction(function () use ($request) {
                $enqueteurRole = Role::where('slug', Role::ENQUETEUR)->first();
                // 1. Créer l'User
                $user = User::create([
                    'name'      => $request->firstname." ".$request->lastname,
                    'firstname' => $request->prenom,
                    'lastname'  => $request->nom,
                    'email'     => $request->email,
                    'password'  => Hash::make($request->password),
                    'role_id'   => $enqueteurRole->id,
                ]);                // 2. Créer l'Enqueteur lié
                $enqueteur = Enqueteur::create([
                    'poste'     => strtolower($request->poste),
                    'travail'   => strtolower($request->travail),
                    'user_id'   => $user->id,
                    'is_active' => $request->is_active ?? true,
                ]);

                return $enqueteur;
            });

            // Réponse JSON pour AJAX (évite le redirect)
            return redirect()->back()->with('success', 'Enquêteur créé avec succès !');

        } catch (\Exception $e) {
            dd($e);
        }
    }

    public function update(EnqueteurRequest $request, Enqueteur $enqueteur)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $enqueteur) {
            // 1. Mise à jour de l'Utilisateur (Table 'users')
            $userData = [
                'lastname'  => $data['nom'],
                'firstname' => $data['prenom'],
                'email'     => $data['email'],
            ];

            // On ne change le mot de passe que s'il est rempli
            if (!empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }

            $enqueteur->user->update($userData);

            // 2. Mise à jour de l'Enquêteur (Table 'enqueteurs')
            $enqueteur->update([
                'poste'  => $data['poste'],
                'travail' => $data['travail'],
            ]);
        });

        return redirect()->back()->with('success', 'Enquêteur et compte utilisateur mis à jour.');
    }

    public function destroy(Enqueteur $enqueteur)
    {
        \DB::transaction(function () use ($enqueteur) {
            $userId = $enqueteur->user_id;

            // On utilise DB::table pour contourner les hooks de modèles qui pourraient bloquer
            // On supprime d'abord l'enquêteur
            \DB::table('enqueteurs')->where('id', $enqueteur->id)->delete();

            // Puis on supprime l'utilisateur
            if ($userId) {
                \DB::table('users')->where('id', $userId)->delete();
            }
        });

        return redirect()->back()->with('success', 'Suppression radicale effectuée.');
    }
}
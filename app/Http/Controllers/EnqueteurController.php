<?php

namespace App\Http\Controllers;

use App\Models\Enqueteur;
use App\Models\User;
use App\Models\Role;
use App\Models\Societe;
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
        $user = auth()->user();
        $isAdmin = $user->isAdmin();

        // Charger les enquêteurs avec leur utilisateur et la société de l'utilisateur
        $query = Enqueteur::with(['user.societe'])
            ->where('is_active', true);

        // Si Manager, filtrer par société via l'utilisateur
        if ($user->isManager()) {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('societe_id', $user->societe_id);
            });
        }

        $enqueteurs = $query->get()->map(function ($item) {
            return [
                'id'          => $item->id,
                'user_id'     => $item->user_id,
                'nom'         => $item->user->lastname ?? '',
                'prenom'      => $item->user->firstname ?? '',
                'email'       => $item->user->email ?? '',
                'poste'       => $item->poste,
                'travail'     => $item->travail,
                'societe_id'  => $item->user->societe_id ?? null,
                'societe_nom' => $item->user->societe ? $item->user->societe->nom : null,
            ];
        });

        $societes = $isAdmin ? Societe::select('id', 'nom')->get() : [];

        return Inertia::render('enqueteurs/Liste', [
            'enqueteurs' => $enqueteurs,
            'isAdmin'    => $isAdmin,
            'societes'   => $societes,
        ]);
    }

    public function store(EnqueteurRequest $request)
    {
        try {
            $user = auth()->user();
            // Détermination sécurisée du societe_id pour l'utilisateur
            $societe_id = $user->isAdmin() ? $request->societe_id : $user->societe_id;

            DB::transaction(function () use ($request, $societe_id) {
                $roleEnqueteur = Role::where('slug', Role::ENQUETEUR)->firstOrFail();

                // 1. Création de l'User (avec societe_id)
                $userCreated = User::create([
                    'name'       => $request->prenom . ' ' . $request->nom,
                    'firstname'  => $request->prenom,
                    'lastname'   => $request->nom,
                    'email'      => $request->email,
                    'password'   => Hash::make($request->password),
                    'role_id'    => $roleEnqueteur->id,
                    'societe_id' => $societe_id, // ici
                ]);

                // 2. Création de l'Enquêteur (sans societe_id)
                Enqueteur::create([
                    'user_id'   => $userCreated->id,
                    'poste'     => $request->poste,
                    'travail'   => $request->travail,
                    'is_active' => true,
                    // plus de societe_id
                ]);
            });

            return redirect()->back()->with('success', 'Enquêteur créé avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur store enquêteur : ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function update(EnqueteurRequest $request, Enqueteur $enqueteur)
    {
        $user = auth()->user();
        $societe_id = $user->isAdmin() ? $request->societe_id : $user->societe_id;

        DB::transaction(function () use ($request, $enqueteur, $societe_id) {
            $data = $request->validated();

            // 1. Mise à jour de l'User (avec societe_id)
            $userData = [
                'firstname'  => $data['prenom'],
                'lastname'   => $data['nom'],
                'email'      => $data['email'],
                'societe_id' => $societe_id,
            ];

            if (!empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }

            if ($enqueteur->user) {
                $enqueteur->user->update($userData);
            }

            // 2. Mise à jour de l'Enquêteur (sans societe_id)
            $enqueteur->update([
                'poste'   => $data['poste'],
                'travail' => $data['travail'],
                // plus de societe_id
            ]);
        });

        return redirect()->back()->with('success', 'Enquêteur et compte utilisateur mis à jour.');
    }

    public function destroy(Enqueteur $enqueteur)
    {
        DB::transaction(function () use ($enqueteur) {
            $userId = $enqueteur->user_id;

            // Suppression physique de l'enquêteur (forceDelete) pour libérer la contrainte
            $enqueteur->forceDelete();

            // Suppression de l'utilisateur (si vous utilisez SoftDeletes sur User, on peut faire delete() ou forceDelete())
            if ($userId) {
                $user = User::find($userId);
                if ($user) {
                    // Si User a aussi SoftDeletes, on peut faire delete() pour le garder dans la corbeille,
                    // mais attention à la contrainte (l'enquêteur a déjà été supprimé physiquement donc OK).
                    // Je préfère une suppression définitive pour rester cohérent :
                    $user->forceDelete(); // ou ->delete() si vous voulez conserver l'historique
                }
            }
        });

        return redirect()->back()->with('success', 'Suppression effectuée.');
    }

    public function liste()
    {
        $user = auth()->user();

        // On récupère directement les utilisateurs ayant le rôle enquêteur et actifs
        $query = User::whereHas('role', function ($q) {
                $q->where('slug', 'enqueteur');
            })
            ->whereHas('enqueteur', fn($q) => $q->where('is_active', true));

        if ($user->isManager()) {
            $query->where('societe_id', $user->societe_id);
        }

        $enqueteurs = $query->select('id', 'lastname', 'firstname')
            ->orderBy('lastname')
            ->get()
            ->map(fn($u) => [
                'id'   => $u->id,
                'text' => $u->firstname . ' ' . $u->lastname,
            ]);

        return response()->json($enqueteurs);
    }
}
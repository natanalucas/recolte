<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Societe;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SocieteController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        // On charge également les infos du manager avec "with('manager')"
        $societes = Societe::with('manager')
            ->when($search, function ($query, $search) {
                $query->where('nom', 'like', "%{$search}%")
                      ->orWhere('nif', 'like', "%{$search}%")
                      ->orWhere('stat', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('societes/Index', [
            'societes' => $societes,
            'filters' => $request->only(['search']),
        ]);
    }

public function store(Request $request)
{
    $validated = $request->validate([
        'nom'               => 'required|string|max:255',
        'adresse'           => 'required|string|max:255',
        'phone'             => 'required|string|max:50',
        'nif'               => 'required|string|max:50',
        'stat'              => 'required|string|max:50',
        'latitude'          => 'nullable|numeric',
        'longitude'         => 'nullable|numeric',
        'logo'              => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        
        'manager_name'      => 'required|string|max:255',
        'manager_email'     => 'required|email|max:255|unique:users,email',
        'manager_password'  => 'required|string|min:8',
        // On a enlevé manager_role_id d'ici, on va le gérer côté serveur
    ]);

    DB::transaction(function () use ($request, $validated) {
        
        // 1. On récupère dynamiquement l'ID du rôle Manager
        $roleManager = Role::where('slug', Role::MANAGER)->firstOrFail();
        
        // 2. Création de l'utilisateur (Le fameux Manager)
        $manager = User::create([
            'name'     => $validated['manager_name'],
            'email'    => $validated['manager_email'],
            'password' => Hash::make($validated['manager_password']),
            'role_id'  => $roleManager->id, // On assigne l'ID du rôle trouvé
        ]);

        // 3. Gestion du logo
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
        }

        // 4. Création de la société
        Societe::create([
            'nom'        => $validated['nom'],
            'adresse'    => $validated['adresse'],
            'phone'      => $validated['phone'],
            'nif'        => $validated['nif'],
            'stat'       => $validated['stat'],
            'latitude'   => $validated['latitude'],
            'longitude'  => $validated['longitude'],
            'logo'       => $logoPath,
            'manager_id' => $manager->id, // On pointe vers l'ID du USER créé juste au-dessus !
        ]);
    });

    return redirect()->back()->with('success', 'Société et Manager créés avec succès.');
}

    public function update(Request $request, Societe $societe)
    {
        $validated = $request->validate([
            // Validation Société
            'nom'               => 'required|string|max:255',
            'adresse'           => 'required|string|max:255',
            'phone'             => 'required|string|max:50',
            'nif'               => 'required|string|max:50',
            'stat'              => 'required|string|max:50',
            'latitude'          => 'nullable|numeric',
            'longitude'         => 'nullable|numeric',
            'logo'              => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            
            // Validation Manager
            'manager_name'      => 'required|string|max:255',
            // On ignore l'email actuel du manager lors de la mise à jour
            'manager_email'     => 'required|email|max:255|unique:users,email,' . $societe->manager_id,
            'manager_password'  => 'nullable|string|min:8', // Optionnel à la mise à jour
            'manager_role_id'   => 'required|integer',
        ]);

        DB::transaction(function () use ($request, $validated, $societe) {
            
            // 1. Mise à jour de l'utilisateur (Manager)
            $managerData = [
                'name'    => $validated['manager_name'],
                'email'   => $validated['manager_email'],
                'role_id' => $validated['manager_role_id'],
            ];

            // On ne met à jour le mot de passe que s'il a été renseigné
            if (!empty($validated['manager_password'])) {
                $managerData['password'] = Hash::make($validated['manager_password']);
            }

            $societe->manager()->update($managerData);

            // 2. Gestion du logo
            $logoPath = $societe->logo;
            if ($request->hasFile('logo')) {
                if ($societe->logo && Storage::disk('public')->exists($societe->logo)) {
                    Storage::disk('public')->delete($societe->logo);
                }
                $logoPath = $request->file('logo')->store('logos', 'public');
            }

            // 3. Mise à jour de la société
            $societe->update([
                'nom'       => $validated['nom'],
                'adresse'   => $validated['adresse'],
                'phone'     => $validated['phone'],
                'nif'       => $validated['nif'],
                'stat'      => $validated['stat'],
                'latitude'  => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'logo'      => $logoPath,
            ]);
        });

        return redirect()->back()->with('success', 'Société et Manager mis à jour avec succès.');
    }

    public function destroy(Societe $societe)
    {
        DB::transaction(function () use ($societe) {
            if ($societe->logo && Storage::disk('public')->exists($societe->logo)) {
                Storage::disk('public')->delete($societe->logo);
            }

            // Supprimer la société
            $societe->delete();
            
            // IMPORTANT : Si la règle métier exige de supprimer le manager quand on supprime la société :
            // $societe->manager()->delete(); 
            // (Si vous avez mis onDelete('cascade') sur la migration du user_id vers la société, cela se fera tout seul).
        });

        return redirect()->back()->with('success', 'Société supprimée avec succès.');
    }
}
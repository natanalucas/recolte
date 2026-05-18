<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class ReceptionController extends Controller
{
    public function index(): Response
    {
        $enqueteurs = User::orderBy('id')
            ->get(['id', 'firstname', 'lastname'])
            ->map(fn($u) => [
                'id'   => $u->id,
                'name' => $u->firstname . ' ' . $u->lastname,
            ]);

        return Inertia::render('fiche/Reception', [
            'enqueteurs' => $enqueteurs,
        ]);
    }
}
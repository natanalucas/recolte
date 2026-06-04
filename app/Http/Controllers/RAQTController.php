<?php

namespace App\Http\Controllers;
use App\Models\RAQT;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RAQTController extends Controller
{
    public function index(): Response {
        return Inertia::render('raqt/Liste', [
            'agents' => RAQT::orderBy('nom')->get(),
        ]);
    }
    public function store(Request $request) {
        $request->validate(['nom' => 'required|string|max:100', 'prenom' => 'required|string|max:100']);
        RAQT::create($request->only('nom', 'prenom'));
        return back();
    }
    public function update(Request $request, RAQT $agent) {
        $request->validate(['nom' => 'required|string|max:100', 'prenom' => 'required|string|max:100']);
        $agent->update($request->only('nom', 'prenom'));
        return back();
    }
    public function destroy(RAQT $agent) {
        $agent->delete();
        return back();
    }
}

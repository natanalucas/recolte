<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Poids; // Assurez-vous d'avoir votre modèle Poids

class PoidsController extends Controller
{
    // PoidsController.php
    public function update(Request $request)
    {
        $request->validate(['kg' => 'required|numeric|min:1']);
        $poids = Poids::firstOrNew([]);
        $poids->kg = $request->kg;
        $poids->save();
        return back();
    }
}
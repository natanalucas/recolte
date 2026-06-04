<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Inertia\Inertia;
use App\Http\Controllers\EnqueteurController;
use App\Http\Controllers\OperateurController;
use App\Http\Controllers\ProducteurController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\PoidsController;
use App\Http\Controllers\RAQTController;
use App\Http\Controllers\SoufrageController;
use App\Http\Controllers\TypeCertificationController;
use App\Http\Controllers\TriageController;
use App\Http\Controllers\FicheReceptionController;
use Illuminate\Http\Request;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    //Route::inertia('fiche-triage', 'fiche/Triage')->name('fiche-triage');
    Route::inertia('fiche-soufrage', 'fiche/Soufrage')->name('fiche-soufrage');
    //Route::inertia('fiche-reception', 'fiche/Reception')->name('fiche-reception');
    Route::inertia('fiche-paletisation', 'fiche/Paletisation')->name('fiche-paletisation');
    Route::inertia('fiche-expedition', 'fiche/Expedition')->name('fiche-expedition');
   // Route::inertia('enqueteurs', 'enqueteurs/Liste')->name('enqueteurs');
    // Route::inertia('producteurs', 'producteurs/Liste')->name('producteurs');
    Route::get('archives/{slug}', function ($slug) {
        return Inertia::render('archives/Produit', [
            'slug' => $slug 
        ]);
    })->name('archives-produit');

    // Une seule route pour la vue principale
    Route::get('/enqueteurs', [EnqueteurController::class, 'index'])->name('enqueteurs.index');

    // Actions CRUD
    Route::post('/enqueteurs', [EnqueteurController::class, 'store'])->name('enqueteurs.store');
    Route::put('/enqueteurs/{enqueteur}', [EnqueteurController::class, 'update'])->name('enqueteurs.update');
    Route::delete('/enqueteurs/{enqueteur}', [EnqueteurController::class, 'destroy'])->name('enqueteurs.destroy');

    Route::resource('producteurs', ProducteurController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::get('/reception', [ReceptionController::class, 'index'])->name('reception.index');

    Route::put('poids', [PoidsController::class, 'update'])->name('poids.update');

    // routes/web.php
    Route::get('raqt', [RAQTController::class, 'index'])->name('raqt.index');
    Route::post('raqt', [RAQTController::class, 'store'])->name('raqt.store');
    Route::put('raqt/{agent}', [RAQTController::class, 'update'])->name('raqt.update');
    Route::delete('raqt/{agent}', [RAQTController::class, 'destroy'])->name('raqt.destroy');
    
    Route::resource('operateurs', OperateurController::class)
    ->only(['index', 'store', 'update', 'destroy']);

    Route::get('soufrage', [SoufrageController::class, 'index'])->name('soufrage.index');
    Route::post('soufrage', [SoufrageController::class, 'store'])->name('soufrage.store');
    Route::put('soufrage/{soufrage}',   [SoufrageController::class, 'update'])->name('soufrage.update');
    Route::patch('soufrage/{soufrage}', [SoufrageController::class, 'update'])->name('soufrage.patch'); // pour contrôle RAQT seul
    // web.php
    Route::delete('soufrage/{soufrage}', [SoufrageController::class, 'destroy'])->name('soufrage.destroy');

    Route::resource('type-certifications', TypeCertificationController::class)
    ->only(['index', 'store', 'update', 'destroy']);

    // routes/web.php
    Route::get('triage',                [TriageController::class, 'index']  )->name('triage.index');
    Route::post('triage',               [TriageController::class, 'store']  )->name('triage.store');
    Route::put('triage/{triage}',       [TriageController::class, 'update'] )->name('triage.update');
    Route::delete('triage/{triage}',    [TriageController::class, 'destroy'])->name('triage.destroy');

    Route::post('settings/poids', function (Request $r) {
        \App\Models\Setting::set('poids_par_caissette', $r->input('poids_par_caissette'));
        return back();
    })->name('settings.poids');

    Route::resource('reception', FicheReceptionController::class)
     ->only(['index', 'store', 'update', 'destroy']);
    });

    Route::get('/enqueteurs/liste', [EnqueteurController::class, 'liste']);
    

require __DIR__.'/settings.php';

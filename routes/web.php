<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Inertia\Inertia;
use App\Http\Controllers\EnqueteurController;
use App\Http\Controllers\ProducteurController;
use App\Http\Controllers\ReceptionController;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::inertia('fiche-triage', 'fiche/Triage')->name('fiche-triage');
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

});

require __DIR__.'/settings.php';

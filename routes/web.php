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
use App\Http\Controllers\PaletisationController;
use App\Http\Controllers\ExpeditionController;
use App\Http\Controllers\ExportPdfController;
use App\Http\Controllers\StatistiqueController;
use App\Http\Controllers\SocieteController;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {    
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    //Route::inertia('fiche-paletisation', 'fiche/Paletisation')->name('fiche-paletisation');
    //Route::inertia('fiche-expedition', 'fiche/Expedition')->name('fiche-expedition');
    
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

    Route::get('/palettisation', [PaletisationController::class, 'index'])
        ->name('paletisation.index');
 
    Route::post('/palettisation', [PaletisationController::class, 'store'])
        ->name('paletisation.store');
 
    Route::put('/palettisation/{paletisation}', [PaletisationController::class, 'update'])
        ->name('paletisation.update');
 
    Route::delete('/palettisation/{paletisation}', [PaletisationController::class, 'destroy'])
        ->name('paletisation.destroy');

    Route::get('/expeditions', [ExpeditionController::class, 'index'])->name('expeditions.index');
    Route::post('/expeditions', [ExpeditionController::class, 'store'])->name('expeditions.store');
    Route::put('/expeditions/{expedition}', [ExpeditionController::class, 'update'])->name('expeditions.update');
    Route::delete('/expeditions/{expedition}', [ExpeditionController::class, 'destroy'])->name('expeditions.destroy');

    Route::post('settings/poids', function (Request $r) {
        \App\Models\Setting::set('poids_par_caissette', $r->input('poids_par_caissette'));
        return back();
    })->name('settings.poids');

    Route::get('/fiches-reception/{ficheReception}/export-pdf', [FicheReceptionController::class, 'exportPdf'])
    ->name('fiches-reception.export-pdf');

    Route::resource('reception', FicheReceptionController::class)
     ->only(['index', 'store', 'update', 'destroy']);
    });

    Route::get('/fiches-reception/{ficheReception}/export-pdf', [ExportPdfController::class, 'reception'])
    ->name('fiches-reception.export-pdf');

    Route::get('/fiches-reception/annee/{year}/export-pdf', [ExportPdfController::class, 'receptionByYear'])
        ->name('fiches-reception.export-pdf-annee');

    Route::get('/fiches-soufrage/{ficheNumber}/export-pdf', [ExportPdfController::class, 'soufrage'])
        ->name('fiches-soufrage.export-pdf');

    Route::get('/fiches-triage/{ficheNumber}/export-pdf', [ExportPdfController::class, 'triage'])
        ->name('fiches-triage.export-pdf');

    Route::get('/fiches-triage/annee/{year}/export-pdf', [ExportPdfController::class, 'triageByYear'])
        ->where('year', '[0-9]{4}')
        ->name('fiches-triage.export-pdf-annee');

    Route::get('/fiches-soufrage/annee/{year}/export-pdf', [ExportPdfController::class, 'soufrageByYear'])
    ->name('fiches-soufrage.export-pdf-annee');

    // Palettisation — pas de filtre produit car pas de lien parcelle/producteur
    Route::get('/fiches-paletisation/annee/{year}/export-pdf', [ExportPdfController::class, 'paletisationByYear'])
    ->name('fiches-paletisation.export-pdf-annee');

    Route::get('/fiches-expedition/annee/{year}/export-pdf', [ExportPdfController::class, 'expeditionByYear'])
    ->name('fiches-expedition.export-pdf-annee');

    Route::get('/enqueteurs/liste', [EnqueteurController::class, 'liste']);

    Route::get('/statistiques/{slug?}', [StatistiqueController::class, 'index'])->name('statistiques.index');
    
    Route::middleware(['auth'])->group(function () {
        Route::resource('societes', SocieteController::class)->only([
            'index', 'store', 'update', 'destroy'
        ]);
    });

    Route::post('/triage/{triage}/facture', [TriageController::class, 'facture'])
    ->name('triage.facture');

require __DIR__.'/settings.php';

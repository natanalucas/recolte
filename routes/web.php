<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::inertia('fiche-tracabilite', 'fiche/Tracabilite')->name('tracabilite');
    Route::inertia('fiche-soufrage', 'fiche/Soufrage')->name('fiche-soufrage');
    Route::inertia('agent-tracabilite', 'agent/Liste')->name('agent-tracabilite');
    Route::inertia('producteurs', 'producteurs/Liste')->name('producteurs');
});

require __DIR__.'/settings.php';

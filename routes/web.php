<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Redirection après connexion
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    
    // Redirection automatique selon le rôle
    Route::get('/dashboard', function () {
        if (auth()->user()->isGerant()) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('pos.dashboard');
    })->name('dashboard');

    // Routes pour le GÉRANT
    Route::middleware(['gerant'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        // Les autres routes admin seront ajoutées ici
    });

    // Routes pour le VENDEUR
    Route::middleware(['vendeur'])->prefix('pos')->name('pos.')->group(function () {
        Route::get('/dashboard', function () {
            return view('pos.dashboard');
        })->name('dashboard');

        // Les autres routes POS seront ajoutées ici
    });
});
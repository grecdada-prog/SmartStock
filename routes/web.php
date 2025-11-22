<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\LoginController;

// Route d'accueil
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Routes d'authentification personnalisées
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

// Redirection après connexion
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    
    // Redirection automatique du /dashboard selon le rôle
    Route::get('/dashboard', function () {
        if (Auth::user()->isGerant()) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('pos.dashboard');
    })->name('dashboard');

    // Routes pour le GÉRANT
    Route::middleware(['gerant'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        // Gestion des produits et catégories
        Route::get('/products', function () {
            return view('admin.products.index');
        })->name('products');

        Route::get('/categories', function () {
            return view('admin.categories.index');
        })->name('categories');

        // Gestion des vendeurs
        Route::resource('sellers', \App\Http\Controllers\Admin\SellerController::class);
        Route::post('sellers/{seller}/toggle-status', [\App\Http\Controllers\Admin\SellerController::class, 'toggleStatus'])
            ->name('sellers.toggle-status');
    });

    // Routes pour le VENDEUR
    Route::middleware(['vendeur'])->prefix('pos')->name('pos.')->group(function () {
        Route::get('/dashboard', function () {
            return view('pos.dashboard');
        })->name('dashboard');

        // Les autres routes POS seront ajoutées ici
    });
});
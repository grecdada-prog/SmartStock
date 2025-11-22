<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SessionController;

Route::middleware('api')->group(function () {
    // Route pour fermer la session (sans authentification requise)
    Route::post('/close-session', [SessionController::class, 'closeSession']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Vérifier la validité de la session
    Route::get('/check-session', [SessionController::class, 'checkSession']);
});

Route::middleware('auth:sanctum')->group(function () {
    // Nettoyage des anciennes sessions (admin only)
    Route::post('/cleanup-sessions', [SessionController::class, 'cleanupOldSessions'])
        ->middleware('admin');
});
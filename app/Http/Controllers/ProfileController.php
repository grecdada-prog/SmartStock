<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\ActivityLog;

class ProfileController extends Controller
{
    /**
     * Afficher le profil de l'utilisateur
     */
    public function show()
    {
        $user = Auth::user();
        
        // Rediriger vers le bon profil selon le rôle
        if ($user->hasRole('super_admin')) {
            return view('superadmin.profile.show', compact('user'));
        } elseif ($user->hasRole('manager')) {
            return view('manager.profile.show', compact('user'));
        } elseif ($user->hasRole('seller')) {
            return view('seller.profile.show', compact('user'));
        }
    }

    /**
     * Mettre à jour les informations du profil
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        ActivityLog::log(
            'profile_updated',
            'Profil mis à jour',
            'User',
            $user->id
        );

        return back()->with('success', 'Profil mis à jour avec succès.');
    }

    /**
     * Mettre à jour le mot de passe (Super Admin et Manager uniquement)
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        // Vérifier que l'utilisateur a le droit de changer son mot de passe
        if (!$user->hasRole(['super_admin', 'manager'])) {
            return back()->with('error', 'Vous n\'avez pas l\'autorisation de modifier votre mot de passe.');
        }

        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised()],
        ], [
            'current_password.required' => 'Le mot de passe actuel est obligatoire',
            'password.required' => 'Le nouveau mot de passe est obligatoire',
            'password.confirmed' => 'Les mots de passe ne correspondent pas',
        ]);

        // Vérifier le mot de passe actuel
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        ActivityLog::log(
            'password_changed',
            'Mot de passe modifié',
            'User',
            $user->id
        );

        return back()->with('success', 'Mot de passe modifié avec succès.');
    }
}
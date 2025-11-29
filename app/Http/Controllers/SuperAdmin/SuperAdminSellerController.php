<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Notifications\UserCreatedNotification;

class SuperAdminSellerController extends Controller
{
    /**
     * Liste des vendeurs
     */
    public function index(Request $request)
    {
        $query = User::role('seller')
            ->with(['creator', 'sales'])
            ->withCount(['sales', 'activityLogs'])
            ->withSum('sales', 'total');

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $sellers = $query->latest()->paginate(20);

        return view('superadmin.sellers.index', compact('sellers'));
    }

    /**
     * Afficher le formulaire de création de vendeur
     */
    public function create()
    {
        return view('superadmin.sellers.create');
    }

    /**
     * Créer un nouveau vendeur
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users', 'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'],
            'phone' => ['nullable', 'regex:/^[0-9]{9,15}$/'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()],
            'is_active' => ['boolean'],
        ], [
            'email.regex' => 'Le format de l\'email est invalide.',
            'phone.regex' => 'Le téléphone doit contenir uniquement des chiffres (9-15 caractères).',
        ]);

        // Stocker le mot de passe temporaire avant le hash
        $temporaryPassword = $validated['password'];

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'is_active' => $request->has('is_active'),
            'created_by' => auth()->id(),
        ]);

        $user->assignRole('seller');

        ActivityLog::log(
            'seller_created',
            "Nouveau vendeur créé : {$user->name}",
            'User',
            $user->id
        );

        // Envoyer l'email de bienvenue
        $user->notify(new UserCreatedNotification($temporaryPassword, auth()->user()));

        return redirect()->route('superadmin.sellers.index')
            ->with('success', 'Vendeur créé avec succès ! Un email de bienvenue a été envoyé.');
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(User $user)
    {
        // Vérifier que l'utilisateur est bien un vendeur
        if (!$user->hasRole('seller')) {
            return redirect()->route('superadmin.sellers.index')
                ->with('error', 'Cet utilisateur n\'est pas un vendeur.');
        }

        $user->load('roles');
        return view('superadmin.sellers.edit', compact('user'));
    }

    /**
     * Mettre à jour un vendeur
     */
    public function update(Request $request, User $user)
    {
        // Vérifier que l'utilisateur est bien un vendeur
        if (!$user->hasRole('seller')) {
            return redirect()->route('superadmin.sellers.index')
                ->with('error', 'Cet utilisateur n\'est pas un vendeur.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id, 'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'],
            'phone' => ['nullable', 'regex:/^[0-9]{9,15}$/'],
            'is_active' => ['boolean'],
        ], [
            'email.regex' => 'Le format de l\'email est invalide.',
            'phone.regex' => 'Le téléphone doit contenir uniquement des chiffres (9-15 caractères).',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'is_active' => $request->has('is_active'),
        ]);

        ActivityLog::log(
            'seller_updated',
            "Vendeur modifié : {$user->name}",
            'User',
            $user->id
        );

        return redirect()->route('superadmin.sellers.index')
            ->with('success', 'Vendeur modifié avec succès !');
    }

    /**
     * Supprimer un vendeur
     */
    public function destroy(User $user)
    {
        // Vérifier que l'utilisateur est bien un vendeur
        if (!$user->hasRole('seller')) {
            return redirect()->route('superadmin.sellers.index')
                ->with('error', 'Cet utilisateur n\'est pas un vendeur.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $userName = $user->name;

        ActivityLog::log(
            'seller_deleted',
            "Vendeur supprimé : {$userName}",
            'User',
            $user->id
        );

        $user->delete();

        return redirect()->route('superadmin.sellers.index')
            ->with('success', "Le vendeur {$userName} a été supprimé avec succès !");
    }
}

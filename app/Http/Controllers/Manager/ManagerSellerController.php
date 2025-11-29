<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ActivityLog;
use App\Services\SessionManager;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Notifications\UserCreatedNotification;
use App\Notifications\Enable2FANotification;
use App\Notifications\PasswordResetNotification;

class ManagerSellerController extends Controller
{
    /**
     * Liste des vendeurs créés par le manager authentifié
     */
    public function index(Request $request)
    {
        $query = User::role('seller')
            ->where('created_by', auth()->id())
            ->with(['creator', 'sales'])
            ->withCount(['sales', 'activityLogs'])
            ->withSum('sales', 'total');

        // Filtres
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

        return view('manager.sellers.index', compact('sellers'));
    }

    /**
     * Afficher le formulaire de création de vendeur
     */
    public function create()
    {
        return view('manager.sellers.create');
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
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => auth()->id(),
        ]);

        $user->assignRole('seller');

        ActivityLog::log(
            'seller_created',
            "Vendeur créé : {$user->name} par le gérant " . auth()->user()->name,
            'User',
            $user->id
        );

        // Envoyer les emails de notification
        $user->notify(new UserCreatedNotification($temporaryPassword, auth()->user()));
        $user->notify(new Enable2FANotification());

        return redirect()->route('manager.sellers.index')
            ->with('success', 'Vendeur créé avec succès ! Un email de bienvenue a été envoyé.');
    }

    /**
     * Afficher le formulaire d'édition d'un vendeur
     */
    public function edit(User $user)
    {
        // Vérifier que le vendeur appartient bien au manager
        if ($user->created_by !== auth()->id()) {
            abort(403, 'Vous n\'avez pas l\'autorisation de modifier ce vendeur.');
        }

        // Vérifier que c'est bien un vendeur
        if (!$user->hasRole('seller')) {
            abort(403, 'Cet utilisateur n\'est pas un vendeur.');
        }

        return view('manager.sellers.edit', compact('user'));
    }

    /**
     * Mettre à jour un vendeur
     */
    public function update(Request $request, User $user)
    {
        // Vérifier que le vendeur appartient bien au manager
        if ($user->created_by !== auth()->id()) {
            abort(403, 'Vous n\'avez pas l\'autorisation de modifier ce vendeur.');
        }

        // Vérifier que c'est bien un vendeur
        if (!$user->hasRole('seller')) {
            abort(403, 'Cet utilisateur n\'est pas un vendeur.');
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
            'phone' => $validated['phone'] ?? null,
            'is_active' => $validated['is_active'] ?? $user->is_active,
        ]);

        ActivityLog::log(
            'seller_updated',
            "Vendeur mis à jour : {$user->name}",
            'User',
            $user->id
        );

        return redirect()->route('manager.sellers.index')
            ->with('success', 'Vendeur mis à jour avec succès.');
    }

    /**
     * Supprimer un vendeur
     */
    public function destroy(User $user)
    {
        // Vérifier que le vendeur appartient bien au manager
        if ($user->created_by !== auth()->id()) {
            abort(403, 'Vous n\'avez pas l\'autorisation de supprimer ce vendeur.');
        }

        // Vérifier que c'est bien un vendeur
        if (!$user->hasRole('seller')) {
            abort(403, 'Cet utilisateur n\'est pas un vendeur.');
        }

        $userName = $user->name;

        ActivityLog::log(
            'seller_deleted',
            "Vendeur supprimé : {$userName}",
            'User',
            $user->id
        );

        $user->delete();

        return redirect()->route('manager.sellers.index')
            ->with('success', "Vendeur {$userName} supprimé avec succès.");
    }

    /**
     * Afficher les vendeurs en ligne
     */
    public function onlineSellers()
    {
        $onlineSellers = SessionManager::getOnlineUsers('seller')
            ->where('created_by', auth()->id())
            ->get();

        return view('manager.sellers.online', compact('onlineSellers'));
    }

    /**
     * Réinitialiser le mot de passe d'un vendeur
     */
    public function resetPassword(User $user)
    {
        // Vérifier que le vendeur appartient bien au manager
        if ($user->created_by !== auth()->id()) {
            abort(403, 'Vous n\'avez pas l\'autorisation de réinitialiser ce mot de passe.');
        }

        // Générer un mot de passe temporaire sécurisé
        $temporaryPassword = \App\Helpers\PasswordHelper::generateReadablePassword();

        $user->update([
            'password' => Hash::make($temporaryPassword),
        ]);

        ActivityLog::log(
            'password_reset',
            "Mot de passe réinitialisé pour : {$user->name}",
            'User',
            $user->id
        );

        // Envoyer un email avec le mot de passe temporaire
        $user->notify(new PasswordResetNotification($temporaryPassword));

        return back()->with('success', "Mot de passe réinitialisé avec succès. Un email a été envoyé à {$user->name}.");
    }

    /**
     * Activer/désactiver un vendeur
     */
    public function toggleStatus(User $user)
    {
        // Vérifier que le vendeur appartient bien au manager
        if ($user->created_by !== auth()->id()) {
            abort(403, 'Vous n\'avez pas l\'autorisation de modifier ce vendeur.');
        }

        $newStatus = !$user->is_active;
        $user->update(['is_active' => $newStatus]);

        ActivityLog::log(
            'seller_status_changed',
            "Statut changé pour {$user->name} : " . ($newStatus ? 'activé' : 'désactivé'),
            'User',
            $user->id
        );

        return back()->with('success', "Vendeur " . ($newStatus ? 'activé' : 'désactivé') . " avec succès.");
    }

    /**
     * Forcer la déconnexion d'un vendeur
     */
    public function forceLogout(User $user)
    {
        // Vérifier que le vendeur appartient bien au manager
        if ($user->created_by !== auth()->id()) {
            abort(403, 'Vous n\'avez pas l\'autorisation de déconnecter ce vendeur.');
        }

        $isOnline = SessionManager::isUserOnline($user->id);

        if (!$isOnline) {
            return back()->with('error', 'Ce vendeur n\'est pas connecté.');
        }

        SessionManager::logoutUserFromAllSessions(
            $user->id,
            'Déconnexion forcée par le gérant'
        );

        ActivityLog::log(
            'seller_force_logout',
            "Déconnexion forcée de : {$user->name}",
            'User',
            $user->id
        );

        return back()->with('success', "{$user->name} a été déconnecté avec succès.");
    }
}

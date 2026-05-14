<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ActivityLog;
use App\Services\PasswordSetupLinkService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use App\Notifications\UserCreatedNotification;
use App\Notifications\Enable2FANotification;
use Throwable;

class SuperAdminManagerController extends Controller
{
    /**
     * Liste des managers
     */
    public function index(Request $request)
    {
        $query = User::role('manager')
            ->with('creator')
            ->withCount(['createdUsers', 'activityLogs']);

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $managers = $query->latest()->paginate(20);

        return view('superadmin.managers.index', compact('managers'));
    }

    /**
     * Afficher le formulaire de création de manager
     */
    public function create()
    {
        return view('superadmin.managers.create');
    }

    /**
     * Créer un nouveau manager
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

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_active' => $request->has('is_active'),
            'created_by' => auth()->id(),
        ]);

        $user->assignRole('manager');

        ActivityLog::log(
            'manager_created',
            "Nouveau manager créé : {$user->name}",
            'User',
            $user->id
        );

        $emailWarning = null;

        try {
            // Envoyer l'email de bienvenue
            $setupUrl = app(PasswordSetupLinkService::class)->createUrl($user);
            $user->notify(new UserCreatedNotification($setupUrl, auth()->user()));

        // Suggérer l'activation du 2FA pour les managers
            $user->notify(new Enable2FANotification());
        } catch (Throwable $exception) {
            Log::warning('Unable to send manager creation notification.', [
                'user_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);

            $emailWarning = 'Le gerant a ete cree, mais l email de bienvenue n a pas pu etre envoye.';
        }

        return redirect()->route('superadmin.managers.index')
            ->with('success', 'Gerant cree avec succes !'.($emailWarning ? ' '.$emailWarning : ' Un email de bienvenue a ete envoye.'));
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(User $user)
    {
        $this->authorize('manageManagerAsSuperAdmin', $user);

        // Vérifier que l'utilisateur est bien un manager
        if (!$user->hasRole('manager')) {
            return redirect()->route('superadmin.managers.index')
                ->with('error', 'Cet utilisateur n\'est pas un manager.');
        }

        $user->load('roles');
        return view('superadmin.managers.edit', compact('user'));
    }

    /**
     * Mettre à jour un manager
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('manageManagerAsSuperAdmin', $user);

        // Vérifier que l'utilisateur est bien un manager
        if (!$user->hasRole('manager')) {
            return redirect()->route('superadmin.managers.index')
                ->with('error', 'Cet utilisateur n\'est pas un manager.');
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
            'is_active' => $request->has('is_active'),
        ]);

        ActivityLog::log(
            'manager_updated',
            "Manager modifié : {$user->name}",
            'User',
            $user->id
        );

        return redirect()->route('superadmin.managers.index')
            ->with('success', 'Manager modifié avec succès !');
    }

    /**
     * Supprimer un manager
     */
    public function destroy(User $user)
    {
        $this->authorize('deleteAsSuperAdmin', $user);
        $this->authorize('manageManagerAsSuperAdmin', $user);

        // Vérifier que l'utilisateur est bien un manager
        if (!$user->hasRole('manager')) {
            return redirect()->route('superadmin.managers.index')
                ->with('error', 'Cet utilisateur n\'est pas un manager.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $userName = $user->name;

        ActivityLog::log(
            'manager_deleted',
            "Manager supprimé : {$userName}",
            'User',
            $user->id
        );

        $user->delete();

        return redirect()->route('superadmin.managers.index')
            ->with('success', "Le manager {$userName} a été supprimé avec succès !");
    }
}

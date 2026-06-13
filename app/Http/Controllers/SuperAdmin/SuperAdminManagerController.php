<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ActivityLog;
use App\Services\PasswordSetupLinkService;
use App\Services\UserDeletionService;
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

        $managers = $query->latest()->paginate(15)->withQueryString();

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
        $this->normalizeContactInputs($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => $this->strictEmailRules('unique:users'),
            'phone' => $this->phoneRules(),
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()],
            'is_active' => ['boolean'],
        ], $this->contactValidationMessages());

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

        $this->normalizeContactInputs($request);

        // Vérifier que l'utilisateur est bien un manager
        if (!$user->hasRole('manager')) {
            return redirect()->route('superadmin.managers.index')
                ->with('error', 'Cet utilisateur n\'est pas un manager.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => $this->strictEmailRules('unique:users,email,' . $user->id),
            'phone' => $this->phoneRules(),
            'is_active' => ['boolean'],
        ], $this->contactValidationMessages());

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

        app(UserDeletionService::class)->delete($user);

        return redirect()->route('superadmin.managers.index')
            ->with('success', "Le manager {$userName} a été supprimé avec succès !");
    }
}



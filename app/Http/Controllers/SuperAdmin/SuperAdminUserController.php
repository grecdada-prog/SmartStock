<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ActivityLog;
use App\Services\SessionManager;
use App\Services\PasswordSetupLinkService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use App\Notifications\UserCreatedNotification;
use App\Notifications\Enable2FANotification;
use App\Notifications\PasswordResetNotification;
use App\Notifications\AccountStatusChangedNotification;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Throwable;

class SuperAdminUserController extends Controller
{
    /**
     * Liste de tous les utilisateurs
     */
    public function index(Request $request)
    {
        $query = User::with(['roles', 'creator'])
            ->withCount(['sales', 'activityLogs']);

        // Filtres
        if ($request->filled('role')) {
            $query->role($request->role);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->latest()->paginate(20);

        return view('superadmin.users.index', compact('users'));
    }

    /**
     * Liste des managers
     */
    public function managers(Request $request)
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

        return view('superadmin.users.managers', compact('managers'));
    }

    /**
     * Liste des sellers
     */
    public function sellers(Request $request)
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

        return view('superadmin.users.sellers', compact('sellers'));
    }

    /**
     * Afficher le formulaire de création d'utilisateur
     */
    public function create()
    {
        $managers = User::role('manager')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('superadmin.users.create', compact('managers'));
    }

    /**
     * Afficher le formulaire de création de manager
     */
    public function createManager()
    {
        return view('superadmin.users.create-manager');
    }

    /**
     * Créer un nouvel utilisateur
     */
public function store(Request $request)
{
    $this->normalizeContactInputs($request);

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => $this->strictEmailRules('unique:users'),
        'phone' => $this->phoneRules(),
        'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()],
        'role' => ['required', 'in:super_admin,manager,seller'],
        'manager_id' => ['required_if:role,seller', 'nullable', 'exists:users,id'],
        'is_active' => ['boolean'],
    ], $this->contactValidationMessages());

    $creatorId = auth()->id();

    if ($validated['role'] === 'seller') {
        $manager = User::role('manager')->find($validated['manager_id']);

        if (!$manager) {
            return back()->withErrors(['manager_id' => 'Gérant invalide.'])->withInput();
        }

        $creatorId = $manager->id;
    }

    $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'phone' => $validated['phone'] ?? null,
        'password' => Hash::make($validated['password']),
        'is_active' => $request->boolean('is_active'),
        'created_by' => $creatorId,
    ]);

    $user->assignRole($validated['role']);

    ActivityLog::log(
        'user_created',
        "Nouvel utilisateur créé : {$user->name} ({$validated['role']})",
        'User',
        $user->id
    );

    // Envoyer l'email de bienvenue avec le mot de passe
    $emailWarning = null;

    try {
        $setupUrl = app(PasswordSetupLinkService::class)->createUrl($user);
        $user->notify(new UserCreatedNotification($setupUrl, auth()->user()));

    // Suggérer l'activation du 2FA pour les rôles sensibles
        if (in_array($validated['role'], ['super_admin', 'manager'])) {
            $user->notify(new Enable2FANotification());
        }
    } catch (Throwable $exception) {
        Log::warning('Unable to send user creation notification.', [
            'user_id' => $user->id,
            'exception' => $exception->getMessage(),
        ]);

        $emailWarning = 'L utilisateur a ete cree, mais l email de bienvenue n a pas pu etre envoye.';
    }

    return redirect()->route('superadmin.users.index')
        ->with('success', 'Utilisateur cree avec succes !'.($emailWarning ? ' '.$emailWarning : ' Un email de bienvenue a ete envoye.'));
}

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(User $user)
    {
        $this->authorize('manageAsSuperAdmin', $user);

        $user->load('roles');
        $managers = User::role('manager')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('superadmin.users.edit', compact('user', 'managers'));
    }

    /**
     * Mettre à jour un utilisateur
     */
public function update(Request $request, User $user)
{
    $this->authorize('manageAsSuperAdmin', $user);
    $this->normalizeContactInputs($request);

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => $this->strictEmailRules('unique:users,email,' . $user->id),
        'phone' => $this->phoneRules(),
        'role' => ['required', 'in:super_admin,manager,seller'],
        'manager_id' => ['required_if:role,seller', 'nullable', 'exists:users,id'],
        'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()],
        'is_active' => ['boolean'],
    ], $this->contactValidationMessages());

    if ($user->id === auth()->id() && $validated['role'] !== $user->getRoleNames()->first()) {
        return back()->withErrors(['role' => 'Vous ne pouvez pas modifier votre propre role.'])->withInput();
    }

    if ($user->id === auth()->id() && !$request->boolean('is_active')) {
        return back()->withErrors(['is_active' => 'Vous ne pouvez pas desactiver votre propre compte.'])->withInput();
    }

    $creatorId = $user->created_by;

    if ($validated['role'] === 'seller') {
        $manager = User::role('manager')->find($validated['manager_id']);

        if (!$manager) {
            return back()->withErrors(['manager_id' => 'Gérant invalide.'])->withInput();
        }

        $creatorId = $manager->id;
    } elseif ($user->hasRole('seller')) {
        $creatorId = auth()->id();
    }

    $attributes = [
        'name' => $validated['name'],
        'email' => $validated['email'],
        'phone' => $validated['phone'] ?? null,
        'is_active' => $request->boolean('is_active'),
        'created_by' => $creatorId,
    ];

    if (!empty($validated['password'])) {
        $attributes['password'] = Hash::make($validated['password']);
    }

    $user->update($attributes);

    if ($user->getRoleNames()->first() !== $validated['role']) {
        $user->syncRoles([$validated['role']]);
    }

    ActivityLog::log(
        'user_updated',
        "Utilisateur modifié : {$user->name}",
        'User',
        $user->id
    );

    return redirect()->route('superadmin.users.index')
        ->with('success', 'Utilisateur modifié avec succès !');
}

    /**
     * Supprimer un utilisateur
     */
public function destroy(User $user)
{
    $this->authorize('deleteAsSuperAdmin', $user);

    if ($user->id === auth()->id()) {
        return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
    }

    $userName = $user->name;
    $userRole = $user->getRoleNames()->first();

    ActivityLog::log(
        'user_deleted',
        "Utilisateur supprimé : {$userName} ({$userRole})",
        'User',
        $user->id
    );

    $user->delete();

    return redirect()->route('superadmin.users.index')
        ->with('success', "L'utilisateur {$userName} a été supprimé avec succès !");
}

    /**
     * Activer/Désactiver un utilisateur
     */
public function toggleStatus(User $user)
{
    $this->authorize('toggleStatusAsSuperAdmin', $user);

    if ($user->id === auth()->id()) {
        return back()->with('error', 'Vous ne pouvez pas modifier votre propre statut.');
    }

    $user->update([
        'is_active' => !$user->is_active,
    ]);

    $status = $user->is_active ? 'activé' : 'désactivé';

    ActivityLog::log(
        'user_status_changed',
        "Utilisateur {$status} : {$user->name}",
        'User',
        $user->id
    );

    // Envoyer un email pour notifier le changement de statut
    $user->notify(new AccountStatusChangedNotification($user->is_active, auth()->user()->name));

    return back()->with('success', "L'utilisateur {$user->name} a été {$status} avec succès. Un email de notification a été envoyé.");
}

    /**
     * Forcer la déconnexion d'un utilisateur
     */
    public function forceLogout(User $user)
    {
        $this->authorize('forceLogoutAsSuperAdmin', $user);

        // Empêcher de se déconnecter soi-même
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas vous déconnecter vous-même.');
        }

        $isOnline = SessionManager::isUserOnline($user->id);

        if (!$isOnline) {
            return back()->with('error', 'Cet utilisateur n\'est pas connecté.');
        }

        SessionManager::logoutUserFromAllSessions($user->id, 'Déconnexion forcée par le Super Admin');

        ActivityLog::log(
            'user_forced_logout',
            "Déconnexion forcée de l'utilisateur : {$user->name}",
            'User',
            $user->id
        );

        return back()->with('success', 'Utilisateur déconnecté avec succès.');
    }

    /**
 * Réinitialiser le mot de passe d'un utilisateur
 */
    public function resetPassword(User $user)
    {
        $this->authorize('resetPasswordAsSuperAdmin', $user);

        $resetUrl = app(PasswordSetupLinkService::class)->createUrl($user);

        ActivityLog::log(
            'password_reset',
            "Mot de passe réinitialisé pour : {$user->name}",
            'User',
            $user->id
        );

        $user->notify(new PasswordResetNotification($resetUrl));

        return back()->with('success', "Un lien de reinitialisation du mot de passe a ete envoye a l'utilisateur.");
    }

    /**
     * Exporter les utilisateurs en Excel
     */
    public function exportExcel(Request $request)
    {
        ActivityLog::log(
            'export_users_excel',
            'Export des utilisateurs en Excel',
            'User',
            null
        );

        return Excel::download(
            new UsersExport($request->role, $request->status, $request->search),
            'utilisateurs_' . now()->format('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    /**
     * Exporter les utilisateurs en PDF
     */
    public function exportPdf(Request $request)
    {
        $query = User::with(['roles', 'creator']);

        if ($request->filled('role')) {
            $query->role($request->role);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->latest()->get();

        ActivityLog::log(
            'export_users_pdf',
            'Export des utilisateurs en PDF',
            'User',
            null
        );

        $pdf = Pdf::loadView('superadmin.exports.users-pdf', compact('users'));

        return $pdf->download('utilisateurs_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }
}



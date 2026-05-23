<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CashRegisterClosure;
use App\Models\User;
use App\Notifications\Enable2FANotification;
use App\Notifications\UserCreatedNotification;
use App\Services\CashRegisterService;
use App\Services\PasswordSetupLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Throwable;

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
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        $sellers = $query->latest()->get();
        $closedCashRegisterSellerIds = CashRegisterClosure::whereIn('seller_id', $sellers->pluck('id'))
            ->whereNull('opened_at')
            ->whereDate('business_date', today())
            ->pluck('seller_id')
            ->all();
        $openCashRegisterSellerIds = CashRegisterClosure::whereIn('seller_id', $sellers->pluck('id'))
            ->whereNotNull('opened_at')
            ->whereDate('business_date', today())
            ->pluck('seller_id')
            ->all();

        return view('superadmin.sellers.index', compact('sellers', 'closedCashRegisterSellerIds', 'openCashRegisterSellerIds'));
    }

    /**
     * Afficher le formulaire de création de vendeur
     */
    public function create()
    {
        $managers = User::role('manager')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('superadmin.sellers.create', compact('managers'));
    }

    /**
     * Créer un nouveau vendeur
     */
    public function store(Request $request)
    {
        $this->normalizeContactInputs($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => $this->strictEmailRules('unique:users'),
            'phone' => $this->phoneRules(),
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()],
            'manager_id' => ['required', 'exists:users,id'],
            'is_active' => ['boolean'],
        ], $this->contactValidationMessages());

        $manager = User::role('manager')->find($validated['manager_id']);

        if (! $manager) {
            return back()->withErrors(['manager_id' => 'Gerant invalide.'])->withInput();
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_active' => $request->has('is_active'),
            'created_by' => $manager->id,
        ]);

        $user->assignRole('seller');

        ActivityLog::log(
            'seller_created',
            "Nouveau vendeur créé : {$user->name}",
            'User',
            $user->id
        );

        // Envoyer l'email de bienvenue
        $emailWarning = null;

        try {
            $setupUrl = app(PasswordSetupLinkService::class)->createUrl($user);
            $user->notify(new UserCreatedNotification($setupUrl, auth()->user()));
            $user->notify(new Enable2FANotification);
        } catch (Throwable $exception) {
            Log::warning('Unable to send seller welcome notification.', [
                'user_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);

            $emailWarning = 'Email non envoye.';
        }

        return redirect()->route('superadmin.sellers.index')
            ->with('success', $emailWarning ? 'Vendeur cree. '.$emailWarning : 'Vendeur cree.');
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(User $user)
    {
        $this->authorize('manageSellerAsSuperAdmin', $user);

        // Vérifier que l'utilisateur est bien un vendeur
        if (! $user->hasRole('seller')) {
            return redirect()->route('superadmin.sellers.index')
                ->with('error', 'Utilisateur invalide.');
        }

        $user->load('roles');
        $managers = User::role('manager')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $reassignmentHistory = ActivityLog::with('user')
            ->where('action', 'seller_reassigned')
            ->where('model', 'User')
            ->where('model_id', $user->id)
            ->latest()
            ->take(5)
            ->get();
        $pendingCashClosure = CashRegisterClosure::where('seller_id', $user->id)
            ->whereNull('opened_at')
            ->whereDate('business_date', today())
            ->latest('closed_at')
            ->first();
        $openCashRegister = app(CashRegisterService::class)->openRegisterForSeller($user);
        $cashRegisterBalance = app(CashRegisterService::class)->balanceForSeller($user);

        return view('superadmin.sellers.edit', compact(
            'user',
            'managers',
            'reassignmentHistory',
            'pendingCashClosure',
            'openCashRegister',
            'cashRegisterBalance'
        ));
    }

    /**
     * Mettre à jour un vendeur
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('manageSellerAsSuperAdmin', $user);

        $this->normalizeContactInputs($request);

        // Vérifier que l'utilisateur est bien un vendeur
        if (! $user->hasRole('seller')) {
            return redirect()->route('superadmin.sellers.index')
                ->with('error', 'Utilisateur invalide.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => $this->strictEmailRules('unique:users,email,'.$user->id),
            'phone' => $this->phoneRules(),
            'manager_id' => ['required', 'exists:users,id'],
            'is_active' => ['boolean'],
        ], $this->contactValidationMessages());

        $manager = User::role('manager')->find($validated['manager_id']);

        if (! $manager) {
            return back()->withErrors(['manager_id' => 'Gerant invalide.'])->withInput();
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => $request->has('is_active'),
            'created_by' => $manager->id,
        ]);

        ActivityLog::log(
            'seller_updated',
            "Vendeur modifié : {$user->name}",
            'User',
            $user->id
        );

        return redirect()->route('superadmin.sellers.index')
            ->with('success', 'Vendeur mis a jour.');
    }

    /**
     * Reaffecter un vendeur a un autre gerant
     */
    public function reassignManager(Request $request, User $user)
    {
        $this->authorize('manageSellerAsSuperAdmin', $user);

        if (! $user->hasRole('seller')) {
            return redirect()->route('superadmin.sellers.index')
                ->with('error', 'Utilisateur invalide.');
        }

        $validated = $request->validate([
            'manager_id' => ['required', 'exists:users,id'],
            'reason' => ['required', 'string', 'min:8', 'max:500'],
        ]);

        $newManager = User::role('manager')->where('is_active', true)->find($validated['manager_id']);

        if (! $newManager) {
            return back()->withErrors(['manager_id' => 'Gerant invalide ou inactif.'])->withInput();
        }

        if ((int) $user->created_by === (int) $newManager->id) {
            return back()->withErrors(['manager_id' => 'Vendeur deja rattache a ce gerant.'])->withInput();
        }

        $previousManager = User::find($user->created_by);

        DB::transaction(function () use ($user, $newManager, $previousManager, $validated) {
            $user->update([
                'created_by' => $newManager->id,
            ]);

            ActivityLog::log(
                'seller_reassigned',
                "Vendeur reaffecte : {$user->name}",
                'User',
                $user->id,
                [
                    'seller_id' => $user->id,
                    'seller_name' => $user->name,
                    'previous_manager_id' => $previousManager?->id,
                    'previous_manager_name' => $previousManager?->name,
                    'new_manager_id' => $newManager->id,
                    'new_manager_name' => $newManager->name,
                    'reason' => $validated['reason'],
                ]
            );
        });

        return redirect()->route('superadmin.sellers.edit', $user)
            ->with('success', 'Vendeur reaffecte.');
    }

    public function closeCashRegister(Request $request, User $user, CashRegisterService $cashRegisterService)
    {
        $this->authorize('manageSellerAsSuperAdmin', $user);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:8', 'max:500'],
        ]);

        $pendingClosure = $cashRegisterService->pendingClosureForSeller($user);

        $redirect = $request->input('redirect_to') === 'index'
            ? redirect()->route('superadmin.sellers.index')
            : redirect()->route('superadmin.sellers.edit', $user);

        if ($pendingClosure || ! $cashRegisterService->isOpenForSeller($user)) {
            return $redirect
                ->with('warning', 'La caisse de ce vendeur n\'est pas ouverte.');
        }

        $closure = $cashRegisterService->closeForSeller($user, null, 'super_admin', $validated['reason']);

        Cache::put(
            'seller_cash_register_closed_notice:'.$user->id,
            'Ton gerant a cloture ta caisse. Les ventes sont bloquees jusqu a la prochaine ouverture.',
            now()->addHours(12)
        );

        ActivityLog::log(
            'seller_cash_register_force_closed',
            "Caisse fermee a distance pour {$user->name}",
            'CashRegisterClosure',
            $closure->id,
            [
                'seller_id' => $user->id,
                'seller_name' => $user->name,
                'reason' => $validated['reason'],
                'amount' => (float) $closure->amount,
            ]
        );

        return $redirect
            ->with('success', 'Caisse cloturee.');
    }

    /**
     * Supprimer un vendeur
     */
    public function destroy(User $user)
    {
        $this->authorize('deleteAsSuperAdmin', $user);
        $this->authorize('manageSellerAsSuperAdmin', $user);

        // Vérifier que l'utilisateur est bien un vendeur
        if (! $user->hasRole('seller')) {
            return redirect()->route('superadmin.sellers.index')
                ->with('error', 'Utilisateur invalide.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Action non autorisee.');
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
            ->with('success', 'Vendeur supprime.');
    }
}



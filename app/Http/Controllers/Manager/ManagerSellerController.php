<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CashBalanceAdjustment;
use App\Models\CashRegisterClosure;
use App\Models\User;
use App\Notifications\Enable2FANotification;
use App\Notifications\PasswordResetNotification;
use App\Notifications\UserCreatedNotification;
use App\Services\CashRegisterService;
use App\Services\PasswordSetupLinkService;
use App\Services\SessionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

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
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        $sellers = $query->latest()->get();
        $sellerIds = User::role('seller')
            ->where('created_by', auth()->id())
            ->pluck('id');
        $cashBalances = $this->cashBalancesForSellers($sellerIds);
        $mobileMoneyBalances = $this->mobileMoneyBalancesForSellers($sellerIds);
        $cashRegisterClosures = CashRegisterClosure::with(['seller', 'closedByUser', 'openedByUser'])
            ->whereIn('seller_id', $sellerIds)
            ->latest('closed_at')
            ->take(12)
            ->get();
        $closedCashRegisterSellerIds = CashRegisterClosure::whereIn('seller_id', $sellerIds)
            ->whereNull('opened_at')
            ->whereDate('business_date', today())
            ->pluck('seller_id')
            ->all();
        $openCashRegisterSellerIds = CashRegisterClosure::whereIn('seller_id', $sellerIds)
            ->whereNotNull('opened_at')
            ->whereDate('business_date', today())
            ->pluck('seller_id')
            ->all();

        return view('manager.sellers.index', compact('sellers', 'cashBalances', 'mobileMoneyBalances', 'cashRegisterClosures', 'closedCashRegisterSellerIds', 'openCashRegisterSellerIds'));
    }

    /**
     * Afficher les détails d'un vendeur
     */
    public function show(User $user)
    {
        // Vérifier que le vendeur appartient bien au manager et a le rôle seller
        $this->authorize('manageSeller', $user);

        // Charger les relations nécessaires
        $user->load([
            'sales' => function ($query) {
                $query->latest()->limit(10);
            },
            'activityLogs' => function ($query) {
                $query->latest()->limit(10);
            },
            'creator',
        ]);

        $cashRegisterService = app(CashRegisterService::class);
        $recentCashAdjustments = CashBalanceAdjustment::with('manager')
            ->where('seller_id', $user->id)
            ->latest()
            ->take(8)
            ->get();

        // Statistiques du vendeur
        $stats = [
            'total_sales' => $user->sales()->count(),
            'total_revenue' => $user->sales()->sum('total'),
            'today_sales' => $user->sales()->whereDate('created_at', today())->count(),
            'today_revenue' => $user->sales()->whereDate('created_at', today())->sum('total'),
            'this_month_sales' => $user->sales()->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(),
            'this_month_revenue' => $user->sales()->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->sum('total'),
            'cash_balance' => $cashRegisterService->balanceForSeller($user),
            'mobile_money_balance' => $cashRegisterService->mobileMoneyBalanceForSeller($user),
        ];

        return view('manager.sellers.show', compact('user', 'stats', 'recentCashAdjustments'));
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
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => auth()->id(),
        ]);

        $user->assignRole('seller');

        ActivityLog::log(
            'seller_created',
            "Vendeur créé : {$user->name} par le gérant ".auth()->user()->name,
            'User',
            $user->id
        );

        // Envoyer les emails de notification
        $setupUrl = app(PasswordSetupLinkService::class)->createUrl($user);
        $user->notify(new UserCreatedNotification($setupUrl, auth()->user()));
        $user->notify(new Enable2FANotification);

        return redirect()->route('manager.sellers.index')
            ->with('success', 'Vendeur cree.');
    }

    /**
     * Afficher le formulaire d'édition d'un vendeur
     */
    public function edit(User $user)
    {
        // Vérifier que le vendeur appartient bien au manager
        $this->authorize('manageSeller', $user);

        // Vérifier que c'est bien un vendeur
        return view('manager.sellers.edit', compact('user'));
    }

    /**
     * Mettre à jour un vendeur
     */
    public function update(Request $request, User $user)
    {
        // Vérifier que le vendeur appartient bien au manager
        $this->authorize('manageSeller', $user);
        $this->normalizeContactInputs($request);

        // Vérifier que c'est bien un vendeur
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => $this->strictEmailRules('unique:users,email,'.$user->id),
            'phone' => $this->phoneRules(),
            'is_active' => ['boolean'],
        ], $this->contactValidationMessages());

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
            ->with('success', 'Vendeur mis a jour.');
    }

    /**
     * Supprimer un vendeur
     */
    public function destroy(User $user)
    {
        // Vérifier que le vendeur appartient bien au manager
        $this->authorize('manageSeller', $user);

        // Vérifier que c'est bien un vendeur
        $userName = $user->name;

        ActivityLog::log(
            'seller_deleted',
            "Vendeur supprimé : {$userName}",
            'User',
            $user->id
        );

        $user->delete();

        return redirect()->route('manager.sellers.index')
            ->with('success', 'Vendeur supprime.');
    }

    /**
     * Afficher les vendeurs en ligne
     */
    public function onlineSellers()
    {
        $onlineSellers = SessionManager::getOnlineUsers('seller')
            ->where('created_by', auth()->id())
            ->values();

        return view('manager.sellers.online', compact('onlineSellers'));
    }

    /**
     * Réinitialiser le mot de passe d'un vendeur
     */
    public function resetPassword(User $user)
    {
        // Vérifier que le vendeur appartient bien au manager
        $this->authorize('manageSeller', $user);

        $resetUrl = app(PasswordSetupLinkService::class)->createUrl($user);

        ActivityLog::log(
            'password_reset',
            "Mot de passe réinitialisé pour : {$user->name}",
            'User',
            $user->id
        );

        $user->notify(new PasswordResetNotification($resetUrl));

        return back()->with('success', 'Lien de reinitialisation envoye.');
    }

    /**
     * Activer/désactiver un vendeur
     */
    public function toggleStatus(User $user)
    {
        // Vérifier que le vendeur appartient bien au manager
        $this->authorize('manageSeller', $user);

        $newStatus = ! $user->is_active;
        $user->update(['is_active' => $newStatus]);

        ActivityLog::log(
            'seller_status_changed',
            "Statut changé pour {$user->name} : ".($newStatus ? 'activé' : 'désactivé'),
            'User',
            $user->id
        );

        return back()->with('success', $newStatus ? 'Vendeur active.' : 'Vendeur desactive.');
    }

    /**
     * Forcer la déconnexion d'un vendeur
     */
    public function forceLogout(User $user)
    {
        // Vérifier que le vendeur appartient bien au manager
        $this->authorize('manageSeller', $user);

        $isOnline = SessionManager::isUserOnline($user->id);

        if (! $isOnline) {
            return back()->with('error', 'Vendeur hors ligne.');
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

        return back()->with('success', 'Vendeur deconnecte.');
    }

    public function closeCashRegister(User $user, CashRegisterService $cashRegisterService)
    {
        $this->authorize('manageSeller', $user);

        if (! $cashRegisterService->isOpenForSeller($user)) {
            return back()->with(
                'warning',
                'La caisse de ce vendeur n\'est pas ouverte.'
            );
        }

        $closure = $cashRegisterService->closeForSeller(
            $user,
            null,
            'manager',
            'Cloture forcee par le gerant '.auth()->user()->name
        );

        Cache::put(
            'seller_cash_register_closed_notice:'.$user->id,
            'Ton gerant a cloture ta caisse. Les ventes sont bloquees jusqu a la prochaine ouverture.',
            now()->addHours(12)
        );

        return back()->with(
            'success',
            'Caisse cloturee.'
        );
    }

    public function adjustCashBalance(Request $request, User $user, CashRegisterService $cashRegisterService)
    {
        $this->authorize('manageSeller', $user);

        $validated = $request->validate([
            'type' => ['required', 'in:add,withdraw'],
            'balance_type' => ['required', 'in:cash,mobile_money'],
            'amount' => ['required', 'numeric', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['type'] === 'withdraw') {
            $request->validate([
                'reason' => ['required', 'string', 'max:255'],
            ]);
        }

        try {
            $cashRegisterService->adjustBalance(
                $user,
                auth()->user(),
                $validated['type'],
                (float) $validated['amount'],
                $validated['reason'] ?? null,
                $validated['balance_type']
            );
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage())->withInput();
        }

        $balanceLabel = $validated['balance_type'] === 'mobile_money' ? 'Paiements mobiles' : 'Cash';

        return back()->with('success', $validated['type'] === 'add' ? "Fonds {$balanceLabel} ajoutes." : "Fonds {$balanceLabel} retires.");
    }

    private function cashBalancesForSellers($sellerIds)
    {
        $cashRegisterService = app(CashRegisterService::class);
        $sellers = User::whereIn('id', $sellerIds)->get()->keyBy('id');

        return $sellerIds->mapWithKeys(function ($sellerId) use ($cashRegisterService, $sellers) {
            return [
                $sellerId => $sellers->has($sellerId)
                    ? $cashRegisterService->balanceForSeller($sellers[$sellerId])
                    : 0,
            ];
        });
    }

    private function mobileMoneyBalancesForSellers($sellerIds)
    {
        $cashRegisterService = app(CashRegisterService::class);
        $sellers = User::whereIn('id', $sellerIds)->get()->keyBy('id');

        return $sellerIds->mapWithKeys(function ($sellerId) use ($cashRegisterService, $sellers) {
            return [
                $sellerId => $sellers->has($sellerId)
                    ? $cashRegisterService->mobileMoneyBalanceForSeller($sellers[$sellerId])
                    : 0,
            ];
        });
    }
}


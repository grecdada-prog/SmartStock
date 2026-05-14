<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\CashRegisterClosure;
use App\Services\PasswordSetupLinkService;
use App\Services\CashRegisterService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use App\Notifications\UserCreatedNotification;
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users', 'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'],
            'phone' => ['nullable', 'regex:/^[0-9]{9,15}$/'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()],
            'manager_id' => ['required', 'exists:users,id'],
            'is_active' => ['boolean'],
        ], [
            'email.regex' => 'Le format de l\'email est invalide.',
            'phone.regex' => 'Le téléphone doit contenir uniquement des chiffres (9-15 caractères).',
        ]);

        $manager = User::role('manager')->find($validated['manager_id']);

        if (!$manager) {
            return back()->withErrors(['manager_id' => 'Gérant invalide.'])->withInput();
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
        } catch (Throwable $exception) {
            Log::warning('Unable to send seller welcome notification.', [
                'user_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);

            $emailWarning = 'Le vendeur a ete cree, mais l email de bienvenue n a pas pu etre envoye.';
        }

        return redirect()->route('superadmin.sellers.index')
            ->with('success', 'Vendeur cree avec succes !'.($emailWarning ? ' '.$emailWarning : ' Un email de bienvenue a ete envoye.'));
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(User $user)
    {
        $this->authorize('manageSellerAsSuperAdmin', $user);

        // Vérifier que l'utilisateur est bien un vendeur
        if (!$user->hasRole('seller')) {
            return redirect()->route('superadmin.sellers.index')
                ->with('error', 'Cet utilisateur n\'est pas un vendeur.');
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
            ->latest('closed_at')
            ->first();
        $cashRegisterBalance = app(CashRegisterService::class)->balanceForSeller($user);

        return view('superadmin.sellers.edit', compact(
            'user',
            'managers',
            'reassignmentHistory',
            'pendingCashClosure',
            'cashRegisterBalance'
        ));
    }

    /**
     * Mettre à jour un vendeur
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('manageSellerAsSuperAdmin', $user);

        // Vérifier que l'utilisateur est bien un vendeur
        if (!$user->hasRole('seller')) {
            return redirect()->route('superadmin.sellers.index')
                ->with('error', 'Cet utilisateur n\'est pas un vendeur.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id, 'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'],
            'phone' => ['nullable', 'regex:/^[0-9]{9,15}$/'],
            'manager_id' => ['required', 'exists:users,id'],
            'is_active' => ['boolean'],
        ], [
            'email.regex' => 'Le format de l\'email est invalide.',
            'phone.regex' => 'Le téléphone doit contenir uniquement des chiffres (9-15 caractères).',
        ]);

        $manager = User::role('manager')->find($validated['manager_id']);

        if (!$manager) {
            return back()->withErrors(['manager_id' => 'Gérant invalide.'])->withInput();
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
            ->with('success', 'Vendeur modifié avec succès !');
    }

    /**
     * Reaffecter un vendeur a un autre gerant
     */
    public function reassignManager(Request $request, User $user)
    {
        $this->authorize('manageSellerAsSuperAdmin', $user);

        if (!$user->hasRole('seller')) {
            return redirect()->route('superadmin.sellers.index')
                ->with('error', 'Cet utilisateur n\'est pas un vendeur.');
        }

        $validated = $request->validate([
            'manager_id' => ['required', 'exists:users,id'],
            'reason' => ['required', 'string', 'min:8', 'max:500'],
        ]);

        $newManager = User::role('manager')->where('is_active', true)->find($validated['manager_id']);

        if (!$newManager) {
            return back()->withErrors(['manager_id' => 'Gerant invalide ou inactif.'])->withInput();
        }

        if ((int) $user->created_by === (int) $newManager->id) {
            return back()->withErrors(['manager_id' => 'Ce vendeur est deja rattache a ce gerant.'])->withInput();
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
            ->with('success', "Le vendeur {$user->name} est maintenant rattache a {$newManager->name}.");
    }

    public function closeCashRegister(Request $request, User $user, CashRegisterService $cashRegisterService)
    {
        $this->authorize('manageSellerAsSuperAdmin', $user);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:8', 'max:500'],
        ]);

        $pendingClosure = $cashRegisterService->pendingClosureForSeller($user);

        if ($pendingClosure) {
            return redirect()->route('superadmin.sellers.edit', $user)
                ->with('warning', 'La caisse de '.$user->name.' est deja fermee depuis le '.$pendingClosure->closed_at->format('d/m/Y').' a '.$pendingClosure->closed_at->format('H:i').'. Aucune nouvelle cloture n a ete effectuee.');
        }

        $closure = $cashRegisterService->closeForSeller($user, null, 'super_admin', $validated['reason']);

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

        return redirect()->route('superadmin.sellers.edit', $user)
            ->with('success', "Caisse fermee pour {$user->name}. ".number_format((float) $closure->amount, 0, ',', ' ')." FCFA transferes au Solde Cash.");
    }

    public function openCashRegister(Request $request, User $user, CashRegisterService $cashRegisterService)
    {
        $this->authorize('manageSellerAsSuperAdmin', $user);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:8', 'max:500'],
        ]);

        $closure = $cashRegisterService->openForSeller($user, null, 'super_admin', $validated['reason']);

        if (!$closure) {
            return back()->with('error', 'Aucune caisse fermee a rouvrir pour ce vendeur.');
        }

        ActivityLog::log(
            'seller_cash_register_force_opened',
            "Caisse rouverte a distance pour {$user->name}",
            'CashRegisterClosure',
            $closure->id,
            [
                'seller_id' => $user->id,
                'seller_name' => $user->name,
                'reason' => $validated['reason'],
            ]
        );

        return redirect()->route('superadmin.sellers.edit', $user)
            ->with('success', "Caisse rouverte pour {$user->name}. Le vendeur peut reprendre les ventes.");
    }

    /**
     * Supprimer un vendeur
     */
    public function destroy(User $user)
    {
        $this->authorize('deleteAsSuperAdmin', $user);
        $this->authorize('manageSellerAsSuperAdmin', $user);

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

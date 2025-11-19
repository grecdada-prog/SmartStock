<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SellerController extends Controller
{
    /**
     * Display a listing of sellers
     */
    public function index()
    {
        $sellers = User::where('role', 'vendeur')->latest()->get();
        return view('admin.sellers.index', compact('sellers'));
    }

    /**
     * Show the form for creating a new seller
     */
    public function create()
    {
        return view('admin.sellers.create');
    }

    /**
     * Store a newly created seller
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $seller = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'vendeur',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.sellers.index')
            ->with('success', 'Compte vendeur créé avec succès !');
    }

    /**
     * Display the specified seller
     */
    public function show(User $seller)
    {
        // Vérifier que c'est bien un vendeur
        if ($seller->role !== 'vendeur') {
            abort(404);
        }

        return view('admin.sellers.show', compact('seller'));
    }

    /**
     * Show the form for editing the specified seller
     */
    public function edit(User $seller)
    {
        // Vérifier que c'est bien un vendeur
        if ($seller->role !== 'vendeur') {
            abort(404);
        }

        return view('admin.sellers.edit', compact('seller'));
    }

    /**
     * Update the specified seller
     */
    public function update(Request $request, User $seller)
    {
        // Vérifier que c'est bien un vendeur
        if ($seller->role !== 'vendeur') {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $seller->id],
            'is_active' => ['boolean'],
        ]);

        $seller->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $request->has('is_active'),
        ]);

        // Si un nouveau mot de passe est fourni
        if ($request->filled('password')) {
            $request->validate([
                'password' => ['required', 'confirmed', Password::defaults()],
            ]);

            $seller->update([
                'password' => Hash::make($request->password),
            ]);
        }

        return redirect()->route('admin.sellers.index')
            ->with('success', 'Compte vendeur mis à jour avec succès !');
    }

    /**
     * Toggle seller active status
     */
    public function toggleStatus(User $seller)
    {
        // Vérifier que c'est bien un vendeur
        if ($seller->role !== 'vendeur') {
            abort(404);
        }

        $seller->update([
            'is_active' => !$seller->is_active,
        ]);

        $status = $seller->is_active ? 'activé' : 'désactivé';
        
        return redirect()->route('admin.sellers.index')
            ->with('success', "Compte vendeur {$status} avec succès !");
    }

    /**
     * Remove the specified seller
     */
    public function destroy(User $seller)
    {
        // Vérifier que c'est bien un vendeur
        if ($seller->role !== 'vendeur') {
            abort(404);
        }

        $seller->delete();

        return redirect()->route('admin.sellers.index')
            ->with('success', 'Compte vendeur supprimé avec succès !');
    }
}
<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class Dashboard extends Component
{
    public $totalProducts = 0;
    public $todaySales = 0;
    public $lowStockProducts = 0;
    public $activeSellers = 0;
    public $totalSellers = 0;

    // Rafraîchir automatiquement toutes les 30 secondes
    protected $listeners = ['refreshDashboard' => '$refresh'];

    public function mount()
    {
        $this->loadStats();
    }

    public function loadStats()
    {
        // Nombre total de produits
        $this->totalProducts = Product::count();

        // Ventes du jour en FCFA
        $this->todaySales = Sale::whereDate('created_at', today())
            ->sum('total_amount');

        // Produits avec stock faible (moins de 10)
        $this->lowStockProducts = Product::where('quantity', '<', 10)
            ->where('quantity', '>', 0)
            ->count();

        // Vendeurs actuellement connectés (sessions actives et compte actif)
        $this->activeSellers = \App\Models\UserSession::where('is_active', true)
            ->whereHas('user', function ($query) {
                $query->where('role', 'vendeur')
                    ->where('is_active', true);
            })
            ->distinct('user_id')
            ->count();

        // Total vendeurs actifs (pas les désactivés)
        $this->totalSellers = User::where('role', 'vendeur')
            ->where('is_active', true)
            ->count();
    }

    public function render()
    {
        return view('livewire.admin.dashboard');
    }
}
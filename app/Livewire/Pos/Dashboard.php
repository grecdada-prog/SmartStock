<?php

namespace App\Livewire\Pos;

use Livewire\Component;
use App\Models\Sale;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    public $mySalesToday = 0;
    public $myTransactionsCount = 0;
    public $availableProducts = 0;

    // Rafraîchir automatiquement
    protected $listeners = ['refreshDashboard' => '$refresh'];

    public function mount()
    {
        $this->loadStats();
    }

    public function loadStats()
    {
        // Mes ventes aujourd'hui
        $this->mySalesToday = Sale::where('user_id', Auth::id())
            ->whereDate('created_at', today())
            ->sum('total_amount');

        // Nombre de mes transactions aujourd'hui
        $this->myTransactionsCount = Sale::where('user_id', Auth::id())
            ->whereDate('created_at', today())
            ->count();

        // Produits disponibles en stock
        $this->availableProducts = Product::where('quantity', '>', 0)->count();
    }

    public function render()
    {
        return view('livewire.pos.dashboard');
    }
}
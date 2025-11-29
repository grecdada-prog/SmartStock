<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Category;
use App\Services\SessionManager;
use Illuminate\Support\Facades\DB;

class ManagerDashboardController extends Controller
{
    public function index()
    {
        // Statistiques
        $stats = [
            'total_sellers' => User::role('seller')->count(),
            'active_sellers' => User::role('seller')->where('is_active', true)->count(),
            'online_sellers' => SessionManager::getOnlineUsers('seller')->count(),
            'total_products' => Product::count(),
            'low_stock_products' => Product::lowStock()->count(),
            'total_categories' => Category::count(),
            'total_sales' => Sale::count(),
            'total_revenue' => Sale::sum('total'),
            'today_sales' => Sale::whereDate('created_at', today())->count(),
            'today_revenue' => Sale::whereDate('created_at', today())->sum('total'),
        ];

        // Vendeurs en ligne
        $onlineSellers = SessionManager::getOnlineUsers('seller');

        // Top vendeurs du mois
        $topSellers = User::role('seller')
            ->withCount(['sales' => function($query) {
                $query->whereMonth('created_at', now()->month);
            }])
            ->withSum(['sales' => function($query) {
                $query->whereMonth('created_at', now()->month);
            }], 'total')
            ->orderBy('sales_sum_total', 'desc')
            ->take(5)
            ->get();

        // Produits en stock faible
        $lowStockProducts = Product::lowStock()->take(5)->get();

        return view('manager.dashboard', compact('stats', 'onlineSellers', 'topSellers', 'lowStockProducts'));
    }
}
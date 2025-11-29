<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class SellerDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Statistiques personnelles
        $stats = [
            'today_sales' => Sale::where('seller_id', $user->id)
                ->whereDate('created_at', today())
                ->count(),
            'today_revenue' => Sale::where('seller_id', $user->id)
                ->whereDate('created_at', today())
                ->sum('total'),
            'month_sales' => Sale::where('seller_id', $user->id)
                ->whereMonth('created_at', now()->month)
                ->count(),
            'month_revenue' => Sale::where('seller_id', $user->id)
                ->whereMonth('created_at', now()->month)
                ->sum('total'),
            'total_sales' => Sale::where('seller_id', $user->id)->count(),
            'total_revenue' => Sale::where('seller_id', $user->id)->sum('total'),
            'available_products' => Product::where('quantity', '>', 0)->count(),
        ];

        // Dernières ventes
        $recentSales = Sale::where('seller_id', $user->id)
            ->with('items.product')
            ->latest()
            ->take(5)
            ->get();

        return view('seller.dashboard', compact('stats', 'recentSales'));
    }

    public function products()
    {
        $products = Product::with('category')
            ->where('is_active', true)
            ->paginate(20);

        return view('seller.products', compact('products'));
    }

    public function showProduct($id)
    {
        $product = Product::with('category')->findOrFail($id);
        return view('seller.products.show', compact('product'));
    }
}
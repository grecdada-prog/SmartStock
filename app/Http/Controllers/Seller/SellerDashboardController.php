<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\CashRegisterClosure;
use App\Models\Product;
use App\Models\Sale;
use App\Services\CashRegisterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SellerDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $todayClosure = CashRegisterClosure::where('seller_id', $user->id)
            ->whereDate('business_date', today())
            ->first();

        $pendingClosure = CashRegisterClosure::where('seller_id', $user->id)
            ->whereNull('opened_at')
            ->latest('closed_at')
            ->first();

        $lastOpenedClosure = CashRegisterClosure::where('seller_id', $user->id)
            ->whereNotNull('opened_at')
            ->latest('opened_at')
            ->first();

        $currentSessionOpenedAt = $todayClosure?->opened_at;

        if (!$currentSessionOpenedAt && $lastOpenedClosure?->opened_at?->isToday()) {
            $currentSessionOpenedAt = $lastOpenedClosure->opened_at;
        }

        $todaySalesQuery = Sale::where('seller_id', $user->id)
            ->whereDate('created_at', today());

        if ($pendingClosure) {
            $todaySalesQuery->whereRaw('1 = 0');
        } elseif ($currentSessionOpenedAt) {
            $todaySalesQuery->where('created_at', '>=', $currentSessionOpenedAt);
        }

        $todayCashRevenue = Sale::where('seller_id', $user->id)
            ->whereDate('created_at', today());

        if ($pendingClosure) {
            $todayCashRevenue->whereRaw('1 = 0');
        } elseif ($currentSessionOpenedAt) {
            $todayCashRevenue->where('created_at', '>=', $currentSessionOpenedAt);
        }

        $yesterdayClosure = CashRegisterClosure::where('seller_id', $user->id)
            ->whereDate('business_date', today()->subDay())
            ->first();

        $yesterdayCashSalesQuery = Sale::where('seller_id', $user->id)
            ->whereDate('created_at', today()->subDay());

        $stats = [
            'today_cash_sales' => (clone $todaySalesQuery)->count(),
            'today_revenue' => (clone $todayCashRevenue)->sum('total'),
            'yesterday_cash_sales' => $yesterdayClosure
                ? null
                : (clone $yesterdayCashSalesQuery)->count(),
            'yesterday_revenue' => $yesterdayClosure?->amount
                ?? (clone $yesterdayCashSalesQuery)->sum('total'),
            'cash_balance' => app(CashRegisterService::class)->balanceForSeller($user),
            'cash_register_closed_today' => $pendingClosure !== null,
            'cash_register_closed_at' => $pendingClosure?->closed_at,
            'cash_register_closed_business_date' => $pendingClosure?->business_date,
            'cash_register_opened_at' => $currentSessionOpenedAt,
        ];

        return view('seller.dashboard', compact('stats'));
    }

    public function closeCashRegister(CashRegisterService $cashRegisterService)
    {
        $seller = Auth::user();
        $pendingClosure = $cashRegisterService->pendingClosureForSeller($seller);

        if ($pendingClosure) {
            return back()->with(
                'warning',
                'Caisse deja fermee.'
            );
        }

        $closure = $cashRegisterService->closeForSeller($seller);

        return back()->with(
            'success',
            'Caisse cloturee.'
        );
    }

    public function openCashRegister(CashRegisterService $cashRegisterService)
    {
        $cashRegisterService->openForSeller(Auth::user());

        return back()->with('success', 'Caisse ouverte.');
    }

    public function products(Request $request)
    {
        $query = Product::with('category')
            ->where('is_active', true)
            ->where('created_by', Auth::user()->created_by);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('sku', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->paginate(20);

        return view('seller.products.index', compact('products'));
    }

    public function showProduct($id)
    {
        $product = Product::with('category')
            ->where('is_active', true)
            ->where('created_by', Auth::user()->created_by)
            ->findOrFail($id);

        $this->authorize('view', $product);

        return view('seller.products.show', compact('product'));
    }

    public function myStats()
    {
        $user = Auth::user();
        $managerId = $user->created_by;

        $stats = [
            'today_sales' => Sale::where('seller_id', $user->id)
                ->whereDate('created_at', today())
                ->count(),
            'today_revenue' => Sale::where('seller_id', $user->id)
                ->whereDate('created_at', today())
                ->sum('total'),
            'month_sales' => Sale::where('seller_id', $user->id)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count(),
            'month_revenue' => Sale::where('seller_id', $user->id)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->sum('total'),
            'total_sales' => Sale::where('seller_id', $user->id)->count(),
            'total_revenue' => Sale::where('seller_id', $user->id)->sum('total'),
            'available_products' => Product::where('created_by', $managerId)
                ->where('is_active', true)
                ->where('quantity', '>', 0)
                ->count(),
            'low_stock_products' => Product::where('created_by', $managerId)
                ->where('is_active', true)
                ->lowStock()
                ->count(),
        ];

        $salesChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $salesChart[] = [
                'date' => now()->subDays($i)->format('d/m'),
                'sales' => Sale::where('seller_id', $user->id)
                    ->whereDate('created_at', $date)
                    ->sum('total'),
            ];
        }

        return view('seller.my-stats', compact('stats', 'salesChart'));
    }
}

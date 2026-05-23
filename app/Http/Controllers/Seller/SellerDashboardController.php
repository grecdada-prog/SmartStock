<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\CashRegisterClosure;
use App\Models\Product;
use App\Models\Sale;
use App\Services\CashRegisterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SellerDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $cashRegisterService = app(CashRegisterService::class);
        $pendingClosure = $cashRegisterService->pendingClosureForSeller($user);
        $todayCashSalesQuery = $cashRegisterService->currentDayCashSalesQuery($user);
        $todaySalesQuery = $cashRegisterService->currentDaySalesQuery($user);
        $currentSessionOpenedAt = $cashRegisterService->currentSessionOpenedAt($user);
        $cashRegisterIsOpen = $cashRegisterService->isOpenForSeller($user);
        $yesterdayClosure = CashRegisterClosure::where('seller_id', $user->id)
            ->whereDate('business_date', today()->subDay())
            ->first();

        $stats = [
            'today_sales' => (clone $todaySalesQuery)->count(),
            'today_cash_sales' => (clone $todayCashSalesQuery)->count(),
            'today_revenue' => (clone $todaySalesQuery)->sum('total'),
            'yesterday_cash_sales' => $yesterdayClosure
                ? null
                : $cashRegisterService->cashSalesCountForDate($user, today()->subDay()),
            'yesterday_sales' => Sale::where('seller_id', $user->id)->whereDate('created_at', today()->subDay())->count(),
            'yesterday_revenue' => $cashRegisterService->previousDayRevenueForSeller($user),
            'cash_balance' => $cashRegisterService->balanceForSeller($user),
            'orange_money_balance' => $cashRegisterService->orangeMoneyBalanceForSeller($user),
            'mtn_momo_balance' => $cashRegisterService->mtnMomoBalanceForSeller($user),
            'mobile_money_balance' => $cashRegisterService->mobileMoneyBalanceForSeller($user),
            'cash_register_closed_today' => $pendingClosure !== null,
            'cash_register_is_open' => $cashRegisterIsOpen,
            'cash_register_closed_at' => $pendingClosure?->closed_at,
            'cash_register_closed_business_date' => $pendingClosure?->business_date,
            'cash_register_opened_at' => $currentSessionOpenedAt,
        ];

        return view('seller.dashboard', compact('stats'));
    }

    public function closeCashRegister(CashRegisterService $cashRegisterService)
    {
        $seller = Auth::user();
        if (! $cashRegisterService->isOpenForSeller($seller)) {
            return back()->with(
                'warning',
                $cashRegisterService->pendingClosureForSeller($seller)
                    ? 'La caisse est deja cloturee.'
                    : 'Ouvrez la caisse avant de la cloturer.'
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

    public function acknowledgeManagerClosureNotice()
    {
        Cache::forget('seller_cash_register_closed_notice:'.Auth::id());

        return response()->noContent();
    }

    public function products(Request $request)
    {
        $query = Product::with('category')
            ->where('is_active', true)
            ->where('created_by', Auth::user()->created_by);

        if ($request->filled('search')) {
            $search = $request->search;
            $barcodeSearch = preg_replace('/\D+/', '', $search);

            $query->where(function ($q) use ($search, $barcodeSearch) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('barcode', 'like', '%'.$search.'%');

                if ($barcodeSearch !== '') {
                    $q->orWhereRaw("REPLACE(barcode, ' ', '') like ?", ['%'.$barcodeSearch.'%']);
                }
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->get();

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


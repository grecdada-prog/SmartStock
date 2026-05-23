<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Category;
use App\Models\ActivityLog;
use App\Services\CashRegisterService;
use App\Services\SessionManager;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ManagerSalesExport;
use Barryvdh\DomPDF\Facade\Pdf;

class ManagerDashboardController extends Controller
{
    public function index()
    {
        $managerId = auth()->id();
        $categoryOwnerIds = User::role('super_admin')->pluck('id')->push($managerId);
        $sellerIds = User::role('seller')
            ->where('created_by', $managerId)
            ->pluck('id');

        $cashRegisterService = app(CashRegisterService::class);
        $sellers = User::role('seller')
            ->where('created_by', $managerId)
            ->orderBy('name')
            ->get();

        $sellerFinancials = $sellers->map(function (User $seller) use ($cashRegisterService) {
            return [
                'seller' => $seller,
                'cash_balance' => $cashRegisterService->balanceForSeller($seller),
                'orange_money_balance' => $cashRegisterService->orangeMoneyBalanceForSeller($seller),
                'mtn_momo_balance' => $cashRegisterService->mtnMomoBalanceForSeller($seller),
                'mobile_money_balance' => $cashRegisterService->mobileMoneyBalanceForSeller($seller),
                'today_revenue' => $cashRegisterService->currentDayCashRevenueForSeller($seller),
                'yesterday_revenue' => $cashRegisterService->previousDayRevenueForSeller($seller),
            ];
        });

        // Statistiques
        $stats = [
            'total_sellers' => User::role('seller')->where('created_by', $managerId)->count(),
            'active_sellers' => User::role('seller')->where('created_by', $managerId)->where('is_active', true)->count(),
            'online_sellers' => SessionManager::getOnlineUsers('seller')->where('created_by', $managerId)->count(),
            'low_stock_products' => Product::where('created_by', $managerId)->lowStock()->count(),
            'total_categories' => Category::whereIn('created_by', $categoryOwnerIds)->count(),
            'total_sales' => Sale::whereIn('seller_id', $sellerIds)->count(),
            'total_revenue' => Sale::whereIn('seller_id', $sellerIds)->sum('total'),
            'today_sales' => Sale::whereIn('seller_id', $sellerIds)->whereDate('created_at', today())->count(),
            'today_revenue' => Sale::whereIn('seller_id', $sellerIds)->whereDate('created_at', today())->sum('total'),
            'total_cash_balance' => $sellerFinancials->sum('cash_balance'),
            'total_orange_money_balance' => $sellerFinancials->sum('orange_money_balance'),
            'total_mtn_momo_balance' => $sellerFinancials->sum('mtn_momo_balance'),
            'total_mobile_money_balance' => $sellerFinancials->sum('mobile_money_balance'),
            'total_current_day_revenue' => $sellerFinancials->sum('today_revenue'),
            'total_yesterday_revenue' => $sellerFinancials->sum('yesterday_revenue'),
            'yesterday_sales' => Sale::whereIn('seller_id', $sellerIds)->whereDate('created_at', today()->subDay())->count(),
        ];

        // Vendeurs en ligne
        $onlineSellers = SessionManager::getOnlineUsers('seller')
            ->where('created_by', $managerId)
            ->values();

        // Top vendeurs du mois
        $topSellers = User::role('seller')
            ->where('created_by', $managerId)
            ->withCount(['sales' => function($query) {
                $query->whereYear('created_at', now()->year);
                $query->whereMonth('created_at', now()->month);
            }])
            ->withSum(['sales' => function($query) {
                $query->whereYear('created_at', now()->year);
                $query->whereMonth('created_at', now()->month);
            }], 'total')
            ->orderBy('sales_sum_total', 'desc')
            ->take(5)
            ->get();

        // Produits en stock faible
        $lowStockProducts = Product::where('created_by', $managerId)
            ->active()
            ->lowStock()
            ->take(5)
            ->get();

        return view('manager.dashboard', compact('stats', 'onlineSellers', 'topSellers', 'lowStockProducts', 'sellerFinancials'));
    }

    /**
     * Liste des ventes réalisées par les vendeurs du manager
     */
    public function sales(Request $request)
    {
        // Récupérer les IDs des vendeurs créés par ce manager
        $sellerIds = User::role('seller')
            ->where('created_by', auth()->id())
            ->pluck('id');

        $query = Sale::with(['seller', 'items.product'])
            ->whereIn('seller_id', $sellerIds);

        // Filtres
        if ($request->filled('seller_id')) {
            $query->where('seller_id', $request->seller_id);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $query->where('invoice_number', 'like', '%' . $request->search . '%');
        }

        $filteredSalesQuery = clone $query;
        $sales = $query->latest()->get();
        $sellerFinancials = $this->sellerFinancials();

        // Statistiques des ventes
        $stats = [
            'filtered_sales' => (clone $filteredSalesQuery)->count(),
            'filtered_revenue' => (float) (clone $filteredSalesQuery)->sum('total'),
            'average_sale' => (float) (clone $filteredSalesQuery)->avg('total'),
            'total_cash_balance' => $sellerFinancials->sum('cash_balance'),
            'total_orange_money_balance' => $sellerFinancials->sum('orange_money_balance'),
            'total_mtn_momo_balance' => $sellerFinancials->sum('mtn_momo_balance'),
            'total_mobile_money_balance' => $sellerFinancials->sum('mobile_money_balance'),
            'total_current_day_revenue' => $sellerFinancials->sum('today_revenue'),
            'total_yesterday_revenue' => $sellerFinancials->sum('yesterday_revenue'),
            'today_sales' => Sale::whereIn('seller_id', $sellerIds)->whereDate('created_at', today())->count(),
            'yesterday_sales' => Sale::whereIn('seller_id', $sellerIds)->whereDate('created_at', today()->subDay())->count(),
        ];

        // Liste des vendeurs pour le filtre
        $sellers = User::role('seller')
            ->where('created_by', auth()->id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('manager.sales.index', compact('sales', 'stats', 'sellers'));
    }

    private function sellerFinancials()
    {
        $cashRegisterService = app(CashRegisterService::class);

        return User::role('seller')
            ->where('created_by', auth()->id())
            ->orderBy('name')
            ->get()
            ->map(function (User $seller) use ($cashRegisterService) {
                return [
                    'seller' => $seller,
                    'cash_balance' => (float) $cashRegisterService->balanceForSeller($seller),
                    'orange_money_balance' => $cashRegisterService->orangeMoneyBalanceForSeller($seller),
                    'mtn_momo_balance' => $cashRegisterService->mtnMomoBalanceForSeller($seller),
                    'mobile_money_balance' => $cashRegisterService->mobileMoneyBalanceForSeller($seller),
                    'today_revenue' => $cashRegisterService->currentDayCashRevenueForSeller($seller),
                    'yesterday_revenue' => $cashRegisterService->previousDayRevenueForSeller($seller),
                ];
            });
    }

    /**
     * Exporter les ventes en Excel
     */
    public function exportSalesExcel(Request $request)
    {
        ActivityLog::log(
            'export_manager_sales_excel',
            'Export des ventes du Manager en Excel',
            'Sale',
            null
        );

        return Excel::download(
            new ManagerSalesExport(
                auth()->id(),
                $request->seller_id,
                $request->date_from,
                $request->date_to
            ),
            'ventes_manager_' . now()->format('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    /**
     * Exporter les ventes en PDF
     */
    public function exportSalesPdf(Request $request)
    {
        ActivityLog::log(
            'export_manager_sales_pdf',
            'Export des ventes du Manager en PDF',
            'Sale',
            null
        );

        // Récupérer les IDs des vendeurs créés par ce manager
        $sellerIds = User::role('seller')
            ->where('created_by', auth()->id())
            ->pluck('id');

        $query = Sale::with(['seller', 'items.product'])
            ->whereIn('seller_id', $sellerIds);

        // Appliquer les mêmes filtres que l'export Excel
        if ($request->filled('seller_id')) {
            $query->where('seller_id', $request->seller_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $sales = $query->latest()->get();

        // Statistiques
        $stats = [
            'total_sales' => $sales->count(),
            'filtered_revenue' => $sales->sum('total'),
            'total_items' => $sales->sum(function($sale) {
                return $sale->items->count();
            }),
        ];

        $pdf = Pdf::loadView('manager.sales.pdf', [
            'sales' => $sales,
            'stats' => $stats,
            'filters' => $request->all(),
            'manager' => auth()->user(),
        ]);

        return $pdf->download('ventes_manager_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }
}


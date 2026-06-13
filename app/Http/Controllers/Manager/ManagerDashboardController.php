<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Category;
use App\Models\ActivityLog;
use App\Models\CashBalanceAdjustment;
use App\Models\StockMovement;
use App\Services\CashRegisterService;
use App\Services\SaleCancellationService;
use App\Services\SessionManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Throwable;
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
                'today_revenue' => $cashRegisterService->currentDayRevenueForSeller($seller),
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
        ];

        // Vendeurs en ligne
        $onlineSellers = SessionManager::getOnlineUsers('seller')
            ->where('created_by', $managerId)
            ->values();

        $expiringStockMovements = StockMovement::with(['product.category'])
            ->where('type', 'in')
            ->where('remaining_quantity', '>', 0)
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '<=', today()->addDays(10))
            ->whereHas('product', fn ($query) => $query
                ->where('created_by', $managerId)
                ->where('is_active', true))
            ->orderBy('expiration_date')
            ->take(5)
            ->get();

        // Produits en stock faible
        $lowStockProducts = Product::where('created_by', $managerId)
            ->active()
            ->lowStock()
            ->take(5)
            ->get();

        $recentServiceOperations = CashBalanceAdjustment::with('seller')
            ->whereIn('seller_id', $sellerIds)
            ->where('source', 'service')
            ->where('balance_type', CashRegisterService::CASH_BALANCE_TYPE)
            ->latest()
            ->take(6)
            ->get();

        return view('manager.dashboard', compact('stats', 'onlineSellers', 'expiringStockMovements', 'lowStockProducts', 'sellerFinancials', 'recentServiceOperations'));
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

        $query = Sale::with(['seller', 'items.product', 'items.promotion', 'paymentTransactions'])
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

        if ($request->filled('product_id')) {
            $query->whereHas('items.product', function ($q) use ($request) {
                $q->where('products.id', $request->integer('product_id'))
                    ->where('products.created_by', auth()->id());
            });
        } elseif ($request->filled('product_search')) {
            $productSearch = trim((string) $request->product_search);
            $barcodeSearch = preg_replace('/\D+/', '', $productSearch);

            $query->where(function ($saleQuery) use ($productSearch, $barcodeSearch) {
                $saleQuery->whereHas('items.product', function ($q) use ($productSearch, $barcodeSearch) {
                    $q->where('name', 'like', '%'.$productSearch.'%')
                        ->orWhere('barcode', 'like', '%'.$productSearch.'%');

                    if ($barcodeSearch !== '') {
                        $q->orWhere('barcode', 'like', '%'.$barcodeSearch.'%');
                    }
                })->orWhereHas('items', function ($q) use ($productSearch) {
                    $q->where('service_name', 'like', '%'.$productSearch.'%');
                });
            });
        }

        $filteredSalesQuery = clone $query;
        $sales = $query->latest()->paginate(15)->withQueryString();
        $sellerFinancials = $this->sellerFinancials();

        $filteredSalesCount = (clone $filteredSalesQuery)->count();
        $filteredRevenue = (float) (clone $filteredSalesQuery)->sum('total');
        $topProducts = SaleItem::query()
            ->select('products.name', DB::raw('SUM(sale_items.quantity) as total_quantity'))
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereNotNull('sale_items.product_id')
            ->whereIn('sale_items.sale_id', (clone $filteredSalesQuery)->select('sales.id'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->orderBy('products.name')
            ->limit(3)
            ->get();

        // Statistiques des ventes
        $stats = [
            'filtered_sales' => $filteredSalesCount,
            'filtered_revenue' => $filteredRevenue,
            'average_sale' => $filteredSalesCount > 0 ? $filteredRevenue / $filteredSalesCount : 0,
            'top_products' => $topProducts,
            'total_cash_balance' => $sellerFinancials->sum('cash_balance'),
            'total_orange_money_balance' => $sellerFinancials->sum('orange_money_balance'),
            'total_mtn_momo_balance' => $sellerFinancials->sum('mtn_momo_balance'),
            'total_mobile_money_balance' => $sellerFinancials->sum('mobile_money_balance'),
            'total_current_day_revenue' => $sellerFinancials->sum('today_revenue'),
            'today_sales' => Sale::whereIn('seller_id', $sellerIds)->whereDate('created_at', today())->count(),
        ];

        // Liste des vendeurs pour le filtre
        $sellers = User::role('seller')
            ->where('created_by', auth()->id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $productSuggestions = Product::where('created_by', auth()->id())
            ->orderBy('name')
            ->get(['id', 'name', 'barcode'])
            ->push((object) [
                'id' => '',
                'name' => 'Token Energie',
                'barcode' => null,
            ]);

        return view('manager.sales.index', compact('sales', 'stats', 'sellers', 'productSuggestions'));
    }

    public function topProducts(Request $request)
    {
        [$dateFrom, $dateTo, $period] = $this->salesPeriod($request);
        $sellerIds = User::role('seller')
            ->where('created_by', auth()->id())
            ->pluck('id');

        $products = SaleItem::query()
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.unit',
                'categories.name as category_name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue')
            )
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereIn('sales.seller_id', $sellerIds)
            ->where('products.created_by', auth()->id())
            ->whereBetween('sales.created_at', [$dateFrom->copy()->startOfDay(), $dateTo->copy()->endOfDay()])
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.unit', 'categories.name')
            ->orderByDesc('total_quantity')
            ->orderBy('products.name')
            ->paginate(30)
            ->withQueryString();

        return view('manager.sales.top-products', compact('products', 'period', 'dateFrom', 'dateTo'));
    }

    public function destroySale(Sale $sale, SaleCancellationService $saleCancellationService)
    {
        try {
            $saleCancellationService->cancelForManager($sale, auth()->user());
        } catch (AuthorizationException $exception) {
            return back()->with('error', 'Vente hors perimetre du gerant.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Erreur de correction stock/solde. La vente n\'a pas ete supprimee.');
        }

        return back()->with('success', 'Vente supprimee. Stock et soldes corriges.');
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
                    'today_revenue' => $cashRegisterService->currentDayRevenueForSeller($seller),
                ];
            });
    }

    private function salesPeriod(Request $request): array
    {
        $period = $request->input('period', '30_days');

        if ($period === 'custom') {
            $validated = $request->validate([
                'date_from' => ['required', 'date'],
                'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            ]);

            return [\Illuminate\Support\Carbon::parse($validated['date_from']), \Illuminate\Support\Carbon::parse($validated['date_to']), $period];
        }

        return match ($period) {
            'today' => [today(), today(), $period],
            '7_days' => [today()->subDays(6), today(), $period],
            'month' => [now()->startOfMonth(), today(), $period],
            default => [today()->subDays(29), today(), '30_days'],
        };
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

        $query = Sale::with(['seller', 'items.product', 'items.promotion'])
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


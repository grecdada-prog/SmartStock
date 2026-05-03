<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Sale;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\ActivityLog;
use App\Models\CashRegisterClosure;
use App\Services\CashRegisterService;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ManagerSalesExport;

class ReportController extends Controller
{
    /**
     * Afficher la page des rapports
     */
    public function index()
    {
        // Statistiques générales pour la page
        $sellerIds = User::role('seller')
            ->where('created_by', auth()->id())
            ->pluck('id');

        $sellerFinancials = $this->sellerFinancials();

        $stats = [
            'total_sellers' => User::role('seller')->where('created_by', auth()->id())->count(),
            'total_sales' => Sale::whereIn('seller_id', $sellerIds)->count(),
            'total_cash_balance' => $sellerFinancials->sum('cash_balance'),
            'total_current_day_revenue' => $sellerFinancials->sum('today_revenue'),
            'total_yesterday_revenue' => $sellerFinancials->sum('yesterday_revenue'),
            'total_products' => Product::where('created_by', auth()->id())->count(),
        ];

        return view('manager.reports.index', compact('stats'));
    }

    /**
     * Rapport des ventes
     */
    public function salesReport(Request $request)
    {
        $sellerIds = User::role('seller')
            ->where('created_by', auth()->id())
            ->pluck('id');

        $query = Sale::with(['seller', 'items.product'])
            ->whereIn('seller_id', $sellerIds);

        // Filtres
        if ($request->filled('seller_id')) {
            $query->where('seller_id', $request->seller_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Grouper par date si demandé
        $filteredSalesQuery = clone $query;

        if ($request->filled('group_by') && $request->group_by === 'date') {
            $salesByDate = $query->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total) as revenue')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get();

            $sales = collect();
        } else {
            $sales = $query->latest()->paginate(50);
            $salesByDate = collect();
        }

        $sellerFinancials = $this->sellerFinancials();

        // Statistiques
        $stats = [
            'filtered_sales' => (clone $filteredSalesQuery)->count(),
            'filtered_revenue' => (float) (clone $filteredSalesQuery)->sum('total'),
            'average_sale' => (float) (clone $filteredSalesQuery)->avg('total'),
            'total_cash_balance' => $sellerFinancials->sum('cash_balance'),
            'total_current_day_revenue' => $sellerFinancials->sum('today_revenue'),
            'total_yesterday_revenue' => $sellerFinancials->sum('yesterday_revenue'),
        ];

        // Liste des vendeurs pour le filtre
        $sellers = User::role('seller')
            ->where('created_by', auth()->id())
            ->orderBy('name')
            ->get();

        return view('manager.reports.sales', compact('sales', 'salesByDate', 'stats', 'sellers'));
    }

    /**
     * Rapport d'activité
     */
    public function activityReport(Request $request)
    {
        $query = ActivityLog::with('user')->where('user_id', auth()->id());

        // Filtres
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $activities = $query->latest()->paginate(50);

        // Statistiques
        $stats = [
            'total_activities' => ActivityLog::where('user_id', auth()->id())->count(),
            'today_activities' => ActivityLog::where('user_id', auth()->id())->whereDate('created_at', today())->count(),
            'this_month_activities' => ActivityLog::where('user_id', auth()->id())
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count(),
        ];

        // Types d'actions
        $actionTypes = ActivityLog::where('user_id', auth()->id())
            ->select('action')
            ->distinct()
            ->pluck('action');

        return view('manager.reports.activity', compact('activities', 'stats', 'actionTypes'));
    }

    /**
     * Rapport de stock
     */
    public function stockReport(Request $request)
    {
        $query = Product::where('created_by', auth()->id());

        // Filtres
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'low') {
                $query->lowStock();
            } elseif ($request->stock_status === 'out') {
                $query->where('quantity', 0);
            } elseif ($request->stock_status === 'normal') {
                $query->whereColumn('quantity', '>', 'alert_quantity');
            }
        }

        $products = $query->with('category')->latest()->paginate(50);

        // Statistiques
        $stats = [
            'total_products' => Product::where('created_by', auth()->id())->count(),
            'low_stock_products' => Product::where('created_by', auth()->id())->lowStock()->count(),
            'out_of_stock_products' => Product::where('created_by', auth()->id())->where('quantity', 0)->count(),
            'total_value' => Product::where('created_by', auth()->id())->sum(DB::raw('quantity * purchase_price')),
        ];

        return view('manager.reports.stock', compact('products', 'stats'));
    }

    /**
     * Exporter le rapport de ventes en PDF
     */
    public function exportSalesReportPdf(Request $request)
    {
        ActivityLog::log(
            'export_sales_report_pdf',
            'Export du rapport de ventes en PDF',
            'Sale',
            null
        );

        $sellerIds = User::role('seller')
            ->where('created_by', auth()->id())
            ->pluck('id');

        $query = Sale::with(['seller', 'items.product'])
            ->whereIn('seller_id', $sellerIds);

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

        $stats = [
            'total_sales' => $sales->count(),
            'filtered_revenue' => $sales->sum('total'),
            'average_sale' => $sales->avg('total'),
        ];

        $pdf = Pdf::loadView('manager.reports.sales-pdf', [
            'sales' => $sales,
            'stats' => $stats,
            'filters' => $request->all(),
            'manager' => auth()->user(),
        ]);

        return $pdf->download('rapport_ventes_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }

    private function sellerFinancials()
    {
        $cashRegisterService = app(CashRegisterService::class);

        return User::role('seller')
            ->where('created_by', auth()->id())
            ->orderBy('name')
            ->get()
            ->map(function (User $seller) use ($cashRegisterService) {
                $todayClosure = CashRegisterClosure::where('seller_id', $seller->id)
                    ->whereDate('business_date', today())
                    ->first();
                $pendingClosure = CashRegisterClosure::where('seller_id', $seller->id)
                    ->whereNull('opened_at')
                    ->latest('closed_at')
                    ->first();
                $lastOpenedClosure = CashRegisterClosure::where('seller_id', $seller->id)
                    ->whereNotNull('opened_at')
                    ->latest('opened_at')
                    ->first();
                $currentSessionOpenedAt = $todayClosure?->opened_at;

                if (!$currentSessionOpenedAt && $lastOpenedClosure?->opened_at?->isToday()) {
                    $currentSessionOpenedAt = $lastOpenedClosure->opened_at;
                }

                $todayRevenueQuery = Sale::where('seller_id', $seller->id)
                    ->whereDate('created_at', today());

                if ($pendingClosure) {
                    $todayRevenueQuery->whereRaw('1 = 0');
                } elseif ($currentSessionOpenedAt) {
                    $todayRevenueQuery->where('created_at', '>=', $currentSessionOpenedAt);
                }

                $yesterdayClosure = CashRegisterClosure::where('seller_id', $seller->id)
                    ->whereDate('business_date', today()->subDay())
                    ->first();
                $yesterdayRevenue = $yesterdayClosure?->amount
                    ?? Sale::where('seller_id', $seller->id)
                        ->whereDate('created_at', today()->subDay())
                        ->sum('total');

                return [
                    'seller' => $seller,
                    'cash_balance' => (float) $cashRegisterService->balanceForSeller($seller),
                    'today_revenue' => (float) $todayRevenueQuery->sum('total'),
                    'yesterday_revenue' => (float) $yesterdayRevenue,
                ];
            });
    }
}

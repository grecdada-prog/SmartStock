<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Category;
use App\Models\ActivityLog;
use App\Services\SessionManager;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ManagerSalesExport;
use Barryvdh\DomPDF\Facade\Pdf;

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

        $sales = $query->latest()->paginate(20);

        // Statistiques des ventes
        $stats = [
            'total_sales' => Sale::whereIn('seller_id', $sellerIds)->count(),
            'total_revenue' => Sale::whereIn('seller_id', $sellerIds)->sum('total'),
            'today_sales' => Sale::whereIn('seller_id', $sellerIds)->whereDate('created_at', today())->count(),
            'today_revenue' => Sale::whereIn('seller_id', $sellerIds)->whereDate('created_at', today())->sum('total'),
            'this_month_sales' => Sale::whereIn('seller_id', $sellerIds)->whereMonth('created_at', now()->month)->count(),
            'this_month_revenue' => Sale::whereIn('seller_id', $sellerIds)->whereMonth('created_at', now()->month)->sum('total'),
        ];

        // Liste des vendeurs pour le filtre
        $sellers = User::role('seller')
            ->where('created_by', auth()->id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('manager.sales.index', compact('sales', 'stats', 'sellers'));
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
            'total_revenue' => $sales->sum('total'),
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
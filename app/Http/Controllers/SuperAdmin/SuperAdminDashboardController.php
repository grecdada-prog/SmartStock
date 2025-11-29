<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Category;
use App\Models\ActivityLog;
use App\Services\SessionManager;
use App\Models\ActiveSession;
use Illuminate\Support\Facades\DB;
use App\Exports\SalesExport;
use App\Exports\ActivityLogsExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class SuperAdminDashboardController extends Controller
{
    /**
     * Afficher le dashboard Super Admin
     */
    public function index()
    {
        // Statistiques générales
        $stats = [
            'total_users' => User::count(),
            'total_managers' => User::role('manager')->count(),
            'total_sellers' => User::role('seller')->count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_products' => Product::count(),
            'low_stock_products' => Product::lowStock()->count(),
            'total_categories' => Category::count(),
            'total_sales' => Sale::count(),
            'total_revenue' => Sale::sum('total'),
            'today_sales' => Sale::whereDate('created_at', today())->count(),
            'today_revenue' => Sale::whereDate('created_at', today())->sum('total'),
        ];

        // Utilisateurs en ligne
        $onlineUsers = SessionManager::getOnlineCountByRole();

        // Dernières activités
        $recentActivities = ActivityLog::with('user')
            ->latest()
            ->take(3)
            ->get();

        // Ventes récentes
        $recentSales = Sale::with(['seller', 'items.product'])
            ->latest()
            ->take(5)
            ->get();

        // Graphique des ventes (7 derniers jours)
        $salesChart = Sale::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as revenue')
            )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

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

        return view('superadmin.dashboard', compact(
            'stats',
            'onlineUsers',
            'recentActivities',
            'recentSales',
            'salesChart',
            'topSellers'
        ));
    }

    /**
     * Afficher les statistiques détaillées
     */
public function statistics()
{
    // Statistiques détaillées par période
    $periods = ['today', 'week', 'month', 'year'];
    $statistics = [];

    foreach ($periods as $period) {
        $query = Sale::query();

        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                break;
            case 'year':
                $query->whereYear('created_at', now()->year);
                break;
        }

        $statistics[$period] = [
            'sales_count' => $query->count(),
            'revenue' => $query->sum('total'),
            'average_sale' => $query->avg('total') ?? 0,
        ];
    }

    // Graphique des ventes (7 derniers jours)
    $salesChart = Sale::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(total) as revenue')
        )
        ->where('created_at', '>=', now()->subDays(7))
        ->groupBy('date')
        ->orderBy('date')
        ->get();

    // Top 5 vendeurs du mois
    $topSellers = User::role('seller')
        ->withSum(['sales' => function($query) {
            $query->whereMonth('created_at', now()->month);
        }], 'total')
        ->orderBy('sales_sum_total', 'desc')
        ->take(5)
        ->get();

    // Top 5 produits vendus
    $topProducts = Product::select('products.*')
        ->join('sale_items', 'products.id', '=', 'sale_items.product_id')
        ->selectRaw('SUM(sale_items.quantity) as total_sold')
        ->groupBy('products.id')
        ->orderBy('total_sold', 'desc')
        ->take(5)
        ->get();

    return view('superadmin.statistics', compact('statistics', 'salesChart', 'topSellers', 'topProducts'));
}

    /**
     * Afficher les logs d'activité
     */
    public function activityLogs(Request $request)
    {
        $query = ActivityLog::with('user')->latest();

        // Filtres
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(50);
        $users = User::orderBy('name')->get();

        return view('superadmin.activity-logs', compact('logs', 'users'));
    }

    /**
     * Vue globale des produits
     */
    public function products()
    {
        $products = Product::with(['category', 'creator'])
            ->withCount('saleItems')
            ->latest()
            ->paginate(20);

        $categories = Category::withCount('products')->get();

        return view('superadmin.products', compact('products', 'categories'));
    }

    /**
     * Vue globale des ventes
     */
    public function sales(Request $request)
    {
        $query = Sale::with(['seller', 'items.product'])->latest();

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

        $sales = $query->paginate(20);
        $sellers = User::role('seller')->orderBy('name')->get();

        // Calculer les statistiques pour les ventes affichées (avec filtres)
        $statsQuery = Sale::query();

        if ($request->filled('seller_id')) {
            $statsQuery->where('seller_id', $request->seller_id);
        }

        if ($request->filled('date_from')) {
            $statsQuery->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $statsQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $totalRevenue = $statsQuery->sum('total');
        $averageSale = $statsQuery->avg('total') ?? 0;

        return view('superadmin.sales', compact('sales', 'sellers', 'totalRevenue', 'averageSale'));
    }

    public function activeSessions()
{
    $activeSessions = ActiveSession::with('user.roles')
        ->where('last_activity', '>', now()->subMinutes(30))
        ->orderBy('last_activity', 'desc')
        ->get();

    return view('superadmin.active-sessions', compact('activeSessions'));
}

public function destroySession(ActiveSession $session)
{
    if ($session->user_id === auth()->id()) {
        return back()->with('error', 'Vous ne pouvez pas déconnecter votre propre session.');
    }

    $userName = $session->user->name;
    
    SessionManager::logoutUserFromAllSessions(
        $session->user_id, 
        'Session terminée par le Super Admin'
    );

    return back()->with('success', "{$userName} a été déconnecté avec succès.");
}

public function cleanupSessions()
{
    $count = SessionManager::cleanExpiredSessions(30);

    return back()->with('success', "{$count} session(s) expirée(s) nettoyée(s).");
}

    /**
     * Exporter les ventes en Excel
     */
    public function exportSalesExcel(Request $request)
    {
        ActivityLog::log(
            'export_sales_excel',
            'Export des ventes en Excel',
            'Sale',
            null
        );

        return Excel::download(
            new SalesExport($request->seller_id, $request->date_from, $request->date_to),
            'ventes_' . now()->format('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    /**
     * Exporter les ventes en PDF
     */
    public function exportSalesPdf(Request $request)
    {
        $query = Sale::with(['seller', 'saleItems.product']);

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
        $totalRevenue = $sales->sum('total');
        $averageSale = $sales->avg('total') ?? 0;

        ActivityLog::log(
            'export_sales_pdf',
            'Export des ventes en PDF',
            'Sale',
            null
        );

        $pdf = Pdf::loadView('superadmin.exports.sales-pdf', compact('sales', 'totalRevenue', 'averageSale'));

        return $pdf->download('ventes_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }

    /**
     * Exporter les logs d'activité en Excel
     */
    public function exportActivityLogsExcel(Request $request)
    {
        ActivityLog::log(
            'export_activity_logs_excel',
            'Export des logs d\'activité en Excel',
            'ActivityLog',
            null
        );

        return Excel::download(
            new ActivityLogsExport($request->user_id, $request->action_type, $request->date_from, $request->date_to),
            'logs_activite_' . now()->format('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    /**
     * Exporter les logs d'activité en PDF
     */
    public function exportActivityLogsPdf(Request $request)
    {
        $query = ActivityLog::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->latest()->get();

        ActivityLog::log(
            'export_activity_logs_pdf',
            'Export des logs d\'activité en PDF',
            'ActivityLog',
            null
        );

        $pdf = Pdf::loadView('superadmin.exports.activity-logs-pdf', compact('logs'));

        return $pdf->download('logs_activite_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }
}
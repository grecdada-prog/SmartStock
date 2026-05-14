<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Exports\ActivityLogsExport;
use App\Exports\SalesExport;
use App\Http\Controllers\Controller;
use App\Models\ActiveSession;
use App\Models\ActivityLog;
use App\Models\CashRegisterClosure;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\CashRegisterService;
use App\Services\SessionManager;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SuperAdminDashboardController extends Controller
{
    /**
     * Display the super admin dashboard.
     */
    public function index()
    {
        $sellerFinancials = $this->sellerFinancials();
        $managerSummaries = $this->managerSummaries($sellerFinancials);
        $oversightAlerts = $this->oversightAlerts($managerSummaries);

        $stats = [
            'total_users' => User::count(),
            'total_managers' => User::role('manager')->count(),
            'total_sellers' => User::role('seller')->count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_products' => Product::count(),
            'low_stock_products' => Product::lowStock()->count(),
            'total_categories' => Category::count(),
            'total_sales' => Sale::count(),
            'today_sales' => Sale::whereDate('created_at', today())->count(),
            'total_current_day_revenue' => $sellerFinancials->sum('today_revenue'),
            'total_yesterday_revenue' => $sellerFinancials->sum('yesterday_revenue'),
            'total_cash_balance' => $sellerFinancials->sum('cash_balance'),
        ];

        $onlineUsers = SessionManager::getOnlineCountByRole();

        $recentActivities = ActivityLog::with('user')
            ->latest()
            ->take(3)
            ->get();

        $recentSales = Sale::with(['seller', 'items.product'])
            ->latest()
            ->take(5)
            ->get();

        $salesChart = Sale::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as revenue')
            )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topSellers = User::role('seller')
            ->withCount(['sales' => function ($query) {
                $query->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month);
            }])
            ->withSum(['sales' => function ($query) {
                $query->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month);
            }], 'total')
            ->orderBy('sales_sum_total', 'desc')
            ->take(5)
            ->get();

        $cashRegisterClosures = CashRegisterClosure::with('seller')
            ->latest('closed_at')
            ->take(10)
            ->get();

        return view('superadmin.dashboard', compact(
            'stats',
            'onlineUsers',
            'recentActivities',
            'recentSales',
            'salesChart',
            'topSellers',
            'cashRegisterClosures',
            'managerSummaries',
            'oversightAlerts'
        ));
    }

    /**
     * Detailed statistics page.
     */
    public function statistics()
    {
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

        $salesChart = Sale::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as revenue')
            )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topSellers = User::role('seller')
            ->withSum(['sales' => function ($query) {
                $query->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month);
            }], 'total')
            ->orderBy('sales_sum_total', 'desc')
            ->take(5)
            ->get();

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
     * Activity logs page.
     */
    public function activityLogs(Request $request)
    {
        $query = ActivityLog::with('user')->latest();

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
     * Anomalies center.
     */
    public function anomalies(Request $request)
    {
        $sellerFinancials = $this->sellerFinancials();
        $managerSummaries = $this->managerSummaries($sellerFinancials);
        $anomalies = $this->buildAnomalies($managerSummaries);

        if ($request->filled('severity')) {
            $anomalies = $anomalies->where('severity', $request->severity)->values();
        }

        if ($request->filled('type')) {
            $anomalies = $anomalies->where('type', $request->type)->values();
        }

        if ($request->filled('manager_id')) {
            $anomalies = $anomalies->where('manager_id', (int) $request->manager_id)->values();
        }

        $summary = [
            'critical' => $anomalies->where('severity', 'danger')->count(),
            'warning' => $anomalies->where('severity', 'warning')->count(),
            'info' => $anomalies->where('severity', 'info')->count(),
            'total' => $anomalies->count(),
        ];

        $managers = User::role('manager')->orderBy('name')->get();

        return view('superadmin.anomalies', compact('anomalies', 'summary', 'managers'));
    }

    /**
     * Global products view.
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
     * Global sales view.
     */
    public function sales(Request $request)
    {
        $query = Sale::with(['seller', 'items.product'])->latest();

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

        $sales = $query->paginate(20);
        $sellers = User::role('seller')->orderBy('name')->get();
        $sellerFinancials = $this->sellerFinancials();

        $statsQuery = Sale::query();

        if ($request->filled('seller_id')) {
            $statsQuery->where('seller_id', $request->seller_id);
        }

        if ($request->filled('payment_method')) {
            $statsQuery->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $statsQuery->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $statsQuery->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $statsQuery->where('invoice_number', 'like', '%' . $request->search . '%');
        }

        $stats = [
            'filtered_sales' => (clone $statsQuery)->count(),
            'filtered_revenue' => (float) (clone $statsQuery)->sum('total'),
            'average_sale' => (float) (clone $statsQuery)->avg('total'),
            'total_current_day_revenue' => $sellerFinancials->sum('today_revenue'),
            'total_yesterday_revenue' => $sellerFinancials->sum('yesterday_revenue'),
            'total_cash_balance' => $sellerFinancials->sum('cash_balance'),
        ];

        return view('superadmin.sales', compact('sales', 'sellers', 'stats'));
    }

    /**
     * Active sessions page.
     */
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
            return back()->with('error', 'Vous ne pouvez pas deconnecter votre propre session.');
        }

        $userName = $session->user->name;

        SessionManager::logoutUserFromAllSessions(
            $session->user_id,
            'Session terminee par le Super Admin'
        );

        return back()->with('success', "{$userName} a ete deconnecte avec succes.");
    }

    public function cleanupSessions()
    {
        $count = SessionManager::cleanExpiredSessions(30);

        return back()->with('success', "{$count} session(s) expiree(s) nettoyee(s).");
    }

    /**
     * Export sales to Excel.
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
            new SalesExport(
                $request->seller_id,
                $request->date_from,
                $request->date_to,
                $request->payment_method,
                $request->search
            ),
            'ventes_' . now()->format('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    /**
     * Export sales to PDF.
     */
    public function exportSalesPdf(Request $request)
    {
        $query = Sale::with(['seller', 'items.product']);

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
     * Export activity logs to Excel.
     */
    public function exportActivityLogsExcel(Request $request)
    {
        ActivityLog::log(
            'export_activity_logs_excel',
            "Export des logs d'activite en Excel",
            'ActivityLog',
            null
        );

        return Excel::download(
            new ActivityLogsExport($request->user_id, $request->action, $request->date_from, $request->date_to),
            'logs_activite_' . now()->format('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    /**
     * Export activity logs to PDF.
     */
    public function exportActivityLogsPdf(Request $request)
    {
        $query = ActivityLog::with('user');

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

        $logs = $query->latest()->get();

        ActivityLog::log(
            'export_activity_logs_pdf',
            "Export des logs d'activite en PDF",
            'ActivityLog',
            null
        );

        $pdf = Pdf::loadView('superadmin.exports.activity-logs-pdf', compact('logs'));

        return $pdf->download('logs_activite_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }

    private function sellerFinancials()
    {
        $cashRegisterService = app(CashRegisterService::class);

        return User::role('seller')
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

    private function managerSummaries($sellerFinancials)
    {
        return User::role('manager')
            ->with(['createdUsers.roles', 'createdUsers.activeSessions'])
            ->orderBy('name')
            ->get()
            ->map(function (User $manager) use ($sellerFinancials) {
                $sellers = $manager->createdUsers->filter(fn (User $user) => $user->hasRole('seller'))->values();
                $sellerIds = $sellers->pluck('id');
                $activeSellers = $sellers->where('is_active', true);
                $sellerFinancialRows = $sellerFinancials
                    ->filter(fn (array $row) => $sellerIds->contains($row['seller']->id))
                    ->values();
                $lastSaleAt = $sellerIds->isNotEmpty()
                    ? Sale::whereIn('seller_id', $sellerIds)->latest('created_at')->value('created_at')
                    : null;

                return [
                    'manager' => $manager,
                    'sellers_count' => $sellers->count(),
                    'active_sellers' => $activeSellers->count(),
                    'online_sellers' => $activeSellers->filter(
                        fn (User $seller) => $seller->activeSessions->contains(
                            fn ($session) => $session->last_activity && $session->last_activity->gt(now()->subMinutes(5))
                        )
                    )->count(),
                    'today_revenue' => (float) $sellerFinancialRows->sum('today_revenue'),
                    'yesterday_revenue' => (float) $sellerFinancialRows->sum('yesterday_revenue'),
                    'cash_balance' => (float) $sellerFinancialRows->sum('cash_balance'),
                    'pending_closures' => CashRegisterClosure::whereIn('seller_id', $sellerIds)
                        ->whereNull('opened_at')
                        ->count(),
                    'low_stock_products' => Product::where('created_by', $manager->id)
                        ->lowStock()
                        ->count(),
                    'last_sale_at' => $lastSaleAt ? Carbon::parse($lastSaleAt) : null,
                ];
            });
    }

    private function oversightAlerts($managerSummaries)
    {
        return $this->buildAnomalies($managerSummaries)
            ->map(function (array $anomaly) {
                return [
                    'severity' => $anomaly['severity'],
                    'title' => $anomaly['title'],
                    'message' => $anomaly['message'],
                    'route' => $anomaly['route'],
                    'cta' => $anomaly['cta'],
                    'weight' => $anomaly['weight'],
                ];
            })
            ->sortBy('weight')
            ->values()
            ->take(8);
    }

    private function buildAnomalies($managerSummaries)
    {
        $anomalies = collect();
        $activeSellers = User::role('seller')
            ->where('is_active', true)
            ->with('creator')
            ->orderBy('name')
            ->get();
        $lastSalesBySeller = Sale::select('seller_id', DB::raw('MAX(created_at) as last_sale_at'))
            ->groupBy('seller_id')
            ->pluck('last_sale_at', 'seller_id');

        foreach ($managerSummaries as $summary) {
            $manager = $summary['manager'];

            if ($summary['pending_closures'] > 0) {
                $anomalies->push([
                    'severity' => 'danger',
                    'type' => 'pending_cash_closure',
                    'manager_id' => $manager->id,
                    'manager_name' => $manager->name,
                    'title' => 'Caisses fermees en attente',
                    'message' => "{$manager->name} a {$summary['pending_closures']} caisse(s) fermee(s) qui attendent une reouverture.",
                    'recommendation' => 'Verifier le vendeur concerne et decider si la caisse doit etre rouverte ou la journee laissee cloturee.',
                    'route' => route('superadmin.sellers.index'),
                    'cta' => 'Voir les vendeurs',
                    'weight' => 1,
                ]);
            }

            if ($summary['low_stock_products'] > 0) {
                $anomalies->push([
                    'severity' => 'warning',
                    'type' => 'critical_low_stock',
                    'manager_id' => $manager->id,
                    'manager_name' => $manager->name,
                    'title' => 'Stock faible critique',
                    'message' => "{$manager->name} a {$summary['low_stock_products']} produit(s) a surveiller de pres.",
                    'recommendation' => 'Programmer un reapprovisionnement ou controler les sorties stock inhabituelles.',
                    'route' => route('superadmin.products'),
                    'cta' => 'Voir les produits',
                    'weight' => 2,
                ]);
            }

            if ($summary['active_sellers'] > 0 && $summary['online_sellers'] === 0) {
                $anomalies->push([
                    'severity' => 'info',
                    'type' => 'no_online_seller',
                    'manager_id' => $manager->id,
                    'manager_name' => $manager->name,
                    'title' => 'Aucun vendeur en ligne',
                    'message' => "{$manager->name} n'a actuellement aucun vendeur connecte.",
                    'recommendation' => 'Verifier si les vendeurs sont attendus en poste ou si un incident de connexion est en cours.',
                    'route' => route('superadmin.sessions.active'),
                    'cta' => 'Voir les sessions',
                    'weight' => 3,
                ]);
            }
        }

        foreach ($activeSellers as $seller) {
            $lastSaleAt = $lastSalesBySeller->get($seller->id);
            $parsedLastSaleAt = $lastSaleAt ? Carbon::parse($lastSaleAt) : null;
            $pendingClosure = CashRegisterClosure::where('seller_id', $seller->id)
                ->whereNull('opened_at')
                ->latest('closed_at')
                ->first();

            if ($pendingClosure && $pendingClosure->closed_at && $pendingClosure->closed_at->lt(now()->subHours(8))) {
                $anomalies->push([
                    'severity' => 'danger',
                    'type' => 'closure_open_too_long',
                    'manager_id' => $seller->created_by,
                    'manager_name' => $seller->creator->name ?? 'N/A',
                    'title' => 'Caisse fermee depuis trop longtemps',
                    'message' => "{$seller->name} a une caisse fermee depuis le {$pendingClosure->closed_at->format('d/m/Y H:i')}.",
                    'recommendation' => 'Verifier si le vendeur doit rouvrir sa caisse ou si le poste reste volontairement inactif.',
                    'route' => route('superadmin.sellers.edit', $seller),
                    'cta' => 'Voir le vendeur',
                    'weight' => 1,
                ]);
            }

            if (!$pendingClosure && (!$parsedLastSaleAt || $parsedLastSaleAt->lt(now()->subDays(2)))) {
                $anomalies->push([
                    'severity' => 'warning',
                    'type' => 'inactive_seller_sales',
                    'manager_id' => $seller->created_by,
                    'manager_name' => $seller->creator->name ?? 'N/A',
                    'title' => 'Vendeur sans vente recente',
                    'message' => "{$seller->name} n'a pas enregistre de vente recente.".($parsedLastSaleAt ? " Derniere vente le {$parsedLastSaleAt->format('d/m/Y H:i')}." : ''),
                    'recommendation' => 'Verifier si le vendeur est bien affecte a un point de vente actif ou s il faut reequilibrer l equipe.',
                    'route' => route('superadmin.sellers.edit', $seller),
                    'cta' => 'Voir le vendeur',
                    'weight' => 2,
                ]);
            }
        }

        ActivityLog::with('user')
            ->where('action', 'cash_balance_withdrawn')
            ->where('created_at', '>=', now()->subDay())
            ->latest()
            ->take(8)
            ->get()
            ->each(function (ActivityLog $log) use ($anomalies) {
                $properties = $log->properties ?? [];
                $sellerName = $properties['seller_name'] ?? 'un vendeur';
                $managerName = $properties['manager_name'] ?? ($log->user->name ?? 'un gerant');
                $amount = number_format((float) ($properties['amount'] ?? 0), 0, ',', ' ');

                $anomalies->push([
                    'severity' => 'info',
                    'type' => 'cash_withdrawal_alert',
                    'manager_id' => $properties['manager_id'] ?? null,
                    'manager_name' => $managerName,
                    'title' => 'Retrait de fonds vendeur',
                    'message' => "{$managerName} a retire {$amount} FCFA du Solde Cash de {$sellerName}.",
                    'recommendation' => 'Verifier le motif du retrait et rapprocher le mouvement avec la caisse selectionnee.',
                    'route' => route('superadmin.activity-logs', ['action' => 'cash_balance_withdrawn']),
                    'cta' => 'Voir le log',
                    'weight' => 0,
                ]);
            });

        return $anomalies
            ->sortBy('weight')
            ->values();
    }
}

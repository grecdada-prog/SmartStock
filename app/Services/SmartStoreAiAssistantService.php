<?php

namespace App\Services;

use App\Models\CashBalanceAdjustment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SmartStoreAiAssistantService
{
    private const ANALYSIS_DAYS = 30;
    private const RECENT_DAYS = 7;
    private const RESTOCK_TARGET_DAYS = 15;
    private const EXPIRY_ALERT_DAYS = 10;

    public function analyze(User $manager): array
    {
        $sellerIds = User::role('seller')
            ->where('created_by', $manager->id)
            ->pluck('id');

        $stockInsights = $this->stockInsights($manager, $sellerIds);
        $expiryInsights = $this->expiryInsights($manager);
        $anomalies = $this->anomalies($manager, $sellerIds, $stockInsights, $expiryInsights);
        $salesSummary = $this->salesSummary($sellerIds);

        return [
            'generated_at' => now(),
            'period_days' => self::ANALYSIS_DAYS,
            'target_days' => self::RESTOCK_TARGET_DAYS,
            'sales_summary' => $salesSummary,
            'stock_predictions' => $stockInsights,
            'expiry_alerts' => $expiryInsights,
            'anomalies' => $anomalies,
            'narrative' => $this->narrative($salesSummary, $stockInsights, $expiryInsights, $anomalies),
            'methodology' => [
                'Analyse des '.self::ANALYSIS_DAYS.' derniers jours',
                'Ponderation des ventes recentes sur '.self::RECENT_DAYS.' jours',
                'Score de priorite: risque de rupture, stock actuel, volume vendu et peremption',
                'Confiance: qualite de l historique disponible par produit',
            ],
            'kpis' => [
                'critical_stock' => $stockInsights->where('severity', 'critical')->count(),
                'restock_recommendations' => $stockInsights->where('recommended_quantity', '>', 0)->count(),
                'expiry_alerts' => $expiryInsights->count(),
                'anomalies' => $anomalies->count(),
            ],
        ];
    }

    private function stockInsights(User $manager, Collection $sellerIds): Collection
    {
        $salesProfileByProduct = $this->salesProfileByProduct($sellerIds);
        $expiryRiskByProduct = $this->expiryRiskByProduct($manager);

        return Product::with('category')
            ->where('created_by', $manager->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) use ($salesProfileByProduct, $expiryRiskByProduct) {
                $profile = $salesProfileByProduct->get($product->id, [
                    'sold_quantity' => 0,
                    'recent_quantity' => 0,
                    'active_sale_days' => 0,
                ]);

                $soldQuantity = (int) $profile['sold_quantity'];
                $recentQuantity = (int) $profile['recent_quantity'];
                $activeSaleDays = (int) $profile['active_sale_days'];
                $longAverage = $soldQuantity / self::ANALYSIS_DAYS;
                $recentAverage = $recentQuantity / self::RECENT_DAYS;
                $averageDailySales = $recentQuantity > 0
                    ? (($recentAverage * 0.65) + ($longAverage * 0.35))
                    : $longAverage;
                $daysToStockout = $averageDailySales > 0
                    ? round($product->quantity / $averageDailySales, 1)
                    : null;
                $targetStock = (int) ceil($averageDailySales * self::RESTOCK_TARGET_DAYS * 1.2);
                $recommendedQuantity = max(0, $targetStock - (int) $product->quantity);
                $severity = $this->stockSeverity($product, $daysToStockout);
                $confidence = $this->restockConfidence($soldQuantity, $recentQuantity, $activeSaleDays);
                $expiryRisk = (int) ($expiryRiskByProduct->get($product->id) ?? 0);
                $priorityScore = $this->priorityScore($severity, $daysToStockout, $soldQuantity, $recommendedQuantity, $expiryRisk, $confidence);

                return [
                    'product' => $product,
                    'category' => $product->category?->name ?? 'Sans categorie',
                    'sold_quantity' => $soldQuantity,
                    'recent_quantity' => $recentQuantity,
                    'active_sale_days' => $activeSaleDays,
                    'average_daily_sales' => round($averageDailySales, 2),
                    'days_to_stockout' => $daysToStockout,
                    'recommended_quantity' => $recommendedQuantity,
                    'severity' => $severity,
                    'confidence' => $confidence,
                    'priority_score' => $priorityScore,
                    'action' => $this->actionPlan($severity, $recommendedQuantity, $expiryRisk, $confidence),
                    'reason' => $this->stockReason($product, $averageDailySales, $daysToStockout, $recommendedQuantity),
                ];
            })
            ->filter(fn (array $row) => $row['severity'] !== 'stable' || $row['recommended_quantity'] > 0)
            ->sort(function (array $a, array $b) {
                return ($b['priority_score'] <=> $a['priority_score'])
                    ?: ($this->severityWeight($a['severity']) <=> $this->severityWeight($b['severity']))
                    ?: (($a['days_to_stockout'] ?? 9999) <=> ($b['days_to_stockout'] ?? 9999))
                    ?: ($b['sold_quantity'] <=> $a['sold_quantity']);
            })
            ->take(15)
            ->values();
    }

    private function salesProfileByProduct(Collection $sellerIds): Collection
    {
        if ($sellerIds->isEmpty()) {
            return collect();
        }

        $totals = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereIn('sales.seller_id', $sellerIds)
            ->whereNotNull('sale_items.product_id')
            ->where('sales.created_at', '>=', now()->subDays(self::ANALYSIS_DAYS))
            ->select('sale_items.product_id')
            ->selectRaw('SUM(sale_items.quantity) as sold_quantity')
            ->groupBy('sale_items.product_id')
            ->pluck('sold_quantity', 'product_id');

        $recentTotals = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereIn('sales.seller_id', $sellerIds)
            ->whereNotNull('sale_items.product_id')
            ->where('sales.created_at', '>=', now()->subDays(self::RECENT_DAYS))
            ->select('sale_items.product_id')
            ->selectRaw('SUM(sale_items.quantity) as sold_quantity')
            ->groupBy('sale_items.product_id')
            ->pluck('sold_quantity', 'product_id');

        $activeDays = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereIn('sales.seller_id', $sellerIds)
            ->whereNotNull('sale_items.product_id')
            ->where('sales.created_at', '>=', now()->subDays(self::ANALYSIS_DAYS))
            ->select('sale_items.product_id')
            ->selectRaw('COUNT(DISTINCT DATE(sales.created_at)) as active_sale_days')
            ->groupBy('sale_items.product_id')
            ->pluck('active_sale_days', 'product_id');

        return $totals
            ->keys()
            ->merge($recentTotals->keys())
            ->unique()
            ->mapWithKeys(fn ($productId) => [
                $productId => [
                    'sold_quantity' => (int) ($totals->get($productId) ?? 0),
                    'recent_quantity' => (int) ($recentTotals->get($productId) ?? 0),
                    'active_sale_days' => (int) ($activeDays->get($productId) ?? 0),
                ],
            ]);
    }

    private function expiryRiskByProduct(User $manager): Collection
    {
        return StockMovement::query()
            ->whereIn('type', [StockMovement::TYPE_IN, StockMovement::TYPE_CORRECTION_CANCELLATION])
            ->where('remaining_quantity', '>', 0)
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '<=', today()->addDays(self::EXPIRY_ALERT_DAYS))
            ->whereHas('product', fn ($query) => $query
                ->where('created_by', $manager->id)
                ->where('is_active', true))
            ->select('product_id')
            ->selectRaw('COUNT(*) as risky_batches')
            ->groupBy('product_id')
            ->pluck('risky_batches', 'product_id');
    }

    private function expiryInsights(User $manager): Collection
    {
        return StockMovement::with(['product.category'])
            ->whereIn('type', [StockMovement::TYPE_IN, StockMovement::TYPE_CORRECTION_CANCELLATION])
            ->where('remaining_quantity', '>', 0)
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '<=', today()->addDays(self::EXPIRY_ALERT_DAYS))
            ->whereHas('product', fn ($query) => $query
                ->where('created_by', $manager->id)
                ->where('is_active', true))
            ->orderBy('expiration_date')
            ->orderBy('id')
            ->limit(10)
            ->get()
            ->map(function (StockMovement $movement) {
                $daysLeft = today()->diffInDays($movement->expiration_date, false);

                return [
                    'movement' => $movement,
                    'product' => $movement->product,
                    'days_left' => $daysLeft,
                    'severity' => $daysLeft < 0 ? 'critical' : ($daysLeft <= 3 ? 'high' : 'medium'),
                    'message' => $daysLeft < 0
                        ? 'Lot deja expire'
                        : 'Expiration dans '.$daysLeft.' jour(s)',
                ];
            });
    }

    private function anomalies(User $manager, Collection $sellerIds, Collection $stockInsights, Collection $expiryInsights): Collection
    {
        $anomalies = collect();

        $this->appendInactiveSellerAnomalies($anomalies, $manager, $sellerIds);
        $this->appendCashWithdrawalAnomalies($anomalies, $sellerIds);
        $this->appendRevenueDropAnomaly($anomalies, $sellerIds);
        $this->appendSellerRevenueDropAnomalies($anomalies, $manager, $sellerIds);

        foreach ($stockInsights->where('severity', 'critical')->take(5) as $insight) {
            $anomalies->push([
                'severity' => 'critical',
                'type' => 'stockout_prediction',
                'title' => 'Rupture probable',
                'message' => $insight['product']->name.' risque une rupture rapide.',
                'recommendation' => 'Prevoir '.$insight['recommended_quantity'].' unite(s) de reapprovisionnement.',
            ]);
        }

        foreach ($expiryInsights->whereIn('severity', ['critical', 'high'])->take(5) as $insight) {
            $anomalies->push([
                'severity' => $insight['severity'],
                'type' => 'expiry_risk',
                'title' => 'Risque de peremption',
                'message' => $insight['product']->name.' - '.$insight['message'].'.',
                'recommendation' => 'Verifier le lot et envisager une promotion ou un retrait.',
            ]);
        }

        return $anomalies
            ->sortBy(fn (array $row) => $this->severityWeight($row['severity']))
            ->take(12)
            ->values();
    }

    private function appendInactiveSellerAnomalies(Collection $anomalies, User $manager, Collection $sellerIds): void
    {
        if ($sellerIds->isEmpty()) {
            return;
        }

        $lastSales = Sale::query()
            ->whereIn('seller_id', $sellerIds)
            ->select('seller_id', DB::raw('MAX(created_at) as last_sale_at'))
            ->groupBy('seller_id')
            ->pluck('last_sale_at', 'seller_id');

        User::role('seller')
            ->where('created_by', $manager->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->each(function (User $seller) use ($anomalies, $lastSales) {
                $lastSaleAt = $lastSales->get($seller->id);
                $lastSaleDate = $lastSaleAt ? Carbon::parse($lastSaleAt) : null;

                if (! $lastSaleDate || $lastSaleDate->lt(now()->subDays(2))) {
                    $anomalies->push([
                        'severity' => 'medium',
                        'type' => 'inactive_seller',
                        'title' => 'Vendeur sans vente recente',
                        'message' => $seller->name.' n a pas enregistre de vente depuis plus de 48h.',
                        'recommendation' => 'Verifier son poste, sa connexion ou son affectation.',
                    ]);
                }
            });
    }

    private function appendCashWithdrawalAnomalies(Collection $anomalies, Collection $sellerIds): void
    {
        if ($sellerIds->isEmpty()) {
            return;
        }

        CashBalanceAdjustment::with('seller')
            ->whereIn('seller_id', $sellerIds)
            ->where('type', 'withdraw')
            ->where('created_at', '>=', now()->subDay())
            ->select('seller_id', 'balance_type', DB::raw('COUNT(*) as withdrawals_count'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('seller_id', 'balance_type')
            ->having('withdrawals_count', '>=', 3)
            ->get()
            ->each(function ($row) use ($anomalies) {
                $anomalies->push([
                    'severity' => 'high',
                    'type' => 'cash_withdrawal_frequency',
                    'title' => 'Retraits frequents',
                    'message' => ($row->seller->name ?? 'Un vendeur').' a '.$row->withdrawals_count.' retrait(s) sur les dernieres 24h.',
                    'recommendation' => 'Verifier les motifs et rapprocher le solde de caisse.',
                ]);
            });
    }

    private function appendRevenueDropAnomaly(Collection $anomalies, Collection $sellerIds): void
    {
        if ($sellerIds->isEmpty()) {
            return;
        }

        $todayRevenue = (float) Sale::whereIn('seller_id', $sellerIds)
            ->whereDate('created_at', today())
            ->sum('total');

        $previousRevenue = (float) Sale::whereIn('seller_id', $sellerIds)
            ->whereBetween('created_at', [today()->subDays(7)->startOfDay(), today()->subDay()->endOfDay()])
            ->sum('total');
        $dailyAverage = $previousRevenue / 7;

        if ($dailyAverage > 0 && $todayRevenue < ($dailyAverage * 0.45)) {
            $anomalies->push([
                'severity' => 'medium',
                'type' => 'revenue_drop',
                'title' => 'Baisse inhabituelle du chiffre d affaires',
                'message' => 'La recette du jour est nettement inferieure a la moyenne des 7 derniers jours.',
                'recommendation' => 'Verifier les vendeurs connectes, les stocks critiques et les caisses ouvertes.',
            ]);
        }
    }

    private function appendSellerRevenueDropAnomalies(Collection $anomalies, User $manager, Collection $sellerIds): void
    {
        if ($sellerIds->isEmpty()) {
            return;
        }

        $todayRevenueBySeller = Sale::query()
            ->whereIn('seller_id', $sellerIds)
            ->whereDate('created_at', today())
            ->select('seller_id')
            ->selectRaw('SUM(total) as total_revenue')
            ->groupBy('seller_id')
            ->pluck('total_revenue', 'seller_id');

        $recentStatsBySeller = Sale::query()
            ->whereIn('seller_id', $sellerIds)
            ->whereBetween('created_at', [today()->subDays(7)->startOfDay(), today()->subDay()->endOfDay()])
            ->select('seller_id')
            ->selectRaw('SUM(total) as total_revenue')
            ->selectRaw('COUNT(DISTINCT DATE(created_at)) as active_days')
            ->groupBy('seller_id')
            ->get()
            ->keyBy('seller_id');

        User::role('seller')
            ->where('created_by', $manager->id)
            ->whereIn('id', $sellerIds)
            ->orderBy('name')
            ->get()
            ->each(function (User $seller) use ($anomalies, $todayRevenueBySeller, $recentStatsBySeller) {
                $recentStats = $recentStatsBySeller->get($seller->id);

                if (! $recentStats || (int) $recentStats->active_days < 3) {
                    return;
                }

                $dailyAverage = (float) $recentStats->total_revenue / 7;
                $todayRevenue = (float) ($todayRevenueBySeller->get($seller->id) ?? 0);

                if ($dailyAverage <= 0 || $todayRevenue >= ($dailyAverage * 0.45)) {
                    return;
                }

                $confidence = (int) $recentStats->active_days >= 5 ? 'high' : 'medium';

                $anomalies->push([
                    'severity' => 'medium',
                    'type' => 'seller_revenue_drop',
                    'title' => 'Baisse inhabituelle par vendeur',
                    'message' => $seller->name.' a une recette du jour inferieure a 45% de sa moyenne recente.',
                    'recommendation' => 'Comparer son stock disponible, son temps de connexion et les ventes non finalisees.',
                    'seller_id' => $seller->id,
                    'seller_name' => $seller->name,
                    'today_revenue' => round($todayRevenue, 2),
                    'daily_average' => round($dailyAverage, 2),
                    'active_days' => (int) $recentStats->active_days,
                    'confidence' => $confidence,
                ]);
            });
    }

    private function salesSummary(Collection $sellerIds): array
    {
        $baseQuery = Sale::whereIn('seller_id', $sellerIds);

        $todaySales = (clone $baseQuery)->whereDate('created_at', today())->count();
        $todayRevenue = (float) (clone $baseQuery)->whereDate('created_at', today())->sum('total');
        $monthRevenue = (float) (clone $baseQuery)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('total');

        $topProduct = $sellerIds->isEmpty()
            ? null
            : SaleItem::query()
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->join('products', 'products.id', '=', 'sale_items.product_id')
                ->whereIn('sales.seller_id', $sellerIds)
                ->whereDate('sales.created_at', today())
                ->whereNotNull('sale_items.product_id')
                ->select('products.name', DB::raw('SUM(sale_items.quantity) as total_quantity'))
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('total_quantity')
                ->first();

        return [
            'today_sales' => $todaySales,
            'today_revenue' => $todayRevenue,
            'month_revenue' => $monthRevenue,
            'top_product_name' => $topProduct?->name,
            'top_product_quantity' => (int) ($topProduct?->total_quantity ?? 0),
        ];
    }

    private function narrative(array $salesSummary, Collection $stockInsights, Collection $expiryInsights, Collection $anomalies): string
    {
        $topProduct = $salesSummary['top_product_name']
            ? ' Le produit le plus vendu aujourd hui est '.$salesSummary['top_product_name'].' avec '.$salesSummary['top_product_quantity'].' unite(s).'
            : ' Aucun produit dominant n a encore ete identifie aujourd hui.';

        return 'Aujourd hui, le magasin a realise '.$salesSummary['today_sales'].' vente(s) pour un chiffre d affaires de '
            .number_format($salesSummary['today_revenue'], 0, ',', ' ').' FCFA.'
            .$topProduct.' '.$stockInsights->where('severity', 'critical')->count().' produit(s) presentent un risque critique de rupture, '
            .$expiryInsights->count().' lot(s) sont proches de la peremption et '
            .$anomalies->count().' anomalie(s) meritent une verification.';
    }

    private function stockSeverity(Product $product, ?float $daysToStockout): string
    {
        if ($product->quantity <= 0 || ($daysToStockout !== null && $daysToStockout <= 3)) {
            return 'critical';
        }

        if ($daysToStockout !== null && $daysToStockout <= 7) {
            return 'high';
        }

        if ($product->quantity <= $product->alert_quantity || ($daysToStockout !== null && $daysToStockout <= 14)) {
            return 'medium';
        }

        return 'stable';
    }

    private function stockReason(Product $product, float $averageDailySales, ?float $daysToStockout, int $recommendedQuantity): string
    {
        if ($averageDailySales <= 0) {
            return $product->quantity <= $product->alert_quantity
                ? 'Produit sous le seuil d alerte, sans vente recente detectee.'
                : 'Stock stable sur la periode analysee.';
        }

        $daysText = $daysToStockout === null ? 'indetermine' : $daysToStockout.' jour(s)';

        return 'Vente moyenne: '.round($averageDailySales, 2).' unite(s)/jour. Rupture estimee: '.$daysText.'. Recommandation: '.$recommendedQuantity.' unite(s).';
    }

    private function restockConfidence(int $soldQuantity, int $recentQuantity, int $activeSaleDays): string
    {
        if ($soldQuantity >= 30 && $recentQuantity >= 5 && $activeSaleDays >= 5) {
            return 'high';
        }

        if ($soldQuantity >= 8 && $activeSaleDays >= 2) {
            return 'medium';
        }

        return 'low';
    }

    private function priorityScore(string $severity, ?float $daysToStockout, int $soldQuantity, int $recommendedQuantity, int $expiryRisk, string $confidence): int
    {
        $score = match ($severity) {
            'critical' => 55,
            'high' => 40,
            'medium' => 25,
            default => 10,
        };

        if ($daysToStockout !== null) {
            $score += max(0, (int) round(20 - min($daysToStockout, 20)));
        }

        $score += min(15, (int) floor($soldQuantity / 3));
        $score += min(10, (int) ceil($recommendedQuantity / 5));
        $score += min(10, $expiryRisk * 5);
        $score += match ($confidence) {
            'high' => 5,
            'medium' => 2,
            default => 0,
        };

        return min(100, $score);
    }

    private function actionPlan(string $severity, int $recommendedQuantity, int $expiryRisk, string $confidence): string
    {
        if ($severity === 'critical' && $recommendedQuantity > 0) {
            return 'Commander en priorite et bloquer une verification de stock physique.';
        }

        if ($expiryRisk > 0) {
            return 'Lancer une promotion courte ou rapprocher les lots proches de la caisse.';
        }

        if ($recommendedQuantity > 0 && $confidence === 'low') {
            return 'Verifier l historique avant commande, puis completer le stock si la tendance se confirme.';
        }

        if ($recommendedQuantity > 0) {
            return 'Planifier le reapprovisionnement dans le prochain cycle d achat.';
        }

        return 'Surveiller sans action immediate.';
    }

    private function severityWeight(string $severity): int
    {
        return match ($severity) {
            'critical' => 0,
            'high' => 1,
            'medium' => 2,
            default => 3,
        };
    }
}

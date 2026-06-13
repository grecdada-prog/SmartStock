<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AiAnalysisSnapshot;
use App\Models\ActivityLog;
use App\Services\SmartStoreAiAssistantService;
use Barryvdh\DomPDF\Facade\Pdf;

class AiAssistantController extends Controller
{
    public function index(SmartStoreAiAssistantService $assistant)
    {
        $analysis = $assistant->analyze(auth()->user());
        $snapshot = $this->createSnapshot($analysis);
        $latestSnapshots = AiAnalysisSnapshot::where('manager_id', auth()->id())
            ->latest('generated_at')
            ->limit(5)
            ->get();

        ActivityLog::log(
            'smartstore_ai_assistant_viewed',
            'Consultation du module SmartStore AI Assistant',
            'SmartStoreAiAssistant',
            $snapshot->id,
            [
                'critical_stock' => $analysis['kpis']['critical_stock'],
                'anomalies' => $analysis['kpis']['anomalies'],
                'snapshot_id' => $snapshot->id,
            ]
        );

        return view('manager.ai-assistant.index', compact('analysis', 'snapshot', 'latestSnapshots'));
    }

    public function exportPdf(SmartStoreAiAssistantService $assistant)
    {
        $analysis = $assistant->analyze(auth()->user());
        $snapshot = $this->createSnapshot($analysis);

        ActivityLog::log(
            'smartstore_ai_report_exported',
            'Export PDF du rapport SmartStore AI Assistant',
            'SmartStoreAiAssistant',
            $snapshot->id,
            [
                'critical_stock' => $analysis['kpis']['critical_stock'],
                'anomalies' => $analysis['kpis']['anomalies'],
                'snapshot_id' => $snapshot->id,
            ]
        );

        $pdf = Pdf::loadView('manager.ai-assistant.report-pdf', [
            'analysis' => $analysis,
            'snapshot' => $snapshot,
            'manager' => auth()->user(),
        ])->setPaper('a4');

        return $pdf->download('rapport_ia_smartstore_'.now()->format('Y-m-d_H-i-s').'.pdf');
    }

    private function createSnapshot(array $analysis): AiAnalysisSnapshot
    {
        return AiAnalysisSnapshot::create([
            'manager_id' => auth()->id(),
            'generated_at' => $analysis['generated_at'],
            'period_days' => $analysis['period_days'],
            'target_days' => $analysis['target_days'],
            'kpis' => $analysis['kpis'],
            'stock_predictions' => $this->snapshotStockPredictions($analysis['stock_predictions']),
            'expiry_alerts' => $this->snapshotExpiryAlerts($analysis['expiry_alerts']),
            'anomalies' => $analysis['anomalies']->values()->all(),
            'narrative' => $analysis['narrative'],
            'methodology' => $analysis['methodology'],
        ]);
    }

    private function snapshotStockPredictions($predictions): array
    {
        return $predictions
            ->map(function (array $prediction) {
                $product = $prediction['product'];

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                    'unit' => $product->unit,
                    'current_quantity' => (int) $product->quantity,
                    'alert_quantity' => (int) $product->alert_quantity,
                    'category' => $prediction['category'],
                    'sold_quantity' => $prediction['sold_quantity'],
                    'recent_quantity' => $prediction['recent_quantity'],
                    'active_sale_days' => $prediction['active_sale_days'],
                    'average_daily_sales' => $prediction['average_daily_sales'],
                    'days_to_stockout' => $prediction['days_to_stockout'],
                    'recommended_quantity' => $prediction['recommended_quantity'],
                    'severity' => $prediction['severity'],
                    'confidence' => $prediction['confidence'],
                    'priority_score' => $prediction['priority_score'],
                    'action' => $prediction['action'],
                    'reason' => $prediction['reason'],
                ];
            })
            ->values()
            ->all();
    }

    private function snapshotExpiryAlerts($alerts): array
    {
        return $alerts
            ->map(function (array $alert) {
                $movement = $alert['movement'];
                $product = $alert['product'];

                return [
                    'stock_movement_id' => $movement->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit' => $product->unit,
                    'batch_code' => $movement->batch_code ?? 'LOT-'.$movement->id,
                    'remaining_quantity' => (int) $movement->remaining_quantity,
                    'expiration_date' => $movement->expiration_date?->toDateString(),
                    'days_left' => $alert['days_left'],
                    'severity' => $alert['severity'],
                    'message' => $alert['message'],
                ];
            })
            ->values()
            ->all();
    }
}

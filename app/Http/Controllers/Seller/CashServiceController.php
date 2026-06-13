<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\CashBalanceAdjustment;
use App\Services\CashRegisterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashServiceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $cash = app(CashRegisterService::class);

        $cashBalance = $cash->balanceForSeller($user);
        $mobileMoneyBalance = $cash->mobileMoneyBalanceForSeller($user);

        return view('seller.services.index', compact('cashBalance', 'mobileMoneyBalance'));
    }

    public function store(Request $request, CashRegisterService $cash)
    {
        $validated = $request->validate([
            'operation' => ['required', 'in:depot,retrait'],
            'amount' => ['required', 'numeric', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ], [
            'operation.required' => 'Choisissez une operation.',
            'operation.in' => 'Operation invalide.',
            'amount.required' => 'Saisissez le montant.',
            'amount.min' => 'Le montant doit etre superieur a 0.',
        ]);

        $amount = (float) $validated['amount'];
        $operation = $validated['operation'];

        try {
            $cash->recordServiceTransfer(
                Auth::user(),
                $operation,
                $amount,
                $validated['reason'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        $label = $operation === 'depot' ? 'Depot' : 'Retrait';
        $formattedAmount = number_format($amount, 0, ',', ' ');

        return redirect()->route('seller.services.index')
            ->with('success', "{$label} de {$formattedAmount} FCFA enregistre avec succes.");
    }

    public function history(Request $request)
    {
        $seller = Auth::user();

        $query = CashBalanceAdjustment::where('seller_id', $seller->id)
            ->where('source', 'service')
            ->where('balance_type', CashRegisterService::CASH_BALANCE_TYPE)
            ->latest();

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('operation')) {
            $query->where('type', $request->operation === 'depot' ? 'add' : 'withdraw');
        }

        $operations = $query->paginate(20)->withQueryString();

        $totalsQuery = CashBalanceAdjustment::where('seller_id', $seller->id)
            ->where('source', 'service')
            ->where('balance_type', CashRegisterService::CASH_BALANCE_TYPE);

        if ($request->filled('date_from')) {
            $totalsQuery->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $totalsQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $stats = [
            'total_depots' => (clone $totalsQuery)->where('type', 'add')->sum('amount'),
            'total_retraits' => (clone $totalsQuery)->where('type', 'withdraw')->sum('amount'),
            'count_depots' => (clone $totalsQuery)->where('type', 'add')->count(),
            'count_retraits' => (clone $totalsQuery)->where('type', 'withdraw')->count(),
        ];

        return view('seller.services.history', compact('operations', 'stats'));
    }
}

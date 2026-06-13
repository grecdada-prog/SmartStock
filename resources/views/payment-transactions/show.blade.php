@php
    $rawPayload = $transaction->last_check_payload ?? $transaction->response_payload ?? $transaction->callback_payload;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Transaction paiement {{ $transaction->reference }}
            </h2>
            <a href="{{ url()->previous() }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 shadow-sm hover:bg-gray-50">
                Retour
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-5 lg:grid-cols-[1fr_1.2fr]">
                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-extrabold uppercase text-gray-900">Details</h3>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-gray-500">Statut</dt><dd class="font-bold">{{ $transaction->status }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-gray-500">Type</dt><dd class="font-bold">{{ $transaction->type }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-gray-500">Vendeur</dt><dd class="font-bold">{{ $transaction->seller->name ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-gray-500">Operateur</dt><dd class="font-bold">{{ $transaction->operator_label ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-gray-500">Telephone</dt><dd class="font-bold">{{ $transaction->customer_phone ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-gray-500">Montant</dt><dd class="font-bold">{{ number_format($transaction->amount, 0, ',', ' ') }} FCFA</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-gray-500">Frais</dt><dd class="font-bold">{{ number_format($transaction->operator_fee, 0, ',', ' ') }} FCFA</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-gray-500">Total</dt><dd class="font-extrabold text-rose-600">{{ number_format($transaction->total_amount, 0, ',', ' ') }} FCFA</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-gray-500">Payment ID</dt><dd class="font-bold">{{ $transaction->monetbil_payment_id ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-gray-500">Vente</dt><dd class="font-bold">{{ $transaction->sale?->invoice_number ?? '-' }}</dd></div>
                    </dl>

                    @if($transaction->failure_reason)
                        <div class="mt-5 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700">
                            {{ $transaction->failure_reason }}
                        </div>
                    @endif
                </section>

                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-extrabold uppercase text-gray-900">Reponse brute Monetbil</h3>
                    <pre class="mt-4 max-h-[520px] overflow-auto rounded-lg bg-gray-950 p-4 text-xs text-gray-100">{{ json_encode($rawPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>

@props(['sale', 'showActions' => true, 'showClose' => false, 'closeAttribute' => null])

@php
    $method = $sale->payment_method;
    $isCash = $method === 'cash';
    $isOrange = $method === 'card';
    $isMtn = $method === 'mobile_money';
    $accent = $isOrange ? '#f97316' : ($isMtn ? '#111827' : '#e11d48');
    $softAccent = $isOrange ? '#fff7ed' : ($isMtn ? '#f8fafc' : '#fff1f2');
    $borderAccent = $isOrange ? '#fdba74' : ($isMtn ? '#cbd5e1' : '#fecdd3');
    $paymentLabel = $sale->payment_method_label ?? ($isCash ? 'Especes' : ($isOrange ? 'Orange Money' : ($isMtn ? 'MTN Momo' : $method)));
    $phoneDigits = preg_replace('/\D+/', '', (string) $sale->customer_phone);
    $formattedPhone = $phoneDigits !== '' ? trim(preg_replace('/^(\d)(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1 $2 $3 $4 $5', $phoneDigits)) : '-';
    $customerName = $sale->customer_name ?: '-';
    $receiptTimestamp = ($sale->updated_at ?? $sale->created_at)->format('d/m/Y H:i');
@endphp

<article class="smartstore-receipt-card mx-auto w-full max-w-md rounded-xl border border-gray-200 bg-white text-gray-950 shadow-xl">
    <div class="relative rounded-t-xl px-6 py-5 text-center text-white" style="background: {{ $accent }};">
        @if($showClose && $closeAttribute)
            <button type="button"
                    {!! $closeAttribute !!}
                    class="absolute right-3 top-3 inline-flex h-8 w-8 items-center justify-center rounded-full border border-white/50 bg-white/10 text-xl leading-none text-white transition hover:bg-white/20"
                    aria-label="Fermer">
                &times;
            </button>
        @endif

        <div class="mb-2 flex justify-center">
            <span class="inline-flex rounded-full bg-white px-3 py-1 shadow-sm">
                <x-smartstore-logo size="xs" tone="light" />
            </span>
        </div>
        <h1 class="text-xl font-extrabold tracking-normal">RECU DE VENTE</h1>
    </div>

    <div class="space-y-5 px-5 py-5">
        <section class="space-y-2 text-sm">
            <div class="flex items-center justify-between gap-4">
                <span class="text-gray-500">N° Facture</span>
                <span class="text-right font-bold text-rose-600">{{ $sale->invoice_number }}</span>
            </div>
            <div class="flex items-center justify-between gap-4">
                <span class="text-gray-500">Date</span>
                <span class="text-right font-semibold">{{ $sale->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="flex items-center justify-between gap-4">
                <span class="text-gray-500">Vendeur</span>
                <span class="text-right font-semibold text-blue-600">{{ $sale->seller->name ?? 'N/A' }}</span>
            </div>
        </section>

        <section class="rounded-lg border px-4 py-3 text-sm shadow-sm" style="border-color: {{ $borderAccent }}; background: {{ $softAccent }};">
            <h2 class="mb-3 text-xs font-extrabold uppercase text-gray-800">
                Infos Paiement
            </h2>
            <div class="space-y-2">
                <div class="flex items-center justify-between gap-4">
                    <span class="text-gray-500">Mode de paiement</span>
                    <span class="rounded-full border border-gray-200 bg-white px-2.5 py-1 text-xs font-bold" style="color: {{ $accent }};">{{ $paymentLabel }}</span>
                </div>
                @if($isOrange || $isMtn)
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-gray-500">Numero</span>
                        <span class="font-bold tracking-wide text-gray-900">{{ $formattedPhone }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-gray-500">Nom du client</span>
                        <span class="text-right font-medium text-gray-700">{{ $customerName }}</span>
                    </div>
                @endif
            </div>
        </section>

        <section>
            <h2 class="mb-3 text-xs font-extrabold uppercase text-gray-700">Articles</h2>
            <div class="divide-y divide-gray-200">
                @foreach($sale->items as $item)
                    <div class="flex items-start justify-between gap-4 py-2 first:pt-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-gray-950">{{ $item->product->name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500">{{ $item->quantity }} x {{ number_format($item->unit_price, 0, ',', ' ') }} FCFA</p>
                        </div>
                        <p class="shrink-0 text-sm font-bold text-gray-950">{{ number_format($item->subtotal, 0, ',', ' ') }} FCFA</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-lg bg-gray-50 px-4 py-3">
            <div class="flex items-center justify-between gap-4 border-b border-gray-200 pb-3">
                <span class="text-sm font-bold">Total</span>
                <span class="text-xl font-extrabold text-rose-600">{{ number_format($sale->total, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="mt-3 space-y-2 text-sm">
                @if($isCash)
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-gray-600">Montant recu</span>
                        <span class="font-bold">{{ number_format($sale->amount_received ?? $sale->total, 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-gray-600">Monnaie rendue</span>
                        <span class="rounded-full bg-green-50 px-3 py-1 font-bold text-green-700">{{ number_format($sale->change_given ?? 0, 0, ',', ' ') }} FCFA</span>
                    </div>
                @endif
            </div>
        </section>

        @if($sale->notes)
            <section class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900">
                <span class="font-bold">Note:</span> {{ $sale->notes }}
            </section>
        @endif

        <footer class="border-t border-dashed border-gray-200 pt-4 text-center">
            <p class="text-sm font-extrabold text-rose-600">Merci pour votre achat!</p>
            <p class="mt-1 text-xs text-gray-500">{{ $receiptTimestamp }}</p>
        </footer>

        @if($showActions)
            <div class="grid grid-cols-2 gap-3 print:hidden">
                <button type="button"
                        onclick="window.print()"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-900 transition hover:bg-gray-50">
                    Imprimer
                </button>
                <button type="button"
                        onclick="window.print()"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-900 transition hover:bg-gray-50">
                    PDF
                </button>
            </div>
        @endif
    </div>
</article>

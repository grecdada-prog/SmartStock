@extends('seller.layouts.app')

@section('title', 'Recu de Vente')

@section('content')
<div class="min-h-screen bg-slate-50 px-4 py-8">
    <x-sale-receipt-card :sale="$sale" :show-actions="true" />

    @unless(request()->boolean('modal'))
        <div class="mx-auto mt-4 max-w-md text-center print:hidden">
            <a href="{{ route('seller.pos.index') }}"
               class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-2 text-sm font-bold text-gray-900 transition hover:bg-gray-50">
                Retour au POS
            </a>
        </div>
    @endunless
</div>

<style>
@media print {
    body,
    .bg-slate-50 {
        background: #ffffff !important;
    }

    .smartstore-navbar,
    .smartstore-dashboard-footer,
    .print\:hidden {
        display: none !important;
    }

    .min-h-screen {
        min-height: auto !important;
        padding: 0 !important;
    }

    .smartstore-receipt-card {
        box-shadow: none !important;
        margin: 0 auto !important;
    }
}
</style>
@endsection

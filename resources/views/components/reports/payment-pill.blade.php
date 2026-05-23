@props(['method'])

@php
    $classes = match ($method) {
        'cash' => 'reports-pill reports-pill--cash',
        'card', 'mobile_money' => 'reports-pill reports-pill--mobile',
        default => 'reports-pill',
    };
    $label = match ($method) {
        'cash' => 'Espèces',
        'card' => 'Orange Money',
        'mobile_money' => 'MTN Momo',
        default => $method ?? 'N/A',
    };
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>{{ $label }}</span>

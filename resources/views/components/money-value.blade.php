@props([
    'amount',
    'state' => 'visible',
    'class' => '',
])

<span
    {{ $attributes->merge(['class' => $class]) }}
    x-text="{{ $state }} ? @js($amount) : '******'"
></span>

@props([
    'state' => 'visible',
    'label' => 'montant',
    'refreshOnShow' => false,
    'class' => 'money-eye-btn',
])

<button
    type="button"
    x-on:click="{{ $state }} = !{{ $state }}@if($refreshOnShow); if ({{ $state }}) { window.dispatchEvent(new CustomEvent('smartstore:refresh-now', { detail: { force: true } })); }@endif"
    {{ $attributes->merge(['class' => $class, 'type' => 'button']) }}
    :aria-label="{{ $state }} ? @js('Masquer ' . $label) : @js('Afficher ' . $label)"
>
    <svg x-show="!{{ $state }}" class="money-eye-btn__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z" />
    </svg>
    <svg x-show="{{ $state }}" x-cloak class="money-eye-btn__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-5 0-9-4-10-7 0.4-1.2 1.2-2.3 2.2-3.3M3 3l18 18" />
    </svg>
</button>

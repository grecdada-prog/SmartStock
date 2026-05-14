@props([
    'amount',
    'label' => 'montant',
    'valueClass' => 'text-2xl font-semibold text-gray-900',
])

<div x-data="{ visible: false }" class="flex items-center gap-2">
    <span class="{{ $valueClass }}" x-text="visible ? @js($amount) : '******'"></span>
    <button
        type="button"
        @click="visible = !visible; if (visible) window.dispatchEvent(new CustomEvent('smartstore:refresh-now', { detail: { force: true } }))"
        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-gray-400 hover:bg-gray-50 hover:text-gray-700"
        :aria-label="visible ? 'Masquer {{ $label }}' : 'Afficher {{ $label }}'"
    >
        <svg x-show="!visible" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z" />
        </svg>
        <svg x-show="visible" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-5 0-9-4-10-7 0.4-1.2 1.2-2.3 2.2-3.3M3 3l18 18" />
        </svg>
    </button>
</div>

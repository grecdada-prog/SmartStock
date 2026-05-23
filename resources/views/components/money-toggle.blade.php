@props([
    'amount',
    'label' => 'montant',
    'valueClass' => 'text-2xl font-semibold text-gray-900',
    'stateKey' => null,
])

@php
    $visibilityKey = $stateKey ?? md5($label);
@endphp

<div
    x-data="{
        key: @js($visibilityKey),
        visible: false,
        init() {
            window.SmartStoreMoneyVisibility = window.SmartStoreMoneyVisibility || {};
            this.visible = window.SmartStoreMoneyVisibility[this.key] === true;

            this.$watch('visible', (value) => {
                window.SmartStoreMoneyVisibility[this.key] = value;
            });
        },
    }"
    class="money-amount-row"
>
    <span class="{{ $valueClass }} min-w-0 max-w-full leading-tight" x-text="visible ? @js($amount) : '******'"></span>
    <x-money-eye-button state="visible" :label="$label" refresh-on-show />
</div>

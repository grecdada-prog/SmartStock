@props([
    'title',
    'amount',
    'label' => 'montant',
    'footer' => null,
    'valueClass' => 'text-2xl font-semibold text-gray-900',
    'stateKey' => null,
    'refreshOnShow' => true,
])

@php
    $visibilityKey = $stateKey ?? md5($title . $label);
@endphp

<div
    {{ $attributes->merge(['class' => 'money-stat-card relative rounded-lg bg-white p-5 shadow']) }}
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
>
    <div class="pr-7">
        <p class="min-w-0 text-sm font-medium leading-snug text-gray-500">{{ $title }}</p>
        <x-money-eye-button
            state="visible"
            :label="$label"
            :refresh-on-show="$refreshOnShow"
            class="money-eye-btn absolute right-3 top-3"
        />
    </div>
    <p class="{{ $valueClass }} mt-2 min-w-0 break-words leading-tight" x-text="visible ? @js($amount) : '******'"></p>
    @if ($footer)
        <p class="mt-2 min-w-0 break-words text-sm leading-snug text-gray-500">{{ $footer }}</p>
    @endif
</div>

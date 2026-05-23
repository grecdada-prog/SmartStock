@props([
    'tone' => 'light',
    'size' => 'md',
])

<div {{ $attributes->merge(['class' => "smartstore-logo smartstore-logo--{$tone} smartstore-logo--{$size}"]) }}>
    <span class="smartstore-logo__dot"></span>
    <span class="smartstore-logo__text">
        <span class="smartstore-logo__smart">Smart</span><span class="smartstore-logo__store">Store</span>
    </span>
</div>

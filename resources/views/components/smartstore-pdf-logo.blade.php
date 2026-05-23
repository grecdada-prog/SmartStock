@props([
    'title' => null,
    'subtitle' => null,
    'tone' => 'light',
])

@php
    $smartColor = $tone === 'dark' ? '#ffffff' : '#111827';
@endphp

<div style="text-align: center; margin-bottom: 18px;">
    <div style="display: inline-block; font-family: DejaVu Sans, Arial, sans-serif; font-weight: 800; font-size: 22px; line-height: 1;">
        <span style="display: inline-block; width: 8px; height: 8px; margin-right: 7px; border-radius: 999px; background: #ff0033; vertical-align: middle;"></span>
        <span style="color: {{ $smartColor }};">Smart</span><span style="color: #ff0033;">Store</span>
    </div>
    @if($title)
        <div style="margin-top: 10px; font-size: 18px; font-weight: 700; color: #111827;">{{ $title }}</div>
    @endif
    @if($subtitle)
        <div style="margin-top: 4px; font-size: 11px; color: #6b7280;">{{ $subtitle }}</div>
    @endif
</div>

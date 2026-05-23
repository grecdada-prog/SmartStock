@props([
    'label',
    'value',
    'hint' => null,
    'badge' => null,
    'badgeTrend' => null,
    'valueClass' => '',
    'wide' => false,
])

<div @class([
    'reports-kpi',
    'reports-kpi--wide' => $wide,
])>
    <p class="reports-kpi__label">{{ $label }}</p>
    <p @class(['reports-kpi__value', $valueClass])>{{ $value }}</p>
    @if ($hint)
        <p class="reports-kpi__hint">{{ $hint }}</p>
    @endif
    @if ($badge)
        <span @class([
            'reports-kpi__badge',
            'reports-kpi__badge--up' => $badgeTrend === 'up',
            'reports-kpi__badge--down' => $badgeTrend === 'down',
        ])>{{ $badge }}</span>
    @endif
</div>

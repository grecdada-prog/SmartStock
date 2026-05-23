@props(['title', 'meta' => null])

<section {{ $attributes->merge(['class' => 'reports-panel']) }}>
    <div class="reports-panel__head">
        <h2 class="reports-panel__title">{{ $title }}</h2>
        @if ($meta)
            <span class="reports-panel__meta">{{ $meta }}</span>
        @endif
    </div>
    <div class="reports-panel__body">
        {{ $slot }}
    </div>
</section>

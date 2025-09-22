{{-- ======================================== --}}
{{-- STAT CARD COMPONENT --}}
{{-- Reusable statistics card for dashboard --}}
{{-- ======================================== --}}

@props([
    'title',
    'value',
    'change' => null,
    'changeType' => 'neutral', // positive, negative, neutral
    'icon' => null,
    'type' => 'default', // primary, success, warning, info, error, default
    'href' => null
])

@php
$classes = 'stat-card';
if ($type !== 'default') {
    $classes .= ' ' . $type;
}

$component = $href ? 'a' : 'div';
$attributes = $href ? ['href' => $href] : [];
@endphp

<{{ $component }} class="{{ $classes }}" @foreach($attributes as $key => $val) {{ $key }}="{{ $val }}" @endforeach>
    <div class="stat-card-header">
        <div>
            <div class="stat-card-title">{{ $title }}</div>
        </div>

        @if($icon)
        <div class="stat-card-icon">
            <i class="{{ $icon }}"></i>
        </div>
        @endif
    </div>

    <div class="stat-card-value">
        {{ is_numeric($value) ? number_format($value) : $value }}
    </div>

    @if($change !== null)
    <div class="stat-card-change {{ $changeType }}">
        @if($changeType === 'positive')
            <i class="fas fa-arrow-up"></i>
        @elseif($changeType === 'negative')
            <i class="fas fa-arrow-down"></i>
        @else
            <i class="fas fa-minus"></i>
        @endif
        <span>{{ $change }}</span>
    </div>
    @endif
</{{ $component }}>

@once
@push('styles')
<style>
/* Additional stat card hover effects for links */
a.stat-card {
    text-decoration: none;
    color: inherit;
}

a.stat-card:hover {
    text-decoration: none;
    color: inherit;
}

/* Pulse animation for real-time updates */
.stat-card.updating {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(16, 185, 129, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
    }
}
</style>
@endpush
@endonce
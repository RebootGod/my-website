{{-- ======================================== --}}
{{-- BREADCRUMBS COMPONENT --}}
{{-- ======================================== --}}
{{-- File: resources/views/admin/components/breadcrumbs.blade.php --}}

{{--
Usage:
@include('admin.components.breadcrumbs', [
    'items' => [
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Movies', 'url' => route('admin.movies.index')],
        ['label' => 'Edit Movie', 'url' => null] // null = current page
    ]
])
--}}

@props(['items' => []])

@if(count($items) > 0)
<nav class="breadcrumb-nav" aria-label="Breadcrumb">
    <ol class="breadcrumb-list">
        {{-- Home Icon --}}
        <li class="breadcrumb-item">
            <a href="{{ route('admin.dashboard') }}" class="breadcrumb-link" title="Dashboard">
                <i class="fas fa-home"></i>
            </a>
        </li>

        {{-- Breadcrumb Items --}}
        @foreach($items as $index => $item)
            <li class="breadcrumb-item">
                <span class="breadcrumb-separator">
                    <i class="fas fa-chevron-right"></i>
                </span>

                @if($item['url'] ?? false)
                    {{-- Clickable link --}}
                    <a href="{{ $item['url'] }}" class="breadcrumb-link">
                        @if($item['icon'] ?? false)
                            <i class="{{ $item['icon'] }}"></i>
                        @endif
                        {{ $item['label'] }}
                    </a>
                @else
                    {{-- Current page (not clickable) --}}
                    <span class="breadcrumb-current" aria-current="page">
                        @if($item['icon'] ?? false)
                            <i class="{{ $item['icon'] }}"></i>
                        @endif
                        {{ $item['label'] }}
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
@endif

{{--
    Loading Spinner Component
    
    Reusable loading spinner for Blade templates
    
    Usage:
        @include('admin.components.loading-spinner', ['size' => 'medium', 'text' => 'Loading...'])
    
    Parameters:
        - size: 'small' | 'medium' | 'large' (default: 'medium')
        - text: Loading text (optional)
        - inline: true | false (default: false)
        - color: 'primary' | 'success' | 'warning' | 'danger' (default: 'primary')
--}}

@php
    $size = $size ?? 'medium';
    $text = $text ?? '';
    $inline = $inline ?? false;
    $color = $color ?? 'primary';
    
    $sizeClasses = [
        'small' => 'w-4 h-4',
        'medium' => 'w-8 h-8',
        'large' => 'w-12 h-12',
    ];
    
    $colorClasses = [
        'primary' => 'border-blue-500',
        'success' => 'border-green-500',
        'warning' => 'border-yellow-500',
        'danger' => 'border-red-500',
    ];
    
    $spinnerSize = $sizeClasses[$size] ?? $sizeClasses['medium'];
    $spinnerColor = $colorClasses[$color] ?? $colorClasses['primary'];
@endphp

@if($inline)
    <span class="inline-flex items-center gap-2">
        <span class="inline-block {{ $spinnerSize }} border-2 border-gray-600 {{ $spinnerColor }} border-t-transparent rounded-full animate-spin"></span>
        @if($text)
            <span class="text-sm text-gray-400">{{ $text }}</span>
        @endif
    </span>
@else
    <div class="flex flex-col items-center justify-center p-8">
        <div class="{{ $spinnerSize }} border-4 border-gray-700 {{ $spinnerColor }} border-t-transparent rounded-full animate-spin"></div>
        @if($text)
            <p class="mt-4 text-sm text-gray-400">{{ $text }}</p>
        @endif
    </div>
@endif

{{--
    Top Content Widget Component
    
    Usage:
    @include('admin.components.widgets.top-content-widget', [
        'id' => 'top-movies',
        'title' => 'Top Movies',
        'contentType' => 'movies', // movies, series
        'items' => $topMovies, // Collection of Movie/Series models
        'limit' => 5,
        'showThumbnails' => true,
        'showViews' => true,
        'showRating' => true
    ])
--}}

@php
    $widgetId = $id ?? 'top-content-' . uniqid();
    $title = $title ?? 'Top Content';
    $contentType = $contentType ?? 'movies'; // movies, series
    $items = $items ?? collect();
    $limit = $limit ?? 5;
    $showThumbnails = $showThumbnails ?? true;
    $showViews = $showViews ?? true;
    $showRating = $showRating ?? true;
    $viewAllUrl = $viewAllUrl ?? route('admin.' . $contentType . '.index');
@endphp

<div class="dashboard-widget top-content-widget" 
     id="{{ $widgetId }}" 
     data-widget-id="{{ $widgetId }}"
     draggable="true">
    
    {{-- Widget Header --}}
    <div class="widget-header">
        <div class="widget-info">
            <div class="widget-drag-handle" title="Drag to reorder">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                </svg>
            </div>
            <h3 class="widget-title">{{ $title }}</h3>
        </div>
        <div class="widget-controls">
            <a href="{{ $viewAllUrl }}" class="widget-control-btn" title="View all">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
            <button type="button" 
                    class="widget-control-btn widget-hide-btn" 
                    onclick="window.dashboardWidgets?.hideWidget('{{ $widgetId }}')"
                    title="Hide widget">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
    
    {{-- Content List --}}
    <div class="widget-content">
        @if($items->isEmpty())
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                </svg>
                <p>No {{ $contentType }} found</p>
            </div>
        @else
            <div class="content-list">
                @foreach($items->take($limit) as $index => $item)
                    <div class="content-item">
                        {{-- Rank Badge --}}
                        <div class="content-rank rank-{{ $index + 1 }}">
                            {{ $index + 1 }}
                        </div>
                        
                        {{-- Thumbnail --}}
                        @if($showThumbnails)
                            <div class="content-thumbnail">
                                @if($item->poster_path)
                                    <img src="{{ $item->poster_path }}" 
                                         alt="{{ $item->title }}"
                                         loading="lazy"
                                         onerror="this.src='/images/no-poster.png'">
                                @else
                                    <div class="thumbnail-placeholder">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                @endif
                            </div>
                        @endif
                        
                        {{-- Details --}}
                        <div class="content-details">
                            <a href="{{ route('admin.' . $contentType . '.edit', $item->id) }}" 
                               class="content-title">
                                {{ Str::limit($item->title, 40) }}
                            </a>
                            
                            <div class="content-meta">
                                {{-- Views --}}
                                @if($showViews && isset($item->views_count))
                                    <span class="meta-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        {{ number_format($item->views_count) }}
                                    </span>
                                @endif
                                
                                {{-- Rating --}}
                                @if($showRating && isset($item->vote_average))
                                    <span class="meta-item rating">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                        </svg>
                                        {{ number_format($item->vote_average, 1) }}
                                    </span>
                                @endif
                                
                                {{-- Year --}}
                                @if(isset($item->release_date))
                                    <span class="meta-item">
                                        {{ \Carbon\Carbon::parse($item->release_date)->format('Y') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        {{-- Quick Actions --}}
                        <div class="content-actions">
                            <a href="{{ route('admin.' . $contentType . '.edit', $item->id) }}" 
                               class="action-btn" 
                               title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<style>
.top-content-widget .widget-content {
    padding: 0;
}

.content-list {
    display: flex;
    flex-direction: column;
}

.content-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--dashboard-border, rgba(255, 255, 255, 0.1));
    transition: background 0.2s;
}

.content-item:last-child {
    border-bottom: none;
}

.content-item:hover {
    background: rgba(255, 255, 255, 0.05);
}

.content-rank {
    flex-shrink: 0;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.875rem;
    border-radius: 0.5rem;
    background: rgba(255, 255, 255, 0.1);
    color: var(--dashboard-text-primary);
}

/* Top 3 special colors */
.content-rank.rank-1 {
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    color: #1f2937;
}

.content-rank.rank-2 {
    background: linear-gradient(135deg, #c0c0c0 0%, #e8e8e8 100%);
    color: #1f2937;
}

.content-rank.rank-3 {
    background: linear-gradient(135deg, #cd7f32 0%, #e5a66d 100%);
    color: #1f2937;
}

.content-thumbnail {
    flex-shrink: 0;
    width: 3.5rem;
    height: 5.25rem;
    border-radius: 0.375rem;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.05);
}

.content-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.thumbnail-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.05);
}

.thumbnail-placeholder svg {
    width: 1.5rem;
    height: 1.5rem;
    opacity: 0.3;
}

.content-details {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.content-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--dashboard-text-primary);
    text-decoration: none;
    transition: color 0.2s;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.content-title:hover {
    color: var(--dashboard-primary, #3b82f6);
}

.content-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8125rem;
    opacity: 0.7;
}

.meta-item svg {
    width: 1rem;
    height: 1rem;
}

.meta-item.rating {
    color: #fbbf24;
    opacity: 1;
}

.content-actions {
    flex-shrink: 0;
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    padding: 0.5rem;
    background: transparent;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: background 0.2s;
    color: var(--dashboard-text-primary);
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
}

.action-btn:hover {
    background: rgba(255, 255, 255, 0.1);
}

.action-btn svg {
    width: 1.125rem;
    height: 1.125rem;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 1rem;
    gap: 1rem;
}

.empty-state svg {
    width: 4rem;
    height: 4rem;
    opacity: 0.3;
}

.empty-state p {
    font-size: 0.875rem;
    opacity: 0.6;
    margin: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .content-item {
        padding: 0.875rem 1rem;
    }
    
    .content-rank {
        width: 1.75rem;
        height: 1.75rem;
        font-size: 0.8125rem;
    }
    
    .content-thumbnail {
        width: 3rem;
        height: 4.5rem;
    }
    
    .content-title {
        font-size: 0.875rem;
    }
    
    .meta-item {
        font-size: 0.75rem;
    }
    
    .content-actions {
        display: none;
    }
}

/* Density modes */
.density-compact .content-item {
    padding: 0.625rem 1rem;
    gap: 0.75rem;
}

.density-compact .content-thumbnail {
    width: 2.5rem;
    height: 3.75rem;
}

.density-compact .content-rank {
    width: 1.5rem;
    height: 1.5rem;
    font-size: 0.75rem;
}

.density-comfortable .content-item {
    padding: 1.25rem 1.5rem;
    gap: 1.25rem;
}

.density-comfortable .content-thumbnail {
    width: 4rem;
    height: 6rem;
}
</style>

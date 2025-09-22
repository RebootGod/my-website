{{-- ======================================== --}}
{{-- 1. MOVIE CARD COMPONENT --}}
{{-- ======================================== --}}
{{-- File: resources/views/components/movie-card.blade.php --}}

<a href="{{ route('movies.show', $movie->slug) }}" class="text-decoration-none">
    <div class="card bg-dark text-white h-100 movie-card">
        {{-- Poster --}}
        <div class="position-relative">
            @if($movie->poster_url)
                <img 
                    src="{{ $movie->poster_url }}" 
                    alt="{{ $movie->title }}"
                    class="card-img-top"
                    style="height: 350px; object-fit: cover;"
                    loading="lazy"
                >
            @else
                <div class="card-img-top bg-primary d-flex align-items-center justify-content-center text-white fw-bold" style="height: 350px;">
                    {{ $movie->title }}
                </div>
            @endif
            
            {{-- Quality Badge --}}
            <span class="position-absolute top-0 end-0 badge bg-warning text-dark m-2">
                {{ $movie->quality ?? 'HD' }}
            </span>
            
            {{-- Rating Badge --}}
            @if($movie->rating)
                <span class="position-absolute top-0 start-0 badge bg-success m-2">
                    <i class="fas fa-star"></i> {{ number_format($movie->rating, 1) }}
                </span>
            @endif
        </div>
        
        {{-- Info --}}
        <div class="card-body">
            <h6 class="card-title text-truncate" title="{{ $movie->title }}">{{ $movie->title }}</h6>
            <div class="small text-muted">
                <div class="mb-1">
                    <i class="fas fa-calendar me-1"></i>{{ $movie->year ?? 'N/A' }}
                </div>
                <div class="mb-1">
                    <i class="fas fa-tags me-1"></i>{{ $movie->genres->pluck('name')->take(2)->join(', ') ?: 'N/A' }}
                </div>
                @if($movie->runtime)
                    <div class="mb-1">
                        <i class="fas fa-clock me-1"></i>{{ $movie->runtime }} min
                    </div>
                @endif
            </div>
            
            <div class="mt-2">
                <small class="text-success">
                    <i class="fas fa-eye me-1"></i>{{ number_format($movie->view_count ?? 0) }} views
                </small>
            </div>
        </div>
        
        {{-- Hover Effect --}}
        <div class="card-hover-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.8); opacity: 0; transition: opacity 0.3s;">
            <button class="btn btn-primary">
                <i class="fas fa-play me-2"></i>Watch Now
            </button>
        </div>
    </div>
</a>
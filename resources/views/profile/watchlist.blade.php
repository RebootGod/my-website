@extends('layouts.app')

@section('title', 'Watchlist Saya')

@section('content')
<div class="container mt-4">
    <h1 class="text-white">Watchlist Saya</h1>
    <p class="text-light">Total: {{ $movies->total() }} film tersimpan</p>
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if($movies->count() > 0)
        <div class="row">
            @foreach($movies as $movie)
                                <div class="col-md-3 mb-4">
                    <div class="card bg-dark text-white h-100">
                        <img src="https://image.tmdb.org/t/p/w400{{ $movie->poster_path }}" 
                             class="card-img-top" 
                             alt="{{ $movie->title }}"
                             style="height: 300px; object-fit: cover;"
                             onerror="this.src='https://via.placeholder.com/400x600/2d3748/ffffff?text=No+Image'">
                        <div class="card-body">
                            <h6>{{ $movie->title }}</h6>
                            <p class="card-text small text-muted">
                                {{ Str::limit($movie->overview ?? 'No description available.', 100) }}
                            </p>
                            <div class="d-flex justify-content-between mt-auto">
                                <a href="{{ route('movies.show', $movie->id) }}" class="btn btn-primary btn-sm">Tonton</a>
                                <form action="{{ route('watchlist.remove', $movie->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Hapus?')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        {{ $movies->links() }}
    @else
        <div class="text-center py-5">
            <h3 class="text-white">Watchlist Kosong</h3>
            <a href="{{ route('home') }}" class="btn btn-primary">Jelajahi Film</a>
        </div>
    @endif
</div>
@endsection

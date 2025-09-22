{{-- ======================================== --}}
{{-- 4. GENRE PAGE --}}
{{-- ======================================== --}}
{{-- File: resources/views/movies/genre.blade.php --}}

@extends('layouts.app')

@section('title', $genre->name . ' Movies - Noobz Cinema')

@section('content')
<div class="container mx-auto px-6">
    <h1 class="text-3xl font-bold mb-6">Genre: {{ $genre->name }}</h1>
    
    {{-- Movies Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
        @forelse($movies as $movie)
            @include('components.movie-card', ['movie' => $movie])
        @empty
            <div class="col-span-full text-center py-12">
                <p class="text-gray-400 text-lg">Tidak ada film dalam genre ini.</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($movies->hasPages())
    <div class="mt-8">
        {{ $movies->links() }}
    </div>
    @endif
</div>
@endsection
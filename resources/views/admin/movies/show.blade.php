@extends('layouts.admin')

@section('title', 'Movie Details - ' . $movie->title)

@section('content')
<div class="container mx-auto px-6 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-white">{{ $movie->title }}</h1>
            <p class="text-gray-400 mt-2">Movie Details & Information</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.movies.edit', $movie) }}" 
               class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg transition flex items-center">
                <i class="fas fa-edit mr-2"></i>Edit Movie
            </a>
            <a href="{{ route('admin.movies.index') }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>Back to List
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Movie Poster & Basic Info -->
        <div class="lg:col-span-1">
            <div class="bg-gray-800 rounded-lg p-6">
                <!-- Poster -->
                <div class="mb-6">
                    @if($movie->poster_url)
                        <img src="{{ $movie->poster_url }}" 
                             alt="{{ $movie->title }}" 
                             class="w-full rounded-lg shadow-lg">
                    @else
                        <div class="w-full h-96 bg-gray-700 rounded-lg flex items-center justify-center">
                            <i class="fas fa-image text-gray-500 text-4xl"></i>
                        </div>
                    @endif
                </div>

                <!-- Basic Stats -->
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Status:</span>
                        <span class="px-2 py-1 rounded text-xs font-medium
                            {{ $movie->status === 'published' ? 'bg-green-800 text-green-200' : 'bg-red-800 text-red-200' }}">
                            {{ ucfirst($movie->status) }}
                        </span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-400">Rating:</span>
                        <span class="text-white">
                            <i class="fas fa-star text-yellow-400"></i>
                            {{ $movie->rating }}/10
                        </span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-400">Views:</span>
                        <span class="text-white">{{ number_format($movie->view_count) }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-400">Duration:</span>
                        <span class="text-white">{{ $movie->duration ?? 'N/A' }} min</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-400">Quality:</span>
                        <span class="text-white">{{ $movie->quality ?? 'N/A' }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-400">Year:</span>
                        <span class="text-white">{{ $movie->year ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Movie Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Overview -->
            <div class="bg-gray-800 rounded-lg p-6">
                <h2 class="text-xl font-semibold text-white mb-4">
                    <i class="fas fa-info-circle mr-2"></i>Overview
                </h2>
                <p class="text-gray-300 leading-relaxed">
                    {{ $movie->description ?: $movie->overview ?: 'No description available.' }}
                </p>
            </div>

            <!-- Technical Details -->
            <div class="bg-gray-800 rounded-lg p-6">
                <h2 class="text-xl font-semibold text-white mb-4">
                    <i class="fas fa-cog mr-2"></i>Technical Information
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-gray-400 text-sm">TMDB ID:</span>
                        <p class="text-white">{{ $movie->tmdb_id ?: 'N/A' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-400 text-sm">IMDB ID:</span>
                        <p class="text-white">{{ $movie->imdb_id ?: 'N/A' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-400 text-sm">Slug:</span>
                        <p class="text-white">{{ $movie->slug }}</p>
                    </div>
                    <div>
                        <span class="text-gray-400 text-sm">Release Date:</span>
                        <p class="text-white">{{ $movie->release_date ? \Carbon\Carbon::parse($movie->release_date)->format('M d, Y') : 'N/A' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-400 text-sm">Language:</span>
                        <p class="text-white">{{ $movie->language ?: 'N/A' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-400 text-sm">Popularity:</span>
                        <p class="text-white">{{ $movie->popularity ?: 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Genres -->
            <div class="bg-gray-800 rounded-lg p-6">
                <h2 class="text-xl font-semibold text-white mb-4">
                    <i class="fas fa-tags mr-2"></i>Genres
                </h2>
                @if($movie->genres->count() > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach($movie->genres as $genre)
                            <span class="bg-blue-600 text-blue-100 px-3 py-1 rounded-full text-sm">
                                {{ $genre->name }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-400">No genres assigned</p>
                @endif
            </div>

            <!-- Movie Sources -->
            <div class="bg-gray-800 rounded-lg p-6">
                <h2 class="text-xl font-semibold text-white mb-4">
                    <i class="fas fa-play-circle mr-2"></i>Movie Sources
                </h2>
                @if($movie->sources->count() > 0)
                    <div class="space-y-3">
                        @foreach($movie->sources as $source)
                            <div class="bg-gray-700 rounded p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-white font-medium">{{ $source->name }}</h3>
                                        <p class="text-gray-400 text-sm">{{ $source->type }} - {{ $source->quality }}</p>
                                        @if($source->language)
                                            <p class="text-gray-400 text-sm">Language: {{ $source->language }}</p>
                                        @endif
                                    </div>
                                    <div class="flex space-x-2">
                                        @if($source->url)
                                            <a href="{{ $source->url }}" target="_blank" 
                                               class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm transition">
                                                <i class="fas fa-external-link-alt"></i> View
                                            </a>
                                        @endif
                                        <span class="px-2 py-1 rounded text-xs
                                            {{ $source->status === 'active' ? 'bg-green-800 text-green-200' : 'bg-red-800 text-red-200' }}">
                                            {{ ucfirst($source->status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-400">No sources available</p>
                @endif
            </div>

            <!-- Features -->
            <div class="bg-gray-800 rounded-lg p-6">
                <h2 class="text-xl font-semibold text-white mb-4">
                    <i class="fas fa-star mr-2"></i>Features
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex items-center">
                        @if($movie->has_subtitle)
                            <span class="text-green-400 mr-2 font-bold">✓</span>
                        @else
                            <span class="text-red-400 mr-2 font-bold">✗</span>
                        @endif
                        <span class="text-white">Subtitles</span>
                    </div>
                    <div class="flex items-center">
                        @if($movie->is_dubbed)
                            <span class="text-green-400 mr-2 font-bold">✓</span>
                        @else
                            <span class="text-red-400 mr-2 font-bold">✗</span>
                        @endif
                        <span class="text-white">Dubbed</span>
                    </div>
                    <div class="flex items-center">
                        @if($movie->is_featured)
                            <span class="text-green-400 mr-2 font-bold">✓</span>
                        @else
                            <span class="text-red-400 mr-2 font-bold">✗</span>
                        @endif
                        <span class="text-white">Featured</span>
                    </div>
                    <div class="flex items-center">
                        @if($movie->is_active)
                            <span class="text-green-400 mr-2 font-bold">✓</span>
                        @else
                            <span class="text-red-400 mr-2 font-bold">✗</span>
                        @endif
                        <span class="text-white">Active</span>
                    </div>
                </div>
            </div>

            <!-- Metadata -->
            <div class="bg-gray-800 rounded-lg p-6">
                <h2 class="text-xl font-semibold text-white mb-4">
                    <i class="fas fa-clock mr-2"></i>Metadata
                </h2>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-400">Added:</span>
                        <p class="text-white">{{ $movie->created_at->format('M d, Y H:i') }}</p>
                    </div>
                    <div>
                        <span class="text-gray-400">Last Updated:</span>
                        <p class="text-white">{{ $movie->updated_at->format('M d, Y H:i') }}</p>
                    </div>
                    <div>
                        <span class="text-gray-400">Added By:</span>
                        <p class="text-white">User ID: {{ $movie->added_by }}</p>
                    </div>
                    <div>
                        <span class="text-gray-400">Vote Count:</span>
                        <p class="text-white">{{ $movie->vote_count }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
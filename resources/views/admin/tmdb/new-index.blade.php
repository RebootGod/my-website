@extends('layouts.admin')

@section('title', 'New TMDB Import - Admin')

@push('styles')
<link rel="stylesheet" href="{{ safe_asset_version('css/admin/tmdb.css') }}">
@endpush

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">New TMDB Movie Import</h1>
        <a href="{{ route('admin.movies.index') }}" class="bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded-lg transition">
            <i class="fas fa-arrow-left mr-2"></i>Back to Movies
        </a>
    </div>

    <!-- Search Section -->
    <div class="bg-gray-800 rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Search Movies</h2>
        
        <div class="flex gap-4 mb-4">
            <div class="flex-1">
                <input type="text" 
                       id="searchInput" 
                       placeholder="Search by title or TMDB ID (e.g., 1107979)..." 
                       class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400">
            </div>
            <button id="searchBtn" 
                    class="bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded-lg transition">
                <i class="fas fa-search mr-2"></i>Search
            </button>
        </div>

        <div class="flex gap-4">
            <button id="popularBtn" 
                    class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg transition">
                <i class="fas fa-fire mr-2"></i>Popular Movies
            </button>
            <button id="trendingBtn" 
                    class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded-lg transition">
                <i class="fas fa-trending-up mr-2"></i>Trending Movies
            </button>
        </div>
    </div>

    <!-- Results -->
    <div id="resultsContainer">
        <div id="loadingIndicator" class="hidden text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            <p class="mt-2 text-gray-400">Loading movies...</p>
        </div>

        <div id="errorMessage" class="hidden bg-red-600 text-white p-4 rounded-lg mb-4">
            <span id="errorText"></span>
        </div>

        <div id="moviesGrid" class="hidden">
            <h3 class="text-xl font-semibold mb-4">Results</h3>
            <div id="moviesList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <!-- Movies will be populated here -->
            </div>
            
            <!-- Pagination -->
            <div id="pagination" class="mt-8 flex justify-center">
                <!-- Pagination buttons will be populated here -->
            </div>
        </div>

        <div id="noResults" class="hidden text-center py-12">
            <i class="fas fa-search text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-400">No movies found. Try a different search term.</p>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="importModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gray-800 rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Import Movie</h3>
                <button id="closeModal" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="importForm">
                <input type="hidden" id="tmdbId" name="tmdb_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Embed URL *</label>
                    <input type="url" 
                           id="embedUrl" 
                           name="embed_url" 
                           required
                           placeholder="https://example.com/player/movie123"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Quality *</label>
                    <select id="quality" name="quality" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
                        <option value="">Select Quality</option>
                        <option value="CAM">CAM</option>
                        <option value="HD">HD</option>
                        <option value="FHD">Full HD</option>
                        <option value="4K">4K</option>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium mb-2">Status *</label>
                    <select id="status" name="status" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
                        <option value="">Select Status</option>
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                
                <div class="flex gap-3">
                    <button type="button" id="cancelImport" class="flex-1 bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded transition">
                        Cancel
                    </button>
                    <button type="submit" id="submitImport" class="flex-1 bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded transition">
                        <span id="importBtnText">Import Movie</span>
                        <span id="importBtnLoading" class="hidden">Importing...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ safe_asset_version('js/admin/tmdb.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeTMDBAdmin({
        searchUrl: '{{ route("admin.tmdb-new.search") }}',
        popularUrl: '{{ route("admin.tmdb-new.popular") }}',
        trendingUrl: '{{ route("admin.tmdb-new.trending") }}',
        importUrl: '{{ route("admin.tmdb-new.import") }}',
        bulkImportUrl: '{{ route("admin.tmdb.bulk-import") }}',
        csrfToken: '{{ csrf_token() }}'
    });
    // All JavaScript functionality is handled by tmdb.js via initializeTMDBAdmin()

});
</script>
@endpush
@endsection
@extends('layouts.admin')

@section('title', 'New TMDB Import - Admin')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">New TMDB Series Import</h1>
        <a href="{{ route('admin.series.index') }}" class="bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded-lg transition">
            <i class="fas fa-arrow-left mr-2"></i>Back to Series
        </a>
    </div>

    <!-- Search Section -->
    <div class="bg-gray-800 rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Search Series</h2>

        <div class="flex gap-4 mb-4">
            <div class="flex-1">
                <input type="text"
                       id="searchInput"
                       placeholder="Search by title or TMDB ID (e.g., 1399)..."
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
                <i class="fas fa-fire mr-2"></i>Popular Series
            </button>
            <button id="trendingBtn"
                    class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded-lg transition">
                <i class="fas fa-trending-up mr-2"></i>Trending Series
            </button>
        </div>
    </div>

    <!-- Results -->
    <div id="resultsContainer">
        <div id="loadingIndicator" class="hidden text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            <p class="mt-2 text-gray-400">Loading series...</p>
        </div>

        <div id="errorMessage" class="hidden bg-red-600 text-white p-4 rounded-lg mb-4">
            <span id="errorText"></span>
        </div>

        <div id="seriesGrid" class="hidden">
            <h3 class="text-xl font-semibold mb-4">Results</h3>
            <div id="seriesList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <!-- Series will be populated here -->
            </div>
        </div>

        <div id="noResults" class="hidden text-center py-12">
            <i class="fas fa-search text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-400">No series found. Try a different search term.</p>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="importModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gray-800 rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Import Series</h3>
                <button id="closeModal" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="importForm">
                <input type="hidden" id="tmdbId" name="tmdb_id">

                <div class="mb-6 text-center">
                    <p class="text-sm text-gray-400 mb-2">This will import the series with:</p>
                    <ul class="text-sm text-gray-300 space-y-1">
                        <li>• Title</li>
                        <li>• Description</li>
                        <li>• Poster & Backdrop URLs</li>
                        <li>• Year</li>
                        <li>• Rating</li>
                        <li>• Status (Published)</li>
                        <li>• TMDB ID</li>
                        <li>• Genres</li>
                    </ul>
                    <p class="text-sm text-yellow-400 mt-3">Note: Episodes will be managed separately after import.</p>
                </div>

                <div class="flex gap-3">
                    <button type="button" id="cancelImport" class="flex-1 bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded transition">
                        Cancel
                    </button>
                    <button type="submit" id="submitImport" class="flex-1 bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded transition">
                        <span id="importBtnText">Import Series</span>
                        <span id="importBtnLoading" class="hidden">Importing...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const popularBtn = document.getElementById('popularBtn');
    const trendingBtn = document.getElementById('trendingBtn');

    const loadingIndicator = document.getElementById('loadingIndicator');
    const errorMessage = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');
    const seriesGrid = document.getElementById('seriesGrid');
    const seriesList = document.getElementById('seriesList');
    const noResults = document.getElementById('noResults');

    const importModal = document.getElementById('importModal');
    const importForm = document.getElementById('importForm');
    const closeModal = document.getElementById('closeModal');
    const cancelImport = document.getElementById('cancelImport');

    // Event listeners
    searchBtn.addEventListener('click', () => {
        const query = searchInput.value.trim();
        if (query) {
            searchSeries(query);
        }
    });

    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            searchBtn.click();
        }
    });

    popularBtn.addEventListener('click', () => {
        loadPopularSeries();
    });

    trendingBtn.addEventListener('click', () => {
        loadTrendingSeries();
    });

    closeModal.addEventListener('click', hideImportModal);
    cancelImport.addEventListener('click', hideImportModal);

    importForm.addEventListener('submit', handleImport);

    // Functions
    function searchSeries(query) {
        console.log('Starting search for:', query);
        showLoading();

        const url = `{{ route('admin.series.tmdb-new.search') }}?query=${encodeURIComponent(query)}`;
        console.log('Fetching URL:', url);

        fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                console.log('Response received:', response.status, response.statusText);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                hideLoading();
                if (data.error) {
                    console.error('API Error:', data.error);
                    showError(data.error);
                } else {
                    console.log('Success! Results:', data.results?.length || 0);
                    displaySeries(data.results || []);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                hideLoading();
                showError('Failed to search series. Please try again. Error: ' + error.message);
            });
    }

    function loadPopularSeries() {
        console.log('Loading popular series');
        showLoading();

        const url = `{{ route('admin.series.tmdb-new.popular') }}`;
        console.log('Fetching URL:', url);

        fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                console.log('Popular response:', response.status, response.statusText);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Popular data:', data);
                hideLoading();
                if (data.error) {
                    console.error('Popular API Error:', data.error);
                    showError(data.error);
                } else {
                    console.log('Popular success! Results:', data.results?.length || 0);
                    displaySeries(data.results || []);
                }
            })
            .catch(error => {
                console.error('Popular fetch error:', error);
                hideLoading();
                showError('Failed to load popular series. Please try again. Error: ' + error.message);
            });
    }

    function loadTrendingSeries() {
        showLoading();

        fetch(`{{ route('admin.series.tmdb-new.trending') }}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                hideLoading();
                if (data.error) {
                    showError(data.error);
                } else {
                    displaySeries(data.results || []);
                }
            })
            .catch(error => {
                hideLoading();
                showError('Failed to load trending series. Please try again.');
                console.error('Trending error:', error);
            });
    }

    function displaySeries(series) {
        hideError();

        if (!series || series.length === 0) {
            showNoResults();
            return;
        }

        seriesList.innerHTML = '';

        series.forEach(item => {
            const seriesCard = createSeriesCard(item);
            seriesList.appendChild(seriesCard);
        });

        showSeriesGrid();
    }

    function createSeriesCard(series) {
        const card = document.createElement('div');
        card.className = 'bg-gray-700 rounded-lg overflow-hidden hover:bg-gray-600 transition';

        const posterUrl = series.poster_path
            ? `https://image.tmdb.org/t/p/w300${series.poster_path}`
            : '/images/no-poster.jpg';

        const year = series.first_air_date ? new Date(series.first_air_date).getFullYear() : 'N/A';
        const rating = series.vote_average ? series.vote_average.toFixed(1) : 'N/A';

        card.innerHTML = `
            <div class="relative">
                <img src="${posterUrl}" alt="${series.name}" class="w-full h-64 object-cover" onerror="this.src='/images/no-poster.jpg'">
                ${series.exists_in_db ? '<div class="absolute top-2 left-2 bg-green-600 text-xs px-2 py-1 rounded">Already Imported</div>' : ''}
            </div>
            <div class="p-4">
                <h3 class="font-semibold text-sm mb-2 line-clamp-2">${series.name}</h3>
                <p class="text-xs text-gray-400 mb-2 line-clamp-2">${series.overview || 'No description available'}</p>
                <div class="text-xs text-gray-400 mb-3">
                    <span>${year}</span> • <span>★ ${rating}</span> • <span>ID: ${series.id}</span>
                </div>
                <div class="flex gap-2">
                    ${!series.exists_in_db ? `
                        <button class="import-btn flex-1 bg-green-600 hover:bg-green-700 px-3 py-1 rounded text-xs transition"
                                data-tmdb-id="${series.id}">
                            Import
                        </button>
                    ` : `
                        <button class="flex-1 bg-gray-500 px-3 py-1 rounded text-xs cursor-not-allowed" disabled>
                            Already Imported
                        </button>
                    `}
                </div>
            </div>
        `;

        // Add import event listener
        const importBtn = card.querySelector('.import-btn');
        if (importBtn) {
            importBtn.addEventListener('click', () => {
                showImportModal(series.id);
            });
        }

        return card;
    }

    function showImportModal(tmdbId) {
        document.getElementById('tmdbId').value = tmdbId;
        importModal.classList.remove('hidden');
    }

    function hideImportModal() {
        importModal.classList.add('hidden');
        importForm.reset();
    }

    function handleImport(e) {
        e.preventDefault();

        const formData = new FormData(importForm);
        const submitBtn = document.getElementById('submitImport');
        const btnText = document.getElementById('importBtnText');
        const btnLoading = document.getElementById('importBtnLoading');

        // Show loading state
        submitBtn.disabled = true;
        btnText.classList.add('hidden');
        btnLoading.classList.remove('hidden');

        fetch(`{{ route('admin.series.tmdb-new.import') }}`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Toast.success('Series imported successfully!');
                hideImportModal();
                // Refresh the current search to update the UI
                if (searchInput.value.trim()) {
                    searchSeries(searchInput.value.trim());
                }
            } else {
                Toast.error(data.error || 'Failed to import series');
            }
        })
        .catch(error => {
            console.error('Import error:', error);
            Toast.error('Failed to import series. Please try again.');
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            btnText.classList.remove('hidden');
            btnLoading.classList.add('hidden');
        });
    }

    // UI state functions
    function showLoading() {
        loadingIndicator.classList.remove('hidden');
        seriesGrid.classList.add('hidden');
        noResults.classList.add('hidden');
        hideError();
    }

    function hideLoading() {
        loadingIndicator.classList.add('hidden');
    }

    function showSeriesGrid() {
        loadingIndicator.classList.add('hidden');
        seriesGrid.classList.remove('hidden');
        noResults.classList.add('hidden');
        hideError();
    }

    function showNoResults() {
        loadingIndicator.classList.add('hidden');
        seriesGrid.classList.add('hidden');
        noResults.classList.remove('hidden');
        hideError();
    }

    function showError(message) {
        loadingIndicator.classList.add('hidden');
        seriesGrid.classList.add('hidden');
        noResults.classList.add('hidden');
        errorText.textContent = message;
        errorMessage.classList.remove('hidden');
    }

    function hideError() {
        errorMessage.classList.add('hidden');
    }
});
</script>
@endsection
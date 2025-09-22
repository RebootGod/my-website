{{-- ======================================== --}}
{{-- ADMIN SERIES TMDB IMPORT --}}
{{-- ======================================== --}}
{{-- File: resources/views/admin/series/import_tmdb.blade.php --}}

@extends('layouts.admin')

@section('title', 'Import Series from TMDB - Admin')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Import Series from TMDB</h1>
        <a href="{{ route('admin.series.index') }}" class="bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded-lg transition text-white">
            <i class="fas fa-arrow-left mr-2"></i>Back to Series
        </a>
    </div>

    <!-- Search Section -->
    <div class="bg-gray-800 rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Search Series</h2>
        <div class="flex gap-4 mb-4">
            <div class="flex-1">
                <input type="text" id="searchInput" placeholder="Search by title or TMDB ID (e.g., 1107979)..." class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400">
            </div>
            <button id="searchBtn" class="bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded-lg transition">
                <i class="fas fa-search mr-2"></i>Search
            </button>
        </div>
        <div class="flex gap-4">
            <button id="popularBtn" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg transition">
                <i class="fas fa-fire mr-2"></i>Popular Series
            </button>
            <button id="trendingBtn" class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded-lg transition">
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
        @push('scripts')
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
                showLoading();
                fetch(`/admin/series/tmdb-search?query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
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
                        showError('Failed to search series. Please try again.');
                    });
            }
            function loadPopularSeries() {
                showLoading();
                fetch(`/admin/series/tmdb-search?popular=1`)
                    .then(response => response.json())
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
                        showError('Failed to load popular series. Please try again.');
                    });
            }
            function loadTrendingSeries() {
                showLoading();
                fetch(`/admin/series/tmdb-search?trending=1`)
                    .then(response => response.json())
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
                    const card = createSeriesCard(item);
                    seriesList.appendChild(card);
                });
                showSeriesGrid();
            }
            function createSeriesCard(item) {
                const card = document.createElement('div');
                card.className = 'bg-gray-700 rounded-lg overflow-hidden hover:bg-gray-600 transition';
                const posterUrl = item.poster_path ? `https://image.tmdb.org/t/p/w300${item.poster_path}` : '/images/no-poster.jpg';
                const year = item.year || 'N/A';
                const rating = item.rating ? item.rating.toFixed(1) : 'N/A';
                card.innerHTML = `
                    <div class="relative">
                        <img src="${posterUrl}" alt="${item.name}" class="w-full h-64 object-cover" onerror="this.src='/images/no-poster.jpg'">
                        ${item.exists_in_db ? '<div class="absolute top-2 left-2 bg-green-600 text-xs px-2 py-1 rounded">Already Imported</div>' : ''}
                    </div>
                    <div class="p-4">
                        <h3 class="font-semibold text-sm mb-2 line-clamp-2">${item.name}</h3>
                        <p class="text-xs text-gray-400 mb-2 line-clamp-2">${item.overview || 'No description available'}</p>
                        <div class="text-xs text-gray-400 mb-3">
                            <span>${year}</span> • <span>★ ${rating}</span> • <span>ID: ${item.id}</span>
                        </div>
                        <div class="flex gap-2">
                            ${!item.exists_in_db ? `
                                <button class="import-btn flex-1 bg-green-600 hover:bg-green-700 px-3 py-1 rounded text-xs transition" data-tmdb-id="${item.id}">Import</button>
                            ` : `
                                <button class="flex-1 bg-gray-500 px-3 py-1 rounded text-xs cursor-not-allowed" disabled>Already Imported</button>
                            `}
                        </div>
                    </div>
                `;
                // Add import event listener
                const importBtn = card.querySelector('.import-btn');
                if (importBtn) {
                    importBtn.addEventListener('click', () => {
                        showImportModal(item.id);
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
                fetch(importForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Series imported successfully!');
                        hideImportModal();
                        if (searchInput.value.trim()) {
                            searchSeries(searchInput.value.trim());
                        }
                    } else {
                        alert(data.error || 'Failed to import series');
                    }
                })
                .catch(error => {
                    alert('Failed to import series. Please try again.');
                })
                .finally(() => {
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
        @endpush
        const year = item.year || 'N/A';
        const rating = item.rating ? item.rating.toFixed(1) : 'N/A';
        card.innerHTML = `
            <div class="relative">
                <img src="${posterUrl}" alt="${item.name}" class="w-full h-64 object-cover" onerror="this.src='/images/no-poster.jpg'">
                ${item.exists_in_db ? '<div class="absolute top-2 left-2 bg-green-600 text-xs px-2 py-1 rounded">Already Imported</div>' : ''}
            </div>
            <div class="p-4">
                <h3 class="font-semibold text-sm mb-2 line-clamp-2">${item.name}</h3>
                <p class="text-xs text-gray-400 mb-2 line-clamp-2">${item.overview || 'No description available'}</p>
                <div class="text-xs text-gray-400 mb-3">
                    <span>${year}</span> • <span>★ ${rating}</span> • <span>ID: ${item.id}</span>
                </div>
                <div class="flex gap-2">
                    ${!item.exists_in_db ? `
                        <button class="import-btn flex-1 bg-green-600 hover:bg-green-700 px-3 py-1 rounded text-xs transition" data-tmdb-id="${item.id}">Import</button>
                    ` : `
                        <button class="flex-1 bg-gray-500 px-3 py-1 rounded text-xs cursor-not-allowed" disabled>Already Imported</button>
                    `}
                </div>
            </div>
        `;
        // Add import event listener
        const importBtn = card.querySelector('.import-btn');
        if (importBtn) {
            importBtn.addEventListener('click', () => {
                showImportModal(item.id);
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
        fetch(importForm.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Series imported successfully!');
                hideImportModal();
                if (searchInput.value.trim()) {
                    searchSeries(searchInput.value.trim());
                }
            } else {
                alert(data.error || 'Failed to import series');
            }
        })
        .catch(error => {
            alert('Failed to import series. Please try again.');
        })
        .finally(() => {
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
// ...existing code...

@push('scripts')
<script>
function renderSeriesGrid(data) {
    if (!data.results || data.results.length === 0) {
        return '<div class="text-center py-6 text-gray-400">No series found.</div>';
    }
    let html = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">';
    data.results.forEach(item => {
        html += `<div class="bg-gray-900 rounded-lg p-4 flex flex-col items-center">
            <img src="${item.poster_path ? 'https://image.tmdb.org/t/p/w200' + item.poster_path : 'https://via.placeholder.com/100x150'}" alt="${item.name}" class="w-24 h-36 rounded mb-2">
            <div class="text-white font-semibold text-lg mb-1">${item.name}</div>
            <div class="text-gray-400 text-sm mb-2">${item.overview ? item.overview.substring(0, 80) + '...' : '-'}</div>
            <button class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg font-semibold transition import-btn" data-tmdb-id="${item.id}">Import</button>
        </div>`;
    });
    html += '</div>';
    return html;
}

function fetchSeries(url) {
    const resultsDiv = document.getElementById('searchResults');
    resultsDiv.innerHTML = '<div class="text-center py-6 text-gray-400">Loading...</div>';
    fetch(url)
        .then(res => res.json())
        .then(data => {
            resultsDiv.innerHTML = renderSeriesGrid(data);
            document.querySelectorAll('.import-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('tmdb_id').value = this.getAttribute('data-tmdb-id');
                    window.scrollTo({ top: document.getElementById('tmdb_id').offsetTop - 100, behavior: 'smooth' });
                });
            });
        })
        .catch(() => {
            resultsDiv.innerHTML = '<div class="text-center py-6 text-red-400">Error fetching data from TMDB.</div>';
        });
}

document.getElementById('searchBtn').addEventListener('click', function() {
    const query = document.getElementById('search_title').value.trim();
    if (!query) return;
    fetchSeries(`https://api.themoviedb.org/3/search/tv?api_key={{ config('services.tmdb.api_key') }}&language=en-US&query=${encodeURIComponent(query)}`);
});

document.getElementById('popularBtn').addEventListener('click', function() {
    fetchSeries(`https://api.themoviedb.org/3/tv/popular?api_key={{ config('services.tmdb.api_key') }}&language=en-US`);
});

document.getElementById('trendingBtn').addEventListener('click', function() {
    fetchSeries(`https://api.themoviedb.org/3/trending/tv/week?api_key={{ config('services.tmdb.api_key') }}&language=en-US`);
});
</script>
@endpush
@endsection

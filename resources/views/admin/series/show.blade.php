{{-- ======================================== --}}
{{-- ADMIN SERIES DETAIL --}}
{{-- ======================================== --}}
{{-- File: resources/views/admin/series/show.blade.php --}}

@extends('layouts.admin')

@section('title', 'Series Detail - Admin')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">{{ $series->title }}</h1>
        <div class="flex space-x-4">
            <a href="{{ route('admin.series.edit', $series) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition">
                Edit Series
            </a>
            <a href="{{ route('admin.series.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition">
                Back to List
            </a>
        </div>
    </div>

    {{-- Series Overview --}}
    <div class="bg-gray-800 rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Poster --}}
            <div class="lg:col-span-1">
                <img 
                    src="{{ $series->poster_url }}" 
                    alt="{{ $series->title }}" 
                    class="w-full rounded-lg"
                >
            </div>

            {{-- Series Info --}}
            <div class="lg:col-span-2">
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-400">Status</h3>
                        <span class="px-2 py-1 text-xs rounded-full 
                            {{ $series->status == 'published' ? 'bg-green-600' : ($series->status == 'draft' ? 'bg-yellow-600' : 'bg-gray-600') }} text-white">
                            {{ ucfirst($series->status) }}
                        </span>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-400">Year</h3>
                        <p class="text-white">{{ $series->year ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-400">Rating</h3>
                        <p class="text-white">{{ $series->getFormattedRating() }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-400">Views</h3>
                        <p class="text-white">{{ number_format($series->view_count) }}</p>
                    </div>
                </div>

                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-400 mb-2">Description</h3>
                    <p class="text-gray-300">{{ $series->description ?: 'No description available.' }}</p>
                </div>

                {{-- Genres --}}
                @if($series->genres->count() > 0)
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-400 mb-2">Genres</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($series->genres as $genre)
                            <span class="px-2 py-1 bg-blue-600 text-white text-xs rounded">{{ $genre->name }}</span>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Additional Info --}}
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <h4 class="text-gray-400">TMDB ID</h4>
                        <p class="text-white">{{ $series->tmdb_id ?: 'N/A' }}</p>
                    </div>
                    <div>
                        <h4 class="text-gray-400">Created</h4>
                        <p class="text-white">{{ $series->created_at->format('d M Y H:i') }}</p>
                    </div>
                    <div>
                        <h4 class="text-gray-400">Updated</h4>
                        <p class="text-white">{{ $series->updated_at->format('d M Y H:i') }}</p>
                    </div>
                    <div>
                        <h4 class="text-gray-400">Duration</h4>
                        <p class="text-white">{{ $series->getFormattedDuration() }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Seasons and Episodes --}}
    <div class="bg-gray-800 rounded-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Seasons & Episodes</h2>
            <button id="addSeasonBtn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition">
                <i class="fas fa-plus mr-2"></i>Add New Season
            </button>
        </div>
        
        @if($series->seasons->count() > 0)
            <div class="space-y-6">
                @foreach($series->seasons as $season)
                    <div class="bg-gray-900 rounded-lg p-4">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-semibold">Season {{ $season->season_number }}</h3>
                            <div class="flex items-center space-x-4">
                                <span class="text-sm text-gray-400">{{ $season->episodes->count() }} episodes</span>
                                <button class="addEpisodeBtn bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition"
                                        data-season-id="{{ $season->id }}"
                                        data-season-number="{{ $season->season_number }}">
                                    <i class="fas fa-plus mr-1"></i>Add Episode
                                </button>
                                <button class="deleteSeasonBtn bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm transition"
                                        data-season-id="{{ $season->id }}"
                                        data-season-number="{{ $season->season_number }}"
                                        data-episode-count="{{ $season->episodes->count() }}">
                                    <i class="fas fa-trash mr-1"></i>Delete Season
                                </button>
                            </div>
                        </div>
                        
                        @if($season->name)
                            <p class="text-gray-300 mb-4">{{ $season->name }}</p>
                        @endif
                        
                        @if($season->overview)
                            <p class="text-gray-400 text-sm mb-4">{{ $season->overview }}</p>
                        @endif

                        {{-- Episodes --}}
                        @if($season->episodes->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($season->episodes as $episode)
                                    <div class="bg-gray-800 rounded p-3">
                                        <div class="flex justify-between items-start mb-2">
                                            <h4 class="font-medium text-white">Episode {{ $episode->episode_number }}</h4>
                                            <div class="flex items-center space-x-2">
                                                @if($episode->runtime)
                                                    <span class="text-xs text-gray-400">{{ $episode->getFormattedRuntime() }}</span>
                                                @endif
                                                <div class="flex items-center space-x-1">
                                                    <a href="{{ route('admin.series.episodes.edit', [$series, $episode]) }}" 
                                                       class="text-blue-500 hover:text-blue-700 text-xs transition"
                                                       title="Edit Episode">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button class="deleteEpisodeBtn text-red-500 hover:text-red-700 text-xs transition"
                                                            data-episode-id="{{ $episode->id }}"
                                                            data-episode-number="{{ $episode->episode_number }}"
                                                            data-season-number="{{ $season->season_number }}"
                                                            data-episode-title="{{ $episode->name }}"
                                                            title="Delete Episode">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <h5 class="text-sm font-medium text-gray-300 mb-2">{{ $episode->name }}</h5>
                                        @if($episode->overview)
                                            <p class="text-xs text-gray-400">{{ Str::limit($episode->overview, 100) }}</p>
                                        @endif
                                        @if($episode->air_date)
                                            <p class="text-xs text-gray-500 mt-2">{{ \Carbon\Carbon::parse($episode->air_date)->format('d M Y') }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-400 text-sm">No episodes available for this season.</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <p class="text-gray-400">No seasons available for this series.</p>
                <p class="text-sm text-gray-500 mt-2">Seasons and episodes will be populated when importing from TMDB.</p>
            </div>
        @endif
    </div>
</div>

<!-- Add Season Modal -->
<div id="addSeasonModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gray-800 rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Add New Season</h3>
                <button id="closeSeasonModal" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="addSeasonForm">
                @csrf
                <input type="hidden" name="series_id" value="{{ $series->id }}">

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Season Number *</label>
                    <input type="number"
                           name="season_number"
                           required
                           min="1"
                           placeholder="e.g., 1"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Season Name (Optional)</label>
                    <input type="text"
                           name="name"
                           placeholder="e.g., The Beginning"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium mb-2">Overview (Optional)</label>
                    <textarea name="overview"
                              rows="3"
                              placeholder="Brief description of this season..."
                              class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white resize-none"></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="button" id="cancelAddSeason" class="flex-1 bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded transition">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 px-4 py-2 rounded transition">
                        <span id="addSeasonBtnText">Add Season</span>
                        <span id="addSeasonBtnLoading" class="hidden">Adding...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Episode Modal -->
<div id="addEpisodeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gray-800 rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Add New Episode</h3>
                <button id="closeEpisodeModal" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="addEpisodeForm">
                @csrf
                <input type="hidden" id="episodeSeriesId" name="series_id" value="{{ $series->id }}">
                <input type="hidden" id="episodeSeasonId" name="season_id">

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Season</label>
                    <p id="episodeSeasonDisplay" class="text-white bg-gray-700 px-3 py-2 rounded">Season X</p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Episode Number *</label>
                    <input type="number"
                           name="episode_number"
                           required
                           min="1"
                           placeholder="e.g., 1"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Episode Title *</label>
                    <input type="text"
                           name="name"
                           required
                           placeholder="e.g., The Beginning"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Description *</label>
                    <textarea name="overview"
                              required
                              rows="3"
                              placeholder="Brief description of this episode..."
                              class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white resize-none"></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Duration (minutes) *</label>
                    <input type="number"
                           name="runtime"
                           required
                           min="1"
                           placeholder="e.g., 45"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Episode Thumbnail URL (Optional)</label>
                    <input type="url"
                           name="still_path"
                           placeholder="https://example.com/episode-poster.jpg"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
                    <p class="text-xs text-gray-400 mt-1">Leave empty to use default placeholder</p>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium mb-2">Embed URL *</label>
                    <input type="url"
                           name="embed_url"
                           required
                           placeholder="https://example.com/player/episode123"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
                </div>

                <div class="flex gap-3">
                    <button type="button" id="cancelAddEpisode" class="flex-1 bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded transition">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded transition">
                        <span id="addEpisodeBtnText">Add Episode</span>
                        <span id="addEpisodeBtnLoading" class="hidden">Adding...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal elements
    const addSeasonModal = document.getElementById('addSeasonModal');
    const addEpisodeModal = document.getElementById('addEpisodeModal');
    const addSeasonBtn = document.getElementById('addSeasonBtn');
    const addEpisodeBtns = document.querySelectorAll('.addEpisodeBtn');
    const deleteSeasonBtns = document.querySelectorAll('.deleteSeasonBtn');
    const deleteEpisodeBtns = document.querySelectorAll('.deleteEpisodeBtn');

    // Forms
    const addSeasonForm = document.getElementById('addSeasonForm');
    const addEpisodeForm = document.getElementById('addEpisodeForm');

    // Close buttons
    const closeSeasonModal = document.getElementById('closeSeasonModal');
    const closeEpisodeModal = document.getElementById('closeEpisodeModal');
    const cancelAddSeason = document.getElementById('cancelAddSeason');
    const cancelAddEpisode = document.getElementById('cancelAddEpisode');

    // Event listeners for opening modals
    addSeasonBtn.addEventListener('click', () => {
        addSeasonModal.classList.remove('hidden');
    });

    addEpisodeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const seasonId = btn.dataset.seasonId;
            const seasonNumber = btn.dataset.seasonNumber;

            document.getElementById('episodeSeasonId').value = seasonId;
            document.getElementById('episodeSeasonDisplay').textContent = `Season ${seasonNumber}`;

            addEpisodeModal.classList.remove('hidden');
        });
    });

    deleteSeasonBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const seasonId = btn.dataset.seasonId;
            const seasonNumber = btn.dataset.seasonNumber;
            const episodeCount = btn.dataset.episodeCount;

            let confirmMessage = `Are you sure you want to delete Season ${seasonNumber}?`;
            if (episodeCount > 0) {
                confirmMessage += `\n\nThis will also delete ${episodeCount} episode(s) in this season.`;
            }

            if (confirm(confirmMessage)) {
                handleDeleteSeason(seasonId, seasonNumber);
            }
        });
    });

    deleteEpisodeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const episodeId = btn.dataset.episodeId;
            const episodeNumber = btn.dataset.episodeNumber;
            const seasonNumber = btn.dataset.seasonNumber;
            const episodeTitle = btn.dataset.episodeTitle;

            const confirmMessage = `Are you sure you want to delete Episode ${episodeNumber} (${episodeTitle}) from Season ${seasonNumber}?`;

            if (confirm(confirmMessage)) {
                handleDeleteEpisode(episodeId, episodeNumber, seasonNumber);
            }
        });
    });

    // Event listeners for closing modals
    closeSeasonModal.addEventListener('click', hideSeasonModal);
    closeEpisodeModal.addEventListener('click', hideEpisodeModal);
    cancelAddSeason.addEventListener('click', hideSeasonModal);
    cancelAddEpisode.addEventListener('click', hideEpisodeModal);

    // Form submissions
    addSeasonForm.addEventListener('submit', handleAddSeason);
    addEpisodeForm.addEventListener('submit', handleAddEpisode);

    function hideSeasonModal() {
        addSeasonModal.classList.add('hidden');
        addSeasonForm.reset();
    }

    function hideEpisodeModal() {
        addEpisodeModal.classList.add('hidden');
        addEpisodeForm.reset();
    }

    function handleAddSeason(e) {
        e.preventDefault();

        const formData = new FormData(addSeasonForm);
        const submitBtn = addSeasonForm.querySelector('button[type="submit"]');
        const btnText = document.getElementById('addSeasonBtnText');
        const btnLoading = document.getElementById('addSeasonBtnLoading');

        // Show loading state
        submitBtn.disabled = true;
        btnText.classList.add('hidden');
        btnLoading.classList.remove('hidden');

        fetch('{{ route("admin.series.seasons.store", $series) }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Toast.success('Season added successfully!');
                hideSeasonModal();
                location.reload(); // Refresh to show new season
            } else {
                Toast.error(data.error || 'Failed to add season');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Toast.error('Failed to add season. Please try again.');
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            btnText.classList.remove('hidden');
            btnLoading.classList.add('hidden');
        });
    }

    function handleAddEpisode(e) {
        e.preventDefault();

        const formData = new FormData(addEpisodeForm);
        const submitBtn = addEpisodeForm.querySelector('button[type="submit"]');
        const btnText = document.getElementById('addEpisodeBtnText');
        const btnLoading = document.getElementById('addEpisodeBtnLoading');

        // Show loading state
        submitBtn.disabled = true;
        btnText.classList.add('hidden');
        btnLoading.classList.remove('hidden');

        fetch('{{ route("admin.series.episodes.store", $series) }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Toast.success('Episode added successfully!');
                hideEpisodeModal();
                location.reload(); // Refresh to show new episode
            } else {
                Toast.error(data.error || 'Failed to add episode');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Toast.error('Failed to add episode. Please try again.');
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            btnText.classList.remove('hidden');
            btnLoading.classList.add('hidden');
        });
    }

    function handleDeleteSeason(seasonId, seasonNumber) {
        fetch(`{{ route("admin.series.seasons.destroy", [$series, "__SEASON_ID__"]) }}`.replace('__SEASON_ID__', seasonId), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Toast.success(data.message);
                location.reload(); // Refresh to show updated seasons
            } else {
                Toast.error(data.error || 'Failed to delete season');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Toast.error('Failed to delete season. Please try again.');
        });
    }

    function handleDeleteEpisode(episodeId, episodeNumber, seasonNumber) {
        fetch(`{{ route("admin.series.episodes.destroy", [$series, "__EPISODE_ID__"]) }}`.replace('__EPISODE_ID__', episodeId), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Toast.success(data.message || `Episode ${episodeNumber} deleted successfully!`);
                location.reload(); // Refresh to show updated episodes
            } else {
                Toast.error(data.error || 'Failed to delete episode');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Toast.error('Failed to delete episode. Please try again.');
        });
    }
});
</script>
@endsection

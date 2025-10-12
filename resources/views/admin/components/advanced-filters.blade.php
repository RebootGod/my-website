{{-- Advanced Filters Component --}}
{{-- Max 350 lines per workinginstruction.md --}}
{{-- Reusable for Movies and Series --}}

<div class="mb-6">
    {{-- Toggle Button --}}
    <button 
        type="button" 
        id="toggle-advanced-filters" 
        class="filter-toggle-btn"
    >
        <i class="fas fa-filter mr-2"></i>
        Show Advanced Filters
    </button>

    {{-- Filter Panel --}}
    <div id="advanced-filter-panel" class="advanced-filter-panel hidden mt-4">
        <form id="advanced-filter-form" method="GET" action="{{ $action }}">
            
            {{-- Filter Grid --}}
            <div class="filter-grid">
                
                {{-- Search Filter --}}
                <div class="filter-group">
                    <label for="search">
                        <i class="fas fa-search mr-1"></i>
                        Search
                    </label>
                    <input 
                        type="text" 
                        id="search" 
                        name="search" 
                        class="filter-input" 
                        placeholder="Title, description..." 
                        value="{{ request('search') }}"
                    >
                </div>

                {{-- Status Filter --}}
                <div class="filter-group">
                    <label for="status">
                        <i class="fas fa-circle-dot mr-1"></i>
                        Status
                    </label>
                    <select id="status" name="status" class="filter-select">
                        <option value="">All Status</option>
                        <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Published</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        @if($contentType === 'movie')
                        <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>Archived</option>
                        @endif
                    </select>
                </div>

                {{-- Year Range --}}
                <div class="filter-group">
                    <label>
                        <i class="fas fa-calendar mr-1"></i>
                        Year Range
                    </label>
                    <div class="range-slider-container">
                        <div class="range-inputs">
                            <div class="range-input-group">
                                <label>From: <span id="year_from_value" class="range-value">{{ request('year_from', 1900) }}</span></label>
                                <input 
                                    type="range" 
                                    id="year_from" 
                                    name="year_from" 
                                    class="range-input" 
                                    min="1900" 
                                    max="{{ date('Y') }}" 
                                    value="{{ request('year_from', 1900) }}"
                                >
                            </div>
                            <div class="range-input-group">
                                <label>To: <span id="year_to_value" class="range-value">{{ request('year_to', date('Y')) }}</span></label>
                                <input 
                                    type="range" 
                                    id="year_to" 
                                    name="year_to" 
                                    class="range-input" 
                                    min="1900" 
                                    max="{{ date('Y') }}" 
                                    value="{{ request('year_to', date('Y')) }}"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Rating Range --}}
                <div class="filter-group">
                    <label>
                        <i class="fas fa-star mr-1"></i>
                        Rating Range
                    </label>
                    <div class="range-slider-container">
                        <div class="range-inputs">
                            <div class="range-input-group">
                                <label>From: <span id="rating_from_value" class="range-value">{{ request('rating_from', 0) }}</span></label>
                                <input 
                                    type="range" 
                                    id="rating_from" 
                                    name="rating_from" 
                                    class="range-input" 
                                    min="0" 
                                    max="10" 
                                    step="0.1" 
                                    value="{{ request('rating_from', 0) }}"
                                >
                            </div>
                            <div class="range-input-group">
                                <label>To: <span id="rating_to_value" class="range-value">{{ request('rating_to', 10) }}</span></label>
                                <input 
                                    type="range" 
                                    id="rating_to" 
                                    name="rating_to" 
                                    class="range-input" 
                                    min="0" 
                                    max="10" 
                                    step="0.1" 
                                    value="{{ request('rating_to', 10) }}"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Views Range --}}
                <div class="filter-group">
                    <label>
                        <i class="fas fa-eye mr-1"></i>
                        View Count Range
                    </label>
                    <div class="range-slider-container">
                        <div class="range-inputs">
                            <div class="range-input-group">
                                <label>From: <span id="views_from_value" class="range-value">{{ request('views_from', 0) }}</span></label>
                                <input 
                                    type="range" 
                                    id="views_from" 
                                    name="views_from" 
                                    class="range-input" 
                                    min="0" 
                                    max="100000" 
                                    step="100" 
                                    value="{{ request('views_from', 0) }}"
                                >
                            </div>
                            <div class="range-input-group">
                                <label>To: <span id="views_to_value" class="range-value">{{ request('views_to', 100000) }}</span></label>
                                <input 
                                    type="range" 
                                    id="views_to" 
                                    name="views_to" 
                                    class="range-input" 
                                    min="0" 
                                    max="100000" 
                                    step="100" 
                                    value="{{ request('views_to', 100000) }}"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TMDB Status --}}
                <div class="filter-group">
                    <label for="has_tmdb">
                        <i class="fas fa-database mr-1"></i>
                        TMDB Status
                    </label>
                    <select id="has_tmdb" name="has_tmdb" class="filter-select">
                        <option value="">All Items</option>
                        <option value="1" {{ request('has_tmdb') == '1' ? 'selected' : '' }}>Has TMDB ID</option>
                        <option value="0" {{ request('has_tmdb') === '0' ? 'selected' : '' }}>No TMDB ID</option>
                    </select>
                </div>

                {{-- Genre Filter (if genres available) --}}
                @if(isset($genres) && count($genres) > 0)
                <div class="filter-group" style="grid-column: 1 / -1;">
                    <label>
                        <i class="fas fa-tags mr-1"></i>
                        Genres (Select Multiple)
                    </label>
                    <div class="checkbox-group">
                        @foreach($genres as $genre)
                        <div class="checkbox-item">
                            <input 
                                type="checkbox" 
                                id="genre_{{ $genre->id }}" 
                                name="genre_ids[]" 
                                value="{{ $genre->id }}"
                                {{ in_array($genre->id, request('genre_ids', [])) ? 'checked' : '' }}
                            >
                            <label for="genre_{{ $genre->id }}">{{ $genre->name }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Quality Filter (Movies only) --}}
                @if($contentType === 'movie')
                <div class="filter-group">
                    <label for="quality">
                        <i class="fas fa-video mr-1"></i>
                        Quality
                    </label>
                    <select id="quality" name="quality" class="filter-select">
                        <option value="">All Qualities</option>
                        <option value="CAM" {{ request('quality') == 'CAM' ? 'selected' : '' }}>CAM</option>
                        <option value="HD" {{ request('quality') == 'HD' ? 'selected' : '' }}>HD</option>
                        <option value="FHD" {{ request('quality') == 'FHD' ? 'selected' : '' }}>FHD</option>
                        <option value="4K" {{ request('quality') == '4K' ? 'selected' : '' }}>4K</option>
                    </select>
                </div>
                @endif

            </div>

            {{-- Filter Actions --}}
            <div class="filter-actions">
                <button type="button" id="apply-filters" class="filter-btn filter-btn-primary">
                    <i class="fas fa-check mr-2"></i>
                    Apply Filters
                </button>
                
                <button type="button" id="clear-all-filters" class="filter-btn filter-btn-secondary">
                    <i class="fas fa-times mr-2"></i>
                    Clear All
                </button>

                <div id="filter-result-count" class="filter-result-count">
                    <i class="fas fa-list mr-1"></i>
                    Loading...
                </div>

                <button type="button" id="export-results" class="filter-btn filter-btn-secondary ml-auto">
                    <i class="fas fa-download mr-2"></i>
                    Export CSV
                </button>
            </div>

            {{-- Preset Management --}}
            <div class="preset-controls">
                <select id="filter-presets-select" class="preset-select">
                    <option value="">-- Load Preset --</option>
                </select>

                <button type="button" id="save-filter-preset" class="preset-btn">
                    <i class="fas fa-save mr-1"></i>
                    Save
                </button>

                <button type="button" id="delete-filter-preset" class="preset-btn">
                    <i class="fas fa-trash mr-1"></i>
                    Delete
                </button>

                <button type="button" id="export-presets" class="preset-btn">
                    <i class="fas fa-file-export mr-1"></i>
                    Export
                </button>

                <label for="import-presets-input" class="preset-btn" style="margin: 0; cursor: pointer;">
                    <i class="fas fa-file-import mr-1"></i>
                    Import
                </label>
                <input type="file" id="import-presets-input" accept=".json" style="display: none;">

                <span id="presets-count" class="preset-count">0 saved</span>
            </div>

        </form>
    </div>
</div>

{{-- Include Required Assets --}}
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/advanced-filters.css') }}?v={{ time() }}">
@endpush

@push('scripts')
<script src="{{ asset('js/admin/advanced-filters.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/admin/filter-presets.js') }}?v={{ time() }}"></script>
@endpush

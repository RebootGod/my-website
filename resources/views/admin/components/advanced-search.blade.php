{{-- ======================================== --}}
{{-- ADVANCED SEARCH COMPONENT --}}
{{-- Reusable advanced search panel for admin tables --}}
{{-- ======================================== --}}

@props([
    'action' => '',
    'method' => 'GET',
    'fields' => [],
    'values' => [],
    'showAdvanced' => false
])

<div class="advanced-search-panel">
    <form action="{{ $action }}" method="{{ $method }}" id="advancedSearchForm">
        @if($method !== 'GET')
            @csrf
        @endif

        {{-- Basic Search Row --}}
        <div class="search-row">
            <div class="search-fields">
                {{-- Quick Search --}}
                <div class="search-field">
                    <label for="search">Quick Search</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        class="search-input"
                        placeholder="Search by title, description..."
                        value="{{ $values['search'] ?? request('search') }}"
                    >
                </div>

                {{-- Status Filter --}}
                @if(in_array('status', $fields))
                <div class="search-field">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="search-select">
                        <option value="">All Status</option>
                        <option value="published" {{ ($values['status'] ?? request('status')) === 'published' ? 'selected' : '' }}>
                            Published
                        </option>
                        <option value="draft" {{ ($values['status'] ?? request('status')) === 'draft' ? 'selected' : '' }}>
                            Draft
                        </option>
                        <option value="archived" {{ ($values['status'] ?? request('status')) === 'archived' ? 'selected' : '' }}>
                            Archived
                        </option>
                    </select>
                </div>
                @endif

                {{-- Genre Filter --}}
                @if(in_array('genre', $fields) && isset($genres))
                <div class="search-field">
                    <label for="genre_ids">Genres</label>
                    <select id="genre_ids" name="genre_ids[]" class="search-select" multiple>
                        @foreach($genres as $genre)
                            <option
                                value="{{ $genre->id }}"
                                {{ in_array($genre->id, $values['genre_ids'] ?? request('genre_ids', [])) ? 'selected' : '' }}
                            >
                                {{ $genre->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>

            <div class="search-buttons">
                <button type="submit" class="btn-search">
                    <i class="fas fa-search"></i>
                    Search
                </button>

                <button type="button" class="btn-toggle-advanced" onclick="toggleAdvancedSearch()">
                    <i class="fas fa-sliders-h"></i>
                    Advanced
                </button>

                @if(request()->hasAny(['search', 'status', 'genre_ids', 'date_from', 'date_to', 'views_min', 'views_max']))
                <a href="{{ $action }}" class="btn-reset">
                    <i class="fas fa-times"></i>
                    Clear
                </a>
                @endif
            </div>
        </div>

        {{-- Advanced Search Fields --}}
        <div id="advancedFields" style="display: {{ $showAdvanced ? 'block' : 'none' }};">
            <hr class="admin-divider">

            <div class="search-fields">
                {{-- Date Range --}}
                @if(in_array('date_range', $fields))
                <div class="search-field">
                    <label for="date_from">Date From</label>
                    <input
                        type="date"
                        id="date_from"
                        name="date_from"
                        class="search-input"
                        value="{{ $values['date_from'] ?? request('date_from') }}"
                    >
                </div>

                <div class="search-field">
                    <label for="date_to">Date To</label>
                    <input
                        type="date"
                        id="date_to"
                        name="date_to"
                        class="search-input"
                        value="{{ $values['date_to'] ?? request('date_to') }}"
                    >
                </div>
                @endif

                {{-- View Count Range --}}
                @if(in_array('view_range', $fields))
                <div class="search-field">
                    <label for="views_min">Min Views</label>
                    <input
                        type="number"
                        id="views_min"
                        name="views_min"
                        class="search-input"
                        placeholder="0"
                        min="0"
                        value="{{ $values['views_min'] ?? request('views_min') }}"
                    >
                </div>

                <div class="search-field">
                    <label for="views_max">Max Views</label>
                    <input
                        type="number"
                        id="views_max"
                        name="views_max"
                        class="search-input"
                        placeholder="999999"
                        min="0"
                        value="{{ $values['views_max'] ?? request('views_max') }}"
                    >
                </div>
                @endif

                {{-- Year Range --}}
                @if(in_array('year_range', $fields))
                <div class="search-field">
                    <label for="year_from">Year From</label>
                    <input
                        type="number"
                        id="year_from"
                        name="year_from"
                        class="search-input"
                        placeholder="1900"
                        min="1900"
                        max="{{ date('Y') }}"
                        value="{{ $values['year_from'] ?? request('year_from') }}"
                    >
                </div>

                <div class="search-field">
                    <label for="year_to">Year To</label>
                    <input
                        type="number"
                        id="year_to"
                        name="year_to"
                        class="search-input"
                        placeholder="{{ date('Y') }}"
                        min="1900"
                        max="{{ date('Y') }}"
                        value="{{ $values['year_to'] ?? request('year_to') }}"
                    >
                </div>
                @endif

                {{-- Quality Filter --}}
                @if(in_array('quality', $fields))
                <div class="search-field">
                    <label for="quality">Quality</label>
                    <select id="quality" name="quality" class="search-select">
                        <option value="">All Quality</option>
                        <option value="360p" {{ ($values['quality'] ?? request('quality')) === '360p' ? 'selected' : '' }}>360p</option>
                        <option value="480p" {{ ($values['quality'] ?? request('quality')) === '480p' ? 'selected' : '' }}>480p</option>
                        <option value="720p" {{ ($values['quality'] ?? request('quality')) === '720p' ? 'selected' : '' }}>720p</option>
                        <option value="1080p" {{ ($values['quality'] ?? request('quality')) === '1080p' ? 'selected' : '' }}>1080p</option>
                        <option value="4K" {{ ($values['quality'] ?? request('quality')) === '4K' ? 'selected' : '' }}>4K</option>
                    </select>
                </div>
                @endif

                {{-- Sorting Options --}}
                <div class="search-field">
                    <label for="sort_by">Sort By</label>
                    <select id="sort_by" name="sort_by" class="search-select">
                        <option value="created_at" {{ ($values['sort_by'] ?? request('sort_by')) === 'created_at' ? 'selected' : '' }}>
                            Date Created
                        </option>
                        <option value="updated_at" {{ ($values['sort_by'] ?? request('sort_by')) === 'updated_at' ? 'selected' : '' }}>
                            Date Updated
                        </option>
                        <option value="title" {{ ($values['sort_by'] ?? request('sort_by')) === 'title' ? 'selected' : '' }}>
                            Title
                        </option>
                        <option value="year" {{ ($values['sort_by'] ?? request('sort_by')) === 'year' ? 'selected' : '' }}>
                            Year
                        </option>
                        <option value="view_count" {{ ($values['sort_by'] ?? request('sort_by')) === 'view_count' ? 'selected' : '' }}>
                            View Count
                        </option>
                    </select>
                </div>

                <div class="search-field">
                    <label for="sort_order">Sort Order</label>
                    <select id="sort_order" name="sort_order" class="search-select">
                        <option value="desc" {{ ($values['sort_order'] ?? request('sort_order')) === 'desc' ? 'selected' : '' }}>
                            Descending
                        </option>
                        <option value="asc" {{ ($values['sort_order'] ?? request('sort_order')) === 'asc' ? 'selected' : '' }}>
                            Ascending
                        </option>
                    </select>
                </div>
            </div>
        </div>
    </form>

    {{-- Filter Summary --}}
    @if($filterSummary ?? false)
    <div class="filter-summary show">
        <div class="filter-summary-title">Active Filters:</div>
        <div class="filter-tags">
            @foreach($filterSummary as $filter)
                <span class="filter-tag">
                    {{ $filter }}
                    <span class="remove" onclick="removeFilter(this)">&times;</span>
                </span>
            @endforeach
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
function toggleAdvancedSearch() {
    const advancedFields = document.getElementById('advancedFields');
    const button = document.querySelector('.btn-toggle-advanced');
    const icon = button.querySelector('i');

    if (advancedFields.style.display === 'none') {
        advancedFields.style.display = 'block';
        icon.className = 'fas fa-chevron-up';
        button.innerHTML = '<i class="fas fa-chevron-up"></i> Basic';
    } else {
        advancedFields.style.display = 'none';
        icon.className = 'fas fa-sliders-h';
        button.innerHTML = '<i class="fas fa-sliders-h"></i> Advanced';
    }
}

function removeFilter(element) {
    // This would typically clear the specific filter and resubmit
    // For now, just redirect to clear all filters
    window.location.href = '{{ $action }}';
}

// Auto-submit on status/genre change for better UX
document.addEventListener('DOMContentLoaded', function() {
    const autoSubmitFields = ['status', 'genre_ids', 'quality', 'sort_by', 'sort_order'];

    autoSubmitFields.forEach(fieldName => {
        const field = document.getElementById(fieldName);
        if (field) {
            field.addEventListener('change', function() {
                document.getElementById('advancedSearchForm').submit();
            });
        }
    });
});
</script>
@endpush
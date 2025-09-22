{{-- ======================================== --}}
{{-- 2. SEARCH BAR COMPONENT --}}
{{-- ======================================== --}}
{{-- File: resources/views/components/search-bar.blade.php --}}

<div class="bg-gradient-to-r from-cyan-400 to-cyan-500 py-4">
    <div class="container mx-auto px-6">
        <form action="{{ route('movies.search') }}" method="GET" class="flex flex-wrap gap-2">
            {{-- Single Search Input --}}
            <input 
                type="text" 
                name="search" 
                placeholder="Cari film..."
                value="{{ request('search') }}"
                class="flex-1 min-w-[200px] px-4 py-2 rounded-lg bg-white/90 text-black placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-cyan-600"
            >
            
            {{-- Type Filter --}}
            <select name="type" class="px-4 py-2 rounded-lg bg-white/90 text-black">
                <option value="">Type</option>
                <option value="movie" {{ request('type') == 'movie' ? 'selected' : '' }}>Movie</option>
                <option value="series" disabled>Series (Coming Soon)</option>
            </select>
            
            {{-- Genre Filter --}}
            <select name="genre" class="px-4 py-2 rounded-lg bg-white/90 text-black">
                <option value="">Semua Genre</option>
                @foreach(\App\Models\Genre::orderBy('name')->get() as $genre)
                    <option value="{{ $genre->slug }}" {{ request('genre') == $genre->slug ? 'selected' : '' }}>
                        {{ $genre->name }}
                    </option>
                @endforeach
            </select>
            
            {{-- Year Filter --}}
            <select name="year" class="px-4 py-2 rounded-lg bg-white/90 text-black">
                <option value="">Semua Tahun</option>
                @for($year = date('Y'); $year >= 2000; $year--)
                    <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                @endfor
            </select>
            
            {{-- Quality Filter --}}
            <select name="quality" class="px-4 py-2 rounded-lg bg-white/90 text-black">
                <option value="">Semua Kualitas</option>
                <option value="CAM" {{ request('quality') == 'CAM' ? 'selected' : '' }}>CAM</option>
                <option value="HD" {{ request('quality') == 'HD' ? 'selected' : '' }}>HD</option>
                <option value="FHD" {{ request('quality') == 'FHD' ? 'selected' : '' }}>Full HD</option>
                <option value="4K" {{ request('quality') == '4K' ? 'selected' : '' }}>4K</option>
            </select>
            
            {{-- Submit Button --}}
            <button type="submit" class="px-6 py-2 bg-white hover:bg-gray-100 text-black font-semibold rounded-lg transition">
                Search
            </button>
        </form>
    </div>
</div>
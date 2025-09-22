{{-- ======================================== --}}
{{-- 4. ALERT COMPONENT --}}
{{-- ======================================== --}}
{{-- File: resources/views/components/alert.blade.php --}}

@if(session('success'))
    <div class="bg-green-500 text-white px-6 py-3 rounded-lg mb-6 flex items-center justify-between">
        <span>{{ session('success') }}</span>
        <button onclick="this.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
            ✕
        </button>
    </div>
@endif

@if(session('error'))
    <div class="bg-red-500 text-white px-6 py-3 rounded-lg mb-6 flex items-center justify-between">
        <span>{{ session('error') }}</span>
        <button onclick="this.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
            ✕
        </button>
    </div>
@endif

@if(session('warning'))
    <div class="bg-yellow-500 text-black px-6 py-3 rounded-lg mb-6 flex items-center justify-between">
        <span>{{ session('warning') }}</span>
        <button onclick="this.parentElement.remove()" class="ml-4 text-black hover:text-gray-700">
            ✕
        </button>
    </div>
@endif

@if($errors->any())
    <div class="bg-red-500 text-white px-6 py-3 rounded-lg mb-6">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
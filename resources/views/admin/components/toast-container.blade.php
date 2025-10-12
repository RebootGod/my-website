{{-- 
========================================
TOAST CONTAINER COMPONENT
Blade component for toast notifications
========================================

Usage in layout:
@include('admin.components.toast-container')
--}}

<div id="toast-container" class="toast-container" aria-live="polite" aria-atomic="true">
    {{-- Toasts will be dynamically inserted here by JavaScript --}}
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/toast-notifications.css') }}?v={{ filemtime(public_path('css/admin/toast-notifications.css')) }}">
@endpush

@push('scripts')
<script src="{{ asset('js/admin/toast-notifications.js') }}?v={{ filemtime(public_path('js/admin/toast-notifications.js')) }}"></script>
@endpush

{{-- Laravel Flash Messages to Toast --}}
@if(session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Toast.success('{{ session('success') }}');
    });
</script>
@endif

@if(session('error'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Toast.error('{{ session('error') }}');
    });
</script>
@endif

@if(session('warning'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Toast.warning('{{ session('warning') }}');
    });
</script>
@endif

@if(session('info'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Toast.info('{{ session('info') }}');
    });
</script>
@endif

@if($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @foreach($errors->all() as $error)
            Toast.error('{{ $error }}');
        @endforeach
    });
</script>
@endif

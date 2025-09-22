{{-- ======================================== --}}
{{-- BULK ACTIONS COMPONENT --}}
{{-- Reusable bulk actions bar for admin tables --}}
{{-- ======================================== --}}

@props([
    'actions' => [],
    'title' => 'Bulk Actions'
])

<div class="bulk-actions-bar" id="bulkActionsBar">
    <div class="flex items-center">
        <span class="selected-count font-semibold">0 items selected</span>
    </div>

    <div class="bulk-actions-buttons">
        @foreach($actions as $action)
            <button
                type="button"
                class="bulk-action-btn {{ $action['type'] ?? 'primary' }}"
                data-bulk-action="{{ $action['action'] }}"
                @if(isset($action['confirmation']))
                data-confirmation="{{ $action['confirmation'] }}"
                @endif
                title="{{ $action['title'] ?? $action['label'] }}"
            >
                @if(isset($action['icon']))
                    <i class="{{ $action['icon'] }}"></i>
                @endif
                {{ $action['label'] }}
            </button>
        @endforeach

        <button
            type="button"
            class="bulk-action-btn"
            onclick="Admin.Bulk.clearSelection()"
            title="Clear Selection"
        >
            <i class="fas fa-times"></i>
            Clear
        </button>
    </div>
</div>

@push('styles')
<style>
/* Toast notification styles for bulk operations */
.admin-toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    max-width: 400px;
}

.admin-toast {
    background-color: var(--admin-bg-gray);
    border: 1px solid var(--admin-border);
    border-radius: 0.5rem;
    margin-bottom: 0.5rem;
    padding: 1rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    transform: translateX(100%);
    opacity: 0;
    transition: all 0.3s ease;
}

.admin-toast.show {
    transform: translateX(0);
    opacity: 1;
}

.admin-toast-success {
    border-left: 4px solid var(--admin-success);
}

.admin-toast-error {
    border-left: 4px solid var(--admin-error);
}

.admin-toast-warning {
    border-left: 4px solid var(--admin-warning);
}

.admin-toast-info {
    border-left: 4px solid var(--admin-info);
}

.admin-toast-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.admin-toast-message {
    color: var(--admin-text-white);
    font-size: 0.875rem;
}

.admin-toast-close {
    background: none;
    border: none;
    color: var(--admin-text-muted);
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0;
    margin-left: 1rem;
}

.admin-toast-close:hover {
    color: var(--admin-text-white);
}
</style>
@endpush
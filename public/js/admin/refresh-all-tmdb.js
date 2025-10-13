/**
 * Refresh All TMDB - JavaScript Handler
 * 
 * Handles "Refresh All TMDB" button for bulk refreshing all movies/series
 * Max 350 lines per workinginstruction.md
 * 
 * Features:
 * - Confirmation dialog with warning
 * - Progress tracking
 * - Error handling
 * - CSRF protection
 * 
 * Security: CSRF token, XSS prevention, input validation
 */

document.addEventListener('DOMContentLoaded', () => {
    console.log('🔧 Refresh All TMDB: Initializing...');
    
    const refreshAllBtn = document.getElementById('refresh-all-tmdb-btn');
    
    if (!refreshAllBtn) {
        console.warn('⚠️ Refresh All TMDB button not found');
        return;
    }
    
    // Get content type from container
    const container = document.querySelector('[data-content-type]');
    const contentType = container?.dataset.contentType;
    
    if (!contentType) {
        console.error('❌ Content type not found');
        return;
    }
    
    console.log('✅ Refresh All TMDB initialized for:', contentType);
    
    // Add event listener
    refreshAllBtn.addEventListener('click', async () => {
        await handleRefreshAll(contentType);
    });
});

/**
 * Handle Refresh All TMDB
 */
async function handleRefreshAll(contentType) {
    console.log('🔄 Refresh All TMDB clicked for:', contentType);
    
    // Show warning confirmation
    const confirmed = confirm(
        `⚠️ WARNING: This will refresh TMDB data for ALL ${contentType}s!\n\n` +
        `This process may take several minutes depending on the number of items.\n\n` +
        `Are you sure you want to continue?`
    );
    
    if (!confirmed) {
        console.log('❌ User cancelled Refresh All');
        return;
    }
    
    // Double confirmation for safety
    const doubleConfirm = confirm(
        `🚨 FINAL CONFIRMATION\n\n` +
        `This will refresh ALL ${contentType}s in the database.\n` +
        `This operation cannot be undone.\n\n` +
        `Click OK to proceed or Cancel to abort.`
    );
    
    if (!doubleConfirm) {
        console.log('❌ User cancelled on second confirmation');
        return;
    }
    
    console.log('✅ User confirmed, starting Refresh All...');
    
    // Check if BulkProgressTracker is available
    // If not, wait a bit for it to load (deferred script)
    if (!window.BulkProgressTracker) {
        console.log('⏳ Waiting for BulkProgressTracker to load...');
        
        // Wait up to 2 seconds for it to load
        let attempts = 0;
        const checkInterval = setInterval(() => {
            attempts++;
            
            if (window.BulkProgressTracker) {
                clearInterval(checkInterval);
                console.log('✅ BulkProgressTracker loaded');
                // Proceed with the operation
                executeRefreshAll(contentType);
            } else if (attempts >= 20) { // 20 attempts * 100ms = 2 seconds
                clearInterval(checkInterval);
                console.error('❌ BulkProgressTracker failed to load');
                alert('❌ Error: Progress tracker not available.\n\nPlease refresh the page and try again.');
            }
        }, 100);
        
        return;
    }
    
    // If BulkProgressTracker is already available, proceed
    executeRefreshAll(contentType);
}

/**
 * Execute Refresh All TMDB operation
 */
async function executeRefreshAll(contentType) {
    console.log('🚀 Executing Refresh All for:', contentType);
    
    // Create and show progress modal IMMEDIATELY (before API call)
    
    // Generate a temporary progress key for immediate display
    const tempProgressKey = `bulk_operation_tmdb_refresh_all_${contentType}_${Date.now()}`;
    const tracker = new window.BulkProgressTracker(tempProgressKey);
    
    // Show modal immediately with "Initializing..." state
    tracker.createModal();
    tracker.showModal();
    
    // Update initial state
    const statusEl = document.getElementById('progress-status');
    if (statusEl) {
        statusEl.textContent = 'Initializing refresh operation...';
    }
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        if (!csrfToken) {
            tracker.hideModal();
            throw new Error('CSRF token not found');
        }
        
        // Update status: Sending request
        if (statusEl) {
            statusEl.textContent = 'Sending request to server...';
        }
        
        const response = await fetch('/admin/bulk/refresh-all-tmdb', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                type: contentType
            })
        });
        
        console.log('📡 Response status:', response.status);
        console.log('📡 Response ok:', response.ok);
        
        // Check if response is JSON (renamed to avoid conflict with function parameter)
        const responseContentType = response.headers.get('content-type');
        if (!responseContentType || !responseContentType.includes('application/json')) {
            console.error('❌ Response is not JSON, got:', responseContentType);
            tracker.hideModal();
            alert('❌ Server Error: Received HTML instead of JSON.\n\nThis usually means:\n- Server timeout (too many items)\n- Server error\n\nPlease check server logs or try again with fewer items.');
            return;
        }
        
        const result = await response.json();
        console.log('📦 Response:', result);
        
        if (result.success && result.progressKey) {
            // Update tracker with real progress key from server
            tracker.progressKey = result.progressKey;
            
            // Update status: Processing
            if (statusEl) {
                statusEl.textContent = 'Processing items...';
            }
            
            // Start polling for real progress updates
            tracker.startPolling();
        } else {
            // Hide modal and show error
            tracker.hideModal();
            const errorMsg = result.message || 'Refresh All failed';
            const errors = result.errors ? JSON.stringify(result.errors, null, 2) : '';
            alert(`❌ Error: ${errorMsg}\n\n${errors}`);
            console.error('❌ Refresh All failed:', result);
        }
        
    } catch (error) {
        console.error('💥 Error during Refresh All:', error);
        tracker.hideModal();
        alert(`❌ Error: ${error.message}\n\nPlease check console for details.`);
    }
}

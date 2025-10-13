/**
 * Refresh All TMDB - Movies Only
 * 
 * Dedicated JavaScript for "Refresh All TMDB" button on Movies page
 * Independent from series operations
 * 
 * File naming: refresh_all_tmdb_movies_admin_panel.js (per workinginstruction.md)
 * Max lines: 350 (per workinginstruction.md)
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
    console.log('🎬 Refresh All TMDB Movies: Initializing...');
    
    const refreshAllBtn = document.getElementById('refresh-all-tmdb-btn');
    
    if (!refreshAllBtn) {
        console.warn('⚠️ Refresh All TMDB Movies button not found');
        return;
    }
    
    console.log('✅ Refresh All TMDB Movies initialized');
    
    // Add event listener
    refreshAllBtn.addEventListener('click', async () => {
        await handleRefreshAllMovies();
    });
});

/**
 * Handle Refresh All TMDB for Movies
 */
async function handleRefreshAllMovies() {
    console.log('🎬 Refresh All TMDB Movies clicked');
    
    // Show warning confirmation
    const confirmed = confirm(
        `⚠️ WARNING: This will refresh TMDB data for ALL MOVIES!\n\n` +
        `This process may take several minutes depending on the number of movies.\n\n` +
        `Are you sure you want to continue?`
    );
    
    if (!confirmed) {
        console.log('❌ User cancelled Refresh All Movies');
        return;
    }
    
    // Double confirmation for safety
    const doubleConfirm = confirm(
        `🚨 FINAL CONFIRMATION\n\n` +
        `This will refresh ALL movies in the database.\n` +
        `This operation cannot be undone.\n\n` +
        `Click OK to proceed or Cancel to abort.`
    );
    
    if (!doubleConfirm) {
        console.log('❌ User cancelled on second confirmation');
        return;
    }
    
    console.log('✅ User confirmed, starting Refresh All Movies...');
    
    // Check if BulkProgressTracker is available
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
                executeRefreshAllMovies();
            } else if (attempts >= 20) { // 20 attempts * 100ms = 2 seconds
                clearInterval(checkInterval);
                console.error('❌ BulkProgressTracker failed to load');
                alert('❌ Error: Progress tracker not available.\n\nPlease refresh the page and try again.');
            }
        }, 100);
        
        return;
    }
    
    // If BulkProgressTracker is already available, proceed
    executeRefreshAllMovies();
}

/**
 * Execute Refresh All TMDB operation for Movies
 */
async function executeRefreshAllMovies() {
    console.log('🚀 Executing Refresh All Movies');
    
    // Create and show progress modal IMMEDIATELY (before API call)
    const tempProgressKey = `bulk_operation_tmdb_refresh_all_movie_${Date.now()}`;
    const tracker = new window.BulkProgressTracker(tempProgressKey);
    
    // Show modal immediately with "Initializing..." state
    tracker.createModal();
    tracker.showModal();
    
    // Update initial state
    const statusEl = document.getElementById('progress-status');
    if (statusEl) {
        statusEl.textContent = 'Initializing movie refresh operation...';
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
        
        console.log('📡 Sending POST to /admin/bulk/refresh-all-tmdb with type: movie');
        
        const response = await fetch('/admin/bulk/refresh-all-tmdb', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                type: 'movie' // HARDCODED: Movies only
            })
        });
        
        console.log('📡 Response status:', response.status);
        console.log('📡 Response ok:', response.ok);
        
        // Check if response is JSON
        const responseContentType = response.headers.get('content-type');
        if (!responseContentType || !responseContentType.includes('application/json')) {
            console.error('❌ Response is not JSON, got:', responseContentType);
            tracker.hideModal();
            alert('❌ Server Error: Received HTML instead of JSON.\n\nThis usually means:\n- Server timeout (too many movies)\n- Server error\n\nPlease check server logs or try again.');
            return;
        }
        
        const result = await response.json();
        console.log('📦 Response received:', result);
        
        if (result.success && result.progressKey) {
            console.log('✅ Refresh All Movies queued successfully');
            console.log('🔑 Progress key:', result.progressKey);
            
            // Update tracker with real progress key from server
            tracker.progressKey = result.progressKey;
            
            // Update status: Processing
            if (statusEl) {
                statusEl.textContent = 'Processing movies...';
            }
            
            // Show success message
            if (result.message) {
                console.log('📝 Message:', result.message);
            }
            
            // Start polling for real progress updates
            console.log('🔄 Starting progress polling...');
            tracker.startPolling();
        } else {
            // Hide modal and show error
            tracker.hideModal();
            const errorMsg = result.message || 'Refresh All Movies failed';
            const errors = result.errors ? JSON.stringify(result.errors, null, 2) : '';
            alert(`❌ Error: ${errorMsg}\n\n${errors}`);
            console.error('❌ Refresh All Movies failed:', result);
        }
        
    } catch (error) {
        console.error('💥 Error during Refresh All Movies:', error);
        tracker.hideModal();
        alert(`❌ Error: ${error.message}\n\nPlease check console for details.`);
    }
}

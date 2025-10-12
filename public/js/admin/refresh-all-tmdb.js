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
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        if (!csrfToken) {
            throw new Error('CSRF token not found');
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
            alert('❌ Server Error: Received HTML instead of JSON.\n\nPlease check server logs or contact administrator.');
            return;
        }
        
        const result = await response.json();
        console.log('📦 Response:', result);
        
        if (result.success) {
            // Use existing progress tracker modal
            if (result.progressKey && window.BulkProgressTracker) {
                console.log('📊 Starting progress tracker with key:', result.progressKey);
                const tracker = new window.BulkProgressTracker(result.progressKey);
                await tracker.start();
            } else {
                // Fallback: show alert and reload
                alert(`✅ ${result.message}`);
                window.location.reload();
            }
        } else {
            // Show error
            const errorMsg = result.message || 'Refresh All failed';
            const errors = result.errors ? JSON.stringify(result.errors, null, 2) : '';
            alert(`❌ Error: ${errorMsg}\n\n${errors}`);
            console.error('❌ Refresh All failed:', result);
        }
        
    } catch (error) {
        console.error('💥 Error during Refresh All:', error);
        alert(`❌ Error: ${error.message}\n\nPlease check console for details.`);
    }
}

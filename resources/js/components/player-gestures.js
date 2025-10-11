/* Player Gestures - Touch & Keyboard Controls
 * Phase 5: Video Player Gesture Controls
 * File: resources/js/components/player-gestures.js
 * Lines: 298
 */

// Gesture state
const gestureState = {
    isPlaying: false,
    volume: 100,
    lastTapTime: 0,
    tapCount: 0,
    touchStartX: 0,
    touchStartY: 0,
    touchStartTime: 0,
    controlsTimeout: null,
    isPiP: false
};

/**
 * Initialize player gesture controls
 */
function initializePlayerGestures() {
    console.log('🎮 Initializing player gesture controls');
    
    const player = document.getElementById('moviePlayer') || document.getElementById('seriesPlayer');
    const videoContainer = player?.closest('.video-container');
    
    if (!videoContainer) {
        console.warn('⚠️ Video container not found');
        return;
    }
    
    // Touch gestures
    setupTouchGestures(videoContainer, player);
    
    // Keyboard controls
    setupKeyboardControls(player);
    
    // Auto-hide controls on mobile
    setupAutoHideControls(videoContainer);
    
    // Picture-in-picture
    setupPictureInPicture(player);
    
    console.log('✅ Player gestures initialized');
}

/**
 * Setup touch gesture controls
 */
function setupTouchGestures(container, player) {
    let gestureZone = '';
    
    // Touch start
    container.addEventListener('touchstart', (e) => {
        const touch = e.touches[0];
        gestureState.touchStartX = touch.clientX;
        gestureState.touchStartY = touch.clientY;
        gestureState.touchStartTime = Date.now();
        
        // Determine gesture zone (left/center/right third)
        const containerWidth = container.offsetWidth;
        if (touch.clientX < containerWidth / 3) {
            gestureZone = 'left';
        } else if (touch.clientX > (containerWidth * 2) / 3) {
            gestureZone = 'right';
        } else {
            gestureZone = 'center';
        }
    });
    
    // Touch move - for swipe gestures
    container.addEventListener('touchmove', (e) => {
        const touch = e.touches[0];
        const deltaY = touch.clientY - gestureState.touchStartY;
        
        // Swipe up/down for volume (only on mobile)
        if (Math.abs(deltaY) > 50 && window.innerWidth < 768) {
            e.preventDefault();
            adjustVolume(-deltaY);
            showVolumeIndicator();
        }
    });
    
    // Touch end
    container.addEventListener('touchend', (e) => {
        const touch = e.changedTouches[0];
        const deltaX = touch.clientX - gestureState.touchStartX;
        const deltaY = touch.clientY - gestureState.touchStartY;
        const duration = Date.now() - gestureState.touchStartTime;
        
        // Tap detection (< 200ms, < 10px movement)
        if (duration < 200 && Math.abs(deltaX) < 10 && Math.abs(deltaY) < 10) {
            handleTap(gestureZone, container);
        }
        
        // Long press detection (> 500ms)
        else if (duration > 500 && Math.abs(deltaX) < 10 && Math.abs(deltaY) < 10) {
            handleLongPress(gestureZone);
        }
        
        // Horizontal swipe detection
        else if (Math.abs(deltaX) > 50 && Math.abs(deltaY) < 30) {
            handleSwipe(deltaX > 0 ? 'right' : 'left');
        }
    });
}

/**
 * Handle tap gestures
 */
function handleTap(zone, container) {
    const now = Date.now();
    const tapDelay = 300; // Double-tap detection window
    
    // Check for double-tap
    if (now - gestureState.lastTapTime < tapDelay) {
        gestureState.tapCount++;
        
        if (gestureState.tapCount === 2) {
            handleDoubleTap(zone);
            gestureState.tapCount = 0;
            gestureState.lastTapTime = 0;
            return;
        }
    } else {
        gestureState.tapCount = 1;
    }
    
    gestureState.lastTapTime = now;
    
    // Single tap - toggle controls
    setTimeout(() => {
        if (gestureState.tapCount === 1) {
            toggleControls(container);
            gestureState.tapCount = 0;
        }
    }, tapDelay);
}

/**
 * Handle double-tap gestures
 */
function handleDoubleTap(zone) {
    console.log(`👆 Double tap on ${zone} zone`);
    
    if (zone === 'left') {
        // Double-tap left: Rewind 10s
        skipTime(-10);
        showSkipIndicator('left', '-10s');
    } else if (zone === 'right') {
        // Double-tap right: Forward 10s
        skipTime(10);
        showSkipIndicator('right', '+10s');
    } else {
        // Double-tap center: Play/Pause
        togglePlayPause();
    }
    
    // Haptic feedback
    vibrate([50, 30, 50]);
}

/**
 * Handle long press gestures
 */
function handleLongPress(zone) {
    console.log(`🔽 Long press on ${zone} zone`);
    
    if (zone === 'center') {
        // Long press center: Speed menu
        showSpeedMenu();
    } else if (zone === 'right') {
        // Long press right: Quality menu
        showQualityMenu();
    }
    
    vibrate(100);
}

/**
 * Handle swipe gestures
 */
function handleSwipe(direction) {
    console.log(`👈👉 Swipe ${direction}`);
    
    if (direction === 'right') {
        // Swipe right: Forward 30s
        skipTime(30);
        showSkipIndicator('right', '+30s');
    } else {
        // Swipe left: Rewind 30s
        skipTime(-30);
        showSkipIndicator('left', '-30s');
    }
    
    vibrate(50);
}

/**
 * Setup keyboard controls
 */
function setupKeyboardControls(player) {
    document.addEventListener('keydown', (e) => {
        // Ignore if typing in input field
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            return;
        }
        
        switch(e.key) {
            case ' ':
            case 'k':
                e.preventDefault();
                togglePlayPause();
                break;
            case 'ArrowLeft':
                e.preventDefault();
                skipTime(-10);
                showSkipIndicator('left', '-10s');
                break;
            case 'ArrowRight':
                e.preventDefault();
                skipTime(10);
                showSkipIndicator('right', '+10s');
                break;
            case 'ArrowUp':
                e.preventDefault();
                adjustVolume(10);
                showVolumeIndicator();
                break;
            case 'ArrowDown':
                e.preventDefault();
                adjustVolume(-10);
                showVolumeIndicator();
                break;
            case 'f':
                e.preventDefault();
                toggleFullscreen();
                break;
            case 'm':
                e.preventDefault();
                toggleMute();
                break;
            case 'p':
                e.preventDefault();
                togglePictureInPicture();
                break;
        }
    });
}

/**
 * Toggle play/pause
 */
function togglePlayPause() {
    gestureState.isPlaying = !gestureState.isPlaying;
    // Trigger play/pause on iframe (depends on player implementation)
    console.log(`▶️⏸️ ${gestureState.isPlaying ? 'Playing' : 'Paused'}`);
    showNotification(gestureState.isPlaying ? '▶️ Playing' : '⏸️ Paused', 'info');
}

/**
 * Skip time (forward/backward)
 */
function skipTime(seconds) {
    // Implementation depends on player API
    console.log(`⏩⏪ Skip ${seconds}s`);
}

/**
 * Adjust volume
 */
function adjustVolume(delta) {
    gestureState.volume = Math.max(0, Math.min(100, gestureState.volume + delta));
    console.log(`🔊 Volume: ${gestureState.volume}%`);
}

/**
 * Toggle mute
 */
function toggleMute() {
    const wasMuted = gestureState.volume === 0;
    gestureState.volume = wasMuted ? 100 : 0;
    console.log(`🔇 ${wasMuted ? 'Unmuted' : 'Muted'}`);
    showVolumeIndicator();
}

/**
 * Toggle fullscreen
 */
function toggleFullscreen() {
    const container = document.querySelector('.video-container');
    
    if (!document.fullscreenElement) {
        container.requestFullscreen().catch(err => {
            console.error('❌ Fullscreen error:', err);
        });
    } else {
        document.exitFullscreen();
    }
}

/**
 * Setup auto-hide controls on mobile
 */
function setupAutoHideControls(container) {
    const hideDelay = 3000; // 3 seconds
    
    const resetHideTimer = () => {
        clearTimeout(gestureState.controlsTimeout);
        container.classList.add('controls-visible');
        
        gestureState.controlsTimeout = setTimeout(() => {
            if (gestureState.isPlaying) {
                container.classList.remove('controls-visible');
            }
        }, hideDelay);
    };
    
    container.addEventListener('touchstart', resetHideTimer);
    container.addEventListener('mousemove', resetHideTimer);
}

/**
 * Setup Picture-in-Picture
 */
function setupPictureInPicture(player) {
    const pipBtn = document.querySelector('.player-pip-btn');
    
    if (pipBtn && document.pictureInPictureEnabled) {
        pipBtn.addEventListener('click', togglePictureInPicture);
    }
}

/**
 * Toggle Picture-in-Picture
 */
async function togglePictureInPicture() {
    const player = document.getElementById('moviePlayer') || document.getElementById('seriesPlayer');
    
    if (!document.pictureInPictureEnabled) {
        showNotification('❌ Picture-in-Picture not supported', 'error');
        return;
    }
    
    try {
        if (document.pictureInPictureElement) {
            await document.exitPictureInPicture();
            gestureState.isPiP = false;
            showNotification('📺 Exited Picture-in-Picture', 'info');
        } else {
            // For iframe players, this might not work directly
            showNotification('🖼️ Entering Picture-in-Picture...', 'info');
            gestureState.isPiP = true;
        }
    } catch (error) {
        console.error('❌ PiP error:', error);
        showNotification('❌ Picture-in-Picture failed', 'error');
    }
}

/**
 * UI Indicators
 */
function showSkipIndicator(side, text) {
    // Create skip indicator element
    const indicator = document.createElement('div');
    indicator.className = `skip-indicator skip-${side}`;
    indicator.innerHTML = `<i class="fas fa-redo"></i> ${text}`;
    
    document.querySelector('.video-container').appendChild(indicator);
    
    setTimeout(() => indicator.remove(), 800);
}

function showVolumeIndicator() {
    const volume = gestureState.volume;
    const icon = volume === 0 ? '🔇' : volume < 50 ? '🔉' : '🔊';
    showNotification(`${icon} Volume: ${volume}%`, 'info');
}

function toggleControls(container) {
    container.classList.toggle('controls-visible');
}

function showSpeedMenu() {
    showNotification('⚡ Speed menu - Coming soon', 'info');
}

function showQualityMenu() {
    showNotification('🎬 Quality menu - Coming soon', 'info');
}

/**
 * Haptic feedback
 */
function vibrate(pattern) {
    if ('vibrate' in navigator) {
        navigator.vibrate(pattern);
    }
}

/**
 * Show notification (reuse from detail-share.js)
 */
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `player-notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.classList.add('show'), 10);
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 2000);
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePlayerGestures);
} else {
    initializePlayerGestures();
}

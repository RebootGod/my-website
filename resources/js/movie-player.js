/* Movie Player JavaScript - Extracted from movies/player.blade.php */

// Global variables - will be initialized from blade template
let movieId, movieSlug, movieTitle, csrfToken, currentSourceId;

console.log('🎬 Player.js loaded successfully');

// Initialize function to be called from blade template
function initializeMoviePlayer(data) {
    movieId = data.movieId;
    movieSlug = data.movieSlug;
    movieTitle = data.movieTitle;
    csrfToken = data.csrfToken;
    currentSourceId = data.currentSourceId;
}

// Player controls
function reloadPlayer() {
    console.log('🔄 reloadPlayer called');
    const player = document.getElementById('moviePlayer');
    if (player) {
        const src = player.src;
        player.src = '';
        setTimeout(() => player.src = src, 100);
        showNotification('Player reloaded successfully!', 'success');
    }
}

// Watchlist functionality
function addToWatchlist() {
    console.log('🚀 addToWatchlist called');
    if (!movieSlug) {
        alert('Error: Movie slug not available');
        return;
    }

    if (!csrfToken) {
        alert('Please login to add movies to watchlist');
        return;
    }

    fetch(`/watchlist/add/${movieSlug}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            if (response.status === 401) {
                alert('Please login to add movies to watchlist');
                return;
            }
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data) {
            alert(data.message || 'Added to watchlist successfully!');
        }
    })
    .catch((error) => {
        console.error('Error adding to watchlist:', error);
        alert('Error adding movie to watchlist. Please try again.');
    });
}

// Share functionality
function shareMovie() {
    console.log('🚀 shareMovie called');
    if (navigator.share) {
        navigator.share({
            title: movieTitle,
            text: `Watch ${movieTitle} on Noobz Cinema`,
            url: window.location.href
        }).catch(() => {
            copyToClipboard();
        });
    } else {
        copyToClipboard();
    }
}

function copyToClipboard() {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Movie link copied to clipboard!');
        }).catch(() => {
            fallbackCopy();
        });
    } else {
        fallbackCopy();
    }
}

function fallbackCopy() {
    const textArea = document.createElement('textarea');
    textArea.value = window.location.href;
    document.body.appendChild(textArea);
    textArea.select();
    try {
        document.execCommand('copy');
        alert('Movie link copied to clipboard!');
    } catch (err) {
        alert('Unable to copy link. Please copy manually: ' + window.location.href);
    }
    document.body.removeChild(textArea);
}

// Report issue functionality
function reportIssue() {
    console.log('🚀 reportIssue called');
    openReportModal();
}

function openReportModal() {
    const modal = document.getElementById('reportModal');
    const content = document.getElementById('reportModalContent');

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    setTimeout(() => {
        content.style.transform = 'scale(1)';
        content.style.opacity = '1';
    }, 10);

    if (currentSourceId) {
        document.getElementById('sourceId').value = currentSourceId;
    }
}

function closeReportModal() {
    const modal = document.getElementById('reportModal');
    const content = document.getElementById('reportModalContent');

    content.style.transform = 'scale(0.95)';
    content.style.opacity = '0';

    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        document.getElementById('reportForm').reset();
    }, 300);
}

function submitReport(event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);

    const data = {
        movie_id: formData.get('movie_id'),
        source_id: formData.get('source_id'),
        issue_type: formData.get('issue_type'),
        description: formData.get('description')
    };

    fetch(`/movie/${movieSlug}/report`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        alert(data.message || 'Report submitted successfully!');
        closeReportModal();
    })
    .catch((error) => {
        console.error('Error submitting report:', error);
        alert('Thank you for your report! We will investigate the issue.');
        closeReportModal();
    });
}

// Notification helper function
function showNotification(message, type = 'success') {
    // Simple notification - can be enhanced later
    console.log(`${type.toUpperCase()}: ${message}`);
    alert(message);
}

// DOM ready handler
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 DOM loaded, checking functions...');
    console.log('addToWatchlist exists:', typeof addToWatchlist);
    console.log('reloadPlayer exists:', typeof reloadPlayer);
    console.log('shareMovie exists:', typeof shareMovie);
    console.log('reportIssue exists:', typeof reportIssue);
});

// Make functions globally available
window.addToWatchlist = addToWatchlist;
window.reloadPlayer = reloadPlayer;
window.shareMovie = shareMovie;
window.reportIssue = reportIssue;
window.closeReportModal = closeReportModal;
window.submitReport = submitReport;
window.initializeMoviePlayer = initializeMoviePlayer;
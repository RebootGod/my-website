/* ======================================== */
/* SERIES PLAYER JAVASCRIPT */
/* ======================================== */
/* Extracted from series player.blade.php for better code organization */

function reloadPlayer() {
    const iframe = document.getElementById('episodePlayer');
    if (iframe) {
        iframe.src = iframe.src;
    }
}

function shareEpisode() {
    if (navigator.share) {
        navigator.share({
            title: window.seriesPlayerConfig.shareTitle,
            text: window.seriesPlayerConfig.shareText,
            url: window.location.href
        });
    } else {
        navigator.clipboard.writeText(window.location.href);
        alert('Episode link copied to clipboard!');
    }
}

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

    // Convert FormData to JSON
    const data = {
        series_id: formData.get('series_id'),
        episode_id: formData.get('episode_id'),
        issue_type: formData.get('issue_type'),
        description: formData.get('description')
    };

    fetch(window.seriesPlayerConfig.reportUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': window.seriesPlayerConfig.csrfToken,
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
        alert(data.message || 'Report submitted successfully! Thank you for your feedback.');
        closeReportModal();
    })
    .catch((error) => {
        console.error('Error submitting report:', error);
        alert('Thank you for your report! We will investigate the issue.');
        closeReportModal();
    });
}

// Initialize function to be called from blade template
function initializeSeriesPlayer(config) {
    // Store config globally for access in other functions
    window.seriesPlayerConfig = config;

    console.log('Series player initialized with config:', config);
}
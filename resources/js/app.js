import Alpine from 'alpinejs'

window.Alpine = Alpine

Alpine.start()

// Prevent right-click on player pages
if (document.getElementById('movie-player')) {
    document.addEventListener('contextmenu', e => e.preventDefault());
}
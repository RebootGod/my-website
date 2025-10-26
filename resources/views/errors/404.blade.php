<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan | {{ config('app.name') }}</title>
    
    {{-- External CSS File --}}
    <link href="{{ asset('css/404_error_page.css') }}?v={{ time() }}" rel="stylesheet">
</head>
<body class="error-404-page">
    <!-- Animated Background -->
    <div class="background-animation">
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
    </div>

    <!-- Error Container -->
    <div class="error-container">
        <div class="film-icon">🎬</div>
        <h1 class="error-code">404</h1>
        <h2 class="error-title">Halaman Tidak Ditemukan</h2>
        <p class="error-message">
            Maaf, halaman yang Anda cari tidak dapat ditemukan. 
            Halaman mungkin telah dipindahkan, dihapus, atau URL yang Anda masukkan salah.
        </p>

        <!-- Action Buttons -->
        <div class="button-group">
            <a href="{{ route('home') }}" class="btn btn-primary">
                <span>🏠</span>
                <span>Kembali ke Beranda</span>
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                <span>←</span>
                <span>Halaman Sebelumnya</span>
            </button>
        </div>

        <!-- Help Box -->
        <div class="help-box">
            <h3>Butuh bantuan?</h3>
            <p>
                Jika Anda yakin halaman ini seharusnya ada, silakan hubungi administrator.
            </p>
        </div>
    </div>
</body>
</html>
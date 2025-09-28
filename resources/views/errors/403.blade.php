<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - {{ config('app.name') }}</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com" 
            integrity="sha384-6pzBo8GQ1xaHYbpRYwrFcPmLLtZ3JzKE5by3fTBqtBr3vLI6O5xmHwqDYh1pJ4" 
            crossorigin="anonymous"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center">
    <div class="text-center max-w-md mx-auto px-4">
        <!-- Error Icon -->
        <div class="mb-8">
            <svg class="mx-auto h-32 w-32 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>

        <!-- Error Message -->
        <div class="mb-8">
            <h1 class="text-6xl font-bold text-yellow-500 mb-4">403</h1>
            <h2 class="text-2xl font-semibold mb-4">Akses Ditolak</h2>
            <p class="text-gray-300 mb-6">
                Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.
                Silakan login dengan akun yang memiliki hak akses yang sesuai.
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="space-y-4">
            @guest
                <a href="{{ route('login') }}" 
                   class="inline-block bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-3 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105">
                    🔑 Login
                </a>
            @else
                <a href="{{ route('home') }}" 
                   class="inline-block bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-3 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105">
                    🏠 Kembali ke Beranda
                </a>
            @endguest
            
            <div class="mt-4">
                <button onclick="history.back()" 
                        class="text-gray-300 hover:text-white underline">
                    ← Kembali ke halaman sebelumnya
                </button>
            </div>
        </div>

        <!-- Additional Info -->
        <div class="mt-8 p-4 bg-gray-800 rounded-lg">
            <h3 class="font-semibold mb-2">Butuh akses?</h3>
            <p class="text-sm text-gray-300">
                Jika Anda memerlukan akses ke halaman ini, silakan hubungi administrator.
            </p>
        </div>
    </div>

    <!-- Background Animation -->
    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -top-4 -left-4 w-72 h-72 bg-yellow-300 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse"></div>
        <div class="absolute -bottom-8 -right-4 w-72 h-72 bg-orange-300 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-red-300 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse animation-delay-4000"></div>
    </div>
</body>
</html>
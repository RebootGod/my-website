<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Tidak Ditemukan - {{ config('app.name') }}</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com" 
            integrity="sha384-6pzBo8GQ1xaHYbpRYwrFcPmLLtZ3JzKE5by3fTBqtBr3vLI6O5xmHwqDYh1pJ4" 
            crossorigin="anonymous"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center">
    <div class="text-center max-w-md mx-auto px-4">
        <!-- Error Icon -->
        <div class="mb-8">
            <svg class="mx-auto h-32 w-32 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.98-.833-2.75 0L3.064 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
        </div>

        <!-- Error Message -->
        <div class="mb-8">
            <h1 class="text-6xl font-bold text-red-500 mb-4">404</h1>
            <h2 class="text-2xl font-semibold mb-4">Halaman Tidak Ditemukan</h2>
            <p class="text-gray-300 mb-6">
                Maaf, halaman yang Anda cari tidak dapat ditemukan. 
                Halaman mungkin telah dipindahkan, dihapus, atau URL yang Anda masukkan salah.
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="space-y-4">
            <a href="{{ route('home') }}" 
               class="inline-block bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105">
                🏠 Kembali ke Beranda
            </a>
            
            <div class="mt-4">
                <button onclick="history.back()" 
                        class="text-gray-300 hover:text-white underline">
                    ← Kembali ke halaman sebelumnya
                </button>
            </div>
        </div>

        <!-- Additional Help -->
        <div class="mt-8 p-4 bg-gray-800 rounded-lg">
            <h3 class="font-semibold mb-2">Butuh bantuan?</h3>
            <p class="text-sm text-gray-300">
                Jika Anda yakin halaman ini seharusnya ada, silakan hubungi administrator.
            </p>
        </div>
    </div>

    <!-- Background Animation -->
    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -top-4 -left-4 w-72 h-72 bg-red-300 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse"></div>
        <div class="absolute -bottom-8 -right-4 w-72 h-72 bg-yellow-300 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-pink-300 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse animation-delay-4000"></div>
    </div>
</body>
</html>
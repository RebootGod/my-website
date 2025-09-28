<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error - {{ config('app.name') }}</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            font-family: 'Arial', sans-serif;
            overflow-x: hidden;
        }
        
        .error-container {
            animation: fadeInUp 1s ease-out;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .glitch {
            position: relative;
            color: #fff;
            animation: glitch 2s infinite;
        }
        
        .glitch:before,
        .glitch:after {
            content: attr(data-text);
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        .glitch:before {
            animation: glitch-1 0.3s infinite;
            color: #ff0040;
            z-index: -1;
        }
        
        .glitch:after {
            animation: glitch-2 0.3s infinite;
            color: #00ffff;
            z-index: -2;
        }
        
        @keyframes glitch {
            0%, 100% { transform: translate(0) }
            20% { transform: translate(-2px, 2px) }
            40% { transform: translate(-2px, -2px) }
            60% { transform: translate(2px, 2px) }
            80% { transform: translate(2px, -2px) }
        }
        
        @keyframes glitch-1 {
            0%, 100% { transform: translate(0) }
            10% { transform: translate(-2px, -1px) }
            20% { transform: translate(-1px, 2px) }
            30% { transform: translate(1px, 2px) }
            40% { transform: translate(-1px, -1px) }
            50% { transform: translate(2px, -1px) }
            60% { transform: translate(-2px, 1px) }
            70% { transform: translate(-1px, 0px) }
            80% { transform: translate(-1px, -1px) }
            90% { transform: translate(2px, 1px) }
        }
        
        @keyframes glitch-2 {
            0%, 100% { transform: translate(0) }
            10% { transform: translate(1px, 0) }
            20% { transform: translate(-1px, 0) }
            30% { transform: translate(0, 1px) }
            40% { transform: translate(0, -1px) }
            50% { transform: translate(-1px, 1px) }
            60% { transform: translate(1px, 1px) }
            70% { transform: translate(1px, -1px) }
            80% { transform: translate(-1px, -1px) }
            90% { transform: translate(1px, 0) }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translate3d(0, 40px, 0);
            }
            to {
                opacity: 1;
                transform: translate3d(0, 0, 0);
            }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        @keyframes pulse-glow {
            0%, 100% { 
                box-shadow: 0 0 20px rgba(220, 53, 69, 0.3),
                           0 0 40px rgba(220, 53, 69, 0.1);
            }
            50% { 
                box-shadow: 0 0 30px rgba(220, 53, 69, 0.6),
                           0 0 60px rgba(220, 53, 69, 0.3);
            }
        }
        
        .floating-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: particle-float 6s infinite linear;
        }
        
        @keyframes particle-float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-10vh) rotate(360deg);
                opacity: 0;
            }
        }
        
        .btn-modern {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            position: relative;
            overflow: hidden;
            transform: perspective(1px) translateZ(0);
            transition: all 0.3s ease;
        }
        
        .btn-modern:hover {
            background: linear-gradient(135deg, #c82333, #dc3545);
            transform: translateY(-2px);
            animation: pulse-glow 1.5s infinite;
        }
        
        .btn-modern:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-modern:hover:before {
            left: 100%;
        }
        
        .server-icon {
            animation: float 3s ease-in-out infinite;
            filter: drop-shadow(0 10px 20px rgba(220, 53, 69, 0.3));
        }
        
        .info-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .reload-btn {
            transition: all 0.3s ease;
        }
        
        .reload-btn:hover {
            transform: rotate(180deg);
            color: #ffc107 !important;
        }
    </style>
</head>
<body class="min-vh-100 d-flex align-items-center justify-content-center text-white position-relative">
    <!-- Floating Particles Background -->
    <div class="floating-particles">
        <div class="particle" style="left: 10%; animation-delay: 0s; width: 4px; height: 4px;"></div>
        <div class="particle" style="left: 20%; animation-delay: 1s; width: 6px; height: 6px;"></div>
        <div class="particle" style="left: 30%; animation-delay: 2s; width: 3px; height: 3px;"></div>
        <div class="particle" style="left: 40%; animation-delay: 3s; width: 5px; height: 5px;"></div>
        <div class="particle" style="left: 50%; animation-delay: 4s; width: 4px; height: 4px;"></div>
        <div class="particle" style="left: 60%; animation-delay: 5s; width: 6px; height: 6px;"></div>
        <div class="particle" style="left: 70%; animation-delay: 6s; width: 3px; height: 3px;"></div>
        <div class="particle" style="left: 80%; animation-delay: 1.5s; width: 5px; height: 5px;"></div>
        <div class="particle" style="left: 90%; animation-delay: 2.5s; width: 4px; height: 4px;"></div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="error-container bg-dark bg-opacity-25 rounded-4 p-5 text-center shadow-lg">
                    <!-- Server Error Icon -->
                    <div class="mb-4">
                        <i class="fas fa-server server-icon display-1 text-danger"></i>
                    </div>

                    <!-- Error Number with Glitch Effect -->
                    <div class="mb-4">
                        <h1 class="glitch display-1 fw-bold text-danger" data-text="500" style="font-size: 8rem;">500</h1>
                    </div>

                    <!-- Error Title -->
                    <div class="mb-4">
                        <h2 class="h3 fw-bold text-white mb-3">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            Server Internal Error
                        </h2>
                        <p class="text-white-50 fs-5 mb-0">
                            Oops! Sepertinya server kami sedang mengalami masalah teknis. 
                            <br>Tim developer kami sedang bekerja keras mengatasi ini! 🔧
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-grid gap-3 mb-4">
                        <a href="{{ route('home') }}" class="btn btn-modern btn-lg rounded-pill text-white py-3">
                            <i class="fas fa-home me-2"></i>
                            Kembali ke Beranda
                        </a>
                        
                        <div class="d-flex justify-content-center gap-3">
                            <button onclick="location.reload()" class="btn btn-outline-light rounded-pill reload-btn">
                                <i class="fas fa-redo-alt me-1"></i> Coba Lagi
                            </button>
                            <button onclick="history.back()" class="btn btn-outline-secondary rounded-pill">
                                <i class="fas fa-arrow-left me-1"></i> Kembali
                            </button>
                        </div>
                    </div>

                    <!-- Information Card -->
                    <div class="info-card rounded-3 p-4 mt-4">
                        <h5 class="text-warning mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            Informasi Error
                        </h5>
                        <div class="row text-start">
                            <div class="col-6">
                                <small class="text-white-50">Error ID:</small>
                                <br>
                                <code class="text-info">#{{ strtoupper(Str::random(8)) }}</code>
                            </div>
                            <div class="col-6">
                                <small class="text-white-50">Waktu:</small>
                                <br>
                                <code class="text-info">{{ now()->format('d/m/Y H:i:s') }}</code>
                            </div>
                        </div>
                        <hr class="border-secondary my-3">
                        <p class="text-white-50 small mb-0">
                            <i class="fas fa-lightbulb text-warning me-1"></i>
                            Jika masalah terus berlanjut, silakan hubungi tim support kami dengan Error ID di atas.
                        </p>
                    </div>

                    <!-- Fun Message -->
                    <div class="mt-4 p-3 bg-primary bg-opacity-10 rounded-3 border border-primary border-opacity-25">
                        <p class="text-primary mb-0">
                            <i class="fas fa-coffee me-1"></i>
                            Sementara menunggu, mungkin saatnya ambil kopi? ☕
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Background Effects -->
    <div class="position-fixed top-0 start-0 w-100 h-100" style="z-index: -10;">
        <div class="position-absolute" style="top: 10%; left: 10%; width: 300px; height: 300px; background: radial-gradient(circle, rgba(220,53,69,0.1) 0%, transparent 70%); border-radius: 50%; animation: float 4s ease-in-out infinite;"></div>
        <div class="position-absolute" style="bottom: 10%; right: 10%; width: 200px; height: 200px; background: radial-gradient(circle, rgba(255,193,7,0.1) 0%, transparent 70%); border-radius: 50%; animation: float 6s ease-in-out infinite reverse;"></div>
        <div class="position-absolute" style="top: 50%; left: 50%; width: 150px; height: 150px; background: radial-gradient(circle, rgba(13,110,253,0.1) 0%, transparent 70%); border-radius: 50%; animation: float 5s ease-in-out infinite;"></div>
    </div>
</body>
</html>
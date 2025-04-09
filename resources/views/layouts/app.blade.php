<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Aplikasi pemantauan media untuk analisis konten">
    <meta name="theme-color" content="#0d6efd">
    <title>@yield('title', 'Media Monitoring')</title>
    
    <!-- Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts - Poppins & Inter (Update) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
    :root {
        /* Modern Color Palette - Glassmorphism & Neomorphism inspired */
        --primary-color: #4361ee;            /* Warna utama, biru modern */
        --primary-light: #4cc9f0;            /* Warna primer terang untuk gradasi */
        --primary-dark: #3a56d4;             /* Warna primer gelap untuk gradasi */
        --secondary-color: rgba(77, 77, 77, 0.15); /* Warna sekunder, dengan transparansi */
        --accent-color: #4cc9f0;             /* Warna aksen, biru muda */
        --accent-color-2: #f72585;           /* Aksen kedua, pink */
        --success-color: #0cce6b;            /* Warna sukses/positif */
        --danger-color: #e5383b;             /* Warna bahaya/error/negatif */
        --warning-color: #ff9e00;            /* Warna peringatan */
        --text-color: #2b2d42;               /* Warna teks utama */
        --text-light: #6c757d;               /* Warna teks sekunder */
        --light-bg: #f8f9fa;                 /* Background terang */
        
        /* Modern Shadow Styles */
        --card-shadow: 0 10px 20px rgba(0,0,0,0.04), 0 6px 6px rgba(0,0,0,0.05);
        --hover-shadow: 0 14px 28px rgba(0,0,0,0.08), 0 10px 10px rgba(0,0,0,0.05);
        --neomorphic-shadow: 5px 5px 10px rgba(0,0,0,0.05), -5px -5px 10px rgba(255,255,255,0.8);
        --glass-effect: backdrop-filter: blur(10px);
        
        /* Modern Transitions */
        --transition-fast: all 0.2s ease;
        --transition-normal: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        --transition-slow: all 0.5s ease;
        --border-radius-sm: 8px;
        --border-radius-md: 12px;
        --border-radius-lg: 16px;
    }
    
    body {
        font-family: 'Inter', 'Poppins', sans-serif; /* Kombinasi font modern */
        color: var(--text-color);
        background-color: #f5f7fa; /* Background warna lebih soft */
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        line-height: 1.6;
    }
    
    /* Navbar styling dengan Glassmorphism Effect */
    .custom-navbar {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-light)) !important;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(255,255,255,0.2);
        padding: 0.7rem 1rem;
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .navbar-logo-left {
        width: 60px;
        height: 60px;
        object-fit: contain;
        transition: var(--transition-normal);
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }
    
    .navbar-logo-left:hover {
        transform: scale(1.05);
    }

    .navbar-brand {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .navbar-nav {
        margin-left: auto;
    }

    .navbar-brand-center {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        flex-direction: column;
        align-items: center;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .navbar-title {
        font-size: 1.4rem;
        font-weight: 700;
        color: white;
        line-height: 1.1;
        letter-spacing: 0.5px;
    }

    .navbar-subtitle {
        font-size: 0.85rem;
        font-weight: 500;
        color: rgba(255,255,255,0.9);
        line-height: 1.1;
        letter-spacing: 0.3px;
    }
    
    .navbar-nav .nav-link {
        font-weight: 500;
        transition: var(--transition-normal);
        padding: 0.5rem 1rem;
        border-radius: var(--border-radius-sm);
        color: rgba(255,255,255,0.95) !important;
        letter-spacing: 0.3px;
    }
    
    .navbar-nav .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.15);
        transform: translateY(-1px);
    }
    
    /* Modern Floating Dropdown Menu */
    .dropdown-menu {
        border: none;
        box-shadow: var(--card-shadow);
        border-radius: var(--border-radius-md);
        overflow: hidden;
        border: 1px solid rgba(255,255,255,0.1);
        margin-top: 8px;
        animation: dropdownFade 0.2s ease-out;
    }
    
    @keyframes dropdownFade {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .dropdown-item {
        padding: 0.7rem 1.2rem;
        transition: var(--transition-normal);
        font-weight: 500;
        display: flex;
        align-items: center;
    }
    
    .dropdown-item:hover {
        background-color: var(--secondary-color);
        transform: translateX(3px);
    }
    
    .dropdown-item i {
        transition: var(--transition-normal);
    }
    
    .dropdown-item:hover i {
        transform: scale(1.1);
    }
    
    /* Content container with modern spacing */
    .content-fluid {
        flex: 1;
        padding: 2rem 1.5rem;
        max-width: 1300px;
        margin: 0 auto;
        width: 100%;
    }
    
    /* Modern Card Style with Neomorphism */
    .card {
        border: none;
        border-radius: var(--border-radius-md);
        box-shadow: var(--card-shadow);
        transition: var(--transition-normal);
        overflow: hidden;
        margin-bottom: 1.5rem;
        background-color: #ffffff;
        border: 1px solid rgba(0,0,0,0.03);
    }
    
    .card:hover {
        transform: translateY(-3px);
        box-shadow: var(--hover-shadow);
    }
    
    .card-header {
        background: linear-gradient(135deg, var(--primary-color, #4361ee), var(--accent-color, #4cc9f0));
        background-color: white;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        font-weight: 600;
        padding: 1.2rem 1.5rem;
        display: flex;
        align-items: center;
    }
    
    .card-header i {
        margin-right: 0.5rem;
        color: var(--primary-color);
    }
    
    /* Modern Alert Design */
    .alert {
        border-radius: var(--border-radius-md);
        border: none;
        box-shadow: var(--card-shadow);
        margin-bottom: 1.5rem;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
    }
    
    .alert-success {
        background-color: rgba(230, 244, 234, 0.8);
        color: #347045;
        border-left: 4px solid #28a745;
    }
    
    .alert-danger {
        background-color: rgba(252, 232, 232, 0.8);
        color: #c53929;
        border-left: 4px solid #dc3545;
    }
    
    /* Modern Date Navigation with Pill Design */
    .date-nav {
        display: flex;
        overflow-x: auto;
        gap: 0.7rem;
        padding: 0.75rem 0;
        margin-bottom: 1.75rem;
        -ms-overflow-style: none;
        scrollbar-width: none;
        scroll-behavior: smooth;
        padding-bottom: 5px;
    }
    
    .date-nav::-webkit-scrollbar {
        height: 5px;
    }
    
    .date-nav::-webkit-scrollbar-track {
        background: rgba(0,0,0,0.03);
        border-radius: 10px;
    }
    
    .date-nav::-webkit-scrollbar-thumb {
        background: rgba(0,0,0,0.15);
        border-radius: 10px;
    }
    
    .date-nav .date-item {
        padding: 0.6rem 1.2rem;
        border-radius: 50px; /* Pill shape */
        background-color: white;
        box-shadow: var(--neomorphic-shadow);
        white-space: nowrap;
        cursor: pointer;
        transition: var(--transition-normal);
        font-weight: 500;
        display: flex;
        align-items: center;
        border: 1px solid rgba(0,0,0,0.03);
    }
    
    .date-nav .date-item i {
        margin-right: 6px;
        font-size: 0.8rem;
    }
    
    .date-nav .active {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
        color: white;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(77, 97, 238, 0.3);
        border: none;
    }
    
    .date-nav .date-item:hover:not(.active) {
        background-color: var(--secondary-color);
        transform: translateY(-2px);
    }
    
    /* Modern Footer with subtle gradient */
    footer {
        background: linear-gradient(to right, rgba(255,255,255,0.9), rgba(248,249,250,0.9));
        box-shadow: 0 -2px 10px rgba(0,0,0,0.03);
        padding: 1.5rem 0;
        margin-top: auto;
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
        border-top: 1px solid rgba(0,0,0,0.04);
    }
    
    footer p {
        color: var(--text-light);
        font-weight: 500;
        font-size: 0.95rem;
    }
    
    /* Modern Button Styles with Subtle Gradient */
    .btn-primary {
        background: linear-gradient(135deg, var(--primary-color), #3b55e6);
        border: none;
        border-radius: var(--border-radius-sm);
        padding: 0.7rem 1.4rem;
        font-weight: 500;
        box-shadow: 0 4px 10px rgba(67, 97, 238, 0.2);
        transition: var(--transition-normal);
        position: relative;
        overflow: hidden;
        letter-spacing: 0.3px;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #3b55e6, var(--primary-color));
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(67, 97, 238, 0.3);
    }
    
    .btn-primary:active {
        transform: translateY(0);
        box-shadow: 0 2px 5px rgba(67, 97, 238, 0.2);
    }
    
    /* Modern Modal Design */
    #welcomeModal .modal-content {
        border: none;
        border-radius: var(--border-radius-lg);
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    #welcomeModal .modal-header {
        border-bottom: none;
        padding: 1.5rem 1.5rem 0.5rem;
    }

    #welcomeModal .modal-body {
        padding: 1.5rem 2rem;
    }

    #welcomeModal h2 {
        color: var(--primary-color);
        font-weight: 700;
        margin-bottom: 1.5rem;
        position: relative;
        display: inline-block;
    }
    
    #welcomeModal h2:after {
        content: '';
        position: absolute;
        width: 40%;
        height: 3px;
        background: linear-gradient(to right, var(--primary-color), var(--primary-light));
        bottom: -10px;
        left: 30%;
        border-radius: 50px;
    }

    #welcomeModal ul {
        padding-left: 1.5rem;
    }

    #welcomeModal li {
        margin-bottom: 1.2rem;
        line-height: 1.7;
        position: relative;
    }
    
    #welcomeModal li::marker {
        color: var(--primary-color);
    }

    #welcomeModal .btn-dark {
        background: linear-gradient(135deg, #333, #222);
        border-radius: 50px;
        padding: 0.6rem 2.5rem;
        font-weight: 500;
        border: none;
        transition: var(--transition-normal);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    
    #welcomeModal .btn-dark:hover {
        background: linear-gradient(135deg, #222, #333);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.25);
    }

    #welcomeModal .modal-footer {
        padding: 1rem 1.5rem 1.5rem;
    }

    /* Glassmorphism Effect for Containers */
    .glass-container {
        background: rgba(255,255,255,0.8);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-radius: var(--border-radius-md);
        border: 1px solid rgba(255,255,255,0.2);
        box-shadow: 0 8px 32px rgba(0,0,0,0.05);
    }
    
    /* Animation for Page Transitions */
    .page-transition {
        animation: fadeIn 0.5s ease-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Style untuk komponen di halaman isu */
    .breadcrumb {
        background-color: transparent;
        border-radius: var(--border-radius-md);
        font-size: 0.9rem;
        font-weight: 500;
        padding: 0.75rem 0;
    }
    
    .breadcrumb-item a {
        color: var(--primary-color);
        transition: var(--transition-fast);
    }
    
    .breadcrumb-item a:hover {
        color: var(--primary-dark);
        text-decoration: none;
    }
    
    .breadcrumb-item.active {
        color: var(--text-light);
        font-weight: 600;
    }
    
    /* Section Rangkuman */
    .rangkuman-section {
        margin-bottom: 2rem;
        padding: 1.5rem;
        background-color: rgba(67, 97, 238, 0.03);
        border-radius: var(--border-radius-md);
        border-left: 4px solid var(--primary-color);
    }
    
    .section-title {
        color: var(--primary-color);
        font-weight: 600;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid rgba(67, 97, 238, 0.1);
    }
    
    /* Content Text (untuk mencegah teks putih) */
    .content-text, .card-body p, .card-body ul, .card-body ol, .card-body li {
        color: var(--text-color) !important;
    }
    
    /* Badge with modern design */
    .badge {
        font-weight: 600;
        padding: 0.5rem 0.75rem;
        border-radius: 50px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    /* Style khusus untuk card header narasi */
    .narasi-card .card-header {
        display: flex;
        align-items: center;
        padding: 1rem 1.5rem;
    }

    .narasi-card .card-header.bg-success {
        background: linear-gradient(135deg, #28a745, #20c997) !important;
        border-bottom: none;
    }

    .narasi-card .card-header.bg-danger {
        background: linear-gradient(135deg, #dc3545, #e74c3c) !important;
        border-bottom: none;
    }

    .narasi-card .card-header i {
        color: rgba(255, 255, 255, 0.9);
        margin-right: 0.75rem;
        font-size: 1.1rem;
    }

    .narasi-card .card-header h5 {
        margin-bottom: 0;
        font-weight: 600;
        letter-spacing: 0.3px;
    }
    
    /* Referensi cards */
    .referensi-card {
        transition: var(--transition-normal);
    }
    
    .referensi-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--hover-shadow);
    }
    
    .card-title {
        color: var(--text-color);
        font-weight: 600;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 2rem;
        background-color: rgba(0,0,0,0.02);
        border-radius: var(--border-radius-md);
        color: var(--text-light);
    }
    
    .empty-state i {
        font-size: 2.5rem;
        color: var(--text-light);
        display: block;
        margin-bottom: 1rem;
    }
    
    /* Divider */
    .divider {
        margin: 2.5rem 0;
        height: 1px;
        background: linear-gradient(to right, transparent, rgba(0,0,0,0.1), transparent);
        border: none;
    }
    
    /* Action Buttons */
    .btn-action {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-weight: 500;
        font-size: 0.9rem;
        cursor: pointer;
        text-decoration: none;
        transition: var(--transition-normal);
        border: none;
    }
    
    .btn-edit {
        background-color: rgba(255, 193, 7, 0.1);
        color: #e0a800;
        border: 1px solid rgba(255, 193, 7, 0.3);
    }
    
    .btn-edit:hover {
        background-color: rgba(255, 193, 7, 0.2);
        color: #d39e00;
        transform: translateY(-2px);
    }
    
    .btn-delete {
        background-color: rgba(229, 56, 59, 0.1);
        color: var(--danger-color);
        border: 1px solid rgba(229, 56, 59, 0.3);
    }
    
    .btn-delete:hover {
        background-color: rgba(229, 56, 59, 0.2);
        color: #c82333;
        transform: translateY(-2px);
    }
    
    /* Responsive adjustments with modern breakpoints */
    @media (max-width: 992px) {
        .navbar-brand-center {
            position: static;
            transform: none;
            margin: 0 auto;
        }
        
        .navbar-logo-left {
            width: 45px;
            height: 45px;
        }
        
        .content-fluid {
            padding: 1.5rem 1rem;
        }
    }
    
    @media (max-width: 768px) {
        .isu-slider-container {
            max-height: 450px;
            overflow-y: auto;
            border-radius: var(--border-radius-md);
            box-shadow: var(--card-shadow);
        }
        
        .navbar-title {
            font-size: 1.3rem;
        }
        
        .navbar-subtitle {
            font-size: 0.8rem;
        }
        
        .card {
            margin-bottom: 1.2rem;
        }
        
        #welcomeModal .modal-dialog {
            max-width: 95%;
            margin: 10px auto;
        }
        
        .date-nav .date-item {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        .card-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .card-header > div:last-child {
            margin-top: 1rem;
        }
        
        .narasi-card {
            margin-bottom: 1.5rem;
        }
    }
</style>
    @yield('styles')
</head>
<body>

    <!-- Splash screen dengan animasi modern -->
    <div class="modal fade" id="welcomeModal" tabindex="-1" aria-labelledby="welcomeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <h2 class="fw-bold mb-4">Tentang Media Monitoring</h2>
                    <ul class="text-start">
                        <li>Laporan Monitoring Isu Strategis Nasional ini disusun oleh Tim Pengelolaan Media sebagai upaya untuk memahami perspektif media terhadap berbagai kebijakan, isu, dan topik yang berkembang di Indonesia pada <span id="current-date">{{ session('latestIsuDate', now()->format('d F Y')) }}</span>.</li>
                        <li>Isu-isu strategis yang disajikan dalam laporan ini bersumber dari data Kantor Komunikasi Kepresidenan (Presidential Communication Office / PCO).</li>
                        <li>Analisis dilakukan berdasarkan hasil dari Mesin PCO yang berbasis Social Network Analysis (SNA) dengan teknologi IMA dan ISA, yang dikombinasikan dengan penelitian kualitatif yang dikaji Tim Pengelolaan Media KSP melalui pemberitaan di media cetak dan media online.</li>
                        <li>Analisis yang dilakukan bertujuan untuk memberikan wawasan bagi insan Kantor Staf Presiden (KSP) dalam mencermati dinamika pemberitaan di media massa. Selain itu, laporan ini diharapkan dapat menjadi referensi dalam diskusi serta landasan bagi tindak lanjut terhadap isu-isu yang berkembang.</li>
                    </ul>
                </div>
                <div class="modal-footer justify-content-center border-0">
                    <button type="button" class="btn btn-dark px-4" data-bs-dismiss="modal">Mengerti</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Navbar dengan Glassmorphism effect -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4 custom-navbar">
        <div class="container d-flex align-items-center">
            <!-- Logo Kiri dengan animasi hover -->
            <a href="{{ route('home') }}" class="d-flex align-items-center">
                <img src="/header.png" alt="Logo KSP" class="navbar-logo-left">
            </a>
            
            <!-- Teks Tengah dengan bayangan text modern -->
            <div class="navbar-brand text-center navbar-brand-center">
                <div class="navbar-title">MEDIA MONITORING</div>
                <div class="navbar-subtitle">KANTOR STAF PRESIDEN</div>
            </div>
            
            <!-- Dropdown Pengguna dengan animasi -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    @guest
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('login') }}">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                    @else
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-white" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i>{{ Auth::user()->name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a href="#" class="dropdown-item"><i class="fas fa-user me-2"></i>Profil</a></li>
                                <li><a href="#" class="dropdown-item"><i class="fas fa-cog me-2"></i>Pengaturan</a></li>
                                <li><a class="dropdown-item" href="#" id="showAboutModal"><i class="fas fa-info-circle me-2"></i>Tentang</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endguest
                </ul>
            </div>
            
            <!-- Tombol Toggle untuk Mobile dengan animasi -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>

    <!-- Content dengan efek page transition -->
    <div class="content-fluid page-transition">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <!-- Footer dengan efek glassmorphism -->
    <footer class="py-3">
        <div class="container text-center">
            <p class="mb-0">
                Media Monitoring <i class="fas fa-copyright me-1"></i>Kantor Staf Presiden {{ date('Y') }} 
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get the "Tentang" link
        const aboutLink = document.getElementById('showAboutModal');

        // Add click event listener to show the modal
        if (aboutLink) {
            aboutLink.addEventListener('click', function(event) {
                event.preventDefault(); // Prevent default link behavior
                const welcomeModal = new bootstrap.Modal(document.getElementById('welcomeModal'));
                welcomeModal.show();
            });
        }
        
        // Animasi smooth scroll untuk date-nav
        const dateNav = document.querySelector('.date-nav');
        if (dateNav) {
            const activeDateItem = dateNav.querySelector('.active');
            if (activeDateItem) {
                setTimeout(() => {
                    activeDateItem.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                }, 300);
            }
        }
        
        // Tambahkan efek ripple pada tombol
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                const x = e.clientX - e.target.offsetLeft;
                const y = e.clientY - e.target.offsetTop;
                
                const ripple = document.createElement('span');
                ripple.style.left = `${x}px`;
                ripple.style.top = `${y}px`;
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    });
    </script>    
    @yield('scripts')
</body>
</html>
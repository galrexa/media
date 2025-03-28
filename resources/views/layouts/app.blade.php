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
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4361ee;      /* Warna utama, biru modern */
            --secondary-color:rgba(77, 77, 77, 0.25);    /* Warna sekunder, ungu gelap */
            --accent-color: #4cc9f0;       /* Warna aksen, biru muda */
            --accent-color-2: #f72585;     /* Aksen kedua, pink */
            --text-color: #2b2d42;         /* Warna teks utama */
            --text-light: #6c757d;         /* Warna teks sekunder */
            --light-bg: #f8f9fa;           /* Background terang */
            --card-shadow: 0 10px 20px rgba(0,0,0,0.05), 0 6px 6px rgba(0,0,0,0.07);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        body {
            font-family: 'Poppins', sans-serif; /* Font modern yang mudah dibaca */
            color: var(--text-color);
            background-color: #f5f5f7; /* Background warna abu-abu sangat terang */
            min-height: 100vh; /* Memastikan footer tetap di bawah */
            display: flex;
            flex-direction: column;
        }
        
        /* Navbar styling */
        
        .custom-navbar {
            background-color: var(--accent-color) !important; /* Warna biru muda seperti di gambar */
            padding: 0.5rem 1rem;
        }

        .navbar-logo-left {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }

        .navbar-brand {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .navbar-nav {
            margin-left: auto;
        }

        .navbar {
            box-shadow: var(--shadow);
            background: var(--primary-color) !important;
            padding: 0.8rem 0;
        }
        
        .navbar-brand-center {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .navbar-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            line-height: 1;
        }

        .navbar-subtitle {
            font-size: 0.9rem;
            font-weight: 500;
            color: white;
            line-height: 1;
        }
        
        .navbar-nav .nav-link {
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 4px;
        }
        
        .navbar-nav .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        /* Dropdown styling */
        .dropdown-menu {
            border: none;
            box-shadow: var(--shadow);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .dropdown-item {
            padding: 0.7rem 1.2rem;
            transition: all 0.2s;
        }
        
        .dropdown-item:hover {
            background-color: var(--secondary-color);
        }
        
        /* Content container */
        .content-container {
            flex: 1; /* Memastikan konten mengisi ruang yang tersedia */
            padding: 1.5rem 0;
            max-width: 1200px; /* Membatasi lebar maksimum konten */
            margin: 0 auto; /* Mengatur konten ke tengah */
        }
        
        /* Card styling */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.12);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-weight: 600;
            padding: 1rem 1.25rem;
        }
        
        /* Alert styling */
        .alert {
            border-radius: 8px;
            border: none;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background-color: #e6f4ea;
            color: #347045;
        }
        
        .alert-danger {
            background-color: #fce8e8;
            color: #c53929;
        }
        
        /* Date navigation styling */
        .date-nav {
            display: flex;
            overflow-x: auto;
            gap: 0.5rem;
            padding: 0.5rem 0;
            margin-bottom: 1.5rem;
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
        
        .date-nav::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }
        
        .date-nav .date-item {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            background-color: white;
            box-shadow: var(--shadow);
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .date-nav .active {
            background-color: var(--accent-color);
            color: black;
            font-weight: 600;
        }
        
        .date-nav .date-item:hover:not(.active) {
            background-color: var(--secondary-color);
        }
        
        /* Footer styling */
        footer {
            background-color: white;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
            padding: 1.5rem 0;
            margin-top: auto; /* Mendorong footer ke bawah */
        }
        
        footer p {
            color: var(--text-light);
            font-weight: 500;
        }
        
        /* Button styling */
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            border-radius: 6px;
            padding: 0.6rem 1.2rem;
            font-weight: 500;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #0b5ed7;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .isu-slider-container {
                max-height: 400px;
                overflow-y: auto;
                border-radius: 10px;
                box-shadow: var(--shadow);
            }
            
            .navbar-brand {
                font-size: 1.2rem;
            }
            
            .content-container {
                padding: 1rem;
            }
            
            .card {
                margin-bottom: 1rem;
            }
        }
        
        /* Dark mode support for users with system preference */
        @media (prefers-color-scheme: dark) {
            :root {
                --secondary-color: #303134;
                --text-color: #e8eaed;
                --text-light: #bdc1c6;
            }
            
            body {
                background-color: #202124;
            }
            
            .card, .dropdown-menu, footer, .date-nav .date-item {
                background-color: #303134;
            }
            
            .card-header {
                background-color: #303134;
                border-bottom: 1px solid rgba(255,255,255,0.05);
            }
            
            .dropdown-item:hover {
                background-color: #3c4043;
            }
            
            .alert-success {
                background-color: #0d3121;
                color: #a2e0bc;
            }
            
            .alert-danger {
                background-color: #3c1314;
                color: #f9c6c6;
            }
        }

        #welcomeModal .modal-content {
        border: none;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    #welcomeModal ul {
        padding-left: 1.5rem;
    }

    #welcomeModal li {
        margin-bottom: 1rem;
        line-height: 1.6;
    }

    #welcomeModal .btn-dark {
        border-radius: 5px;
        padding: 0.5rem 2rem;
        font-weight: 500;
    }

    #welcomeModal .modal-dialog {
        max-width: 800px; 
        margin: 0 auto; 
    }

    @media (max-width: 768px) {
        #welcomeModal .modal-dialog {
            max-width: 90%;
        }
    }

    </style>
    @yield('styles')
</head>
<body>

    <!-- Splash screen -->
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

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4 custom-navbar">
        <div class="container d-flex align-items-center">
            <!-- Logo Kiri -->
            <a href="{{ route('home') }}"><img src="/header.png" alt="Logo KSP" class="navbar-logo-left"></a>
            
        <!-- Teks Tengah -->
        <div class="navbar-brand text-center navbar-brand-center">
            <div class="navbar-title">MEDIA MONITORING</div>
            <div class="navbar-subtitle">KANTOR STAF PRESIDEN</div>
        </div>
            
            <!-- Dropdown Pengguna -->
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
            
            <!-- Tombol Toggle untuk Mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>

    <!-- Content -->
    <div class="content-fluid">
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

    <!-- Footer -->
    <footer class="py-3">
        <div class="container text-center">
            <p class="mb-0">
                <i class="fas fa-copyright me-1"></i> {{ date('Y') }} Media Monitoring | Hak Cipta Dilindungi
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
    });
    </script>    
    @yield('scripts')
</body>
</html>
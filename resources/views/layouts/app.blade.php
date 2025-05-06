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

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">

    <!-- Google Fonts - Poppins & Inter (Update) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/layouts/app.css') }}">
    @yield('styles')
</head>
<body>

    <!-- Splash screen dengan animasi modern -->
    @include('partials.modal')

    <!-- Navbar dengan Glassmorphism effect -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4 custom-navbar">
        <div class="container-fluid px-3">
            <!-- Mobile Layout Group -->
            <div class="d-flex align-items-center justify-content-between w-100">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="navbar-logo-link">
                    <img src="/header.png" alt="Logo KSP" class="navbar-logo-left">
                </a>

                <!-- Mobile Brand - Responsive Text -->
                <div class="navbar-brand-mobile text-center mx-2 flex-grow-1">
                    <div class="navbar-title-mobile">MEDIA MONITORING</div>
                    <div class="navbar-subtitle-mobile">Kantor Staf Presiden</div>
                </div>

                <!-- Toggle Button -->
                <button class="navbar-toggler ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>

            <!-- Collapsible Menu -->
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
                                <i class="fas fa-user me-1"></i>{{ Auth::user()->name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a href="{{ route('profile.index') }}" class="dropdown-item"><i class="fas fa-user me-2"></i>Profil</a></li>
                                <!-- <li><a href="{{ route('settings.index') }}" class="dropdown-item"><i class="fas fa-cog me-2"></i>Pengaturan</a></li> -->
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
                Media Monitoring <i class="fas fa-copyright me-1"></i>KSP {{ date('Y') }}
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

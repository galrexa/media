<!-- resources/views/layouts/admin.blade.php -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin dashboard untuk sistem pemantauan media">
    <meta name="theme-color" content="#4361ee">
    <title>@yield('title', 'Admin Dashboard')</title>

    <!-- Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts - Inter & Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/layouts/admin.css') }}">
    @yield('styles')
</head>
<body>
    <!-- Navbar fixed di atas (Simpel Tanpa Search & Notification) -->
<header class="top-navbar">
    <div class="navbar-container px-3">

        <!-- Brand/logo dengan toggle sidebar -->
        <div class="d-flex align-items-center w-100">
            <button class="toggle-sidebar d-none d-md-flex" id="toggleSidebar" aria-label="Toggle Sidebar">
                <i class="bi bi-list"></i>
            </button>

            <!-- Mobile Menu Button -->
            <button class="mobile-toggle d-md-none me-2" id="mobileSidebarToggle" aria-label="Toggle Mobile Sidebar">
                <i class="bi bi-list"></i>
            </button>
            <span class="brand-text">Media Monitoring</span>

            <!-- User Profile Dropdown - diperbarui agar mirip dengan app.css -->
            <div class="navbar-actions ms-auto">
                <ul class="navbar-nav">
                    <li class="nav-item dropdown user-dropdown">
                        <a class="nav-link dropdown-toggle text-white user-dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i>{{ Auth::user()->name ?? 'Admin' }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a href="{{ route('profile.index') }}" class="dropdown-item"><i class="fas fa-user me-2"></i>Profil</a></li>
                            <li><a href="{{ route('settings.index') }}" class="dropdown-item"><i class="fas fa-cog me-2"></i>Pengaturan</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-info-circle me-2"></i>Tentang</a></li>
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
                </ul>
            </div>
        </div>
    </div>
</header>
    <!-- Sidebar dengan Glassmorphism Effect -->
    <nav id="sidebar" class="sidebar">
        <div class="sidebar-content">

            <div class="sidebar-menu">
                @include('partials.sidebar')
            </div>
        </div>
    </nav>

    <!-- Main Content dengan Modern UI -->
    <main id="content" class="main-content">
        <!-- Alerts dengan modern styling -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Page Content dengan animasi transisi -->
        <div class="page-content">
            @yield('content')
        </div>
    </main>

    <!-- Mobile Toggle Button dengan efek floating -->
    <button class="mobile-toggle" id="mobileToggle">
        <i class="bi bi-list"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- Script untuk admin.blade.php dengan notifikasi terintegrasi -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');
        const toggleSidebar = document.getElementById('toggleSidebar');
        const mobileToggle = document.getElementById('mobileToggle');

        // Desktop sidebar toggle dengan animasi
        toggleSidebar?.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            content.classList.toggle('expanded');
        });

        // Mobile sidebar toggle dengan animasi
        mobileToggle?.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                const isClickInsideSidebar = sidebar.contains(e.target);
                const isClickOnToggle = mobileToggle.contains(e.target);

                if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('show');
            }
        });

        // Active menu highlighting dengan efek visual
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link');

        navLinks.forEach(link => {
            if (link.getAttribute('href') && link.getAttribute('href') !== '#') {
                const href = link.getAttribute('href');
                if (currentPath === href || currentPath.startsWith(href)) {
                    link.classList.add('active');

                    // If it's a submenu item, highlight the parent menu too
                    if (link.closest('.submenu')) {
                        const parentIndex = Array.from(link.closest('.submenu').parentElement.children)
                            .indexOf(link.closest('.submenu')) - 1;
                        const parentMenu = link.closest('.submenu').parentElement.children[parentIndex];
                        if (parentMenu && parentMenu.querySelector('.nav-link')) {
                            parentMenu.querySelector('.nav-link').classList.add('active');
                        }
                    }
                }
            }
        });

        // Efek ripple pada button
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

        // ====== FITUR NOTIFIKASI ======

            // Sembunyikan notifikasi badge saat dropdown dibuka
            if (markAllAsRead) {
                markAllAsRead.addEventListener('click', function(e) {
                    e.preventDefault();
                    markAllNotificationsAsRead();
                });
            }
        });

        // Fungsi untuk menyembunyikan badge notifikasi
        function hideNotificationBadge() {
            const badge = document.getElementById('notification-badge');
            if (badge) {
                badge.style.display = 'none';

                // Kirim AJAX request untuk menyimpan status di session
                fetch('{{ route("reset.notification.badge") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ status: 'viewed' })
                })
                .then(response => response.json())
                .then(data => console.log('Notification badge reset:', data))
                .catch(error => console.error('Error resetting notification badge:', error));
            }
        }

        // Fungsi untuk menandai semua notifikasi sebagai terbaca
        function markAllNotificationsAsRead() {
            fetch('{{ route("markAllAsRead") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Sembunyikan badge
                    const badge = document.getElementById('notification-badge');
                    if (badge) {
                        badge.style.display = 'none';
                    }

                    // Hapus kelas 'unread' dari semua notifikasi
                    const unreadItems = document.querySelectorAll('.notification-item.unread');
                    unreadItems.forEach(item => {
                        item.classList.remove('unread');
                    });

                    // Update badge di header notifikasi
                    const headerBadge = document.querySelector('.notification-header .badge');
                    if (headerBadge) {
                        headerBadge.style.display = 'none';
                    }

                    // Sembunyikan tombol "Tandai Sudah Dibaca"
                    const markAllReadBtn = document.getElementById('markAllAsRead');
                    if (markAllReadBtn) {
                        markAllReadBtn.style.display = 'none';
                    }
                }
            })
            .catch(error => console.error('Error marking notifications as read:', error));
        }
</script>
    @yield('scripts')
</body>
</html>

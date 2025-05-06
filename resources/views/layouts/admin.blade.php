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
    <link rel="stylesheet" href="{{ asset('css/custom/admin.css') }}">
    @yield('styles')
    
    <!-- Meta tag untuk memastikan rendering mobile yang baik -->
    <meta name="HandheldFriendly" content="true">
    <meta name="apple-mobile-web-app-capable" content="yes">
</head>
<body>
    <!-- Navbar fixed di atas dengan dukungan mobile dan countdown timer -->
    <header class="top-navbar">
        <div class="navbar-container px-3">
            <!-- Brand/logo dengan toggle sidebar -->
            <div class="d-flex align-items-center w-100">
                <!-- Mobile Menu Button - tampil hanya di mobile -->
                <button class="mobile-toggle d-md-none" id="mobileSidebarToggle" aria-label="Toggle Mobile Sidebar">
                    <i class="bi bi-list"></i>
                </button>
                
                <!-- Desktop Menu Button - tampil hanya di desktop -->
                <button class="toggle-sidebar d-none d-md-flex" id="toggleSidebar" aria-label="Toggle Desktop Sidebar">
                    <i class="bi bi-list"></i>
                </button>
                
                <span class="brand-text">Media Monitoring</span>

                <!-- Countdown Timer -->
                <div class="countdown-container ms-auto me-2" id="countdownContainer">
                    <i class="bi bi-alarm-fill countdown-icon"></i>
                    <div class="countdown-timer">
                        <span id="countdownTimer">00:00:00</span>
                    </div>
                    <span class="countdown-overdue-label d-none" id="overdueLabel">Overtime</span>
                </div>

                <!-- User Profile Dropdown - diperbarui agar responsif -->
                <div class="navbar-actions">
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown user-dropdown">
                            <a class="nav-link dropdown-toggle text-white user-dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1 d-none d-sm-inline-block"></i>
                                <span class="d-none d-sm-inline-block">{{ Auth::user()->name ?? 'Admin' }}</span>
                                <i class="fas fa-user-circle d-sm-none"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a href="{{ route('profile.index') }}" class="dropdown-item"><i class="fas fa-user me-2"></i>Profil</a></li>
                                @if (Auth::user()->isAdmin() || Auth::user()->isEditor())
                                    <li><a href="{{ route('settings.index') }}" class="dropdown-item"><i class="fas fa-cog me-2"></i>Pengaturan</a></li>
                                @endif
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

    <!-- Sidebar dengan dukungan mobile -->
    <nav id="sidebar" class="sidebar">
        <div class="sidebar-content">
            <!-- Tambahkan header mobile dengan tombol tutup -->
            <div class="sidebar-header d-flex d-md-none align-items-center justify-content-between">
                <h5 class="m-0">Menu</h5>
                <button class="btn-close sidebar-close" aria-label="Close"></button>
            </div>
            
            <div class="sidebar-menu">
                @include('partials.sidebar')
            </div>
        </div>
    </nav>

    <!-- Main Content dengan dukungan mobile -->
    <main id="content" class="main-content">
        <!-- Alerts dengan styling modern -->
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

        <!-- Page Content dengan transisi -->
        <div class="page-content">
            @yield('content')
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script untuk admin.blade.php dengan notifikasi dan dukungan mobile -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    const toggleSidebar = document.getElementById('toggleSidebar');
    const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
    
    // Tambahkan overlay untuk mobile
    const createOverlay = () => {
        const overlay = document.createElement('div');
        overlay.classList.add('sidebar-overlay');
        document.body.appendChild(overlay);
        
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
        
        return overlay;
    };
    
    const overlay = createOverlay();
    
    // Desktop sidebar toggle dengan fungsi expand
    if (toggleSidebar) {
        toggleSidebar.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            content.classList.toggle('expanded');
        });
    }
    
    // Mobile sidebar toggle
    if (mobileSidebarToggle) {
        mobileSidebarToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });
    }
    
    // Tambahkan event listener untuk tombol tutup di sidebar mobile
    const sidebarClose = document.querySelector('.sidebar-close');
    if (sidebarClose) {
        sidebarClose.addEventListener('click', () => {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }
    
    // Handle window resize
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        }
    });
    
    // Tutup sidebar saat klik di luar
    document.addEventListener('click', (e) => {
        const isClickInsideSidebar = sidebar.contains(e.target);
        const isClickOnToggle = mobileSidebarToggle && mobileSidebarToggle.contains(e.target);
        
        if (!isClickInsideSidebar && !isClickOnToggle && 
            sidebar.classList.contains('show') && 
            window.innerWidth <= 768) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        }
    });
    
    // Tutup sidebar saat tekan Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        }
    });
    
    // Active menu highlighting
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        if (link.getAttribute('href') && link.getAttribute('href') !== '#') {
            const href = link.getAttribute('href');
            if (currentPath === href || currentPath.startsWith(href)) {
                link.classList.add('active');
                
                // Jika item submenu, highlight juga parent menu
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
    
    // Efek ripple pada tombol
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
    
    // Bagian khusus untuk menangani rejected badge
    function hideRejectedBadge() {
        const badge = document.getElementById('rejected-badge');
        if (badge) {
            badge.style.display = 'none';
            
            // Kirim AJAX request untuk menyimpan status
            fetch('{{ route("reset.rejected.badge") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ status: 'viewed' })
            })
            .then(response => response.json())
            .then(data => console.log('Badge reset success:', data))
            .catch(error => console.error('Error resetting badge:', error));
        }
    }
    
    // Tambahkan event listener untuk tombol rejected
    const rejectedMenuLink = document.getElementById('rejected-menu-link');
    if (rejectedMenuLink) {
        rejectedMenuLink.addEventListener('click', hideRejectedBadge);
    }
    
    // Fungsi untuk countdown timer ke jam 14.00 setiap hari
    function updateCountdown() {
        // Ambil waktu saat ini
        const now = new Date();
        
        // Tentukan target waktu jam 14:00 hari ini
        const targetTime = new Date(now);
        targetTime.setHours(14, 0, 0, 0);
        
        // Tentukan waktu reset - apa yang akan lebih awal:
        // 1. 10 jam setelah jam 14:00
        const resetTimePlus10 = new Date(targetTime);
        resetTimePlus10.setHours(resetTimePlus10.getHours() + 10);
        
        // 2. Tengah malam
        const midnightReset = new Date(now);
        midnightReset.setDate(midnightReset.getDate() + 1);
        midnightReset.setHours(0, 0, 0, 0);
        
        // Pilih yang lebih awal dari kedua opsi reset
        const resetTime = resetTimePlus10 < midnightReset ? resetTimePlus10 : midnightReset;
        
        // Status dan mode timer
        let isOverdue = false;
        let isCountUp = false;
        let timeDifference;
        
        // Logika untuk menentukan mode timer
        if (now >= targetTime && now < resetTime) {
            // Jika saat ini sudah lewat jam 14:00 tapi belum mencapai waktu reset
            isOverdue = true;
            isCountUp = true;
            // Menghitung waktu yang telah berlalu sejak jam 14:00
            timeDifference = now - targetTime;
        } else if (now >= resetTime) {
            // Jika sudah melewati waktu reset, set target ke jam 14:00 hari berikutnya
            targetTime.setDate(targetTime.getDate() + 1);
            timeDifference = targetTime - now;
        } else {
            // Mode normal countdown ke jam 14:00 hari ini
            timeDifference = targetTime - now;
        }
        
        // Konversi selisih waktu ke jam, menit, detik
        const hours = Math.floor(timeDifference / (1000 * 60 * 60));
        const minutes = Math.floor((timeDifference % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((timeDifference % (1000 * 60)) / 1000);
        
        // Format waktu dengan leading zeros
        const formattedHours = hours.toString().padStart(2, '0');
        const formattedMinutes = minutes.toString().padStart(2, '0');
        const formattedSeconds = seconds.toString().padStart(2, '0');
        
        // Tambahkan tanda + jika menghitung naik
        const timeDisplay = isCountUp ? 
            `+${formattedHours}:${formattedMinutes}:${formattedSeconds}` : 
            `${formattedHours}:${formattedMinutes}:${formattedSeconds}`;
        
        // Tampilkan countdown/countup pada elemen HTML
        document.getElementById('countdownTimer').textContent = timeDisplay;
        
        // Dapatkan container untuk styling
        const countdownContainer = document.getElementById('countdownContainer');
        const overdueLabel = document.getElementById('overdueLabel');
        
        // Reset semua kelas styling
        countdownContainer.classList.remove(
            'countdown-normal', 
            'countdown-2hour', 
            'countdown-1hour', 
            'countdown-30min', 
            'countdown-10min',
            'countdown-overdue'
        );
        
        // Atur styling berdasarkan status timer
        if (isOverdue) {
            countdownContainer.classList.add('countdown-overdue');
            overdueLabel.classList.remove('d-none');
            overdueLabel.textContent = "Overtime";
        } else {
            overdueLabel.classList.add('d-none');
            
            // Tambahkan class styling sesuai dengan waktu tersisa
            if (timeDifference < 10 * 60 * 1000) { // Kurang dari 10 menit
                countdownContainer.classList.add('countdown-10min');
            } else if (timeDifference < 30 * 60 * 1000) { // Kurang dari 30 menit
                countdownContainer.classList.add('countdown-30min');
            } else if (timeDifference < 60 * 60 * 1000) { // Kurang dari 1 jam
                countdownContainer.classList.add('countdown-1hour');
            } else if (timeDifference < 2 * 60 * 60 * 1000) { // Kurang dari 2 jam
                countdownContainer.classList.add('countdown-2hour');
            } else {
                countdownContainer.classList.add('countdown-normal');
            }
        }
        
        // Panggil fungsi ini lagi setelah 1 detik
        setTimeout(updateCountdown, 1000);
    }
    
    // Mulai countdown timer
    updateCountdown();
});
    </script>
    @yield('scripts')
</body>
</html>
<!-- resources/views/layouts/admin.blade.php -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard')</title>
    
    <!-- Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --success-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --text-color: #495057;
            --sidebar-width: 240px;
            --sidebar-collapsed-width: 70px;
            --border-radius: 0.5rem;
            --box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            margin: 0;
            color: var(--text-color);
            overflow-x: hidden;
        }

        /* Sidebar styling */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: #fff;
            z-index: 1040;
            overflow-y: auto;
            transition: var(--transition);
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .sidebar.collapsed .nav-link span,
        .sidebar.collapsed .sidebar-header h5,
        .sidebar.collapsed .nav-category,
        .sidebar.collapsed .submenu-dash {
            display: none;
        }
        
        .sidebar.collapsed .nav-item.submenu {
            display: none;
        }

        .sidebar-content {
            padding: 0;
            height: 100%;
        }

        .sidebar-header {
            padding: 1.2rem 1.5rem;
            background-color: rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
        }

        .sidebar-menu {
            padding: 1rem 0;
        }

        .nav-item {
            width: 100%;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.6rem 1.5rem;
            color: rgba(255, 255, 255, 0.8) !important;
            transition: all 0.2s ease;
            font-size: 0.95rem;
            white-space: nowrap;
        }

        .nav-link:hover {
            color: #fff !important;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-link.active {
            color: #fff !important;
            background-color: rgba(255, 255, 255, 0.15);
            border-left: 3px solid #fff;
        }

        .nav-link i {
            margin-right: 10px;
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }

        .nav-item.category {
            padding: 0.8rem 1.5rem 0.4rem;
        }

        .nav-category {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
        }

        .nav-item.submenu .nav-link {
            padding-left: 3.2rem;
            font-size: 0.9rem;
            height: 2.2rem;
        }

        .submenu-dash {
            margin-right: 0.5rem;
            color: rgba(255, 255, 255, 0.5);
        }

        .logout-btn {
            width: 100%;
            text-align: left;
            border: none;
            background: transparent;
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        /* Main content styling */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            transition: var(--transition);
            min-height: 100vh;
        }

        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
        }

        /* Cards */
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transform: translateY(-3px);
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            padding: 1rem 1.25rem;
        }

        /* Alerts */
        .alert {
            border-radius: var(--border-radius);
            border-left: 4px solid;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-top: none;
            border-right: none;
            border-bottom: none;
        }

        .alert-success {
            background-color: rgba(25, 135, 84, 0.1);
            border-left-color: #198754;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border-left-color: #dc3545;
        }

        /* Buttons */
        .btn {
            border-radius: var(--border-radius);
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        /* Sidebar toggle button */
        .toggle-sidebar {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--primary-color);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: var(--transition);
        }

        .toggle-sidebar:hover {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--secondary-color);
        }
        
        /* Floating toggle button for mobile */
        .mobile-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1050;
            display: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        /* Mobile styles */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .mobile-toggle {
                display: flex;
            }
            
            .main-content.expanded {
                margin-left: 0;
            }
        }

        /* Dropdown styling */
        .dropdown-menu {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: var(--border-radius);
            background-color: #fff;
            margin-top: 0.5rem;
            padding: 0.5rem 0;
        }

        .dropdown-menu .dropdown-item {
            color: var(--text-color);
            padding: 0.5rem 1.5rem;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .dropdown-menu .dropdown-item:hover {
            color: var(--primary-color);
            background-color: rgba(67, 97, 238, 0.1);
        }

        /* Page heading */
        .page-heading {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .page-heading h1 {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .page-heading p {
            font-size: 0.9rem;
            color: var(--text-color);
        }

        /* Stats cards */
        .stat-card {
            background: #fff;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .stat-card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transform: translateY(-3px);
        }

        .stat-card .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .stat-card .stat-label {
            font-size: 0.9rem;
            color: var(--text-color);
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        /* Tables */
        .table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        .table th {
            font-weight: 600;
            padding: 1rem;
            background-color: rgba(0, 0, 0, 0.02);
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.01);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
    </style>
    @yield('styles')
</head>
<body>
    <!-- Sidebar -->
    <nav id="sidebar" class="sidebar">
        @include('partials.sidebar')
    </nav>

    <!-- Main Content -->
    <main id="content" class="main-content">
        <!-- Top navbar -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <button class="toggle-sidebar d-none d-md-block" id="toggleSidebar">
                <i class="bi bi-list"></i>
            </button>
            
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-circle me-1"></i>
                    <span>{{ Auth::user()->name ?? 'Admin' }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profil</a></li>
                <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Pengaturan</a></li>
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
            </div>
        </div>

        <!-- Alerts -->
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

        <!-- Page Content -->
        <div class="page-content">
            @yield('content')
        </div>
    </main>

    <!-- Mobile Toggle Button -->
    <button class="mobile-toggle" id="mobileToggle">
        <i class="bi bi-list"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            const toggleSidebar = document.getElementById('toggleSidebar');
            const mobileToggle = document.getElementById('mobileToggle');
            
            // Desktop sidebar toggle
            toggleSidebar?.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                content.classList.toggle('expanded');
            });
            
            // Mobile sidebar toggle
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
            
            // Active menu highlighting
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
        });
    </script>
    @yield('scripts')
</body>
</html>
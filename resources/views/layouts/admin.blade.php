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
    <style>
        :root {
            /* Modern Color Palette */
            --primary-color: #4361ee;
            --primary-light: #4cc9f0;
            --primary-dark: #3a56d4;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --success-color: #0cce6b;
            --danger-color: #e5383b;
            --warning-color: #ff9e00;
            --info-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --text-color: #495057;
            --text-light: #6c757d;
            --bg-color: #f5f7fb;
            
            /* Sidebar Dimensions */
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 70px;
            
            /* Modern UI Elements */
            --border-radius-sm: 6px;
            --border-radius-md: 10px;
            --border-radius-lg: 16px;
            --border-radius-xl: 24px;
            
            /* Shadows */
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 5px 15px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.12);
            --shadow-inner: inset 0 2px 4px rgba(0, 0, 0, 0.06);
            
            /* Transitions */
            --transition-fast: all 0.2s ease;
            --transition-normal: all 0.3s ease;
            --transition-slow: all 0.5s ease;
        }

        body {
            font-family: 'Inter', 'Poppins', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            color: var(--text-color);
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* Modern Glassmorphism Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(145deg, var(--primary-color), var(--secondary-color));
            color: #fff;
            z-index: 1040;
            overflow-y: auto;
            transition: var(--transition-normal);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .sidebar.collapsed .nav-link span,
        .sidebar.collapsed .sidebar-header h5,
        .sidebar.collapsed .nav-category,
        .sidebar.collapsed .submenu-dash,
        .sidebar.collapsed .sidebar-title {
            display: none;
        }
        
        .sidebar.collapsed .nav-item.submenu {
            display: none;
        }

        .sidebar-content {
            padding: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 1.4rem 1.5rem;
            background-color: rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
        }

        .sidebar-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .sidebar-menu {
            padding: 1rem 0;
            flex-grow: 1;
        }

        .nav-item {
            width: 100%;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: rgba(255, 255, 255, 0.85) !important;
            transition: var(--transition-fast);
            font-size: 0.95rem;
            white-space: nowrap;
            border-radius: 0;
            position: relative;
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .nav-link:hover {
            color: #fff !important;
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(3px);
        }

        .nav-link.active {
            color: #fff !important;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.15), transparent);
            border-left: 3px solid #fff;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .nav-link i {
            margin-right: 10px;
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
            transition: var(--transition-fast);
        }
        
        .nav-link:hover i {
            transform: scale(1.1);
        }

        .nav-item.category {
            padding: 1rem 1.5rem 0.4rem;
        }

        .nav-category {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 1.2px;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            margin-bottom: 0.3rem;
        }

        .nav-item.submenu .nav-link {
            padding-left: 3.2rem;
            font-size: 0.9rem;
            height: 2.4rem;
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
            color: rgba(255, 255, 255, 0.85);
            transition: var(--transition-fast);
            padding: 0.75rem 1.5rem;
        }
        
        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .logout-btn i {
            margin-right: 10px;
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }

        /* Main Content with Modern UI */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            transition: var(--transition-normal);
            min-height: 100vh;
        }

        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
        }

        /* Modern Floating Cards */
        .card {
            border: none;
            border-radius: var(--border-radius-md);
            box-shadow: var(--shadow-sm);
            transition: var(--transition-normal);
            overflow: hidden;
            margin-bottom: 1.5rem;
            background-color: white;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-3px);
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            padding: 1.2rem 1.5rem;
            display: flex;
            align-items: center;
            letter-spacing: 0.3px;
        }
        
        .card-header i {
            margin-right: 0.5rem;
            color: var(--primary-color);
        }

        /* Modern Alert Design */
        .alert {
            border-radius: var(--border-radius-md);
            border-left: 4px solid;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            border-top: none;
            border-right: none;
            border-bottom: none;
            display: flex;
            align-items: center;
            box-shadow: var(--shadow-sm);
        }

        .alert-success {
            background-color: rgba(12, 206, 107, 0.1);
            border-left-color: var(--success-color);
            color: #0a8c50;
        }

        .alert-danger {
            background-color: rgba(229, 56, 59, 0.1);
            border-left-color: var(--danger-color);
            color: #b52a2c;
        }
        
        .alert-warning {
            background-color: rgba(255, 158, 0, 0.1);
            border-left-color: var(--warning-color);
            color: #cc7e00;
        }
        
        .alert-info {
            background-color: rgba(76, 201, 240, 0.1);
            border-left-color: var(--info-color);
            color: #039ccc;
        }

        /* Modern Button Styles */
        .btn {
            border-radius: var(--border-radius-sm);
            padding: 0.6rem 1.2rem;
            font-weight: 500;
            transition: var(--transition-normal);
            letter-spacing: 0.3px;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.2);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-light {
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.08);
            color: var(--text-color);
            box-shadow: var(--shadow-sm);
        }
        
        .btn-light:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Sidebar Toggle Button with Modern Style */
        .toggle-sidebar {
            background: white;
            border: none;
            font-size: 1.5rem;
            color: var(--primary-color);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: var(--transition-normal);
            box-shadow: var(--shadow-sm);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-sidebar:hover {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-dark);
            transform: rotate(180deg);
            box-shadow: var(--shadow-md);
        }
        
        /* Floating toggle button for mobile */
        .mobile-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1050;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-md);
            transition: var(--transition-normal);
            border: none;
            font-size: 1.25rem;
        }
        
        .mobile-toggle:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow-lg);
        }
        
        .mobile-toggle:active {
            transform: scale(0.95);
        }

        /* Modern Responsive Design */
        @media (max-width: 992px) {
            .main-content {
                padding: 1.5rem;
            }
        }
        
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
                padding: 1.2rem;
            }
            
            .mobile-toggle {
                display: flex;
            }
            
            .main-content.expanded {
                margin-left: 0;
            }
        }

        /* Modern Dropdown Menu with Animation */
        .dropdown-menu {
            border: none;
            box-shadow: var(--shadow-md);
            border-radius: var(--border-radius-md);
            background-color: #fff;
            margin-top: 0.5rem;
            padding: 0.75rem 0;
            border: 1px solid rgba(0, 0, 0, 0.03);
            animation: dropdownFade 0.2s ease-out;
        }
        
        @keyframes dropdownFade {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .dropdown-menu .dropdown-item {
            color: var(--text-color);
            padding: 0.6rem 1.5rem;
            font-size: 0.9rem;
            transition: var(--transition-fast);
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .dropdown-menu .dropdown-item:hover {
            color: var(--primary-color);
            background-color: rgba(67, 97, 238, 0.05);
            transform: translateX(3px);
        }
        
        .dropdown-menu .dropdown-item i {
            transition: var(--transition-fast);
        }
        
        .dropdown-menu .dropdown-item:hover i {
            transform: scale(1.1);
        }

        /* Page Heading with Modern Typography */
        .page-heading {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .page-heading h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            letter-spacing: 0.3px;
        }

        .page-heading p {
            font-size: 0.95rem;
            color: var(--text-light);
            line-height: 1.6;
        }

        /* Modern Stat Cards with Gradient */
        .stat-card {
            background: white;
            border-radius: var(--border-radius-md);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition-normal);
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-3px);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary-color), var(--primary-light));
        }

        .stat-card .stat-icon {
            font-size: 2.25rem;
            margin-bottom: 1.2rem;
            color: var(--primary-color);
            opacity: 0.9;
        }
        
        .stat-card.danger-card::before {
            background: linear-gradient(to bottom, var(--danger-color), #ff6b6d);
        }
        
        .stat-card.danger-card .stat-icon {
            color: var(--danger-color);
        }
        
        .stat-card.success-card::before {
            background: linear-gradient(to bottom, var(--success-color), #5dffaa);
        }
        
        .stat-card.success-card .stat-icon {
            color: var(--success-color);
        }
        
        .stat-card.warning-card::before {
            background: linear-gradient(to bottom, var(--warning-color), #ffce6d);
        }
        
        .stat-card.warning-card .stat-icon {
            color: var(--warning-color);
        }

        .stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .stat-card .stat-label {
            font-size: 0.95rem;
            color: var(--text-light);
            font-weight: 500;
        }
        
        .stat-card .stat-change {
            display: inline-flex;
            align-items: center;
            margin-top: 0.5rem;
            padding: 0.25rem 0.6rem;
            font-size: 0.8rem;
            border-radius: 50px;
            font-weight: 600;
        }
        
        .stat-card .stat-change.positive {
            background-color: rgba(12, 206, 107, 0.1);
            color: var(--success-color);
        }
        
        .stat-card .stat-change.negative {
            background-color: rgba(229, 56, 59, 0.1);
            color: var(--danger-color);
        }
        
        .stat-card .stat-change i {
            margin-right: 0.25rem;
            font-size: 0.75rem;
        }

        /* Modern Scrollbar Design */
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

        /* Modern Table Design */
        .table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        .table thead th {
            font-weight: 600;
            padding: 1rem 1.25rem;
            background-color: rgba(0, 0, 0, 0.02);
            color: var(--text-color);
            border-bottom: 2px solid rgba(0, 0, 0, 0.05);
            vertical-align: middle;
            letter-spacing: 0.3px;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .table thead th:first-child {
            border-top-left-radius: var(--border-radius-sm);
        }
        
        .table thead th:last-child {
            border-top-right-radius: var(--border-radius-sm);
        }

        .table tbody td {
            padding: 1rem 1.25rem;
            vertical-align: middle;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-size: 0.95rem;
        }
        
        .table tbody tr:last-child td:first-child {
            border-bottom-left-radius: var(--border-radius-sm);
        }
        
        .table tbody tr:last-child td:last-child {
            border-bottom-right-radius: var(--border-radius-sm);
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .table-hover tbody tr {
            transition: var(--transition-fast);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
            transform: scale(1.01);
            box-shadow: var(--shadow-sm);
        }
        
        /* Badge Design */
        .badge {
            padding: 0.35em 0.65em;
            font-weight: 600;
            font-size: 0.75rem;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-primary {
            background-color: rgba(67, 97, 238, 0.15);
            color: var(--primary-color);
        }
        
        .badge-success {
            background-color: rgba(12, 206, 107, 0.15);
            color: var(--success-color);
        }
        
        .badge-danger {
            background-color: rgba(229, 56, 59, 0.15);
            color: var(--danger-color);
        }
        
        .badge-warning {
            background-color: rgba(255, 158, 0, 0.15);
            color: var(--warning-color);
        }
        
        /* Page Transition Animation */
        .page-content {
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Top navbar user dropdown */
        .user-dropdown-toggle {
            display: flex;
            align-items: center;
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: var(--border-radius-md);
            padding: 0.6rem 1rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition-fast);
        }
        
        .user-dropdown-toggle:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        
        .user-dropdown-toggle .user-info {
            margin-right: 0.5rem;
        }
        
        .user-dropdown-toggle .user-name {
            font-weight: 600;
            color: var(--text-color);
        }
        
        .user-dropdown-toggle .user-role {
            font-size: 0.8rem;
            color: var(--text-light);
        }
        
        .user-dropdown-toggle .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        
    </style>
    @yield('styles')
</head>
<body>
    <!-- Sidebar dengan Glassmorphism Effect -->
    <nav id="sidebar" class="sidebar">
        <div class="sidebar-content">
            <div class="sidebar-header">
                <div class="sidebar-title">ADMIN DASHBOARD</div>
            </div>
            
            <div class="sidebar-menu">
                @include('partials.sidebar')
            </div>
        </div>
    </nav>

    <!-- Main Content dengan Modern UI -->
    <main id="content" class="main-content">
        <!-- Top navbar dengan modern styling -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <button class="toggle-sidebar d-none d-md-block me-3" id="toggleSidebar">
                    <i class="bi bi-list"></i>
                </button>
                
                <div class="page-title d-none d-md-block">
                    <h4 class="m-0 fw-bold">@yield('page-title', 'Dashboard')</h4>
                </div>
            </div>
            
            <div class="dropdown">
                <button class="user-dropdown-toggle dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="d-none d-md-block">
                        <div class="user-info">
                            <div class="user-name">{{ Auth::user()->name ?? 'Admin' }}</div>
                            <div class="user-role">Administrator</div>
                        </div>
                    </div>
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
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
        });
    </script>
    @yield('scripts')
</body>
</html>
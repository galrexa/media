<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard')</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f8f9fa;
            margin: 0;
        }

        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            color: #fff;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            transition: left 0.3s ease;
        }

        .sidebar-sticky {
            position: sticky;
            top: 0;
            height: 100vh;
            padding-top: 1rem;
            overflow-y: auto;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.75);
            padding: 0.8rem 1rem;
            font-size: 0.95rem;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #007bff;
        }

        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(0, 123, 255, 0.25);
            border-left-color: #007bff;
        }

        .sidebar .nav-link i {
            margin-right: 0.5rem;
            width: 24px;
            text-align: center;
        }

        .sidebar-heading {
            padding: 0.75rem 1rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.5);
        }

        .navbar-brand {
            padding: 1rem;
            font-size: 1.2rem;
            font-weight: bold;
            background-color: #007bff;
            color: white;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .main-content {
            padding: 1.5rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -250px;
                width: 250px;
                z-index: 1000;
            }

            .sidebar.show {
                left: 0;
            }

            .main-content {
                margin-left: 0 !important;
            }

            .navbar {
                z-index: 999;
            }
        }

        .dropdown-menu {
            position: relative !important; /* Mengubah dari position: absolute */
            border: none; /* Menghilangkan border popup */
            box-shadow: none; /* Menghilangkan efek bayangan */
            background-color: inherit; /* Warna latar mengikuti parent */
            padding-left: 1.5rem; /* Memberi indentasi agar terlihat hierarkis */
        }

        .nav-item.dropdown:hover .dropdown-menu {
            display: block; /* Menampilkan saat hover */
        }

        .nav.flex-column .nav-link {
            padding-left: 2rem; /* Memberi indentasi */
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="sidebar-sticky">
                    <div class="navbar-brand">
                        <span>Admin</span>
                        <button class="d-md-none btn btn-link text-white" id="sidebarToggle">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <ul class="nav flex-column mt-3">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ route('home') }}">
                                <i class="bi bi-house"></i> Beranda
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('dashboard/admin') ? 'active' : '' }}" href="{{ route('dashboard.admin') }}">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>

                        <div class="sidebar-heading">Konten</div>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('isu*') ? 'active' : '' }}" href="{{ route('isu.index') }}">
                                <i class="bi bi-file-text"></i> Manajemen Isu
                            </a>
                        </li>
                        <!-- <li class="nav-item">
                            <a class="nav-link {{ request()->is('images/upload') ? 'active' : '' }}" href="{{ route('images.create') }}">
                                <i class="bi bi-image"></i> Upload Gambar Harian
                            </a>
                        </li> -->
                        <li class="nav-item">
                            <a class="nav-link text-white" href="#">
                                <i class="bi bi-images"></i> Manajemen Gambar
                            </a>
                            <ul class="nav flex-column ps-3">
                                <li class="nav-item">
                                    <a class="nav-link text-white" href="{{ route('images.index') }}">Daftar Gambar</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-white" href="{{ route('images.create') }}">Upload Gambar Baru</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-white" href="{{ route('images.edit') }}">Edit Gambar Hari Ini</a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="bi bi-graph-up"></i> Trending
                            </a>
                            <ul class="nav flex-column ps-3">
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ route('trending.index') }}">Daftar Trending</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-white" href="{{ route('trending.create') }}">Tambah Trending</a>
                                </li>
                                <!-- <li class="nav-item">
                                    <a class="nav-link text-white" href="{{ route('trending.edit') }}">Edit Trending</a>
                                </li>  -->
                            </ul>
                        </li>

                        @if(Auth::user()->isAdmin())
                            <div class="sidebar-heading">Administrasi</div>
                            <li class="nav-item">
                                <a class="nav-link" href="#">
                                    <i class="bi bi-people"></i> Manajemen Pengguna
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#">
                                    <i class="bi bi-gear"></i> Pengaturan
                                </a>
                            </li>
                        @endif

                        <div class="sidebar-heading">Akun</div>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="bi bi-person-circle"></i> Profil
                            </a>
                        </li>
                        <li class="nav-item">
                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="nav-link border-0 bg-transparent w-100 text-start">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <!-- Mobile Navbar -->
                <nav class="navbar navbar-expand-md d-md-none navbar-light bg-light mb-4">
                    <div class="container-fluid">
                        <a class="navbar-brand" href="#">Admin</a>
                        <button class="navbar-toggler" type="button" id="mobileMenuToggle">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                    </div>
                </nav>

                <!-- Alerts -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const sidebarToggle = document.getElementById('sidebarToggle');

            mobileMenuToggle?.addEventListener('click', () => sidebar.classList.toggle('show'));
            sidebarToggle?.addEventListener('click', () => sidebar.classList.remove('show'));
        });
    </script>
    @yield('scripts')
</body>
</html>
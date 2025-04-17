<!-- resources/views/partials/sidebar.blade.php -->
<div class="sidebar-content">
    <!-- Header -->
    <div class="sidebar-header">
        <h5 class="sidebar-title">Admin</h5>
    </div>
    
    <!-- Menu Navigation -->
    <div class="sidebar-menu">
        <ul class="nav flex-column">
            <!-- Menu Beranda -->
            <li class="nav-item">
                <a class="nav-link {{ request()->is('home') ? 'active' : '' }}" href="{{ route('home') }}">
                    <i class="bi bi-house-door"></i>
                    <span>Beranda</span>
                </a>
            </li>
            
            <!-- Menu Dashboard -->
            <li class="nav-item">
                <a class="nav-link {{ request()->is('dashboard/admin') ? 'active' : '' }}" href="{{ route('dashboard.admin') }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <!-- Kategori Konten -->
            <li class="nav-item category">
                <span class="nav-category">KONTEN</span>
            </li>
            
            <!-- Manajemen Isu -->
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('isu.*') ? 'active' : '' }}" href="#">
                    <i class="bi bi-file-text"></i>
                    <span>Manajemen Isu</span>
                </a>
            </li>
            
            <!-- Submenu Isu -->
            <li class="nav-item submenu">
                <a class="nav-link {{ request()->routeIs('isu.index') ? 'active' : '' }}" href="{{ route('isu.index') }}">
                    
                    <span>Daftar Isu</span>
                </a>
            </li>
            <li class="nav-item submenu">
                <a class="nav-link {{ request()->routeIs('isu.create') ? 'active' : '' }}" href="{{ route('isu.create') }}">
                    
                    <span>Tambah Isu</span>
                </a>
            </li>
            
            <!-- Manajemen Dokumen -->
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('documents.*') ? 'active' : '' }}" href="#">
                    <i class="bi bi-images"></i>
                    <span>Manajemen Dokumen</span>
                </a>
            </li>
            
            <!-- Submenu Dokumen -->
            <li class="nav-item submenu">
                <a class="nav-link {{ request()->routeIs('documents.index') ? 'active' : '' }}" href="{{ route('documents.index') }}">
                    
                    <span>Daftar Dokumen</span>
                </a>
            </li>
            <li class="nav-item submenu">
                <a class="nav-link {{ request()->routeIs('documents.create') ? 'active' : '' }}" href="{{ route('documents.create') }}">
                    
                    <span>Upload Dokumen</span>
                </a>
            </li>
            
            <!-- Trending -->
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('trending.*') ? 'active' : '' }}" href="#">
                    <i class="bi bi-graph-up"></i>
                    <span>Trending</span>
                </a>
            </li>
            
            <!-- Submenu Trending -->
            <li class="nav-item submenu">
                <a class="nav-link {{ request()->routeIs('trending.selected') ? 'active' : '' }}" href="{{ route('trending.selected') }}">
                    
                    <span>Daftar Trending</span>
                </a>
            </li>
            <li class="nav-item submenu">
                <a class="nav-link {{ request()->routeIs('trending.manageGoogleSelected') ? 'active' : '' }}" href="{{ route('trending.manageGoogleSelected') }}">
                    
                    <span>Trending Google</span>
                </a>
            </li>
            <li class="nav-item submenu">
                <a class="nav-link {{ request()->routeIs('trending.manageXSelected') ? 'active' : '' }}" href="{{ route('trending.manageXSelected') }}">
                    
                    <span>Trending X</span>
                </a>
            </li>
            
            <!-- Kategori Administrasi -->
            @if(Auth::user()->isAdmin())
            <li class="nav-item category">
                <span class="nav-category">ADMINISTRASI</span>
            </li>
            
            <!-- Manajemen User -->
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                    <i class="bi bi-people"></i>
                    <span>Manajemen User</span>
                </a>
            </li>
            
            <!-- Pengaturan -->
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="bi bi-gear"></i>
                    <span>Pengaturan</span>
                </a>
            </li>
            @endif
            
            <!-- Kategori Akun -->
            <li class="nav-item category">
                <span class="nav-category">AKUN</span>
            </li>
            
            <!-- Profil -->
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="bi bi-person-circle"></i>
                    <span>Profil</span>
                </a>
            </li>
            
            <!-- Logout -->
            <li class="nav-item">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="nav-link logout-btn">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </li>
        </ul>
    </div>
</div>
<!-- resources/views/partials/sidebar.blade.php -->
<div class="sidebar-content">
    <!-- Header dengan judul yang dinamis berdasarkan role -->
    <div class="sidebar-header">
        <h5 class="sidebar-title">
            @if(Auth::user()->isAdmin())
                Administrator
            @elseif(Auth::user()->isEditor())
                Editor
            @elseif(Auth::user()->hasRole('verifikator1'))
                Verifikator 1
            @elseif(Auth::user()->hasRole('verifikator2'))
                Verifikator 2
            @else
                User
            @endif
        </h5>
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
            @if(Auth::user()->isAdmin() || Auth::user()->isEditor())
            <li class="nav-item">
                <a class="nav-link {{ request()->is('dashboard/admin') ? 'active' : '' }}" href="{{ route('dashboard.admin') }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            @endif

            <!-- Kategori Konten -->
            <li class="nav-item category">
                <span class="nav-category">KONTEN</span>
            </li>

            <!-- Manajemen Isu -->
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('isu.index') ? 'active' : '' }}" href="{{ route('isu.index') }}">
                    <i class="bi bi-file-text"></i>
                    <span>Manajemen Isu</span>
                </a>
            </li>

            <!-- Submenu Isu dengan dropdown -->
            <li class="nav-item submenu">
                <a class="nav-link {{ request()->routeIs('isu.index') && !request()->has('filter_status') ? 'active' : '' }}" href="{{ route('isu.index') }}">
                    <span>Daftar Isu</span>
                    @if(Auth::user()->isAdmin() && isset($pendingIsuCount) && $pendingIsuCount > 0)
                        <span class="badge bg-danger rounded-pill ms-auto">{{ $pendingIsuCount }}</span>
                    @endif
                </a>

                <!-- Sub-sub menu untuk filter -->
                <div class="{{ request()->routeIs('isu.index') ? 'show' : '' }}" id="filterSubmenu">
                    <ul class="nav flex-column sub-submenu">

                        <!-- Sub-sub menu Draft - Hanya untuk Editor -->
                        @if(Auth::user()->isAdmin() || Auth::user()->isEditor())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('isu.index') && request()->input('filter_status') == 'draft' ? 'active' : '' }}" href="{{ route('isu.index', ['filter_status' => 'draft']) }}">
                                <span>Draft</span>
                                @if(isset($draftIsuCount) && $draftIsuCount > 0)
                                    <span class="badge bg-secondary rounded-pill ms-auto">{{ $draftIsuCount }}</span>
                                @endif
                            </a>
                        </li>
                        @endif

                        <!-- Sub-sub menu Verifikasi 1 - Hanya untuk Verifikator 1 -->
                        @if(Auth::user()->hasRole('verifikator1') || Auth::user()->isAdmin())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('isu.index') && request()->input('filter_status') == 'verifikasi1' ? 'active' : '' }}" href="{{ route('isu.index', ['filter_status' => 'verifikasi1']) }}">
                                <span>Verifikasi 1</span>
                                @if(isset($verifikasi1IsuCount) && $verifikasi1IsuCount > 0)
                                    <span class="badge bg-primary rounded-pill ms-auto">{{ $verifikasi1IsuCount }}</span>
                                @endif
                            </a>
                        </li>
                        @endif

                        <!-- Sub-sub menu Verifikasi 2 - Hanya untuk Verifikator 2 -->
                        @if(Auth::user()->hasRole('verifikator2') || Auth::user()->isAdmin())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('isu.index') && request()->input('filter_status') == 'verifikasi2' ? 'active' : '' }}" href="{{ route('isu.index', ['filter_status' => 'verifikasi2']) }}">
                                <span>Verifikasi 2</span>
                                @if(isset($verifikasi2IsuCount) && $verifikasi2IsuCount > 0)
                                    <span class="badge bg-primary rounded-pill ms-auto">{{ $verifikasi2IsuCount }}</span>
                                @endif
                            </a>
                        </li>
                        @endif

                        <!-- Sub-sub menu Ditolak - Untuk Editor dan Admin -->
                        @if(Auth::user()->isEditor() || Auth::user()->isAdmin())
                        <li class="nav-item">
                            <a id="rejected-menu-link" class="nav-link {{ request()->routeIs('isu.index') && request()->input('filter_status') == 'rejected' ? 'active' : '' }}"
                            href="{{ route('isu.index', ['filter_status' => 'rejected']) }}"
                            onclick="hideRejectedBadge()">
                                <span>Ditolak</span>
                                @if(Auth::user()->isAdmin())
                                    {{-- Admin sees all rejected issues --}}
                                    @if(isset($rejectedIsuCount) && $rejectedIsuCount > 0)
                                        <span id="rejected-badge" class="badge bg-danger rounded-pill ms-auto">{{ $rejectedIsuCount }}</span>
                                    @endif
                                @elseif(Auth::user()->isEditor())
                                    {{-- Editor only sees their own rejected issues --}}
                                    @if(isset($rejectedIsuCount) && $rejectedIsuCount > 0)
                                        <span id="rejected-badge" class="badge bg-danger rounded-pill ms-auto">{{ $rejectedIsuCount }}</span>
                                    @endif
                                @endif
                            </a>
                        </li>
                        @endif

                    </ul>
                </div>
            </li>

            @if(Auth::user()->isAdmin() || Auth::user()->isEditor())
            <li class="nav-item submenu">
                <a class="nav-link {{ request()->routeIs('isu.create') ? 'active' : '' }}" href="{{ route('isu.create') }}">
                    <span>Tambah Isu</span>
                </a>
            </li>
            @endif

            <!-- Manajemen Dokumen -->
            @if(Auth::user()->isAdmin() || Auth::user()->isEditor())
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('documents.index') ? 'active' : '' }}" href="{{ route('documents.index') }}">
                    <i class="bi bi-images"></i>
                    <span>Manajemen Dokumen</span>
                    @if(isset($pendingDocumentCount) && $pendingDocumentCount > 0)
                        <span class="badge bg-warning rounded-pill ms-auto">{{ $pendingDocumentCount }}</span>
                    @endif
                </a>
            </li>

            <!-- Submenu Dokumen -->
            <li class="nav-item submenu">
                <a class="nav-link {{ request()->routeIs('documents.index') ? 'active' : '' }}" href="{{ route('documents.index') }}">
                    <span>Daftar Dokumen</span>
                    @if(isset($pendingDocumentCount) && $pendingDocumentCount > 0)
                        <span class="badge bg-warning rounded-pill ms-auto">{{ $pendingDocumentCount }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item submenu">
                <a class="nav-link {{ request()->routeIs('documents.create') ? 'active' : '' }}" href="{{ route('documents.create') }}">
                    <span>Upload Dokumen</span>
                </a>
            </li>

            <!-- Trending -->
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('trending.selected') ? 'active' : '' }}" href="{{ route('trending.selected') }}">
                    <i class="bi bi-graph-up"></i>
                    <span>Trending</span>
                    @if(isset($pendingTrendingCount) && $pendingTrendingCount > 0)
                        <span class="badge bg-info rounded-pill ms-auto">{{ $pendingTrendingCount }}</span>
                    @endif
                </a>
            </li>

            <!-- Submenu Trending -->
            <li class="nav-item submenu">
                <a class="nav-link {{ request()->routeIs('trending.selected') ? 'active' : '' }}" href="{{ route('trending.selected') }}">
                    <span>Daftar Trending</span>
                    @if(isset($pendingTrendingCount) && $pendingTrendingCount > 0)
                        <span class="badge bg-info rounded-pill ms-auto">{{ $pendingTrendingCount }}</span>
                    @endif
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

            <li class="nav-item submenu">
                <a href="{{ route('trending.manual.create') }}" class="nav-link {{ Request::routeIs('trending.manual.create') ? 'active' : '' }}">
                    <span>Tambah Trending Manual</span>
                </a>
            </li>
            @endif

            <!-- Kategori Pengaturan -->
            @if(Auth::user()->isAdmin() || Auth::user()->isEditor() || Auth::user()->hasRole('verifikator1') || Auth::user()->hasRole('verifikator2'))
            <li class="nav-item category">
                <span class="nav-category">PENGATURAN</span>
            </li>

            <!-- Analytics -->
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('analytics.*') ? 'active' : '' }}" href="{{ route('analytics.index') }}">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span>Analytics</span>
                    @if(isset($analytics) && $analytics['today_visitors'] > 0)
                        <span class="badge bg-info rounded-pill ms-auto">{{ $analytics['today_visitors'] }}</span>
                    @endif
                </a>
            </li>
            @endif

            <!-- Manajemen User -->
            @if(Auth::user()->isAdmin())
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                    <i class="bi bi-people"></i>
                    <span>Manajemen User</span>
                </a>
            </li>
            @endif

            <!-- Pengaturan Aplikasi-->
            @if(Auth::user()->isAdmin() || Auth::user()->isEditor())
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
                    <i class="bi bi-gear"></i>
                    <span>Pengaturan Aplikasi</span>
                </a>
            </li>
            @endif

            <!-- Kategori Akun -->
            <li class="nav-item category">
                <span class="nav-category">AKUN</span>
            </li>

            <!-- Profil -->
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.index') }}">
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
<!-- CSRF Token for JavaScript -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- CSS untuk styling badge dan menu -->
<style>
.submenu-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.sub-submenu {
    padding-left: 1.5rem;
}

.sub-submenu .nav-link {
    padding-left: 1rem;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* Badge styling */
.badge {
    font-size: 0.7rem;
    font-weight: 500;
}

/* Highlight badge dengan animasi pulse jika nilai lebih dari 0 */
.badge.bg-danger, .badge.bg-warning, .badge.bg-primary {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(255, 82, 82, 0.7);
    }
    70% {
        box-shadow: 0 0 0 5px rgba(255, 82, 82, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(255, 82, 82, 0);
    }
}

/* Animasi ikon dropdown */
.submenu-toggle[aria-expanded="true"] .bi-chevron-down {
    transform: rotate(180deg);
    transition: transform 0.3s;
}

.submenu-toggle[aria-expanded="false"] .bi-chevron-down {
    transform: rotate(0deg);
    transition: transform 0.3s;
}

/* Tambahan untuk menyembunyikan badge */
.hidden-badge {
    display: none !important;
}
</style>

<!-- Script untuk toggle dropdown dan reset badge -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi collapse Bootstrap jika belum diinisialisasi
    var submenuToggles = document.querySelectorAll('.submenu-toggle');
    submenuToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            var target = this.getAttribute('data-bs-target');
            var collapse = new bootstrap.Collapse(document.querySelector(target), {
                toggle: true
            });
        });
    });
        function hideRejectedBadge() {
        // Sembunyikan badge secara visual langsung saat diklik
        const badge = document.getElementById('rejected-badge');
        if (badge) {
            badge.style.display = 'none';
        }

        // Kirim AJAX request ke server untuk menyimpan status
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
});
</script>

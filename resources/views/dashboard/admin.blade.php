@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('page_title', 'Dashboard')

@section('styles')
<style>
    /* Root variables for consistency and theming */
    :root {
        --primary: #4e73df;
        --success: #1cc88a;
        --info: #36b9cc;
        --warning: #f6c23e;
        --secondary: #858796;
        --background: #f8f9fc;
        --text-primary: #2c3035;
        --text-secondary: #6c757d;
        --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        --card-hover-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        --transition: all 0.3s ease;
        --glass-bg: rgba(255, 255, 255, 0.15);
        --glass-border: rgba(255, 255, 255, 0.2);
    }

    /* General reset and typography */
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background-color: var(--background);
        color: var(--text-primary);
        line-height: 1.6;
    }

    /* Card styling */
    .stat-card {
        border-radius: 1rem;
        box-shadow: var(--card-shadow);
        transition: var(--transition);
        margin-bottom: 1.75rem;
        overflow: hidden;
        position: relative;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--card-hover-shadow);
    }

    .stat-card .card-body {
        padding: 1.5rem;
    }

    .stat-card .card-border-left {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        width: 6px;
    }

    .stat-card .stat-icon {
        font-size: 2.25rem;
        opacity: 0.3;
        transition: var(--transition);
    }

    .stat-card:hover .stat-icon {
        opacity: 0.5;
        transform: scale(1.05);
    }

    .stat-card .stat-label {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-secondary);
        margin-bottom: 0.5rem;
    }

    .stat-card .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    /* Specific gradient backgrounds for stat cards */
    .stat-card.total-isu {
        background: linear-gradient(135deg, rgba(78, 115, 223, 0.2) 0%, rgba(78, 115, 223, 0.1) 100%);
    }

    .stat-card.isu-strategis {
        background: linear-gradient(135deg, rgba(28, 200, 138, 0.2) 0%, rgba(28, 200, 138, 0.1) 100%);
    }

    .stat-card.pengguna {
        background: linear-gradient(135deg, rgba(54, 185, 204, 0.2) 0%, rgba(54, 185, 204, 0.1) 100%);
    }

    /* Action cards */
    .action-card {
        border-radius: 1rem;
        background: #fff;
        box-shadow: var(--card-shadow);
        transition: var(--transition);
        margin-bottom: 1.75rem;
        overflow: hidden;
    }

    .action-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--card-hover-shadow);
    }

    .card-header {
        background: #fff;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        padding: 1rem 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-header-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--primary);
    }

    /* Buttons */
    .action-btn, .main-action-btn {
        border-radius: 0.75rem;
        text-decoration: none;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
        font-weight: 600;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary) 100%);
        color: #fff;
    }

    .action-btn:hover, .main-action-btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--card-hover-shadow);
        background: linear-gradient(135deg, #3b5cb8 0%, #3b5cb8 100%);
    }

    .action-btn-icon {
        font-size: 1.75rem;
        margin-bottom: 0.5rem;
    }

    .main-action-btn {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .main-action-icon {
        font-size: 2rem;
        margin-right: 0.75rem;
    }

    .main-action-text {
        font-size: 1.125rem;
    }

    /* Recent items */
    .recent-list {
        padding: 0;
        margin: 0;
        list-style: none;
    }

    .recent-item {
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
        border: 1px solid rgba(0, 0, 0, 0.05);
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: space-between;
        text-decoration: none;
        color: var(--text-primary);
    }

    .recent-item:hover {
        background: rgba(78, 115, 223, 0.05);
        border-color: rgba(78, 115, 223, 0.2);
        transform: translateX(2px);
    }

    .recent-item-title {
        font-size: 0.875rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .recent-item-meta {
        font-size: 0.75rem;
        color: var(--text-secondary);
    }

    .status-badge {
        font-size: 0.7rem;
        padding: 0.3rem 0.6rem;
        border-radius: 1rem;
        font-weight: 500;
    }

    .status-badge-strategis {
        background: rgba(28, 200, 138, 0.15);
        color: var(--success);
    }

    .status-badge-non-strategis {
        background: rgba(108, 117, 125, 0.15);
        color: var(--secondary);
    }

    .recent-item-date {
        font-size: 0.75rem;
        color: var(--text-secondary);
    }

    /* Dashboard header */
    .dashboard-header {
        padding: 2rem 0;
        margin-bottom: 2rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .dashboard-title {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    .dashboard-welcome {
        font-size: 1rem;
        color: var(--text-secondary);
    }

    /* Dark mode */
    .dark-mode {
        --background: #1a202c;
        --text-primary: #e2e8f0;
        --text-secondary: #a0aec0;
        --glass-bg: rgba(45, 55, 72, 0.15);
        --glass-border: rgba(255, 255, 255, 0.1);
    }

    .dark-mode .stat-card,
    .dark-mode .action-card,
    .dark-mode .card-header {
        background: #2d3748;
    }

    .dark-mode .stat-card.total-isu {
        background: linear-gradient(135deg, rgba(78, 115, 223, 0.15) 0%, rgba(78, 115, 223, 0.05) 100%);
    }

    .dark-mode .stat-card.isu-strategis {
        background: linear-gradient(135deg, rgba(28, 200, 138, 0.15) 0%, rgba(28, 200, 138, 0.05) 100%);
    }

    .dark-mode .stat-card.pengguna {
        background: linear-gradient(135deg, rgba(54, 185, 204, 0.15) 0%, rgba(54, 185, 204, 0.05) 100%);
    }

    .dark-mode .recent-item:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .stat-card .stat-value {
            font-size: 1.5rem;
        }

        .dashboard-title {
            font-size: 1.5rem;
        }

        .main-action-btn {
            flex-direction: column;
            text-align: center;
        }

        .main-action-icon {
            margin-right: 0;
            margin-bottom: 0.5rem;
        }
    }
</style>
@endsection

@section('content')

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card stat-card total-isu">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-label" style="color: var(--primary);">Total Isu</p>
                        <h4 class="stat-value">{{ App\Models\Isu::count() }}</h4>
                    </div>
                    <i class="fas fa-file-alt stat-icon" style="color: var(--primary);"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card stat-card isu-strategis">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-label" style="color: var(--success);">Isu Strategis</p>
                        <h4 class="stat-value">{{ App\Models\Isu::where('isu_strategis', true)->count() }}</h4>
                    </div>
                    <i class="fas fa-star stat-icon" style="color: var(--success);"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card stat-card pengguna">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-label" style="color: var(--info);">Pengguna</p>
                        <h4 class="stat-value">{{ App\Models\User::count() }}</h4>
                    </div>
                    <i class="fas fa-users stat-icon" style="color: var(--info);"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Content Row -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card action-card h-100">
            <div class="card-header">
                <h6 class="card-header-title">Isu Terbaru</h6>
                <a href="{{ route('isu.index') }}" class="btn btn-sm" style="background: var(--primary); color: #fff;">Lihat Semua</a>
            </div>
            <div class="card-body">
                <ul class="recent-list">
                    @forelse(App\Models\Isu::latest()->take(10)->get() as $isu)
                        <li>
                            <a href="{{ route('isu.show', $isu) }}" class="recent-item">
                                <div>
                                    <h6 class="recent-item-title">{{ $isu->judul }}</h6>
                                    <div class="recent-item-meta">
                                        <span class="status-badge {{ $isu->isu_strategis ? 'status-badge-strategis' : 'status-badge-non-strategis' }}">
                                            <i class="{{ $isu->isu_strategis ? 'fas fa-star' : 'far fa-star' }} me-1"></i>
                                            {{ $isu->isu_strategis ? 'Strategis' : 'Regional' }}
                                        </span>
                                    </div>
                                </div>
                                <span class="recent-item-date">{{ $isu->tanggal->format('d M Y') }}</span>
                            </a>
                        </li>
                    @empty
                        <div class="text-center py-4">
                            <i class="far fa-file-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Belum ada isu terbaru.</p>
                            <a href="{{ route('isu.create') }}" class="btn btn-sm" style="background: var(--primary); color: #fff;">Tambah Isu Baru</a>
                        </div>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card action-card h-100">
            <div class="card-header">
                <h6 class="card-header-title">Tindakan Cepat</h6>
            </div>
            <div class="card-body">
                @if(Auth::user()->isAdmin() || Auth::user()->isEditor())
                    <a href="{{ route('isu.create') }}" class="main-action-btn">
                        <i class="fas fa-file-alt main-action-icon"></i>
                        <span class="main-action-text">Tambah Isu Baru</span>
                    </a>
                @endif
                
                <div class="row g-3">
                    @if(Auth::user()->isAdmin() || Auth::user()->isEditor())
                        <div class="col-md-6">
                            <a href="{{ route('documents.create') }}" class="action-btn" style="background: var(--success);">
                                <i class="fas fa-image action-btn-icon"></i>
                                <span class="action-btn-text">Upload Dokumen</span>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('trending.index') }}" class="action-btn" style="background: var(--info);">
                                <i class="fas fa-chart-line action-btn-icon"></i>
                                <span class="action-btn-text">Kelola Trending</span>
                            </a>
                        </div>
                    @endif
                    @if(Auth::user()->isAdmin() || Auth::user()->isEditor())
                        <div class="col-md-6">
                            <a href="{{ route('settings.index') }}" class="action-btn" style="background: var(--warning);">
                                <i class="fas fa-cog action-btn-icon"></i>
                                <span class="action-btn-text">Pengaturan</span>
                            </a>
                        </div>
                    @endif
                    @if(Auth::user()->isAdmin())
                        <div class="col-md-6">
                            <a href="{{ route('users.index') }}" class="action-btn" style="background: var(--secondary);">
                                <i class="fas fa-users action-btn-icon"></i>
                                <span class="action-btn-text">Kelola Pengguna</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Card animation
        const cards = document.querySelectorAll('.stat-card, .action-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100 * index);
        });

        const actionButtons = document.querySelectorAll('.action-btn, .main-action-btn');
        actionButtons.forEach(btn => {
            btn.setAttribute('title', btn.textContent.trim());
        });
    });
</script>
@endsection
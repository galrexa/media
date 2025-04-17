<!-- resources/views/isu/index.blade.php -->
@extends(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isEditor()) ? 'layouts.admin' : 'layouts.app')

@section('title', 'Daftar Isu')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="page-title mb-0">Daftar Isu</h2>
        </div>
        <div class="col-md-6 text-end">
            @auth
                @if(auth()->user()->isAdmin() || auth()->user()->isEditor())
                    <a href="{{ route('isu.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i> Tambah Isu Baru
                    </a>
                @endif
            @endauth
        </div>
    </div>
    
    <!-- Tambahkan field pencarian global -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('isu.index') }}" method="GET" id="searchForm">
                        <input type="hidden" name="active_tab" id="search_active_tab" value="{{ request('active_tab', 'strategis') }}">
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control" name="search" placeholder="Cari judul isu atau kategori..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary">Cari</button>
                            @if(request('search'))
                                <a href="{{ route('isu.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Reset
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden input untuk menyimpan tab aktif -->
    <input type="hidden" id="active_tab_input" value="{{ request('active_tab', 'strategis') }}">
    
    <!-- Modern Filter Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="custom-tab-container">
                <ul class="custom-tabs" id="issuTabs" role="tablist">
                    <li class="custom-tab-item" role="presentation">
                        <button class="custom-tab-link active" id="strategis-tab" data-bs-toggle="pill" data-bs-target="#strategis" type="button" role="tab">
                            <i class="fas fa-chart-line me-2"></i>Isu Strategis
                        </button>
                    </li>
                    <li class="custom-tab-item" role="presentation">
                        <button class="custom-tab-link" id="lainnya-tab" data-bs-toggle="pill" data-bs-target="#lainnya" type="button" role="tab">
                            <i class="fas fa-list-alt me-2"></i>Isu Regional
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Tab Content dengan style modern -->
    <div class="tab-content" id="issueTabsContent">
        <!-- Tab Isu Strategis -->
        <div class="tab-pane fade show active" id="strategis" role="tabpanel" aria-labelledby="strategis-tab">
            @if($isusStrategis->isNotEmpty())
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover custom-table">
                        <thead>
                            <tr>
                                <th>
                                    Judul
                                </th>
                                <th width="120" class="sortable" data-sort="tanggal">
                                    Tanggal
                                    @if(request('sort') == 'tanggal')
                                        <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @else
                                        <i class="fas fa-sort ms-1 text-muted opacity-50"></i>
                                    @endif
                                </th>
                                <th width="150">Kategori</th>
                                <th width="100" class="sortable" data-sort="tone">
                                    Tone
                                    @if(request('sort') == 'tone')
                                        <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @else
                                        <i class="fas fa-sort ms-1 text-muted opacity-50"></i>
                                    @endif
                                </th>
                                <th width="100" class="sortable" data-sort="skala">
                                    Skala
                                    @if(request('sort') == 'skala')
                                        <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @else
                                        <i class="fas fa-sort ms-1 text-muted opacity-50"></i>
                                    @endif
                                </th>
                                <th width="120" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                            <tbody>
                            @foreach($isusStrategis as $isu)
                                <tr>
                                    <td class="fw-medium">{{ $isu->judul }}</td>
                                    <td>{{ \Carbon\Carbon::parse($isu->tanggal)->format('d/m/Y') }}</td>
                                    <td>
                                        @if($isu->kategoris->isNotEmpty())
                                            @foreach($isu->kategoris as $kategori)
                                                <span class="badge bg-secondary me-1">{{ $kategori->nama }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge-custom" style="background-color: {{ $isu->refTone->warna ?? ($isu->tone == '1' ? '#0cce6b' : '#e5383b') }}">
                                            <i class="fas {{ $isu->refTone && $isu->refTone->icon ? $isu->refTone->icon : ($isu->tone == '1' ? 'fa-thumbs-up' : 'fa-thumbs-down') }} me-1"></i>
                                            {{ $isu->refTone->nama ?? ($isu->tone == '1' ? 'Positif' : 'Negatif') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-custom" style="background-color: {{ $isu->refSkala->warna ?? '#4361ee' }}">
                                            {{ $isu->refSkala->nama ?? $isu->skala }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="{{ route('isu.show', $isu) }}" class="btn-action btn-view" title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @auth
                                                @if(auth()->user()->isAdmin() || auth()->user()->isEditor())
                                                    <a href="{{ route('isu.edit', $isu) }}" class="btn-action btn-edit" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('isu.destroy', $isu) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn-action btn-delete" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus isu ini?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endauth
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="pagination-container mt-4">
                    {{ $isusStrategis->appends(['lainnya' => request()->input('lainnya')])->links() }}
                </div>
            @else
                <div class="empty-state">
                    <i class="fas fa-info-circle"></i>
                    <p>Tidak ada isu strategis untuk ditampilkan.</p>
                </div>
            @endif
        </div>

        <!-- Tab Isu Lainnya -->
        <div class="tab-pane fade" id="lainnya" role="tabpanel" aria-labelledby="lainnya-tab">
            @if($isusLainnya->isNotEmpty())
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover custom-table">
                        <thead>
                            <tr>
                                <th>
                                    Judul
                                </th>
                                <th width="120" class="sortable" data-sort="tanggal">
                                    Tanggal
                                    @if(request('sort') == 'tanggal')
                                        <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @else
                                        <i class="fas fa-sort ms-1 text-muted opacity-50"></i>
                                    @endif
                                </th>
                                <th width="150">Kategori</th>
                                <th width="100" class="sortable" data-sort="tone">
                                    Tone
                                    @if(request('sort') == 'tone')
                                        <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @else
                                        <i class="fas fa-sort ms-1 text-muted opacity-50"></i>
                                    @endif
                                </th>
                                <th width="100" class="sortable" data-sort="skala">
                                    Skala
                                    @if(request('sort') == 'skala')
                                        <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @else
                                        <i class="fas fa-sort ms-1 text-muted opacity-50"></i>
                                    @endif
                                </th>
                                <th width="120" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                            <tbody>
                                @foreach($isusLainnya as $isu)
                                    <tr>
                                        <td class="fw-medium">{{ $isu->judul }}</td>
                                        <td>{{ \Carbon\Carbon::parse($isu->tanggal)->format('d/m/Y') }}</td>
                                        <td>
                                            @if($isu->kategoris->isNotEmpty())
                                                @foreach($isu->kategoris as $kategori)
                                                    <span class="badge bg-secondary me-1">{{ $kategori->nama }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge-custom" style="background-color: {{ $isu->refTone->warna ?? ($isu->tone == '1' ? '#0cce6b' : '#e5383b') }}">
                                                <i class="fas {{ $isu->refTone && $isu->refTone->icon ? $isu->refTone->icon : ($isu->tone == '1' ? 'fa-thumbs-up' : 'fa-thumbs-down') }} me-1"></i>
                                                {{ $isu->refTone->nama ?? ($isu->tone == '1' ? 'Positif' : 'Negatif') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-custom" style="background-color: {{ $isu->refSkala->warna ?? '#4361ee' }}">
                                                {{ $isu->refSkala->nama ?? $isu->skala }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="{{ route('isu.show', $isu) }}" class="btn-action btn-view" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @auth
                                                    @if(auth()->user()->isAdmin() || (auth()->user()->isEditor() && $isu->created_by == auth()->id()))
                                                        <a href="{{ route('isu.edit', $isu) }}" class="btn-action btn-edit" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('isu.destroy', $isu) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn-action btn-delete" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus isu ini?')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endauth
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="pagination-container mt-4">
                    {{ $isusLainnya->appends(['strategis' => request()->input('strategis')])->links() }}
                </div>
            @else
                <div class="empty-state">
                    <i class="fas fa-info-circle"></i>
                    <p>Tidak ada isu regional untuk ditampilkan.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    /* Modern Tab Styling */
    .page-title {
        color: var(--primary-color);
        font-weight: 700;
        letter-spacing: 0.3px;
    }
    
    .custom-tab-container {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .custom-tabs {
        display: flex;
        list-style: none;
        padding: 0;
        margin: 0;
        gap: 1rem;
    }
    
    .custom-tab-item {
        margin-bottom: -1px;
    }
    
    .custom-tab-link {
        display: inline-flex;
        align-items: center;
        padding: 0.75rem 1.5rem;
        background: transparent;
        border: none;
        border-bottom: 2px solid transparent;
        color: var(--text-light);
        font-weight: 600;
        transition: var(--transition-normal);
        position: relative;
        letter-spacing: 0.3px;
    }
    
    .custom-tab-link:hover {
        color: var(--primary-color);
        background-color: rgba(67, 97, 238, 0.05);
    }
    
    .custom-tab-link.active {
        color: var(--primary-color);
        border-bottom: 2px solid var(--primary-color);
    }
    
    .custom-tab-link i {
        opacity: 0.8;
    }
    
    .custom-tab-link.active i {
        opacity: 1;
    }
    
    /* Table Styling */
    .custom-table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
        margin-bottom: 0;
    }
    
    .custom-table thead th {
        font-weight: 600;
        color: var(--text-color);
        background-color: rgba(0, 0, 0, 0.02);
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 2px solid rgba(0, 0, 0, 0.05);
    }
    
    .custom-table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .custom-table tbody tr {
        transition: var(--transition-fast);
    }
    
    .custom-table tbody tr:hover {
        background-color: rgba(67, 97, 238, 0.03);
    }

    .sortable {
    cursor: pointer;
    position: relative;
    user-select: none;
    }

    .sortable:hover {
        background-color: rgba(67, 97, 238, 0.05);
    }

    .sortable i {
        font-size: 0.75rem;
    }
    
    /* Custom Badge */
    .badge-custom {
        display: inline-flex;
        align-items: center;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.35rem 0.65rem;
        border-radius: 50px;
        color: white;
    }
    
    .badge-success {
        background-color: var(--success-color, #0cce6b);
    }
    
    .badge-danger {
        background-color: var(--danger-color, #e5383b);
    }
    
    .badge-info {
        background-color: var(--primary-color, #4361ee);
    }
    
    /* Action Buttons */
    .action-buttons {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    
    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        padding: 0;
        border-radius: 50%;
        font-size: 0.875rem;
        transition: var(--transition-normal);
        border: none;
        cursor: pointer;
    }
    
    .btn-view {
        background-color: rgba(67, 97, 238, 0.1);
        color: var(--primary-color);
    }
    
    .btn-view:hover {
        background-color: var(--primary-color);
        color: white;
        transform: translateY(-2px);
    }
    
    .btn-edit {
        background-color: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }
    
    .btn-edit:hover {
        background-color: #ffc107;
        color: white;
        transform: translateY(-2px);
    }
    
    .btn-delete {
        background-color: rgba(229, 56, 59, 0.1);
        color: var(--danger-color);
    }
    
    .btn-delete:hover {
        background-color: var(--danger-color);
        color: white;
        transform: translateY(-2px);
    }
    
    /* Pagination Container */
    .pagination-container {
        display: flex;
        justify-content: center;
    }
    
    .pagination {
        display: flex;
        padding: 0;
        margin: 0;
        list-style: none;
        border-radius: var(--border-radius-md);
    }
    
    .page-item {
        margin: 0 0.25rem;
    }
    
    .page-link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        color: var(--text-color);
        background-color: white;
        box-shadow: var(--shadow-sm);
        transition: var(--transition-normal);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .page-link:hover {
        background-color: rgba(67, 97, 238, 0.05);
        color: var(--primary-color);
        transform: translateY(-2px);
    }
    
    .page-item.active .page-link {
        background-color: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }
    
    .page-item.disabled .page-link {
        color: var(--text-light);
        pointer-events: none;
        background-color: #f8f9fa;
        border-color: rgba(0, 0, 0, 0.05);
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem;
        background-color: rgba(0, 0, 0, 0.02);
        border-radius: var(--border-radius-md);
        margin-bottom: 1.5rem;
    }
    
    .empty-state i {
        font-size: 3rem;
        color: var(--text-light);
        margin-bottom: 1rem;
        display: block;
    }
    
    .empty-state p {
        color: var(--text-light);
        font-size: 1.1rem;
        margin-bottom: 0;
    }
    
    /* Card Table Container */
    .card {
        border: none;
        border-radius: var(--border-radius-md);
        box-shadow: var(--shadow-sm);
        transition: var(--transition-normal);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    
    .card:hover {
        box-shadow: var(--shadow-md);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .custom-tab-link {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        .action-buttons {
            flex-wrap: wrap;
        }
        
        .custom-table thead th,
        .custom-table tbody td {
            padding: 0.75rem 0.5rem;
        }
        
        .badge-custom {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }
    }
    
    /* Dark Mode Support */
    @media (prefers-color-scheme: dark) {
        .custom-table thead th {
            background-color: rgba(255, 255, 255, 0.05);
            border-bottom: 2px solid rgba(255, 255, 255, 0.05);
        }
        
        .custom-table tbody td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .custom-table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.1);
        }
        
        .empty-state {
            background-color: rgba(255, 255, 255, 0.03);
        }
        
        .page-link {
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .page-item.disabled .page-link {
            background-color: rgba(255, 255, 255, 0.03);
            border-color: rgba(255, 255, 255, 0.05);
        }
        
        .custom-tab-container {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
    }
</style>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tangkap perubahan tab
        const tabs = document.querySelectorAll('#issuTabs button');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Simpan tab yang aktif ke session storage
                sessionStorage.setItem('activeIsuTab', this.id);
            });
        });
        
        // Cek dan set tab aktif dari session storage
        const activeTab = sessionStorage.getItem('activeIsuTab');
        if (activeTab) {
            const tabToActivate = document.getElementById(activeTab);
            if (tabToActivate) {
                const tabInstance = new bootstrap.Tab(tabToActivate);
                tabInstance.show();
            }
        }
        
        // Cek jika ada parameter URL untuk tab
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('lainnya') && !urlParams.has('strategis')) {
            document.getElementById('lainnya-tab').click();
        }

        // Saat user mengklik tab, update juga hidden input di form pencarian
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Simpan tab yang aktif ke session storage
                sessionStorage.setItem('activeIsuTab', this.id);
                
                // Update hidden input dengan nilai tab aktif
                const tabValue = this.id.replace('-tab', '');
                document.getElementById('active_tab_input').value = tabValue;
                
                // Update juga di form pencarian
                if (document.getElementById('search_active_tab')) {
                    document.getElementById('search_active_tab').value = tabValue;
                }
            });
        });

        // Tangani klik pada header kolom untuk sorting
        const sortableHeaders = document.querySelectorAll('.sortable');
        sortableHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const sortField = this.getAttribute('data-sort');
                let direction = 'asc';
                
                // Jika sudah di-sort dengan field yang sama, balik arahnya
                if (new URLSearchParams(window.location.search).get('sort') === sortField) {
                    direction = new URLSearchParams(window.location.search).get('direction') === 'asc' ? 'desc' : 'asc';
                }
                
                // Buat URL baru dengan parameter sort
                const urlParams = new URLSearchParams(window.location.search);
                urlParams.set('sort', sortField);
                urlParams.set('direction', direction);
                
                // Pastikan parameter active_tab disertakan
                const activeTab = document.querySelector('#issuTabs button.active').id.replace('-tab', '');
                urlParams.set('active_tab', activeTab);
                
                // Redirect ke URL dengan parameter sorting
                window.location.href = `${window.location.pathname}?${urlParams.toString()}`;
            });
        });       
    });
</script>
@endsection
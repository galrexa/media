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
                                <th width="100">Kategori</th>
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
                                        <span class="badge-custom" style="background-color: {{ $isu->refTone && $isu->tone ? $isu->refTone->warna : '#d3d3d3' }}">
                                                {{ $isu->refTone && $isu->tone ? $isu->refTone->nama : '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-custom" style="background-color: {{ $isu->refSkala && $isu->skala ? $isu->refSkala->warna : '#d3d3d3' }}">
                                                {{ $isu->refSkala && $isu->skala ? $isu->refSkala->nama : '-' }}
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
                                                    <!-- <a href="{{ route('isu.history', $isu) }}" class="btn-action btn-log" title="Riwayat">
                                                        <i class="fas fa-clock-rotate-left"></i>
                                                    </a> -->
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
                                <th width="100">Kategori</th>
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
                                            <span class="badge-custom" style="background-color: {{ $isu->refTone && $isu->tone ? $isu->refTone->warna : '#d3d3d3' }}">
                                                {{ $isu->refTone && $isu->tone ? $isu->refTone->nama : '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-custom" style="background-color: {{ $isu->refSkala && $isu->skala ? $isu->refSkala->warna : '#d3d3d3' }}">
                                                {{ $isu->refSkala && $isu->skala ? $isu->refSkala->nama : '-' }}
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
                                                        <!-- <a href="{{ route('isu.history', $isu) }}" class="btn-action btn-log" title="Riwayat">
                                                            <i class="fas fa-clock-rotate-left"></i>
                                                        </a> -->
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
<link rel="stylesheet" href="{{ asset('css/layouts/app.css') }}"> 
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
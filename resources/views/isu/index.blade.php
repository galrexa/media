<!-- resources/views/isu/index.blade.php -->
@extends(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isEditor()) ? 'layouts.admin' : 'layouts.app')

@section('title', 'Daftar Isu')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2 class="mb-0">Daftar Isu</h2>
        </div>
        <div class="col-md-6 text-end">
            @auth
                @if(auth()->user()->isAdmin() || auth()->user()->isEditor())
                    <a href="{{ route('isu.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Isu Baru
                    </a>
                @endif
            @endauth
        </div>
    </div>

    <!-- Filter Pills/Tabs -->
    <div class="row mb-3">
        <div class="col-12">
            <ul class="nav nav-pills" id="issuTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="strategis-tab" data-bs-toggle="pill" data-bs-target="#strategis" type="button" role="tab">
                        Isu Strategis
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="lainnya-tab" data-bs-toggle="pill" data-bs-target="#lainnya" type="button" role="tab">
                        Isu Lainnya
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="issueTabsContent">
        <!-- Tab Isu Strategis -->
        <div class="tab-pane fade show active" id="strategis" role="tabpanel" aria-labelledby="strategis-tab">
            @if($isusStrategis->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Tone</th>
                                <th>Skala</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($isusStrategis as $isu)
                            <tr>
                                <td>{{ $isu->judul }}</td>
                                <td>{{ \Carbon\Carbon::parse($isu->tanggal)->format('d/m/Y') }}</td>
                                <td>{{ $isu->kategori }}</td>
                                <td>
                                    <span class="badge" style="background-color: {{ $isu->refTone->warna ?? ($isu->tone == '1' ? '#28a745' : '#dc3545') }}">
                                        {{ $isu->refTone->nama ?? ($isu->tone == '1' ? 'Positif' : 'Negatif') }}
                                    </span>
                                </td>
                                <td>{{ $isu->refSkala->nama ?? $isu->skala }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('isu.show', $isu) }}" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @auth
                                            @if(auth()->user()->isAdmin() || (auth()->user()->isEditor() && $isu->created_by == auth()->id()))
                                                <a href="{{ route('isu.edit', $isu) }}" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="{{ route('isu.destroy', $isu) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus isu ini?')">
                                                        <i class="bi bi-trash"></i>
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
                <div class="mt-3">
                    {{ $isusStrategis->appends(['lainnya' => request()->input('lainnya')])->links() }}
                </div>
            @else
                <div class="alert alert-info">
                    Tidak ada isu strategis untuk ditampilkan.
                </div>
            @endif
        </div>

        <!-- Tab Isu Lainnya -->
        <div class="tab-pane fade" id="lainnya" role="tabpanel" aria-labelledby="lainnya-tab">
            @if($isusLainnya->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Tone</th>
                                <th>Skala</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($isusLainnya as $isu)
                                <tr>
                                    <td>{{ $isu->judul }}</td>
                                    <td>{{ \Carbon\Carbon::parse($isu->tanggal)->format('d/m/Y') }}</td>
                                    <td>{{ $isu->kategori }}</td>
                                    <td>
                                        <span class="badge" style="background-color: {{ $isu->refTone->warna ?? ($isu->tone == '1' ? '#28a745' : '#dc3545') }}">
                                            {{ $isu->refTone->nama ?? ($isu->tone == '1' ? 'Positif' : 'Negatif') }}
                                        </span>
                                    </td>
                                    <td>{{ $isu->refSkala->nama ?? $isu->skala }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('isu.show', $isu) }}" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @auth
                                                @if(auth()->user()->isAdmin() || (auth()->user()->isEditor() && $isu->created_by == auth()->id()))
                                                    <a href="{{ route('isu.edit', $isu) }}" class="btn btn-sm btn-warning">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form action="{{ route('isu.destroy', $isu) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus isu ini?')">
                                                            <i class="bi bi-trash"></i>
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
                <div class="mt-3">
                    {{ $isusLainnya->appends(['strategis' => request()->input('strategis')])->links() }}
                </div>
            @else
                <div class="alert alert-info">
                    Tidak ada isu lainnya untuk ditampilkan.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
    /* Custom styles untuk tampilan seperti pada gambar */
    .nav-pills .nav-link {
        border-radius: 0;
        padding: 8px 16px;
        color: #333;
        background-color: #f8f9fa;
        margin-right: 5px;
    }
    
    .nav-pills .nav-link.active {
        background-color: #17a2b8;
        color: white;
    }
    
    .table thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    
    .badge {
        font-weight: 500;
        padding: 5px 8px;
    }
    
    .btn-group .btn {
        margin-right: 2px;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
</style>
@endsection

@section('scripts')
<script>
    // Script untuk menyimpan tab aktif ke session storage
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
    });
</script>
@endsection
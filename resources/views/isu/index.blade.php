<!-- resources/views/isu/index.blade.php -->
@extends(
    auth()->check() &&
    (
        auth()->user()->isAdmin() ||
        auth()->user()->isEditor() ||
        auth()->user()->isVerifikator1() ||
        auth()->user()->isVerifikator2()
    )
    ? 'layouts.admin'
    : 'layouts.app'
)

@section('title', 'Daftar Isu')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="page-title fw-bold mb-0">Daftar Isu</h2>
        </div>
        <div class="col-md-6 text-md-end">
            @auth
                @if(auth()->user()->isAdmin() || auth()->user()->isEditor())
                    <a href="{{ route('isu.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i> Tambah Isu Baru
                    </a>
                @endif
            @endauth
        </div>
    </div>

    <!-- Filter dan Pencarian -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body py-3">
                    <form action="{{ route('isu.index') }}" method="GET" id="searchForm">
                        <input type="hidden" name="active_tab" id="search_active_tab" value="{{ request('active_tab', 'strategis') }}">
                        @if(request()->has('filter_status'))
                            <input type="hidden" name="filter_status" value="{{ request('filter_status') }}">
                        @endif
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0" name="search"
                                           placeholder="Cari judul isu atau kategori..."
                                           value="{{ request('search') }}"
                                           aria-label="Cari">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-select" aria-label="Filter berdasarkan status">
                                    <option value="">- Semua Status -</option>
                                    @foreach($statusList as $status)
                                        <option value="{{ $status->id }}" {{ request('status') == $status->id ? 'selected' : '' }}>
                                            {{ $status->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- <div class="col-md-3">
                                <div class="input-group">
                                    <input type="date" class="form-control" name="date_from"
                                           aria-label="Dari Tanggal"
                                           value="{{ request('date_from') }}">
                                    <span class="input-group-text bg-light">s/d</span>
                                    <input type="date" class="form-control" name="date_to"
                                           aria-label="Sampai Tanggal"
                                           value="{{ request('date_to') }}">
                                </div>
                            </div> -->
                            <div class="col-md-2 d-flex">
                                <button type="submit" class="btn btn-primary me-2 flex-grow-1">
                                    <i class="fas fa-filter me-1"></i> Filter
                                </button>
                                @if(request('search') || request('status') || request('filter_status') || request('date_from') || request('date_to'))
                                    <a href="{{ route('isu.index') }}" class="btn btn-outline-secondary"
                                       aria-label="Reset filter">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                @endif
                            </div>
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
                    <li class="custom-tab-item nav-item" role="presentation">
                        <button class="custom-tab-link nav-link active" id="strategis-tab"
                                data-bs-toggle="tab" data-bs-target="#strategis" type="button"
                                role="tab" aria-controls="strategis" aria-selected="true">
                            <i class="fas fa-chart-line me-2"></i>Isu Strategis
                        </button>
                    </li>
                    <li class="custom-tab-item nav-item" role="presentation">
                        <button class="custom-tab-link nav-link" id="lainnya-tab"
                                data-bs-toggle="tab" data-bs-target="#lainnya" type="button"
                                role="tab" aria-controls="lainnya" aria-selected="false">
                            <i class="fas fa-list-alt me-2"></i>Isu Regional
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="issueTabsContent">
        <!-- Tab Isu Strategis -->
        <div class="tab-pane fade show active" id="strategis" role="tabpanel" aria-labelledby="strategis-tab">
            @include('partials._isu_table', [
                'isus' => $isusStrategis,
                'tabId' => 'strategis',
                'emptyMessage' => 'Tidak ada isu strategis untuk ditampilkan.'
            ])
        </div>

        <!-- Tab Isu Regional -->
        <div class="tab-pane fade" id="lainnya" role="tabpanel" aria-labelledby="lainnya-tab">
            @include('partials._isu_table', [
                'isus' => $isusLainnya,
                'tabId' => 'lainnya',
                'emptyMessage' => 'Tidak ada isu regional untuk ditampilkan.'
            ])
        </div>
    </div>
</div>
@endsection

<!-- Form untuk aksi massal -->
<form id="mass-action-form" action="{{ route('isu.massAction') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="action" id="mass-action">
    <input type="hidden" name="selected_ids" id="selected-ids">
    <input type="hidden" name="rejection_reason" id="rejection-reason">
</form>

<!-- Modal untuk Alasan Penolakan -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">Alasan Penolakan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="rejection-reason-input" class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="rejection-reason-input" rows="4"
                              placeholder="Masukkan alasan penolakan..." required></textarea>
                    <div class="invalid-feedback">
                        Alasan penolakan harus diisi.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirm-reject">
                    <i class="fas fa-times-circle me-1"></i> Tolak
                </button>
            </div>
        </div>
    </div>
</div>

@section('styles')
<link rel="stylesheet" href="{{ asset('css/layouts/app.css') }}">
<style>

    /* Custom styles for this page */
    .badge-custom {
        display: inline-block;
        padding: 0.35em 0.65em;
        font-size: 0.75em;
        font-weight: 500;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.5rem;
        color: #fff;
    }

    .custom-table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }

    .action-buttons {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: none;
        background: none;
        transition: all 0.2s;
    }

    .btn-view { color: var(--bs-primary); }
    .btn-edit { color: var(--bs-warning); }
    .btn-log { color: var(--bs-info); }
    .btn-delete { color: var(--bs-danger); }

    .btn-action:hover {
        background-color: rgba(0,0,0,0.05);
    }

    .sortable {
        cursor: pointer;
    }

    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem;
        background-color: #f8f9fa;
        border-radius: 0.5rem;
        text-align: center;
    }

    .empty-state i {
        font-size: 3rem;
        color: #adb5bd;
        margin-bottom: 1rem;
    }

    .selected-actions {
        padding: 0.75rem 1rem;
        background-color: #f8f9fa;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        border: 1px solid #dee2e6;
    }

    .form-check-input:checked {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
    }
</style>
@endsection

@section('scripts')
<script src="{{ asset('js/isu/index.js') }}"></script>
@endsection

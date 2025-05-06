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
                    @if(auth()->user()->hasRole('verifikator1') || auth()->user()->hasRole('verifikator2'))
                        <li class="custom-tab-item nav-item" role="presentation">
                            <button class="custom-tab-link nav-link active" id="semua-tab"
                                    data-bs-toggle="tab" data-bs-target="#semua" type="button"
                                    role="tab" aria-controls="semua" aria-selected="true">
                                <i class="fas fa-list me-2"></i>Semua Isu
                            </button>
                        </li>
                    @else
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
                    @endif
                </ul>
            </div>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="issueTabsContent">
        @if(auth()->user()->hasRole('verifikator1') || auth()->user()->hasRole('verifikator2'))
            <!-- Gabungan Tab untuk Verifikator1 dan Verifikator2 -->
            <div class="tab-pane fade show active" id="semua" role="tabpanel" aria-labelledby="semua-tab">
                @include('partials._isu_table', [
                    'isus' => $isusGabungan, 
                    'tabId' => 'semua',
                    'emptyMessage' => 'Tidak ada isu untuk ditampilkan.'
                ])
            </div>
        @else
            <!-- Tab Isu Strategis (untuk user selain verifikator) -->
            <div class="tab-pane fade show active" id="strategis" role="tabpanel" aria-labelledby="strategis-tab">
                @include('partials._isu_table', [
                    'isus' => $isusStrategis,
                    'tabId' => 'strategis',
                    'emptyMessage' => 'Tidak ada isu strategis untuk ditampilkan.'
                ])
            </div>

            <!-- Tab Isu Regional (untuk user selain verifikator) -->
            <div class="tab-pane fade" id="lainnya" role="tabpanel" aria-labelledby="lainnya-tab">
                @include('partials._isu_table', [
                    'isus' => $isusLainnya,
                    'tabId' => 'lainnya',
                    'emptyMessage' => 'Tidak ada isu regional untuk ditampilkan.'
                ])
            </div>
        @endif
    </div>
</div>
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
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rejectModalLabel">Tolak Isu</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="rejection-reason-input" class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('rejection_reason') is-invalid @enderror" id="rejection-reason-input" name="rejection_reason" rows="4" placeholder="Masukkan alasan penolakan..." required></textarea>
                    <small class="text-muted">Berikan alasan penolakan yang jelas agar dapat diperbaiki.</small>
                    <div class="invalid-feedback">
                        Alasan penolakan minimal 10 karakter.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirm-reject">
                    <i class="fas fa-times-circle me-1"></i> Tolak Isu
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
@section('styles')
<link rel="stylesheet" href="{{ asset('css/custom/app.css') }}">
<style>
    /* Custom styles for this page */
    /* .badge-custom {
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
    } */

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

    .recent-item {
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
        transition: all 0.2s ease;
        border: 1px solid rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        justify-content: space-between;
        text-decoration: none;
        color: #333;
    }
    
    .recent-item:hover {
        background-color: rgba(78, 115, 223, 0.05);
        border-color: rgba(78, 115, 223, 0.1);
        transform: translateX(3px);
    }
    
    .recent-item-title {
        font-weight: 600;
        margin-bottom: 0.25rem;
        color: #333;
    }
    
    .recent-item-meta {
        font-size: 0.75rem;
        color: #6c757d;
        display: flex;
        align-items: center;
    }

    /* Style untuk badge status isu */
    .status-badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
        border-radius: 50px;
        font-weight: 500;
        margin-right: 0.5rem;
    }

    .bg-strategis {
        background-color: rgba(28, 200, 138, 0.1);
        color: #1cc88a;
        border-radius: 50px;
    }

    .bg-regional {
        background-color: rgba(108, 117, 125, 0.1);
        color: #6c757d;
        border-radius: 50px;
    }

    .modal {
    z-index: 2000 !important;
    }
    
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi modal
    const rejectModal = document.getElementById('rejectModal');
    const modalInstance = new bootstrap.Modal(rejectModal);
    const confirmRejectBtn = document.getElementById('confirm-reject');
    const rejectionReasonInput = document.getElementById('rejection-reason-input');
    const massActionForm = document.getElementById('mass-action-form');
    
    // Tambahkan referensi untuk tombol close dan batal
    const closeBtn = rejectModal.querySelector('.btn-close');
    const cancelBtn = rejectModal.querySelector('.btn-secondary');

    // Fungsi untuk mengumpulkan ID isu yang dipilih
    function getSelectedIds(tabId) {
        const checkboxes = document.querySelectorAll(`.isu-checkbox[data-tab="${tabId}"]:checked`);
        return Array.from(checkboxes).map(cb => cb.value);
    }
    
    // Fungsi untuk reset form dan state modal
    function resetModalState() {
        rejectionReasonInput.value = '';
        rejectionReasonInput.classList.remove('is-invalid');
        confirmRejectBtn.disabled = false;
        confirmRejectBtn.innerHTML = '<i class="fas fa-times-circle me-1"></i> Tolak Isu';
        // Reset ID yang dipilih jika diperlukan
        document.getElementById('selected-ids').value = '';
        document.getElementById('rejection-reason').value = '';
    }

    // Event listener untuk opsi "Tolak" di dropdown aksi massal
    document.querySelectorAll('[data-action="reject"]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const tabId = this.id.split('-').slice(-1)[0]; // Ambil tabId dari ID tombol
            const selectedIds = getSelectedIds(tabId);

            if (selectedIds.length === 0) {
                alert('Pilih setidaknya satu isu untuk ditolak.');
                return;
            }

            // Reset modal state sebelum menampilkan
            resetModalState();
            
            // Simpan selectedIds untuk digunakan saat submit
            document.getElementById('selected-ids').value = selectedIds.join(',');
            modalInstance.show();
        });
    });

    // Validasi dan submit form penolakan
    if (confirmRejectBtn) {
        confirmRejectBtn.addEventListener('click', function(e) {
            const rejectionReason = rejectionReasonInput.value.trim();

            // Validasi minimal 10 karakter
            if (rejectionReason.length < 10) {
                rejectionReasonInput.classList.add('is-invalid');
                alert('Alasan penolakan minimal 10 karakter.');
                return;
            }

            // Hapus class invalid jika validasi lolos
            rejectionReasonInput.classList.remove('is-invalid');

            // Isi form tersembunyi
            document.getElementById('mass-action').value = 'reject';
            document.getElementById('rejection-reason').value = rejectionReason;

            // Nonaktifkan tombol dan tampilkan loading state
            confirmRejectBtn.disabled = true;
            confirmRejectBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...';

            // Submit form
            massActionForm.submit();
        });
    }

    // Event listener untuk tombol close (X) pada modal
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            resetModalState();
        });
    }
    
    // Event listener untuk tombol Batal pada modal
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            resetModalState();
        });
    }

    // Reset form dan tombol saat modal ditutup (event dari Bootstrap)
    rejectModal.addEventListener('hidden.bs.modal', function() {
        resetModalState();
    });

    // Tambahkan event escape key untuk menutup modal dan reset
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && rejectModal.classList.contains('show')) {
            modalInstance.hide();
            resetModalState();
        }
    });

    // Cek jika ada error validasi dari server
    @if($errors->has('rejection_reason'))
        modalInstance.show();
        rejectionReasonInput.classList.add('is-invalid');
    @endif
});
</script>
<script src="{{ asset('js/isu/index.js') }}"></script>
@endsection

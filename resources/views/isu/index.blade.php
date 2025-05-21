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
                    <a href="{{ route('isu.create') }}" class="btn btn-primary responsive-btn">
                        <span class="icon-part"><i class="fas fa-plus-circle"></i></span>
                        <span class="text-part">Tambah Isu</span>
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
                                <button type="submit" class="btn btn-primary me-2 flex-grow-1 responsive-btn">
                                    <span class="icon-part"><i class="fas fa-filter"></i></span>
                                    <span class="text-part">Filter</span>
                                </button>
                                @if(request('search') || request('status') || request('filter_status') || request('date_from') || request('date_to'))
                                    <a href="{{ route('isu.index') }}" class="btn btn-outline-secondary responsive-btn"
                                    aria-label="Reset filter">
                                        <span class="icon-part"><i class="fas fa-times-circle"></i></span>
                                        <span class="text-part">Reset</span>
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
                            <button class="custom-tab-link nav-link active responsive-btn" id="semua-tab"
                                data-bs-toggle="tab" data-bs-target="#semua" type="button"
                                role="tab" aria-controls="semua" aria-selected="true">
                                <span class="icon-part"><i class="fas fa-list"></i></span>
                                <span class="text-part">Semua Isu</span>
                            </button>
                        </li>
                    @else
                        <li class="custom-tab-item nav-item" role="presentation">
                            <button class="custom-tab-link nav-link active responsive-btn" id="strategis-tab"
                                    data-bs-toggle="tab" data-bs-target="#strategis" type="button"
                                    role="tab" aria-controls="strategis" aria-selected="true">
                                <span class="icon-part"><i class="fas fa-chart-line"></i></span>
                                <span class="text-part">Isu Strategis</span>
                            </button>
                        </li>
                        <li class="custom-tab-item nav-item" role="presentation">
                            <button class="custom-tab-link nav-link responsive-btn" id="lainnya-tab"
                                    data-bs-toggle="tab" data-bs-target="#lainnya" type="button"
                                    role="tab" aria-controls="lainnya" aria-selected="false">
                                <span class="icon-part"><i class="fas fa-list-alt"></i></span>
                                <span class="text-part">Isu Regional</span>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.isuActionInitialized) return;
    window.isuActionInitialized = true;
    
    console.log("Inisialisasi handler aksi isu...");
    
    const rejectModal = document.getElementById('rejectModal');
    if (!rejectModal) {
        console.error("Modal penolakan tidak ditemukan!");
        return;
    }
    
    const modalInstance = new bootstrap.Modal(rejectModal);
    const confirmRejectBtn = document.getElementById('confirm-reject');
    const rejectionReasonInput = document.getElementById('rejection-reason-input');
    const massActionForm = document.getElementById('mass-action-form');
    const massActionInput = document.getElementById('mass-action');
    const selectedIdsInput = document.getElementById('selected-ids');
    const rejectionReasonHiddenInput = document.getElementById('rejection-reason');
    
    // Fungsi untuk mengumpulkan ID isu yang dipilih
    function getSelectedIds(tabId) {
        const checkboxes = document.querySelectorAll(`.isu-checkbox[data-tab="${tabId}"]:checked`);
        return Array.from(checkboxes).map(cb => cb.value);
    }
    
    // Fungsi untuk reset form dan state modal
    function resetModalState() {
        console.log("Reset state modal");
        if (rejectionReasonInput) rejectionReasonInput.value = '';
        if (rejectionReasonInput) rejectionReasonInput.classList.remove('is-invalid');
        if (confirmRejectBtn) {
            confirmRejectBtn.disabled = false;
            confirmRejectBtn.innerHTML = '<i class="fas fa-times-circle me-1"></i> Tolak Isu';
        }
    }
    
    // Fungsi untuk menyimpan nilai ke form
    function setFormValues(action, selectedIds, reason = null) {
        console.log(`Setting form values: action=${action}, selectedIds=${selectedIds.length} items`);
        if (massActionInput) massActionInput.value = action;
        if (selectedIdsInput) selectedIdsInput.value = JSON.stringify(selectedIds);
        if (reason && rejectionReasonHiddenInput) rejectionReasonHiddenInput.value = reason;
    }

        function ensureCorrectTabStructure() {
        const tabButtons = document.querySelectorAll('.custom-tab-link');
        
        tabButtons.forEach(button => {
            // Jika struktur tidak memiliki icon-part dan text-part
            if (!button.querySelector('.icon-part') || !button.querySelector('.text-part')) {
                console.log('Memperbaiki struktur tab:', button.id);
                
                // Simpan ikon dan teks
                const iconElement = button.querySelector('i');
                const buttonText = button.textContent.trim();
                
                // Bersihkan konten button
                button.innerHTML = '';
                
                // Tambahkan kelas responsive-btn jika belum ada
                if (!button.classList.contains('responsive-btn')) {
                    button.classList.add('responsive-btn');
                }
                
                // Buat struktur baru
                const iconPart = document.createElement('span');
                iconPart.className = 'icon-part';
                iconPart.appendChild(iconElement.cloneNode(true));
                
                const textPart = document.createElement('span');
                textPart.className = 'text-part';
                textPart.textContent = buttonText;
                
                // Tambahkan ke button
                button.appendChild(iconPart);
                button.appendChild(textPart);
            }
        });
    }
    
    // Panggil fungsi saat halaman dimuat
    ensureCorrectTabStructure();
    
    // Juga ketika tab diaktifkan
    const tabElements = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabElements.forEach(tab => {
        tab.addEventListener('shown.bs.tab', ensureCorrectTabStructure);
    });

    // Event listener untuk tombol dengan data-action
    document.querySelectorAll('[data-action]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const action = this.getAttribute('data-action');
            const tabId = this.id.split('-').slice(-1)[0];
            const selectedIds = getSelectedIds(tabId);
            
            console.log(`Button clicked: action=${action}, tabId=${tabId}, selectedIds=${selectedIds.length}`);
            
            if (selectedIds.length === 0) {
                showAlert(
                    'Tidak Ada Item Dipilih',
                    'Pilih setidaknya satu isu untuk diproses.',
                    'warning'
                );
                return;
            }
            
            // Set nilai form terlebih dahulu
            setFormValues(action, selectedIds);
            
            // Penanganan aksi
            if (action === 'reject') {
                resetModalState();
                modalInstance.show();
                
                // Double check form values
                // Double check form values
                setTimeout(() => {
                    if (!massActionInput.value || massActionInput.value !== 'reject') {
                        console.warn("Nilai action hilang, mengatur ulang...");
                        massActionInput.value = 'reject';
                    }
                    
                    if (!selectedIdsInput.value || selectedIdsInput.value === '[]') {
                        console.warn("Nilai selected_ids hilang, mengatur ulang...");
                        selectedIdsInput.value = JSON.stringify(selectedIds);
                    }
                }, 100);
            } else {
                // Konfigurasi untuk aksi lain
                let config = {};
                switch(action) {
                    case 'delete':
                        config = {
                            title: 'Hapus Isu',
                            text: `Apakah Anda yakin ingin menghapus ${selectedIds.length} isu terpilih?`,
                            icon: 'warning',
                            confirmButtonText: 'Hapus',
                            confirmButtonColor: '#dc3545'
                        };
                        break;
                    case 'send-to-verif1':
                        config = {
                            title: 'Kirim ke Verifikator 1',
                            text: `Apakah Anda yakin ingin mengirim ${selectedIds.length} isu ke Verifikator 1?`,
                            icon: 'question',
                            confirmButtonText: 'Kirim',
                            confirmButtonColor: '#3085d6'
                        };
                        break;
                    case 'send-to-verif2':
                        config = {
                            title: 'Kirim ke Verifikator 2',
                            text: `Apakah Anda yakin ingin mengirim ${selectedIds.length} isu ke Verifikator 2?`,
                            icon: 'question',
                            confirmButtonText: 'Ya',
                            confirmButtonColor: '#3085d6'
                        };
                        break;
                    case 'publish':
                        config = {
                            title: 'Publikasikan Isu',
                            text: `Apakah Anda yakin ingin mempublikasikan ${selectedIds.length} isu terpilih?`,
                            icon: 'question',
                            confirmButtonText: 'Ya',
                            confirmButtonColor: '#28a745'
                        };
                        break;
                    default:
                        return;
                }
                
                showConfirm(
                    'Konfirmasi Penolakan',
                    `Apakah Anda yakin ingin menolak ${JSON.parse(selectedIdsInput.value).length} isu yang dipilih?`,
                    () => {
                        const closeLoading = showLoading('Memproses Penolakan...');
                        setTimeout(() => {
                            massActionForm.submit();
                            closeLoading();
                        }, 200);
                    },
                    'warning',           // type parameter
                    'Tolak',        // confirmButtonText parameter
                    '#dc3545'            // confirmButtonColor parameter
                );
            }
        });
    });

    // Handler untuk tombol konfirmasi di modal
    if (confirmRejectBtn) {
        confirmRejectBtn.addEventListener('click', function(e) {
            console.log("Confirm button clicked");
            
            const rejectionReason = rejectionReasonInput ? rejectionReasonInput.value.trim() : '';
            
            if (rejectionReason.length < 10) {
                if (rejectionReasonInput) rejectionReasonInput.classList.add('is-invalid');
                showAlert(
                    'Validasi Gagal',
                    'Alasan penolakan minimal 10 karakter.',
                    'error'
                );
                return;
            }
            
            if (rejectionReasonInput) rejectionReasonInput.classList.remove('is-invalid');
            
            if (massActionInput && (!massActionInput.value || massActionInput.value !== 'reject')) {
                console.warn("Action value missing, resetting to 'reject'");
                massActionInput.value = 'reject';
            }
            
            if (selectedIdsInput && (!selectedIdsInput.value || selectedIdsInput.value === '[]')) {
                console.error("Selected IDs missing or empty!");
                showAlert(
                    'Error',
                    'Tidak ada isu yang dipilih. Silakan pilih isu terlebih dahulu.',
                    'error',
                    () => modalInstance.hide()
                );
                return;
            }
            
            if (rejectionReasonHiddenInput) {
                rejectionReasonHiddenInput.value = rejectionReason;
            }
            
            confirmRejectBtn.disabled = true;
            confirmRejectBtn.innerHTML = '<span class="Spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...';
            
            console.log("Form data before submit:", {
                action: massActionInput ? massActionInput.value : 'N/A',
                selected_ids: selectedIdsInput ? selectedIdsInput.value : 'N/A',
                rejection_reason: rejectionReasonHiddenInput ? rejectionReasonHiddenInput.value : 'N/A'
            });
            
            showConfirm(
                'Konfirmasi Penolakan',
                `Apakah Anda yakin ingin menolak ${JSON.parse(selectedIdsInput.value).length} isu yang dipilih?`,
                () => {
                    const closeLoading = showLoading('Memproses Penolakan...');
                    setTimeout(() => {
                        massActionForm.submit();
                        closeLoading();
                    }, 200);
                },
                'warning',
                'Tolak',
                '#dc3545'
            );
        });
    }

    // Event listeners untuk tombol close dan batal
    const closeBtn = rejectModal.querySelector('.btn-close');
    const cancelBtn = rejectModal.querySelector('.btn-secondary');
    
    if (closeBtn) closeBtn.addEventListener('click', resetModalState);
    if (cancelBtn) cancelBtn.addEventListener('click', resetModalState);
    
    // Reset state saat modal ditutup
    rejectModal.addEventListener('hidden.bs.modal', resetModalState);
    
    // Penanganan error validasi dari server
    @if($errors->has('rejection_reason'))
        modalInstance.show();
        if (rejectionReasonInput) rejectionReasonInput.classList.add('is-invalid');
        showAlert(
            'Validasi Gagal',
            '{{ $errors->first("rejection_reason") }}',
            'error'
        );
    @endif
    
    console.log("Inisialisasi handler aksi isu selesai!");
});
</script>
<script src="{{ asset('js/isu/index.js') }}"></script>
@endsection
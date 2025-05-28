{{-- resources/views/users/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Kelola Pengguna')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>Kelola Pengguna</h1>
            <p class="text-muted">Manajemen user dengan autentikasi API KSP</p>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('users.create') }}" class="btn btn-primary">
                <i class="fas fa-user-plus me-1"></i> 
                Tambah Pengguna
            </a>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h5 class="card-title text-primary">
                        <i class="fas fa-users"></i>
                        {{ $users->total() }}
                    </h5>
                    <p class="card-text text-muted mb-0">Total Pengguna</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h5 class="card-title text-success">
                        <i class="fas fa-user-check"></i>
                        {{ $users->where('is_active', true)->count() }}
                    </h5>
                    <p class="card-text text-muted mb-0">User Aktif</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h5 class="card-title text-warning">
                        <i class="fas fa-user-slash"></i>
                        {{ $users->where('is_active', false)->count() }}
                    </h5>
                    <p class="card-text text-muted mb-0">User Nonaktif</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h5 class="card-title text-info">
                        <i class="fas fa-api"></i>
                        {{ $users->whereNotNull('api_user_id')->count() }}
                    </h5>
                    <p class="card-text text-muted mb-0">Sudah Login API</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Table --}}
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>
                Daftar Pengguna
            </h5>
        </div>
        <div class="card-body">
            {{-- Enhanced User Table dengan Authentication Info --}}
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 2%">#</th>
                            <th style="width: 20%">Identitas</th>
                            <th style="width: 10%">Username</th>
                            <th style="width: 10%">Peran</th>
                            <th style="width: 8%">Status</th>
                            <!-- <th style="width: 10%">Authentication</th> {{-- KOLOM BARU --}} -->
                            <!-- <th style="width: 12%">Terdaftar</th> -->
                            <th style="width: 10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $index => $user)
                            <tr>
                                {{-- Row Number --}}
                                <td>{{ $users->firstItem() + $index }}</td>
                                
                                {{-- User Identity --}}
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-2">
                                            <div class="avatar-title rounded-circle bg-{{ $user->is_active ? 'primary' : 'secondary' }}">
                                                {{ strtoupper(substr($user->username, 0, 2)) }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="fw-bold">
                                                {{ $user->name ?: 'Belum diisi' }}
                                            </div>
                                            @if($user->email)
                                                <small class="text-muted">{{ $user->email }}</small>
                                            @endif
                                            @if($user->position)
                                                <small class="text-info d-block">{{ $user->getFormattedPosition() }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                
                                {{-- Username --}}
                                <td>
                                    <span class="badge bg-dark">{{ $user->username }}</span>
                                </td>
                                
                                {{-- Role --}}
                                <td>
                                    @if($user->role)
                                        <span class="badge bg-info">
                                            {{ ucfirst($user->role->name) }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            Tidak Ada Peran
                                        </span>
                                    @endif
                                </td>
                                
                                {{-- Active Status --}}
                                <td>
                                    @if($user->is_active)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times-circle me-1"></i>
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                
                                <!-- {{-- Authentication Methods - KOLOM BARU --}}
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        {{-- KSP API Status --}}
                                        @if($user->hasCompletedApiLogin())
                                            <span class="badge bg-success" title="Terhubung dengan KSP API">
                                                <i class="fas fa-link me-1"></i>
                                                KSP API
                                            </span>
                                            @if($user->last_api_login)
                                                <small class="text-muted">
                                                    Login: {{ $user->last_api_login->diffForHumans() }}
                                                </small>
                                            @endif
                                        @else
                                            <span class="badge bg-warning" title="Belum pernah login via KSP API">
                                                <i class="fas fa-unlink me-1"></i>
                                                Belum API
                                            </span>
                                        @endif
                                        
                                        {{-- Backup Password Status --}}
                                        <span class="badge bg-info" title="Password cadangan tersedia untuk backup authentication">
                                            <i class="fas fa-shield-alt me-1"></i>
                                            Backup OK
                                        </span>
                                    </div>
                                </td>
                                
                                {{-- Registration Date --}}
                                <td>
                                    <div>{{ $user->created_at->format('d M Y') }}</div>
                                    <small class="text-muted">{{ $user->created_at->format('H:i') }}</small>
                                </td> -->
                                
                                {{-- Actions - ENHANCED --}}
                                <td>
                                    <div class="btn-group" role="group">
                                        {{-- Edit Button --}}
                                        <a href="{{ route('users.edit', $user->id) }}" 
                                           class="btn btn-sm btn-warning"
                                           title="Edit User">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        {{-- Reset Backup Password Button --}}
                                        <form action="{{ route('users.reset-backup-password', $user->id) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('Yakin ingin mereset password cadangan untuk {{ $user->username }}?')">
                                            @csrf
                                            @method('POST')
                                            <button type="submit" 
                                                    class="btn btn-sm btn-secondary"
                                                    title="Reset Password Cadangan">
                                                <i class="fas fa-key"></i>
                                            </button>
                                        </form>

                                        {{-- Toggle Active Button --}}
                                        <form action="{{ route('users.toggle-active', $user->id) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('Yakin ingin mengubah status user ini?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" 
                                                    class="btn btn-sm btn-{{ $user->is_active ? 'outline-warning' : 'outline-success' }}"
                                                    title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} User">
                                                <i class="fas fa-{{ $user->is_active ? 'user-slash' : 'user-check' }}"></i>
                                            </button>
                                        </form>

                                        {{-- Delete Button (only if not current user) --}}
                                        @if($user->id !== Auth::id())
                                        <form action="{{ route('users.destroy', $user->id) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('Yakin ingin menghapus pengguna {{ $user->username }}?\n\nPerhatian: Password cadangan dan data API akan hilang!\n\nTindakan ini tidak dapat dibatalkan!')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="btn btn-sm btn-danger"
                                                    title="Hapus User">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @else
                                        <button class="btn btn-sm btn-secondary" disabled title="Tidak bisa hapus diri sendiri">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                        @endif

                                        <!-- {{-- Test Backup Auth Button (Debug Mode Only) --}}
                                        @if(config('app.debug'))
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-info"
                                                title="Test Backup Authentication"
                                                onclick="testBackupAuth({{ $user->id }}, '{{ $user->username }}')">
                                            <i class="fas fa-vial"></i>
                                        </button>
                                        @endif -->
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-users fa-2x mb-2"></i>
                                        <div>Belum ada data pengguna</div>
                                        <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm mt-2">
                                            <i class="fas fa-plus me-1"></i>
                                            Tambah Pengguna Pertama
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Enhanced Footer dengan Authentication Info --}}
        @if($users->hasPages())
        <div class="card-footer">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="text-muted">
                        Menampilkan {{ $users->firstItem() }} - {{ $users->lastItem() }} 
                        dari {{ $users->total() }} pengguna
                    </div>
                    <small class="text-info">
                        <i class="fas fa-info-circle me-1"></i>
                        Semua user memiliki backup authentication untuk failover
                    </small>
                </div>
                <div class="col-md-6 text-end">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Modal untuk Test Backup Authentication (Debug Mode) --}}
@if(config('app.debug'))
<div class="modal fade" id="testAuthModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test Backup Authentication</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="testAuthForm">
                    <input type="hidden" id="testUserId" name="user_id">
                    <div class="mb-3">
                        <label for="testPassword" class="form-label">Password Cadangan</label>
                        <input type="password" class="form-control" id="testPassword" name="test_password" required>
                        <div class="form-text">Masukkan password cadangan untuk testing authentication fallback</div>
                    </div>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">Test Authentication</button>
                    </div>
                </form>
                <div id="testResult" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('styles')
<style>
.avatar-sm {
    width: 40px;
    height: 40px;
}

.avatar-title {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 600;
}

.table th {
    border-top: none;
    font-weight: 600;
}

.btn-group .btn {
    border-radius: 0;
}

.btn-group .btn:first-child {
    border-top-left-radius: 0.25rem;
    border-bottom-left-radius: 0.25rem;
}

.btn-group .btn:last-child {
    border-top-right-radius: 0.25rem;
    border-bottom-right-radius: 0.25rem;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.025);
}

/* Style untuk kolom Authentication */
.badge + .badge {
    margin-top: 2px;
}

/* Animation untuk alert dismissible */
@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-dismissible {
    animation: slideInDown 0.3s ease-out;
}

/* Style untuk test button di debug mode */
@if (config('app.debug'))
.btn-outline-info {
    border-color: #0dcaf0;
    color: #0dcaf0;
}

.btn-outline-info:hover {
    background-color: #0dcaf0;
    border-color: #0dcaf0;
    color: #fff;
}
@endif
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto dismiss alerts after 7 seconds (lebih lama karena info penting)
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 7000);
    });
    
    // Tooltip initialization untuk semua button dengan title
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    @if(config('app.debug'))
    // Test Auth Modal Handler (Debug Mode Only)
    window.testBackupAuth = function(userId, username) {
        document.getElementById('testUserId').value = userId;
        document.querySelector('#testAuthModal .modal-title').textContent = 'Test Backup Authentication - ' + username;
        document.getElementById('testResult').style.display = 'none';
        document.getElementById('testPassword').value = '';
        
        const modal = new bootstrap.Modal(document.getElementById('testAuthModal'));
        modal.show();
    };
    
    // Test Auth Form Submit Handler
    document.getElementById('testAuthForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const userId = formData.get('user_id');
        const submitBtn = this.querySelector('button[type="submit"]');
        
        // Disable submit button dan tampilkan loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Testing...';
        
        fetch(`/users/${userId}/test-backup-auth`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            const resultDiv = document.getElementById('testResult');
            resultDiv.style.display = 'block';
            
            if (data.backup_password_valid) {
                resultDiv.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Authentication Berhasil!</strong><br>
                        ${data.message}
                    </div>`;
            } else {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle me-2"></i>
                        <strong>Authentication Gagal!</strong><br>
                        ${data.message}
                    </div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const resultDiv = document.getElementById('testResult');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Error!</strong><br>
                    Terjadi kesalahan saat testing authentication: ${error.message}
                </div>`;
        })
        .finally(() => {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Test Authentication';
        });
    });
    @endif
    
    // Security: Log user actions untuk audit trail
    const actionButtons = document.querySelectorAll('.btn-group form button[type="submit"]');
    actionButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Logging action untuk security audit
            const action = this.getAttribute('title') || 'Unknown Action';
            const userId = this.closest('tr').querySelector('.badge.bg-dark').textContent;
            
            console.log(`[AUDIT] User action attempted: ${action} on user ${userId} at ${new Date().toISOString()}`);
        });
    });
});

// Global function untuk konfirmasi delete dengan enhanced security warning
window.confirmUserDelete = function(username, hasApiData) {
    const warningMessage = hasApiData 
        ? `PERINGATAN KEAMANAN!\n\nAnda akan menghapus pengguna: ${username}\n\n⚠️  Data yang akan hilang:\n- Password cadangan\n- Data autentikasi API KSP\n- Riwayat login\n- Semua data terkait user\n\n❌ TINDAKAN INI TIDAK DAPAT DIBATALKAN!\n\nKetik "${username}" untuk konfirmasi:`
        : `Yakin ingin menghapus pengguna: ${username}?\n\nTindakan ini tidak dapat dibatalkan!`;
        
    if (hasApiData) {
        const confirmation = prompt(warningMessage);
        return confirmation === username;
    } else {
        return confirm(warningMessage);
    }
};
</script>
@endsection
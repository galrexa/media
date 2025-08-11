{{-- resources/views/users/create.blade.php --}}
@extends('layouts.admin')

@section('title', 'Tambah Pengguna Baru')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Kelola Pengguna</a></li>
                    <li class="breadcrumb-item active">Tambah Pengguna Baru</li>
                </ol>
            </nav>
            <h1>Tambah Pengguna Baru</h1>
            <!-- <p class="text-muted">
                Daftarkan username dan role untuk pengguna baru dengan sistem backup authentication. 
                Pengguna akan login menggunakan kredensial KSP mereka dengan fallback ke password cadangan.
            </p> -->
        </div>
    </div>

    {{-- Enhanced Success Message --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <div>{!! session('success') !!}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Error Messages --}}
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-user-plus me-2"></i>
                Form Pendaftaran Pengguna
            </h5>
        </div>
        <div class="card-body">
            <!-- {{-- Enhanced Alert Info for Backup Authentication System --}}
            <div class="alert alert-primary border-left-primary">
                <h5 class="alert-heading">
                    <i class="fas fa-shield-alt me-2"></i>
                    Sistem Backup Authentication
                </h5>
                <p class="mb-2">
                    <strong>Password yang Anda masukkan akan menjadi "Password Cadangan"</strong> untuk keperluan failover authentication.
                </p>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-cog text-primary me-1"></i> Cara Kerja Login:</h6>
                        <ol class="mb-0">
                            <li><span class="badge bg-primary">Priority 1</span> Coba login via <strong>KSP API</strong></li>
                            <li><span class="badge bg-warning">Priority 2</span> Jika API gagal, gunakan <strong>Password Cadangan</strong></li>
                        </ol>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-lock text-success me-1"></i> Keamanan Password:</h6>
                        <ul class="mb-0">
                            <li>Password cadangan <strong>TIDAK AKAN</strong> di-overwrite dari API</li>
                            <li>Tetap tersimpan aman untuk backup authentication</li>
                            <li>Dapat direset manual oleh admin jika diperlukan</li>
                        </ul>
                    </div>
                </div>
            </div> -->

            {{-- Traditional Info Alert (kept for backward compatibility) --}}
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Informasi Tambahan:</strong>
                <ul class="mb-0 mt-2">
                    <li>Username harus sesuai dengan username di sistem KSP</li>
                    <li>Data nama, email, dan profil akan diambil otomatis saat user pertama kali login via KSP</li>
                    <li>Password cadangan berguna jika server KSP sedang mengalami gangguan</li>
                </ul>
            </div>

            <form action="{{ route('users.store') }}" method="POST" id="createUserForm">
                @csrf

                {{-- Username Field --}}
                <div class="mb-4">
                    <label for="username" class="form-label">
                        <i class="fas fa-user me-1"></i>
                        Username<span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           class="form-control @error('username') is-invalid @enderror" 
                           id="username" 
                           name="username" 
                           value="{{ old('username') }}" 
                           placeholder="Username email KSP"
                           maxlength="255"
                           required>
                    @error('username')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- Enhanced Password Cadangan Field --}}
                <div class="mb-4">
                    <label for="initial_password" class="form-label">
                        <i class="fas fa-shield-alt me-1"></i>
                        Password Cadangan<span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input type="password" 
                               class="form-control @error('initial_password') is-invalid @enderror" 
                               id="initial_password" 
                               name="initial_password"
                               placeholder="Masukkan password cadangan (minimal 6 karakter)"
                               minlength="6"
                               required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword" title="Tampilkan/Sembunyikan Password">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-outline-info" type="button" id="generatePassword" title="Generate Password Acak">
                            <i class="fas fa-random"></i>
                        </button>
                    </div>                    
                    {{-- Password Strength Indicator --}}
                    <div class="mt-2">
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                        </div>
                        <small class="text-muted" id="passwordStrengthText">Kekuatan password: -</small>
                    </div>
                    @error('initial_password')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- Role Selection --}}
                <div class="mb-4">
                    <label for="role_id" class="form-label">
                        <i class="fas fa-user-tag me-1"></i>
                        Peran Pengguna <span class="text-danger">*</span>
                    </label>
                    <select class="form-select @error('role_id') is-invalid @enderror" 
                            id="role_id" 
                            name="role_id" 
                            required>
                        <option value="">Pilih Peran Pengguna</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" 
                                    {{ old('role_id') == $role->id ? 'selected' : '' }}
                                    data-description="{{ $role->description ?? '' }}">
                                {{ ucfirst($role->name) }}
                                @if($role->description)
                                    - {{ $role->description }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    {{-- Role Description Display --}}
                    <div id="roleDescription" class="form-text text-primary" style="display: none;">
                        <i class="fas fa-info-circle me-1"></i>
                        <span id="roleDescriptionText"></span>
                    </div>
                    @error('role_id')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- Additional User Status Options --}}
                <div class="mb-4">
                    <div class="card border-light">
                        <div class="card-header bg-light">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-cogs me-1"></i>
                                Pengaturan Tambahan
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">
                                    <i class="fas fa-user-check text-success me-1"></i>
                                    <strong>User Aktif</strong>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex justify-content-between align-items-right">
                    </div>
                    <div>
                        <a href="{{ route('users.index') }}" class="btn btn-secondary me-2">
                            <i class="fas fa-times me-1"></i>
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save me-1"></i>
                            Daftarkan Pengguna
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .alert-info {
        border-left: 4px solid #0dcaf0;
    }
    
    .border-left-primary {
        border-left: 4px solid #0d6efd !important;
    }
    
    .form-label {
        font-weight: 600;
        color: #495057;
    }
    
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }

    .progress-bar {
        transition: width 0.3s ease, background-color 0.3s ease;
    }

    .progress-bar.bg-danger {
        background-color: #dc3545 !important;
    }

    .progress-bar.bg-warning {
        background-color: #ffc107 !important;
    }

    .progress-bar.bg-info {
        background-color: #0dcaf0 !important;
    }

    .progress-bar.bg-success {
        background-color: #198754 !important;
    }

    .btn-outline-info:hover {
        color: #fff;
        background-color: #0dcaf0;
        border-color: #0dcaf0;
    }

    .form-check-input:checked {
        background-color: #198754;
        border-color: #198754;
    }

    .alert-heading {
        font-weight: 600;
    }

    /* Animation untuk alert */
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

    .alert {
        animation: slideInDown 0.3s ease-out;
    }
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // === PASSWORD VISIBILITY TOGGLE ===
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('initial_password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }

    // === PASSWORD GENERATOR ===
    const generatePasswordBtn = document.getElementById('generatePassword');
    if (generatePasswordBtn && passwordInput) {
        generatePasswordBtn.addEventListener('click', function() {
            const generatedPassword = generateSecurePassword(12);
            passwordInput.value = generatedPassword;
            passwordInput.setAttribute('type', 'text'); // Show generated password
            
            // Update toggle button
            const toggleIcon = togglePassword.querySelector('i');
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
            
            // Update password strength
            updatePasswordStrength(generatedPassword);
            
            // Show success message
            showToast('Password acak telah dibuat!', 'success');
        });
    }

    // === USERNAME VALIDATION ===
    const usernameInput = document.getElementById('username');
    if (usernameInput) {
        usernameInput.addEventListener('input', function() {
            // Remove invalid characters and show warning if needed
            const originalValue = this.value;
            const cleanValue = this.value.replace(/[^a-zA-Z0-9._-]/g, '');
            
            if (originalValue !== cleanValue) {
                this.value = cleanValue;
                showToast('Karakter tidak valid dihapus. Hanya huruf, angka, titik, underscore, dan dash yang diperbolehkan.', 'warning');
            }
        });
    }

    // === PASSWORD STRENGTH CHECKER ===
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            updatePasswordStrength(this.value);
        });
    }

    // === ROLE SELECTION DESCRIPTION ===
    const roleSelect = document.getElementById('role_id');
    const roleDescription = document.getElementById('roleDescription');
    const roleDescriptionText = document.getElementById('roleDescriptionText');
    
    if (roleSelect && roleDescription && roleDescriptionText) {
        roleSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const description = selectedOption.getAttribute('data-description');
            
            if (description) {
                roleDescriptionText.textContent = description;
                roleDescription.style.display = 'block';
            } else {
                roleDescription.style.display = 'none';
            }
        });
    }

    // === FORM SUBMISSION HANDLING ===
    const form = document.getElementById('createUserForm');
    const submitBtn = document.getElementById('submitBtn');
    
    if (form && submitBtn) {
        form.addEventListener('submit', function(e) {
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Mendaftarkan...';
            
            // Re-enable after 5 seconds (fallback)
            setTimeout(function() {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Daftarkan Pengguna';
            }, 5000);
        });
    }

    // === AUTO-DISMISS ALERTS ===
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 7000);
    });
});

// === UTILITY FUNCTIONS ===

/**
 * Generate secure password
 */
function generateSecurePassword(length = 12) {
    const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    let password = '';
    
    // Ensure at least one character from each type
    const lowercase = 'abcdefghijklmnopqrstuvwxyz';
    const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const numbers = '0123456789';
    const symbols = '!@#$%^&*';
    
    password += lowercase.charAt(Math.floor(Math.random() * lowercase.length));
    password += uppercase.charAt(Math.floor(Math.random() * uppercase.length));
    password += numbers.charAt(Math.floor(Math.random() * numbers.length));
    password += symbols.charAt(Math.floor(Math.random() * symbols.length));
    
    // Fill the rest randomly
    for (let i = password.length; i < length; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
    }
    
    // Shuffle the password
    return password.split('').sort(() => Math.random() - 0.5).join('');
}

/**
 * Update password strength indicator
 */
function updatePasswordStrength(password) {
    const strengthBar = document.getElementById('passwordStrength');
    const strengthText = document.getElementById('passwordStrengthText');
    
    if (!strengthBar || !strengthText) return;
    
    let strength = 0;
    let strengthLabel = '';
    
    // Length check
    if (password.length >= 8) strength += 25;
    if (password.length >= 12) strength += 15;
    
    // Character variety checks
    if (/[a-z]/.test(password)) strength += 15;
    if (/[A-Z]/.test(password)) strength += 15;
    if (/[0-9]/.test(password)) strength += 15;
    if (/[^A-Za-z0-9]/.test(password)) strength += 15;
    
    // Set strength label and color
    if (strength < 30) {
        strengthLabel = 'Lemah';
        strengthBar.className = 'progress-bar bg-danger';
    } else if (strength < 60) {
        strengthLabel = 'Sedang';
        strengthBar.className = 'progress-bar bg-warning';
    } else if (strength < 80) {
        strengthLabel = 'Kuat';
        strengthBar.className = 'progress-bar bg-info';
    } else {
        strengthLabel = 'Sangat Kuat';
        strengthBar.className = 'progress-bar bg-success';
    }
    
    strengthBar.style.width = strength + '%';
    strengthText.textContent = `Kekuatan password: ${strengthLabel}`;
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const toastContainer = document.createElement('div');
    toastContainer.className = 'position-fixed top-0 end-0 p-3';
    toastContainer.style.zIndex = '9999';
    
    const toastElement = document.createElement('div');
    toastElement.className = `toast align-items-center text-white bg-${type} border-0`;
    toastElement.setAttribute('role', 'alert');
    
    toastElement.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${type === 'success' ? 'check' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toastElement);
    document.body.appendChild(toastContainer);
    
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    // Remove from DOM after hiding
    toastElement.addEventListener('hidden.bs.toast', function() {
        document.body.removeChild(toastContainer);
    });
}
</script>
@endsection
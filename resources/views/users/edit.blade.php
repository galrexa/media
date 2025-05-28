{{-- resources/views/users/edit.blade.php --}}
@extends('layouts.admin')

@section('title', 'Edit Pengguna')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1>Edit Pengguna</h1>
            <p class="text-muted">
                Edit informasi pengguna: {{ $user->username }}
            </p>
        </div>
    </div>

    <div class="row">
        {{-- Main Edit Form --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-edit me-2"></i>
                        Informasi Pengguna
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('users.update', $user->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Username Field --}}
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="fas fa-user me-1"></i>
                                Username
                            </label>
                            <input type="text" 
                                   class="form-control @error('username') is-invalid @enderror" 
                                   id="username" 
                                   name="username" 
                                   value="{{ old('username', $user->username) }}" 
                                   required>
                            @error('username')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Role Selection --}}
                        <div class="mb-3">
                            <label for="role_id" class="form-label">
                                <i class="fas fa-user-tag me-1"></i>
                                Peran Pengguna
                            </label>
                            <select class="form-select @error('role_id') is-invalid @enderror" 
                                    id="role_id" 
                                    name="role_id" 
                                    required>
                                <option value="">Pilih Peran</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" 
                                            {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                                        {{ ucfirst($role->name) }}
                                        @if($role->description)
                                            - {{ $role->description }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('role_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Status Active --}}
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="is_active" 
                                       name="is_active" 
                                       value="1"
                                       {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    <i class="fas fa-user-check me-1"></i>
                                    Status Aktif
                                </label>
                            </div>
                            <div class="form-text">
                                Nonaktifkan untuk mencegah user login ke sistem.
                            </div>
                        </div>

                        {{-- Reset Password Section --}}
                        <hr>
                        <h6 class="text-danger">
                            <i class="fas fa-key me-1"></i>
                            Reset Password (Opsional)
                        </h6>
                        <div class="mb-3">
                            <label for="reset_password" class="form-label">
                                Password Baru
                            </label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control @error('reset_password') is-invalid @enderror" 
                                       id="reset_password" 
                                       name="reset_password"
                                       placeholder="Kosongkan jika tidak ingin mereset password">
                                <button class="btn btn-outline-secondary" type="button" id="toggleResetPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">
                                Isi hanya jika ingin mereset password sistem. User tetap login dengan password KSP.
                            </div>
                            @error('reset_password')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Action Buttons --}}
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('users.index') }}" class="btn btn-secondary me-2">
                                <i class="fas fa-times me-1"></i>
                                Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- User Info Sidebar --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Informasi User
                    </h5>
                </div>
                <div class="card-body">
                    {{-- Basic Info --}}
                    <div class="mb-3">
                        <strong>Username:</strong><br>
                        <span class="text-muted">{{ $user->username }}</span>
                    </div>

                    {{-- API Status --}}
                    <div class="mb-3">
                        <strong>Status API:</strong><br>
                        @if($user->hasCompletedApiLogin())
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle me-1"></i>
                                Sudah Login via API
                            </span>
                        @else
                            <span class="badge bg-warning">
                                <i class="fas fa-clock me-1"></i>
                                Belum Login via API
                            </span>
                        @endif
                    </div>

                    {{-- User Data from API --}}
                    @if($user->name)
                    <div class="mb-3">
                        <strong>Nama:</strong><br>
                        <span class="text-muted">{{ $user->name }}</span>
                    </div>
                    @endif

                    @if($user->email)
                    <div class="mb-3">
                        <strong>Email:</strong><br>
                        <span class="text-muted">{{ $user->email }}</span>
                    </div>
                    @endif

                    @if($user->api_user_id)
                    <div class="mb-3">
                        <strong>ID User API:</strong><br>
                        <span class="text-muted">{{ $user->api_user_id }}</span>
                    </div>
                    @endif

                    {{-- Registration Info --}}
                    <div class="mb-3">
                        <strong>Terdaftar:</strong><br>
                        <span class="text-muted">{{ $user->created_at->format('d M Y H:i') }}</span>
                    </div>

                    <div class="mb-3">
                        <strong>Terakhir Update:</strong><br>
                        <span class="text-muted">{{ $user->updated_at->format('d M Y H:i') }}</span>
                    </div>

                    {{-- Quick Actions --}}
                    <hr>
                    <h6>Aksi Cepat:</h6>
                    <div class="d-grid gap-2">
                        <form action="{{ route('users.toggle-active', $user->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-outline-{{ $user->is_active ? 'warning' : 'success' }} w-100">
                                <i class="fas fa-{{ $user->is_active ? 'user-slash' : 'user-check' }} me-1"></i>
                                {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} User
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Warning Card --}}
            <div class="card mt-3">
                <div class="card-body">
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Perhatian:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Username harus sesuai dengan sistem KSP</li>
                            <li>Data nama dan email dari API tidak bisa diedit manual</li>
                            <li>Reset password hanya untuk keperluan sistem</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const toggleResetPassword = document.getElementById('toggleResetPassword');
    const resetPasswordInput = document.getElementById('reset_password');
    
    if (toggleResetPassword && resetPasswordInput) {
        toggleResetPassword.addEventListener('click', function() {
            const type = resetPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            resetPasswordInput.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }
    
    // Username validation
    const usernameInput = document.getElementById('username');
    if (usernameInput) {
        usernameInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^a-zA-Z0-9._-]/g, '');
        });
    }
});
</script>
@endsection
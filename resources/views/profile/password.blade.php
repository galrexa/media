<!-- resources/views/profile/password.blade.php -->
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


@section('title', 'Ubah Password')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-lg-3">
            <!-- Card Profil -->
            <div class="card mb-4 profile-card">
                <div class="card-body text-center">
                    <div class="profile-avatar-container mb-3">
                        <i class="bi bi-person-circle profile-avatar-icon"></i>
                    </div>
                    <h5 class="card-title mb-1">{{ auth()->user()->name }}</h5>
                    <p class="text-muted">
                        {{ auth()->user()->getHighestRoleName() === 'admin' ? 'Administrator' : ucfirst(auth()->user()->getHighestRoleName()) }}
                    </p>
                    <p class="card-text text-muted mt-2">{{ auth()->user()->email }}</p>
                </div>
            </div>

            <!-- Menu Navigasi -->
            <div class="card profile-menu-card">
                <div class="list-group list-group-flush">
                    <a href="{{ route('profile.index') }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-person me-2"></i> Informasi Profil
                    </a>
                    <a href="{{ route('profile.password') }}" class="list-group-item list-group-item-action active">
                        <i class="bi bi-key me-2"></i> Ubah Password
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <!-- Card Ubah Password -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-key me-2"></i>Ubah Password</h5>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('profile.password.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Password Saat Ini</label>
                            <div class="input-group">
                                <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password">
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                                @error('current_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password Baru</label>
                            <div class="input-group">
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-text">
                                <small>Password harus memiliki minimal 8 karakter, kombinasi huruf besar dan kecil, angka, dan simbol.</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password_confirmation">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Password Strength Indicator -->
                        <div class="mb-4">
                            <div class="password-strength">
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="password-feedback mt-1"></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('profile.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-floppy-disk"></i> Ubah Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    /* Styling untuk halaman profil */
    .profile-card {
        border-radius: 10px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        transition: all 0.3s ease;
    }

    .profile-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .profile-avatar-container {
        width: 100px;
        height: 100px;
        margin: 0 auto;
        border-radius: 50%;
        background-color: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 5px solid #e9ecef;
    }

    .profile-avatar-icon {
        font-size: 3.5rem;
        color: #6c757d;
    }

    .profile-menu-card {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .profile-menu-card .list-group-item {
        border-left: 0;
        border-right: 0;
        padding: 0.75rem 1.25rem;
        transition: all 0.2s ease;
    }

    .profile-menu-card .list-group-item:first-child {
        border-top: 0;
    }

    .profile-menu-card .list-group-item.active {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
    }

    .profile-menu-card .list-group-item:not(.active):hover {
        background-color: #f8f9fa;
    }

    /* Progress bar untuk kekuatan password */
    .password-strength .progress-bar {
        transition: width 0.3s ease;
    }

    .password-strength .progress-bar.weak {
        background-color: #dc3545;
    }

    .password-strength .progress-bar.medium {
        background-color: #ffc107;
    }

    .password-strength .progress-bar.strong {
        background-color: #198754;
    }

    .password-feedback {
        font-size: 0.875rem;
        height: 20px;
    }

    .password-feedback.weak {
        color: #dc3545;
    }

    .password-feedback.medium {
        color: #ffc107;
    }

    .password-feedback.strong {
        color: #198754;
    }
</style>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle password visibility
        const toggleButtons = document.querySelectorAll('.toggle-password');
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const inputField = document.getElementById(targetId);
                const icon = this.querySelector('i');

                if (inputField.type === 'password') {
                    inputField.type = 'text';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                } else {
                    inputField.type = 'password';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                }
            });
        });

        // Password strength meter
        const passwordInput = document.getElementById('password');
        const progressBar = document.querySelector('.password-strength .progress-bar');
        const feedback = document.querySelector('.password-feedback');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let message = '';

            // Jika password kosong
            if (password.length === 0) {
                strength = 0;
                message = '';
            } else {
                // Cek panjang
                if (password.length >= 8) {
                    strength += 25;
                }

                // Cek huruf kecil
                if (password.match(/[a-z]/)) {
                    strength += 15;
                }

                // Cek huruf besar
                if (password.match(/[A-Z]/)) {
                    strength += 20;
                }

                // Cek angka
                if (password.match(/[0-9]/)) {
                    strength += 20;
                }

                // Cek karakter spesial
                if (password.match(/[^a-zA-Z0-9]/)) {
                    strength += 20;
                }

                // Set pesan berdasarkan kekuatan
                if (strength < 40) {
                    progressBar.className = 'progress-bar weak';
                    feedback.className = 'password-feedback mt-1 weak';
                    message = 'Password lemah';
                } else if (strength < 70) {
                    progressBar.className = 'progress-bar medium';
                    feedback.className = 'password-feedback mt-1 medium';
                    message = 'Password sedang';
                } else {
                    progressBar.className = 'progress-bar strong';
                    feedback.className = 'password-feedback mt-1 strong';
                    message = 'Password kuat';
                }
            }

            // Update UI
            progressBar.style.width = strength + '%';
            progressBar.setAttribute('aria-valuenow', strength);
            feedback.textContent = message;
        });
    });
</script>
@endsection

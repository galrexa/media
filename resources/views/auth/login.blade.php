<!-- resources/views/auth/login.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Media Monitoring</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            background-color: #f5f7ff;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            max-width: 400px;
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            background: white;
        }

        .login-header {
            text-align: center;
            padding: 2rem 0 1.5rem;
        }

        .login-logo {
            max-height: 100px;
            margin-bottom: 1rem;
        }

        .login-form {
            padding: 0 2rem 2rem;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 13px;
            top: 13px;
            color: #6c757d;
        }

        .input-with-icon input {
            padding-left: 40px;
            height: 48px;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
        }

        .input-with-icon input:focus {
            box-shadow: 0 0 0 3px rgba(66, 99, 235, 0.1);
            border-color: #4263eb;
        }

        .btn-login {
            height: 48px;
            border-radius: 10px;
            background: #4263eb;
            border: none;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-login:hover {
            background: #3b5bd9;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(66, 99, 235, 0.2);
        }

        .form-switch .form-check-input {
            width: 2.5em;
            height: 1.25em;
        }

        .form-switch .form-check-input:checked {
            background-color: #4263eb;
            border-color: #4263eb;
        }

        .login-footer {
            padding: 1rem;
            text-align: center;
            border-top: 1px solid #f5f5f5;
            color: #6c757d;
            font-size: 0.8rem;
        }

        .forgot-password {
            color: #4263eb;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .toggle-password {
            position: absolute;
            right: 13px;
            top: 13px;
            cursor: pointer;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Logo -->
            <div class="text-center mb-4">
                <img src="{{ asset('logo.png') }}" alt="Logo" class="logo mb-3">
                <h4>Selamat Datang</h4>
            </div>

        <!-- Form login -->
        <div class="login-form">
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Input username dengan ikon -->
                <div class="mb-3 input-with-icon">
                    <i class="fa fa-user"></i>
                    <input id="username" type="text"
                        class="form-control @error('username') is-invalid @enderror"
                        name="username"
                        value="{{ old('username') }}"
                        placeholder="Masukkan username"
                        required autocomplete="username" autofocus>
                    @error('username')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Input password dengan ikon dan toggle visibility -->
                <div class="mb-3 input-with-icon">
                    <i class="fas fa-key"></i>
                    <input id="password" type="password"
                        class="form-control @error('password') is-invalid @enderror"
                        name="password"
                        placeholder="Masukkan password"
                        required autocomplete="current-password">
                    <!-- <span class="toggle-password" id="togglePassword">
                        <i class="bi bi-eye-slash" id="toggleIcon"></i>
                    </span> -->
                    @error('password')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Remember me dengan styling modern -->
                <!-- <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" name="remember" id="remember"
                            {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember">Ingat Saya</label>
                    </div>

                    @if (Route::has('password.request'))
                        <a class="forgot-password" href="{{ route('password.request') }}">
                            Lupa password?
                        </a>
                    @endif
                </div> -->

                <!-- Tombol login -->
                <div class="d-grid mb-4">
                    <button type="submit" class="btn btn-primary btn-login">
                        Login
                    </button>
                </div>

                <!-- Link registrasi jika ada -->
                @if (Route::has('register'))
                    <div class="text-center">
                        <span class="text-muted">Belum memiliki akun? </span>
                        <a href="{{ route('register') }}" class="forgot-password fw-bold">
                            Daftar disini
                        </a>
                    </div>
                @endif
            </form>
        </div>

        <!-- Footer sederhana -->
        <div class="login-footer">
            © 2025 Media Monitoring. All rights reserved.
        </div>
    </div>

    <!-- Script untuk toggle password visibility -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (togglePassword) {
                togglePassword.addEventListener('click', function () {
                    // Toggle tipe input antara password dan text
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);

                    // Toggle ikon
                    toggleIcon.classList.toggle('bi-eye');
                    toggleIcon.classList.toggle('bi-eye-slash');
                });
            }
        });
    </script>
</body>
</html>
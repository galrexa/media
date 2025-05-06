<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Media Monitoring</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/custom/login.css') }}">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Logo -->
            <div class="text-center mb-4">
                <img src="{{ asset('logo.png') }}" alt="Logo" class="logo mb-3">
                <h4>Selamat Datang</h4>
            </div>

            <!-- Login Form -->
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Username -->
                <div class="form-group mb-3">
                    <div class="input-with-icon @error('username') has-error @enderror">
                        <i class="fas fa-user"></i>
                        <input id="username" type="text"
                               class="form-control @error('username') is-invalid @enderror"
                               name="username"
                               value="{{ old('username') }}"
                               placeholder="Username"
                               required
                               autofocus>
                        @error('username')
                            <i class="fas fa-exclamation-circle error-icon"></i>
                        @enderror
                    </div>
                    @error('username')
                        <div class="error-message">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group mb-3">
                    <div class="input-with-icon @error('password') has-error @enderror">
                        <i class="fas fa-lock"></i>
                        <input id="password" type="password"
                               class="form-control @error('password') is-invalid @enderror"
                               name="password"
                               placeholder="Password"
                               required>
                        <i id="togglePassword" class="fas fa-eye toggle-password"></i>
                        @error('password')
                            <i class="fas fa-exclamation-circle error-icon"></i>
                        @enderror
                    </div>
                    @error('password')
                        <div class="error-message">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-login">
                        Login
                    </button>
                </div>
            </form>

            <!-- Footer -->
            <div class="text-center mt-4">
                <small class="text-muted">© 2025 Media Monitoring</small>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('js/login.js') }}"></script>
</body>
</html>
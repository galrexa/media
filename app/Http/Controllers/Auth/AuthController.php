<?php
// app/Http/Controllers/Auth/AuthController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\KspApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Isu;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * @var KspApiService|null
     */
    private $kspApiService;
    
    /**
     * Constructor - Initialize services dengan safety checks
     */
    public function __construct()
    {
        // Initialize KSP API Service hanya jika tersedia dan configured
        if (class_exists('App\Services\KspApiService') && config('services.ksp_api.enabled', false)) {
            try {
                $this->kspApiService = app(KspApiService::class);
                Log::info('KSP API Service initialized successfully');
            } catch (\Exception $e) {
                Log::warning('Failed to initialize KSP API Service', ['error' => $e->getMessage()]);
                $this->kspApiService = null;
            }
        } else {
            Log::info('KSP API Service not available or disabled');
            $this->kspApiService = null;
        }
    }

    /**
     * Menampilkan halaman login dengan informasi failover
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        $authMethods = [
            'ksp_api_available' => $this->kspApiService !== null,
            'backup_enabled' => true,
            'auth_priority' => 'KSP API → Password Cadangan'
        ];

        return view('auth.login', compact('authMethods'));
    }

    /**
     * Handle proses login dengan Enhanced Failover Authentication System
     * 
     * SECURITY PRIORITY:
     * 1. Validasi user existence & status
     * 2. KSP API Authentication (preferred)
     * 3. Backup Local Password Authentication (failover)
     * 4. Security logging & audit trail
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $startTime = microtime(true);
        $sessionId = $request->session()->getId();
        
        // Enhanced logging untuk security audit
        Log::info('=== LOGIN ATTEMPT STARTED ===', [
            'session_id' => $sessionId,
            'username' => $request->username,
            'ip' => $this->getRealIpAddr($request),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
            'ksp_api_available' => $this->kspApiService !== null,
        ]);

        // === STEP 1: REQUEST VALIDATION ===
        $validationResult = $this->validateLoginRequest($request);
        if (!$validationResult['success']) {
            return $validationResult['response'];
        }

        $username = trim($request->username);
        $password = $request->password;

        // === STEP 2: USER VERIFICATION ===
        $userResult = $this->verifyUserAccount($username, $request);
        if (!$userResult['success']) {
            return $userResult['response'];
        }

        $localUser = $userResult['user'];

        // === STEP 3: ENHANCED FAILOVER AUTHENTICATION ===
        $authResult = $this->performFailoverAuthentication($request, $localUser, $username, $password);
        
        // === STEP 4: PERFORMANCE & SECURITY LOGGING ===
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        Log::info('=== LOGIN ATTEMPT COMPLETED ===', [
            'session_id' => $sessionId,
            'username' => $username,
            'success' => $authResult['success'],
            'auth_method' => $authResult['auth_method'] ?? 'failed',
            'execution_time_ms' => $executionTime,
            'ip' => $this->getRealIpAddr($request),
        ]);

        return $authResult['response'];
    }

    /**
     * Validasi request login dengan enhanced security
     */
    private function validateLoginRequest(Request $request): array
    {
        try {
            $request->validate([
                'username' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9._-]+$/'],
                'password' => ['required', 'string', 'min:1'],
            ], [
                'username.required' => 'Username wajib diisi.',
                'username.max' => 'Username tidak boleh lebih dari 255 karakter.',
                'username.regex' => 'Username hanya boleh mengandung huruf, angka, titik, underscore, dan dash.',
                'password.required' => 'Password wajib diisi.',
            ]);

            return ['success' => true];

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Login validation failed', [
                'errors' => $e->errors(),
                'ip' => $this->getRealIpAddr($request),
            ]);

            return [
                'success' => false,
                'response' => back()->withErrors($e->errors())->onlyInput('username')
            ];
        }
    }

    /**
     * Verifikasi akun user dengan enhanced security checks
     */
    private function verifyUserAccount(string $username, Request $request): array
    {
        // Cari user di database
        $localUser = User::where('username', $username)->first();
        
        Log::info('User account verification', [
            'username' => $username,
            'user_found' => $localUser !== null,
            'user_id' => $localUser?->id,
            'user_active' => $localUser?->is_active ?? 'N/A',
            'has_api_data' => $localUser?->hasCompletedApiLogin() ?? false,
        ]);

        // User tidak ditemukan
        if (!$localUser) {
            $this->logSecurityEvent('USER_NOT_FOUND', $username, $request);
            
            return [
                'success' => false,
                'response' => back()->withErrors([
                    'username' => 'Username tidak terdaftar dalam sistem. Silakan hubungi administrator untuk mendaftarkan akun Anda.',
                ])->onlyInput('username')
            ];
        }

        // User tidak aktif
        if (!$localUser->is_active) {
            $this->logSecurityEvent('INACTIVE_USER_LOGIN_ATTEMPT', $username, $request, [
                'user_id' => $localUser->id
            ]);
            
            return [
                'success' => false,
                'response' => back()->withErrors([
                    'username' => 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.',
                ])->onlyInput('username')
            ];
        }

        return [
            'success' => true,
            'user' => $localUser
        ];
    }

    /**
     * Enhanced Failover Authentication System
     * Prioritas: KSP API → Backup Local Password
     */
    private function performFailoverAuthentication(Request $request, User $localUser, string $username, string $password): array
    {
        Log::info('Starting failover authentication process', [
            'username' => $username,
            'user_id' => $localUser->id,
            'ksp_api_available' => $this->kspApiService !== null,
        ]);

        // === PRIORITY 1: KSP API AUTHENTICATION ===
        if ($this->kspApiService) {
            Log::info('Attempting KSP API authentication (Priority 1)');
            
            $apiResult = $this->attemptKspApiAuthentication($request, $localUser, $username, $password);
            
            if ($apiResult['success']) {
                return [
                    'success' => true,
                    'auth_method' => 'KSP_API',
                    'response' => $apiResult['response']
                ];
            }
            
            // Log API failure untuk audit
            Log::warning('KSP API authentication failed, attempting backup authentication', [
                'username' => $username,
                'api_error' => $apiResult['message'],
                'fallback_available' => true,
            ]);
        }

        // === PRIORITY 2: BACKUP LOCAL PASSWORD AUTHENTICATION ===
        Log::info('Attempting backup local authentication (Priority 2)');
        
        $backupResult = $this->attemptBackupAuthentication($request, $localUser, $username, $password);
        
        if ($backupResult['success']) {
            return [
                'success' => true,
                'auth_method' => 'BACKUP_LOCAL',
                'response' => $backupResult['response']
            ];
        }

        // === BOTH AUTHENTICATION METHODS FAILED ===
        $this->logSecurityEvent('AUTHENTICATION_FAILED', $username, $request, [
            'user_id' => $localUser->id,
            'ksp_api_attempted' => $this->kspApiService !== null,
            'ksp_api_error' => $apiResult['message'] ?? 'N/A',
            'backup_attempted' => true,
        ]);

        $errorMessage = $this->kspApiService 
            ? 'Login gagal. Kredensial KSP tidak valid dan password cadangan juga tidak cocok.'
            : 'Login gagal. Password tidak valid.';

        return [
            'success' => false,
            'auth_method' => 'failed',
            'response' => back()->withErrors([
                'username' => $errorMessage . ' Silakan hubungi administrator jika terus mengalami masalah.',
            ])->onlyInput('username')
              ->with('warning', $this->kspApiService ? 'Server KSP tidak dapat diakses.' : null)
        ];
    }

    /**
     * Attempt KSP API Authentication dengan enhanced error handling
     */
    private function attemptKspApiAuthentication(Request $request, User $localUser, string $username, string $password): array
    {
        try {
            $apiResponse = $this->kspApiService->authenticate($username, $password);

            Log::info('KSP API authentication response', [
                'username' => $username,
                'success' => $apiResponse['success'],
                'message' => $apiResponse['message'],
                'response_code' => $apiResponse['response_code'] ?? 'N/A',
            ]);

            if ($apiResponse['success']) {
                // Update user data dari API response (selective fields only)
                $this->updateUserFromApiData($localUser, $apiResponse['data']);

                // Login user dan regenerate session
                Auth::login($localUser);
                $request->session()->regenerate();

                // Log successful login
                $this->logSecurityEvent('LOGIN_SUCCESS_API', $username, $request, [
                    'user_id' => $localUser->id,
                    'role' => $localUser->getHighestRoleName(),
                ]);

                return [
                    'success' => true,
                    'response' => $this->redirectAfterLogin($localUser, 'KSP API')
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $apiResponse['message'] ?? 'KSP API authentication failed'
                ];
            }

        } catch (\Exception $e) {
            Log::error('KSP API authentication exception', [
                'username' => $username,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'KSP API connection error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Attempt Backup Local Authentication dengan enhanced security
     */
    private function attemptBackupAuthentication(Request $request, User $localUser, string $username, string $password): array
    {
        Log::info('Backup authentication started', [
            'username' => $username,
            'user_id' => $localUser->id,
        ]);

        // Menggunakan Laravel's built-in authentication untuk konsistensi
        $credentials = [
            'username' => $username,
            'password' => $password,
            'is_active' => true  // Pastikan hanya user aktif yang bisa login
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // Log successful backup authentication
            $this->logSecurityEvent('LOGIN_SUCCESS_BACKUP', $username, $request, [
                'user_id' => $localUser->id,
                'role' => $localUser->getHighestRoleName(),
                'ksp_api_available' => $this->kspApiService !== null,
            ]);

            return [
                'success' => true,
                'response' => $this->redirectAfterLogin($localUser, 'Backup Local')
            ];
        }

        Log::warning('Backup authentication failed', [
            'username' => $username,
            'user_id' => $localUser->id,
            'reason' => 'Invalid backup password',
        ]);

        return [
            'success' => false,
            'message' => 'Invalid backup password'
        ];
    }

    /**
     * Update data user dari API response dengan selective field update
     * IMPORTANT: Password TIDAK PERNAH di-update untuk mempertahankan backup auth
     */
    private function updateUserFromApiData(User $user, array $apiData): void
    {
        try {
            $updateData = [];
            
            Log::info('Updating user data from KSP API (selective fields, password preserved)', [
                'user_id' => $user->id,
                'username' => $user->username,
            ]);
            
            // === SELECTIVE FIELD UPDATES ===
            
            // 1. Nama lengkap
            if (!empty($apiData['namalengkap'])) {
                $updateData['name'] = trim($apiData['namalengkap']);
            }
            
            // 2. Position/Jabatan
            if (!empty($apiData['jabatan']) && $user->columnExists('position')) {
                $updateData['position'] = trim($apiData['jabatan']);
            }
            
            // 3. Department/Satuan Kerja
            if (!empty($apiData['satuankerja']) && $user->columnExists('department')) {
                $updateData['department'] = trim($apiData['satuankerja']);
            }
            
            // 4. Email (dari uname jika valid email)
            if (!empty($apiData['uname']) && filter_var($apiData['uname'], FILTER_VALIDATE_EMAIL)) {
                $updateData['email'] = trim($apiData['uname']);
            }
            
            // 5. Profile photo indicator
            if (!empty($apiData['foto']) && $user->columnExists('profile_photo')) {
                $updateData['profile_photo'] = 'has_photo';
            }
            
            // 6. API user ID
            if (!empty($apiData['id_user'])) {
                $updateData['api_user_id'] = $apiData['id_user'];
            }
            
            // 7. Employee ID
            if (!empty($apiData['id_pegawai']) && $user->columnExists('employee_id')) {
                $updateData['employee_id'] = $apiData['id_pegawai'];
            }
            
            // 8. Update last API login time
            if ($user->columnExists('last_api_login')) {
                $updateData['last_api_login'] = now();
            }
            
            // === CRITICAL: PASSWORD TIDAK PERNAH DI-UPDATE ===
            // Ini mempertahankan backup authentication mechanism
            
            // Update user jika ada data yang berubah
            if (!empty($updateData)) {
                $user->update($updateData);
                
                Log::info('User data updated from KSP API', [
                    'user_id' => $user->id,
                    'updated_fields' => array_keys($updateData),
                    'password_preserved' => true,
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to update user from API data', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Enhanced redirect after login dengan informasi authentication method
     */
    private function redirectAfterLogin(User $user, string $authMethod = 'Unknown')
    {
        // Set flash messages untuk feedback ke user
        session()->flash('login_success', true);
        session()->flash('auth_method', $authMethod);
        
        // Ambil tanggal isu terakhir untuk dashboard
        try {
            $latestIsu = Isu::latest('tanggal')->first();
            $latestDateFormatted = $latestIsu 
                ? $latestIsu->tanggal->translatedFormat('d F Y') 
                : now()->translatedFormat('d F Y');
            session()->flash('latestIsuDate', $latestDateFormatted);
        } catch (\Exception $e) {
            Log::info('Could not fetch latest issue date', ['error' => $e->getMessage()]);
        }

        // Buat pesan welcome berdasarkan method authentication
        $displayName = $user->name ?: $user->username;
        $welcomeMessage = "Selamat datang, {$displayName}!";

        // Redirect berdasarkan role user
        $redirectUrl = $this->determineRedirectUrl($user);
        
        return redirect()->intended($redirectUrl)->with('success', $welcomeMessage);
    }

    /**
     * Tentukan URL redirect berdasarkan role user
     */
    private function determineRedirectUrl(User $user): string
    {
        if ($user->isAdmin() || $user->isEditor()) {
            return 'dashboard/admin';
        }
        
        return '/';
    }

    /**
     * Enhanced logout dengan security logging
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        
        if ($user) {
            $this->logSecurityEvent('LOGOUT', $user->username, $request, [
                'user_id' => $user->id,
                'role' => $user->getHighestRoleName(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Anda telah keluar dari sistem.');
    }

    /**
     * Test backup authentication untuk debugging (admin only)
     */
    public function testBackupAuth(Request $request, User $user)
    {
        // Security check: hanya admin atau debug mode
        if (!config('app.debug') && !Auth::user()?->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'test_password' => 'required|string'
        ]);

        $isValid = Hash::check($request->test_password, $user->password);

        $this->logSecurityEvent('BACKUP_AUTH_TEST', $user->username, $request, [
            'target_user_id' => $user->id,
            'test_result' => $isValid,
            'tester_id' => Auth::id(),
        ]);

        return response()->json([
            'backup_password_valid' => $isValid,
            'message' => $isValid 
                ? 'Password cadangan valid dan dapat digunakan untuk backup authentication.'
                : 'Password cadangan tidak cocok.',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
            ]
        ]);
    }

    /**
     * Reset backup password untuk user (admin only)
     */
    public function resetBackupPassword(Request $request, User $user)
    {
        // Security check: hanya admin
        if (!Auth::user()?->isAdmin()) {
            abort(403, 'Only administrators can reset backup passwords');
        }

        // Generate password baru
        $newPassword = $this->generateSecurePassword();
        
        // Update password
        $user->update(['password' => Hash::make($newPassword)]);

        $this->logSecurityEvent('BACKUP_PASSWORD_RESET', $user->username, $request, [
            'target_user_id' => $user->id,
            'reset_by' => Auth::id(),
        ]);

        return back()->with('success', 
            "Password cadangan untuk {$user->username} telah direset. Password baru: <strong>{$newPassword}</strong> (simpan dengan aman!)"
        );
    }

    /**
     * Generate secure password untuk backup
     */
    private function generateSecurePassword(int $length = 12): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $password;
    }

    /**
     * Enhanced security event logging
     */
    private function logSecurityEvent(string $event, string $username, Request $request, array $additionalData = []): void
    {
        $logData = array_merge([
            'event' => $event,
            'username' => $username,
            'ip' => $this->getRealIpAddr($request),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
            'session_id' => $request->session()->getId(),
        ], $additionalData);

        // Log ke file sistem
        Log::info("SECURITY_EVENT: {$event}", $logData);
    }

    /**
     * Get real IP address dengan comprehensive checking
     */
    private function getRealIpAddr(Request $request): string
    {
        // Header yang mungkin mengandung real IP
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];

        foreach ($ipHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                // Validasi IP dan pastikan bukan private/reserved
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        // Fallback ke IP Laravel
        $requestIp = $request->ip();
        
        // Jika masih localhost, coba dapatkan server IP
        if (in_array($requestIp, ['127.0.0.1', '::1', 'localhost'])) {
            $serverIp = $this->getServerLocalIp();
            if ($serverIp) {
                return $serverIp;
            }
        }

        return $requestIp;
    }

    /**
     * Get server's local IP address
     */
    private function getServerLocalIp(): ?string
    {
        // Coba dari server address
        if (!empty($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] !== '127.0.0.1') {
            return $_SERVER['SERVER_ADDR'];
        }

        // Method alternatif menggunakan hostname
        if (function_exists('gethostname')) {
            $hostname = gethostname();
            $localIp = gethostbyname($hostname);
            
            if ($localIp && $localIp !== $hostname && $localIp !== '127.0.0.1') {
                return $localIp;
            }
        }

        return null;
    }

    /**
     * Test API connection untuk debugging (debug mode only)
     */
    public function testApiConnection(Request $request)
    {
        if (!config('app.debug')) {
            abort(403, 'Only available in debug mode');
        }

        if (!Auth::user()?->isAdmin()) {
            abort(403, 'Only administrators can test API connection');
        }

        $response = [
            'timestamp' => now()->toISOString(),
            'real_ip' => $this->getRealIpAddr($request),
            'request_ip' => $request->ip(),
            'server_addr' => $_SERVER['SERVER_ADDR'] ?? 'N/A',
            'ksp_api_service_available' => $this->kspApiService !== null,
        ];

        if ($this->kspApiService) {
            try {
                $response['connection_test'] = $this->kspApiService->testConnection();
                $response['config_validation'] = $this->kspApiService->validateConfig();
            } catch (\Exception $e) {
                $response['error'] = $e->getMessage();
            }
        } else {
            $response['error'] = 'KSP API Service not available';
            $response['config_exists'] = file_exists(config_path('services.php'));
            $response['service_class_exists'] = class_exists('App\Services\KspApiService');
        }

        return response()->json($response);
    }
}
<?php
// app/Http/Middleware/ApiAuthMiddleware.php - Middleware untuk API authentication tracking

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\LoginHistory;
use Carbon\Carbon;

class ApiAuthMiddleware
{
    /**
     * Handle an incoming request dengan API authentication tracking.
     */
    public function handle(Request $request, Closure $next)
    {
        // Jika user sudah login via web, skip API auth
        if (Auth::check()) {
            $user = Auth::user();
            
            // Update last API login untuk tracking aktivitas
            if ($this->shouldUpdateApiLogin($user)) {
                $user->update(['last_api_login' => now()]);
                
                // Track sebagai API activity jika belum ada login web hari ini
                $this->trackApiActivity($user, $request);
            }
            
            return $next($request);
        }

        // Coba authentication via API
        $apiAuthResult = $this->attemptApiAuthentication($request);
        
        if ($apiAuthResult['success']) {
            $user = $apiAuthResult['user'];
            
            // Login user ke session
            Auth::login($user);
            
            // Track API login
            $this->trackLoginHistory($user, 'api', $request);
            
            // Update last API login
            $user->update(['last_api_login' => now()]);
            
            Log::info('API authentication successful', [
                'user_id' => $user->id,
                'username' => $user->username,
                'role' => $user->getHighestRoleName(),
                'ip' => $request->ip()
            ]);
            
            return $next($request);
        }

        // Authentication failed
        Log::warning('API authentication failed', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'message' => $apiAuthResult['message']
        ]);

        return redirect()->route('login')->with('error', 'Session expired. Please login again.');
    }

    /**
     * Attempt API authentication
     */
    private function attemptApiAuthentication(Request $request)
    {
        try {
            // Get API credentials from request atau session
            $apiToken = $request->header('Authorization') 
                     ?? $request->session()->get('api_token')
                     ?? $request->cookie('api_token');
            
            if (!$apiToken) {
                return [
                    'success' => false,
                    'message' => 'No API token provided'
                ];
            }

            $apiUrl = config('app.ksp_api_verify_url', config('app.ksp_api_url') . '/verify');
            
            // Verify token dengan API
            $response = Http::timeout(10)
                ->withHeaders(['Authorization' => $apiToken])
                ->post($apiUrl, [
                    'api_key' => config('app.ksp_api_key')
                ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'API verification failed'
                ];
            }

            $data = $response->json();

            if (!isset($data['status']) || $data['status'] !== 'success') {
                return [
                    'success' => false,
                    'message' => $data['message'] ?? 'Token verification failed'
                ];
            }

            // Get or update user
            $userData = $data['data'];
            $user = $this->getOrUpdateUser($userData);

            if (!$user || !$user->is_active) {
                return [
                    'success' => false,
                    'message' => 'User not found or inactive'
                ];
            }

            return [
                'success' => true,
                'user' => $user,
                'message' => 'API authentication successful'
            ];

        } catch (\Exception $e) {
            Log::error('API authentication error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'API authentication exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get or update user from API data
     */
    private function getOrUpdateUser($userData)
    {
        try {
            $user = User::where('api_user_id', $userData['user_id'])
                       ->orWhere('username', $userData['username'])
                       ->first();

            if ($user) {
                // Update existing user data from API
                $user->update([
                    'name' => $userData['nama'] ?? $user->name,
                    'email' => $userData['email'] ?? $user->email,
                    'position' => $userData['jabatan'] ?? $user->position,
                    'department' => $userData['unit_kerja'] ?? $user->department,
                    'employee_id' => $userData['nip'] ?? $user->employee_id,
                    'is_active' => isset($userData['aktif']) ? (bool)$userData['aktif'] : $user->is_active,
                ]);

                Log::info('User updated from API', [
                    'user_id' => $user->id,
                    'username' => $user->username
                ]);
            } else {
                // Create new user jika tidak ada
                $user = User::create([
                    'name' => $userData['nama'],
                    'username' => $userData['username'],
                    'email' => $userData['email'] ?? $userData['username'] . '@ksp.go.id',
                    'password' => bcrypt('api_user_' . time()),
                    'position' => $userData['jabatan'] ?? null,
                    'department' => $userData['unit_kerja'] ?? null,
                    'api_user_id' => $userData['user_id'],
                    'employee_id' => $userData['nip'] ?? null,
                    'role_id' => 5, // Default viewer role
                    'is_active' => isset($userData['aktif']) ? (bool)$userData['aktif'] : true,
                ]);

                Log::info('New user created from API', [
                    'user_id' => $user->id,
                    'username' => $user->username
                ]);
            }

            return $user;

        } catch (\Exception $e) {
            Log::error('Error getting/updating user from API', [
                'error' => $e->getMessage(),
                'userData' => $userData
            ]);
            return null;
        }
    }

    /**
     * Track login history untuk API login
     */
    private function trackLoginHistory($user, $loginType, $request)
    {
        try {
            LoginHistory::create([
                'user_id' => $user->id,
                'login_type' => $loginType,
                'login_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => session()->getId(),
                'role_name' => $user->getHighestRoleName(),
            ]);

            Log::info('API login history tracked', [
                'user_id' => $user->id,
                'login_type' => $loginType,
                'role' => $user->getHighestRoleName()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to track API login history', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Track API activity (bukan login, tapi aktivitas biasa)
     */
    private function trackApiActivity($user, $request)
    {
        try {
            // Cek apakah sudah ada aktivitas hari ini
            $today = Carbon::today();
            $hasActivityToday = LoginHistory::where('user_id', $user->id)
                ->whereDate('login_at', $today)
                ->exists();

            // Jika belum ada aktivitas hari ini, track sebagai API activity
            if (!$hasActivityToday) {
                LoginHistory::create([
                    'user_id' => $user->id,
                    'login_type' => 'api_activity',
                    'login_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'session_id' => session()->getId(),
                    'role_name' => $user->getHighestRoleName(),
                ]);

                Log::info('API activity tracked', [
                    'user_id' => $user->id,
                    'role' => $user->getHighestRoleName()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to track API activity', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Tentukan apakah perlu update last_api_login
     */
    private function shouldUpdateApiLogin($user)
    {
        if (!$user->last_api_login) {
            return true;
        }

        // Update jika terakhir login lebih dari 5 menit yang lalu
        $lastLogin = Carbon::parse($user->last_api_login);
        return $lastLogin->diffInMinutes(now()) > 5;
    }

    /**
     * Handle API logout (jika diperlukan)
     */
    public function handleApiLogout(Request $request)
    {
        $user = Auth::user();
        
        if ($user) {
            // Track logout untuk API session
            $this->trackLogoutHistory($user, $request);
            
            Log::info('API logout', [
                'user_id' => $user->id,
                'username' => $user->username
            ]);
        }

        // Clear API token dari session/cookie
        $request->session()->forget('api_token');
        
        // Logout dari session
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }

    /**
     * Track logout history untuk API session
     */
    private function trackLogoutHistory($user, $request)
    {
        try {
            // Update record login terakhir dengan logout time
            $lastLogin = LoginHistory::where('user_id', $user->id)
                ->whereNull('logout_at')
                ->whereIn('login_type', ['api', 'api_activity'])
                ->latest('login_at')
                ->first();

            if ($lastLogin) {
                $sessionDuration = $lastLogin->login_at->diffInSeconds(now());
                
                $lastLogin->update([
                    'logout_at' => now(),
                    'session_duration' => $sessionDuration
                ]);

                Log::info('API logout tracked', [
                    'user_id' => $user->id,
                    'session_duration' => $sessionDuration . ' seconds',
                    'login_type' => $lastLogin->login_type
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to track API logout', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get API authentication status untuk debugging
     */
    public function getAuthStatus(Request $request)
    {
        $user = Auth::user();
        
        return response()->json([
            'authenticated' => Auth::check(),
            'user' => $user ? [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'role' => $user->getHighestRoleName(),
                'last_api_login' => $user->last_api_login,
                'is_active' => $user->is_active
            ] : null,
            'session_id' => session()->getId(),
            'timestamp' => now()
        ]);
    }
}
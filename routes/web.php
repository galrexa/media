<?php
// routes/web.php - Phase 3: Enhanced Analytics Routes

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IsuController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\TrendingController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AnalyticsController;

/*
|--------------------------------------------------------------------------
| Web Routes - Phase 3: Multi-Role Analytics Implementation
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| TRACKED ROUTES - Multi-Role Analytics Tracking
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', \App\Http\Middleware\AnalyticsMiddleware::class])->group(function () {
    
    // Homepage/Dashboard - tracked untuk semua role
    Route::get('/', function () {
        if (auth()->check()) {
            $user = auth()->user();
            
            // Cek apakah user masih aktif
            if (isset($user->is_active) && !$user->is_active) {
                auth()->logout();
                request()->session()->invalidate();
                request()->session()->regenerateToken();
                return redirect()->route('login')
                               ->with('error', 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.');
            }
            
            // Redirect berdasarkan role
            if ($user->isAdmin() || $user->isEditor()) {
                return redirect()->route('dashboard.admin');
            }
            
            // Viewer ke home controller
            return app(HomeController::class)->index(request());
        }
        return redirect()->route('login');
    })->name('home');

    Route::get('/home', [HomeController::class, 'index'])->name('home.index');

    Route::get('/dashboard-landing', function () {
        $user = auth()->user();
        if ($user->isAdmin() || $user->isEditor()) {
            return redirect()->route('dashboard.admin');
        } else {
            return redirect()->route('home');
        }
    })->name('dashboard.landing');

    // Admin Dashboard - tracked untuk admin/editor
    Route::get('/dashboard/admin', fn() => view('dashboard.admin'))
        ->middleware('role:admin,editor')
        ->name('dashboard.admin');

    // Isu Pages - tracked untuk semua role yang akses
    Route::get('/isu/{isu}', [IsuController::class, 'show'])->name('isu.show');
    Route::get('/preview-isu', [IsuController::class, 'preview'])->name('preview.isu');

    // Trending Pages - tracked untuk semua role
    Route::get('/trending', [TrendingController::class, 'index'])->name('trending.index');
    Route::get('/trending/selected', [TrendingController::class, 'selected'])->name('trending.selected');

    // Documents Pages - tracked untuk semua role
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');

    // Profile Pages - tracked untuk semua role
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::get('/profile/password', [ProfileController::class, 'editPassword'])->name('profile.password');
    Route::put('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // Preview Pages - tracked untuk semua role
    Route::get('/preview', [PreviewController::class, 'getPreview'])->name('preview.full');

    // Admin Management Pages - tracked untuk admin/editor/verifikator
    Route::middleware('role:admin,editor,verifikator1,verifikator2')->group(function () {
        
        // Isu Management - tracked
        Route::get('/isu', [IsuController::class, 'index'])->name('isu.index');
        Route::get('/isu/create', [IsuController::class, 'create'])->name('isu.create');
        Route::get('/isu/{isu}/edit', [IsuController::class, 'edit'])->name('isu.edit');
        
        // Trending Management - tracked untuk admin/editor
        Route::middleware('role:admin,editor')->group(function () {
            Route::get('/trending/create', [TrendingController::class, 'create'])->name('trending.create');
            Route::get('/trending/manual/create', [TrendingController::class, 'createManual'])->name('trending.manual.create');
            Route::get('/trending/manage-google', [TrendingController::class, 'manageGoogleSelected'])->name('trending.manageGoogleSelected');
            Route::get('/trending/manage-x', [TrendingController::class, 'manageXSelected'])->name('trending.manageXSelected');
        });
        
        // Document Management - tracked untuk admin/editor
        Route::middleware('role:admin,editor')->group(function () {
            Route::get('/documents/upload', [DocumentController::class, 'create'])->name('documents.create');
            Route::get('/documents/edit/{date?}', [DocumentController::class, 'edit'])->name('documents.edit');
        });
    });
});

/*
|--------------------------------------------------------------------------
| NON-TRACKED ROUTES - CRUD Operations & API Endpoints
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth'])->group(function () {
    
    // Dashboard redirect
    Route::get('/dashboard', function () {
        $user = auth()->user();
        if ($user->isAdmin() || $user->isEditor()) {
            return redirect()->route('dashboard.admin');
        }
        return redirect()->route('home');
    })->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Issue Management CRUD (Non-tracked)
    |--------------------------------------------------------------------------
    */
    
    Route::prefix('isu')->name('isu.')->group(function () {
        // CRUD operations - tidak di-track
        Route::middleware('role:admin,editor,verifikator1,verifikator2')->group(function () {
            Route::post('/', [IsuController::class, 'store'])->name('store');
            Route::put('/{isu}', [IsuController::class, 'update'])->name('update');
            Route::post('/isu/mass-action', [IsuController::class, 'massAction'])->name('massAction');
            Route::get('/{isu}/history', [IsuController::class, 'history'])->name('history');
        });

        // Routes khusus untuk verifikator (penolakan isu)
        Route::middleware('role:admin,verifikator1,verifikator2')->group(function () {
            Route::post('/{isu}/penolakan', [IsuController::class, 'processPenolakan'])->name('penolakan');
        });

        // Hapus isu hanya bisa dilakukan admin atau editor
        Route::middleware('role:admin,verifikator2,editor')
             ->delete('/{isu}', [IsuController::class, 'destroy'])
             ->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Document Management CRUD (Non-tracked)
    |--------------------------------------------------------------------------
    */
    
    Route::middleware('role:admin,editor')->prefix('documents')->name('documents.')->group(function () {
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::put('/{id}', [DocumentController::class, 'update'])->name('update');
    });

    /*
    |--------------------------------------------------------------------------
    | Trending Management CRUD (Non-tracked)
    |--------------------------------------------------------------------------
    */
    
    Route::prefix('trending')->name('trending.')->group(function () {
        Route::middleware('role:admin,editor')->group(function () {
            Route::post('/manual/store', [TrendingController::class, 'storeManual'])->name('manual.store');
            Route::post('/', [TrendingController::class, 'store'])->name('store');
            Route::delete('/{trending}', [TrendingController::class, 'destroy'])->name('destroy');
            Route::put('/{trending}/toggle-selected', [TrendingController::class, 'toggleSelected'])->name('toggleSelected');

            Route::get('/refresh-google', [TrendingController::class, 'refreshGoogleTrends'])->name('refreshGoogleTrends');
            Route::get('/refresh-x', [TrendingController::class, 'refreshXTrends'])->name('refreshXTrends');

            Route::post('/save-google', [TrendingController::class, 'saveGoogleWithSelection'])->name('saveGoogleWithSelection');
            Route::post('/save-x', [TrendingController::class, 'saveXWithSelection'])->name('saveXWithSelection');
            Route::post('/save-with-selection', [TrendingController::class, 'saveFromFeedWithSelection'])->name('saveFromFeedWithSelection');
            Route::post('/save-from-feed', [TrendingController::class, 'saveFromFeed'])->name('saveFromFeed');

            Route::post('/update-google-order', [TrendingController::class, 'updateGoogleOrder'])->name('updateGoogleOrder');
            Route::post('/update-x-order', [TrendingController::class, 'updateXOrder'])->name('updateXOrder');
            Route::post('/update-order', [TrendingController::class, 'updateOrder'])->name('updateOrder');

            Route::get('/getdaytrends', [TrendingController::class, 'getDayTrends'])->name('getdaytrends');
            Route::post('/edit/{date?}', [TrendingController::class, 'edit'])->name('edit');
            Route::get('/save-all-trends24', [TrendingController::class, 'saveAllTrends24'])->name('saveAllTrends24');
            Route::get('/save-all-google-trends', [TrendingController::class, 'saveAllGoogleTrends'])->name('saveAllGoogleTrends');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | User Management Routes - Admin Only (Non-tracked)
    |--------------------------------------------------------------------------
    */
    
    Route::middleware('role:admin')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        Route::patch('/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/{user}/reset-backup-password', [UserController::class, 'resetBackupPassword'])->name('reset-backup-password');
        Route::post('/{user}/test-backup-auth', [AuthController::class, 'testBackupAuth'])->name('test-backup-auth');
    });

    /*
    |--------------------------------------------------------------------------
    | Settings Management - Admin & Editor (Non-tracked)
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin,editor')->prefix('admin')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingsController::class, 'store'])->name('settings.store');
        Route::put('/settings/{id}', [SettingsController::class, 'update'])->name('settings.update');
        Route::delete('/settings/{id}', [SettingsController::class, 'destroy'])->name('settings.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Analytics Routes - Phase 3: Multi-Role Access
    |--------------------------------------------------------------------------
    */

    Route::prefix('admin/analytics')->name('analytics.')->group(function () {
        
        // Analytics Dashboard - Role-based access
        Route::get('/', [AnalyticsController::class, 'index'])
            ->middleware('role:admin,editor,verifikator1,verifikator2,viewer')
            ->name('index');
        
        // Analytics API Endpoints - Role-based access
        Route::get('/chart-data', [AnalyticsController::class, 'getChartData'])
            ->middleware('role:admin,editor,verifikator1,verifikator2,viewer')
            ->name('chart-data');
        
        Route::get('/real-time', [AnalyticsController::class, 'getRealTimeData'])
            ->middleware('role:admin,editor,verifikator1,verifikator2,viewer')
            ->name('real-time');
        
        // Export - Role-based access
        Route::get('/export', [AnalyticsController::class, 'export'])
            ->middleware('role:admin,editor,verifikator1,verifikator2,viewer')
            ->name('export');
        
        // Admin-only analytics management
        Route::middleware('role:admin')->group(function () {
            Route::post('/cleanup', [AnalyticsController::class, 'cleanup'])->name('cleanup');
            Route::post('/estimate-cleanup', [AnalyticsController::class, 'estimateCleanup'])->name('estimate-cleanup');
            Route::post('/optimize', [AnalyticsController::class, 'optimize'])->name('optimize');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Notification Management (Non-tracked)
    |--------------------------------------------------------------------------
    */
    
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifikasi.index');
    Route::get('/notifikasi/mark-as-read/{notifikasi}', [NotificationController::class, 'markAsRead'])->name('markAsRead');
    Route::post('/notifikasi/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('markAllAsRead');
    Route::get('/notifications/get', [NotificationController::class, 'getNotifications'])->name('get');
    Route::delete('/notifikasi/{notifikasi}', [NotificationController::class, 'destroy'])->name('destroy');
    Route::delete('/notifikasi/destroy-read', [NotificationController::class, 'destroyRead'])->name('destroyRead');
    Route::delete('/notifikasi/destroy-all', [NotificationController::class, 'destroyAll'])->name('destroyAll');

    /*
    |--------------------------------------------------------------------------
    | Badge Management Routes (Non-tracked)
    |--------------------------------------------------------------------------
    */
    
    Route::post('/reset-notification-badge', function () {
        session(['notification_badge_hidden' => true]);
        return response()->json(['success' => true]);
    })->name('reset.notification.badge');

    Route::post('/reset-rejected-badge', function () {
        session(['rejected_badge_hidden' => true]);
        return response()->json(['success' => true]);
    })->name('reset.rejected.badge');

    Route::get('/show-rejected-badge', function () {
        session()->forget('rejected_badge_hidden');
        return redirect()->back();
    })->name('show.rejected.badge');

    /*
    |--------------------------------------------------------------------------
    | API Endpoints (Non-tracked)
    |--------------------------------------------------------------------------
    */
    
    Route::get('/api/trending/selected', [TrendingController::class, 'getSelectedTrendings'])
        ->name('api.trending.selected');

    // Analytics duration update endpoint
    Route::post('/api/analytics/update-duration', [AnalyticsController::class, 'updateDuration'])
        ->name('api.analytics.update-duration');
});

/*
|--------------------------------------------------------------------------
| Development & Testing Routes (Non-tracked)
|--------------------------------------------------------------------------
*/

if (config('app.debug')) {
    
    // Development routes untuk admin
    Route::middleware(['auth', 'role:admin'])->prefix('dev')->name('dev.')->group(function () {
        Route::get('/test-api', function () {
            return view('dev.test-api', [
                'api_url' => config('app.ksp_api_url', 'https://layanan-api.ksp.go.id/index.php/login'),
                'api_key' => config('app.ksp_api_key', 'e7f0s9Cc9feBf61d49i3Kz5'),
            ]);
        })->name('test-api');
        
        Route::post('/test-api-connection', [AuthController::class, 'testApiConnection'])->name('test-api-connection');
        
        Route::get('/system-health', function () {
            return response()->json([
                'database' => \DB::connection()->getPdo() ? 'connected' : 'disconnected',
                'cache' => 'working',
                'session' => session()->isStarted() ? 'active' : 'inactive',
                'auth' => auth()->check() ? 'authenticated' : 'guest',
                'timestamp' => now(),
            ]);
        })->name('system-health');
    });

    // Analytics monitoring untuk development
    Route::middleware(['web', 'auth'])->get('/analytics-monitor', function () {
        $user = auth()->user();
        $userRole = $user->getHighestRoleName();
        
        // Base query berdasarkan role
        $baseQuery = \App\Models\UserAnalytics::activeUsersOnly();
        
        if ($userRole === 'admin') {
            // Admin bisa lihat semua
        } elseif (in_array($userRole, ['editor', 'verifikator1', 'verifikator2'])) {
            $baseQuery = $baseQuery->where('role_name', 'viewer');
        } else {
            $baseQuery = $baseQuery->where('user_id', $user->id);
        }
        
        $analytics = $baseQuery->with('user:id,name,username')
            ->latest()
            ->take(20)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'user' => $item->user ? $item->user->name : 'Unknown',
                    'role' => $item->role_name ?? 'Unknown',
                    'page_name' => $item->page_name,
                    'page_title' => $item->page_title,
                    'visited_at' => $item->visited_at->format('Y-m-d H:i:s'),
                    'duration' => $item->duration_seconds,
                    'ip_address' => $item->ip_address
                ];
            });
        
        $totalQuery = \App\Models\UserAnalytics::query();
        if ($userRole !== 'admin') {
            if (in_array($userRole, ['editor', 'verifikator1', 'verifikator2'])) {
                $totalQuery = $totalQuery->where('role_name', 'viewer');
            } else {
                $totalQuery = $totalQuery->where('user_id', $user->id);
            }
        }
        
        return response()->json([
            'user_role' => $userRole,
            'total_records' => $totalQuery->count(),
            'today_records' => $totalQuery->whereDate('visited_at', today())->count(),
            'unique_users_today' => $totalQuery->whereDate('visited_at', today())->distinct('user_id')->count(),
            'latest_activities' => $analytics
        ]);
    })->name('analytics.monitor');
}

/*
|--------------------------------------------------------------------------
| Static/Public Routes (Non-tracked)
|--------------------------------------------------------------------------
*/

Route::get('/up', function () {
    return response('OK', 200);
});

// Storage route for file access
Route::get('/storage/{path}', function ($path) {
    $filePath = storage_path('app/public/' . $path);
    if (file_exists($filePath)) {
        return response()->file($filePath);
    }
    abort(404);
})->where('path', '.*')->name('storage.local');
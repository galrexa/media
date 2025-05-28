<?php

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
use App\Http\Controllers\BadgeController;

/*
|--------------------------------------------------------------------------
| Web Routes - FIXED: Removed undefined middleware
|--------------------------------------------------------------------------
*/

// Home / Landing page - Fixed untuk API auth
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        
        // Cek apakah user masih aktif (jika ada field is_active)
        if (isset($user->is_active) && !$user->is_active) {
            auth()->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
            return redirect()->route('login')
                           ->with('error', 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.');
        }
        
        // Redirect ke home controller
        $request = request();
        return app(HomeController::class)->index($request);
    }
    return redirect()->route('login');
})->name('home');

// Dashboard sebagai landing page untuk admin/editor
Route::get('/dashboard-landing', function () {
    $user = auth()->user();
    if ($user->isAdmin() || $user->isEditor()) {
        return redirect()->route('dashboard.admin');
    } else {
        return redirect()->route('home');
    }
})->middleware('auth')->name('dashboard.landing');

// Route untuk halaman home
Route::get('/home', [HomeController::class, 'index'])
    ->name('home.index')
    ->middleware('auth');

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
| Settings Management - Admin & Editor
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,editor'])->prefix('admin')->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'store'])->name('settings.store');
    Route::put('/settings/{id}', [SettingsController::class, 'update'])->name('settings.update');
    Route::delete('/settings/{id}', [SettingsController::class, 'destroy'])->name('settings.destroy');
});

/*
|--------------------------------------------------------------------------
| Profile Management
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::get('/profile/password', [ProfileController::class, 'editPassword'])->name('profile.password');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
});

/*
|--------------------------------------------------------------------------
| Main Application Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    
    // Dashboard Routes
    Route::get('/dashboard', function () {
        $user = auth()->user();
        if ($user->isAdmin() || $user->isEditor()) {
            return redirect()->route('dashboard.admin');
        }
        return redirect()->route('home');
    })->name('dashboard');

    Route::get('/dashboard/admin', fn() => view('dashboard.admin'))
        ->middleware('role:admin,editor')
        ->name('dashboard.admin');

    
    /*
    |--------------------------------------------------------------------------
    | User Management Routes - Admin Only (FIXED: Removed undefined middleware)
    |--------------------------------------------------------------------------
    */
    
    // FIXED: Removed 'user.active' and 'audit.trail' middleware yang tidak terdefinisi
    Route::middleware(['auth', 'role:admin'])->prefix('users')->name('users.')->group(function () {
        // Standard CRUD routes
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        
        // Enhanced user management actions dengan backup password support
        Route::patch('/{user}/toggle-active', [UserController::class, 'toggleActive'])
             ->name('toggle-active');
        
        // NEW: Reset backup password untuk failover mechanism
        Route::post('/{user}/reset-backup-password', [UserController::class, 'resetBackupPassword'])
             ->name('reset-backup-password');
        
        // NEW: Test backup authentication (debug mode only)
        Route::post('/{user}/test-backup-auth', [AuthController::class, 'testBackupAuth'])
             ->name('test-backup-auth');
    });

    /*
    |--------------------------------------------------------------------------
    | Issue Management
    |--------------------------------------------------------------------------
    */
    
    Route::prefix('isu')->name('isu.')->group(function () {

        // Routes khusus untuk membuat dan mengedit isu
        Route::middleware('role:admin,editor,verifikator1,verifikator2')->group(function () {
            Route::get('/create', [IsuController::class, 'create'])->name('create');
            Route::post('/', [IsuController::class, 'store'])->name('store');
        });

        // Routes yang bisa diakses semua authenticated users
        Route::get('/', [IsuController::class, 'index'])->name('index');
        Route::get('/{isu}', [IsuController::class, 'show'])->name('show');
        Route::post('/isu/mass-action', [IsuController::class, 'massAction'])->name('massAction');

        // Route untuk history log isu - semua pengguna
        Route::get('/{isu}/history', [IsuController::class, 'history'])->name('history');

        // Routes yang dapat diakses oleh semua level (admin, editor, verifikator)
        Route::middleware('role:admin,editor,verifikator1,verifikator2')->group(function () {
            Route::get('/{isu}/edit', [IsuController::class, 'edit'])->name('edit');
            Route::put('/{isu}', [IsuController::class, 'update'])->name('update');
        });

        // Routes khusus untuk verifikator (penolakan isu)
        Route::middleware('role:admin,verifikator1,verifikator2')->group(function () {
            Route::post('/{isu}/penolakan', [IsuController::class, 'processPenolakan'])->name('penolakan');
        });

        // Hapus isu hanya bisa dilakukan admin atau editor (dengan status Draft)
        Route::middleware('role:admin,verifikator2,editor')
             ->delete('/{isu}', [IsuController::class, 'destroy'])
             ->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Document Management
    |--------------------------------------------------------------------------
    */
    
    Route::middleware('role:admin,editor')->prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        Route::get('/upload', [DocumentController::class, 'create'])->name('create');
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::get('/edit/{date?}', [DocumentController::class, 'edit'])->name('edit');
        Route::put('/{id}', [DocumentController::class, 'update'])->name('update');
    });

    /*
    |--------------------------------------------------------------------------
    | Trending Management
    |--------------------------------------------------------------------------
    */
    
    Route::prefix('trending')->name('trending.')->group(function () {
        // Public trending routes - semua authenticated users
        Route::get('/', [TrendingController::class, 'index'])->name('index');
        Route::get('/selected', [TrendingController::class, 'selected'])->name('selected');

        // Admin & Editor only trending management
        Route::middleware('role:admin,editor')->group(function () {
            // Routes untuk tambah manual trending
            Route::get('/manual/create', [TrendingController::class, 'createManual'])->name('manual.create');
            Route::post('/manual/store', [TrendingController::class, 'storeManual'])->name('manual.store');

            Route::get('/create', [TrendingController::class, 'create'])->name('create');
            Route::post('/', [TrendingController::class, 'store'])->name('store');
            Route::delete('/{trending}', [TrendingController::class, 'destroy'])->name('destroy');
            Route::put('/{trending}/toggle-selected', [TrendingController::class, 'toggleSelected'])->name('toggleSelected');

            Route::get('/manage-google', [TrendingController::class, 'manageGoogleSelected'])->name('manageGoogleSelected');
            Route::get('/manage-x', [TrendingController::class, 'manageXSelected'])->name('manageXSelected');

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
    | Preview Routes
    |--------------------------------------------------------------------------
    */
    
    Route::get('/preview-isu', [IsuController::class, 'preview'])->name('preview.isu');
    Route::get('/preview', [PreviewController::class, 'getPreview'])->name('preview.full');

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    */
    
    Route::get('/api/trending/selected', [TrendingController::class, 'getSelectedTrendings'])
        ->name('api.trending.selected');

    /*
    |--------------------------------------------------------------------------
    | Notification Management
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
    | Badge Management Routes
    |--------------------------------------------------------------------------
    */
    
    Route::post('/reset-notification-badge', function () {
        Session::put('notification_badge_hidden', true);
        return response()->json(['success' => true]);
    })->name('reset.notification.badge');

    Route::post('/reset-rejected-badge', function () {
        Session::put('rejected_badge_hidden', true);
        return response()->json(['success' => true]);
    })->name('reset.rejected.badge');

    Route::get('/show-rejected-badge', function () {
        Session::forget('rejected_badge_hidden');
        return redirect()->back();
    })->name('show.rejected.badge');
});

/*
|--------------------------------------------------------------------------
| Development Routes (Only in Debug Mode)
|--------------------------------------------------------------------------
*/

if (config('app.debug')) {
    Route::middleware(['auth', 'role:admin'])->prefix('dev')->name('dev.')->group(function () {
        Route::get('/test-api', function () {
            return view('dev.test-api', [
                'api_url' => config('app.ksp_api_url', 'https://layanan-api.ksp.go.id/index.php/login'),
                'api_key' => config('app.ksp_api_key', 'e7f0s9Cc9feBf61d49i3Kz5'),
            ]);
        })->name('test-api');
        
        Route::post('/test-api-connection', [AuthController::class, 'testApiConnection'])
             ->name('test-api-connection');
        
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
}
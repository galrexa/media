<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IsuController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\TrendingController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Home / Landing page 
Route::get('/', function () { 
    if (auth()->check()) { 
        // Alih-alih mengarahkan ke dashboard, semua user diarahkan ke home
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
Route::get('/home', [HomeController::class, 'index'])->name('home.index')->middleware('auth');

// Autentikasi
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Routes yang memerlukan autentikasi
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        $user = auth()->user();
        if ($user->isAdmin() || $user->isEditor()) {
            return redirect()->route('dashboard.admin');
        }
        return redirect()->route('home');
    })->name('dashboard');

    Route::get('/dashboard/admin', fn() => view('dashboard.admin'))
        ->middleware('role:admin,editor')->name('dashboard.admin');

    // User Management - Admin Only
    Route::middleware('role:admin')->resource('users', UserController::class);

    // Isu Management
    Route::prefix('isu')->name('isu.')->group(function () {
        Route::get('/', [IsuController::class, 'index'])->name('index');
        
        Route::middleware('role:admin,editor')->group(function () {
            Route::get('/create', [IsuController::class, 'create'])->name('create');
            Route::post('/', [IsuController::class, 'store'])->name('store');
            Route::get('/{isu}/edit', [IsuController::class, 'edit'])->name('edit');
            Route::put('/{isu}', [IsuController::class, 'update'])->name('update');
            Route::delete('/{isu}', [IsuController::class, 'destroy'])->name('destroy');
        });
        
        // Pindahkan rute dengan parameter ke bagian bawah
        Route::get('/{isu}', [IsuController::class, 'show'])->name('show');
    });

    // Document Management
    Route::middleware('role:admin,editor')->prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        Route::get('/upload', [DocumentController::class, 'create'])->name('create');
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::get('/edit/{date?}', [DocumentController::class, 'edit'])->name('edit');
        Route::put('/{id}', [DocumentController::class, 'update'])->name('update');
    });

    // Trending Management
    Route::prefix('trending')->name('trending.')->group(function () {
        Route::get('/', [TrendingController::class, 'index'])->name('index');
        Route::get('/selected', [TrendingController::class, 'selected'])->name('selected');

        Route::middleware('role:admin,editor')->group(function () {
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

    // Preview
    Route::get('/preview-isu', [IsuController::class, 'preview'])->name('preview.isu');
    Route::get('/preview', [PreviewController::class, 'getPreview'])->name('preview.full');

    // Public API-style endpoint (move to api.php if needed)
    Route::get('/api/trending/selected', [TrendingController::class, 'getSelectedTrendings'])
        ->name('api.trending.selected');
});
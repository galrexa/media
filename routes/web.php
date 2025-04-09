<?php
// routes/web.php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IsuController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\TrendingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Halaman beranda
Route::get('/', function () {
    if (auth()->check()) {
        // Jika sudah login, arahkan sesuai peran
        if (auth()->user()->isAdmin()) {
            return redirect()->route('dashboard.admin');
        } elseif (auth()->user()->isEditor()) {
            return redirect()->route('dashboard.editor');
        } else {
            return app()->call([app(HomeController::class), 'index']); // Viewer melihat halaman beranda
        }
    }
    // Jika belum login, arahkan ke login
    return redirect()->route('login');
})->name('home');

// Route autentikasi
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


// Route yang memerlukan autentikasi
Route::middleware(['auth'])->group(function () {
    
    // Halaman dashboard berdasarkan role
    Route::get('/dashboard', function () {
        if (auth()->user()->isAdmin()) {
            return redirect()->route('dashboard.admin');
        } elseif (auth()->user()->isEditor()) {
            return redirect()->route('dashboard.editor');
        } else {
            return redirect()->route('home'); // Viewer diarahkan ke halaman beranda
        }
    })->name('dashboard');

    // Tambahkan route ini di dalam grup middleware auth
    Route::get('/', [HomeController::class, 'index'])->name('home');

    // Dashboard admin
    Route::get('/dashboard/admin', function () {
        return view('dashboard.admin');
    })->middleware('role:admin')->name('dashboard.admin');

    // Routes untuk manajemen pengguna (hanya admin)
    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::resource('users', UserController::class);
    });

    // Dashboard editor
    Route::get('/dashboard/editor', function () {
        return view('dashboard.editor');
    })->middleware('role:admin,editor')->name('dashboard.editor');

    // Route yang dapat diakses semua user yang login
    Route::get('/isu', [IsuController::class, 'index'])->name('isu.index');
    Route::get('/trending', [TrendingController::class, 'index'])->name('trending.index');
    
    // PINDAHKAN route test sebelum route parameter trending/{trending}
    Route::get('/trending/test', [TrendingController::class, 'test'])->name('trending.test');
    
    Route::get('/preview', [IsuController::class, 'preview'])->name('preview');


    // Route yang hanya dapat diakses admin dan editor - PENTING: create harus sebelum wildcard {isu}
    Route::middleware(['role:admin,editor'])->group(function () {
        Route::get('/isu/create', [IsuController::class, 'create'])->name('isu.create');
        Route::post('/isu', [IsuController::class, 'store'])->name('isu.store');
        Route::get('/isu/{isu}/edit', [IsuController::class, 'edit'])->name('isu.edit');
        Route::put('/isu/{isu}', [IsuController::class, 'update'])->name('isu.update');
        Route::delete('/isu/{isu}', [IsuController::class, 'destroy'])->name('isu.destroy');

        
        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/upload', [DocumentController::class, 'create'])->name('documents.create');
        Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::get('/documents/edit/{date?}', [DocumentController::class, 'edit'])->name('documents.edit');
        Route::put('/documents/{id}', [DocumentController::class, 'update'])->name('documents.update');
        Route::get('/trending/create', [TrendingController::class, 'create'])->name('trending.create');
        Route::post('/trending', [TrendingController::class, 'store'])->name('trending.store');
        Route::post('/trending/edit/{date?}', [TrendingController::class, 'edit'])->name('trending.edit');
        Route::post('/trending/save-from-feed', [TrendingController::class, 'saveFromFeed'])->name('trending.saveFromFeed');
        
        // Pastikan route ini SETELAH route /trending/test
        Route::delete('/trending/{trending}', [TrendingController::class, 'destroy'])->name('trending.destroy');

        // routes/web.php
        Route::get('/preview', [PreviewController::class, 'getPreview'])->name('preview');
    });
    
    // Route detail isu harus setelah create
    Route::get('/isu/{isu}', [IsuController::class, 'show'])->name('isu.show');
});
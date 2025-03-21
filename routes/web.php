<?php
// routes/web.php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IsuController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\TrendingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Halaman beranda
Route::get('/', [HomeController::class, 'index'])->name('home');

// Route autentikasi
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
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
            return redirect()->route('dashboard.viewer');
        }
    })->name('dashboard');

    // Dashboard admin
    Route::get('/dashboard/admin', function () {
        return view('dashboard.admin');
    })->middleware('role:admin')->name('dashboard.admin');

    // Dashboard editor
    Route::get('/dashboard/editor', function () {
        return view('dashboard.editor');
    })->middleware('role:admin,editor')->name('dashboard.editor');

    // Dashboard viewer
    Route::get('/dashboard/viewer', function () {
        return view('dashboard.viewer');
    })->name('dashboard.viewer');

    // Route yang dapat diakses semua user yang login
    Route::get('/isu', [IsuController::class, 'index'])->name('isu.index');
    //Route::get('/images', [TrendingController::class, 'index'])->name('images.index');
    Route::get('/trending', [TrendingController::class, 'index'])->name('trending.index');
    
    // Route yang hanya dapat diakses admin dan editor - PENTING: create harus sebelum wildcard {isu}
    Route::middleware(['role:admin,editor'])->group(function () {
        Route::get('/isu/create', [IsuController::class, 'create'])->name('isu.create');
        Route::post('/isu', [IsuController::class, 'store'])->name('isu.store');
        Route::get('/isu/{isu}/edit', [IsuController::class, 'edit'])->name('isu.edit');
        Route::put('/isu/{isu}', [IsuController::class, 'update'])->name('isu.update');
        Route::delete('/isu/{isu}', [IsuController::class, 'destroy'])->name('isu.destroy');
        Route::get('/images', [ImageController::class, 'index'])->name('images.index');
        Route::get('/images/upload', [ImageController::class, 'create'])->name('images.create');
        Route::post('/images', [ImageController::class, 'store'])->name('images.store');
        Route::get('/images/edit/{date?}', [ImageController::class, 'edit'])->name('images.edit');
        Route::put('/images/{id}', [ImageController::class, 'update'])->name('images.update');
        Route::get('/trending/create', [TrendingController::class, 'create'])->name('trending.create');
        Route::post('/trending', [TrendingController::class, 'store'])->name('trending.store');
        Route::post('/trending/edit/{date?}', [TrendingController::class, 'edit'])->name('trending.edit');
        //Route::get('/trending/create', [TrendingController::class, 'create'])->name('trending.create');
        //Route::post('/trending', [TrendingController::class, 'store'])->name('trending.store');
        //Route::get('/trending/{trending}/edit', [TrendingController::class, 'edit'])->name('trending.edit');
        //Route::put('/trending/{trending}', [TrendingController::class, 'update'])->name('trending.update');
        Route::delete('/trending/{trending}', [TrendingController::class, 'destroy'])->name('trending.destroy');

    });
    
    // Route detail isu harus setelah create
    Route::get('/isu/{isu}', [IsuController::class, 'show'])->name('isu.show');
});
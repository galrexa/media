<!-- resources/views/home.blade.php -->
@extends('layouts.app')

@section('title', 'Beranda - Kurasi Berita Terkini')

@section('content')
<div class="container main-container">
    <h1 class="text-center mb-4 main-title">KURASI BERITA TERKINI</h1>

    <!-- Tanggal Navigation -->
    <div class="date-nav mb-4">
        <div class="d-flex flex-nowrap overflow-auto">
            <!-- Kotak Hari Ini -->
            <div class="p-2">
                <a href="{{ route('home') }}" class="day-box current-day {{ $selectedDay == 0 ? 'active' : '' }}">
                    <div class="day-title">HARI INI</div>
                    <div class="day-date">{{ Carbon\Carbon::now()->format('d F Y') }}</div>
                </a>
            </div>
            
            <!-- Tanggal-tanggal lainnya -->
            @foreach($dates as $dateInfo)
                <!-- Tampilkan semua tanggal kecuali hari ini yang sudah punya kotak sendiri -->
                @if(!($dateInfo['is_today'] ?? false))
                    <div class="p-2">
                        <a href="{{ route('home', ['day' => 1]) }}" 
                        class="date-btn {{ $selectedDay == 1 ? 'active' : '' }}">
                            {{ Carbon\Carbon::now()->subDays(1)->format('d F') }}
                        </a>
                    </div>
                @endif
            @endforeach
            
            <!-- Tombol navigasi kiri/kanan -->
            <div class="ms-auto d-flex">
                @if($hasPrevPage)
                    <div class="p-2">
                        <a href="{{ route('home', ['page' => $page - 1]) }}" class="nav-btn">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </div>
                @endif
                
                @if($hasNextPage)
                    <div class="p-2">
                        <a href="{{ route('home', ['page' => $page + 1]) }}" class="nav-btn">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Isu Strategis Section -->
    <div class="row mb-4">
        <div class="col-lg-5 col-md-12 mb-3 mb-lg-0">
            <div class="image-container main-image-container">
                @if($dailyImages && $dailyImages->image_1)
                    <img src="{{ asset('storage/' . $dailyImages->image_1) }}" alt="Gambar Utama" class="img-fluid w-100 h-100 object-fit-cover">
                    <div class="image-overlay">
                        <h3 class="overlay-title">Headline Terkini</h3>
                    </div>
                @else
                    <div class="placeholder-image d-flex align-items-center justify-content-center">
                        <i class="fas fa-newspaper fa-3x mb-3"></i>
                        <p class="text-center mb-0">Headline Terkini</p>
                    </div>
                @endif
            </div>
        </div>
        <div class="col-lg-7 col-md-12">
            <div class="card h-100 card-custom">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-star me-2"></i>Isu Strategis</h5>
                    <div class="navigation-buttons">
                        <button class="btn-nav prev-isu-slide">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="btn-nav next-isu-slide">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="isu-slider-container">
                        <div class="isu-slider">
                            <!-- Halaman pertama -->
                            <div class="isu-slide active">
                                <div class="list-group list-group-flush">
                                    @forelse($isuStrategis->take(5) as $index => $isu)
                                        <div class="list-group-item border-bottom py-3">
                                            <a href="{{ route('isu.show', $isu) }}" class="isu-link">
                                                <span class="isu-number">{{ $index + 1 }}</span> {{ $isu->judul }}
                                            </a>
                                        </div>
                                    @empty
                                        <div class="list-group-item">Tidak ada isu strategis untuk ditampilkan</div>
                                    @endforelse
                                </div>
                            </div>
                            
                            <!-- Halaman kedua (jika ada) -->
                            @if(count($isuStrategis) > 5)
                                <div class="isu-slide">
                                    <div class="list-group list-group-flush">
                                        @foreach($isuStrategis->skip(5)->take(5) as $index => $isu)
                                            <div class="list-group-item border-bottom py-3">
                                                <a href="{{ route('isu.show', $isu) }}" class="isu-link">
                                                    <span class="isu-number">{{ $index + 6 }}</span> {{ $isu->judul }}
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Halaman ketiga (jika ada) -->
                            @if(count($isuStrategis) > 10)
                                <div class="isu-slide">
                                    <div class="list-group list-group-flush">
                                        @foreach($isuStrategis->skip(10)->take(5) as $index => $isu)
                                            <div class="list-group-item border-bottom py-3">
                                                <a href="{{ route('isu.show', $isu) }}" class="isu-link">
                                                    <span class="isu-number">{{ $index + 11 }}</span> {{ $isu->judul }}
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-footer p-0">
                    <div class="slider-indicators d-flex justify-content-start p-2">
                        @for ($i = 0; $i < ceil(count($isuStrategis) / 5); $i++)
                            <span class="slider-dot {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}"></span>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Isu Lainnya Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-custom">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>Isu Lainnya</h5>
                    <div class="navigation-buttons">
                        <button class="btn-nav prev-lainnya-slide">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="btn-nav next-lainnya-slide">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="lainnya-slider-container">
                        <div class="lainnya-slider">
                            <!-- Halaman pertama -->
                            <div class="lainnya-slide active">
                                <div class="row">
                                    @forelse($isuLainnya->take(6) as $index => $isu)
                                        <div class="col-md-4 mb-3">
                                            <div class="isu-card">
                                                <a href="{{ route('isu.show', $isu) }}" class="isu-lainnya-link">
                                                    <span class="isu-badge">{{ $index + 1 }}</span>
                                                    <span class="isu-title">{{ $isu->judul }}</span>
                                                </a>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12">Tidak ada isu lainnya untuk ditampilkan</div>
                                    @endforelse
                                </div>
                            </div>
                            
                            <!-- Halaman kedua (jika ada) -->
                            @if(count($isuLainnya) > 6)
                                <div class="lainnya-slide">
                                    <div class="row">
                                        @foreach($isuLainnya->skip(6)->take(6) as $index => $isu)
                                            <div class="col-md-4 mb-3">
                                                <div class="isu-card">
                                                    <a href="{{ route('isu.show', $isu) }}" class="isu-lainnya-link">
                                                        <span class="isu-badge">{{ $index + 7 }}</span>
                                                        <span class="isu-title">{{ $isu->judul }}</span>
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-footer p-0">
                    <div class="slider-indicators d-flex justify-content-start p-2">
                        @for ($i = 0; $i < ceil(count($isuLainnya) / 6); $i++)
                            <span class="slider-dot lainnya-dot {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}"></span>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Trending Section -->
    <div class="row mb-4">
        <div class="col-md-5">
            <div class="image-container thumbnail-image-container">
                @if($dailyImages && $dailyImages->image_2)
                    <img src="{{ asset('storage/' . $dailyImages->image_2) }}" alt="Gambar Trending" class="img-fluid w-100 h-100 object-fit-cover">
                    <div class="image-overlay">
                        <h3 class="overlay-title">Trending Saat Ini</h3>
                    </div>
                @else
                    <div class="placeholder-image d-flex align-items-center justify-content-center">
                        <i class="fas fa-chart-line fa-3x mb-3"></i>
                        <p class="text-center mb-0">Trending Saat Ini</p>
                    </div>
                @endif
            </div>
        </div>
        <div class="col-md-7">
            <div class="row h-100">
                <!-- Trending Google -->
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="card h-100 card-custom trending-card">
                        <div class="card-header google-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fab fa-google me-2"></i>Trend Google</h5>
                            <div class="navigation-buttons">
                                <button class="btn-nav prev-google-slide">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button class="btn-nav next-google-slide">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="google-slider-container">
                                <div class="google-slider">
                                    <!-- Halaman pertama -->
                                    <div class="google-slide active">
                                        <div class="list-group list-group-flush">
                                            @forelse($trendingGoogle->take(3) as $index => $trend)
                                                <div class="list-group-item border-bottom py-3">
                                                    <a href="{{ $trend->url }}" target="_blank" class="trend-link">
                                                        <span class="trend-number">{{ $index + 1 }}</span>
                                                        <span class="trend-title">{{ $trend->judul }}</span>
                                                    </a>
                                                </div>
                                            @empty
                                                <div class="list-group-item">Tidak ada trending Google</div>
                                            @endforelse
                                        </div>
                                    </div>
                                    
                                    <!-- Halaman kedua (jika ada) -->
                                    @if(count($trendingGoogle) > 3)
                                        <div class="google-slide">
                                            <div class="list-group list-group-flush">
                                                @foreach($trendingGoogle->skip(3)->take(3) as $index => $trend)
                                                    <div class="list-group-item border-bottom py-3">
                                                        <a href="{{ $trend->url }}" target="_blank" class="trend-link">
                                                            <span class="trend-number">{{ $index + 4 }}</span>
                                                            <span class="trend-title">{{ $trend->judul }}</span>
                                                        </a>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="card-footer p-0">
                            <div class="slider-indicators d-flex justify-content-start p-2">
                                @for ($i = 0; $i < ceil(count($trendingGoogle) / 3); $i++)
                                    <span class="slider-dot google-dot {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}"></span>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Trending X -->
                <div class="col-md-6">
                    <div class="card h-100 card-custom trending-card">
                        <div class="card-header x-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fab fa-x-twitter me-2"></i>Trend X</h5>
                            <div class="navigation-buttons">
                                <button class="btn-nav prev-x-slide">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button class="btn-nav next-x-slide">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="x-slider-container">
                                <div class="x-slider">
                                    <!-- Halaman pertama -->
                                    <div class="x-slide active">
                                        <div class="list-group list-group-flush">
                                            @forelse($trendingX->take(3) as $index => $trend)
                                                <div class="list-group-item border-bottom py-3">
                                                    <a href="{{ $trend->url }}" target="_blank" class="trend-link">
                                                        <span class="trend-number">{{ $index + 1 }}</span>
                                                        <span class="trend-title">{{ $trend->judul }}</span>
                                                    </a>
                                                </div>
                                            @empty
                                                <div class="list-group-item">Tidak ada trending X</div>
                                            @endforelse
                                        </div>
                                    </div>
                                    
                                    <!-- Halaman kedua (jika ada) -->
                                    @if(count($trendingX) > 3)
                                        <div class="x-slide">
                                            <div class="list-group list-group-flush">
                                                @foreach($trendingX->skip(3)->take(3) as $index => $trend)
                                                    <div class="list-group-item border-bottom py-3">
                                                        <a href="{{ $trend->url }}" target="_blank" class="trend-link">
                                                            <span class="trend-number">{{ $index + 4 }}</span>
                                                            <span class="trend-title">{{ $trend->judul }}</span>
                                                        </a>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="card-footer p-0">
                            <div class="slider-indicators d-flex justify-content-start p-2">
                                @for ($i = 0; $i < ceil(count($trendingX) / 3); $i++)
                                    <span class="slider-dot x-dot {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}"></span>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Banner Section - Gambar dengan resolusi tetap -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="image-container banner-image-container">
                @if($dailyImages && $dailyImages->image_3)
                    <img src="{{ asset('storage/' . $dailyImages->image_3) }}" alt="Gambar Banner" class="img-fluid w-100 h-100 object-fit-cover">
                @else
                    <div class="banner-placeholder d-flex align-items-center justify-content-center">
                        <div class="text-center">
                            <i class="fas fa-ad fa-3x mb-3"></i>
                            <p class="mb-0">Banner Promosi</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
<style>
    /* Styling dasar dan font */
    body {
        font-family: 'Roboto', sans-serif;
        background-color: #f8f9fa;
        color: #212529;
    }
    
    /* Styling untuk trending links */
    .trending-card {
        height: 100%;
    }
    
    .trend-link {
        color: #333;
        display: flex;
        align-items: center;
        text-decoration: none;
        transition: color 0.3s ease;
    }
    
    .trend-link:hover {
        color: #1a237e;
    }
    
    .trend-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        background-color: #1a237e;
        color: white;
        border-radius: 4px;
        margin-right: 10px;
        font-weight: 500;
        font-size: 0.8rem;
    }
    
    .trend-title {
        font-weight: 400;
        line-height: 1.4;
    }
    
    /* Styling untuk container gambar */
    .image-container {
        position: relative;
        overflow: hidden;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .image-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 100%);
        padding: 20px;
        color: white;
    }
    
    .overlay-title {
        margin: 0;
        font-weight: 600;
        text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
    }
    
    .main-image-container {
        height: 400px;
        width: 100%;
        border-radius: 10px;
    }
    
    .main-image-container img {
        transition: transform 0.5s ease;
    }
    
    .main-image-container:hover img {
        transform: scale(1.05);
    }
    
    .thumbnail-image-container {
        height: 300px;
        width: 100%;
    }
    
    .banner-image-container {
        height: 300px;
        width: 100%;
        margin-top: 1rem;
    }
    
    .placeholder-image {
        width: 100%;
        height: 100%;
        background-color: #e0e0e0;
        color: #757575;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        border-radius: 10px;
    }
    
    .banner-placeholder {
        height: 300px;
        background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
    }
    
    /* Animasi */
    .animate-fade-in {
        animation: fadeIn 0.5s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    /* Media Queries */
    @media (max-width: 992px) {
        .main-image-container {
            height: 350px;
        }
        
        .thumbnail-image-container {
            height: 250px;
        }
    }
    
    @media (max-width: 768px) {
        .main-title {
            font-size: 1.8rem;
        }
        
        .day-box {
            width: 140px;
            padding: 12px;
        }
        
        .day-title {
            font-size: 1.2rem;
        }
        
        .day-date {
            font-size: 0.9rem;
        }
        
        .main-image-container {
            height: 300px;
            margin-bottom: 1rem;
        }
        
        .thumbnail-image-container {
            height: 220px;
            margin-bottom: 1rem;
        }
        
        .banner-image-container {
            height: 200px;
        }
    }
    
    @media (max-width: 576px) {
        .main-title {
            font-size: 1.5rem;
        }
        
        .day-box {
            width: 120px;
            padding: 10px;
        }
        
        .day-title {
            font-size: 1rem;
        }
        
        .day-date {
            font-size: 0.8rem;
        }
        
        .main-image-container {
            height: 250px;
        }
        
        .thumbnail-image-container {
            height: 200px;
        }
        
        .banner-image-container {
            height: 150px;
        }
    }
    
    .main-container {
        padding-top: 1.5rem;
        padding-bottom: 1.5rem;
    }
    
    .main-title {
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
        color: #1a237e;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        margin-bottom: 1.5rem;
        letter-spacing: 1px;
        padding-bottom: 0.5rem;
        position: relative;
        display: inline-block;
    }
    
    .main-title:after {
        content: '';
        position: absolute;
        width: 80px;
        height: 4px;
        background-color: #ffc107;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        border-radius: 2px;
    }
    
    /* Styling untuk card */
    .card-custom {
        border: none;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;
    }
    
    .card-custom:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1), 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .card-header {
        background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%);
        color: white;
        border-bottom: none;
        padding: 1rem 1.25rem;
        font-weight: 500;
    }
    
    .google-header {
        background: linear-gradient(135deg, #4285F4 0%, #34A853 100%);
    }
    
    .x-header {
        background: linear-gradient(135deg, #14171A 0%, #657786 100%);
    }
    
    .card-body {
        padding: 1.25rem;
    }
    
    .card-footer {
        background-color: rgba(0,0,0,0.02);
        border-top: none;
    }
    
    /* Styling untuk kotak Hari Ini */
    .day-box {
        display: block;
        width: 160px;
        padding: 15px;
        text-align: center;
        text-decoration: none;
        color: white;
        background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .day-box:hover {
        background: linear-gradient(135deg, #F57C00 0%, #E65100 100%);
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    }
    
    .day-box.active {
        background: linear-gradient(135deg, #E65100 0%, #BF360C 100%);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .day-title {
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 5px;
        font-family: 'Montserrat', sans-serif;
    }
    
    .day-date {
        font-size: 1rem;
    }
    
    /* Styling untuk tombol tanggal */
    .date-btn {
        display: inline-block;
        padding: 10px 16px;
        border-radius: 8px;
        background-color: #e0e0e0;
        color: #424242;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
        min-width: 100px;
        text-align: center;
    }
    
    .date-btn:hover {
        background-color: #bdbdbd;
        color: #212121;
    }
    
    .date-btn.active {
        background-color: #FFC107;
        color: #212121;
    }
    
    /* Styling untuk navigasi tanggal */
    .date-nav {
        overflow-x: auto;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        position: relative;
        scrollbar-width: thin;
        scrollbar-color: #ffc107 #f0f0f0;
    }
    
    .date-nav::-webkit-scrollbar {
        height: 5px;
    }
    
    .date-nav::-webkit-scrollbar-track {
        background: #f0f0f0;
        border-radius: 10px;
    }
    
    .date-nav::-webkit-scrollbar-thumb {
        background-color: #ffc107;
        border-radius: 10px;
    }
    
    .nav-btn {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background-color: #f0f0f0;
        color: #616161;
        border: none;
        transition: all 0.3s ease;
        text-decoration: none;
    }
    
    .nav-btn:hover {
        background-color: #e0e0e0;
        color: #212121;
    }

    /* Styling untuk semua slider container */
    .isu-slider-container,
    .lainnya-slider-container,
    .google-slider-container,
    .x-slider-container {
        position: relative;
        overflow: hidden;
    }

    /* Styling untuk semua slider */
    .isu-slider,
    .lainnya-slider,
    .google-slider,
    .x-slider {
        display: flex;
        transition: transform 0.4s ease-in-out;
    }

    /* Styling untuk semua slide */
    .isu-slide,
    .lainnya-slide,
    .google-slide,
    .x-slide {
        min-width: 100%;
        display: none;
    }

    .isu-slide.active,
    .lainnya-slide.active,
    .google-slide.active,
    .x-slide.active {
        display: block;
    }

    /* Styling untuk tombol navigasi */
    .navigation-buttons {
        display: flex;
    }

    .btn-nav {
        cursor: pointer;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: rgba(255,255,255,0.2);
        color: white;
        border: none;
        border-radius: 4px;
        transition: all 0.3s ease;
        margin-left: 5px;
    }

    .btn-nav:hover {
        background-color: rgba(255,255,255,0.4);
    }

    .btn-nav:disabled {
        cursor: not-allowed;
        opacity: 0.5;
    }

    /* Styling untuk indikator slider */
    .slider-indicators {
        display: flex;
        gap: 8px;
        padding: 10px;
    }

    .slider-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #e0e0e0;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .slider-dot:hover {
        background-color: #bdbdbd;
    }

    .slider-dot.active {
        background-color: #1a237e;
        transform: scale(1.2);
    }

    /* Styling untuk isu strategis list */
    .list-group-item {
        border-left: none;
        border-right: none;
        padding: 0.8rem 0;
        transition: background-color 0.3s ease;
    }

    .list-group-item:hover {
        background-color: rgba(0,0,0,0.02);
    }

    .isu-link {
        color: #333;
        display: flex;
        align-items: center;
        text-decoration: none;
        transition: color 0.3s ease;
        font-weight: 400;
    }

    .isu-link:hover {
        color: #1a237e;
    }

    .isu-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        background-color: #1a237e;
        color: white;
        border-radius: 50%;
        margin-right: 12px;
        font-weight: 500;
        font-size: 0.9rem;
    }

    /* Styling untuk isu lainnya cards */
    .isu-card {
        padding: 0.8rem;
        border-radius: 8px;
        background-color: #f0f0f0;
        height: 100%;
        transition: all 0.3s ease;
    }
    
    .isu-card:hover {
        background-color: #e0e0e0;
        transform: translateY(-3px);
    }
    
    .isu-lainnya-link {
        color: #333;
        text-decoration: none;
        display: flex;
        align-items: flex-start;
    }
    
    .isu-lainnya-link:hover {
        color: #1a237e;
    }
    
    .isu-badge {
        background-color: #1a237e;
        color: white;
        display: inline-block;
        width: 24px;
        height: 24px;
        line-height: 24px;
        text-align: center;
        border-radius: 4px;
        margin-right: 10px;
        flex-shrink: 0;
    }
    
    .isu-title {
        font-weight: 400;
        line-height: 1.4;
    }
<!-- resources/views/home.blade.php -->
@extends('layouts.app')

@section('title', 'Beranda - Kurasi Berita Terkini')

@section('content')
<h1 class="text-center mb-4">KURASI BERITA TERKINI</h1>

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
        
        <!-- Tampilkan semua tanggal tanpa filter -->
        @foreach($dates as $dateInfo)
            @if(!($dateInfo['is_today'] ?? false))
                <div class="p-2">
                    <a href="{{ route('home', ['day' => $dateInfo['day']]) }}" 
                       class="btn {{ $dateInfo['active'] ? 'btn-warning' : 'btn-info' }} px-4">
                        {{ $dateInfo['display_date'] }}
                    </a>
                </div>
            @endif
        @endforeach
        
        <!-- Tombol navigasi kiri/kanan -->
        <div class="ms-auto d-flex">
            @if($hasPrevPage)
                <div class="p-2">
                    <a href="{{ route('home', ['page' => $page - 1]) }}" class="btn btn-light px-3">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </div>
            @endif
            
            @if($hasNextPage)
                <div class="p-2">
                    <a href="{{ route('home', ['page' => $page + 1]) }}" class="btn btn-light px-3">
                        <i class="bi bi-chevron-right"></i>
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
            @else
                <div class="bg-secondary p-3 h-100 d-flex align-items-center justify-content-center">
                    <p class="text-center mb-0">Placeholder Gambar</p>
                </div>
            @endif
        </div>
    </div>
    <div class="col-lg-7 col-md-12">
        <div class="card h-100">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Isu Strategis</h5>
                <div class="navigation-buttons">
                    <button class="btn btn-sm btn-light prev-isu-slide">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button class="btn btn-sm btn-light next-isu-slide">
                        <i class="bi bi-chevron-right"></i>
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
                                    <div class="list-group-item border-bottom py-2">
                                        <a href="{{ route('isu.show', $isu) }}" class="text-decoration-none text-primary">
                                            {{ $index + 1 }}. {{ $isu->judul }}
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
                                        <div class="list-group-item border-bottom py-2">
                                            <a href="{{ route('isu.show', $isu) }}" class="text-decoration-none text-primary">
                                                {{ $index + 6 }}. {{ $isu->judul }}
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
                                        <div class="list-group-item border-bottom py-2">
                                            <a href="{{ route('isu.show', $isu) }}" class="text-decoration-none text-primary">
                                                {{ $index + 11 }}. {{ $isu->judul }}
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
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Isu Lainnya</h5>
                <div class="navigation-buttons">
                    <button class="btn btn-sm btn-light prev-lainnya-slide">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button class="btn btn-sm btn-light next-lainnya-slide">
                        <i class="bi bi-chevron-right"></i>
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
                                    <div class="col-md-4 mb-2">
                                        <a href="{{ route('isu.show', $isu) }}" class="text-decoration-none text-dark">
                                            {{ $index + 1 }}. {{ $isu->judul }}
                                        </a>
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
                                        <div class="col-md-4 mb-2">
                                            <a href="{{ route('isu.show', $isu) }}" class="text-decoration-none text-dark">
                                                {{ $index + 7 }}. {{ $isu->judul }}
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
            @else
                <div class="bg-secondary p-3 h-100 d-flex align-items-center justify-content-center">
                    <p class="text-center mb-0">Placeholder Gambar 2</p>
                </div>
            @endif
        </div>
    </div>
    <div class="col-md-7">
        <div class="row h-100">
            <!-- Trending Google -->
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="card h-100">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Trend Google</h5>
                        <div class="navigation-buttons">
                            <button class="btn btn-sm btn-light prev-google-slide">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <button class="btn btn-sm btn-light next-google-slide">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="google-slider-container">
                            <div class="google-slider">
                                <!-- Halaman pertama -->
                                <div class="google-slide active">
                                    <div class="list-group list-group-flush">
                                        @forelse($trendingGoogle->take(5) as $index => $trend)
                                            <div class="list-group-item border-bottom py-2">
                                                <a href="{{ $trend->url }}" target="_blank" class="text-decoration-none">
                                                    {{ $index + 1 }}. {{ $trend->judul }}
                                                </a>
                                            </div>
                                        @empty
                                            <div class="list-group-item">Tidak ada trending Google</div>
                                        @endforelse
                                    </div>
                                </div>
                                
                                <!-- Halaman kedua (jika ada) -->
                                @if(count($trendingGoogle) > 5)
                                    <div class="google-slide">
                                        <div class="list-group list-group-flush">
                                            @foreach($trendingGoogle->skip(5)->take(5) as $index => $trend)
                                                <div class="list-group-item border-bottom py-2">
                                                    <a href="{{ $trend->url }}" target="_blank" class="text-decoration-none">
                                                        {{ $index + 46 }}. {{ $trend->judul }}
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
                <div class="card h-100">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Trend X</h5>
                        <div class="navigation-buttons">
                            <button class="btn btn-sm btn-light prev-x-slide">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <button class="btn btn-sm btn-light next-x-slide">
                                <i class="bi bi-chevron-right"></i>
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
                                            <div class="list-group-item border-bottom py-2">
                                                <a href="{{ $trend->url }}" target="_blank" class="text-decoration-none">
                                                    {{ $index + 1 }}. {{ $trend->judul }}
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
                                                <div class="list-group-item border-bottom py-2">
                                                    <a href="{{ $trend->url }}" target="_blank" class="text-decoration-none">
                                                        {{ $index + 4 }}. {{ $trend->judul }}
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
                <div class="bg-secondary p-5 d-flex align-items-center justify-content-center">
                    <p class="text-center mb-0">Placeholder Image 3</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
    /* Styling untuk kotak Hari Ini */
    .day-box {
        display: block;
        width: 160px;
        padding: 15px;
        text-align: center;
        text-decoration: none;
        color: white;
        background-color: #D4AF37; /* Warna emas */
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }
    
    .day-box:hover {
        background-color: #C5A028;
        color: white;
        transform: translateY(-2px);
    }
    
    .day-box.active {
        background-color: #B8860B; /* Warna emas lebih gelap saat aktif */
    }
    
    .day-title {
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .day-date {
        font-size: 1rem;
    }
    
    /* Styling untuk navigasi tanggal */
    .date-nav {
        overflow-x: auto;
    }
    
    .date-nav::-webkit-scrollbar {
        height: 5px;
    }
    
    .date-nav::-webkit-scrollbar-thumb {
        background-color: #D4AF37;
        border-radius: 10px;
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

    .prev-isu-slide, .next-isu-slide,
    .prev-lainnya-slide, .next-lainnya-slide,
    .prev-google-slide, .next-google-slide,
    .prev-x-slide, .next-x-slide {
        cursor: pointer;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: white;
        color: #17a2b8;
        border: none;
    }

    .prev-isu-slide, 
    .prev-lainnya-slide, 
    .prev-google-slide, 
    .prev-x-slide {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
        border-right: 1px solid #e9ecef;
    }

    .next-isu-slide, 
    .next-lainnya-slide, 
    .next-google-slide, 
    .next-x-slide {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    .prev-isu-slide:disabled, .next-isu-slide:disabled,
    .prev-lainnya-slide:disabled, .next-lainnya-slide:disabled,
    .prev-google-slide:disabled, .next-google-slide:disabled,
    .prev-x-slide:disabled, .next-x-slide:disabled {
        cursor: not-allowed;
        opacity: 0.5;
    }

    /* Styling untuk indikator slider */
    .slider-indicators {
        display: flex;
        gap: 8px;
        padding: 5px;
    }

    .slider-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #ccc;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .slider-dot.active {
        background-color: #17a2b8;
    }

    /* Styling untuk list group */
    .list-group-item {
        border-left: none;
        border-right: none;
        padding-left: 0;
        padding-right: 0;
    }

    .list-group-item:first-child {
        border-top: none;
    }

    .list-group-item:last-child {
        border-bottom: 1px solid rgba(0,0,0,.125);
    }

    .list-group-item a {
        color: #333;
        display: block;
        transition: color 0.2s;
    }

    .list-group-item a:hover {
        color: #17a2b8;
    }

    /* Media Queries */
    @media (max-width: 768px) {
        .day-box {
            width: 140px;
            padding: 10px;
        }
        
        .day-title {
            font-size: 1.2rem;
        }
        
        .day-date {
            font-size: 0.9rem;
        }
        
        .date-nav .btn {
            padding: 0.375rem 0.5rem;
        }

        .isu-slider-container {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .slider-indicators {
            margin-bottom: 10px;
        }
    }

    @media (max-width: 576px) {
        .day-box {
            width: 120px;
            padding: 8px;
        }
        
        .day-title {
            font-size: 1rem;
        }
        
        .day-date {
            font-size: 0.8rem;
        }
    }

    /* Styling untuk container gambar dengan resolusi tetap */
    .image-container {
        overflow: hidden;
        background-color: #f8f9fa;
    }

    .main-image-container {
        height: 400px;
        width: 100%;
    }

    .thumbnail-image-container {
        height: 300px;
        width: 100%;
    }

    .banner-image-container {
        height: 300px;
        width: 100%;
    }

    .main-image, .thumbnail-image, .banner-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .placeholder-image {
        width: 100%;
        height: 100%;
        background-color: #dee2e6;
        color: #6c757d;
    }

    .banner-placeholder {
        height: 300px;
    }
</style>
@endsection
@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Buat fungsi umum untuk menginisialisasi slider
        function initializeSlider(prevBtnClass, nextBtnClass, slideClass, dotClass) {
            const prevBtn = document.querySelector(`.${prevBtnClass}`);
            const nextBtn = document.querySelector(`.${nextBtnClass}`);
            const slides = document.querySelectorAll(`.${slideClass}`);
            const dots = document.querySelectorAll(`.${dotClass}`);
            
            if (slides.length > 0) {
                let currentSlide = 0;
                
                // Update status tombol navigasi
                function updateButtons() {
                    if(prevBtn) prevBtn.disabled = currentSlide === 0;
                    if(nextBtn) nextBtn.disabled = currentSlide === slides.length - 1;
                }
                
                // Update indikator titik
                function updateDots() {
                    dots.forEach((dot, index) => {
                        dot.classList.toggle('active', index === currentSlide);
                    });
                }
                
                // Tampilkan slide berdasarkan indeks
                function showSlide(index) {
                    // Sembunyikan semua slide
                    slides.forEach(slide => {
                        slide.classList.remove('active');
                    });
                    
                    // Tampilkan slide saat ini
                    slides[index].classList.add('active');
                    
                    // Update status tombol dan indikator
                    updateButtons();
                    updateDots();
                }
                
                // Event untuk tombol selanjutnya
                if (nextBtn) {
                    nextBtn.addEventListener('click', function() {
                        if (currentSlide < slides.length - 1) {
                            currentSlide++;
                            showSlide(currentSlide);
                        }
                    });
                }
                
                // Event untuk tombol sebelumnya
                if (prevBtn) {
                    prevBtn.addEventListener('click', function() {
                        if (currentSlide > 0) {
                            currentSlide--;
                            showSlide(currentSlide);
                        }
                    });
                }
                
                // Event untuk indikator titik
                dots.forEach((dot, index) => {
                    dot.addEventListener('click', function() {
                        currentSlide = index;
                        showSlide(currentSlide);
                    });
                });
                
                // Inisialisasi
                updateButtons();
            }
        }
        
        // Inisialisasi semua slider
        initializeSlider('prev-isu-slide', 'next-isu-slide', 'isu-slide', 'slider-dot:not(.lainnya-dot):not(.google-dot):not(.x-dot)');
        initializeSlider('prev-lainnya-slide', 'next-lainnya-slide', 'lainnya-slide', 'lainnya-dot');
        initializeSlider('prev-google-slide', 'next-google-slide', 'google-slide', 'google-dot');
        initializeSlider('prev-x-slide', 'next-x-slide', 'x-slide', 'x-dot');
    });
</script>
@endsection
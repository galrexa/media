<!-- resources/views/dashboard/home/admin.blade.php -->
@extends('layouts.admin')

@section('title', 'Beranda - Kurasi Berita Terkini')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1>KURASI BERITA TERKINI</h1>
</div>

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
    <div class="col-md-5">
        <div class="image-container main-image-container">
            @if($dailyImages && $dailyImages->image_1)
                <img src="{{ asset('storage/' . $dailyImages->image_1) }}" alt="Gambar Utama" class="img-fluid object-fit-cover">
            @else
                <div class="bg-secondary h-100 d-flex align-items-center justify-content-center">
                    <p class="text-center mb-0 text-white">Placeholder Gambar</p>
                </div>
            @endif
        </div>
    </div>
    <div class="col-md-7">
        <div class="card">
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
                        <div class="isu-slide active">
                            <ul class="list-group list-group-flush">
                                @forelse($isuStrategis->take(5) as $index => $isu)
                                    <li class="list-group-item">
                                        <a href="{{ route('isu.show', $isu) }}" class="text-decoration-none">
                                            {{ $index + 1 }}. {{ $isu->judul }}
                                        </a>
                                    </li>
                                @empty
                                    <li class="list-group-item">Tidak ada isu strategis untuk ditampilkan</li>
                                @endforelse
                            </ul>
                        </div>
                        
                        @if(isset($isuStrategis) && is_countable($isuStrategis) && count($isuStrategis) > 5)
                            <div class="isu-slide">
                                <ul class="list-group list-group-flush">
                                    @foreach($isuStrategis->slice(5, 5) as $index => $isu)
                                        <li class="list-group-item">
                                            <a href="{{ route('isu.show', $isu) }}" class="text-decoration-none">
                                                {{ $index + 6 }}. {{ $isu->judul }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        @if(isset($isuStrategis) && is_countable($isuStrategis) && count($isuStrategis) > 10)
                            <div class="isu-slide">
                                <ul class="list-group list-group-flush">
                                    @foreach($isuStrategis->slice(10, 5) as $index => $isu)
                                        <li class="list-group-item">
                                            <a href="{{ route('isu.show', $isu) }}" class="text-decoration-none">
                                                {{ $index + 11 }}. {{ $isu->judul }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-footer p-0">
                <div class="slider-indicators d-flex justify-content-start p-2">
                    @if(isset($isuStrategis) && is_countable($isuStrategis) && count($isuStrategis) > 5)
                        @for ($i = 0; $i < ceil(count($isuStrategis) / 5); $i++)
                            <span class="slider-dot {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}"></span>
                        @endfor
                    @endif
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
                    <button class="btn btn-sm btn-secondary prev-lainnya-slide">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button class="btn btn-sm btn-secondary next-lainnya-slide">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="lainnya-slider-container">
                    <div class="lainnya-slider">
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
                        
                        @if(isset($isuLainnya) && is_countable($isuLainnya) && count($isuLainnya) > 6)
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
        </div>
    </div>
</div>

<!-- Trending Section -->
<div class="row mb-4">
    <div class="col-md-5">
        <div class="image-container thumbnail-image-container">
            @if($dailyImages && $dailyImages->image_2)
                <img src="{{ asset('storage/' . $dailyImages->image_2) }}" alt="Gambar Trending" class="img-fluid object-fit-cover">
            @else
                <div class="bg-secondary h-100 d-flex align-items-center justify-content-center">
                    <p class="text-center mb-0 text-white">Placeholder Gambar 2</p>
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
                                <div class="google-slide active">
                                    <ul class="list-group list-group-flush">
                                        @forelse($trendingGoogle->take(10) as $index => $trend)
                                            <li class="list-group-item">
                                                <a href="{{ $trend->url }}" target="_blank" class="text-decoration-none">
                                                    {{ $index + 1 }}. {{ $trend->judul }}
                                                </a>
                                            </li>
                                        @empty
                                            <li class="list-group-item">Tidak ada trending Google</li>
                                        @endforelse
                                    </ul>
                                </div>
                                
                                @if(isset($trendingGoogle) && is_countable($trendingGoogle) && count($trendingGoogle) > 10)
                                    <div class="google-slide">
                                        <ul class="list-group list-group-flush">
                                            @foreach($trendingGoogle->skip(10)->take(10) as $index => $trend)
                                                <li class="list-group-item">
                                                    <a href="{{ $trend->url }}" target="_blank" class="text-decoration-none">
                                                        {{ $index + 11 }}. {{ $trend->judul }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
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
                                <div class="x-slide active">
                                    <ul class="list-group list-group-flush">
                                        @forelse($trendingX->take(3) as $index => $trend)
                                            <li class="list-group-item">
                                                <a href="{{ $trend->url }}" target="_blank" class="text-decoration-none">
                                                    {{ $index + 1 }}. {{ $trend->judul }}
                                                </a>
                                            </li>
                                        @empty
                                            <li class="list-group-item">Tidak ada trending X</li>
                                        @endforelse
                                    </ul>
                                </div>
                                
                                @if(isset($trendingX) && is_countable($trendingX) && count($trendingX) > 3)
                                    <div class="x-slide">
                                        <ul class="list-group list-group-flush">
                                            @foreach($trendingX->skip(3)->take(3) as $index => $trend)
                                                <li class="list-group-item">
                                                    <a href="{{ $trend->url }}" target="_blank" class="text-decoration-none">
                                                        {{ $index + 4 }}. {{ $trend->judul }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Banner Section -->
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
<style>
    .day-box {
        display: block;
        width: 160px;
        padding: 15px;
        text-align: center;
        text-decoration: none;
        color: white;
        background-color: #D4AF37; /* Warna emas */
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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

    .prev-isu-slide, .prev-lainnya-slide, .prev-google-slide, .prev-x-slide {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
        border-right: 1px solid #e9ecef;
    }

    .next-isu-slide, .next-lainnya-slide, .next-google-slide, .next-x-slide {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    /* Styling untuk indikator slider */
    .slider-indicators {
        display: flex;
        gap: 5px;
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
    
    /* Styling untuk container gambar dengan resolusi tetap */
    .image-container {
        overflow: hidden;
        background-color: #f8f9fa;
    }

    .main-image-container {
        width: 640px; /* Persegi */
        height: 640px;
        max-width: 100%; /* Responsif */
    }

    .main-image-container img,
    .main-image-container .bg-secondary {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Card Isu Strategis mengikuti tinggi gambar */
    .col-md-7 .card {
        height: 100%;
        min-height: 400px; /* Sesuai dengan tinggi gambar */
    }

    .thumbnail-image-container {
        width: 640px; /* Persegi, sama seperti main-image-container */
        height: 640px;
        max-width: 100%; /* Responsif */
    }

    .thumbnail-image-container img,
    .thumbnail-image-container .bg-secondary {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .banner-image-container {
        height: 300px;
        width: 100%;
    }

    .object-fit-cover {
        object-fit: cover;
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
<!-- resources/views/home.blade.php -->
@extends(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isEditor()) ? 'layouts.admin' : 'layouts.app')

@section('title', 'Beranda - Media Monitoring')

@section('content')

<div class="content-wrapper">
    <div class="row mb-3">
        <!-- Kolom untuk navigasi tanggal -->
        <div class="col-md-9">
            <!-- Tanggal Navigation -->
            <div class="date-nav mb-3">
                <div class="date-nav-controls">
                    <!-- Tombol navigasi hari sebelumnya (panah saja) -->
                    <a href="{{ route('home', ['offset' => ($offset ?? 0) - 1]) }}" class="nav-arrow prev-arrow">
                        <i class="bi bi-chevron-left"></i>
                    </a>

                    <!-- Dua hari sebelumnya -->
                    @php
                        $prevOffset2 = ($offset ?? 0) - 2;
                        $prevDate2 = Carbon\Carbon::now()->addDays($prevOffset2);
                        $isPrevDate2Available = in_array($prevDate2->format('Y-m-d'), $availableDates ?? []);
                    @endphp
                    <a href="{{ route('home', ['offset' => $prevOffset2]) }}" class="date-button {{ !$isPrevDate2Available ? 'date-disabled' : '' }}">
                        <div class="date-day">{{ $prevDate2->format('d') }}</div>
                        <div class="date-month">{{ strtoupper($prevDate2->format('M')) }}</div>
                        <div class="date-year">{{ $prevDate2->format('Y') }}</div>
                    </a>
                    
                    <!-- Satu hari sebelumnya -->
                    @php
                        $prevOffset1 = ($offset ?? 0) - 1;
                        $prevDate1 = Carbon\Carbon::now()->addDays($prevOffset1);
                        $isPrevDate1Available = in_array($prevDate1->format('Y-m-d'), $availableDates ?? []);
                    @endphp
                    <a href="{{ route('home', ['offset' => $prevOffset1]) }}" class="date-button {{ !$isPrevDate1Available ? 'date-disabled' : '' }}">
                        <div class="date-day">{{ $prevDate1->format('d') }}</div>
                        <div class="date-month">{{ strtoupper($prevDate1->format('M')) }}</div>
                        <div class="date-year">{{ $prevDate1->format('Y') }}</div>
                    </a>
                        
                    <!-- Tanggal yang aktif/dipilih (lebih besar) -->
                    @php
                        $currentDate = Carbon\Carbon::now()->addDays($offset ?? 0);
                        $isCurrentDateAvailable = in_array($currentDate->format('Y-m-d'), $availableDates ?? []);
                    @endphp
                    <div class="date-button date-button-active {{ !$isCurrentDateAvailable ? 'date-disabled' : '' }}">
                        <div class="date-day">{{ $currentDate->format('d') }}</div>
                        <div class="date-month">{{ strtoupper($currentDate->format('M')) }}</div>
                        <div class="date-year">{{ $currentDate->format('Y') }}</div>
                    </div>
                        
                    <!-- Tombol navigasi hari berikutnya (panah saja) -->
                    <a href="{{ route('home', ['offset' => ($offset ?? 0) + 1]) }}" class="nav-arrow next-arrow">
                        <i class="bi bi-chevron-right"></i>
                    </a>

                    <!-- Tombol kembali ke hari ini (desain baru) -->
                    @if(($offset ?? 0) != 0)
                    <a href="{{ route('home') }}" class="today-button">
                        <div class="today-dot"></div>
                        <span>{{ Carbon\Carbon::now()->format('d') }} {{ strtoupper(Carbon\Carbon::now()->format('M')) }}</span>
                    </a>
                    @endif

                </div>
            </div>
        </div>

        <div class="col-md-3 d-flex align-items-center justify-content-end">
            @if($dailyImages && $dailyImages->dokumen_url)
                <a href="{{ $dailyImages->dokumen_url }}" class="download-report-btn" target="_blank">
                    <i class="bi bi-file-earmark-pdf"></i>
                    <span>Laporan Harian Kompas</span>
                </a>
            @endif
        </div>    
    </div>

    <!-- Isu Strategis Section -->
    <div class="row mb-4">
        @php
             $noImages = !($dailyImages && ($dailyImages->image_1 || $dailyImages->image_2 || $dailyImages->image_3));
        @endphp
        @if(!$noImages)
            <div class="col-lg-5 col-md-12 mb-3 mb-lg-0">
                <div class="image-container main-image-container">
                    @if($dailyImages && $dailyImages->image_1)
                        <img src="{{ asset('storage/' . $dailyImages->image_1) }}" alt="Gambar Utama" class="img-fluid w-100 h-100 main-image" loading="lazy">
                    @else
                        <img src="{{ asset('images/placeholder-main.jpg') }}" alt="Placeholder Gambar" class="img-fluid w-100 h-100 main-image" loading="lazy">
                    @endif
                </div>
            </div>
            <div class="col-lg-7 col-md-12">
        @else
            <div class="col-12">
        @endif
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
                            <!-- @php
                                $nomorUrut = 1;
                            @endphp -->
                            <!-- Halaman pertama -->
                            <div class="isu-slide active">
                                <div class="list-group list-group-flush">
                                    @forelse($isuStrategis->take(10) as $index => $isu)
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
                            @if($isuStrategis->count() > 10)
                                <div class="isu-slide">
                                    <div class="list-group list-group-flush">
                                        @foreach($isuStrategis->slice(10, 10) as $index => $isu)
                                            <div class="list-group-item border-bottom py-2">
                                                <a href="{{ route('isu.show', $isu) }}" class="text-decoration-none text-primary">
                                                    {{ $index + 1 }}. {{ $isu->judul }}
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="slider-indicators d-flex justify-content-start p-2">
                    @for ($i = 0; $i < ceil(count($isuStrategis) / 10); $i++)
                        <span class="slider-dot isu-dot {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}"></span>
                    @endfor
                </div>
            </div>
        </div>
    </div>

    <!-- Isu Lainnya Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info d-flex text-white justify-content-between align-items-center">
                    <h5 class="mb-0">Isu Regional</h5>
                    <div class="navigation-buttons">
                        <button class="btn btn-sm btn-pill prev-lainnya-slide">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <button class="btn btn-sm btn-pill next-lainnya-slide">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="lainnya-slider-container">
                        <div class="lainnya-slider">
                            <!-- Halaman pertama -->
                            <div class="lainnya-slide active">
                                <div class="list-group list-group-flush">
                                    @forelse($isuLainnya->take(10) as $index => $isu)
                                        <div class="list-group-item border-bottom py-2">
                                            <a href="{{ route('isu.show', $isu) }}" class="text-decoration-none text-primary">
                                                {{ $index + 1 }}. {{ $isu->judul }}
                                            </a>
                                        </div>
                                    @empty
                                        <div class="list-group-item">Tidak ada isu regional untuk ditampilkan</div>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Halaman kedua (jika ada) -->
                            @if(count($isuLainnya) > 10)
                                <div class="lainnya-slide">
                                    <div class="list-group list-group-flush">
                                        @foreach($isuLainnya->skip(10)->take(10) as $index => $isu)
                                            <div class="list-group-item border-bottom py-2">
                                                <a href="{{ route('isu.show', $isu) }}" class="text-decoration-none text-primary">
                                                    {{ $index + 1 }}. {{ $isu->judul }}
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="slider-indicators d-flex justify-content-start p-2">
                    @for ($i = 0; $i < ceil(count($isuLainnya) / 10); $i++)
                        <span class="slider-dot lainnya-dot {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}"></span>
                    @endfor
                </div>
            </div>
        </div>
    </div>

<!-- Trending Section dengan Google dan X dipisahkan -->
<div class="row mb-4">
    @if(!$noImages)
        <div class="col-md-5">
            <div class="image-container thumbnail-image-container">
                @if($dailyImages && $dailyImages->image_2)
                    <img src="{{ asset('storage/' . $dailyImages->image_2) }}" alt="Gambar Trending" class="img-fluid w-100 h-100 thumbnail-image" loading="lazy">
                @else
                    <img src="{{ asset('images/placeholder-thumbnail.jpg') }}" alt="Placeholder Gambar 2" class="img-fluid w-100 h-100 thumbnail-image" loading="lazy">
                @endif
            </div>
        </div>
        <div class="col-md-7">
    @else
        <div class="col-12">
    @endif
            <div class="row h-100">
            <!-- Trending Google yang dipilih (urutan terpisah) -->
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="card h-100 {{ $noImages ? 'full-width-card' : '' }}">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-google" style="color: white;"></i> Trend Google
                        </h5>
                        <div class="navigation-buttons">
                            <button class="btn btn-sm btn-light prev-google-selected-slide">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <button class="btn btn-sm btn-light next-google-selected-slide">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="google-selected-slider-container">
                            <div class="google-selected-slider">
                                <!-- Halaman pertama -->
                                <div class="google-selected-slide active">
                                    <ul class="trend-list">
                                        @forelse($selectedGoogleTrendings->take(5) as $index => $trend)
                                            <li class="trend-item">
                                                <span class="trend-rank {{ $index < 3 ? 'top-'.($index+1) : '' }}">{{ $index + 1 }}</span>
                                                <div class="trend-content">
                                                    <div class="trend-title">
                                                        <a href="{{ $trend->url }}" target="_blank">{{ $trend->judul }}</a>
                                                    </div>
                                                    <div class="trend-info">
                                                        <span class="trend-time"><i class="bi bi-clock"></i> {{ $trend->tanggal->format('H:i') }}</span>
                                                    </div>
                                                </div>
                                            </li>
                                        @empty
                                            <li class="trend-item empty-trend">
                                                <div class="text-center w-100 py-3">
                                                    <p class="text-muted mb-0">Belum ada trending Google untuk tanggal {{ $selectedDate->format('d M Y') }}</p>
                                                </div>
                                            </li>
                                        @endforelse
                                    </ul>
                                </div>
                                
                                <!-- Halaman kedua (jika ada) -->
                                @if($selectedGoogleTrendings->count() > 5)
                                    <div class="google-selected-slide">
                                        <ul class="trend-list">
                                            @foreach($selectedGoogleTrendings->skip(5)->take(5) as $index => $trend)
                                                <li class="trend-item">
                                                    <span class="trend-rank">{{ $index + 6 }}</span>
                                                    <div class="trend-content">
                                                        <div class="trend-title">
                                                            <a href="{{ $trend->url }}" target="_blank">{{ $trend->judul }}</a>
                                                        </div>
                                                        <div class="trend-info">
                                                            <span class="trend-time"><i class="bi bi-clock"></i> {{ $trend->tanggal->format('H:i') }}</span>
                                                        </div>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @if($selectedGoogleTrendings->count() > 0)
                        <div class="card-footer p-0">
                            <div class="slider-indicators d-flex justify-content-start p-2">
                                @php
                                    $totalGoogleSlides = ceil($selectedGoogleTrendings->count() / 5);
                                @endphp
                                
                                @for ($i = 0; $i < $totalGoogleSlides; $i++)
                                    <span class="slider-dot google-selected-dot {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}"></span>
                                @endfor
                            </div>
                        </div>
                    @endif
                </div>
            </div>
                
                <!-- Trending X yang dipilih (urutan terpisah) -->
                <div class="col-md-6">
                    <div class="card h-100 {{ $noImages ? 'full-width-card' : '' }}">
                    <div class="card-header glassmorphic-dark text-white d-flex justify-content-between align-items-center">    
                    <!-- <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center"> -->
                            <h5 class="mb-0"><i class="bi bi-x" style="color: white;"></i>Trend X</h5>
                            <div class="navigation-buttons">
                                <button class="btn btn-sm btn-glassmorphic prev-x-selected-slide">
                                    <i class="bi bi-chevron-left text-white"></i>
                                </button>
                                <button class="btn btn-sm btn-glassmorphic next-x-selected-slide">
                                    <i class="bi bi-chevron-right text-white"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="x-selected-slider-container">
                                <div class="x-selected-slider">
                                    <!-- Halaman pertama -->
                                    <div class="x-selected-slide active">
                                        <ul class="trend-list">
                                            @forelse($selectedXTrendings->take(5) as $index => $trend)
                                                <li class="trend-item">
                                                    <span class="trend-rank {{ $index < 3 ? 'top-'.($index+1) : '' }}">{{ $index + 1 }}</span>
                                                    <div class="trend-content">
                                                        <div class="trend-title">
                                                            <a href="{{ $trend->url }}" target="_blank">{{ $trend->judul }}</a>
                                                        </div>
                                                        <div class="trend-info">
                                                            <span class="trend-time"><i class="bi bi-clock"></i> {{ $trend->tanggal->format('H:i') }}</span>
                                                            <!-- <span class="trend-source"><i class="bi bi-twitter-x"></i> Terpilih</span> -->
                                                        </div>
                                                    </div>
                                                </li>
                                            @empty
                                                <li class="trend-item empty-trend">
                                                <div class="text-center w-100 py-3">
                                                    <p class="text-muted mb-0">Belum ada trending X untuk tanggal {{ $selectedDate->format('d M Y') }}</p>
                                                </div>
                                                </li>
                                            @endforelse
                                        </ul>
                                    </div>
                                    
                                    <!-- Halaman kedua (jika ada) -->
                                    @if($selectedXTrendings->count() > 5)
                                        <div class="x-selected-slide">
                                            <ul class="trend-list">
                                                @foreach($selectedXTrendings->skip(5)->take(5) as $index => $trend)
                                                    <li class="trend-item">
                                                        <span class="trend-rank">{{ $index + 6 }}</span>
                                                        <div class="trend-content">
                                                            <div class="trend-title">
                                                                <a href="{{ $trend->url }}" target="_blank">{{ $trend->judul }}</a>
                                                            </div>
                                                            <div class="trend-info">
                                                                <span class="trend-time"><i class="bi bi-clock"></i> {{ $trend->tanggal->format('H:i') }}</span>
                                                                <!-- <span class="trend-source"><i class="bi bi-twitter-x"></i> Terpilih</span> -->
                                                            </div>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if($selectedXTrendings->count() > 0)
                            <div class="card-footer p-0">
                                <div class="slider-indicators d-flex justify-content-start p-2">
                                    @php
                                        $totalXSlides = ceil($selectedXTrendings->count() / 5);
                                    @endphp
                                    
                                    @for ($i = 0; $i < $totalXSlides; $i++)
                                        <span class="slider-dot x-selected-dot {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}"></span>
                                    @endfor
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    @if(!$noImages)
        <div class="row mb-4">
            <div class="col-12">
                <div class="image-container banner-image-container">
                    @if($dailyImages && $dailyImages->image_3)
                        <img src="{{ asset('storage/' . $dailyImages->image_3) }}" alt="Gambar Banner" class="img-fluid w-100 h-100 banner-image" loading="lazy">
                    @else
                        <img src="{{ asset('images/placeholder-banner.jpg') }}" alt="Placeholder Image 3" class="img-fluid w-100 h-100 banner-image" loading="lazy">
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="{{ asset('css/layouts/app.css') }}">  
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function initializeSlider(prevBtnClass, nextBtnClass, slideClass, dotClass) {
            const prevBtn = document.querySelector(`.${prevBtnClass}`);
            const nextBtn = document.querySelector(`.${nextBtnClass}`);
            const slides = document.querySelectorAll(`.${slideClass}`);
            const dots = document.querySelectorAll(`.${dotClass}`);
            
            if (slides.length > 0) {
                let currentSlide = 0;
                
                function updateButtons() {
                    if(prevBtn) prevBtn.disabled = currentSlide === 0;
                    if(nextBtn) nextBtn.disabled = currentSlide === slides.length - 1;
                }
                
                function updateDots() {
                    dots.forEach((dot, index) => {
                        dot.classList.toggle('active', index === currentSlide);
                    });
                }
                
                function showSlide(index) {
                    slides.forEach(slide => {
                        slide.classList.remove('active');
                    });
                    slides[index].classList.add('active');
                    updateButtons();
                    updateDots();
                }
                
                if (nextBtn) {
                    nextBtn.addEventListener('click', function() {
                        if (currentSlide < slides.length - 1) {
                            currentSlide++;
                            showSlide(currentSlide);
                        }
                    });
                }
                
                if (prevBtn) {
                    prevBtn.addEventListener('click', function() {
                        if (currentSlide > 0) {
                            currentSlide--;
                            showSlide(currentSlide);
                        }
                    });
                }
                
                dots.forEach((dot, index) => {
                    dot.addEventListener('click', function() {
                        currentSlide = index;
                        showSlide(currentSlide);
                    });
                });
                
                updateButtons();
            }
        }
        
        initializeSlider('prev-isu-slide', 'next-isu-slide', 'isu-slide', 'isu-dot');
        initializeSlider('prev-lainnya-slide', 'next-lainnya-slide', 'lainnya-slide', 'lainnya-dot');

        // Inisialisasi slider untuk Google Trends yang dipilih
        initializeSlider('prev-google-selected-slide', 'next-google-selected-slide', 'google-selected-slide', 'google-selected-dot');

        // Inisialisasi slider untuk X Trends yang dipilih
        initializeSlider('prev-x-selected-slide', 'next-x-selected-slide', 'x-selected-slide', 'x-selected-dot');

        // Batasan offset berdasarkan data yang tersedia
        const minOffset = {{ $minOffset ?? -30 }}; // Default -30 hari ke belakang
        const maxOffset = {{ $maxOffset ?? 30 }}; // Default 30 hari ke depan
        
        // Jika link navigasi perlu dinonaktifkan pada batas tertentu
        const prevLink = document.querySelector('.nav-arrow:first-child');
        const nextLink = document.querySelector('.nav-arrow:last-child');
        
        if ({{ $offset }} <= minOffset) {
            prevLink.classList.add('disabled');
            prevLink.href = 'javascript:void(0)';
        }
        
        if ({{ $offset }} >= maxOffset) {
            nextLink.classList.add('disabled');
            nextLink.href = 'javascript:void(0)';
        }

        // Tampilkan modal splash screen jika login baru berhasil
        const welcomeModal = new bootstrap.Modal(document.getElementById('welcomeModal'));
        
        @if(session('login_success'))
            welcomeModal.show();
        @endif
        
        // Update tanggal dengan latestIsuDate jika tersedia
        @if(session('latestIsuDate'))
            document.getElementById('current-date').innerText = '{{ session('latestIsuDate') }}';
        @endif
    });
    document.querySelectorAll('.selected-trending-dot').forEach((dot, index) => {
        dot.addEventListener('click', function() {
            const slides = document.querySelectorAll('.selected-trending-slide');
            const dots = document.querySelectorAll('.selected-trending-dot');
            
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            slides[index].classList.add('active');
            dot.classList.add('active');
        });
    });
</script>
@endsection
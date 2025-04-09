<!-- resources/views/home.blade.php -->
@extends(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isEditor()) ? 'layouts.admin' : 'layouts.app')

@section('title', 'Beranda - Kurasi Berita Terkini')

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
            @else
                <button type="button" class="download-report-btn" disabled>
                    <i class="bi bi-file-earmark-pdf"></i>
                    <span>Laporan Harian Kompas</span>
                </button>
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
                            @php
                                $nomorUrut = 1; // Mulai dari 1
                            @endphp
                            <!-- Halaman pertama -->
                            <div class="isu-slide active">
                                <div class="list-group list-group-flush">
                                    @forelse($isuStrategis->take(8) as $isu)
                                        <div class="list-group-item border-bottom py-2">
                                            <a href="{{ route('isu.show', $isu) }}" class="text-decoration-none text-primary">
                                                {{ $nomorUrut++ }}. {{ $isu->judul }}
                                            </a>
                                        </div>
                                    @empty
                                        <div class="list-group-item">Tidak ada isu strategis untuk ditampilkan</div>
                                    @endforelse
                                </div>
                            </div>
                            
                            <!-- Halaman kedua (jika ada) -->
                            @if($isuStrategis->count() > 8)
                                <div class="isu-slide">
                                    <div class="list-group list-group-flush">
                                        @foreach($isuStrategis->slice(8, 8) as $isu)
                                            <div class="list-group-item border-bottom py-2">
                                                <a href="{{ route('isu.show', $isu) }}" class="text-decoration-none text-primary">
                                                    {{ $nomorUrut++ }}. {{ $isu->judul }}
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Halaman ketiga (jika ada) -->
                            @if($isuStrategis->count() > 16)
                                <div class="isu-slide">
                                    <div class="list-group list-group-flush">
                                        @foreach($isuStrategis->slice(16, 8) as $isu)
                                            <div class="list-group-item border-bottom py-2">
                                                <a href="{{ route('isu.show', $isu) }}" class="text-decoration-none text-primary">
                                                    {{ $nomorUrut++ }}. {{ $isu->judul }}
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
                        @for ($i = 0; $i < ceil($isuStrategis->count() / 8); $i++)
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
                    <!-- Trending Google -->
                    <div class="col-md-6 mb-3 mb-md-0">
                        <div class="card h-100 {{ $noImages ? 'full-width-card' : '' }}">
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
                            <div class="card-body p-0">
                                <div class="google-slider-container">
                                    <div class="google-slider">
                                        <!-- Halaman pertama -->
                                        <div class="google-slide active">
                                            <ul class="trend-list">
                                                @forelse(array_slice($trendingGoogle, 0, 5) as $trend)
                                                    <li class="trend-item">
                                                        <span class="trend-rank {{ $trend['rank'] == 1 ? 'top-1' : ($trend['rank'] == 2 ? 'top-2' : ($trend['rank'] == 3 ? 'top-3' : '')) }}">{{ $trend['rank'] }}</span>
                                                        <div class="trend-content">
                                                            <div class="trend-title">
                                                                <a href="{{ $trend['url'] }}" target="_blank">{{ $trend['judul'] }}</a>
                                                            </div>
                                                            <div class="trend-info">
                                                                <span class="trend-traffic"><i class="bi bi-graph-up"></i> {{ $trend['traffic'] }}</span>
                                                                <span class="trend-time"><i class="bi bi-clock"></i> {{ $trend['tanggal']->format('H:i') }}</span>
                                                            </div>
                                                        </div>
                                                    </li>
                                                @empty
                                                    <li class="trend-item">Tidak ada trending Google</li>
                                                @endforelse
                                            </ul>
                                        </div>
                                        <!-- Halaman kedua (jika ada) -->
                                        @if(count($trendingGoogle) > 5)
                                            <div class="google-slide">
                                                <ul class="trend-list">
                                                    @foreach(array_slice($trendingGoogle, 5, 5) as $trend)
                                                        <li class="trend-item">
                                                            <span class="trend-rank {{ $trend['rank'] == 1 ? 'top-1' : ($trend['rank'] == 2 ? 'top-2' : ($trend['rank'] == 3 ? 'top-3' : '')) }}">{{ $trend['rank'] }}</span>
                                                            <div class="trend-content">
                                                                <div class="trend-title">
                                                                    <a href="{{ $trend['url'] }}" target="_blank">{{ $trend['judul'] }}</a>
                                                                </div>
                                                                <div class="trend-info">
                                                                    <span class="trend-traffic"><i class="bi bi-graph-up"></i> {{ $trend['traffic'] }}</span>
                                                                    <span class="trend-time"><i class="bi bi-clock"></i> {{ $trend['tanggal']->format('H:i') }}</span>
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
                            <div class="card-footer p-0">
                                <div class="slider-indicators d-flex justify-content-start p-2">
                                    @for ($i = 0; $i < ceil(count($trendingGoogle) / 5); $i++)
                                        <span class="slider-dot google-dot {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}"></span>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Trending X -->
                    <div class="col-md-6">
                        <div class="card h-100 {{ $noImages ?? false ? 'full-width-card' : '' }}">
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
                            <div class="card-body p-0">
                                <div class="x-slider-container">
                                    <div class="x-slider">
                                        @php
                                            // Tentukan jumlah item per slide yang optimal
                                            $itemsPerSlide = 5;
                                            // Pastikan data trending tersedia
                                            $xData = $trendingXLive ?? [];
                                            // Hitung jumlah slide yang dibutuhkan
                                            $totalSlides = ceil(count($xData) / $itemsPerSlide);
                                        @endphp

                                        @for ($slideIndex = 0; $slideIndex < $totalSlides; $slideIndex++)
                                            <div class="x-slide {{ $slideIndex === 0 ? 'active' : '' }}">
                                                <ul class="trend-list">
                                                    @foreach(array_slice($xData, $slideIndex * $itemsPerSlide, $itemsPerSlide) as $trend)
                                                    <li class="trend-item">
                                                        <span class="trend-rank {{ $trend['rank'] <= 3 ? 'top-'.$trend['rank'] : '' }}">{{ $trend['rank'] }}</span>
                                                        <div class="trend-content">
                                                            <div class="trend-title">
                                                                <a href="{{ $trend['url'] }}" target="_blank">{{ $trend['name'] }}</a>
                                                            </div>
                                                            <div class="trend-info">
                                                                @if(!empty($trend['tweet_count']))
                                                                    <span class="trend-traffic"><i class="bi bi-twitter"></i> {{ $trend['tweet_count'] }}</span>
                                                                @endif
                                                                <span class="trend-source"><i class="bi bi-lightning"></i> Live</span>
                                                            </div>
                                                        </div>
                                                    </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endfor

                                        <!-- Slide fallback jika tidak ada data live -->
                                        @if(empty($xData) && count($trendingX) > 0)
                                            <div class="x-slide active">
                                                <ul class="trend-list">
                                                    @foreach($trendingX->take($itemsPerSlide) as $index => $trend)
                                                    <li class="trend-item">
                                                        <span class="trend-rank {{ $index < 3 ? 'top-'.($index+1) : '' }}">{{ $index + 1 }}</span>
                                                        <div class="trend-content">
                                                            <div class="trend-title">
                                                                <a href="{{ $trend->url }}" target="_blank">{{ $trend->judul }}</a>
                                                            </div>
                                                            <div class="trend-info">
                                                                <span class="trend-time"><i class="bi bi-clock"></i> {{ $trend->tanggal->format('H:i') }}</span>
                                                                <span class="trend-source"><i class="bi bi-database"></i> DB</span>
                                                            </div>
                                                        </div>
                                                    </li>
                                                    @endforeach
                                                </ul>
                                            </div>

                                            @if(count($trendingX) > $itemsPerSlide)
                                                @php
                                                    $dbSlides = ceil(count($trendingX) / $itemsPerSlide) - 1;
                                                @endphp
                                                @for ($dbSlideIndex = 1; $dbSlideIndex <= $dbSlides; $dbSlideIndex++)
                                                    <div class="x-slide">
                                                        <ul class="trend-list">
                                                            @foreach($trendingX->skip($dbSlideIndex * $itemsPerSlide)->take($itemsPerSlide) as $index => $trend)
                                                            <li class="trend-item">
                                                                <span class="trend-rank">{{ ($dbSlideIndex * $itemsPerSlide) + $index + 1 }}</span>
                                                                <div class="trend-content">
                                                                    <div class="trend-title">
                                                                        <a href="{{ $trend->url }}" target="_blank">{{ $trend->judul }}</a>
                                                                    </div>
                                                                    <div class="trend-info">
                                                                        <span class="trend-time"><i class="bi bi-clock"></i> {{ $trend->tanggal->format('H:i') }}</span>
                                                                        <span class="trend-source"><i class="bi bi-database"></i> DB</span>
                                                                    </div>
                                                                </div>
                                                            </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endfor
                                            @endif
                                        @endif

                                        <!-- Slide fallback jika sama sekali tidak ada data -->
                                        @if(empty($xData) && count($trendingX) == 0)
                                            <div class="x-slide active">
                                                <div class="text-center p-4">
                                                    <div class="alert alert-light">
                                                        <i class="bi bi-exclamation-circle"></i> Tidak ada trending X untuk ditampilkan
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer p-0">
                                <div class="slider-indicators d-flex justify-content-start p-2">
                                    @php
                                        // Hitung jumlah indikator
                                        $indicatorCount = 0;
                                        if (!empty($xData)) {
                                            $indicatorCount = $totalSlides;
                                        } elseif (count($trendingX) > 0) {
                                            $indicatorCount = ceil(count($trendingX) / $itemsPerSlide);
                                        } else {
                                            $indicatorCount = 1;
                                        }
                                    @endphp
                                    @for ($i = 0; $i < $indicatorCount; $i++)
                                        <span class="slider-dot x-dot {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}"></span>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Banner Section - Gambar dengan resolusi tetap -->
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
<style>
    /* Wrapper untuk konten */
    .content-wrapper {
        width: 100%;
        padding: 0 15px;
        max-width: 1600px;
        margin: 0 auto;
    }

    /* Styling untuk membuat modal berbentuk persegi */
    #welcomeModal .modal-content {
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    /* Opsional: Sesuaikan padding dan konten agar terlihat rapi */
    #welcomeModal .modal-body {
        padding: 20px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: center;
    }

    #welcomeModal .modal-header {
        border-bottom: none;
        padding: 1.5rem 1.5rem 0.5rem;
    }

    #welcomeModal .modal-footer {
        padding: 1rem 1.5rem 1.5rem;
    }

    /* Responsif: Gunakan vw untuk ukuran dinamis */
    @media (max-width: 576px) {
        #welcomeModal .modal-dialog {
            width: 80vw;
            height: 80vw;
        }
    }

    /* Styling untuk navigasi tanggal baru */
    .date-nav {
        width: 100%;
        padding: 10px 0;
    }

    .date-nav-controls {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 10px;
    }

    /* Styling tombol panah navigasi */
    .nav-arrow {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background-color: #e9f2ff;
        border-radius: 50%;
        color: #2196f3;
        text-decoration: none;
        font-size: 1.2rem;
        transition: all 0.3s ease;
    }

    .nav-arrow:hover {
        background-color: #d0e5ff;
        color: #0d6efd;
    }
    
    /* Styling tombol kembali ke hari ini (desain baru) */
    .today-button {
        display: flex;
        align-items: center;
        margin-left: 20px;
        padding: 5px 10px;
        background-color: white;
        border: 2px solid #4caf50;  /* Hijau menandakan "saat ini" */
        border-radius: 20px;
        color: #4caf50;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .today-dot {
        width: 8px;
        height: 8px;
        background-color: #4caf50;
        border-radius: 50%;
        margin-right: 6px;
    }
    
    .today-button:hover {
        background-color: #4caf50;
        color: white;
    }
    
    .today-button:hover .today-dot {
        background-color: white;
    }

    /* Styling tombol tanggal */
    .date-button {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-width: 100px;
        padding: 8px 12px;
        background-color: #e9f2ff;
        border-radius: 8px;
        color: #2196f3;
        text-decoration: none;
        text-align: center;
        transition: all 0.3s ease;
    }

    .date-button:hover:not(.date-disabled):not(.date-button-active) {
        background-color: #d0e5ff;
    }

    .date-button-active {
        background-color: #d4af37; /* Golden color for active date */
        color: white;
        min-width: 140px; /* Wider button for active date */
        padding: 10px 15px;
    }

    .date-disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }

    .date-day {
        font-size: 1.3rem;
        font-weight: bold;
        line-height: 1.1;
    }

    .date-month {
        font-size: 0.9rem;
        font-weight: 500;
        line-height: 1.1;
    }

    .date-year {
        font-size: 0.8rem;
        opacity: 0.8;
    }

    /* Media Queries */
    @media (max-width: 768px) {
        .date-button {
            min-width: 80px;
            padding: 6px 10px;
        }
        
        .date-button-active {
            min-width: 110px;
        }
        
        .date-day {
            font-size: 1.1rem;
        }
        
        .date-month {
            font-size: 0.8rem;
        }
        
        .date-year {
            font-size: 0.7rem;
        }
        
        .nav-arrow {
            width: 36px;
            height: 36px;
        }
    }

    @media (max-width: 576px) {
        .date-nav-controls {
            gap: 5px;
        }
        
        .date-button {
            min-width: 70px;
            padding: 5px 8px;
        }
        
        .date-button-active {
            min-width: 90px;
        }
        
        .date-day {
            font-size: 1rem;
        }
        
        .date-month {
            font-size: 0.7rem;
        }
        
        .date-year {
            font-size: 0.65rem;
        }
        
        .nav-arrow {
            width: 32px;
            height: 32px;
            font-size: 1rem;
        }
        
        .today-button {
            margin-left: 10px;
            padding: 3px 8px;
            font-size: 0.75rem;
        }
        
        .today-dot {
            width: 6px;
            height: 6px;
            margin-right: 4px;
        }
    }

    /* Styling untuk tombol unduh laporan */
    .download-report-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        color: #212529;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .download-report-btn:hover {
        background-color: #e9ecef;
        border-color: #ced4da;
        color: #0d6efd;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .download-report-btn i {
        font-size: 1.1rem;
        color: #0d6efd;
    }

    @media (max-width: 768px) {
        .download-report-btn {
            padding: 6px 12px;
            font-size: 0.9rem;
        }
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
        display: flex;
        align-items: center;
        justify-content: center;
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
        object-fit: contain; /* Ubah ke contain agar gambar tidak terpotong */
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

    .trend-list {
        list-style-type: none;
        margin: 0;
        padding: 0;
    }

    .trend-item {
        padding: 10px 15px;
        border-bottom: 1px solid #f1f1f1;
        display: flex;
        align-items: center;
    }

    .trend-item:last-child {
        border-bottom: none;
    }

    .trend-rank {
        width: 25px;
        height: 25px;
        background-color: #f1f1f1;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 12px;
        color: #5f6368;
        margin-right: 10px;
        flex-shrink: 0;
    }

    .trend-rank.top-1 {
        background-color: #4285f4;
        color: white;
    }

    .trend-rank.top-2 {
        background-color: #34a853;
        color: white;
    }

    .trend-rank.top-3 {
        background-color: #fbbc05;
        color: white;
    }

    .trend-content {
        flex-grow: 1;
    }

    .trend-title a {
        font-size: 14px;
        font-weight: 500;
        color: #1a0dab;
        text-decoration: none;
    }

    .trend-title a:hover {
        text-decoration: underline;
    }

    .trend-info {
        color: #5f6368;
        font-size: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .trend-traffic i,
    .trend-time i {
        margin-right: 3px;
    }

    @media (max-width: 576px) {
        .trend-info {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inisialisasi semua slider (kode yang sudah ada)
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
        
        initializeSlider('prev-isu-slide', 'next-isu-slide', 'isu-slide', 'slider-dot:not(.lainnya-dot):not(.google-dot):not(.x-dot)');
        initializeSlider('prev-lainnya-slide', 'next-lainnya-slide', 'lainnya-slide', 'lainnya-dot');
        initializeSlider('prev-google-slide', 'next-google-slide', 'google-slide', 'google-dot');
        initializeSlider('prev-x-slide', 'next-x-slide', 'x-slide', 'x-dot');

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
</script>
@endsection
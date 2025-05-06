<!-- resources/views/trending/index.blade.php -->
@extends(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isEditor()) ? 'layouts.admin' : 'layouts.app')

@section('title', 'Trending Terpilih')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="mb-2">Trending Topik Terpilih</h1>
            <div class="d-flex justify-content-end align-items-center mb-3">
                <form action="{{ route('trending.selected') }}" method="GET" class="d-flex align-items-center gap-2">
                    <div class="input-group" style="max-width: 220px;">
                        <span class="input-group-text bg-white"><i class="bi bi-calendar3"></i></span>
                        <input type="date" class="form-control" name="date" value="{{ request('date', date('Y-m-d')) }}">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filter</button>
                    <a href="{{ route('trending.selected') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Google Trending Card -->
        <div class="col-lg-6 mb-4">
            <div class="card border-info h-100">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Trending Google</h5>
                    @if(Auth::user()->isAdmin() || Auth::user()->isEditor())
                        <a href="{{ route('trending.manageGoogleSelected', ['date' => request('date', date('Y-m-d'))]) }}" class="btn btn-sm btn-light">
                            <i class="bi bi-gear-fill"></i> Kelola Topik
                        </a>
                    @endif
                </div>
                <div class="card-body p-0">
                @if(($selectedGoogleTrendings ?? collect([]))->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($selectedGoogleTrendings as $index => $trending)
                                <div class="list-group-item">
                                    <div class="d-flex align-items-start">
                                        <div class="trend-rank me-3 {{ $index < 3 ? 'top-'.($index+1) : '' }}">{{ $index + 1 }}</div>
                                        <div class="trend-content">
                                            <h6 class="mb-1">
                                                <a href="{{ $trending->url }}" target="_blank" class="text-decoration-none">
                                                    {{ $trending->judul }}
                                                </a>
                                            </h6>
                                            <p class="text-muted mb-0 small">
                                                <i class="bi bi-clock me-1"></i> {{ $trending->tanggal->format('d M Y - H:i') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center p-4">
                            <div class="alert alert-info">
                                <p class="mb-0"><i class="bi bi-info-circle me-2"></i> Belum ada trending Google yang dipilih.</p>
                                @if(Auth::user()->isAdmin() || Auth::user()->isEditor())
                                    <a href="{{ route('trending.manageGoogleSelected', ['date' => $date]) }}" class="btn btn-sm btn-primary mt-2">
                                        Pilih Trending
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="bi bi-google me-1"></i> Google Trends
                        </small>
                        <!-- <small class="text-muted">
                            Update terakhir: {{ $selectedGoogleTrendings->count() > 0 ? $selectedGoogleTrendings->sortByDesc('updated_at')->first()->updated_at->format('d/m/Y H:i') : 'Belum ada data' }}
                        </small> -->
                    </div>
                </div>
            </div>
        </div>

        <!-- X Trending Card -->
        <div class="col-lg-6 mb-4">
            <div class="card border-dark h-100">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Trending X</h5>
                    @if(Auth::user()->isAdmin() || Auth::user()->isEditor())
                        <a href="{{ route('trending.manageXSelected', ['date' => request('date', date('Y-m-d'))]) }}" class="btn btn-sm btn-light">
                            <i class="bi bi-gear-fill"></i> Kelola Topik
                        </a>
                    @endif
                </div>
                <div class="card-body p-0">
                @if(($selectedXTrendings ?? collect([]))->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($selectedXTrendings as $index => $trending)
                                <div class="list-group-item">
                                    <div class="d-flex align-items-start">
                                        <div class="trend-rank me-3 {{ $index < 3 ? 'top-'.($index+1) : '' }}">{{ $index + 1 }}</div>
                                        <div class="trend-content">
                                            <h6 class="mb-1">
                                                <a href="{{ $trending->url }}" target="_blank" class="text-decoration-none">
                                                    {{ $trending->judul }}
                                                </a>
                                            </h6>
                                            <p class="text-muted mb-0 small">
                                                <i class="bi bi-clock me-1"></i> {{ $trending->tanggal->format('d M Y - H:i') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center p-4">
                            <div class="alert alert-info">
                                <p class="mb-0"><i class="bi bi-info-circle me-2"></i> Belum ada trending X yang dipilih.</p>
                                @if(Auth::user()->isAdmin() || Auth::user()->isEditor())
                                    <a href="{{ route('trending.manageXSelected', ['date' => $date]) }}" class="btn btn-sm btn-primary mt-2">
                                        Pilih Trending
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="bi bi-x me-1"></i> X Trends
                        </small>
                        <!-- <small class="text-muted">
                            Update terakhir: {{ $selectedXTrendings->count() > 0 ? $selectedXTrendings->sortByDesc('updated_at')->first()->updated_at->format('d/m/Y H:i') : 'Belum ada data' }}
                        </small> -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<!-- <link rel="stylesheet" href="{{ asset('css/layouts/app.css') }}">  -->
<style>
    .trend-rank {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
        color: #6c757d;
        font-weight: 700;
        border-radius: 50%;
        flex-shrink: 0;
    }
    
    .trend-rank.top-1 {
        background-color: #d4af37; /* Gold */
        color: white;
    }
    
    .trend-rank.top-2 {
        background-color: #C0C0C0; /* Silver */
        color: #333;
    }
    
    .trend-rank.top-3 {
        background-color: #CD7F32; /* Bronze */
        color: white;
    }
    
    .card-header.bg-info {
        background: linear-gradient(135deg, #4285f4, #0d6efd) !important;
    }
    
    .card-header.bg-dark {
        background: linear-gradient(135deg, #333333, #212529) !important;
    }
    
    .card.border-info {
        border-color: #4285f4 !important;
    }
    
    .card.border-dark {
        border-color: #333333 !important;
    }
    
    .trend-content h6 {
        font-weight: 600;
        line-height: 1.4;
    }
    
    .trend-content a {
        color: #212529;
        transition: color 0.2s;
    }
    
    .trend-content a:hover {
        color: #0d6efd;
    }
</style>
@endsection
@extends(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isEditor()) ? 'layouts.admin' : 'layouts.app')

@section('title', $isu->judul)

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Beranda</a></li>
                    <li class="breadcrumb-item">
                        @if($isu->isu_strategis)
                            <a href="{{ route('isu.index') }}">Isu Strategis</a>
                        @else
                            <a href="{{ route('isu.index') }}">Isu Lainnya</a>
                        @endif
                    </li>
                    <li class="breadcrumb-item active">{{ $isu->judul }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">{{ $isu->isu_strategis ? 'Isu Strategis' : 'Isu Lainnya' }}</h5>
                <h2 class="mb-0">{{ $isu->judul }}</h2>
            </div>
                <div>
                    <!-- Tone -->
                    <span class="badge {{ $isu->refTone && $isu->refTone->nama == 'Positif' ? 'bg-success' : 'bg-danger' }} p-2">
                        {{ $isu->refTone ? ucfirst($isu->refTone->nama) : ucfirst($isu->tone) }}
                    </span>

                    <!-- Skala -->
                    <span class="badge bg-warning p-2 ms-2">
                        {{ $isu->refSkala ? strtoupper($isu->refSkala->nama) : $isu->skala }}
                    </span>
                </div>
        </div>
        
        <div class="card-body">
            <!-- Main Image if available -->
            @if($isu->main_image)
                <div class="text-center mb-4">
                    <img src="{{ asset('storage/' . $isu->main_image) }}" alt="{{ $isu->judul }}" class="img-fluid rounded" style="max-height: 400px;">
                </div>
            @endif
            
            <section class="mb-4">
                <h3>Rangkuman</h3>
                <div>{!! $isu->rangkuman !!}</div>
            </section>
            </section>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Narasi Positif</h5>
                        </div>
                        <div class="card-body bg-light">
                            <div>{!! $isu->narasi_positif !!}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">Narasi Negatif</h5>
                        </div>
                        <div class="card-body bg-light">
                            <div>{!! $isu->narasi_negatif !!}</div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- Referensi-referensi -->
            <section>
                <h3 class="mb-3">Sumber Berita</h3>
                <div class="row">
                    @forelse($isu->referensi as $ref)
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0 me-3">
                                            @if($ref->thumbnail)
                                                <img src="{{ $ref->thumbnail }}" alt="{{ $ref->judul }}" class="img-fluid" style="width: 80px; height: 80px; object-fit: cover;">
                                            @else
                                                <div class="bg-secondary d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                                    <i class="bi bi-image text-white fs-1"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <h5 class="card-title">{{ $ref->judul }}</h5>
                                            <a href="{{ $ref->url }}" target="_blank" class="text-decoration-none">
                                                Lihat Sumber
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <p>Tidak ada sumber berita terkait.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
        
        <div class="card-footer">
            <div class="d-flex justify-content-between">
                <div>
                    <span class="text-muted">Tanggal: {{ $isu->tanggal->translatedFormat('d F Y') }}</span>
                </div>
                @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isEditor()))
                    <div>
                        <a href="{{ route('isu.edit', $isu) }}" class="btn btn-warning btn-sm me-2">Edit</a>
                        <form action="{{ route('isu.destroy', $isu) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus isu ini?')">Hapus</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
    .card-header h2 {
        font-size: 1.75rem;
    }
</style>
@endsection
<!-- resources/views/isu/admin/show.blade.php -->
@extends('layouts.admin')

@section('title', $isu->judul)

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">{{ $isu->judul }}</h1>
    <div>
        <a href="{{ route('isu.edit', $isu) }}" class="btn btn-warning">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <form action="{{ route('isu.destroy', $isu) }}" method="POST" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus isu ini?')">
                <i class="bi bi-trash"></i> Hapus
            </button>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <span class="badge {{ $isu->isu_strategis ? 'bg-info' : 'bg-secondary' }}">
                {{ $isu->isu_strategis ? 'Isu Strategis' : 'Isu Lainnya' }}
            </span>
            <span class="ms-2 badge {{ $isu->tone == 'positif' ? 'bg-success' : 'bg-danger' }}">
                {{ ucfirst($isu->tone) }}
            </span>
            <span class="ms-2 badge bg-warning text-dark">{{ $isu->skala }}</span>
            <span class="ms-2 badge bg-light text-dark">{{ $isu->kategori }}</span>
        </div>
        <div>
            <span class="text-muted">{{ $isu->tanggal->format('d F Y') }}</span>
        </div>
    </div>
    
    <div class="card-body">
        <section class="mb-4">
            <h3>Rangkuman</h3>
            <p>{{ $isu->rangkuman }}</p>
        </section>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Narasi Positif</h5>
                    </div>
                    <div class="card-body bg-light">
                        <p>{{ $isu->narasi_positif }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">Narasi Negatif</h5>
                    </div>
                    <div class="card-body bg-light">
                        <p>{{ $isu->narasi_negatif }}</p>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="mb-3">Sumber Berita</h3>
        <div class="row">
            @forelse($isu->referensi as $ref)
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    @if($ref->thumbnail)
                                        <img src="{{ asset('storage/' . $ref->thumbnail) }}" alt="{{ $ref->judul }}" class="img-fluid" style="width: 80px; height: 80px; object-fit: cover;">
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
    </div>
</div>
@endsection
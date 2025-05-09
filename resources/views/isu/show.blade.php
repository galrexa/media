<!-- resources/views/isu/show.blade.php -->
@extends(
    auth()->check() &&
    (
        auth()->user()->isAdmin() ||
        auth()->user()->isEditor() ||
        auth()->user()->isVerifikator1() ||
        auth()->user()->isVerifikator2()
    )
    ? 'layouts.admin'
    : 'layouts.app'
)

@section('title', $isu->judul)

@section('content')
<div class="container">
    <div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <!-- <h2 class="page-title fw-bold mb-0">Daftar Isu</h2> -->
    </div>
        <div class="col-md-6 text-md-end">
            @auth
                @if(auth()->user()->isAdmin() || auth()->user()->isEditor())
                    <a href="{{ route('isu.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i> Tambah Isu Baru
                    </a>
                @endif
            @endauth
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">{{ $isu->isu_strategis ? 'Isu Strategis' : 'Isu Regional' }}</h5>
                <h2 class="mb-0">{{ $isu->judul }}</h2>
            </div>
            <div class="d-flex align-items-end">
                <div class="d-flex flex-column align-items-center me-3">
                    <span class="small mb-1">Tone</span>
                    <span class="badge p-2 fs-4" style="background-color: {{ $isu->refTone && $isu->tone ? $isu->refTone->warna : '#d3d3d3' }}">
                        {{ $isu->refTone && $isu->tone ? ucfirst($isu->refTone->nama) : '-' }}
                    </span>
                </div>
                <div class="d-flex flex-column align-items-center">
                    <span class="small mb-1">Skala</span>
                    <span class="badge p-2 fs-4" style="background-color: {{ $isu->refSkala && $isu->skala ? $isu->refSkala->warna : '#d3d3d3' }}">
                        {{ $isu->refSkala && $isu->skala ? $isu->refSkala->nama : '-' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="card-body">
            @if($isu->main_image)
                <div class="text-center mb-4">
                    <img src="{{ asset('storage/' . $isu->main_image) }}" alt="{{ $isu->judul }}" class="img-fluid rounded" style="max-height: 400px;">
                </div>
            @endif

            <section class="mb-4">
                <h3>Rangkuman</h3>
                <div>{!! $isu->rangkuman !!}</div>
            </section>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card h-100 narasi-card">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-plus-circle"></i>
                            <h5>Narasi Positif</h5>
                        </div>
                        <div class="card-body">
                            <div class="content-text">{!! $isu->narasi_positif !!}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100 narasi-card">
                        <div class="card-header bg-danger text-white">
                            <i class="fas fa-minus-circle"></i>
                            <h5>Narasi Negatif</h5>
                        </div>
                        <div class="card-body">
                            <div class="content-text">{!! $isu->narasi_negatif !!}</div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <section>
                <h3 class="mb-3">Sumber Berita</h3>
                <div class="row">
                    @forelse($isu->referensi as $ref)
                        @php
                            $meta = \App\Helpers\ThumbnailHelper::getUrlMetadata($ref->url);
                            $thumb = $ref->thumbnail ?: $meta['image'];
                            $desc = $ref->meta_description ?: $meta['description'];
                        @endphp
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0 me-3">
                                            @if($thumb)
                                                <img src="{{ $thumb }}" alt="{{ $ref->judul }}" class="img-fluid" style="width: 80px; height: 80px; object-fit: cover;">
                                            @else
                                                <div class="bg-secondary d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                                    <i class="bi bi-image text-white fs-1"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <h5 class="card-title">
                                                <a href="{{ $ref->url }}" target="_blank" class="text-decoration-none">{{ $ref->judul }}</a>
                                            </h5>
                                            @if(!empty($desc))
                                                <p class="card-text text-muted small">{{ Str::limit($desc, 100) }}</p>
                                            @endif
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
                @if(Auth::check() && (
                    // Admin memiliki akses penuh untuk edit
                    Auth::user()->isAdmin() || 
                    // Editor hanya bisa mengedit isu yang dia buat sendiri dan dalam status yang memungkinkan
                    (Auth::user()->isEditor() && $isu->created_by == Auth::user()->id && $isu->canBeEditedBy('editor')) ||
                    // Verifikator1 hanya bisa mengedit jika isu dalam status yang sesuai dengan perannya
                    // dan tidak dalam status menunggu_verifikasi2 atau dipublikasikan
                    (Auth::user()->hasRole('verifikator1') && $isu->canBeEditedBy('verifikator1') && 
                    !in_array($isu->status, ['menunggu_verifikasi2', 'dipublikasikan'])) ||
                    // Verifikator2 hanya bisa mengedit jika isu dalam status yang sesuai dengan perannya
                    // dan tidak dalam status dipublikasikan
                    (Auth::user()->hasRole('verifikator2') && $isu->canBeEditedBy('verifikator2') && 
                    $isu->status != 'dipublikasikan')
                ))
                    <div>
                        <a href="{{ route('isu.edit', $isu) }}" class="btn-action btn-edit" title="Edit" aria-label="Edit isu">
                            <i class="fas fa-edit"></i>
                        </a>

                        @if(Auth::user()->isAdmin() || 
                        (Auth::user()->isEditor() && $isu->created_by == Auth::user()->id && $isu->canBeDeleted()))
                            <form action="{{ route('isu.destroy', $isu) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-action btn-delete" title="Hapus" aria-label="Hapus isu" 
                                        onclick="return confirm('Apakah Anda yakin ingin menghapus isu ini?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                @endif


            </div>
            @if($isu->status && $isu->status->nama == 'Ditolak' && $isu->alasan_penolakan)
                <div class="alert alert-danger mt-3">
                    <h5 class="alert-heading"><i class="fas fa-times-circle me-2"></i>Alasan Penolakan: </h5>
                    <p class="mb-0">{{ $isu->alasan_penolakan }}</p>
                </div>
            @endif
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

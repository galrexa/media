@extends(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isEditor()) ? 'layouts.admin' : 'layouts.app')

@section('title', 'Daftar Trending')

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Beranda</a></li>
                    <li class="breadcrumb-item active">Trending</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Daftar Trending</h1>
        @if(Auth::user()->isAdmin() || Auth::user()->isEditor())
            <a href="{{ route('trending.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tambah Trending Baru
            </a>
        @endif
    </div>

    <!-- Trending Google -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Trending Google</h5>
        </div>
        <div class="card-body">
            @if($trendingGoogle->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Tanggal</th>
                                <th>URL</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trendingGoogle as $trending)
                                <tr>
                                    <td>{{ $trending->judul }}</td>
                                    <td>{{ $trending->tanggal->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <a href="{{ $trending->url }}" target="_blank" class="text-decoration-none">
                                            {{ Str::limit($trending->url, 30) }}
                                        </a>
                                    </td>
                                    <td>
                                        @if(Auth::user()->isAdmin() || Auth::user()->isEditor())
                                            <a href="{{ route('trending.edit', $trending) }}" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <form action="{{ route('trending.destroy', $trending) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus trending ini?')">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $trendingGoogle->links() }}
            @else
                <p class="text-center">Tidak ada trending Google untuk ditampilkan</p>
            @endif
        </div>
    </div>

    <!-- Trending X -->
    <div class="card">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Trending X</h5>
        </div>
        <div class="card-body">
            @if($trendingX->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Tanggal</th>
                                <th>URL</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trendingX as $trending)
                                <tr>
                                    <td>{{ $trending->judul }}</td>
                                    <td>{{ $trending->tanggal->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <a href="{{ $trending->url }}" target="_blank" class="text-decoration-none">
                                            {{ Str::limit($trending->url, 30) }}
                                        </a>
                                    </td>
                                    <td>
                                        @if(Auth::user()->isAdmin() || Auth::user()->isEditor())
                                            <a href="{{ route('trending.edit', $trending) }}" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <form action="{{ route('trending.destroy', $trending) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus trending ini?')">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $trendingX->links() }}
            @else
                <p class="text-center">Tidak ada trending X untuk ditampilkan</p>
            @endif
        </div>
    </div>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
@endsection
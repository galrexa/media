<!-- resources/views/dashboard/admin.blade.php -->
@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard Admin</h1>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Isu</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ App\Models\Isu::count() }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-file-text fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Isu Strategis</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ App\Models\Isu::where('isu_strategis', true)->count() }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-star fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Pengguna
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ App\Models\User::count() }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-people fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Isu Terbaru</h6>
                <a href="{{ route('isu.index') }}" class="btn btn-sm btn-primary">Lihat Semua</a>
            </div>
            <div class="card-body">
                <div class="list-group">
                    @foreach(App\Models\Isu::latest()->take(5)->get() as $isu)
                        <a href="{{ route('isu.show', $isu) }}" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">{{ $isu->judul }}</h6>
                                <small>{{ $isu->tanggal->format('d M Y') }}</small>
                            </div>
                            <small>{{ $isu->isu_strategis ? 'Strategis' : 'Non-Strategis' }}</small>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Tindakan Cepat</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 mb-3">
                        <a href="{{ route('isu.create') }}" class="btn btn-primary w-100 py-3">
                            <i class="bi bi-plus-circle"></i><br>
                            Tambah Isu Baru
                        </a>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="{{ route('images.create') }}" class="btn btn-success w-100 py-3">
                            <i class="bi bi-image"></i><br>
                            Upload Gambar
                        </a>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="#" class="btn btn-info w-100 py-3">
                            <i class="bi bi-graph-up"></i><br>
                            Kelola Trending
                        </a>
                    </div>
                    @if(Auth::user()->isAdmin())
                    <div class="col-6 mb-3">
                        <a href="{{ route('users.index') }}" class="btn btn-secondary w-100 py-3">
                            <i class="bi bi-people"></i><br>
                            Kelola Pengguna
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
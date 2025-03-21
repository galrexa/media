<!-- resources/views/isu/index.blade.php -->
@extends('layouts.app')

@section('title', 'Daftar Isu')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Daftar Isu</h1>
                @if(Auth::user()->isAdmin() || Auth::user()->isEditor())
                    <a href="{{ route('isu.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah Isu Baru
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Isu Strategis -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Isu Strategis</h5>
        </div>
        <div class="card-body">
            @if(count($isusStrategis) > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Tone</th>
                                <th>Skala</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($isusStrategis as $isu)
                                <tr>
                                    <td>{{ $isu->judul }}</td>
                                    <td>{{ $isu->tanggal->format('d/m/Y') }}</td>
                                    <td>{{ $isu->kategori }}</td>
                                    <td>
                                        <span class="badge {{ $isu->tone == 'positif' ? 'bg-success' : 'bg-danger' }}">
                                            {{ ucfirst($isu->tone) }}
                                        </span>
                                    </td>
                                    <td>{{ $isu->skala }}</td>
                                    <td>
                                        <a href="{{ route('isu.show', $isu) }}" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i> Lihat
                                        </a>
                                        @if(Auth::user()->isAdmin() || Auth::user()->isEditor())
                                            <a href="{{ route('isu.edit', $isu) }}" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <form action="{{ route('isu.destroy', $isu) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus isu ini?')">
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
                {{ $isusStrategis->links() }}
            @else
                <p class="text-center">Tidak ada isu strategis untuk ditampilkan</p>
            @endif
        </div>
    </div>

    <!-- Isu Lainnya -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">Isu Lainnya</h5>
        </div>
        <div class="card-body">
            @if(count($isusLainnya) > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Skala</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($isusLainnya as $isu)
                                <tr>
                                    <td>{{ $isu->judul }}</td>
                                    <td>{{ $isu->tanggal->format('d/m/Y') }}</td>
                                    <td>{{ $isu->kategori }}</td>
                                    <td>{{ $isu->skala }}</td>
                                    <td>
                                        <a href="{{ route('isu.show', $isu) }}" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i> Lihat
                                        </a>
                                        @if(Auth::user()->isAdmin() || Auth::user()->isEditor())
                                            <a href="{{ route('isu.edit', $isu) }}" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <form action="{{ route('isu.destroy', $isu) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus isu ini?')">
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
                {{ $isusLainnya->links() }}
            @else
                <p class="text-center">Tidak ada isu lainnya untuk ditampilkan</p>
            @endif
        </div>
    </div>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
@endsection
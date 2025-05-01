<!-- resources/views/isu/admin/index.blade.php -->
@extends('layouts.admin')

@section('title', 'Manajemen Isu')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Manajemen Isu</h1>
    <a href="{{ route('isu.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Tambah Isu Baru
    </a>
</div>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#strategis">Isu Strategis</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#lainnya">Isu Regional</a>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="strategis">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Skala</th>
                                <th>Tone</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($isusStrategis as $isu)
                                <tr>
                                    <td>{{ $isu->judul }}</td>
                                    <td>{{ $isu->tanggal->format('d/m/Y') }}</td>
                                    <td>{{ $isu->kategori }}</td>
                                    <td>{{ $isu->skala }}</td>
                                    <td>
                                        <span class="badge {{ $isu->tone == 'positif' ? 'bg-success' : 'bg-danger' }}">
                                            {{ ucfirst($isu->tone) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('isu.show', $isu) }}" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('isu.edit', $isu) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('isu.destroy', $isu) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus isu ini?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">Tidak ada isu strategis untuk ditampilkan</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $isusStrategis->links() }}
            </div>
        </div>
    </div>
    
    <div class="tab-pane fade" id="lainnya">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Skala</th>
                                <th>Tone</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($isusLainnya as $isu)
                                <tr>
                                    <td>{{ $isu->judul }}</td>
                                    <td>{{ $isu->tanggal->format('d/m/Y') }}</td>
                                    <td>{{ $isu->kategori }}</td>
                                    <td>{{ $isu->skala }}</td>
                                    <td>
                                        <span class="badge {{ $isu->tone == 'positif' ? 'bg-success' : 'bg-danger' }}">
                                            {{ ucfirst($isu->tone) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('isu.show', $isu) }}" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('isu.edit', $isu) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('isu.destroy', $isu) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus isu ini?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">Tidak ada isu regional untuk ditampilkan</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $isusLainnya->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
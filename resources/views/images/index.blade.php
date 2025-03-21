<!-- resources/views/images/index.blade.php -->
@extends('layouts.admin')

@section('title', 'Daftar Gambar Harian')

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Beranda</a></li>
                    <li class="breadcrumb-item active">Daftar Gambar Harian</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Daftar Gambar Harian</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <a href="{{ route('images.create') }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-plus-circle"></i> Upload Gambar Baru
                </a>
            </div>
        </div>
        <div class="card-body">
            @if($images->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Gambar 1</th>
                                <th>Gambar 2</th>
                                <th>Gambar 3</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($images as $image)
                                <tr>
                                    <td>{{ $image->tanggal->format('d F Y') }}</td>
                                    <td>
                                        @if($image->image_1)
                                            <img src="{{ asset('storage/' . $image->image_1) }}" alt="Gambar 1" class="img-thumbnail" style="max-height: 50px;">
                                        @else
                                            <span class="text-muted">Tidak ada</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($image->image_2)
                                            <img src="{{ asset('storage/' . $image->image_2) }}" alt="Gambar 2" class="img-thumbnail" style="max-height: 50px;">
                                        @else
                                            <span class="text-muted">Tidak ada</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($image->image_3)
                                            <img src="{{ asset('storage/' . $image->image_3) }}" alt="Gambar 3" class="img-thumbnail" style="max-height: 50px;">
                                        @else
                                            <span class="text-muted">Tidak ada</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('images.edit', $image->tanggal->format('Y-m-d')) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="{{ route('home', ['day' => $image->tanggal->diffInDays(now(), false)]) }}" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i> Lihat
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-center mt-4">
                    {{ $images->links() }}
                </div>
            @else
                <div class="alert alert-info text-center">
                    <p>Belum ada gambar yang diupload.</p>
                    <a href="{{ route('images.create') }}" class="btn btn-primary mt-2">Upload Gambar Baru</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
@endsection
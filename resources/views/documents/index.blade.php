<!-- resources/views/documents/index.blade.php -->
@extends('layouts.admin')

@section('title', 'Daftar Dokumen Harian')

@section('content')
<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Daftar Dokumen Harian</h1>
        <a href="{{ route('documents.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Upload Baru
        </a>
    </div>

    <div class="card">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Dokumen Harian</h5>
        </div>
        <div class="card-body">
            @if($images->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Gambar 1</th>
                                <th>Gambar 2</th>
                                <th>Gambar 3</th>
                                <th>Dokumen PDF</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($images as $image)
                                <tr>
                                    <td>{{ $image->tanggal->format('d F Y') }}</td>
                                    <td>
                                        @if($image->image_1)
                                            <img src="{{ asset('storage/' . $image->image_1) }}" alt="Gambar 1" class="img-thumbnail" style="max-height: 60px;">
                                        @else
                                            <span class="text-muted">Tidak ada</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($image->image_2)
                                            <img src="{{ asset('storage/' . $image->image_2) }}" alt="Gambar 2" class="img-thumbnail" style="max-height: 60px;">
                                        @else
                                            <span class="text-muted">Tidak ada</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($image->image_3)
                                            <img src="{{ asset('storage/' . $image->image_3) }}" alt="Gambar 3" class="img-thumbnail" style="max-height: 60px;">
                                        @else
                                            <span class="text-muted">Tidak ada</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($image->dokumen_url)
                                            <a href="{{ $image->dokumen_url }}" target="_blank" class="btn btn-sm btn-danger">
                                                <i class="bi bi-file-earmark-pdf me-1"></i>
                                            </a>
                                        @else
                                            <span class="text-muted">Tidak ada</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('documents.edit', $image->tanggal->format('Y-m-d')) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil me-1"></i>
                                        </a>
                                        <a href="{{ route('home', ['day' => $image->tanggal->diffInDays(now(), false)]) }}" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye me-1"></i>
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
                <p class="text-center">Belum ada gambar yang diupload.</p>
                <div class="text-center mt-3">
                    <a href="{{ route('documents.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Upload Gambar Baru
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
    .badge {
        font-size: 0.8em;
    }
    
    .table thead th {
        background-color: rgba(0,0,0,0.03);
    }
    
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: none;
        margin-bottom: 1.5rem;
    }
    
    .card-header {
        border-bottom: 1px solid rgba(0,0,0,0.125);
        padding: 0.75rem 1.25rem;
    }
    
    .card-header h5 {
        font-weight: 600;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    
    .img-thumbnail {
        border-radius: 0.25rem;
        border: 1px solid #dee2e6;
        transition: transform 0.2s;
    }
    
    .img-thumbnail:hover {
        transform: scale(1.1);
    }
</style>
@endsection
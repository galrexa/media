<!-- resources/views/documents/create.blade.php -->
@extends('layouts.admin')

@section('title', 'Upload Dokumen Harian')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h2">Upload Dokumen Harian</h1>
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Upload Dokumen Harian</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tanggal" class="form-label">Tanggal</label>
                            <input type="date" class="form-control @error('tanggal') is-invalid @enderror"
                                id="tanggal" name="tanggal"
                                value="{{ old('tanggal', isset($date) ? $date->format('Y-m-d') : today()->format('Y-m-d')) }}" required>
                            @error('tanggal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="mb-0">Gambar 1 (Utama)</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="image_1" class="form-label">Pilih File</label>
                                    <input type="file" class="form-control @error('image_1') is-invalid @enderror" id="image_1" name="image_1" accept="image/*">
                                    <small class="form-text text-muted">Gambar utama (resolusi ideal: 800x400px)</small>
                                    @error('image_1')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                @if(isset($existingImage) && $existingImage->image_1)
                                    <div class="mt-3">
                                        <div class="card">
                                            <div class="card-body p-2 text-center">
                                                <img src="{{ asset('storage/' . $existingImage->image_1) }}" class="img-fluid mb-2" style="max-height: 150px;">
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" id="delete_image_1" name="delete_image_1">
                                                    <label class="form-check-label text-danger" for="delete_image_1">
                                                        Hapus gambar saat ini
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="mb-0">Gambar 2 (Trending)</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="image_2" class="form-label">Pilih File</label>
                                    <input type="file" class="form-control @error('image_2') is-invalid @enderror" id="image_2" name="image_2" accept="image/*">
                                    <small class="form-text text-muted">Gambar trending (resolusi ideal: 400x300px)</small>
                                    @error('image_2')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                @if(isset($existingImage) && $existingImage->image_2)
                                    <div class="mt-3">
                                        <div class="card">
                                            <div class="card-body p-2 text-center">
                                                <img src="{{ asset('storage/' . $existingImage->image_2) }}" class="img-fluid mb-2" style="max-height: 150px;">
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" id="delete_image_2" name="delete_image_2">
                                                    <label class="form-check-label text-danger" for="delete_image_2">
                                                        Hapus gambar saat ini
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="mb-0">Gambar 3 (Banner)</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="image_3" class="form-label">Pilih File</label>
                                    <input type="file" class="form-control @error('image_3') is-invalid @enderror" id="image_3" name="image_3" accept="image/*">
                                    <small class="form-text text-muted">Gambar banner (resolusi ideal: 1200x300px)</small>
                                    @error('image_3')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                @if(isset($existingImage) && $existingImage->image_3)
                                    <div class="mt-3">
                                        <div class="card">
                                            <div class="card-body p-2 text-center">
                                                <img src="{{ asset('storage/' . $existingImage->image_3) }}" class="img-fluid mb-2" style="max-height: 150px;">
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" id="delete_image_3" name="delete_image_3">
                                                    <label class="form-check-label text-danger" for="delete_image_3">
                                                        Hapus gambar saat ini
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Tambahkan setelah bagian gambar dalam file resources/views/documents/create.blade.php -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Dokumen PDF (Laporan Harian)</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="dokumen_url" class="form-label">URL Dokumen PDF</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-file"></i></span>
                                        <input type="url" class="form-control @error('dokumen_url') is-invalid @enderror" id="dokumen_url" name="dokumen_url"
                                            value="{{ old('dokumen_url', isset($existingImage) ? $existingImage->dokumen_url : '') }}"
                                            placeholder="https://example.com/laporan-harian.pdf">
                                    </div>
                                    <small class="form-text text-muted">Masukkan URL dokumen PDF laporan harian (opsional)</small>
                                    @error('dokumen_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                @if(isset($existingImage) && $existingImage->dokumen_url)
                                    <div class="mt-2">
                                        <div class="card">
                                            <div class="card-body p-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <i class="fas fa-file fs-4"></i>
                                                        <a href="{{ $existingImage->dokumen_url }}" target="_blank" class="ms-2">Lihat Dokumen</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <a href="{{ route('home') }}" class="btn btn-secondary me-2">
                        <i class="bi bi-x-circle"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-cloud-upload"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .card-header h6 {
        font-weight: 600;
    }

    .form-check-label.text-danger {
        font-size: 0.9rem;
    }

    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
</style>
@endsection
@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ambil elemen input tanggal
    const tanggalInput = document.getElementById('tanggal');

    // Event listener untuk perubahan tanggal
    tanggalInput.addEventListener('change', function() {
        // Redirect ke halaman create dengan parameter tanggal baru
        window.location.href = "{{ route('documents.create') }}?tanggal=" + this.value;
    });
});
</script>
@endsection

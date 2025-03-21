@extends('layouts.admin')

@section('title', 'Edit Isu')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Isu</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('isu.update', $isu) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="tanggal" class="form-label">Tanggal</label>
                        <input type="date" class="form-control @error('tanggal') is-invalid @enderror" id="tanggal" name="tanggal" value="{{ old('tanggal', $isu->tanggal->format('Y-m-d')) }}" required>
                        @error('tanggal')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="image_1" class="form-label">Gambar 1 (Utama)</label>
                        <input type="file" class="form-control @error('image_1') is-invalid @enderror" id="image_1" name="image_1" accept="image/*">
                        <small class="form-text text-muted">Gambar utama (resolusi ideal: 800x400px)</small>
                        @if($isu->main_image)
                            <div class="mt-2">
                                <img src="{{ asset('storage/' . $isu->main_image) }}" alt="{{ $isu->judul }}" class="img-thumbnail" style="max-height: 100px;">
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" id="delete_main_image" name="delete_main_image">
                                    <label class="form-check-label text-danger" for="delete_main_image">
                                        Hapus gambar saat ini
                                    </label>
                                </div>
                            </div>
                        @endif
                        @error('image_1')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="image_2" class="form-label">Gambar 2 (Trending)</label>
                        <input type="file" class="form-control @error('image_2') is-invalid @enderror" id="image_2" name="image_2" accept="image/*">
                        <small class="form-text text-muted">Gambar trending (resolusi ideal: 400x300px)</small>
                        @if($isu->thumbnail_image)
                            <div class="mt-2">
                                <img src="{{ asset('storage/' . $isu->thumbnail_image) }}" alt="{{ $isu->judul }}" class="img-thumbnail" style="max-height: 100px;">
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" id="delete_thumbnail_image" name="delete_thumbnail_image">
                                    <label class="form-check-label text-danger" for="delete_thumbnail_image">
                                        Hapus gambar saat ini
                                    </label>
                                </div>
                            </div>
                        @endif
                        @error('image_2')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="image_3" class="form-label">Gambar 3 (Banner)</label>
                        <input type="file" class="form-control @error('image_3') is-invalid @enderror" id="image_3" name="image_3" accept="image/*">
                        <small class="form-text text-muted">Gambar banner (resolusi ideal: 1200x300px)</small>
                        @if($isu->banner_image)
                            <div class="mt-2">
                                <img src="{{ asset('storage/' . $isu->banner_image) }}" alt="{{ $isu->judul }}" class="img-thumbnail" style="max-height: 100px;">
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" id="delete_banner_image" name="delete_banner_image">
                                    <label class="form-check-label text-danger" for="delete_banner_image">
                                        Hapus gambar saat ini
                                    </label>
                                </div>
                            </div>
                        @endif
                        @error('image_3')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <a href="{{ route('home') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Upload Gambar</button>
            </div>
        </form>
    </div>
</div>
@endsection
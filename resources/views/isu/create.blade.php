<!-- resources/views/isu/create.blade.php -->
@extends('layouts.app')

@section('title', 'Tambah Isu Baru')

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('isu.index') }}">Isu</a></li>
                    <li class="breadcrumb-item active">Tambah Isu Baru</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Tambah Isu Baru</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('isu.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="judul" class="form-label">Judul Isu</label>
                            <input type="text" class="form-control @error('judul') is-invalid @enderror" id="judul" name="judul" value="{{ old('judul') }}" required>
                            @error('judul')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="tanggal" class="form-label">Tanggal</label>
                            <input type="date" class="form-control @error('tanggal') is-invalid @enderror" id="tanggal" name="tanggal" value="{{ old('tanggal') ?? date('Y-m-d') }}" required>
                            @error('tanggal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="main_image" class="form-label">Gambar Utama</label>
                            <input type="file" class="form-control @error('main_image') is-invalid @enderror" id="main_image" name="main_image" accept="image/*">
                            <small class="form-text text-muted">Gambar utama untuk isu (resolusi ideal: 800x400px)</small>
                            @error('main_image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="thumbnail_image" class="form-label">Gambar Thumbnail</label>
                            <input type="file" class="form-control @error('thumbnail_image') is-invalid @enderror" id="thumbnail_image" name="thumbnail_image" accept="image/*">
                            <small class="form-text text-muted">Thumbnail untuk isu (resolusi ideal: 400x300px)</small>
                            @error('thumbnail_image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="banner_image" class="form-label">Gambar Banner</label>
                            <input type="file" class="form-control @error('banner_image') is-invalid @enderror" id="banner_image" name="banner_image" accept="image/*">
                            <small class="form-text text-muted">Banner untuk isu (resolusi ideal: 1200x300px)</small>
                            @error('banner_image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="isu_strategis" name="isu_strategis" value="1" {{ old('isu_strategis') ? 'checked' : '' }}>
                            <label class="form-check-label" for="isu_strategis">Isu Strategis</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="kategori" class="form-label">Kategori</label>
                            <input type="text" class="form-control @error('kategori') is-invalid @enderror" id="kategori" name="kategori" value="{{ old('kategori') }}" required>
                            @error('kategori')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="skala" class="form-label">Skala</label>
                            <select class="form-select @error('skala') is-invalid @enderror" id="skala" name="skala" required>
                                <option value="">Pilih Skala</option>
                                <option value="Kecil" {{ old('skala') == 'Kecil' ? 'selected' : '' }}>Kecil</option>
                                <option value="Sedang" {{ old('skala') == 'Sedang' ? 'selected' : '' }}>Sedang</option>
                                <option value="Besar" {{ old('skala') == 'Besar' ? 'selected' : '' }}>Besar</option>
                            </select>
                            @error('skala')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>                 
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="tone" class="form-label">Tone Isu</label>
                            <select class="form-select @error('tone') is-invalid @enderror" id="tone" name="tone" required>
                                <option value="">Pilih Tone</option>
                                <option value="positif" {{ old('tone') == 'positif' ? 'selected' : '' }}>Positif</option>
                                <option value="negatif" {{ old('tone') == 'negatif' ? 'selected' : '' }}>Negatif</option>
                            </select>
                            @error('tone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="rangkuman" class="form-label">Rangkuman</label>
                    <textarea class="form-control @error('rangkuman') is-invalid @enderror" id="rangkuman" name="rangkuman" rows="5" required>{{ old('rangkuman') }}</textarea>
                    @error('rangkuman')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="narasi_positif" class="form-label">Narasi Positif</label>
                            <textarea class="form-control @error('narasi_positif') is-invalid @enderror" id="narasi_positif" name="narasi_positif" rows="5" required>{{ old('narasi_positif') }}</textarea>
                            @error('narasi_positif')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="narasi_negatif" class="form-label">Narasi Negatif</label>
                            <textarea class="form-control @error('narasi_negatif') is-invalid @enderror" id="narasi_negatif" name="narasi_negatif" rows="5" required>{{ old('narasi_negatif') }}</textarea>
                            @error('narasi_negatif')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <h5 class="mt-4 mb-3">Referensi</h5>
                <div id="referensi-container">
                    <div class="referensi-item border p-3 mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Judul Referensi</label>
                                    <input type="text" class="form-control" name="referensi_judul[]">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">URL</label>
                                    <input type="url" class="form-control" name="referensi_url[]">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Thumbnail</label>
                            <input type="file" class="form-control" name="referensi_thumbnail[]" accept="image/*">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <button type="button" id="add-referensi" class="btn btn-outline-secondary">
                        <i class="bi bi-plus-circle"></i> Tambah Referensi
                    </button>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <a href="{{ route('isu.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const addReferensiBtn = document.getElementById('add-referensi');
        const referensiContainer = document.getElementById('referensi-container');
        
        addReferensiBtn.addEventListener('click', function() {
            const referensiItem = document.querySelector('.referensi-item').cloneNode(true);
            
            // Clear input values
            const inputs = referensiItem.querySelectorAll('input');
            inputs.forEach(input => {
                input.value = '';
            });
            
            referensiContainer.appendChild(referensiItem);
        });
    });
</script>
@endsection
<!-- resources/views/isu/edit.blade.php -->
@extends('layouts.app')

@section('title', 'Edit Isu')

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('isu.index') }}">Isu</a></li>
                    <li class="breadcrumb-item active">Edit Isu</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-warning text-dark">
            <h4 class="mb-0">Edit Isu</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('isu.update', $isu) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="judul" class="form-label">Judul Isu</label>
                            <input type="text" class="form-control @error('judul') is-invalid @enderror" id="judul" name="judul" value="{{ old('judul', $isu->judul) }}" required>
                            @error('judul')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
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
                            <label for="main_image" class="form-label">Gambar Utama</label>
                            <input type="file" class="form-control @error('main_image') is-invalid @enderror" id="main_image" name="main_image" accept="image/*">
                            <small class="form-text text-muted">Gambar utama untuk isu (resolusi ideal: 800x400px)</small>
                            @if($isu->main_image)
                                <div class="mt-2">
                                    <img src="{{ asset('storage/' . $isu->main_image) }}" alt="{{ $isu->judul }}" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                            @endif
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
                            @if($isu->thumbnail_image)
                                <div class="mt-2">
                                    <img src="{{ asset('storage/' . $isu->thumbnail_image) }}" alt="{{ $isu->judul }}" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                            @endif
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
                            @if($isu->banner_image)
                                <div class="mt-2">
                                    <img src="{{ asset('storage/' . $isu->banner_image) }}" alt="{{ $isu->judul }}" class="img-thumbnail" style="max-height: 100px;">
                                </div>
                            @endif
                            @error('banner_image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="isu_strategis" name="isu_strategis" value="1" {{ old('isu_strategis', $isu->isu_strategis) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isu_strategis">Isu Strategis</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="kategori" class="form-label">Kategori</label>
                            <input type="text" class="form-control @error('kategori') is-invalid @enderror" id="kategori" name="kategori" value="{{ old('kategori', $isu->kategori) }}" required>
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
                                <option value="Kecil" {{ old('skala', $isu->skala) == 'Kecil' ? 'selected' : '' }}>Kecil</option>
                                <option value="Sedang" {{ old('skala', $isu->skala) == 'Sedang' ? 'selected' : '' }}>Sedang</option>
                                <option value="Besar" {{ old('skala', $isu->skala) == 'Besar' ? 'selected' : '' }}>Besar</option>
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
                                <option value="positif" {{ old('tone', $isu->tone) == 'positif' ? 'selected' : '' }}>Positif</option>
                                <option value="negatif" {{ old('tone', $isu->tone) == 'negatif' ? 'selected' : '' }}>Negatif</option>
                            </select>
                            @error('tone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="rangkuman" class="form-label">Rangkuman</label>
                    <textarea class="form-control @error('rangkuman') is-invalid @enderror" id="rangkuman" name="rangkuman" rows="5" required>{{ old('rangkuman', $isu->rangkuman) }}</textarea>
                    @error('rangkuman')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="narasi_positif" class="form-label">Narasi Positif</label>
                            <textarea class="form-control @error('narasi_positif') is-invalid @enderror" id="narasi_positif" name="narasi_positif" rows="5" required>{{ old('narasi_positif', $isu->narasi_positif) }}</textarea>
                            @error('narasi_positif')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="narasi_negatif" class="form-label">Narasi Negatif</label>
                            <textarea class="form-control @error('narasi_negatif') is-invalid @enderror" id="narasi_negatif" name="narasi_negatif" rows="5" required>{{ old('narasi_negatif', $isu->narasi_negatif) }}</textarea>
                            @error('narasi_negatif')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <h5 class="mt-4 mb-3">Referensi</h5>
                <div id="referensi-container">
                    @if($isu->referensi->count() > 0)
                        @foreach($isu->referensi as $index => $referensi)
                            <div class="referensi-item border p-3 mb-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Judul Referensi</label>
                                            <input type="text" class="form-control" name="referensi_judul[]" value="{{ $referensi->judul }}">
                                            <input type="hidden" name="referensi_id[]" value="{{ $referensi->id }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">URL</label>
                                            <input type="url" class="form-control" name="referensi_url[]" value="{{ $referensi->url }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <label class="form-label">Thumbnail</label>
                                            <input type="file" class="form-control" name="referensi_thumbnail[]" accept="image/*">
                                        </div>
                                        <div class="col-md-4">
                                            @if($referensi->thumbnail)
                                                <div class="mt-2">
                                                    <img src="{{ asset('storage/' . $referensi->thumbnail) }}" alt="{{ $referensi->judul }}" class="img-thumbnail" style="max-height: 100px;">
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="button" class="btn btn-sm btn-danger remove-referensi">Hapus Referensi</button>
                                </div>
                            </div>
                        @endforeach
                    @else
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
                            <div class="text-end">
                                <button type="button" class="btn btn-sm btn-danger remove-referensi">Hapus Referensi</button>
                            </div>
                        </div>
                    @endif
                </div>
                
                <div class="mb-3">
                    <button type="button" id="add-referensi" class="btn btn-outline-secondary">
                        <i class="bi bi-plus-circle"></i> Tambah Referensi
                    </button>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <a href="{{ route('isu.show', $isu) }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
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
        
        // Add new reference
        addReferensiBtn.addEventListener('click', function() {
            const referensiItem = document.querySelector('.referensi-item').cloneNode(true);
            
            // Clear input values
            const inputs = referensiItem.querySelectorAll('input');
            inputs.forEach(input => {
                if (input.type !== 'hidden') {
                    input.value = '';
                }
            });
            
            // Remove thumbnail preview if exists
            const imgThumbnail = referensiItem.querySelector('img');
            if (imgThumbnail) {
                imgThumbnail.parentElement.parentElement.remove();
            }
            
            // Add remove event listener to the new item
            const removeBtn = referensiItem.querySelector('.remove-referensi');
            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    if (referensiContainer.querySelectorAll('.referensi-item').length > 1) {
                        referensiItem.remove();
                    } else {
                        alert('Minimal harus ada satu referensi!');
                    }
                });
            }
            
            referensiContainer.appendChild(referensiItem);
        });
        
        // Remove reference
        const removeBtns = document.querySelectorAll('.remove-referensi');
        removeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                if (referensiContainer.querySelectorAll('.referensi-item').length > 1) {
                    this.closest('.referensi-item').remove();
                } else {
                    alert('Minimal harus ada satu referensi!');
                }
            });
        });
    });
</script>
@endsection
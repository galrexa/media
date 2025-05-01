<!-- resources/views/images/edit.blade.php -->
@extends('layouts.admin')

@section('title', 'Edit Dokumen Harian')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Dokumen Harian</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('documents.create') }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-plus-circle"></i> Upload Baru
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-4">
            <label class="form-label">Pilih Tanggal:</label>
            <div class="input-group">
                <select class="form-select" id="date-selector">
                    <option value="">Pilih Tanggal</option>
                    @foreach($availableDates as $date)
                        <option value="{{ $date['date'] }}" {{ $selectedDate->format('Y-m-d') === $date['date'] ? 'selected' : '' }}>{{ $date['formatted'] }}</option>
                    @endforeach
                </select>
                <button class="btn btn-outline-secondary" type="button" id="go-to-date">Pilih</button>
            </div>
        </div>

        @if($image)
            <form action="{{ route('documents.update', $image->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- Tambahkan input hidden untuk tanggal -->
                <input type="hidden" name="tanggal" value="{{ $selectedDate->format('Y-m-d') }}">

                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="image_1" class="form-label">Gambar 1 (Utama)</label>
                            <input type="file" class="form-control @error('image_1') is-invalid @enderror" id="image_1" name="image_1" accept="image/*">
                            <small class="form-text text-muted">Gambar utama (resolusi ideal: 800x400px)</small>
                            @if($image->image_1)
                                <div class="mt-2">
                                    <img src="{{ asset('storage/' . $image->image_1) }}" alt="Gambar 1" class="img-thumbnail" style="max-height: 100px;">
                                    <div class="form-check mt-1">
                                        <input class="form-check-input" type="checkbox" id="delete_image_1" name="delete_image_1" value="1">
                                        <label class="form-check-label text-danger" for="delete_image_1">
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
                            @if($image->image_2)
                                <div class="mt-2">
                                    <img src="{{ asset('storage/' . $image->image_2) }}" alt="Gambar 2" class="img-thumbnail" style="max-height: 100px;">
                                    <div class="form-check mt-1">
                                        <input class="form-check-input" type="checkbox" id="delete_image_2" name="delete_image_2" value="1">
                                        <label class="form-check-label text-danger" for="delete_image_2">
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
                            @if($image->image_3)
                                <div class="mt-2">
                                    <img src="{{ asset('storage/' . $image->image_3) }}" alt="Gambar 3" class="img-thumbnail" style="max-height: 100px;">
                                    <div class="form-check mt-1">
                                        <input class="form-check-input" type="checkbox" id="delete_image_3" name="delete_image_3" value="1">
                                        <label class="form-check-label text-danger" for="delete_image_3">
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

                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="dokumen_url" class="form-label">URL Dokumen PDF (Laporan Harian)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-file-earmark-pdf"></i></span>
                                <input type="url" class="form-control @error('dokumen_url') is-invalid @enderror" id="dokumen_url" name="dokumen_url" 
                                    value="{{ old('dokumen_url', $image->dokumen_url) }}" 
                                    placeholder="https://example.com/laporan-harian.pdf">
                            </div>
                            <small class="form-text text-muted">Masukkan URL dokumen PDF laporan harian (opsional)</small>
                            @error('dokumen_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            
                            @if($image->dokumen_url)
                                <div class="mt-2">
                                    <div class="card">
                                        <div class="card-body p-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <i class="bi bi-file-earmark-pdf text-danger fs-4"></i>
                                                    <a href="{{ $image->dokumen_url }}" target="_blank" class="ms-2">Lihat Dokumen PDF</a>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="delete_dokumen" name="delete_dokumen" value="1">
                                                    <label class="form-check-label text-danger" for="delete_dokumen">
                                                        Hapus dokumen
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <a href="{{ route('documents.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        @else
            <div class="alert alert-info">
                <p>Tidak ada data gambar untuk tanggal {{ $selectedDate->format('d F Y') }}.</p>
                <a href="{{ route('documents.create') }}" class="btn btn-primary mt-2">Upload Gambar Baru</a>
            </div>
        @endif
    </div>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle dropdown date selection
        const dateSelector = document.getElementById('date-selector');
        const goToDateBtn = document.getElementById('go-to-date');
        
        goToDateBtn.addEventListener('click', function() {
            const selectedDate = dateSelector.value;
            if (selectedDate) {
                window.location.href = "{{ route('documents.edit') }}/" + selectedDate;
            }
        });
        
        // File input preview
        const fileInputs = document.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const imgContainer = this.closest('.mb-3').querySelector('.mt-2') || this.closest('.mb-3');
                    if (imgContainer) {
                        const existingPreview = imgContainer.querySelector('.preview-image');
                        if (existingPreview) {
                            existingPreview.remove();
                        }
                        
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const imgPreview = document.createElement('div');
                            imgPreview.className = 'preview-image mt-2';
                            imgPreview.innerHTML = `
                                <p class="text-muted">Preview:</p>
                                <img src="${e.target.result}" class="img-thumbnail" style="max-height: 100px;">
                            `;
                            imgContainer.appendChild(imgPreview);
                        }
                        reader.readAsDataURL(this.files[0]);
                    }
                }
            });
        });
        
        // Toggle delete checkbox and file input
        const deleteCheckboxes = document.querySelectorAll('input[type="checkbox"][name^="delete_image"]');
        deleteCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const fileInput = this.closest('.mb-3').querySelector('input[type="file"]');
                if (this.checked) {
                    fileInput.disabled = true;
                    fileInput.classList.add('bg-light');
                } else {
                    fileInput.disabled = false;
                    fileInput.classList.remove('bg-light');
                }
            });
        });
        
        // Tangani checkbox hapus dokumen
        const deleteDocumentCheckbox = document.getElementById('delete_dokumen');
        if (deleteDocumentCheckbox) {
            deleteDocumentCheckbox.addEventListener('change', function() {
                const urlInput = document.getElementById('dokumen_url');
                if (this.checked) {
                    urlInput.disabled = true;
                    urlInput.classList.add('bg-light');
                } else {
                    urlInput.disabled = false;
                    urlInput.classList.remove('bg-light');
                }
            });
        }
    });
</script>
@endsection
<!-- resources/views/isu/create.blade.php -->
@extends(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isEditor()) ? 'layouts.admin' : 'layouts.app')

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
                    <!-- Checkbox Isu Strategis -->
                    <div class="col-md-4">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" id="isu_strategis" name="isu_strategis" value="1" {{ old('isu_strategis') ? 'checked' : '' }}>
                                <label class="form-check-label" for="isu_strategis">Isu Strategis</label>
                            </div>
                            <small class="form-text text-muted">Centang jika isu ini bersifat strategis</small>
                        </div>
                    </div>

                    <!-- Kategori sebagai Tags Input -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="kategori" class="form-label">Kategori</label>
                                <input type="text" class="form-control @error('kategori') is-invalid @enderror" id="kategori" name="kategori" value="{{ old('kategori') }}" placeholder="Masukkan kategori (pisahkan dengan koma)">
                                @error('kategori')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Tambahkan beberapa kategori dengan menekan Enter atau koma</small>
                            </div>
                        </div>

                    <!-- Skala -->
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="skala" class="form-label">Skala</label>
                            <select class="form-select @error('skala') is-invalid @enderror" id="skala" name="skala" required>
                                <option value="">Pilih Skala</option>
                                @foreach($skalaList as $skala)
                                    <option value="{{ $skala->kode }}" {{ old('skala') == $skala->kode ? 'selected' : '' }}>
                                        {{ $skala->nama }}
                                    </option>
                                @endforeach
                            </select>
                            @error('skala')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Tone -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="tone" class="form-label">Tone Isu</label>
                            <div class="d-flex gap-3">
                                @foreach($toneList as $tone)
                                    <div class="form-check">
                                        <input type="radio" class="form-check-input @error('tone') is-invalid @enderror" 
                                               id="tone_{{ $tone->kode }}" name="tone" value="{{ $tone->kode }}" 
                                               {{ old('tone') == $tone->kode ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="tone_{{ $tone->kode }}">{{ $tone->nama }}</label>
                                    </div>
                                @endforeach
                            </div>
                            @error('tone')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Rangkuman dengan Summernote -->
                <div class="mb-3">
                    <label for="rangkuman" class="form-label">Rangkuman</label>
                    <textarea class="form-control summernote @error('rangkuman') is-invalid @enderror" id="rangkuman" name="rangkuman" required>{{ old('rangkuman') }}</textarea>
                    @error('rangkuman')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>


                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="narasi_positif" class="form-label">Narasi Positif</label>
                            <textarea class="form-control summernote @error('narasi_positif') is-invalid @enderror" id="narasi_positif" name="narasi_positif" rows="5" required>{{ old('narasi_positif') }}</textarea>
                            @error('narasi_positif')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="narasi_negatif" class="form-label">Narasi Negatif</label>
                            <textarea class="form-control summernote @error('narasi_negatif') is-invalid @enderror" id="narasi_negatif" name="narasi_negatif" rows="5" required>{{ old('narasi_negatif') }}</textarea>
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
                                    <input type="text" class="form-control referensi-judul" name="referensi_judul[]">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">URL</label>
                                    <div class="input-group">
                                        <input type="url" class="form-control referensi-url" name="referensi_url[]">
                                        <button type="button" class="btn btn-outline-secondary preview-btn">Preview</button>
                                    </div>
                                    <small class="form-text text-muted">Thumbnail akan otomatis diambil dari URL</small>
                                </div>
                            </div>
                        </div>
                        <div class="preview-container mt-2" style="display: none;">
                            <div class="row">
                                <div class="col-md-4">
                                    <img src="" class="img-fluid preview-img" alt="Preview">
                                </div>
                                <div class="col-md-8">
                                    <p class="preview-title"></p>
                                </div>
                            </div>
                            <input type="hidden" name="referensi_thumbnail_url[]" class="thumbnail-url">
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-sm btn-danger remove-referensi">Hapus Referensi</button>
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
<!-- Tambahkan Summernote CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.9.1/summernote-bs5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet" type="text/css" />
<style>
    .tagify {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        padding: 0.375rem 0.75rem;
    }
    .tagify--focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    .preview-container {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 10px;
        background-color: #f8f9fa;
    }
    .referensi-item {
        position: relative;
    }
    .preview-img {
        max-height: 150px;
        object-fit: contain;
    }
    .form-switch .form-check-input {
        width: 2.5em;
        height: 1.25em;
    }
    .form-check-input:checked {
        background-color: #007bff;
        border-color: #007bff;
    }
</style>
@endsection

@section('scripts')
<!-- Tambahkan Summernote JS dan jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.9.1/summernote-bs5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.min.js"></script>
<script>
$(document).ready(function() {
    // Inisialisasi Summernote
    $('.summernote').summernote({
        height: 200, // Tinggi editor
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['link']],
            ['table', ['table']],
            ['view', ['undo', 'redo']]
        ],
        placeholder: 'Tulis di sini...'
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const referensiContainer = document.getElementById('referensi-container');
    const addReferensiBtn = document.getElementById('add-referensi');

    // Fungsi untuk mengatur tombol hapus
    function updateRemoveButtons() {
        const referensiItems = referensiContainer.querySelectorAll('.referensi-item');
        referensiItems.forEach((item, index) => {
            const removeBtn = item.querySelector('.remove-referensi');
            if (referensiItems.length > 1) {
                removeBtn.style.display = 'block';
            } else {
                removeBtn.style.display = 'none';
            }
        });
    }

        // Inisialisasi Tagify
        const input = document.querySelector('#kategori');
        const tagify = new Tagify(input, {
            whitelist: @json($kategoriList->pluck('nama')->toArray()), // Saran dari database
            dropdown: {
                enabled: 1, // Tampilkan saran setelah 1 karakter
                maxItems: 10, // Maksimum saran
                classname: 'tagify__dropdown',
                position: 'all'
            },
            enforceWhitelist: false, // Izinkan tag baru
            delimiters: ',', // Gunakan koma sebagai pemisah
        });

        // Pastikan data dikirim sebagai string comma-separated
        tagify.on('change', function(e) {
            input.value = tagify.value.map(tag => tag.value).join(',');
        });

    // Tambah referensi baru
    addReferensiBtn.addEventListener('click', function() {
        const referensiItem = document.querySelector('.referensi-item').cloneNode(true);
        
        // Clear input values
        const inputs = referensiItem.querySelectorAll('input');
        inputs.forEach(input => {
            input.value = '';
        });
        
        // Reset preview container
        const previewContainer = referensiItem.querySelector('.preview-container');
        if (previewContainer) {
            previewContainer.style.display = 'none';
            const previewImg = previewContainer.querySelector('.preview-img');
            if (previewImg) {
                previewImg.src = '';
            }
            const previewTitle = previewContainer.querySelector('.preview-title');
            if (previewTitle) {
                previewTitle.textContent = '';
            }
        }
        
        // Tambahkan event listener untuk URL input
        const urlInput = referensiItem.querySelector('.referensi-url');
        if (urlInput) {
            urlInput.addEventListener('change', function() {
                const previewBtn = referensiItem.querySelector('.preview-btn');
                if (previewBtn) {
                    previewBtn.click(); // Auto preview when URL is entered
                }
            });
        }
        
        // Tambahkan event listener untuk tombol preview
        const previewBtn = referensiItem.querySelector('.preview-btn');
        if (previewBtn) {
            previewBtn.addEventListener('click', handlePreview);
        }
        
        // Tambahkan event listener untuk tombol hapus
        const removeBtn = referensiItem.querySelector('.remove-referensi');
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                referensiContainer.removeChild(referensiItem);
                updateRemoveButtons();
            });
        }

        referensiContainer.appendChild(referensiItem);
        updateRemoveButtons();
    });

    // Fungsi untuk menangani preview
    function handlePreview(event) {
        const referensiItem = event.target.closest('.referensi-item');
        const urlInput = referensiItem.querySelector('.referensi-url');
        const url = urlInput.value.trim();
        
        const previewContainer = referensiItem.querySelector('.preview-container');
        const previewImg = previewContainer.querySelector('.preview-img');
        const previewTitle = previewContainer.querySelector('.preview-title');
        const thumbnailUrlInput = referensiItem.querySelector('.thumbnail-url');
        const judulInput = referensiItem.querySelector('.referensi-judul');
        
        // Tampilkan loading state
        event.target.textContent = 'Loading...';
        event.target.disabled = true;
        
        if (!url) {
            event.target.textContent = 'Preview';
            event.target.disabled = false;
            alert('Masukkan URL terlebih dahulu.');
            return;
        }
        
        // Lakukan request ke endpoint preview
        fetch(`/preview?url=${encodeURIComponent(url)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Reset tombol
                event.target.textContent = 'Preview';
                event.target.disabled = false;
                
                if (data.success && data.image) {
                    // Set gambar preview
                    previewImg.src = data.image;
                    
                    // Set judul jika ada
                    if (data.title) {
                        previewTitle.textContent = data.title;
                        // Auto-fill judul referensi jika kosong
                        if (!judulInput.value) {
                            judulInput.value = data.title;
                        }
                    } else {
                        previewTitle.textContent = '';
                    }
                    
                    // Simpan URL thumbnail
                    thumbnailUrlInput.value = data.image;
                    
                    // Tampilkan container preview
                    previewContainer.style.display = 'block';
                } else {
                    alert('Tidak dapat menemukan gambar dari URL tersebut.');
                    previewContainer.style.display = 'none';
                    thumbnailUrlInput.value = '';
                    previewTitle.textContent = '';
                }
            })
            .catch(error => {
                console.error('Error fetching preview:', error);
                
                // Reset tombol
                event.target.textContent = 'Preview';
                event.target.disabled = false;
                
                alert('Terjadi kesalahan saat memuat preview.');
                previewContainer.style.display = 'none';
                thumbnailUrlInput.value = '';
                previewTitle.textContent = '';
            });
    }

    // Auto-preview saat URL diubah
    const urlInputs = document.querySelectorAll('.referensi-url');
    urlInputs.forEach(input => {
        input.addEventListener('change', function() {
            const referensiItem = this.closest('.referensi-item');
            const previewBtn = referensiItem.querySelector('.preview-btn');
            if (previewBtn && this.value.trim()) {
                previewBtn.click();
            }
        });
    });

    // Tambahkan event listener untuk tombol preview yang sudah ada
    const previewButtons = document.querySelectorAll('.preview-btn');
    previewButtons.forEach(button => {
        button.addEventListener('click', handlePreview);
    });

    // Tambahkan event listener untuk tombol hapus yang sudah ada
    const removeButtons = document.querySelectorAll('.remove-referensi');
    removeButtons.forEach(button => {
        button.addEventListener('click', function() {
            referensiContainer.removeChild(button.closest('.referensi-item'));
            updateRemoveButtons();
        });
    });

    // Inisialisasi tombol hapus
    updateRemoveButtons();
});
</script>
@endsection
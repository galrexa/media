<!-- resources/views/isu/edit.blade.php dengan CKEditor -->
@extends(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isEditor()) ? 'layouts.admin' : 'layouts.app')

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
                            <input type="date" class="form-control @error('tanggal') is-invalid @enderror" id="tanggal" name="tanggal" value="{{ old('tanggal', $isu->tanggal->format('Y-m-d')) }}">
                            @error('tanggal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="isu_strategis" name="isu_strategis" value="1" {{ old('isu_strategis', $isu->isu_strategis) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isu_strategis">Isu Strategis</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="kategori" class="form-label">Kategori</label>
                            <input type="text" class="form-control @error('kategori') is-invalid @enderror" id="kategori" name="kategori" value="{{ old('kategori', $isu->kategoris->pluck('nama')->implode(',')) }}" placeholder="Tambah kategori">
                            @error('kategori')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Ketik dan tekan Enter atau koma untuk menambah tag</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="skala" class="form-label">Skala</label>
                            <select class="form-select @error('skala') is-invalid @enderror" id="skala" name="skala">
                                <option value="">Pilih Skala</option>
                                @foreach($skalaList as $skala)
                                    <option value="{{ $skala->id }}" {{ old('skala', $isu->skala) == $skala->id ? 'selected' : '' }}>
                                        {{ $skala->nama }}
                                    </option>
                                @endforeach
                            </select>
                            @error('skala')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="tone" class="form-label">Tone Isu</label>
                            <select class="form-select @error('tone') is-invalid @enderror" id="tone" name="tone">
                                <option value="">Pilih Tone</option>
                                @foreach($toneList as $tone)
                                    <option value="{{ $tone->id }}" {{ old('tone', $isu->tone) == $tone->id ? 'selected' : '' }}>
                                        {{ $tone->nama }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="rangkuman" class="form-label">Rangkuman</label>
                    <textarea class="form-control ckeditor @error('rangkuman') is-invalid @enderror" id="rangkuman" name="rangkuman" rows="5" >{{ old('rangkuman', $isu->rangkuman) }}</textarea>
                    @error('rangkuman')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="narasi_positif" class="form-label">Narasi Positif</label>
                            <textarea class="form-control ckeditor @error('narasi_positif') is-invalid @enderror" id="narasi_positif" name="narasi_positif" rows="5" >{{ old('narasi_positif', $isu->narasi_positif) }}</textarea>
                            @error('narasi_positif')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="narasi_negatif" class="form-label">Narasi Negatif</label>
                            <textarea class="form-control ckeditor @error('narasi_negatif') is-invalid @enderror" id="narasi_negatif" name="narasi_negatif" rows="5" >{{ old('narasi_negatif', $isu->narasi_negatif) }}</textarea>
                            @error('narasi_negatif')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <h5 class="mt-4 mb-3">Referensi</h5>
                <div id="referensi-container">
                    @foreach ($isu->referensi ?? [] as $index => $ref)
                        <div class="referensi-item border p-3 mb-3">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">URL</label>
                                        <div class="input-group">
                                            <input type="url" class="form-control referensi-url" name="referensi_url[]" value="{{ old('referensi_url.' . $index, $ref->url) }}">
                                            <button type="button" class="btn btn-outline-secondary preview-btn">Preview</button>
                                        </div>
                                        <small class="form-text text-muted">Thumbnail dan judul akan otomatis diambil dari URL</small>
                                        <!-- Judul sebagai hidden input -->
                                        <input type="hidden" class="referensi-judul" name="referensi_judul[]" value="{{ old('referensi_judul.' . $index, $ref->judul) }}">
                                        <input type="hidden" name="referensi_id[]" value="{{ $ref->id }}">
                                    </div>
                                </div>
                            </div>
                            <div class="preview-container mt-2" style="{{ $ref->thumbnail ? 'display: block;' : 'display: none;' }}">
                                <div class="row">
                                    <div class="col-md-4">
                                        <img src="{{ $ref->thumbnail ?? '' }}" class="img-fluid preview-img" alt="Preview" style="{{ $ref->thumbnail ? '' : 'display: none;' }}">
                                    </div>
                                    <div class="col-md-8">
                                        <p class="preview-title">{{ $ref->judul ?? '' }}</p>
                                    </div>
                                </div>
                                <input type="hidden" name="referensi_thumbnail_url[]" class="thumbnail-url" value="{{ old('referensi_thumbnail_url.' . $index, $ref->thumbnail) }}">
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-sm btn-danger remove-referensi">Hapus Referensi</button>
                            </div>
                        </div>
                    @endforeach
                    
                    <!-- Item referensi kosong untuk tambahan baru -->
                    <div class="referensi-item border p-3 mb-3" style="display: none;" id="referensi-template">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">URL</label>
                                    <div class="input-group">
                                        <input type="url" class="form-control referensi-url" name="referensi_url[]">
                                        <button type="button" class="btn btn-outline-secondary preview-btn">Preview</button>
                                    </div>
                                    <small class="form-text text-muted">Thumbnail dan judul akan otomatis diambil dari URL</small>
                                    <!-- Judul sebagai hidden input -->
                                    <input type="hidden" class="referensi-judul" name="referensi_judul[]">
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
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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

    .card-body ul, .card-body ol {
    margin-left: 20px;
    }
    
    /* CKEditor styling */
    .ck-editor__editable {
        min-height: 200px;
    }
    .ck-content {
        font-size: 14px;
    }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- CKEditor 5 -->
<script src="https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js"></script>
<script>
$(document).ready(function() {
    // Inisialisasi CKEditor untuk setiap elemen dengan class ckeditor
    document.querySelectorAll('.ckeditor').forEach(function(element) {
        // Untuk halaman edit
        ClassicEditor
            .create(element, {
                toolbar: [
                    'heading', '|', 
                    'bold', 'italic', 'link', '|',
                    'bulletedList', 'numberedList', '|',
                    'undo', 'redo'
                ],
                placeholder: 'Tulis di sini...',
                link: {
                    defaultProtocol: 'https://',
                    addTargetToExternalLinks: true
                }
            })
            .then(editor => {
                // Pastikan konten HTML di-render dengan benar
                // Jika data sudah ada, pastikan HTML tidak dianggap sebagai string biasa
                editor.setData(element.value);
                
                // Tambahkan event listener untuk sinkronisasi konten
                editor.model.document.on('change:data', () => {
                    element.value = editor.getData();
                });
            })
            .catch(error => {
                console.error('CKEditor error:', error);
            });
        });

    const input = document.querySelector('#kategori');
    const tagify = new Tagify(input, {
        whitelist: @json($kategoriList->pluck('nama')->toArray()),
        dropdown: { 
            enabled: 1, 
            maxItems: 10, 
            classname: 'tagify__dropdown', 
            position: 'all' 
        },
        enforceWhitelist: false,
        delimiters: ',',
    });

    const initialValue = input.value;
    if (initialValue) {
        tagify.loadOriginalValues(initialValue);
    }

    tagify.on('change', function(e) {
        input.value = tagify.value.map(tag => tag.value).join(',');
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

    // Fungsi untuk mengekstrak domain dari URL sebagai judul
    function extractDomainForTitle(url) {
        // Menambahkan protokol jika belum ada
        if (!url.startsWith('http://') && !url.startsWith('https://')) {
            url = 'https://' + url;
        }
        
        try {
            // Membuat objek URL untuk mengekstrak hostname
            const urlObj = new URL(url);
            // Mengambil hostname (domain) dari URL
            let domain = urlObj.hostname;
            
            // Menghapus 'www.' jika ada
            domain = domain.replace(/^www\./, '');
            
            return domain;
        } catch (e) {
            // Jika URL tidak valid, kembalikan string kosong
            console.error('URL tidak valid:', e);
            return '';
        }
    }

    // Fungsi untuk update judul berdasarkan URL
    function updateJudulFromUrl(referensiItem) {
        const urlInput = referensiItem.querySelector('.referensi-url');
        const judulInput = referensiItem.querySelector('.referensi-judul');
        
        if (urlInput && urlInput.value && judulInput) {
            const url = urlInput.value.trim();
            if (url) {
                // Mengekstrak domain dan menggunakannya sebagai judul
                const domain = extractDomainForTitle(url);
                if (domain && !judulInput.value) {
                    judulInput.value = domain;
                }
            }
        }
    }

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
            // Event saat input diketik untuk mengupdate judul secara realtime
            urlInput.addEventListener('input', function() {
                updateJudulFromUrl(referensiItem);
            });
            
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
        
        // Auto-fill judul dari domain jika masih kosong
        if (!judulInput.value && url) {
            judulInput.value = extractDomainForTitle(url);
        }

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
                    previewImg.style.display = 'block';
                    
                    // Set judul jika ada
                    if (data.title) {
                        previewTitle.textContent = data.title;
                        // Jika judul saat ini hanya domain atau kosong, ganti dengan judul dari metadata
                        const currentJudul = judulInput.value;
                        if (!currentJudul || currentJudul === extractDomainForTitle(url)) {
                            judulInput.value = data.title;
                        }
                    } else {
                        // Gunakan domain sebagai judul jika tidak ada title dari metadata
                        const domain = extractDomainForTitle(url);
                        previewTitle.textContent = domain;
                    }
                    
                    // Simpan URL thumbnail
                    thumbnailUrlInput.value = data.image;
                    
                    // Tampilkan container preview
                    previewContainer.style.display = 'block';
                } else {
                    // Tetap tampilkan preview dengan domain sebagai judul
                    const domain = extractDomainForTitle(url);
                    previewTitle.textContent = domain;
                    judulInput.value = domain;
                    
                    // Sembunyikan gambar karena tidak ada
                    previewImg.style.display = 'none';
                    thumbnailUrlInput.value = '';
                    
                    // Tampilkan container preview
                    previewContainer.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error fetching preview:', error);
                
                // Reset tombol
                event.target.textContent = 'Preview';
                event.target.disabled = false;
                
                // Gunakan domain sebagai fallback
                const domain = extractDomainForTitle(url);
                previewTitle.textContent = domain;
                judulInput.value = domain;
                
                // Tampilkan container preview tanpa gambar
                previewContainer.style.display = 'block';
                previewImg.style.display = 'none';
                thumbnailUrlInput.value = '';
            });
    }

    // Tambahkan event listener untuk input URL yang sudah ada
    const urlInputs = document.querySelectorAll('.referensi-url');
    urlInputs.forEach(input => {
        // Event untuk update judul saat input diketik
        input.addEventListener('input', function() {
            const referensiItem = this.closest('.referensi-item');
            updateJudulFromUrl(referensiItem);
        });
        
        // Event untuk auto-preview
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

    // Inisialisasi judul dari URL untuk semua referensi yang sudah ada
    urlInputs.forEach(input => {
        if (input.value.trim()) {
            const referensiItem = input.closest('.referensi-item');
            updateJudulFromUrl(referensiItem);
        }
    });

    // Inisialisasi tombol hapus
    updateRemoveButtons();
});
</script>
@endsection
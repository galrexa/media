<!-- resources/views/isu/edit.blade.php dengan CKEditor -->
@extends(
    auth()->check() &&
    (
        auth()->user()->isAdmin() ||
        auth()->user()->isEditor() ||
        auth()->user()->isVerifikator1() ||
        auth()->user()->isVerifikator2()
    )
    ? 'layouts.admin'
    : 'layouts.app'
)

@section('title', 'Edit Isu')

@section('content')
<div class="container">
    <!-- Breadcrumb -->
    <div class="breadcrumb-wrapper mb-3">
        <div class="breadcrumb-container bg-white shadow-sm rounded p-2 d-inline-block">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb d-flex align-items-center m-0 flex-wrap">
                    <li class="breadcrumb-item">
                        <a href="{{ route('home') }}" class="text-decoration-none d-flex align-items-center">
                            <i class="fas fa-home me-1"></i>
                            <span>Beranda</span>
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('isu.index') }}" class="text-decoration-none">
                            <span>Isu</span>
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <span class="fw-medium">Tambah Isu Baru</span>
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Card Utama -->
    <div class="card shadow-sm">
        <div class="card-header bg-warning text-dark">
            <h4 class="mb-0">Edit Isu</h4>
        </div>
        <div class="card-body p-4">
            <!-- Tampilkan alert jika isu ditolak -->
            @if($isu->status && $isu->status->nama == 'Ditolak' && $isu->alasan_penolakan)
            <div class="alert alert-danger mb-4">
                <h5 class="alert-heading"><i class="fas fa-times-circle me-2"></i>Alasan Penolakan:</h5>
                <p class="mb-0">{{ $isu->alasan_penolakan }}</p>
            </div>
            @endif
            <form action="{{ route('isu.update', $isu) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- Bagian Judul dan Tanggal -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="judul" class="form-label fw-bold">Judul Isu <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('judul') is-invalid @enderror" id="judul" name="judul" value="{{ old('judul', $isu->judul) }}" required>
                            @error('judul')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="tanggal" class="form-label fw-bold">Tanggal</label>
                            <input type="date" class="form-control @error('tanggal') is-invalid @enderror" id="tanggal" name="tanggal" value="{{ old('tanggal', $isu->tanggal->format('Y-m-d')) }}">
                            @error('tanggal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Bagian Atribut Isu -->
                <div class="row mb-4">
                    <!-- Checkbox Isu Strategis -->
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="isu_strategis" class="form-label fw-bold d-block">Isu Strategis</label>
                            <div class="form-check form-switch mt-2">
                                <input type="checkbox" class="form-check-input" id="isu_strategis" name="isu_strategis" value="1" {{ old('isu_strategis', $isu->isu_strategis) ? 'checked' : '' }}>
                                <!-- <label class="form-check-label" for="isu_strategis">Isu Strategis</label> -->
                            </div>
                        </div>
                    </div>

                    <!-- Kategori sebagai Tags Input -->
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="kategori" class="form-label fw-bold">Kategori</label>
                            <input type="text" class="form-control @error('kategori') is-invalid @enderror" id="kategori" name="kategori" value="{{ old('kategori', $isu->kategoris->pluck('nama')->implode(',')) }}" placeholder="Pisahkan dengan koma">
                            @error('kategori')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Contoh: Politik, Ekonomi, Sosial</small>
                        </div>
                    </div>

                    <!-- Skala -->
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="skala" class="form-label fw-bold">Skala</label>
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

                    <!-- Tone -->
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="tone" class="form-label fw-bold">Tone Isu</label>
                            <div class="tone-segment-rounded">
                                @foreach($toneList as $tone)
                                <input type="radio" class="tone-radio" id="tone_{{ $tone->id }}" name="tone" value="{{ $tone->id }}"
                                    {{ old('tone', $isu->tone) == $tone->id ? 'checked' : '' }} data-color="{{ $tone->warna }}">
                                <label for="tone_{{ $tone->id }}" {{ old('tone') == $tone->id ? 'style="background-color: '.$tone->warna.'; color: white;"' : '' }}>
                                    {{ $tone->nama }}
                                </label>
                                @endforeach
                            </div>
                            @error('tone')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Editor untuk Rangkuman -->
                <div class="mb-4">
                    <label for="rangkuman" class="form-label fw-bold">Rangkuman</label>
                    <textarea class="form-control ckeditor @error('rangkuman') is-invalid @enderror" id="rangkuman" name="rangkuman" rows="5">{{ old('rangkuman', $isu->rangkuman) }}</textarea>
                    <small class="form-text text-muted">Masukkan ringkasan singkat mengenai isu yang diangkat</small>
                    @error('rangkuman')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Editor untuk Narasi -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="narasi_positif" class="form-label fw-bold">Narasi Positif</label>
                            <textarea class="form-control ckeditor @error('narasi_positif') is-invalid @enderror" id="narasi_positif" name="narasi_positif" rows="5">{{ old('narasi_positif', $isu->narasi_positif) }}</textarea>
                            @error('narasi_positif')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="narasi_negatif" class="form-label fw-bold">Narasi Negatif</label>
                            <textarea class="form-control ckeditor @error('narasi_negatif') is-invalid @enderror" id="narasi_negatif" name="narasi_negatif" rows="5">{{ old('narasi_negatif', $isu->narasi_negatif) }}</textarea>
                            @error('narasi_negatif')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Bagian Referensi -->
                <div class="card bg-light mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0 fw-bold">Referensi</h5>
                    </div>
                    <div class="card-body">
                        <div id="referensi-container">
                            @foreach ($isu->referensi ?? [] as $index => $ref)
                                <div class="referensi-item border rounded p-3 mb-3 bg-white">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">URL</label>
                                                <div class="input-group">
                                                    <input type="url" class="form-control referensi-url" name="referensi_url[]" value="{{ old('referensi_url.' . $index, $ref->url) }}">
                                                    <button type="button" class="btn btn-outline-primary preview-btn">
                                                        <i class="fa fa-eye"></i> Preview
                                                    </button>
                                                </div>
                                                <small class="form-text text-muted">Thumbnail dan judul akan otomatis diambil dari URL</small>
                                                <!-- Judul sebagai hidden input -->
                                                <input type="hidden" class="referensi-judul" name="referensi_judul[]" value="{{ old('referensi_judul.' . $index, $ref->judul) }}">
                                                <input type="hidden" name="referensi_id[]" value="{{ $ref->id }}">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="preview-container mt-2" style="{{ $ref->thumbnail ? 'display: block;' : 'display: none;' }}">
                                        <div class="card border">
                                            <div class="card-body p-2">
                                                <div class="row align-items-center">
                                                    <div class="col-md-4">
                                                        <img src="{{ $ref->thumbnail ?? '' }}" class="img-fluid preview-img" alt="Preview" style="{{ $ref->thumbnail ? '' : 'display: none;' }}">
                                                    </div>
                                                    <div class="col-md-8">
                                                        <p class="preview-title m-0 fw-bold">{{ $ref->judul ?? '' }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="referensi_thumbnail_url[]" class="thumbnail-url" value="{{ old('referensi_thumbnail_url.' . $index, $ref->thumbnail) }}">
                                    </div>

                                    <div class="text-end mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-referensi">
                                            <i class="fas fa-trash"></i> Hapus Referensi
                                        </button>
                                    </div>
                                </div>
                            @endforeach

                            <!-- Item referensi kosong untuk tambahan baru -->
                            <div class="referensi-item border rounded p-3 mb-3 bg-white" style="display: none;" id="referensi-template">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">URL</label>
                                            <div class="input-group">
                                                <input type="url" class="form-control referensi-url" name="referensi_url[]">
                                                <button type="button" class="btn btn-outline-primary preview-btn">
                                                    <i class="fa fa-eye"></i> Preview
                                                </button>
                                            </div>
                                            <small class="form-text text-muted">Thumbnail dan judul akan otomatis diambil dari URL</small>
                                            <!-- Judul sebagai hidden input -->
                                            <input type="hidden" class="referensi-judul" name="referensi_judul[]">
                                        </div>
                                    </div>
                                </div>

                                <div class="preview-container mt-2" style="display: none;">
                                    <div class="card border">
                                        <div class="card-body p-2">
                                            <div class="row align-items-center">
                                                <div class="col-md-4">
                                                    <img src="" class="img-fluid preview-img" alt="Preview">
                                                </div>
                                                <div class="col-md-8">
                                                    <p class="preview-title m-0 fw-bold"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="referensi_thumbnail_url[]" class="thumbnail-url">
                                </div>

                                <div class="text-end mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-referensi">
                                        <i class="fas fa-trash"></i> Hapus Referensi
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <button type="button" id="add-referensi" class="btn btn-outline-primary">
                                <i class="bi bi-plus-circle"></i> Tambah Referensi
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi -->
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('isu.index') }}" class="btn btn-light border">
                        <i class="bi bi-x-circle"></i> Batal
                    </a>
                        <!-- Tombol yang berbeda berdasarkan peran pengguna -->
                    <!-- Tombol untuk Editor -->
                    @if(auth()->user()->isEditor())
                        <button type="submit" name="action" value="simpan" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Simpan Draft
                        </button>
                        <button type="submit" name="action" value="kirim" class="btn btn-success">
                            <i class="fas fa-paper-plane me-1"></i> Kirim ke Verifikator 1
                        </button>

                    <!-- Tombol untuk Verifikator 1 -->
                    @elseif(auth()->user()->isVerifikator1())
                        <button type="submit" name="action" value="simpan" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Simpan Perubahan
                        </button>
                        <button type="submit" name="action" value="teruskan" class="btn btn-success">
                            <i class="fas fa-paper-plane me-1"></i> Teruskan ke Verifikator 2
                        </button>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#tolakModal">
                            <i class="fas fa-times-circle me-1"></i> Tolak
                        </button>

                    <!-- Tombol untuk Verifikator 2 -->
                    @elseif(auth()->user()->isVerifikator2())
                        <button type="submit" name="action" value="simpan" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Simpan Perubahan
                        </button>
                        <button type="submit" name="action" value="submit" class="btn btn-success">
                            <i class="fas fa-check-circle me-1"></i> Publikasikan
                        </button>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#tolakModal">
                            <i class="fas fa-times-circle me-1"></i> Tolak
                        </button>

                    <!-- Tombol untuk Admin (dapat melakukan semua aksi) -->
                    @elseif(auth()->user()->isAdmin())
                        <button type="submit" name="action" value="simpan" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Simpan
                        </button>
                        <button type="submit" name="action" value="kirim" class="btn btn-info">
                            <i class="fas fa-paper-plane me-1"></i> Kirim ke Verifikator 1
                        </button>
                        <button type="submit" name="action" value="teruskan" class="btn btn-secondary">
                            <i class="fas fa-arrow-right me-1"></i> Teruskan ke Verifikator 2
                        </button>
                        <button type="submit" name="action" value="submit" class="btn btn-success">
                            <i class="fas fa-check-circle me-1"></i> Publikasikan
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
    <!-- Perbaikan Modal Penolakan di resources/views/isu/edit.blade.php -->
    <div class="modal fade" id="tolakModal" tabindex="-1" aria-labelledby="tolakModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="tolakModalLabel">Tolak Isu</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('isu.penolakan', $isu) }}" method="POST" id="formPenolakan">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="alasan_penolakan" class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('alasan_penolakan') is-invalid @enderror" id="alasan_penolakan" name="alasan_penolakan" rows="4" required></textarea>
                            <small class="text-muted">Berikan alasan penolakan yang jelas agar dapat diperbaiki.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger" id="btnSubmitPenolakan">Tolak Isu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="{{ asset('vendor/ckeditor5/ckeditor5.css') }}">
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
    /* Custom Badge */
    .badge-custom {
        display: inline-flex;
        align-items: center;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.35rem 0.65rem;
        border-radius: 5px;
        color: white;
    }

    /* Tone Segment dengan Rounded Corners - Style 1 */
    .tone-segment-rounded {
        display: flex;
        width: 100%;
        max-width: 500px;
        position: relative;
        background-color: #f0f0f0;
        padding: 2px;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .tone-segment-rounded input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
    }

    .tone-segment-rounded label {
        flex: 1;
        text-align: center;
        padding: 0.6rem 0;
        cursor: pointer;
        transition: all 0.3s ease;
        border-radius: 5px;
        margin: 0 2px;
        font-size: 0.9rem;
        font-weight: 600;
        color: #495057;
        background-color: transparent;
        position: relative;
        z-index: 1;
    }

    .tone-segment-rounded input:checked + label {
        color: white;
        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    }

    /* Hover effect */
    .tone-segment-rounded label:hover {
        background-color: rgba(0,0,0,0.03);
    }

    /* Active/pressed effect */
    .tone-segment-rounded label:active {
        transform: scale(0.98);
    }

    /* Responsive adjustments */
    @media (max-width: 576px) {
        .tone-segment-rounded {
            max-width: 100%;
        }

        .tone-segment-rounded label {
            padding: 0.5rem 0;
            font-size: 0.8rem;
        }
    }

</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- CKEditor 5 -->
<script type="importmap">
{
    "imports": {
        "ckeditor5": "{{ asset('vendor/ckeditor5/ckeditor5.js') }}",
        "ckeditor5/": "{{ asset('vendor/ckeditor5/') }}"
    }
}
</script>
<script type="module">
    // Import harus ada di level teratas modul
    import {
        ClassicEditor,
        Essentials,
        Paragraph,
        Bold,
        Italic,
        Link,
        Heading,
        List,
        Alignment,
        Underline
    } from 'ckeditor5';

    // Tunggu DOM selesai dimuat
    document.addEventListener('DOMContentLoaded', function() {
        // Inisialisasi CKEditor untuk setiap elemen dengan class ckeditor
        document.querySelectorAll('.ckeditor').forEach(function(element) {
            ClassicEditor
                .create(element, {
                    licenseKey: 'GPL', // Ganti dengan license key Anda atau 'GPL'
                    plugins: [
                        Essentials,
                        Paragraph,
                        Bold,
                        Italic,
                        Link,
                        Heading,
                        List,
                        Alignment,
                        Underline
                    ],
                    toolbar: {
                        items: [
                            'heading', '|',
                            'bold', 'italic', 'underline', '|',
                            'link', '|',
                            'alignment', '|',
                            'bulletedList', 'numberedList', '|',
                            'undo', 'redo'
                        ],
                        shouldNotGroupWhenFull: true
                    },
                    alignment: {
                        options: ['left', 'center', 'right', 'justify']
                    },
                    placeholder: 'Tulis di sini...',
                    link: {
                        defaultProtocol: 'https://',
                        addTargetToExternalLinks: true
                    }
                })
                .then(editor => {
                    // Sinkronisasi konten dengan textarea asli
                    editor.model.document.on('change:data', () => {
                        element.value = editor.getData();
                    });

                    // Simpan referensi editor jika diperlukan kemudian
                    window.editors = window.editors || {};
                    window.editors[element.id] = editor;
                })
                .catch(error => {
                    console.error('CKEditor error:', error);
                });
        });
    });
</script>
<script>
$(document).ready(function() {
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
    // Script untuk validasi form penolakan
    document.addEventListener('DOMContentLoaded', function() {
        const formPenolakan = document.getElementById('formPenolakan');
        const btnSubmitPenolakan = document.getElementById('btnSubmitPenolakan');

        if (formPenolakan) {
            formPenolakan.addEventListener('submit', function(e) {
                const alasanPenolakan = document.getElementById('alasan_penolakan').value.trim();

                if (alasanPenolakan.length < 10) {
                    e.preventDefault();
                    alert('Alasan penolakan minimal 10 karakter');
                    return false;
                }

                // Disable tombol submit untuk mencegah double submit
                btnSubmitPenolakan.disabled = true;
                btnSubmitPenolakan.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...';
            });
        }

        // Pastikan modal tolak tertutup setelah validasi error
        const tolakModal = document.getElementById('tolakModal');
        if (tolakModal) {
            const modalInstance = new bootstrap.Modal(tolakModal);

            // Cek jika ada error pada form penolakan
            @if($errors->has('alasan_penolakan'))
                modalInstance.show();
            @endif
        }
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const referensiContainer = document.getElementById('referensi-container');
    const addReferensiBtn = document.getElementById('add-referensi');

    // Fungsi untuk mengatur warna segmen yang dipilih
    function updateSegmentColor() {
        const toneRadios = document.querySelectorAll('.tone-radio');

        toneRadios.forEach(radio => {
            const label = radio.nextElementSibling;
            if (radio.checked) {
                // Ambil warna dari atribut data-color
                const color = radio.getAttribute('data-color');
                console.log('Radio checked:', radio.id, 'Color:', color); // Debugging

                // Pastikan warna tersedia
                if (color) {
                    label.style.backgroundColor = color;
                    label.style.color = 'white';
                }
            } else {
                label.style.backgroundColor = 'transparent';
                label.style.color = '#495057';
            }
        });
    }

    // Inisialisasi warna segmen saat halaman dimuat
    updateSegmentColor();

    // Perbarui warna segmen saat pengguna memilih tone
    const toneRadios = document.querySelectorAll('.tone-radio');
    toneRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            console.log('Radio changed:', this.id); // Debugging
            updateSegmentColor();
        });
    });

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
        // Cek apakah ada template referensi
        const referensiTemplate = document.getElementById('referensi-template');
        let referensiItem;

        if (referensiTemplate) {
            referensiItem = referensiTemplate.cloneNode(true);
            referensiItem.id = '';
            referensiItem.style.display = 'block';
        } else {
            referensiItem = document.querySelector('.referensi-item').cloneNode(true);

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
        const originalBtnText = event.target.innerHTML;
        event.target.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';
        event.target.disabled = true;

        if (!url) {
            event.target.innerHTML = originalBtnText;
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
                event.target.innerHTML = originalBtnText;
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
                event.target.innerHTML = originalBtnText;
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

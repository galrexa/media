<!-- resources/views/trending/create.blade.php -->
@extends(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isEditor()) ? 'layouts.admin' : 'layouts.app')

@section('title', 'Tambah Trending Baru')

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trending.index') }}">Trending</a></li>
                    <li class="breadcrumb-item active">Tambah Trending Baru</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Tambah Trending Baru</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('trending.store') }}" method="POST">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="media_sosial_id" class="form-label">Media Sosial</label>
                            <select class="form-select @error('media_sosial_id') is-invalid @enderror" id="media_sosial_id" name="media_sosial_id" required>
                                <option value="">Pilih Media Sosial</option>
                                @foreach($mediaSosials as $media)
                                    <option value="{{ $media->id }}" {{ old('media_sosial_id') == $media->id ? 'selected' : '' }}>
                                        {{ $media->nama }}
                                    </option>
                                @endforeach
                            </select>
                            @error('media_sosial_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tanggal" class="form-label">Tanggal</label>
                            <input type="datetime-local" class="form-control @error('tanggal') is-invalid @enderror" id="tanggal" name="tanggal" value="{{ old('tanggal', now()->format('Y-m-d\TH:i')) }}" required>
                            @error('tanggal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="url" class="form-label">URL</label>
                    <input type="url" class="form-control @error('url') is-invalid @enderror" id="url" name="url" value="{{ old('url') }}" required>
                    @error('url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="judul" class="form-label">Judul Trending</label>
                    <input type="text" class="form-control @error('judul') is-invalid @enderror" id="judul" name="judul" value="{{ old('judul') }}" required>
                    @error('judul')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Preview Container -->
                <div class="preview-container" id="preview-container" style="display: none;">
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 preview-image">
                                    <img id="preview-image" src="" alt="Thumbnail" class="img-fluid" style="max-height: 150px;" onerror="this.style.display='none'">
                                </div>
                                <div class="col-md-9">
                                    <h5 id="preview-title" class="site-title"></h5>
                                    <div id="preview-url" class="site-url"></div>
                                    <div id="preview-description" class="site-description"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <a href="{{ route('trending.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
    .preview-container {
        background-color: #f8f9fa;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .site-title {
        margin-top: 0;
        color: #1a0dab;
        font-size: 18px;
    }
    .site-url {
        color: #006621;
        font-size: 14px;
        word-break: break-all;
    }
    .site-description {
        color: #545454;
        font-size: 14px;
        line-height: 1.4;
    }
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlInput = document.getElementById('url');
    const previewContainer = document.getElementById('preview-container');
    const previewImage = document.getElementById('preview-image');
    const previewTitle = document.getElementById('preview-title');
    const previewUrl = document.getElementById('preview-url');
    const previewDescription = document.getElementById('preview-description');

    urlInput.addEventListener('input', debounce(function() {
        const url = urlInput.value.trim();
        if (url) {
            fetch('/trending/preview?url=' + encodeURIComponent(url))
                .then(response => response.json())
                .then(data => {
                    if (data.title || data.image) {
                        previewContainer.style.display = 'block';
                        previewImage.src = data.image || '';
                        previewTitle.textContent = data.title || '';
                        previewUrl.textContent = data.url || '';
                        previewDescription.textContent = data.description || '';
                        
                        // Auto-fill title if empty
                        if (!document.getElementById('judul').value && data.title) {
                            document.getElementById('judul').value = data.title;
                        }
                    } else {
                        previewContainer.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    previewContainer.style.display = 'none';
                });
        } else {
            previewContainer.style.display = 'none';
        }
    }, 500));
});

// Debounce function to limit API calls
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
</script>
@endsection
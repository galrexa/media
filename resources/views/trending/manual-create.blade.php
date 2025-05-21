<!-- resources/views/trending/manual-create.blade.php -->
@extends(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isEditor()) ? 'layouts.admin' : 'layouts.app')

@section('title', 'Tambah Trending Manual')

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trending.index') }}">Daftar Trending</a></li>
                    <li class="breadcrumb-item active">Tambah Trending Manual</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Tambah Trending Manual</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('trending.manual.store') }}" method="POST">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="media_sosial_id" class="form-label">Platform</label>
                                    <select class="form-select @error('media_sosial_id') is-invalid @enderror" id="media_sosial_id" name="media_sosial_id" required>
                                        <option value="">Pilih Platform</option>
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
                            <label for="judul" class="form-label">Judul Trending</label>
                            <input type="text" class="form-control @error('judul') is-invalid @enderror" id="judul" name="judul" value="{{ old('judul') }}" required>
                            <div class="form-text">Masukkan judul trending atau topik yang sedang trending.</div>
                            @error('judul')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Hidden field for keyword - akan diisi otomatis dari judul -->
                        <input type="hidden" id="keyword" name="keyword" value="{{ old('keyword') }}">
                        
                        <!-- Hidden field untuk is_selected yang selalu bernilai 1 -->
                        <input type="hidden" id="is_selected" name="is_selected" value="1">

                        <div class="mb-3">
                            <label for="generated_url" class="form-label">URL yang Akan Dibuat</label>
                            <div class="input-group">
                                <span class="input-group-text" id="url-prefix">https://</span>
                                <input type="text" class="form-control" id="generated_url" readonly disabled>
                            </div>
                            <div class="form-text">URL akan dibuat otomatis berdasarkan platform dan judul trending.</div>
                        </div>

                        <input type="hidden" id="url" name="url" value="{{ old('url') }}">

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('trending.index') }}" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
    .top-alert {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
    }
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mediaSosialSelect = document.getElementById('media_sosial_id');
    const judulInput = document.getElementById('judul');
    const keywordInput = document.getElementById('keyword');
    const generatedUrlInput = document.getElementById('generated_url');
    const urlInput = document.getElementById('url');

    // Fungsi untuk mengupdate URL dan keyword dari judul
    function updateUrl() {
        const platform = mediaSosialSelect.options[mediaSosialSelect.selectedIndex] ?
                        mediaSosialSelect.options[mediaSosialSelect.selectedIndex].text : '';
        const judul = judulInput.value.trim();
        
        // Set keyword sama dengan judul
        keywordInput.value = judul;
        
        let url = '';

        if (platform && judul) {
            if (platform === 'Google') {
                // Membuat URL untuk Google Trends/News
                const encodedKeyword = encodeURIComponent(judul);
                url = `news.google.com/search?hl=id&gl=ID&ceid=ID:id&q=${encodedKeyword}`;
            } else if (platform === 'X') {
                // Membuat URL untuk X (Twitter)
                const encodedKeyword = encodeURIComponent(judul);
                url = `twitter.com/search?q=${encodedKeyword}&src=trend`;
            }
        }

        generatedUrlInput.value = url;
        urlInput.value = url ? `https://${url}` : '';
    }

    // Event listeners untuk perubahan
    mediaSosialSelect.addEventListener('change', updateUrl);
    judulInput.addEventListener('input', updateUrl);

    // Inisialisasi URL jika data sudah ada
    updateUrl();
});
</script>
@endsection
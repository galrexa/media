<!-- resources/views/trending/batch-create.blade.php -->
@extends(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isEditor()) ? 'layouts.admin' : 'layouts.app')

@section('title', 'Batch Input Trending')

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trending.index') }}">Daftar Trending</a></li>
                    <li class="breadcrumb-item active">Batch Input</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Batch Input Trending</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('trending.batch.store') }}" method="POST">
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
                    <label class="form-label">Daftar Trending</label>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Masukkan beberapa trending sekaligus. Anda dapat menambahkan lebih banyak baris jika diperlukan.
                    </div>
                </div>

                <div id="trending-container">
                    <div class="row mb-3 trending-item">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <input type="text" class="form-control" name="trendings[0][judul]" placeholder="Judul Trending" required>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="mb-2">
                                <input type="url" class="form-control" name="trendings[0][url]" placeholder="URL" required>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger remove-trending" disabled>
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <button type="button" id="add-trending" class="btn btn-secondary">
                        <i class="bi bi-plus-circle"></i> Tambah Baris
                    </button>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <a href="{{ route('trending.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Semua</button>
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
        const trendingContainer = document.getElementById('trending-container');
        const addTrendingBtn = document.getElementById('add-trending');
        let trendingCounter = 1;
        
        // Add trending row
        addTrendingBtn.addEventListener('click', function() {
            const trendingItem = document.createElement('div');
            trendingItem.className = 'row mb-3 trending-item';
            trendingItem.innerHTML = `
                <div class="col-md-6">
                    <div class="mb-2">
                        <input type="text" class="form-control" name="trendings[${trendingCounter}][judul]" placeholder="Judul Trending" required>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="mb-2">
                        <input type="url" class="form-control" name="trendings[${trendingCounter}][url]" placeholder="URL" required>
                    </div>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger remove-trending">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
            
            trendingContainer.appendChild(trendingItem);
            
            // Enable first row removal if there are multiple rows
            if (trendingContainer.querySelectorAll('.trending-item').length > 1) {
                const firstRowBtn = trendingContainer.querySelector('.remove-trending[disabled]');
                if (firstRowBtn) {
                    firstRowBtn.removeAttribute('disabled');
                }
            }
            
            trendingCounter++;
            
            // Add event listener for new remove button
            const removeBtn = trendingItem.querySelector('.remove-trending');
            removeBtn.addEventListener('click', function() {
                trendingItem.remove();
                
                // Disable first row removal if only one row remains
                if (trendingContainer.querySelectorAll('.trending-item').length === 1) {
                    const firstRowBtn = trendingContainer.querySelector('.remove-trending');
                    if (firstRowBtn) {
                        firstRowBtn.setAttribute('disabled', true);
                    }
                }
            });
        });
        
        // Setup event listeners for initial remove buttons
        document.querySelectorAll('.remove-trending').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.trending-item').remove();
                
                // Disable first row removal if only one row remains
                if (trendingContainer.querySelectorAll('.trending-item').length === 1) {
                    const firstRowBtn = trendingContainer.querySelector('.remove-trending');
                    if (firstRowBtn) {
                        firstRowBtn.setAttribute('disabled', true);
                    }
                }
            });
        });
    });
</script>
@endsection
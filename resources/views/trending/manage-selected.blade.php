<!-- resources/views/trending/manage-selected.blade.php -->
@extends(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isEditor()) ? 'layouts.admin' : 'layouts.app')

@section('title', 'Kelola Trending untuk Home')

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trending.index') }}">Daftar Trending</a></li>
                    <li class="breadcrumb-item active">Kelola Trending untuk Home</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <!-- Trending yang sudah dipilih -->
        <div class="col-lg-6 mb-4">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Trending yang Ditampilkan di Home</h5>
                    <span class="badge bg-light text-dark">{{ $selectedTrendings->count() }} item</span>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Seret dan lepas untuk mengatur urutan tampilan.
                    </div>
                    
                    <div id="selected-trendings" class="selected-list">
                        @if($selectedTrendings->count() > 0)
                            @foreach($selectedTrendings as $trending)
                                <div class="card mb-2 trending-item" data-id="{{ $trending->id }}" data-order="{{ $trending->display_order }}">
                                    <div class="card-body py-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge {{ $trending->mediaSosial->nama == 'Google' ? 'bg-info' : 'bg-dark' }} me-2">
                                                    {{ $trending->mediaSosial->nama }}
                                                </span>
                                                <strong>{{ $trending->judul }}</strong>
                                            </div>
                                            <div>
                                                <form action="{{ route('trending.toggleSelected', $trending) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-x-circle"></i> Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center p-3">
                                <p class="text-muted">Belum ada trending yang dipilih untuk ditampilkan</p>
                            </div>
                        @endif
                    </div>
                    
                    <div class="d-grid mt-3">
                        <button id="save-order" class="btn btn-success">
                            <i class="bi bi-save"></i> Simpan Urutan
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab untuk trending yang tersedia -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Trending Tersedia</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="trendingSourceTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="google-live-tab" data-bs-toggle="tab" data-bs-target="#google-live" type="button" role="tab" aria-controls="google-live" aria-selected="true">Google (Live)</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="x-live-tab" data-bs-toggle="tab" data-bs-target="#x-live" type="button" role="tab" aria-controls="x-live" aria-selected="false">X (Live)</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="google-db-tab" data-bs-toggle="tab" data-bs-target="#google-db" type="button" role="tab" aria-controls="google-db" aria-selected="false">Google (DB)</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="x-db-tab" data-bs-toggle="tab" data-bs-target="#x-db" type="button" role="tab" aria-controls="x-db" aria-selected="false">X (DB)</button>
                        </li>
                    </ul>
                    
                    <div class="tab-content mt-3" id="trendingSourceTabContent">
                        <!-- Tab Google Trends (Live) -->
                        <div class="tab-pane fade show active" id="google-live" role="tabpanel" aria-labelledby="google-live-tab">
                            @if(!empty($trendingGoogle))
                                @foreach($trendingGoogle as $index => $trending)
                                    <div class="card mb-2 border-info">
                                        <div class="card-body py-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="badge bg-info me-2">Google</span>
                                                    <span>{{ $trending['judul'] }}</span>
                                                </div>
                                                <div>
                                                    <form action="{{ route('trending.saveFromFeedWithSelection') }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="name" value="{{ $trending['judul'] }}">
                                                        <input type="hidden" name="url" value="{{ $trending['url'] }}">
                                                        <input type="hidden" name="source" value="google">
                                                        <input type="hidden" name="is_selected" value="1">
                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            <i class="bi bi-plus-circle"></i> Pilih & Simpan
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center p-3">
                                    <p class="text-muted">Tidak ada trending Google tersedia</p>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Tab X (Twitter) Trends (Live) -->
                        <div class="tab-pane fade" id="x-live" role="tabpanel" aria-labelledby="x-live-tab">
                            @if(!empty($trendingTrends24))
                                @foreach($trendingTrends24 as $trending)
                                    <div class="card mb-2 border-dark">
                                        <div class="card-body py-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="badge bg-dark me-2">X</span>
                                                    <span>{{ $trending['name'] }}</span>
                                                </div>
                                                <div>
                                                    <form action="{{ route('trending.saveFromFeedWithSelection') }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="name" value="{{ $trending['name'] }}">
                                                        <input type="hidden" name="url" value="{{ $trending['url'] }}">
                                                        <input type="hidden" name="source" value="x">
                                                        <input type="hidden" name="is_selected" value="1">
                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            <i class="bi bi-plus-circle"></i> Pilih & Simpan
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center p-3">
                                    <p class="text-muted">Tidak ada trending X tersedia</p>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Tab Google Trends (DB) -->
                        <div class="tab-pane fade" id="google-db" role="tabpanel" aria-labelledby="google-db-tab">
                            @if($googleTrendingsDB->count() > 0)
                                @foreach($googleTrendingsDB as $trending)
                                    <div class="card mb-2 border-info">
                                        <div class="card-body py-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="badge bg-info me-2">Google</span>
                                                    <span>{{ $trending->judul }}</span>
                                                </div>
                                                <div>
                                                    @if($trending->is_selected)
                                                        <span class="badge bg-success me-2">Sudah dipilih</span>
                                                    @else
                                                        <form action="{{ route('trending.toggleSelected', $trending) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('PUT')
                                                            <button type="submit" class="btn btn-sm btn-success">
                                                                <i class="bi bi-plus-circle"></i> Pilih
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center p-3">
                                    <p class="text-muted">Tidak ada trending Google dalam database</p>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Tab X (Twitter) Trends (DB) -->
                        <div class="tab-pane fade" id="x-db" role="tabpanel" aria-labelledby="x-db-tab">
                            @if($xTrendingsDB->count() > 0)
                                @foreach($xTrendingsDB as $trending)
                                    <div class="card mb-2 border-dark">
                                        <div class="card-body py-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="badge bg-dark me-2">X</span>
                                                    <span>{{ $trending->judul }}</span>
                                                </div>
                                                <div>
                                                    @if($trending->is_selected)
                                                        <span class="badge bg-success me-2">Sudah dipilih</span>
                                                    @else
                                                        <form action="{{ route('trending.toggleSelected', $trending) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('PUT')
                                                            <button type="submit" class="btn btn-sm btn-success">
                                                                <i class="bi bi-plus-circle"></i> Pilih
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center p-3">
                                    <p class="text-muted">Tidak ada trending X dalam database</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Perbarui Data Trending</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <a href="{{ route('trending.saveAllGoogleTrends') }}" class="btn btn-info w-100">
                                <i class="bi bi-cloud-download"></i> Update Google Trends
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('trending.saveAllTrends24') }}" class="btn btn-dark w-100">
                                <i class="bi bi-cloud-download"></i> Update X Trends
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
    .selected-list {
        min-height: 100px;
        border: 1px dashed #ccc;
        padding: 10px;
        border-radius: 5px;
    }
    
    .trending-item {
        cursor: grab;
    }
    
    .trending-item:active {
        cursor: grabbing;
    }
    
    .trending-item.dragging {
        opacity: 0.5;
    }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi Sortable untuk drag & drop
    const selectedList = document.getElementById('selected-trendings');
    
    if (selectedList) {
        const sortable = new Sortable(selectedList, {
            animation: 150,
            ghostClass: 'dragging',
            onEnd: function() {
                // Update urutan setelah drag & drop
                updateOrderNumbers();
            }
        });
    }
    
    // Update nomor urutan setelah drag & drop
    function updateOrderNumbers() {
        const items = document.querySelectorAll('#selected-trendings .trending-item');
        items.forEach((item, index) => {
            item.setAttribute('data-order', index);
        });
    }
    
    // Simpan urutan ke database
    const saveOrderBtn = document.getElementById('save-order');
    if (saveOrderBtn) {
        saveOrderBtn.addEventListener('click', function() {
            const items = document.querySelectorAll('#selected-trendings .trending-item');
            
            if (items.length === 0) {
                alert('Tidak ada trending yang dipilih!');
                return;
            }
            
            const orderedItems = [];
            items.forEach((item, index) => {
                orderedItems.push({
                    id: item.getAttribute('data-id'),
                    order: index
                });
            });
            
            // Kirim data ke server
            fetch('{{ route("trending.updateOrder") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ items: orderedItems })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Tampilkan notifikasi sukses
                    alert('Urutan trending berhasil disimpan!');
                    location.reload(); // Muat ulang halaman
                } else {
                    alert('Terjadi kesalahan saat menyimpan urutan trending.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menyimpan urutan trending.');
            });
        });
    }
});
</script>
@endsection
<!-- resources/views/trending/manage-google-selected.blade.php -->
@extends(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isEditor()) ? 'layouts.admin' : 'layouts.app')

@section('title', 'Kelola Trending Google')

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trending.selected') }}">Trending Terpilih</a></li>
                    <li class="breadcrumb-item active">Kelola Trending Google</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- @if(session('success'))
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
    @endif -->

    <!-- Filter Tanggal -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Filter Trending</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('trending.manageGoogleSelected') }}" method="GET" class="d-flex gap-2">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                            <input type="date" class="form-control" name="date" value="{{ request('date', date('Y-m-d')) }}">
                        </div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('trending.manageGoogleSelected') }}" class="btn btn-outline-secondary">Reset</a>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Update Data</h5>
                </div>
                <div class="card-body d-flex align-items-center">
                    <a href="{{ route('trending.refreshGoogleTrends') }}" class="btn btn-info w-100">
                        <i class="bi bi-arrow-clockwise me-2"></i> Refresh Trending Google
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Trending Google Live -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Trending Google (Live)</h5>
                    <span class="badge bg-light text-dark">{{ date('d/m/Y') }}</span>
                </div>
                <div class="card-body">
                    @if(!empty($trendingGoogle))
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Judul</th>
                                        <th>Traffic</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($trendingGoogle as $index => $trend)
                                        <tr>
                                            <td>{{ $trend['rank'] }}</td>
                                            <td>
                                                <a href="{{ $trend['url'] }}" target="_blank" class="text-decoration-none">
                                                    {{ $trend['judul'] }}
                                                </a>
                                            </td>
                                            <td>{{ $trend['traffic'] }}</td>
                                            <td class="text-end">
                                            <form action="{{ route('trending.saveGoogleWithSelection') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="date" value="{{ request('date', date('Y-m-d')) }}">
                                                <input type="hidden" name="name" value="{{ $trend['judul'] }}">
                                                <input type="hidden" name="url" value="{{ $trend['url'] }}">
                                                <input type="hidden" name="traffic" value="{{ $trend['traffic'] }}">
                                                <input type="hidden" name="is_selected" value="1">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="bi bi-plus-circle"></i> Pilih
                                                </button>
                                            </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i> Tidak ada data trending Google. Silakan klik tombol "Refresh Trending Google" untuk mengambil data terbaru.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Trending Google yang Dipilih -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Trending Google Terpilih</h5>
                    <span class="badge bg-light text-dark">{{ request('date', date('Y-m-d')) }}</span>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle me-2"></i> Seret dan lepas untuk mengatur urutan tampilan.
                    </div>
                    
                    <div id="selected-google-trendings" class="selected-list">
                        @if($selectedGoogleTrendings->count() > 0)
                            @foreach($selectedGoogleTrendings as $trending)
                                <div class="card mb-2 trending-item" data-id="{{ $trending->id }}" data-order="{{ $trending->display_order_google }}">
                                    <div class="card-body py-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-primary me-2">{{ $loop->iteration }}</span>
                                                <strong>{{ $trending->judul }}</strong>
                                            </div>
                                            <div>
                                                <form action="{{ route('trending.toggleSelected', $trending) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="date" value="{{ request('date', date('Y-m-d')) }}">
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
                                <p class="text-muted">Belum ada trending Google yang dipilih untuk ditampilkan pada tanggal ini</p>
                            </div>
                        @endif
                    </div>
                    
                    @if($selectedGoogleTrendings->count() > 0)
                        <div class="d-grid mt-3">
                            <button id="save-google-order" class="btn btn-success">
                                <i class="fas fa-floppy-disk me-2"></i> Simpan Urutan
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12 text-center">
            <a href="{{ route('trending.selected') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-2"></i> Kembali ke Trending Terpilih
            </a>
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
    
    .card-header.bg-info {
        background: linear-gradient(135deg, #4285f4, #0d6efd) !important;
    }
    
    .card-header.bg-success {
        background: linear-gradient(135deg, #198754, #28a745) !important;
    }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi Sortable untuk trending Google
    const selectedGoogleList = document.getElementById('selected-google-trendings');
    
    if (selectedGoogleList) {
        const googleSortable = new Sortable(selectedGoogleList, {
            animation: 150,
            ghostClass: 'dragging',
            onEnd: function() {
                // Update urutan setelah drag & drop
                updateGoogleOrderNumbers();
            }
        });
    }
    
    // Update nomor urutan Google setelah drag & drop
    function updateGoogleOrderNumbers() {
        const items = document.querySelectorAll('#selected-google-trendings .trending-item');
        items.forEach((item, index) => {
            item.setAttribute('data-order', index);
            
            // Update nomor urutan yang ditampilkan
            const badge = item.querySelector('.badge');
            if (badge) {
                badge.innerText = index + 1;
            }
        });
    }
    
    // Simpan urutan Google ke database
    const saveGoogleOrderBtn = document.getElementById('save-google-order');
    if (saveGoogleOrderBtn) {
        saveGoogleOrderBtn.addEventListener('click', function() {
            const items = document.querySelectorAll('#selected-google-trendings .trending-item');
            
            if (items.length === 0) {
                alert('Tidak ada trending Google yang dipilih!');
                return;
            }
            
            const orderedItems = [];
            items.forEach((item, index) => {
                orderedItems.push({
                    id: item.getAttribute('data-id'),
                    order: index
                });
            });
            
            // Ambil tanggal dari parameter URL
            const urlParams = new URLSearchParams(window.location.search);
            const date = urlParams.get('date') || '{{ date('Y-m-d') }}';
            
            // Kirim data ke server
            fetch('{{ route("trending.updateGoogleOrder") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ 
                    items: orderedItems,
                    date: date
                })
            })
            .then(response => response.json())
            .then(data => {
                // Buat elemen notifikasi
                const alertDiv = document.createElement('div');
                
                // Atur kelas dan pesan berdasarkan status respons
                alertDiv.className = data.success ? 'alert alert-success' : 'alert alert-danger';
                alertDiv.textContent = data.success ? 'Urutan trending Google berhasil disimpan!' : 'Terjadi kesalahan saat menyimpan urutan trending Google.';
                
                // Tambahkan notifikasi ke halaman
                document.querySelector('.container').prepend(alertDiv); // Sesuaikan selector container
                
                // Otomatis hilangkan notifikasi setelah 3 detik
                setTimeout(() => alertDiv.remove(), 3000);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menyimpan urutan trending Google.');
            });
        });
    }
});
</script>
@endsection
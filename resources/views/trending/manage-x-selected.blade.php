<!-- resources/views/trending/manage-x-selected.blade.php -->
@extends(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isEditor()) ? 'layouts.admin' : 'layouts.app')

@section('title', 'Kelola Trending X')

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trending.selected') }}">Trending Terpilih</a></li>
                    <li class="breadcrumb-item active">Kelola Trending X</li>
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

    <!-- Filter Tanggal -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Filter Trending</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('trending.manageXSelected') }}" method="GET" class="d-flex gap-2">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                            <input type="date" class="form-control" name="date" value="{{ request('date', date('Y-m-d')) }}">
                        </div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('trending.manageXSelected') }}" class="btn btn-outline-secondary">Reset</a>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Update Data</h5>
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    <a href="{{ route('trending.refreshXTrends') }}" class="btn btn-dark">
                        <i class="bi bi-arrow-clockwise me-2"></i> Refresh Trending X
                    </a>
                    <a href="{{ route('trending.manual.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i> Tambah Trending Manual
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Trending X Live -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Trending X (Live)</h5>
                    <span class="badge bg-light text-dark">{{ date('d/m/Y') }}</span>
                </div>
                <div class="card-body">
                    @if(!empty($trendingX))
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Judul</th>
                                        <th>Tweet Count</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($trendingX as $index => $trend)
                                        <tr>
                                            <td>{{ $trend['rank'] }}</td>
                                            <td>
                                                <a href="{{ $trend['url'] }}" target="_blank" class="text-decoration-none">
                                                    {{ $trend['name'] }}
                                                </a>
                                            </td>
                                            <td>{{ $trend['tweet_count'] ?? 'N/A' }}</td>
                                            <td class="text-end">
                                                <form action="{{ route('trending.saveXWithSelection') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="date" value="{{ request('date', date('Y-m-d')) }}">
                                                    <input type="hidden" name="name" value="{{ $trend['name'] }}">
                                                    <input type="hidden" name="url" value="{{ $trend['url'] }}">
                                                    <input type="hidden" name="tweet_count" value="{{ $trend['tweet_count'] ?? '' }}">
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
                            <i class="bi bi-info-circle me-2"></i> Tidak ada data trending X. Silakan klik tombol "Refresh Trending X" untuk mengambil data terbaru.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Trending X yang Dipilih -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Trending X Terpilih</h5>
                    <span class="badge bg-light text-dark">{{ request('date', date('Y-m-d')) }}</span>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle me-2"></i> Seret dan lepas untuk mengatur urutan tampilan.
                    </div>

                    <div id="selected-x-trendings" class="selected-list">
                        @if($selectedXTrendings->count() > 0)
                            @foreach($selectedXTrendings as $trending)
                                <div class="card mb-2 trending-item" data-id="{{ $trending->id }}" data-order="{{ $trending->display_order_x }}">
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
                                <p class="text-muted">Belum ada trending X yang dipilih untuk ditampilkan pada tanggal ini</p>
                            </div>
                        @endif
                    </div>

                    @if($selectedXTrendings->count() > 0)
                        <div class="d-grid mt-3">
                            <button id="save-x-order" class="btn btn-success">
                                <i class="fa-floppy-disk me-2"></i> Simpan Urutan
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

    .card-header.bg-dark {
        background: linear-gradient(135deg, #333333, #212529) !important;
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
    // Inisialisasi Sortable untuk trending X
    const selectedXList = document.getElementById('selected-x-trendings');

    if (selectedXList) {
        const xSortable = new Sortable(selectedXList, {
            animation: 150,
            ghostClass: 'dragging',
            onEnd: function() {
                // Update urutan setelah drag & drop
                updateXOrderNumbers();
            }
        });
    }

    // Update nomor urutan X setelah drag & drop
    function updateXOrderNumbers() {
        const items = document.querySelectorAll('#selected-x-trendings .trending-item');
        items.forEach((item, index) => {
            item.setAttribute('data-order', index);

            // Update nomor urutan yang ditampilkan
            const badge = item.querySelector('.badge');
            if (badge) {
                badge.innerText = index + 1;
            }
        });
    }

    // Simpan urutan X ke database
    const saveXOrderBtn = document.getElementById('save-x-order');
    if (saveXOrderBtn) {
        saveXOrderBtn.addEventListener('click', function() {
            const items = document.querySelectorAll('#selected-x-trendings .trending-item');

            if (items.length === 0) {
                alert('Tidak ada trending X yang dipilih!');
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
            fetch('{{ route("trending.updateXOrder") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    items: orderedItems,
                    date: '{{ request('date', date('Y-m-d')) }}'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Tampilkan notifikasi sukses
                    alert('Urutan trending X berhasil disimpan!');
                } else {
                    alert('Terjadi kesalahan saat menyimpan urutan trending X.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menyimpan urutan trending X.');
            });
        });
    }
});
</script>
@endsection

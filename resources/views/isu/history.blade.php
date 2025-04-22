<!-- resources/views/isu/history.blade.php -->
@extends(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isEditor()) ? 'layouts.admin' : 'layouts.app')

@section('title', 'Riwayat Perubahan Isu')

@section('content')
<div class="container">
    <!-- Breadcrumb -->
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('isu.index') }}">Isu</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('isu.show', $isu) }}">{{ $isu->judul }}</a></li>
                    <li class="breadcrumb-item active">Riwayat Perubahan</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Card Utama -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Riwayat Perubahan: {{ $isu->judul }}</h4>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Tanggal & Waktu</th>
                            <th>User</th>
                            <th>Aksi</th>
                            <th>Detail Perubahan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('d M Y H:i:s') }}</td>
                                <td>{{ $log->user ? $log->user->name : 'User tidak tersedia' }}</td>
                                <td>
                                    @if($log->action == 'CREATE')
                                        <span class="badge bg-success">Dibuat</span>
                                    @elseif($log->action == 'UPDATE')
                                        <span class="badge bg-warning text-dark">Diubah</span>
                                    @elseif($log->action == 'DELETE')
                                        <span class="badge bg-danger">Dihapus</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->action == 'UPDATE')
                                        <strong>{{ $log->field_changed }}</strong>
                                        <div class="small">
                                            <div class="text-danger">- {{ Str::limit($log->old_value, 100) }}</div>
                                            <div class="text-success">+ {{ Str::limit($log->new_value, 100) }}</div>
                                        </div>
                                    @elseif($log->action == 'CREATE')
                                        <span class="text-muted">Isu baru dibuat</span>
                                    @elseif($log->action == 'DELETE')
                                        <span class="text-muted">Isu dihapus</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">Tidak ada riwayat perubahan</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $logs->links() }}
            </div>
            
            <!-- Tombol Kembali -->
            <div class="mt-3">
                <a href="{{ route('isu.show', $isu) }}" class="btn btn-light border">
                    <i class="bi bi-arrow-left"></i> Kembali ke Detail Isu
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
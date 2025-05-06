<!-- resources/views/isu/history.blade.php -->
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

@section('title', 'Riwayat Perubahan Isu')

@section('content')
<div class="container">
    <!-- Breadcrumb dengan ARIA -->
    <nav aria-label="Navigasi breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" aria-label="Kembali ke Beranda">Beranda</a></li>
            <li class="breadcrumb-item"><a href="{{ route('isu.index') }}" aria-label="Kembali ke Daftar Isu">Isu</a></li>
            <li class="breadcrumb-item"><a href="{{ route('isu.show', $isu) }}" aria-label="Kembali ke Detail Isu">{{ $isu->judul }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Riwayat Perubahan</li>
        </ol>
    </nav>

    <!-- Card Utama -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex align-items-center">
            <i class="bi bi-clock-history me-2"></i>
            <h4 class="mb-0">Riwayat Perubahan: {{ $isu->judul }}</h4>
        </div>
        <div class="card-body p-4">
            <!-- Timeline View -->
            <div class="timeline-container">
                @php
                    // Filter log untuk mengabaikan perubahan pada field tanggal
                    $filteredLogs = $logs->filter(function($log) {
                        // Cek apakah field_changed mengandung kata 'tanggal'
                        $isTanggalField = $log->field_changed == 'tanggal' || 
                                        strpos($log->field_changed, 'tanggal_') === 0;
                        
                        // Jika action adalah UPDATE dan field adalah tanggal, filter keluar
                        if ($log->action == 'UPDATE' && $isTanggalField) {
                            return false;
                        }
                        
                        return true;
                    });
                    
                    // Mengelompokkan log berdasarkan waktu dan user untuk timeline
                    $groupedLogs = [];
                    $currentDate = null;
                    $currentUser = null;
                    $currentTimestamp = null;
                    
                    foreach($filteredLogs as $index => $log) {
                        $logDate = $log->created_at->format('d/m/Y H:i:s');
                        $logUser = $log->user ? $log->user->name : 'User tidak tersedia';
                        
                        // Jika tanggal dan user sama, kelompokkan bersama
                        if ($logDate == $currentDate && $logUser == $currentUser) {
                            $groupedLogs[$currentTimestamp]['logs'][] = $log;
                        } else {
                            $currentTimestamp = $log->created_at->timestamp;
                            $currentDate = $logDate;
                            $currentUser = $logUser;
                            
                            $groupedLogs[$currentTimestamp] = [
                                'date' => $logDate,
                                'user' => $logUser,
                                'logs' => [$log],
                                'position' => $index % 2 == 0 ? 'right' : 'left'
                            ];
                        }
                    }
                    
                    // Urutkan log dari yang terbaru
                    krsort($groupedLogs);
                @endphp
                
                <div class="timeline">
                    @forelse($groupedLogs as $timestamp => $group)
                        <div class="timeline-item {{ $group['position'] }}">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content {{ $group['position'] == 'left' ? 'timeline-left' : 'timeline-right' }}">
                                <div class="timeline-date">{{ $group['date'] }}</div>
                                <div class="timeline-card">
                                    <div class="timeline-header">
                                        @if(isset($group['logs'][0]))
                                            @php $firstLog = $group['logs'][0] @endphp
                                            @if($firstLog->action == 'CREATE')
                                                <span class="badge bg-success">Dibuat</span>
                                            @elseif($firstLog->action == 'UPDATE')
                                                <span class="badge bg-warning">Diubah</span>
                                            @elseif($firstLog->action == 'DELETE')
                                                <span class="badge bg-danger">Dihapus</span>
                                            @endif
                                        @endif
                                        <span class="ms-2">oleh {{ $group['user'] }}</span>
                                        <i class="bi bi-info-circle ms-2" title="Klik untuk melihat detail"></i>
                                    </div>
                                    <div class="timeline-body">
                                    
                                    @foreach($group['logs'] as $log)
                                        <div class="timeline-change">
                                            @if($log->action == 'UPDATE')
                                                <div class="mb-2">
                                                    <strong>{{ $log->field_changed }}</strong>
                                                    
                                                    @php
                                                        // Pengecekan khusus untuk kolom kategori
                                                        $oldFormatted = $log->getFormattedOldValue();
                                                        $newFormatted = $log->getFormattedNewValue();
                                                        $showDiff = true;
                                                        
                                                        // Jika field adalah kategori, pastikan nilai ditampilkan dengan benar
                                                        if ($log->field_changed === 'kategori') {
                                                            // Pastikan nilai ditampilkan dalam urutan yang benar
                                                            $tempOld = $oldFormatted;
                                                            $tempNew = $newFormatted;
                                                            
                                                            // Periksa apakah nilai baru lebih panjang dari nilai lama
                                                            // Yang mengindikasikan penambahan kategori bukan pengurangan
                                                            if (strlen($tempNew) > strlen($tempOld)) {
                                                                // Tukar nilai untuk menampilkan dalam urutan yang benar
                                                                $oldFormatted = $tempNew;
                                                                $newFormatted = $tempOld;
                                                            }
                                                        }
                                                    @endphp
                                                    
                                                    @if($showDiff && !str_contains($log->field_changed, 'tanggal'))
                                                        <div class="small">
                                                            <div class="text-danger">- {{ $oldFormatted }}</div>
                                                            <div class="text-success">+ {{ $newFormatted }}</div>
                                                        </div>
                                                    @else
                                                        <div class="small text-muted">
                                                            <em>{{ str_contains($log->field_changed, 'tanggal') ? 'Perubahan tanggal' : 'Perubahan format' }}</em>
                                                        </div>
                                                    @endif
                                                </div>
                                            @elseif($log->action == 'CREATE')
                                                <div class="text-muted">Isu baru dibuat</div>
                                            @elseif($log->action == 'DELETE')
                                                <div class="text-muted">Isu dihapus</div>
                                            @endif
                                        </div>
                                        @if(!$loop->last)
                                            <hr class="timeline-divider">
                                        @endif
                                    @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <p>Tidak ada riwayat perubahan. <a href="{{ route('isu.show', $isu) }}">Kembali ke detail isu</a>.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $logs->links() }}
            </div>

            <!-- Tombol Kembali -->
            <div class="mt-3">
                <a href="{{ route('isu.show', $isu) }}" class="btn btn-light border" aria-label="Kembali ke Detail Isu">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Timeline Styles */
.timeline-container {
    padding: 20px 0;
    width: 100%;
    position: relative;
}

.timeline {
    position: relative;
    width: 100%;
}

.timeline::after {
    content: '';
    position: absolute;
    width: 2px;
    background-color: #e0e0e0;
    top: 0;
    bottom: 0;
    left: 50%;
    margin-left: -1px;
}

.timeline-item {
    padding: 10px 40px;
    position: relative;
    width: 50%;
    box-sizing: border-box;
    margin-bottom: 30px;
}

.timeline-item.left {
    left: 0;
}

.timeline-item.right {
    left: 50%;
}

.timeline-dot {
    width: 20px;
    height: 20px;
    background-color: #ffeb3b;
    border-radius: 50%;
    position: absolute;
    right: -10px;
    top: 15px;
    z-index: 1;
    border: 3px solid white;
}

.timeline-item.right .timeline-dot {
    left: -10px;
}

.timeline-content {
    padding: 15px;
    position: relative;
}

.timeline-left {
    margin-right: 20px;
}

.timeline-right {
    margin-left: 20px;
}

.timeline-date {
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 5px;
    font-weight: bold;
}

.timeline-card {
    background-color: #ffff9c;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.timeline-header {
    margin-bottom: 10px;
    font-weight: bold;
    display: flex;
    align-items: center;
}

.timeline-body {
    font-size: 0.9rem;
}

.timeline-change {
    margin-bottom: 8px;
}

.timeline-divider {
    margin: 10px 0;
    border-top: 1px dashed #ddd;
}

/* Responsive design */
@media screen and (max-width: 768px) {
    .timeline::after {
        left: 31px;
    }
    
    .timeline-item {
        width: 100%;
        padding-left: 70px;
        padding-right: 25px;
    }
    
    .timeline-item.right {
        left: 0;
    }
    
    .timeline-dot {
        left: 21px;
        right: auto;
    }
    
    .timeline-item.left .timeline-dot {
        left: 21px;
    }
    
    .timeline-content {
        width: 100%;
    }
    
    .timeline-left, .timeline-right {
        margin-left: 0;
        margin-right: 0;
    }
}
</style>
@endsection
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
                                    <!-- Di bagian view, pada file history.blade.php -->
                                    @foreach($group['logs'] as $index => $log)
                                        @php
                                            // Untuk UPDATE, cek dulu apakah ada perubahan substantif
                                            $skipUpdate = false;
                                            
                                            if ($log->action == 'UPDATE') {
                                                // Siapkan nilai yang akan ditampilkan
                                                $oldFormatted = $log->getProcessedOldValue();
                                                $newFormatted = $log->getProcessedNewValue();
                                                
                                                // Normalisasi untuk perbandingan
                                                $oldNormalized = is_string($oldFormatted) ? trim(html_entity_decode($oldFormatted, ENT_QUOTES | ENT_HTML5, 'UTF-8')) : $oldFormatted;
                                                $newNormalized = is_string($newFormatted) ? trim(html_entity_decode($newFormatted, ENT_QUOTES | ENT_HTML5, 'UTF-8')) : $newFormatted;
                                                
                                                // Skip jika tidak ada perubahan substantif
                                                $skipUpdate = ($oldNormalized === $newNormalized);
                                            }
                                        @endphp
                                        
                                        @if(!$skipUpdate)
                                            <div class="timeline-change">
                                                @if($log->action == 'UPDATE')
                                                    <div class="mb-2">
                                                        <strong>{{ ucfirst(str_replace('_', ' ', $log->field_changed)) }}</strong>
                                                        
                                                        @php
                                                        // Siapkan nilai yang akan ditampilkan (sudah dihitung di atas)
                                                        // $oldFormatted dan $newFormatted
                                                        
                                                        // Jika field adalah kategori, pastikan nilai ditampilkan dengan benar
                                                        if ($log->field_changed === 'kategori') {
                                                            // Pastikan nilai ditampilkan dalam urutan yang benar
                                                            $tempOld = $oldFormatted;
                                                            $tempNew = $newFormatted;
                                                            
                                                            // Periksa apakah nilai baru lebih panjang dari nilai lama
                                                            if (strlen($tempNew) > strlen($tempOld)) {
                                                                // Tukar nilai untuk menampilkan dalam urutan yang benar
                                                                $oldFormatted = $tempNew;
                                                                $newFormatted = $tempOld;
                                                            }
                                                        }
                                                        
                                                        // Hilangkan tag HTML jika field tertentu
                                                        if (in_array($log->field_changed, ['rangkuman', 'narasi_positif', 'narasi_negatif', 'deskripsi'])) {
                                                            $oldFormatted = strip_tags($oldFormatted);
                                                            $newFormatted = strip_tags($newFormatted);
                                                        }
                                                        
                                                        // Format status jika JSON
                                                        if ($log->field_changed === 'status' && strpos($oldFormatted, '{') === 0) {
                                                            try {
                                                                $statusData = json_decode($oldFormatted, true);
                                                                if (isset($statusData['nama'])) {
                                                                    $oldFormatted = $statusData['nama'];
                                                                }
                                                            } catch (\Exception $e) {}
                                                        }
                                                        
                                                        if ($log->field_changed === 'status' && strpos($newFormatted, '{') === 0) {
                                                            try {
                                                                $statusData = json_decode($newFormatted, true);
                                                                if (isset($statusData['nama'])) {
                                                                    $newFormatted = $statusData['nama'];
                                                                }
                                                            } catch (\Exception $e) {}
                                                        }
                                                        
                                                        // Format tone ID ke nama
                                                        if ($log->field_changed === 'tone' && is_numeric($oldFormatted)) {
                                                            try {
                                                                $tone = \App\Models\RefTone::find($oldFormatted);
                                                                if ($tone) {
                                                                    $oldFormatted = $tone->nama;
                                                                }
                                                            } catch (\Exception $e) {}
                                                        }
                                                        
                                                        if ($log->field_changed === 'tone' && is_numeric($newFormatted)) {
                                                            try {
                                                                $tone = \App\Models\RefTone::find($newFormatted);
                                                                if ($tone) {
                                                                    $newFormatted = $tone->nama;
                                                                }
                                                            } catch (\Exception $e) {}
                                                        }
                                                        
                                                        // Format skala ID ke nama
                                                        if ($log->field_changed === 'skala' && is_numeric($oldFormatted)) {
                                                            try {
                                                                $skala = \App\Models\RefSkala::find($oldFormatted);
                                                                if ($skala) {
                                                                    $oldFormatted = $skala->nama;
                                                                }
                                                            } catch (\Exception $e) {}
                                                        }
                                                        
                                                        if ($log->field_changed === 'skala' && is_numeric($newFormatted)) {
                                                            try {
                                                                $skala = \App\Models\RefSkala::find($newFormatted);
                                                                if ($skala) {
                                                                    $newFormatted = $skala->nama;
                                                                }
                                                            } catch (\Exception $e) {}
                                                        }
                                                        
                                                        $showDiff = !str_contains($log->field_changed, 'tanggal');
                                                        @endphp
                                                        
                                                        @if($showDiff)
                                                            <div class="small">
                                                                <div class="text-danger">
                                                                    - @if(strlen($oldFormatted) > 100)
                                                                        <span class="short-text" id="old-{{ $log->id }}">{{ substr($oldFormatted, 0, 100) }}</span>
                                                                        <span class="full-text" id="old-full-{{ $log->id }}" style="display:none;">{{ $oldFormatted }}</span>
                                                                        <a href="#" class="text-primary toggle-text ms-1" 
                                                                        data-target="old-{{ $log->id }}"
                                                                        data-full-target="old-full-{{ $log->id }}">... lihat semua</a>
                                                                    @else
                                                                        {{ $oldFormatted }}
                                                                    @endif
                                                                </div>
                                                                <div class="text-success">
                                                                    + @if(strlen($newFormatted) > 100)
                                                                        <span class="short-text" id="new-{{ $log->id }}">{{ substr($newFormatted, 0, 100) }}</span>
                                                                        <span class="full-text" id="new-full-{{ $log->id }}" style="display:none;">{{ $newFormatted }}</span>
                                                                        <a href="#" class="text-primary toggle-text ms-1" 
                                                                        data-target="new-{{ $log->id }}"
                                                                        data-full-target="new-full-{{ $log->id }}">... lihat semua</a>
                                                                    @else
                                                                        {{ $newFormatted }}
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @else
                                                            <div class="small text-muted">
                                                                <em>{{ str_contains($log->field_changed, 'tanggal') ? 'Perubahan tanggal' : 'Perubahan format' }}</em>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @elseif($log->action == 'CREATE' && $index === 0)
                                                    <div class="mb-2">
                                                        <div class="text-muted mb-2">Isu baru dibuat dengan data awal:</div>
                                                        
                                                        @php
                                                            // Kumpulkan semua field dari log CREATE di grup yang sama
                                                            $createFields = [];
                                                            foreach ($group['logs'] as $createLog) {
                                                                if ($createLog->action == 'CREATE' && $createLog->field_changed) {
                                                                    $value = $createLog->getFormattedNewValue() ?: $createLog->getFormattedOldValue();
                                                                    $createFields[$createLog->field_changed] = $value;
                                                                }
                                                            }
                                                            
                                                            // PRIORITASKAN DATA LANGSUNG DARI MODEL ISU
                                                            if ($log->isu) {
                                                                $isu = $log->isu;
                                                                
                                                                // Daftar semua field yang ingin kita tampilkan
                                                                $allFields = [
                                                                    'judul', 'deskripsi', 'kategori', 'status',
                                                                    'rangkuman', 'narasi_positif', 'narasi_negatif',
                                                                    'tone', 'skala'
                                                                ];
                                                                
                                                                // Periksa dan tambahkan nilai dari model Isu jika tersedia
                                                                foreach ($allFields as $field) {
                                                                    if (!isset($createFields[$field]) || $createFields[$field] == '(kosong)') {
                                                                        // Coba dengan method getter khusus terlebih dahulu
                                                                        if (method_exists($isu, 'get' . ucfirst($field) . 'Name')) {
                                                                            $method = 'get' . ucfirst($field) . 'Name';
                                                                            $value = $isu->$method();
                                                                            if ($value) {
                                                                                $createFields[$field] = $value;
                                                                            }
                                                                        }
                                                                        // Coba dengan property langsung
                                                                        elseif (property_exists($isu, $field) || isset($isu->$field)) {
                                                                            $value = $isu->$field;
                                                                            if ($value) {
                                                                                $createFields[$field] = $value;
                                                                            }
                                                                        }
                                                                        // Khusus untuk relasi
                                                                        elseif ($field == 'tone' && $isu->tone_id) {
                                                                            try {
                                                                                $tone = \App\Models\RefTone::find($isu->tone_id);
                                                                                if ($tone) {
                                                                                    $createFields['tone'] = $tone->nama;
                                                                                } else {
                                                                                    $createFields['tone'] = $isu->tone_id;
                                                                                }
                                                                            } catch (\Exception $e) {
                                                                                $createFields['tone'] = $isu->tone_id;
                                                                            }
                                                                        }
                                                                        elseif ($field == 'skala' && $isu->skala_id) {
                                                                            try {
                                                                                $skala = \App\Models\RefSkala::find($isu->skala_id);
                                                                                if ($skala) {
                                                                                    $createFields['skala'] = $skala->nama;
                                                                                } else {
                                                                                    $createFields['skala'] = $isu->skala_id;
                                                                                }
                                                                            } catch (\Exception $e) {
                                                                                $createFields['skala'] = $isu->skala_id;
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                            
                                                            // Tambahan untuk field judul dan status jika masih kosong
                                                            if (!isset($createFields['judul']) && $log->isu) {
                                                                $createFields['judul'] = $log->isu->judul;
                                                            }
                                                            
                                                            if (!isset($createFields['status'])) {
                                                                $createFields['status'] = 'Draft';
                                                            }
                                                            
                                                            // Proses formatting untuk field dalam createFields
                                                            foreach ($createFields as $field => $value) {
                                                                // Hilangkan tag HTML jika field tertentu dan normalisasi
                                                                if (in_array($field, ['rangkuman', 'narasi_positif', 'narasi_negatif', 'deskripsi'])) {
                                                                    $value = strip_tags($value);
                                                                    // Normalisasi string untuk menghilangkan whitespace berlebih dan entity HTML
                                                                    if (is_string($value)) {
                                                                        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                                                        $value = preg_replace('/\s+/', ' ', $value);
                                                                        $value = trim($value);
                                                                        $createFields[$field] = $value;
                                                                    }
                                                                }
                                                                
                                                                // Format status jika JSON
                                                                if ($field === 'status' && is_string($value) && strpos($value, '{') === 0) {
                                                                    try {
                                                                        $statusData = json_decode($value, true);
                                                                        if (isset($statusData['nama'])) {
                                                                            $createFields[$field] = $statusData['nama'];
                                                                        }
                                                                    } catch (\Exception $e) {}
                                                                }
                                                                
                                                                // Format tone ID ke nama jika belum diproses
                                                                if ($field === 'tone' && is_numeric($value)) {
                                                                    try {
                                                                        $tone = \App\Models\RefTone::find($value);
                                                                        if ($tone) {
                                                                            $createFields[$field] = $tone->nama;
                                                                        }
                                                                    } catch (\Exception $e) {}
                                                                }
                                                                
                                                                // Format skala ID ke nama jika belum diproses
                                                                if ($field === 'skala' && is_numeric($value)) {
                                                                    try {
                                                                        $skala = \App\Models\RefSkala::find($value);
                                                                        if ($skala) {
                                                                            $createFields[$field] = $skala->nama;
                                                                        }
                                                                    } catch (\Exception $e) {}
                                                                }
                                                                
                                                                // Normalisasi umum untuk semua string
                                                                if (is_string($createFields[$field])) {
                                                                    $createFields[$field] = html_entity_decode($createFields[$field], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                                                    $createFields[$field] = preg_replace('/\s+/', ' ', $createFields[$field]);
                                                                    $createFields[$field] = trim($createFields[$field]);
                                                                }
                                                            }
                                                            
                                                            // Urutkan field untuk ditampilkan
                                                            $fieldOrder = [
                                                                'judul', 'deskripsi', 'kategori',
                                                                'rangkuman', 'narasi_positif', 'narasi_negatif',
                                                                'tone', 'skala'
                                                            ];
                                                        @endphp
                                                        
                                                        <!-- Detail data awal -->
                                                        @foreach($fieldOrder as $fieldName)
                                                            @php
                                                                $fieldValue = isset($createFields[$fieldName]) ? $createFields[$fieldName] : null;
                                                                
                                                                // Skip jika tidak ada nilai atau kosong
                                                                if (!$fieldValue || $fieldValue == '(kosong)') {
                                                                    continue;
                                                                }
                                                                
                                                                // Format nama field untuk tampilan
                                                                $displayName = ucfirst(str_replace('_', ' ', $fieldName));
                                                                if ($fieldName == 'narasi_positif') $displayName = 'Narasi Positif';
                                                                if ($fieldName == 'narasi_negatif') $displayName = 'Narasi Negatif';
                                                            @endphp
                                                            
                                                            <div class="mb-2">
                                                                <strong>{{ $displayName }}</strong>
                                                                <div class="small">
                                                                    <div class="text-success">
                                                                        @if(strlen($fieldValue) > 100)
                                                                            <span class="short-text">{{ substr($fieldValue, 0, 100) }}</span>
                                                                            <span class="full-text" style="display:none;">{{ $fieldValue }}</span>
                                                                            <a href="#" class="text-primary toggle-text ms-1" 
                                                                            data-toggle="expand" 
                                                                            data-target-id="create-{{ $fieldName }}-{{ $log->id }}">... lihat semua</a>
                                                                        @else
                                                                            {{ $fieldValue }}
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @elseif($log->action == 'DELETE')
                                                    <div class="text-muted">Isu dihapus</div>
                                                @endif
                                            </div>
                                            
                                            @if(!$loop->last && !($log->action == 'CREATE' && isset($group['logs'][$index+1]) && $group['logs'][$index+1]->action == 'CREATE'))
                                                <hr class="timeline-divider">
                                            @endif
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

/* Styling untuk initial data container */
.initial-data-container {
    background-color: rgba(255, 255, 255, 0.6);
    border-radius: 6px;
    padding: 12px 15px;
    margin-top: 8px;
    box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.05);
}

.initial-value {
    padding-left: 12px;
    border-left: 3px solid #f0f0f0;
    margin-bottom: 8px;
}

/* Styling untuk nama field */
.initial-data-container strong {
    color: #555;
    min-width: 120px;
    display: inline-block;
}

.toggle-text {
    font-size: 0.8rem;
    font-weight: normal;
    white-space: nowrap;
    text-decoration: none;
}

.toggle-text:hover {
    text-decoration: underline;
}

.short-text, .full-text {
    word-wrap: break-word;
    word-break: break-word;
    white-space: pre-wrap;
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle untuk expand/collapse text - versi unified yang bekerja untuk semua
    document.querySelectorAll('.toggle-text').forEach(function(toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Cari parent container dan elements short/full
            const parent = this.closest('div');
            const shortText = parent.querySelector('.short-text');
            const fullText = parent.querySelector('.full-text');
            
            // Toggle visibility
            if (shortText && fullText) {
                if (fullText.style.display === 'none') {
                    // Expand
                    shortText.style.display = 'none';
                    fullText.style.display = 'inline';
                    this.textContent = '... lebih sedikit';
                } else {
                    // Collapse
                    fullText.style.display = 'none';
                    shortText.style.display = 'inline';
                    this.textContent = '... lihat semua';
                }
            }
            
            // Untuk format yang mungkin menggunakan data-target-id
            const targetId = this.getAttribute('data-target-id');
            if (targetId) {
                const container = document.getElementById(targetId);
                if (container) {
                    const targetShort = container.querySelector('.short-text');
                    const targetFull = container.querySelector('.full-text');

                    if (targetShort && targetFull) {
                       if (targetFull.style.display === 'none') {
                           targetShort.style.display = 'none';
                           targetFull.style.display = 'inline';
                           this.textContent = '... lebih sedikit';
                       } else {
                           targetFull.style.display = 'none';
                           targetShort.style.display = 'inline';
                           this.textContent = '... lihat semua';
                       }
                   }
               }
           }
           
           // Untuk kasus data-short dan data-full
           const shortSelector = this.getAttribute('data-short');
           const fullSelector = this.getAttribute('data-full');
           if (shortSelector && fullSelector) {
               const shortElement = document.querySelector(shortSelector);
               const fullElement = document.querySelector(fullSelector);
               
               if (shortElement && fullElement) {
                   if (fullElement.style.display === 'none') {
                       shortElement.style.display = 'none';
                       fullElement.style.display = 'inline';
                       this.textContent = '... lebih sedikit';
                   } else {
                       fullElement.style.display = 'none';
                       shortElement.style.display = 'inline';
                       this.textContent = '... lihat semua';
                   }
               }
           }
       });
   });
});
</script>
@endsection
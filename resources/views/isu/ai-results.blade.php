@extends(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isEditor()) ? 'layouts.admin' : 'layouts.app')

@section('title', 'Hasil Analisis AI - Review & Edit')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Header Section -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <div>
                            <h2 class="text-primary mb-1 fw-bold">
                                <i class="fas fa-check-circle me-2"></i>Hasil Analisis AI
                            </h2>
                            <p class="text-muted mb-0 small">Review dan edit hasil analisis sebelum menyimpan isu</p>
                        </div>
                        <div>
                            <span class="badge bg-success text-white px-3 py-2 fs-6">
                                <i class="fas fa-robot me-1"></i>AI Analysis Complete
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analysis Summary -->
            <div class="row g-4 mb-4">
                <div class="col-md-8">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-info text-white p-2">
                            <h5 class="mb-0 d-flex align-items-center">
                                <i class="fas fa-info-circle me-2"></i>Ringkasan Analisis
                            </h5>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <strong class="text-dark">Session ID:</strong>
                                    <span class="text-muted small">{{ $analysisResult->session_id ?? 'AI-' . date('Ymd-His') }}</span>
                                </div>
                                <div class="col-md-6">
                                    <strong class="text-dark">Waktu Proses:</strong>
                                    <span class="text-muted small">{{ $analysisResult->processing_time ?? '2' }} detik</span>
                                </div>
                                <div class="col-md-6">
                                    <strong class="text-dark">URL Dianalisis:</strong>
                                    <span class="badge bg-primary text-white">{{ count($analysisResult->urls ?? []) }} URL</span>
                                </div>
                                <div class="col-md-6">
                                    <strong class="text-dark">AI Provider:</strong>
                                    <span class="text-muted small">{{ $analysisResult->ai_provider ?? 'Groq Llama 3.1' }}</span>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <strong class="text-dark">URL yang Dianalisis:</strong>
                                <div class="mt-2">
                                    @if(isset($analysisResult->urls) && is_array($analysisResult->urls))
                                        @foreach($analysisResult->urls as $index => $url)
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="badge bg-secondary text-white me-2">{{ $index + 1 }}</span>
                                                <small class="text-muted flex-grow-1">{{ Str::limit($url, 60) }}</small>
                                                <a href="{{ $url }}" target="_blank" class="btn btn-sm btn-link text-primary p-0 ms-2" aria-label="Buka URL {{ $index + 1 }}">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-muted small">Tidak ada URL yang tersedia.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Confidence Scores -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-warning text-dark p-2">
                            <h5 class="mb-0 d-flex align-items-center">
                                <i class="fas fa-chart-line me-2"></i>Confidence Scores
                            </h5>
                        </div>
                        <div class="card-body p-3">
                            @foreach(['resume' => 'Resume', 'judul' => 'Judul', 'narasi_positif' => 'Narasi Positif', 'narasi_negatif' => 'Narasi Negatif', 'tone' => 'Tone', 'skala' => 'Skala'] as $key => $label)
                                @php
                                    $score = $confidenceScores[$key] ?? 85;
                                    $colorClass = $score >= 90 ? 'success' : ($score >= 75 ? 'warning' : 'danger');
                                @endphp
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small fw-medium text-dark">{{ $label }}</span>
                                        <span class="badge bg-{{ $colorClass }} text-white">{{ $score }}%</span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-{{ $colorClass }}" role="progressbar" style="width: {{ $score }}%" aria-valuenow="{{ $score }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            @endforeach
                            <div class="alert alert-info p-2 mt-3 mb-0">
                                <i class="fas fa-lightbulb me-1"></i>
                                <strong class="small">Tips:</strong> Score di atas 85% menunjukkan hasil AI yang sangat baik. Anda tetap dapat mengedit hasil sebelum menyimpan.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Results Form -->
            <form id="ai-results-form" method="POST" action="{{ route('isu.ai.store') }}" class="needs-validation" novalidate>
                @csrf
                <input type="hidden" name="session_id" value="{{ $analysisResult->session_id }}">
                
                <div class="row g-4">
                    <!-- Left Column - Main Content -->
                    <div class="col-lg-8">
                        <!-- Resume Section -->
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center p-2">
                                <h5 class="mb-0 d-flex align-items-center">
                                    <i class="fas fa-file-alt text-primary me-2"></i>Resume Berita
                                    <span class="badge bg-{{ $confidenceScores['resume'] >= 85 ? 'success' : 'warning' }} text-white ms-2">
                                        {{ $confidenceScores['resume'] ?? 85 }}%
                                    </span>
                                </h5>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="regenerateSection('resume')" aria-label="Regenerate Resume">
                                    <i class="fas fa-sync-alt me-1"></i>Regenerate
                                </button>
                            </div>
                            <div class="card-body p-3">
                                <textarea name="rangkuman" class="form-control" rows="6" required>{{ $analysisResult->ai_resume ?? 'Resume berita akan muncul di sini...' }}</textarea>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle me-1"></i>Resume singkat dan objektif dari semua berita yang dianalisis
                                </small>
                                <div class="invalid-feedback">
                                    Resume wajib diisi.
                                </div>
                            </div>
                        </div>

                        <!-- Title Suggestions -->
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center p-2">
                                <h5 class="mb-0 d-flex align-items-center">
                                    <i class="fas fa-heading text-primary me-2"></i>Saran Judul Isu
                                    <span class="badge bg-{{ $confidenceScores['judul'] >= 85 ? 'success' : 'warning' }} text-white ms-2">
                                        {{ $confidenceScores['judul'] ?? 80 }}%
                                    </span>
                                </h5>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="regenerateSection('titles')" aria-label="Generate More Titles">
                                    <i class="fas fa-sync-alt me-1"></i>Generate More
                                </button>
                            </div>
                            <div class="card-body p-3">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Pilih atau Edit Judul:</label>
                                        <input type="text" name="judul" class="form-control" required 
                                               value="{{ is_array($analysisResult->ai_judul_suggestions ?? null) ? ($analysisResult->ai_judul_suggestions[0] ?? '') : 'Judul Isu akan muncul di sini...' }}">
                                        <div class="invalid-feedback">
                                            Judul wajib diisi.
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tanggal:</label>
                                        <input type="date" name="tanggal" class="form-control" required value="{{ date('Y-m-d') }}">
                                        <div class="invalid-feedback">
                                            Tanggal wajib diisi.
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="form-label">Alternatif Judul:</label>
                                    <div id="title-suggestions" class="mt-2">
                                        @if(isset($analysisResult->ai_judul_suggestions) && is_array($analysisResult->ai_judul_suggestions))
                                            @foreach($analysisResult->ai_judul_suggestions as $index => $title)
                                                <div class="suggestion-item mb-2">
                                                    <div class="d-flex align-items-center">
                                                        <button type="button" class="btn btn-sm btn-outline-primary me-2 use-suggestion" 
                                                                data-title="{{ $title }}" aria-label="Gunakan judul {{ $title }}">
                                                            <i class="fas fa-arrow-up me-1"></i>Gunakan
                                                        </button>
                                                        <span class="suggestion-text text-break">{{ $title }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <p class="text-muted small">Tidak ada saran judul tersedia.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Narratives Section -->
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <div class="card shadow-sm border-0 h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center p-2">
                                        <h6 class="mb-0 d-flex align-items-center">
                                            <i class="fas fa-thumbs-up text-success me-2"></i>Narasi Positif
                                            <span class="badge bg-{{ $confidenceScores['narasi_positif'] >= 85 ? 'success' : 'warning' }} text-white ms-2">
                                                {{ $confidenceScores['narasi_positif'] ?? 82 }}%
                                            </span>
                                        </h6>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="regenerateSection('narrative_positive')" aria-label="Regenerate Positive Narrative">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                    <div class="card-body p-3">
                                        <textarea name="narasi_positif" class="form-control" rows="8" required>{{ $analysisResult->ai_narasi_positif ?? 'Narasi positif akan muncul di sini...' }}</textarea>
                                        <small class="form-text text-muted">Fokus pada dampak positif dan peluang</small>
                                        <div class="invalid-feedback">
                                            Narasi positif wajib diisi.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card shadow-sm border-0 h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center p-2">
                                        <h6 class="mb-0 d-flex align-items-center">
                                            <i class="fas fa-thumbs-down text-danger me-2"></i>Narasi Negatif
                                            <span class="badge bg-{{ $confidenceScores['narasi_negatif'] >= 85 ? 'success' : 'warning' }} text-white ms-2">
                                                {{ $confidenceScores['narasi_negatif'] ?? 82 }}%
                                            </span>
                                        </h6>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="regenerateSection('narrative_negative')" aria-label="Regenerate Negative Narrative">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                    <div class="card-body p-3">
                                        <textarea name="narasi_negatif" class="form-control" rows="8" required>{{ $analysisResult->ai_narasi_negatif ?? 'Narasi negatif akan muncul di sini...' }}</textarea>
                                        <small class="form-text text-muted">Fokus pada risiko dan tantangan</small>
                                        <div class="invalid-feedback">
                                            Narasi negatif wajib diisi.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Metadata & Actions -->
                    <div class="col-lg-4">
                        <!-- Isu Settings -->
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header p-2">
                                <h6 class="mb-0 d-flex align-items-center">
                                    <i class="fas fa-cog text-primary me-2"></i>Pengaturan Isu
                                </h6>
                            </div>
                            <div class="card-body p-3">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="isu_strategis" id="isu_strategis" value="1">
                                        <label class="form-check-label fw-medium" for="isu_strategis">
                                            <i class="fas fa-star text-warning me-1"></i>Isu Strategis
                                        </label>
                                    </div>
                                    <small class="text-muted">Tandai jika ini adalah isu strategis penting</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-medium">Kategori</label>
                                    <select name="kategori" class="form-select" required>
                                        <option value="">Pilih Kategori...</option>
                                        <option value="politik">Politik</option>
                                        <option value="ekonomi">Ekonomi</option>
                                        <option value="sosial">Sosial</option>
                                        <option value="teknologi">Teknologi</option>
                                        <option value="lingkungan">Lingkungan</option>
                                        <option value="kesehatan">Kesehatan</option>
                                        <option value="pendidikan">Pendidikan</option>
                                        <option value="hukum">Hukum</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Kategori wajib diisi.
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-medium">Tone Berita</label>
                                    <select name="tone" class="form-select" required>
                                        <option value="positif" {{ ($analysisResult->ai_tone_suggestion ?? '') == 'positif' ? 'selected' : '' }}>
                                            <i class="fas fa-smile"></i> Positif
                                        </option>
                                        <option value="negatif" {{ ($analysisResult->ai_tone_suggestion ?? '') == 'negatif' ? 'selected' : '' }}>
                                            <i class="fas fa-frown"></i> Negatif
                                        </option>
                                        <option value="netral" {{ ($analysisResult->ai_tone_suggestion ?? '') == 'netral' ? 'selected' : '' }}>
                                            <i class="fas fa-meh"></i> Netral
                                        </option>
                                    </select>
                                    <small class="text-muted">
                                        <i class="fas fa-robot me-1"></i>
                                        AI menyarankan: <strong>{{ ucfirst($analysisResult->ai_tone_suggestion ?? 'netral') }}</strong>
                                        <span class="badge bg-{{ $confidenceScores['tone'] >= 90 ? 'success' : 'warning' }} text-white ms-1">
                                            {{ $confidenceScores['tone'] ?? 90 }}%
                                        </span>
                                    </small>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label fw-medium">Skala Dampak</label>
                                    <select name="skala" class="form-select" required>
                                        <option value="rendah" {{ ($analysisResult->ai_skala_suggestion ?? '') == 'rendah' ? 'selected' : '' }}>
                                            Rendah
                                        </option>
                                        <option value="sedang" {{ ($analysisResult->ai_skala_suggestion ?? '') == 'sedang' ? 'selected' : '' }}>
                                            Sedang
                                        </option>
                                        <option value="tinggi" {{ ($analysisResult->ai_skala_suggestion ?? '') == 'tinggi' ? 'selected' : '' }}>
                                            Tinggi
                                        </option>
                                    </select>
                                    <small class="text-muted">
                                        <i class="fas fa-robot me-1"></i>
                                        AI menyarankan: <strong>{{ ucfirst($analysisResult->ai_skala_suggestion ?? 'sedang') }}</strong>
                                        <span class="badge bg-{{ $confidenceScores['skala'] >= 85 ? 'success' : 'warning' }} text-white ms-1">
                                            {{ $confidenceScores['skala'] ?? 85 }}%
                                        </span>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Reference URLs -->
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header p-2">
                                <h6 class="mb-0 d-flex align-items-center">
                                    <i class="fas fa-link text-primary me-2"></i>URL Referensi
                                </h6>
                            </div>
                            <div class="card-body p-3">
                                <p class="small text-muted mb-3">URL yang dianalisis akan ditambahkan sebagai referensi:</p>
                                @if(isset($analysisResult->urls) && is_array($analysisResult->urls))
                                    @foreach($analysisResult->urls as $index => $url)
                                        <div class="mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="referensi_urls[]" 
                                                       value="{{ $url }}" id="ref_{{ $index }}" checked>
                                                <label class="form-check-label small text-break" for="ref_{{ $index }}">
                                                    {{ Str::limit($url, 40) }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-muted small">Tidak ada URL referensi tersedia.</p>
                                @endif
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="card shadow-sm border-0">
                            <div class="card-body p-3">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg w-100" id="submit-btn">
                                        <i class="fas fa-save me-2"></i>Simpan Isu
                                    </button>
                                    <!-- <button type="button" class="btn btn-outline-secondary w-100" onclick="saveDraft()">
                                        <i class="fas fa-file-alt me-2"></i>Simpan sebagai Draft
                                    </button>
                                    <button type="button" class="btn btn-outline-warning w-100" onclick="exportToManual()">
                                        <i class="fas fa-edit me-2"></i>Edit Manual
                                    </button> -->
                                    <a href="{{ route('isu.ai.create') }}" class="btn btn-outline-danger w-100 text-center">
                                        <i class="fas fa-times me-2"></i>Batal
                                    </a>
                                </div>
                                <div class="mt-3 text-center">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Isu akan disimpan sebagai draft dan perlu verifikasi
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Regeneration Modal -->
<div class="modal fade" id="regenerationModal" tabindex="-1" aria-labelledby="regenerationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header p-2">
                <h5 class="modal-title" id="regenerationModalLabel">
                    <i class="fas fa-sync-alt me-2"></i>Regenerate Konten
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 text-center">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Sedang menggenerate ulang konten...</p>
                <p class="small text-muted">Proses ini memerlukan beberapa detik</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.suggestion-item {
    transition: all 0.2s ease;
}

.suggestion-item:hover {
    background-color: #f8f9fa;
    border-radius: 5px;
    padding: 5px;
}

.card-header h5, .card-header h6 {
    display: flex;
    align-items: center;
}

.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

textarea.form-control {
    resize: vertical;
    min-height: 120px;
}

.badge {
    font-size: 0.75em;
}

.progress {
    border-radius: 10px;
}

.alert-info {
    border-left: 4px solid #0dcaf0;
}

.was-validated .form-control:invalid,
.was-validated .form-select:invalid {
    border-color: #dc3545;
    background-image: none;
}

.was-validated .form-control:valid,
.was-validated .form-select:valid {
    border-color: #198754;
    background-image: none;
}

@media (max-width: 768px) {
    .container-fluid {
        padding-left: 10px;
        padding-right: 10px;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .btn-lg {
        padding: 0.5rem 1rem;
        font-size: 1rem;
    }
    
    .row > .col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Use suggestion functionality
    document.querySelectorAll('.use-suggestion').forEach(button => {
        button.addEventListener('click', function() {
            const title = this.getAttribute('data-title');
            const judulInput = document.querySelector('input[name="judul"]');
            judulInput.value = title;
            judulInput.classList.add('is-valid');

            // Visual feedback
            this.innerHTML = '<i class="fas fa-check me-1"></i>Digunakan';
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-success');
            this.disabled = true;

            // Reset other buttons
            document.querySelectorAll('.use-suggestion').forEach(otherBtn => {
                if (otherBtn !== this && otherBtn.disabled) {
                    otherBtn.innerHTML = '<i class="fas fa-arrow-up me-1"></i>Gunakan';
                    otherBtn.classList.remove('btn-success');
                    otherBtn.classList.add('btn-outline-primary');
                    otherBtn.disabled = false;
                }
            });
        });
    });

    // Form validation
    const form = document.getElementById('ai-results-form');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            form.classList.add('was-validated');
            return;
        }

        // Show loading state
        const submitBtn = document.getElementById('submit-btn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
        submitBtn.disabled = true;

        // Submit form via AJAX
        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Isu berhasil disimpan!');
                window.location.href = data.redirect_url || '{{ route("isu.index") }}';
            } else {
                throw new Error(data.message || 'Gagal menyimpan isu');
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
});

// Global functions for button actions
function regenerateSection(section) {
    const modal = new bootstrap.Modal(document.getElementById('regenerationModal'));
    modal.show();

    // Simulate regeneration (replace with actual API call)
    setTimeout(() => {
        modal.hide();
        alert('Fitur regenerasi akan segera tersedia!');
    }, 3000);
}

function saveDraft() {
    const form = document.getElementById('ai-results-form');
    const draftInput = document.createElement('input');
    draftInput.type = 'hidden';
    draftInput.name = 'save_as_draft';
    draftInput.value = '1';
    form.appendChild(draftInput);
    form.submit();
}

function exportToManual() {
    const formData = new FormData(document.getElementById('ai-results-form'));
    const params = new URLSearchParams();

    for (const [key, value] of formData.entries()) {
        params.append(key, value);
    }

    window.location.href = '{{ route("isu.create") }}?' + params.toString();
}
</script>
@endpush
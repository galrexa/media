@extends('layouts.app')

@section('title', 'Hasil Analisis AI - Review & Edit')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Header Section -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="text-primary mb-1">
                                <i class="fas fa-check-circle me-2"></i>Hasil Analisis AI
                            </h2>
                            <p class="text-muted mb-0">Review dan edit hasil analisis sebelum menyimpan isu</p>
                        </div>
                        <div>
                            <span class="badge bg-success px-3 py-2">
                                <i class="fas fa-robot me-1"></i>AI Analysis Complete
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analysis Summary -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Ringkasan Analisis
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <strong>Session ID:</strong>
                                    <span class="text-muted">{{ $analysisResult->session_id ?? 'AI-' . date('Ymd-His') }}</span>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Waktu Proses:</strong>
                                    <span class="text-muted">{{ $analysisResult->processing_time ?? '2' }} menit</span>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>URL Dianalisis:</strong>
                                    <span class="badge bg-primary">{{ count($analysisResult->urls ?? []) }} URL</span>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>AI Provider:</strong>
                                    <span class="text-muted">{{ $analysisResult->ai_provider ?? 'OpenAI GPT-4' }}</span>
                                </div>
                            </div>
                            
                            <!-- Source URLs -->
                            <div class="mt-3">
                                <strong>Sumber URL:</strong>
                                <div class="mt-2">
                                    @foreach($analysisResult->urls ?? [] as $index => $url)
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge bg-secondary me-2">{{ $index + 1 }}</span>
                                        <a href="{{ $url }}" target="_blank" class="text-truncate" style="max-width: 500px;">
                                            {{ $url }}
                                        </a>
                                        <i class="fas fa-external-link-alt ms-2 text-muted"></i>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0">
                                <i class="fas fa-chart-line me-2"></i>Confidence Scores
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="confidence-item mb-2">
                                <div class="d-flex justify-content-between">
                                    <small>Resume Berita</small>
                                    <strong>{{ $confidenceScores['resume'] ?? '92' }}%</strong>
                                </div>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar bg-success" style="width: {{ $confidenceScores['resume'] ?? '92' }}%"></div>
                                </div>
                            </div>
                            <div class="confidence-item mb-2">
                                <div class="d-flex justify-content-between">
                                    <small>Saran Judul</small>
                                    <strong>{{ $confidenceScores['judul'] ?? '88' }}%</strong>
                                </div>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar bg-success" style="width: {{ $confidenceScores['judul'] ?? '88' }}%"></div>
                                </div>
                            </div>
                            <div class="confidence-item mb-2">
                                <div class="d-flex justify-content-between">
                                    <small>Narasi Positif</small>
                                    <strong>{{ $confidenceScores['narasi_positif'] ?? '85' }}%</strong>
                                </div>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar bg-success" style="width: {{ $confidenceScores['narasi_positif'] ?? '85' }}%"></div>
                                </div>
                            </div>
                            <div class="confidence-item mb-2">
                                <div class="d-flex justify-content-between">
                                    <small>Narasi Negatif</small>
                                    <strong>{{ $confidenceScores['narasi_negatif'] ?? '87' }}%</strong>
                                </div>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar bg-success" style="width: {{ $confidenceScores['narasi_negatif'] ?? '87' }}%"></div>
                                </div>
                            </div>
                            <div class="confidence-item mb-2">
                                <div class="d-flex justify-content-between">
                                    <small>Tone Analysis</small>
                                    <strong>{{ $confidenceScores['tone'] ?? '94' }}%</strong>
                                </div>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar bg-success" style="width: {{ $confidenceScores['tone'] ?? '94' }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Results Form -->
            <form id="ai-results-form" method="POST" action="{{ route('isu.ai.store') }}">
                @csrf
                <input type="hidden" name="session_id" value="{{ $analysisResult->session_id ?? '' }}">
                <input type="hidden" name="urls" value="{{ json_encode($analysisResult->urls ?? []) }}">
                
                <!-- Tabbed Interface for Results -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom-0">
                        <ul class="nav nav-tabs card-header-tabs" id="results-tabs">
                            <li class="nav-item">
                                <a class="nav-link active" id="resume-tab" data-bs-toggle="tab" href="#resume-content">
                                    <i class="fas fa-file-text me-2"></i>Resume Berita
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="judul-tab" data-bs-toggle="tab" href="#judul-content">
                                    <i class="fas fa-heading me-2"></i>Saran Judul
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="narasi-tab" data-bs-toggle="tab" href="#narasi-content">
                                    <i class="fas fa-align-left me-2"></i>Narasi
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="classification-tab" data-bs-toggle="tab" href="#classification-content">
                                    <i class="fas fa-tags me-2"></i>Klasifikasi
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="card-body">
                        <div class="tab-content" id="results-tab-content">
                            <!-- Resume Tab -->
                            <div class="tab-pane fade show active" id="resume-content">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="text-primary mb-3">
                                            <i class="fas fa-robot me-2"></i>Hasil AI
                                            <span class="confidence-badge badge bg-success ms-2">{{ $confidenceScores['resume'] ?? '92' }}%</span>
                                        </h5>
                                        <div class="ai-result-preview border rounded p-3 bg-light">
                                            {{ $analysisResult->ai_resume ?? 'Pemerintah Indonesia mengumumkan kebijakan baru terkait pembangunan infrastruktur digital nasional yang akan dimulai pada tahun 2025. Kebijakan ini meliputi pembangunan jaringan 5G di seluruh Indonesia, digitalisasi layanan publik, dan pengembangan ekosistem startup teknologi. Minister Komunikasi dan Informatika menyatakan bahwa program ini akan meningkatkan daya saing Indonesia di era digital dan menciptakan jutaan lapangan kerja baru di bidang teknologi informasi.' }}
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Word count: <span id="ai-resume-count">{{ str_word_count($analysisResult->ai_resume ?? 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Quod, voluptatum.') }}</span> kata
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="text-success mb-0">
                                                <i class="fas fa-edit me-2"></i>Edit & Review
                                            </h5>
                                            <div>
                                                <button type="button" class="btn btn-sm btn-outline-primary" id="copy-ai-resume">
                                                    <i class="fas fa-copy me-1"></i>Copy AI Result
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" id="reset-ai-resume">
                                                    <i class="fas fa-undo me-1"></i>Reset
                                                </button>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <textarea class="form-control editable-content" 
                                                      name="resume" 
                                                      id="resume-edit" 
                                                      rows="8" 
                                                      data-original="{{ $analysisResult->ai_resume ?? 'Default resume content here...' }}"
                                                      placeholder="Edit resume berita disini...">{{ $analysisResult->ai_resume ?? 'Pemerintah Indonesia mengumumkan kebijakan baru terkait pembangunan infrastruktur digital nasional yang akan dimulai pada tahun 2025. Kebijakan ini meliputi pembangunan jaringan 5G di seluruh Indonesia, digitalisasi layanan publik, dan pengembangan ekosistem startup teknologi. Minister Komunikasi dan Informatika menyatakan bahwa program ini akan meningkatkan daya saing Indonesia di era digital dan menciptakan jutaan lapangan kerja baru di bidang teknologi informasi.' }}</textarea>
                                            <small class="text-muted">
                                                Word count: <span id="edit-resume-count">{{ str_word_count($analysisResult->ai_resume ?? 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Quod, voluptatum.') }}</span> kata
                                                | Target: 200-300 kata
                                            </small>
                                        </div>
                                        <div class="acceptance-controls mt-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="accept-resume" name="accept_resume" checked>
                                                <label class="form-check-label" for="accept-resume">
                                                    <i class="fas fa-check text-success me-1"></i>Terima dan gunakan hasil ini
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Judul Tab -->
                            <div class="tab-pane fade" id="judul-content">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="text-primary mb-3">
                                            <i class="fas fa-robot me-2"></i>Saran Judul AI
                                            <span class="confidence-badge badge bg-success ms-2">{{ $confidenceScores['judul'] ?? '88' }}%</span>
                                        </h5>
                                        <div class="ai-suggestions">
                                            @foreach($analysisResult->ai_judul_suggestions ?? [
                                                'Pemerintah Luncurkan Program Infrastruktur Digital Nasional 2025',
                                                'Indonesia Siap Bangun Ekosistem Digital dengan Jaringan 5G Nasional',
                                                'Kebijakan Baru: Digitalisasi Layanan Publik dan Pengembangan Startup Tech',
                                                'Jutaan Lapangan Kerja Teknologi Tercipta dari Program Digital Indonesia',
                                                'Era Baru Indonesia Digital: 5G dan Startup Technology Ecosystem'
                                            ] as $index => $suggestion)
                                            <div class="suggestion-item border rounded p-3 mb-2 {{ $index === 0 ? 'border-primary bg-primary bg-opacity-10' : '' }}" 
                                                 data-suggestion="{{ $suggestion }}">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="flex-grow-1">
                                                        <span class="badge bg-secondary me-2">{{ $index + 1 }}</span>
                                                        <span class="suggestion-text">{{ $suggestion }}</span>
                                                    </div>
                                                    <div>
                                                        <button type="button" class="btn btn-sm btn-outline-primary select-suggestion" 
                                                                data-target="judul-edit" data-text="{{ $suggestion }}">
                                                            <i class="fas fa-arrow-right"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="text-success mb-0">
                                                <i class="fas fa-edit me-2"></i>Judul Final
                                            </h5>
                                            <div>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" id="generate-more-titles">
                                                    <i class="fas fa-sync me-1"></i>Generate More
                                                </button>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <input type="text" 
                                                   class="form-control form-control-lg editable-content" 
                                                   name="judul" 
                                                   id="judul-edit" 
                                                   value="{{ $analysisResult->ai_judul_suggestions[0] ?? 'Pemerintah Luncurkan Program Infrastruktur Digital Nasional 2025' }}"
                                                   placeholder="Edit judul isu disini...">
                                            <small class="text-muted">
                                                Character count: <span id="judul-char-count">{{ strlen($analysisResult->ai_judul_suggestions[0] ?? 'Pemerintah Luncurkan Program Infrastruktur Digital Nasional 2025') }}</span>
                                                | Recommended: 60-100 karakter
                                            </small>
                                        </div>
                                        <div class="acceptance-controls mt-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="accept-judul" name="accept_judul" checked>
                                                <label class="form-check-label" for="accept-judul">
                                                    <i class="fas fa-check text-success me-1"></i>Terima dan gunakan judul ini
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Narasi Tab -->
                            <div class="tab-pane fade" id="narasi-content">
                                <div class="row">
                                    <!-- Narasi Positif -->
                                    <div class="col-md-6 mb-4">
                                        <div class="border rounded p-3 h-100">
                                            <h6 class="text-success mb-3">
                                                <i class="fas fa-thumbs-up me-2"></i>Narasi Positif
                                                <span class="confidence-badge badge bg-success ms-2">{{ $confidenceScores['narasi_positif'] ?? '85' }}%</span>
                                            </h6>
                                            
                                            <!-- AI Result -->
                                            <div class="ai-result-preview border rounded p-3 bg-light mb-3">
                                                <small class="text-muted d-block mb-2">
                                                    <i class="fas fa-robot me-1"></i>Hasil AI:
                                                </small>
                                                {{ $analysisResult->ai_narasi_positif ?? 'Program infrastruktur digital nasional ini menunjukkan komitmen serius pemerintah dalam memajukan Indonesia di era digital. Investasi dalam jaringan 5G dan digitalisasi layanan publik akan meningkatkan aksesibilitas teknologi untuk seluruh rakyat Indonesia. Pengembangan ekosistem startup teknologi juga akan menciptakan peluang inovasi dan lapangan kerja baru, terutama untuk generasi muda.' }}
                                            </div>
                                            
                                            <!-- Edit Area -->
                                            <div class="form-group">
                                                <label class="form-label">Edit Narasi Positif:</label>
                                                <textarea class="form-control editable-content" 
                                                          name="narasi_positif" 
                                                          id="narasi-positif-edit" 
                                                          rows="6"
                                                          data-original="{{ $analysisResult->ai_narasi_positif ?? 'Default positive narrative...' }}"
                                                          placeholder="Edit narasi positif...">{{ $analysisResult->ai_narasi_positif ?? 'Program infrastruktur digital nasional ini menunjukkan komitmen serius pemerintah dalam memajukan Indonesia di era digital. Investasi dalam jaringan 5G dan digitalisasi layanan publik akan meningkatkan aksesibilitas teknologi untuk seluruh rakyat Indonesia. Pengembangan ekosistem startup teknologi juga akan menciptakan peluang inovasi dan lapangan kerja baru, terutama untuk generasi muda.' }}</textarea>
                                                <small class="text-muted">
                                                    Word count: <span id="narasi-positif-count">{{ str_word_count($analysisResult->ai_narasi_positif ?? 'Lorem ipsum dolor sit amet consectetur.') }}</span> kata | Target: 150-200 kata
                                                </small>
                                            </div>
                                            
                                            <div class="acceptance-controls mt-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="accept-narasi-positif" name="accept_narasi_positif" checked>
                                                    <label class="form-check-label" for="accept-narasi-positif">
                                                        <i class="fas fa-check text-success me-1"></i>Terima narasi positif
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Narasi Negatif -->
                                    <div class="col-md-6 mb-4">
                                        <div class="border rounded p-3 h-100">
                                            <h6 class="text-danger mb-3">
                                                <i class="fas fa-thumbs-down me-2"></i>Narasi Negatif
                                                <span class="confidence-badge badge bg-success ms-2">{{ $confidenceScores['narasi_negatif'] ?? '87' }}%</span>
                                            </h6>
                                            
                                            <!-- AI Result -->
                                            <div class="ai-result-preview border rounded p-3 bg-light mb-3">
                                                <small class="text-muted d-block mb-2">
                                                    <i class="fas fa-robot me-1"></i>Hasil AI:
                                                </small>
                                                {{ $analysisResult->ai_narasi_negatif ?? 'Meskipun program ini terdengar ambisius, masih ada keraguan tentang kemampuan implementasi dan anggaran yang dibutuhkan. Infrastruktur digital memerlukan investasi besar yang mungkin membebani APBN. Selain itu, masih ada kesenjangan digital yang besar antara daerah urban dan rural yang perlu diatasi terlebih dahulu sebelum meluncurkan program skala nasional.' }}
                                            </div>
                                            
                                            <!-- Edit Area -->
                                            <div class="form-group">
                                                <label class="form-label">Edit Narasi Negatif:</label>
                                                <textarea class="form-control editable-content" 
                                                          name="narasi_negatif" 
                                                          id="narasi-negatif-edit" 
                                                          rows="6"
                                                          data-original="{{ $analysisResult->ai_narasi_negatif ?? 'Default negative narrative...' }}"
                                                          placeholder="Edit narasi negatif...">{{ $analysisResult->ai_narasi_negatif ?? 'Meskipun program ini terdengar ambisius, masih ada keraguan tentang kemampuan implementasi dan anggaran yang dibutuhkan. Infrastruktur digital memerlukan investasi besar yang mungkin membebani APBN. Selain itu, masih ada kesenjangan digital yang besar antara daerah urban dan rural yang perlu diatasi terlebih dahulu sebelum meluncurkan program skala nasional.' }}</textarea>
                                                <small class="text-muted">
                                                    Word count: <span id="narasi-negatif-count">{{ str_word_count($analysisResult->ai_narasi_negatif ?? 'Lorem ipsum dolor sit amet consectetur.') }}</span> kata | Target: 150-200 kata
                                                </small>
                                            </div>
                                            
                                            <div class="acceptance-controls mt-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="accept-narasi-negatif" name="accept_narasi_negatif" checked>
                                                    <label class="form-check-label" for="accept-narasi-negatif">
                                                        <i class="fas fa-check text-success me-1"></i>Terima narasi negatif
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Classification Tab -->
                            <div class="tab-pane fade" id="classification-content">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="text-primary mb-3">
                                            <i class="fas fa-robot me-2"></i>Rekomendasi AI
                                        </h5>
                                        
                                        <div class="classification-item border rounded p-3 mb-3">
                                            <h6 class="text-info mb-2">
                                                <i class="fas fa-palette me-2"></i>Tone Berita
                                                <span class="confidence-badge badge bg-success ms-2">{{ $confidenceScores['tone'] ?? '94' }}%</span>
                                            </h6>
                                            <div class="ai-recommendation">
                                                <span class="badge bg-{{ $analysisResult->ai_tone_suggestion === 'positif' ? 'success' : ($analysisResult->ai_tone_suggestion === 'negatif' ? 'danger' : 'secondary') }} px-3 py-2">
                                                    {{ ucfirst($analysisResult->ai_tone_suggestion ?? 'positif') }}
                                                </span>
                                            </div>
                                            <small class="text-muted d-block mt-2">
                                                AI menganalisis tone berdasarkan sentimen keseluruhan dari konten berita
                                            </small>
                                        </div>
                                        
                                        <div class="classification-item border rounded p-3">
                                            <h6 class="text-warning mb-2">
                                                <i class="fas fa-chart-bar me-2"></i>Skala Isu
                                                <span class="confidence-badge badge bg-success ms-2">{{ $confidenceScores['skala'] ?? '89' }}%</span>
                                            </h6>
                                            <div class="ai-recommendation">
                                                <span class="badge bg-{{ $analysisResult->ai_skala_suggestion === 'tinggi' ? 'danger' : ($analysisResult->ai_skala_suggestion === 'sedang' ? 'warning' : 'success') }} px-3 py-2">
                                                    {{ ucfirst($analysisResult->ai_skala_suggestion ?? 'sedang') }}
                                                </span>
                                            </div>
                                            <small class="text-muted d-block mt-2">
                                                Skala ditentukan berdasarkan dampak potensial dan cakupan isu
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h5 class="text-success mb-3">
                                            <i class="fas fa-edit me-2"></i>Final Classification
                                        </h5>
                                        
                                        <div class="form-group mb-4">
                                            <label class="form-label fw-bold">Tone Berita <span class="text-danger">*</span></label>
                                            <select class="form-select" name="tone" id="tone-select" required>
                                                <option value="positif" {{ ($analysisResult->ai_tone_suggestion ?? 'positif') === 'positif' ? 'selected' : '' }}>Positif</option>
                                                <option value="negatif" {{ ($analysisResult->ai_tone_suggestion ?? 'positif') === 'negatif' ? 'selected' : '' }}>Negatif</option>
                                                <option value="netral" {{ ($analysisResult->ai_tone_suggestion ?? 'positif') === 'netral' ? 'selected' : '' }}>Netral</option>
                                            </select>
                                            <small class="text-muted">
                                                <i class="fas fa-lightbulb me-1"></i>AI merekomendasikan: 
                                                <strong>{{ ucfirst($analysisResult->ai_tone_suggestion ?? 'positif') }}</strong>
                                            </small>
                                        </div>
                                        
                                        <div class="form-group mb-4">
                                            <label class="form-label fw-bold">Skala Isu <span class="text-danger">*</span></label>
                                            <select class="form-select" name="skala" id="skala-select" required>
                                                <option value="rendah" {{ ($analysisResult->ai_skala_suggestion ?? 'sedang') === 'rendah' ? 'selected' : '' }}>Rendah</option>
                                                <option value="sedang" {{ ($analysisResult->ai_skala_suggestion ?? 'sedang') === 'sedang' ? 'selected' : '' }}>Sedang</option>
                                                <option value="tinggi" {{ ($analysisResult->ai_skala_suggestion ?? 'sedang') === 'tinggi' ? 'selected' : '' }}>Tinggi</option>
                                            </select>
                                            <small class="text-muted">
                                                <i class="fas fa-lightbulb me-1"></i>AI merekomendasikan: 
                                                <strong>{{ ucfirst($analysisResult->ai_skala_suggestion ?? 'sedang') }}</strong>
                                            </small>
                                        </div>
                                        
                                        <div class="acceptance-controls">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="accept-tone" name="accept_tone" checked>
                                                <label class="form-check-label" for="accept-tone">
                                                    <i class="fas fa-check text-success me-1"></i>Terima rekomendasi tone
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="accept-skala" name="accept_skala" checked>
                                                <label class="form-check-label" for="accept-skala">
                                                    <i class="fas fa-check text-success me-1"></i>Terima rekomendasi skala
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons Section -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Siap untuk menyimpan?</h6>
                                        <small class="text-muted">Review semua hasil dan pilih tindakan yang diinginkan</small>
                                    </div>
                                    <div class="action-buttons">
                                        <button type="button" class="btn btn-outline-secondary me-2" onclick="history.back()">
                                            <i class="fas fa-arrow-left me-1"></i>Kembali
                                        </button>
                                        <button type="button" class="btn btn-outline-info me-2" id="preview-final-result">
                                            <i class="fas fa-eye me-1"></i>Preview
                                        </button>
                                        <button type="button" class="btn btn-warning me-2" id="save-as-draft">
                                            <i class="fas fa-save me-1"></i>Simpan Draft
                                        </button>
                                        <button type="submit" class="btn btn-success" id="create-isu-final">
                                            <i class="fas fa-check me-1"></i>Buat Isu
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Additional Options -->
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="send-for-verification" name="send_for_verification" checked>
                                            <label class="form-check-label" for="send-for-verification">
                                                Kirim untuk verifikasi setelah dibuat
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="export-to-manual" name="export_to_manual">
                                            <label class="form-check-label" for="export-to-manual">
                                                Export ke form manual untuk editing lanjutan
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="finalPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-eye me-2"></i>Preview Isu Final
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div id="final-preview-content">
                    <!-- Preview content akan dimuat disini -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="confirm-create-isu">
                    <i class="fas fa-check me-1"></i>Konfirmasi & Buat Isu
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Regenerate Content Modal -->
<div class="modal fade" id="regenerateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-sync me-2"></i>Regenerate Content
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Pilih konten yang ingin di-generate ulang:</p>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="regen-resume" value="resume">
                    <label class="form-check-label" for="regen-resume">Resume Berita</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="regen-judul" value="judul">
                    <label class="form-check-label" for="regen-judul">Saran Judul</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="regen-narasi" value="narasi">
                    <label class="form-check-label" for="regen-narasi">Narasi Positif & Negatif</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="regen-classification" value="classification">
                    <label class="form-check-label" for="regen-classification">Klasifikasi (Tone & Skala)</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="start-regenerate">
                    <i class="fas fa-sync me-1"></i>Regenerate
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// AI Results Manager
class AIResultsManager {
    constructor() {
        this.originalData = this.captureOriginalData();
        this.initializeEventListeners();
        this.initializeWordCounters();
        this.initializeCharCounters();
    }
    
    captureOriginalData() {
        const data = {};
        document.querySelectorAll('.editable-content').forEach(element => {
            data[element.id] = element.dataset.original || element.value;
        });
        return data;
    }
    
    initializeEventListeners() {
        // Copy AI result buttons
        document.querySelectorAll('[id^="copy-ai-"]').forEach(button => {
            button.addEventListener('click', (e) => {
                this.copyAIResult(e.target);
            });
        });
        
        // Reset buttons
        document.querySelectorAll('[id^="reset-ai-"]').forEach(button => {
            button.addEventListener('click', (e) => {
                this.resetToOriginal(e.target);
            });
        });
        
        // Select suggestion buttons
        document.querySelectorAll('.select-suggestion').forEach(button => {
            button.addEventListener('click', (e) => {
                this.selectSuggestion(e.target);
            });
        });
        
        // Tab change events
        document.querySelectorAll('#results-tabs a').forEach(tab => {
            tab.addEventListener('shown.bs.tab', () => {
                this.onTabChange();
            });
        });
        
        // Form submission
        document.getElementById('ai-results-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleFormSubmission();
        });
        
        // Preview button
        document.getElementById('preview-final-result').addEventListener('click', () => {
            this.showFinalPreview();
        });
        
        // Save as draft
        document.getElementById('save-as-draft').addEventListener('click', () => {
            this.saveAsDraft();
        });
        
        // Generate more titles
        document.getElementById('generate-more-titles').addEventListener('click', () => {
            this.generateMoreTitles();
        });
        
        // Acceptance checkboxes
        document.querySelectorAll('[id^="accept-"]').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                this.handleAcceptanceChange(e.target);
            });
        });
        
        // AI recommendation buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('apply-ai-recommendation')) {
                this.applyAIRecommendation(e.target);
            }
        });
    }
    
    initializeWordCounters() {
        const textareas = ['resume-edit', 'narasi-positif-edit', 'narasi-negatif-edit'];
        
        textareas.forEach(id => {
            const textarea = document.getElementById(id);
            const countSpan = document.getElementById(id.replace('-edit', '-count'));
            
            if (textarea && countSpan) {
                textarea.addEventListener('input', () => {
                    const wordCount = this.countWords(textarea.value);
                    countSpan.textContent = wordCount;
                    this.updateWordCountStatus(countSpan, wordCount, id);
                });
            }
        });
    }
    
    initializeCharCounters() {
        const judulInput = document.getElementById('judul-edit');
        const charCountSpan = document.getElementById('judul-char-count');
        
        if (judulInput && charCountSpan) {
            judulInput.addEventListener('input', () => {
                const charCount = judulInput.value.length;
                charCountSpan.textContent = charCount;
                this.updateCharCountStatus(charCountSpan, charCount);
            });
        }
    }
    
    countWords(text) {
        return text.trim().split(/\s+/).filter(word => word.length > 0).length;
    }
    
    updateWordCountStatus(element, count, fieldId) {
        let target = 250; // default
        if (fieldId.includes('resume')) target = 250;
        if (fieldId.includes('narasi')) target = 175;
        
        element.parentElement.className = element.parentElement.className.replace(/text-\w+/, '');
        
        if (count < target * 0.8) {
            element.parentElement.classList.add('text-warning');
        } else if (count > target * 1.2) {
            element.parentElement.classList.add('text-danger');
        } else {
            element.parentElement.classList.add('text-success');
        }
    }
    
    updateCharCountStatus(element, count) {
        element.parentElement.className = element.parentElement.className.replace(/text-\w+/, '');
        
        if (count < 60) {
            element.parentElement.classList.add('text-warning');
        } else if (count > 100) {
            element.parentElement.classList.add('text-danger');
        } else {
            element.parentElement.classList.add('text-success');
        }
    }
    
    copyAIResult(button) {
        const resultId = button.id.replace('copy-ai-', '');
        const targetInput = document.getElementById(resultId + '-edit');
        const aiResultDiv = button.closest('.col-md-6').querySelector('.ai-result-preview');
        
        if (targetInput && aiResultDiv) {
            const aiText = aiResultDiv.textContent.trim();
            targetInput.value = aiText;
            
            // Trigger input event untuk update counters
            targetInput.dispatchEvent(new Event('input'));
            
            this.showToast('success', 'Hasil AI berhasil disalin');
        }
    }
    
    resetToOriginal(button) {
        const resultId = button.id.replace('reset-ai-', '');
        const targetInput = document.getElementById(resultId + '-edit');
        
        if (targetInput && this.originalData[targetInput.id]) {
            targetInput.value = this.originalData[targetInput.id];
            targetInput.dispatchEvent(new Event('input'));
            
            this.showToast('info', 'Konten direset ke hasil AI original');
        }
    }
    
    selectSuggestion(button) {
        const targetId = button.dataset.target;
        const suggestionText = button.dataset.text;
        const targetInput = document.getElementById(targetId);
        
        if (targetInput) {
            targetInput.value = suggestionText;
            targetInput.dispatchEvent(new Event('input'));
            
            // Highlight selected suggestion
            button.closest('.ai-suggestions').querySelectorAll('.suggestion-item').forEach(item => {
                item.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
            });
            button.closest('.suggestion-item').classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
            
            this.showToast('success', 'Saran judul dipilih');
        }
    }
    
    handleAcceptanceChange(checkbox) {
        const fieldName = checkbox.name.replace('accept_', '');
        const relatedElements = document.querySelectorAll(`[id*="${fieldName}"]`);
        
        relatedElements.forEach(element => {
            if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA' || element.tagName === 'SELECT') {
                element.disabled = !checkbox.checked;
                if (!checkbox.checked) {
                    element.style.opacity = '0.5';
                } else {
                    element.style.opacity = '1';
                }
            }
        });
    }
    
    async generateMoreTitles() {
        const button = document.getElementById('generate-more-titles');
        const originalText = button.innerHTML;
        
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Generating...';
        button.disabled = true;
        
        try {
            const response = await fetch('/api/ai-generate-titles', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    session_id: document.querySelector('[name="session_id"]').value
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.updateTitleSuggestions(result.suggestions);
                this.showToast('success', 'Saran judul baru berhasil di-generate');
            } else {
                throw new Error(result.message || 'Failed to generate titles');
            }
        } catch (error) {
            this.showToast('danger', 'Gagal generate judul baru: ' + error.message);
        } finally {
            button.innerHTML = originalText;
            button.disabled = false;
        }
    }
    
    updateTitleSuggestions(newSuggestions) {
        const suggestionsContainer = document.querySelector('.ai-suggestions');
        
        // Clear existing suggestions
        suggestionsContainer.innerHTML = '';
        
        // Add new suggestions
        newSuggestions.forEach((suggestion, index) => {
            const suggestionHtml = `
                <div class="suggestion-item border rounded p-3 mb-2 ${index === 0 ? 'border-primary bg-primary bg-opacity-10' : ''}" 
                     data-suggestion="${suggestion}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <span class="badge bg-secondary me-2">${index + 1}</span>
                            <span class="suggestion-text">${suggestion}</span>
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-primary select-suggestion" 
                                    data-target="judul-edit" data-text="${suggestion}">
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            suggestionsContainer.insertAdjacentHTML('beforeend', suggestionHtml);
        });
        
        // Re-bind event listeners
        suggestionsContainer.querySelectorAll('.select-suggestion').forEach(button => {
            button.addEventListener('click', (e) => {
                this.selectSuggestion(e.target);
            });
        });
    }
    
    showFinalPreview() {
        const modal = new bootstrap.Modal(document.getElementById('finalPreviewModal'));
        const container = document.getElementById('final-preview-content');
        
        // Collect all form data
        const formData = new FormData(document.getElementById('ai-results-form'));
        const previewData = {};
        
        for (let [key, value] of formData.entries()) {
            previewData[key] = value;
        }
        
        // Generate preview HTML
        const previewHtml = `
            <div class="preview-section mb-4">
                <h5 class="text-primary border-bottom pb-2">Judul Isu</h5>
                <p class="fs-5">${previewData.judul || '-'}</p>
            </div>
            
            <div class="preview-section mb-4">
                <h5 class="text-primary border-bottom pb-2">Resume Berita</h5>
                <p style="text-align: justify;">${(previewData.resume || '-').replace(/\n/g, '<br>')}</p>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="preview-section">
                        <h5 class="text-success border-bottom pb-2">Narasi Positif</h5>
                        <p style="text-align: justify;">${(previewData.narasi_positif || '-').replace(/\n/g, '<br>')}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="preview-section">
                        <h5 class="text-danger border-bottom pb-2">Narasi Negatif</h5>
                        <p style="text-align: justify;">${(previewData.narasi_negatif || '-').replace(/\n/g, '<br>')}</p>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="preview-section">
                        <h5 class="text-info border-bottom pb-2">Tone Berita</h5>
                        <span class="badge bg-${previewData.tone === 'positif' ? 'success' : previewData.tone === 'negatif' ? 'danger' : 'secondary'} px-3 py-2">
                            ${previewData.tone ? previewData.tone.charAt(0).toUpperCase() + previewData.tone.slice(1) : '-'}
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="preview-section">
                        <h5 class="text-warning border-bottom pb-2">Skala Isu</h5>
                        <span class="badge bg-${previewData.skala === 'tinggi' ? 'danger' : previewData.skala === 'sedang' ? 'warning' : 'success'} px-3 py-2">
                            ${previewData.skala ? previewData.skala.charAt(0).toUpperCase() + previewData.skala.slice(1) : '-'}
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="preview-section">
                <h5 class="text-muted border-bottom pb-2">Sumber URL</h5>
                <ol>
                    ${JSON.parse(previewData.urls || '[]').map(url => `<li><a href="${url}" target="_blank">${url}</a></li>`).join('')}
                </ol>
            </div>
        `;
        
        container.innerHTML = previewHtml;
        modal.show();
    }
    
    async saveAsDraft() {
        const button = document.getElementById('save-as-draft');
        const originalText = button.innerHTML;
        
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Menyimpan...';
        button.disabled = true;
        
        try {
            const formData = new FormData(document.getElementById('ai-results-form'));
            formData.append('save_as_draft', '1');
            
            const response = await fetch(document.getElementById('ai-results-form').action, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showToast('success', 'Draft berhasil disimpan');
            } else {
                throw new Error(result.message || 'Failed to save draft');
            }
        } catch (error) {
            this.showToast('danger', 'Gagal menyimpan draft: ' + error.message);
        } finally {
            button.innerHTML = originalText;
            button.disabled = false;
        }
    }
    
    async handleFormSubmission() {
        const button = document.getElementById('create-isu-final');
        const originalText = button.innerHTML;
        
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Membuat Isu...';
        button.disabled = true;
        
        try {
            const formData = new FormData(document.getElementById('ai-results-form'));
            
            const response = await fetch(document.getElementById('ai-results-form').action, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showToast('success', 'Isu berhasil dibuat!');
                
                // Redirect after 1.5 seconds
                setTimeout(() => {
                    if (result.redirect_url) {
                        window.location.href = result.redirect_url;
                    } else {
                        window.location.href = '/isu';
                    }
                }, 1500);
            } else {
                throw new Error(result.message || 'Failed to create isu');
            }
        } catch (error) {
            this.showToast('danger', 'Gagal membuat isu: ' + error.message);
            button.innerHTML = originalText;
            button.disabled = false;
        }
    }
    
    onTabChange() {
        // Handle any tab-specific logic here
        console.log('Tab changed');
    }
    
    showToast(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3" 
                 style="z-index: 9999; min-width: 300px;" role="alert">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', alertHtml);
        
        // Auto dismiss after 4 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            if (alerts.length > 0) {
                const bsAlert = new bootstrap.Alert(alerts[alerts.length - 1]);
                bsAlert.close();
            }
        }, 4000);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.aiResultsManager = new AIResultsManager();
});

// Handle confirm create isu from modal
document.getElementById('confirm-create-isu').addEventListener('click', () => {
    const modal = bootstrap.Modal.getInstance(document.getElementById('finalPreviewModal'));
    modal.hide();
    window.aiResultsManager.handleFormSubmission();
});
</script>

<style>
/* Enhanced styling for results interface */
.confidence-badge {
    font-size: 0.75rem;
}

.progress {
    background-color: #e9ecef;
}

.confidence-item {
    padding: 0.5rem 0;
}

.ai-result-preview {
    font-size: 0.9rem;
    line-height: 1.5;
    max-height: 200px;
    overflow-y: auto;
}

.suggestion-item {
    cursor: pointer;
    transition: all 0.3s ease;
}

.suggestion-item:hover {
    background-color: #f8f9fa !important;
}

.suggestion-item.border-primary {
    animation: selectedPulse 0.5s ease-in-out;
}

@keyframes selectedPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}

.editable-content {
    transition: all 0.3s ease;
}

.editable-content:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.editable-content:disabled {
    background-color: #f8f9fa;
    opacity: 0.5 !important;
}

.acceptance-controls {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
}

.classification-item {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.ai-recommendation .badge {
    font-size: 0.9rem;
}

.nav-tabs .nav-link {
    color: #6c757d;
    border: none;
    border-bottom: 3px solid transparent;
}

.nav-tabs .nav-link.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd;
    background-color: transparent;
}

.tab-content {
    padding-top: 1.5rem;
}

.action-buttons .btn {
    margin-left: 0.5rem;
}

/* Word count status colors */
.text-success {
    color: #198754 !important;
}

.text-warning {
    color: #ffc107 !important;
}

.text-danger {
    color: #dc3545 !important;
}

/* Progress bars in confidence scores */
.progress-bar {
    transition: width 0.5s ease;
}

/* Modal enhancements */
.modal-xl {
    max-width: 90%;
}

.preview-section {
    margin-bottom: 1.5rem;
}

.preview-section h5 {
    color: #495057;
    font-weight: 600;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .action-buttons .btn {
        margin-left: 0;
        width: 100%;
    }
    
    .nav-tabs {
        flex-wrap: wrap;
    }
    
    .nav-tabs .nav-link {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
    
    .confidence-item {
        margin-bottom: 0.75rem;
    }
}

/* Print styles */
@media print {
    .action-buttons, .nav-tabs, .btn {
        display: none !important;
    }
    
    .tab-content .tab-pane {
        display: block !important;
    }
    
    .card {
        border: 1px solid #000 !important;
        box-shadow: none !important;
        page-break-inside: avoid;
    }
}

/* Accessibility improvements */
.btn:focus {
    outline: 2px solid #0d6efd;
    outline-offset: 2px;
}

.form-control:focus, .form-select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Animation for state changes */
.form-check-input:checked {
    animation: checkboxSuccess 0.3s ease;
}

@keyframes checkboxSuccess {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* Loading states */
.btn.loading {
    position: relative;
    pointer-events: none;
}

.btn.loading::after {
    content: "";
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid transparent;
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
@endsection
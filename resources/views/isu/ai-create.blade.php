@extends('layouts.app')

@section('title', 'Tambah Isu dengan AI')

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
                                <i class="fas fa-robot me-2"></i>AI Isu Creator
                            </h2>
                            <p class="text-muted mb-0">Analisis otomatis berita dari URL untuk membuat isu strategis</p>
                        </div>
                        <div>
                            <a href="{{ route('isu.create') }}" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-edit me-1"></i>Manual Input
                            </a>
                            <a href="{{ route('isu.index') }}" class="btn btn-outline-primary">
                                <i class="fas fa-list me-1"></i>Daftar Isu
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main AI Interface -->
            <div class="row">
                <!-- URL Input Section -->
                <div class="col-lg-8 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0">
                                <i class="fas fa-link text-primary me-2"></i>Input URL Berita
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="ai-analysis-form" method="POST" action="{{ route('isu.ai.analyze') }}">
                                @csrf
                                
                                <!-- URL Input Area -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">URL Berita <span class="text-danger">*</span></label>
                                    <div class="border rounded-3 p-3" style="min-height: 200px; background: #f8f9fa;">
                                        <div id="url-input-container">
                                            <!-- URL Input Items akan ditambahkan disini -->
                                            <div class="url-input-item mb-3">
                                                <div class="input-group">
                                                    <span class="input-group-text bg-primary text-white">
                                                        <i class="fas fa-globe"></i>
                                                    </span>
                                                    <input type="url" 
                                                           class="form-control url-input" 
                                                           name="urls[]" 
                                                           placeholder="Masukkan URL berita (contoh: https://example.com/berita)"
                                                           required>
                                                    <button type="button" class="btn btn-outline-secondary preview-url-btn" disabled>
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger remove-url-btn" style="display: none;">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                                <div class="url-validation-feedback mt-1"></div>
                                                <div class="url-preview mt-2" style="display: none;"></div>
                                            </div>
                                        </div>
                                        
                                        <!-- Add URL Button -->
                                        <button type="button" id="add-url-btn" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-plus me-1"></i>Tambah URL (maksimal 5)
                                        </button>
                                        
                                        <!-- Drag & Drop Area -->
                                        <div class="mt-3 p-3 border border-dashed rounded text-center" id="drag-drop-area">
                                            <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                            <p class="text-muted mb-1">Atau salin & tempel multiple URL sekaligus</p>
                                            <small class="text-muted">Satu URL per baris, maksimal 5 URL</small>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Sistem akan menganalisis konten dari setiap URL dan menghasilkan isu strategis secara otomatis
                                    </small>
                                </div>

                                <!-- Processing Options -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Mode Analisis</label>
                                        <select class="form-select" name="analysis_mode">
                                            <option value="balanced">Balanced (Kecepatan vs Akurasi)</option>
                                            <option value="fast">Fast (Lebih cepat, akurasi standar)</option>
                                            <option value="accurate">Accurate (Lebih lambat, akurasi tinggi)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">AI Provider</label>
                                        <select class="form-select" name="ai_provider">
                                            <option value="auto">Auto (Sistem memilih otomatis)</option>
                                            <option value="openai">OpenAI GPT-4</option>
                                            <option value="claude">Anthropic Claude</option>
                                            <option value="gemini">Google Gemini</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-flex justify-content-between">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="save-draft" name="save_draft">
                                        <label class="form-check-label" for="save-draft">
                                            Simpan sebagai draft
                                        </label>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-secondary me-2" id="preview-content-btn" disabled>
                                            <i class="fas fa-search me-1"></i>Preview Konten
                                        </button>
                                        <button type="submit" class="btn btn-primary" id="start-analysis-btn" disabled>
                                            <i class="fas fa-brain me-1"></i>Mulai Analisis AI
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar -->
                <div class="col-lg-4">
                    <!-- Progress Tracker -->
                    <div class="card shadow-sm border-0 mb-4" id="progress-card" style="display: none;">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-tasks me-2"></i>Progress Analisis
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="progress mb-3" style="height: 10px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     role="progressbar" 
                                     style="width: 0%" 
                                     id="main-progress-bar"></div>
                            </div>
                            
                            <div class="progress-steps">
                                <div class="progress-step" data-step="validation">
                                    <i class="fas fa-check-circle text-muted me-2"></i>
                                    <span>Validasi URL</span>
                                    <div class="float-end">
                                        <span class="badge bg-secondary">Menunggu</span>
                                    </div>
                                </div>
                                <div class="progress-step mt-2" data-step="extraction">
                                    <i class="fas fa-download text-muted me-2"></i>
                                    <span>Ekstraksi Konten</span>
                                    <div class="float-end">
                                        <span class="badge bg-secondary">Menunggu</span>
                                    </div>
                                </div>
                                <div class="progress-step mt-2" data-step="analysis">
                                    <i class="fas fa-brain text-muted me-2"></i>
                                    <span>Analisis AI</span>
                                    <div class="float-end">
                                        <span class="badge bg-secondary">Menunggu</span>
                                    </div>
                                </div>
                                <div class="progress-step mt-2" data-step="generation">
                                    <i class="fas fa-magic text-muted me-2"></i>
                                    <span>Generate Hasil</span>
                                    <div class="float-end">
                                        <span class="badge bg-secondary">Menunggu</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <small class="text-muted d-block">Estimasi waktu: <span id="estimated-time">-</span></small>
                                <small class="text-muted d-block">Status: <span id="current-status">Siap memulai</span></small>
                            </div>
                            
                            <button type="button" class="btn btn-outline-danger btn-sm mt-3 w-100" id="cancel-analysis-btn" style="display: none;">
                                <i class="fas fa-stop me-1"></i>Batalkan Analisis
                            </button>
                        </div>
                    </div>

                    <!-- Quick Tips -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-lightbulb me-2"></i>Tips Penggunaan
                            </h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <small>Pastikan URL dapat diakses publik</small>
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <small>Gunakan URL artikel, bukan homepage</small>
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <small>Maksimal 5 URL untuk hasil optimal</small>
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <small>Review hasil AI sebelum menyimpan</small>
                                </li>
                                <li class="mb-0">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <small>Mode "Accurate" untuk isu penting</small>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Cost Estimation -->
                    <div class="card shadow-sm border-0 mt-4">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0">
                                <i class="fas fa-calculator me-2"></i>Estimasi Biaya
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <small>URL yang akan diproses:</small>
                                <strong><span id="url-count">0</span> URL</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <small>Estimasi token:</small>
                                <strong><span id="estimated-tokens">-</span></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small>Estimasi biaya:</small>
                                <strong class="text-primary">$<span id="estimated-cost">0.00</span></strong>
                            </div>
                            <hr class="my-2">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Biaya aktual dapat bervariasi tergantung panjang konten
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Content Preview Modal -->
<div class="modal fade" id="contentPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-eye me-2"></i>Preview Konten
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div id="preview-content-container">
                    <!-- Preview content akan dimuat disini -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="proceed-with-analysis">
                    <i class="fas fa-brain me-1"></i>Lanjut Analisis
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// AI Isu Creator JavaScript
class AIIsuCreator {
    constructor() {
        this.urls = [];
        this.sessionId = null;
        this.pollInterval = null;
        this.maxUrls = 5;
        this.isProcessing = false;
        
        this.initializeEventListeners();
        this.updateUrlCount();
    }
    
    initializeEventListeners() {
        // Add URL button
        document.getElementById('add-url-btn').addEventListener('click', () => {
            this.addUrlInput();
        });
        
        // URL input events
        document.addEventListener('input', (e) => {
            if (e.target.classList.contains('url-input')) {
                this.validateUrl(e.target);
            }
        });
        
        // Remove URL button events
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-url-btn') || e.target.closest('.remove-url-btn')) {
                this.removeUrlInput(e.target.closest('.url-input-item'));
            }
            
            if (e.target.classList.contains('preview-url-btn') || e.target.closest('.preview-url-btn')) {
                this.previewUrl(e.target.closest('.url-input-item'));
            }
        });
        
        // Form submission
        document.getElementById('ai-analysis-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.startAnalysis();
        });
        
        // Preview content button
        document.getElementById('preview-content-btn').addEventListener('click', () => {
            this.showContentPreview();
        });
        
        // Cancel analysis button
        document.getElementById('cancel-analysis-btn').addEventListener('click', () => {
            this.cancelAnalysis();
        });
        
        // Drag and drop functionality
        this.initializeDragDrop();
    }
    
    addUrlInput() {
        const container = document.getElementById('url-input-container');
        const currentInputs = container.querySelectorAll('.url-input-item');
        
        if (currentInputs.length >= this.maxUrls) {
            this.showAlert('warning', `Maksimal ${this.maxUrls} URL yang dapat diproses sekaligus`);
            return;
        }
        
        const urlInputHtml = `
            <div class="url-input-item mb-3">
                <div class="input-group">
                    <span class="input-group-text bg-primary text-white">
                        <i class="fas fa-globe"></i>
                    </span>
                    <input type="url" 
                           class="form-control url-input" 
                           name="urls[]" 
                           placeholder="Masukkan URL berita"
                           required>
                    <button type="button" class="btn btn-outline-secondary preview-url-btn" disabled>
                        <i class="fas fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger remove-url-btn">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <div class="url-validation-feedback mt-1"></div>
                <div class="url-preview mt-2" style="display: none;"></div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', urlInputHtml);
        this.updateUrlCount();
        this.updateAddButton();
        this.updateRemoveButtons();
    }
    
    removeUrlInput(urlItem) {
        urlItem.remove();
        this.updateUrlCount();
        this.updateAddButton();
        this.updateRemoveButtons();
        this.updateFormButtons();
    }
    
    updateRemoveButtons() {
        const items = document.querySelectorAll('.url-input-item');
        items.forEach((item, index) => {
            const removeBtn = item.querySelector('.remove-url-btn');
            if (items.length === 1) {
                removeBtn.style.display = 'none';
            } else {
                removeBtn.style.display = 'block';
            }
        });
    }
    
    updateAddButton() {
        const currentInputs = document.querySelectorAll('.url-input-item');
        const addBtn = document.getElementById('add-url-btn');
        
        if (currentInputs.length >= this.maxUrls) {
            addBtn.disabled = true;
            addBtn.innerHTML = '<i class="fas fa-ban me-1"></i>Maksimal URL tercapai';
        } else {
            addBtn.disabled = false;
            addBtn.innerHTML = `<i class="fas fa-plus me-1"></i>Tambah URL (${currentInputs.length}/${this.maxUrls})`;
        }
    }
    
    validateUrl(input) {
        const url = input.value.trim();
        const feedbackDiv = input.closest('.url-input-item').querySelector('.url-validation-feedback');
        const previewBtn = input.closest('.url-input-item').querySelector('.preview-url-btn');
        
        if (!url) {
            feedbackDiv.innerHTML = '';
            previewBtn.disabled = true;
            this.updateFormButtons();
            return;
        }
        
        // Basic URL validation
        try {
            new URL(url);
            feedbackDiv.innerHTML = '<small class="text-success"><i class="fas fa-check me-1"></i>URL valid</small>';
            previewBtn.disabled = false;
            
            // Additional validation (check if accessible)
            this.checkUrlAccessibility(url, feedbackDiv);
        } catch (e) {
            feedbackDiv.innerHTML = '<small class="text-danger"><i class="fas fa-times me-1"></i>Format URL tidak valid</small>';
            previewBtn.disabled = true;
        }
        
        this.updateFormButtons();
    }
    
    async checkUrlAccessibility(url, feedbackDiv) {
        try {
            const response = await fetch('/api/check-url', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ url: url })
            });
            
            const result = await response.json();
            
            if (result.accessible) {
                feedbackDiv.innerHTML = '<small class="text-success"><i class="fas fa-check me-1"></i>URL dapat diakses</small>';
            } else {
                feedbackDiv.innerHTML = '<small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>URL mungkin tidak dapat diakses</small>';
            }
        } catch (error) {
            feedbackDiv.innerHTML = '<small class="text-muted"><i class="fas fa-question me-1"></i>Checking accessibility...</small>';
        }
    }
    
    updateFormButtons() {
        const validUrls = this.getValidUrls();
        const previewBtn = document.getElementById('preview-content-btn');
        const analysisBtn = document.getElementById('start-analysis-btn');
        
        if (validUrls.length > 0) {
            previewBtn.disabled = false;
            analysisBtn.disabled = false;
        } else {
            previewBtn.disabled = true;
            analysisBtn.disabled = true;
        }
    }
    
    getValidUrls() {
        const urlInputs = document.querySelectorAll('.url-input');
        const validUrls = [];
        
        urlInputs.forEach(input => {
            const url = input.value.trim();
            if (url) {
                try {
                    new URL(url);
                    validUrls.push(url);
                } catch (e) {
                    // Invalid URL, skip
                }
            }
        });
        
        return validUrls;
    }
    
    updateUrlCount() {
        const urlCount = document.querySelectorAll('.url-input-item').length;
        document.getElementById('url-count').textContent = urlCount;
        this.updateCostEstimation(urlCount);
    }
    
    updateCostEstimation(urlCount) {
        // Estimasi berdasarkan rata-rata artikel
        const avgTokensPerUrl = 2000; // Estimasi token per artikel
        const totalTokens = urlCount * avgTokensPerUrl;
        const costPerToken = 0.00002; // Estimasi cost per token
        const estimatedCost = (totalTokens * costPerToken).toFixed(3);
        
        document.getElementById('estimated-tokens').textContent = totalTokens.toLocaleString();
        document.getElementById('estimated-cost').textContent = estimatedCost;
    }
    
    initializeDragDrop() {
        const dropArea = document.getElementById('drag-drop-area');
        
        dropArea.addEventListener('click', () => {
            const text = prompt('Paste multiple URLs (satu per baris):');
            if (text) {
                this.handleMultipleUrls(text);
            }
        });
        
        dropArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropArea.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
        });
        
        dropArea.addEventListener('dragleave', () => {
            dropArea.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
        });
        
        dropArea.addEventListener('drop', (e) => {
            e.preventDefault();
            dropArea.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
            
            const text = e.dataTransfer.getData('text');
            if (text) {
                this.handleMultipleUrls(text);
            }
        });
    }
    
    handleMultipleUrls(text) {
        const urls = text.split('\n').map(url => url.trim()).filter(url => url);
        const container = document.getElementById('url-input-container');
        
        // Clear existing inputs
        container.innerHTML = '';
        
        // Add URLs (max 5)
        const urlsToAdd = urls.slice(0, this.maxUrls);
        
        urlsToAdd.forEach((url, index) => {
            this.addUrlInput();
            const lastInput = container.querySelector('.url-input-item:last-child .url-input');
            lastInput.value = url;
            this.validateUrl(lastInput);
        });
        
        if (urls.length > this.maxUrls) {
            this.showAlert('warning', `Hanya ${this.maxUrls} URL pertama yang ditambahkan. ${urls.length - this.maxUrls} URL lainnya diabaikan.`);
        }
        
        this.updateUrlCount();
        this.updateAddButton();
        this.updateRemoveButtons();
    }
    
    async showContentPreview() {
        const validUrls = this.getValidUrls();
        const modal = new bootstrap.Modal(document.getElementById('contentPreviewModal'));
        const container = document.getElementById('preview-content-container');
        
        container.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div><p class="mt-2">Memuat preview konten...</p></div>';
        modal.show();
        
        try {
            const response = await fetch('/api/preview-content', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ urls: validUrls })
            });
            
            const result = await response.json();
            
            if (result.success) {
                let previewHtml = '';
                result.previews.forEach((preview, index) => {
                    previewHtml += `
                        <div class="border rounded p-3 mb-3">
                            <h6 class="text-primary">URL ${index + 1}: ${preview.url}</h6>
                            <p><strong>Judul:</strong> ${preview.title || 'Tidak ditemukan'}</p>
                            <p><strong>Preview:</strong> ${preview.excerpt || 'Tidak dapat diambil'}</p>
                            <small class="text-muted">Word count: ${preview.word_count || 0} kata</small>
                        </div>
                    `;
                });
                container.innerHTML = previewHtml;
            } else {
                container.innerHTML = '<div class="alert alert-danger">Gagal memuat preview konten</div>';
            }
        } catch (error) {
            container.innerHTML = '<div class="alert alert-danger">Terjadi kesalahan saat memuat preview</div>';
        }
    }
    
    async startAnalysis() {
        if (this.isProcessing) return;
        
        const validUrls = this.getValidUrls();
        if (validUrls.length === 0) {
            this.showAlert('warning', 'Masukkan minimal 1 URL yang valid');
            return;
        }
        
        this.isProcessing = true;
        this.showProgressCard();
        this.disableForm();
        
        const formData = new FormData(document.getElementById('ai-analysis-form'));
        
        try {
            const response = await fetch('/api/ai-analyze', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.sessionId = result.session_id;
                this.startProgressPolling();
            } else {
                throw new Error(result.message || 'Analisis gagal dimulai');
            }
        } catch (error) {
            this.showAlert('danger', 'Gagal memulai analisis: ' + error.message);
            this.isProcessing = false;
            this.enableForm();
            this.hideProgressCard();
        }
    }
    
    startProgressPolling() {
        this.pollInterval = setInterval(() => {
            this.checkAnalysisProgress();
        }, 2000);
    }
    
    async checkAnalysisProgress() {
        if (!this.sessionId) return;
        
        try {
            const response = await fetch(`/api/ai-progress/${this.sessionId}`);
            const result = await response.json();
            
            this.updateProgress(result);
            
            if (result.status === 'completed') {
                clearInterval(this.pollInterval);
                this.handleAnalysisComplete(result);
            } else if (result.status === 'failed') {
                clearInterval(this.pollInterval);
                this.handleAnalysisError(result);
            }
        } catch (error) {
            console.error('Error checking progress:', error);
        }
    }
    
    updateProgress(result) {
        const progressBar = document.getElementById('main-progress-bar');
        const statusSpan = document.getElementById('current-status');
        const timeSpan = document.getElementById('estimated-time');
        
        progressBar.style.width = result.progress + '%';
        statusSpan.textContent = result.current_step || 'Processing...';
        
        if (result.estimated_time_remaining) {
            timeSpan.textContent = result.estimated_time_remaining;
        }
        
        // Update step indicators
        document.querySelectorAll('.progress-step').forEach(step => {
            const stepName = step.dataset.step;
            const badge = step.querySelector('.badge');
            const icon = step.querySelector('i');
            
            if (result.completed_steps && result.completed_steps.includes(stepName)) {
                badge.className = 'badge bg-success';
                badge.textContent = 'Selesai';
                icon.className = 'fas fa-check-circle text-success me-2';
            } else if (result.current_step_key === stepName) {
                badge.className = 'badge bg-primary';
                badge.textContent = 'Proses';
                icon.className = 'fas fa-spinner fa-spin text-primary me-2';
            }
        });
    }
    
    handleAnalysisComplete(result) {
        this.showAlert('success', 'Analisis AI berhasil diselesaikan!');
        
        // Redirect to results page
        setTimeout(() => {
            window.location.href = `/isu/ai-results/${this.sessionId}`;
        }, 1500);
    }
    
    handleAnalysisError(result) {
        this.showAlert('danger', 'Analisis gagal: ' + (result.error || 'Terjadi kesalahan tidak dikenal'));
        this.isProcessing = false;
        this.enableForm();
        this.hideProgressCard();
    }
    
    cancelAnalysis() {
        if (!this.sessionId) return;
        
        if (confirm('Yakin ingin membatalkan analisis yang sedang berjalan?')) {
            fetch(`/api/ai-cancel/${this.sessionId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }).then(() => {
                clearInterval(this.pollInterval);
                this.isProcessing = false;
                this.enableForm();
                this.hideProgressCard();
                this.showAlert('info', 'Analisis dibatalkan');
            });
        }
    }
    
    showProgressCard() {
        document.getElementById('progress-card').style.display = 'block';
        document.getElementById('cancel-analysis-btn').style.display = 'block';
    }
    
    hideProgressCard() {
        document.getElementById('progress-card').style.display = 'none';
        document.getElementById('cancel-analysis-btn').style.display = 'none';
        
        // Reset progress
        document.getElementById('main-progress-bar').style.width = '0%';
        document.getElementById('current-status').textContent = 'Siap memulai';
        document.getElementById('estimated-time').textContent = '-';
        
        // Reset step indicators
        document.querySelectorAll('.progress-step').forEach(step => {
            const badge = step.querySelector('.badge');
            const icon = step.querySelector('i');
            
            badge.className = 'badge bg-secondary';
            badge.textContent = 'Menunggu';
            icon.className = icon.className.replace(/text-\w+/, 'text-muted').replace('fa-spinner fa-spin', 'fa-check-circle');
        });
    }
    
    disableForm() {
        document.getElementById('ai-analysis-form').style.opacity = '0.6';
        document.getElementById('ai-analysis-form').style.pointerEvents = 'none';
        document.getElementById('start-analysis-btn').disabled = true;
        document.getElementById('preview-content-btn').disabled = true;
    }
    
    enableForm() {
        document.getElementById('ai-analysis-form').style.opacity = '1';
        document.getElementById('ai-analysis-form').style.pointerEvents = 'auto';
        this.updateFormButtons();
    }
    
    showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Insert alert at the top of the container
        const container = document.querySelector('.container-fluid');
        container.insertAdjacentHTML('afterbegin', alertHtml);
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            const alert = container.querySelector('.alert');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    }
    
    async previewUrl(urlItem) {
        const urlInput = urlItem.querySelector('.url-input');
        const previewDiv = urlItem.querySelector('.url-preview');
        const url = urlInput.value.trim();
        
        if (!url) return;
        
        previewDiv.style.display = 'block';
        previewDiv.innerHTML = '<small class="text-muted"><i class="fas fa-spinner fa-spin me-1"></i>Memuat preview...</small>';
        
        try {
            const response = await fetch('/api/preview-single-url', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ url: url })
            });
            
            const result = await response.json();
            
            if (result.success) {
                previewDiv.innerHTML = `
                    <div class="card border-0 bg-light">
                        <div class="card-body p-2">
                            <h6 class="card-title mb-1" style="font-size: 0.9rem;">${result.title || 'Judul tidak ditemukan'}</h6>
                            <p class="card-text mb-1" style="font-size: 0.8rem;">${result.excerpt || 'Preview tidak tersedia'}</p>
                            <small class="text-muted">${result.word_count || 0} kata • ${result.domain || 'Unknown domain'}</small>
                        </div>
                    </div>
                `;
            } else {
                previewDiv.innerHTML = '<small class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Gagal memuat preview</small>';
            }
        } catch (error) {
            previewDiv.innerHTML = '<small class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Error memuat preview</small>';
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.aiIsuCreator = new AIIsuCreator();
});

// Handle proceed with analysis from modal
document.getElementById('proceed-with-analysis').addEventListener('click', () => {
    const modal = bootstrap.Modal.getInstance(document.getElementById('contentPreviewModal'));
    modal.hide();
    window.aiIsuCreator.startAnalysis();
});
</script>

<style>
.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 25px rgba(0,0,0,0.1) !important;
}

.url-input-item {
    transition: all 0.3s ease;
}

.url-input-item:hover {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 10px;
    margin: -10px;
}

.progress-step {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
    transition: all 0.3s ease;
}

.progress-step:last-child {
    border-bottom: none;
}

.progress-step:hover {
    background-color: #f8f9fa;
    border-radius: 5px;
    padding: 8px 10px;
    margin: 0 -10px;
}

.progress-bar-animated {
    animation: progress-bar-stripes 1s linear infinite;
}

#drag-drop-area {
    transition: all 0.3s ease;
    cursor: pointer;
}

#drag-drop-area:hover {
    border-color: #0d6efd !important;
    background-color: rgba(13, 110, 253, 0.05) !important;
}

.url-validation-feedback {
    min-height: 20px;
}

.badge {
    font-size: 0.7rem;
}

.input-group .btn {
    border-left: 0;
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

/* Loading animation for buttons */
.btn-loading {
    position: relative;
    pointer-events: none;
}

.btn-loading::after {
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

/* Mobile responsive adjustments */
@media (max-width: 768px) {
    .card-body {
        padding: 1rem;
    }
    
    .url-input-item .input-group {
        flex-wrap: nowrap;
    }
    
    .url-input-item .btn {
        padding: 0.375rem 0.5rem;
    }
    
    .url-input-item .btn i {
        font-size: 0.8rem;
    }
    
    #drag-drop-area {
        padding: 1.5rem 1rem;
    }
}

/* Accessibility improvements */
.btn:focus {
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.form-control:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Dark mode support (if needed) */
@media (prefers-color-scheme: dark) {
    .bg-light {
        background-color: #2d3748 !important;
        color: #e2e8f0;
    }
    
    .text-muted {
        color: #a0aec0 !important;
    }
    
    .border {
        border-color: #4a5568 !important;
    }
}

/* Print styles */
@media print {
    .btn, .card-header, #progress-card {
        display: none !important;
    }
    
    .card {
        border: 1px solid #000 !important;
        box-shadow: none !important;
    }
}
</style>
@endsection
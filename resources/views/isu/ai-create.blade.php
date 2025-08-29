@extends(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isEditor()) ? 'layouts.admin' : 'layouts.app')

@section('title', 'Pembuat Isu - AI')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Header Section - DISERAGAMKAN dengan ai-results -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <div>
                            <h2 class="text-primary mb-1 fw-bold">
                                <i class="fas fa-wand-magic-sparkles me-2"></i>Pembuat Isu AI
                            </h2>
                            <p class="text-muted mb-0 small">Buat isu strategis dari URL berita menggunakan AI</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Left Column - Main Content -->
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0">
                        <!-- Card Header dengan Background Color -->
                        <div class="card-header bg-primary text-white p-2">
                            <h5 class="mb-0 d-flex align-items-center">
                                <i class="fas fa-link me-2"></i>Input URL Berita
                            </h5>
                        </div>
                        <div class="card-body p-3">
                            <form id="ai-analysis-form" method="POST" action="{{ route('isu.ai.analyze') }}">
                                @csrf                                
                                <!-- TAMBAHKAN BAGIAN INI - Provider Selection -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-cogs me-1"></i>AI Provider
                                    </label>
                                    <div class="row g-3" id="providerSelection">
                                        <!-- Providers will be loaded here -->
                                    </div>
                                </div>

                                <!-- TAMBAHKAN BAGIAN INI - Model Selection -->
                                <div class="mb-4" id="modelSelection" style="display: none;">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-microchip me-1"></i>Model Selection
                                    </label>
                                    <select class="form-select" id="modelSelect" name="ai_model">
                                        <option value="">Select Model (Optional)</option>
                                    </select>
                                    <div class="form-text">Leave empty to use default model</div>
                                </div>

                                <!-- TAMBAHKAN BAGIAN INI - Connection Test -->
                                <div class="mb-4" id="connectionTest" style="display: none;">
                                    <button type="button" class="btn btn-outline-info btn-sm" id="testConnectionBtn">
                                        <i class="fas fa-wifi me-1"></i>Test Connection
                                    </button>
                                    <div id="connectionResult" class="mt-2"></div>
                                </div>

                                <!-- TAMBAHKAN INPUT HIDDEN UNTUK PROVIDER -->
                                <input type="hidden" name="provider" id="selectedProvider" value="">
                                
                                <!-- URL Input Section -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">URL Berita <span class="text-danger">*</span></label>
                                    
                                    <!-- First URL Input -->
                                    <div class="url-input-group mb-3">
                                        <label class="form-label">URL 1:</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-link text-primary"></i>
                                            </span>
                                            <input type="url" name="urls[]" class="form-control url-input" 
                                                   placeholder="https://example.com/berita-1"
                                                   oninput="validateSingleURL(this)">
                                            <button type="button" class="btn btn-outline-danger" onclick="removeURL(this)" style="display: none;">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div class="url-status mt-1 small text-muted" style="display: none;"></div>
                                    </div>
                                    
                                    <!-- Container for additional URL inputs -->
                                    <div id="url-container"></div>
                                    
                                    <!-- Add URL Button -->
                                    <button type="button" onclick="addNewURL()" class="btn btn-outline-primary btn-sm mb-3">
                                        <i class="fas fa-plus me-1"></i>Tambah URL
                                    </button>
                                </div>

                                <!-- Bulk URL Input -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Atau masukkan multiple URL (satu per baris):</label>
                                    <textarea id="bulk-urls" class="form-control mb-2" rows="3" 
                                              placeholder="https://example.com/berita-1&#10;https://example.com/berita-2&#10;https://example.com/berita-3"></textarea>
                                    <button type="button" onclick="parseBulkURLs()" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-magic me-1"></i>Parse URLs
                                    </button>
                                </div>

                                <!-- Analysis Options -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Mode Analisis:</label>
                                        <select name="analysis_mode" class="form-select">
                                            <option value="balanced">Balanced (2-3 menit)</option>
                                            <option value="fast">Fast (1-2 menit)</option>
                                            <option value="accurate">Accurate (3-4 menit)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="providerInfo">
                                        <label class="form-label fw-bold">Provider Info:</label>
                                        <div class="text-center text-muted">
                                            <i class="fas fa-arrow-left me-1"></i>
                                            Select a provider to see details
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-grid gap-2 d-md-flex">
                                    <button type="button" onclick="validateURLs()" class="btn btn-outline-warning">
                                        <i class="fas fa-check-circle me-1"></i>Validate URLs
                                    </button>
                                    <button type="button" onclick="submitAnalysis()" class="btn btn-primary" id="analyzeBtn" disabled>
                                        <i class="fas fa-wand-magic-sparkles me-1"></i>Mulai Analisis AI
                                    </button>
                                </div>
                            </form>
                            <div id="analysisProgress" style="display: none;" class="mt-4">
                                <div class="progress mb-3">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                        role="progressbar" style="width: 0%"></div>
                                </div>
                                <div class="text-center">
                                    <small class="text-muted" id="progressText">Initializing...</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Help & Info -->
                <div class="col-lg-4">
                    <!-- Help Card -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-info text-white p-2">
                            <h6 class="mb-0 d-flex align-items-center">
                                <i class="fas fa-question-circle me-2"></i>Panduan Penggunaan
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <ol class="small">
                                <li><strong>Input URLs:</strong> Masukkan 1-5 URL berita yang valid</li>
                                <li><strong>Validate:</strong> Pastikan semua URL valid</li>
                                <li><strong>Analyze:</strong> Mulai analisis AI</li>
                            </ol>
                            
                            <div class="alert alert-light p-2 mt-3 mb-0">
                                <strong>💡 Tips:</strong>
                                <ul class="mb-0 mt-1 small">
                                    <li>Mode "Fast" untuk preview cepat</li>
                                    <li>Mode "Accurate" untuk hasil terbaik</li>
                                    <li>Gunakan URL dari sumber terpercaya</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- System Status Card -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-warning text-dark p-2">
                            <h6 class="mb-0 d-flex align-items-center">
                                <i class="fas fa-server me-2"></i>System Status
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-medium text-dark">AI Service</span>
                                    <span class="badge bg-success text-white">Online</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-medium text-dark">Web Scraping</span>
                                    <span class="badge bg-success text-white">Ready</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 95%"></div>
                                </div>
                            </div>
                            <div class="mb-0">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-medium text-dark">Queue</span>
                                    <span class="badge bg-warning text-dark">2 Jobs</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: 75%"></div>
                                </div>
                            </div>
                            <div class="alert alert-info p-2 mt-3 mb-0">
                                <i class="fas fa-info-circle me-1"></i>
                                <strong class="small">Info:</strong> Semua sistem berjalan normal. Estimasi waktu proses 2-4 menit per analisis.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Validation Modal -->
<div class="modal fade" id="validationModal" tabindex="-1" aria-labelledby="validationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header p-2">
                <h5 class="modal-title" id="validationModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Hasil Validasi URL
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3" id="validation-results">
                Loading...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<!-- Results Modal -->
<div class="modal fade" id="resultsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-chart-line me-2"></i>AI Analysis Results
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="analysisResults">
                <!-- Results will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveResultsBtn">
                    <i class="fas fa-save me-1"></i>Save Results
                </button>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPT INLINE AGAR PASTI BERJALAN -->
<script type="text/javascript">
// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Loading AI Isu Creator with Provider Support...');
    
    // ============================
    // GLOBAL VARIABLES
    // ============================
    let urlCounter = 1;
    const maxUrls = 5;
    let debugMode = true;
    
    // Provider management variables
    let selectedProvider = null;
    let selectedModel = null;
    let sessionId = null;
    let providers = {};

    // ============================
    // PROVIDER FUNCTIONS
    // ============================
    
    async function initializeProviders() {
        try {
            console.log('🔧 Initializing AI providers...');
            
            // Static providers configuration
            providers = {
                'groq': {
                    name: 'Groq',
                    description: 'Cloud-based AI dengan kecepatan tinggi',
                    icon: 'fas fa-bolt',
                    is_available: true,
                    models: {
                        'gemma2-9b-it': 'Gemma2 9B (Recommended)',
                        'llama-3.3-70b-versatile': 'Llama 3.3 70B (Powerful)'
                    }
                },
                'ollama': {
                    name: 'Ollama', 
                    description: 'Local AI models untuk privacy dan kontrol penuh',
                    icon: 'fas fa-server',
                    is_available: false, // Will be checked
                    models: {
                        'llama3.2': 'Llama 3.2 (Recommended)',
                        'gemma2': 'Gemma2 (Google)'
                    }
                }
            };
            
            await checkOllamaAvailability();
            renderProviders();
            
            // Auto-select Groq if available
            if (providers['groq'].is_available) {
                selectProvider('groq');
            }
            
            console.log('✅ Providers initialized successfully');
            
        } catch (error) {
            console.error('❌ Failed to initialize providers:', error);
        }
    }

    async function checkOllamaAvailability() {
        try {
            const response = await fetch('/api/ai/health');
            const data = await response.json();
            if (data.providers && data.providers.ollama) {
                providers.ollama.is_available = data.providers.ollama.status === 'available';
            }
            console.log('Ollama availability:', providers.ollama.is_available);
        } catch (error) {
            console.log('Ollama check failed (expected if not installed):', error.message);
            providers.ollama.is_available = false;
        }
    }

    function renderProviders() {
        const container = document.getElementById('providerSelection');
        if (!container) {
            console.warn('Provider selection container not found');
            return;
        }
        
        container.innerHTML = '';

        Object.entries(providers).forEach(([key, provider]) => {
            const card = document.createElement('div');
            card.className = 'col-md-6';
            card.innerHTML = `
                <div class="provider-card p-3 position-relative" data-provider="${key}">
                    <div class="provider-status">
                        <i class="fas fa-circle ${provider.is_available ? 'status-available' : 'status-unavailable'}"></i>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="${provider.icon} fa-2x text-primary me-3"></i>
                        <div>
                            <h6 class="mb-0">${provider.name}</h6>
                            <small class="text-muted">${provider.is_available ? 'Available' : 'Unavailable'}</small>
                        </div>
                    </div>
                    <p class="mb-0 small">${provider.description}</p>
                </div>
            `;
            container.appendChild(card);
        });

        // Add click listeners for provider selection
        document.querySelectorAll('.provider-card').forEach(card => {
            card.addEventListener('click', () => {
                const provider = card.dataset.provider;
                if (providers[provider].is_available) {
                    selectProvider(provider);
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Provider Tidak Tersedia',
                        text: `${providers[provider].name} sedang tidak tersedia saat ini.`
                    });
                }
            });
        });
    }

    function selectProvider(providerKey) {
        console.log(`🎯 Selecting provider: ${providerKey}`);
        
        // Remove previous selection
        document.querySelectorAll('.provider-card').forEach(card => {
            card.classList.remove('selected');
        });

        // Select new provider
        const providerCard = document.querySelector(`[data-provider="${providerKey}"]`);
        if (providerCard) {
            providerCard.classList.add('selected');
            selectedProvider = providerKey;
            
            // Update hidden input
            const hiddenInput = document.getElementById('selectedProvider');
            if (hiddenInput) {
                hiddenInput.value = providerKey;
            }
            
            showModelSelection();
            showConnectionTest();
            showProviderInfo();
            validateAnalysisButton();
            
            updateDebugInfo(`Provider ${providerKey} selected`);
        }
    }

    function showModelSelection() {
        const provider = providers[selectedProvider];
        const modelSelect = document.getElementById('modelSelect');
        const modelSelection = document.getElementById('modelSelection');
        
        if (!modelSelect || !modelSelection) return;
        
        modelSelect.innerHTML = '<option value="">Select Model (Optional)</option>';
        
        if (provider.models) {
            Object.entries(provider.models).forEach(([key, name]) => {
                const option = document.createElement('option');
                option.value = key;
                option.textContent = name;
                modelSelect.appendChild(option);
            });
        }
        
        modelSelection.style.display = 'block';
        
        // Add change listener for model selection
        modelSelect.removeEventListener('change', handleModelChange);
        modelSelect.addEventListener('change', handleModelChange);
    }

    function handleModelChange(e) {
        selectedModel = e.target.value;
        updateDebugInfo(`Model selected: ${selectedModel || 'default'}`);
    }

    function showConnectionTest() {
        const connectionTest = document.getElementById('connectionTest');
        if (!connectionTest) return;
        
        connectionTest.style.display = 'block';
        
        const testBtn = document.getElementById('testConnectionBtn');
        if (testBtn) {
            // Remove existing listeners
            testBtn.replaceWith(testBtn.cloneNode(true));
            const newTestBtn = document.getElementById('testConnectionBtn');
            
            newTestBtn.addEventListener('click', async () => {
                await testProviderConnection();
            });
        }
    }

    async function testProviderConnection() {
        const btn = document.getElementById('testConnectionBtn');
        const resultDiv = document.getElementById('connectionResult');
        
        if (!btn || !resultDiv) return;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Testing...';
        
        try {
            const response = await fetch('/api/ai/test-provider', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    provider: selectedProvider,
                    model: selectedModel
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                resultDiv.innerHTML = `
                    <div class="connection-success">
                        <i class="fas fa-check-circle me-1"></i>
                        ${data.data.message || 'Connection successful'}
                        ${data.data.response_time ? `<br><small>Response time: ${data.data.response_time}ms</small>` : ''}
                    </div>
                `;
                updateDebugInfo(`${selectedProvider} connection test successful`);
            } else {
                resultDiv.innerHTML = `
                    <div class="connection-error">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        ${data.message}
                    </div>
                `;
                updateDebugInfo(`${selectedProvider} connection test failed`);
            }
        } catch (error) {
            resultDiv.innerHTML = `
                <div class="connection-error">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Connection test failed: ${error.message}
                </div>
            `;
            console.error('Connection test error:', error);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-wifi me-1"></i>Test Connection';
        }
    }

    function showProviderInfo() {
        const provider = providers[selectedProvider];
        const infoContainer = document.getElementById('providerInfo');
        
        if (!infoContainer) return;
        
        infoContainer.innerHTML = `
            <label class="form-label fw-bold">Provider Info:</label>
            <div class="text-center">
                <i class="${provider.icon} fa-2x text-primary mb-2"></i>
                <h6>${provider.name}</h6>
                <p class="small text-muted mb-2">${provider.description}</p>
                <span class="badge ${provider.is_available ? 'bg-success' : 'bg-danger'}">
                    ${provider.is_available ? 'Available' : 'Unavailable'}
                </span>
                <br><small class="text-muted mt-1">${Object.keys(provider.models || {}).length} models available</small>
            </div>
        `;
    }

    function validateAnalysisButton() {
        const analyzeBtn = document.getElementById('analyzeBtn');
        if (!analyzeBtn) return;
        
        const urls = getAllURLs();
        const validUrls = urls.filter(url => isValidURL(url));
        const hasProvider = selectedProvider !== null;
        
        const isValid = hasProvider && validUrls.length > 0;
        analyzeBtn.disabled = !isValid;
        
        // Update button text based on validation
        if (!hasProvider) {
            analyzeBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Pilih Provider Dulu';
        } else if (validUrls.length === 0) {
            analyzeBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Tambah URL Dulu';
        } else {
            analyzeBtn.innerHTML = '<i class="fas fa-wand-magic-sparkles me-1"></i>Mulai Analisis AI';
        }
    }

    // ============================
    // ENHANCED AI ANALYSIS FUNCTIONS
    // ============================
    
    async function startAIAnalysis(urls) {
        const analyzeBtn = document.getElementById('analyzeBtn');
        
        if (analyzeBtn) {
            analyzeBtn.disabled = true;
            analyzeBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Starting Analysis...';
        }
        
        showProgress();

        try {
            const response = await fetch('/api/ai/analyze', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    urls: urls,
                    provider: selectedProvider,
                    model: selectedModel
                })
            });

            // CHECK RESPONSE STATUS FIRST
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            // CHECK CONTENT TYPE
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text);
                throw new Error('Server returned non-JSON response. Check server logs.');
            }

            const data = await response.json();

            if (data.success) {
                sessionId = data.session_id;
                Swal.fire({
                    icon: 'success',
                    title: 'Analisis Dimulai!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                pollAnalysisStatus();
                updateDebugInfo(`Analysis started with session: ${sessionId}`);
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Analysis start failed:', error);
            
            let errorMessage = 'Failed to start analysis';
            
            if (error.message.includes('JSON')) {
                errorMessage = 'Server configuration error. Please check server logs.';
            } else if (error.message.includes('HTTP')) {
                errorMessage = 'Server error: ' + error.message;
            } else {
                errorMessage = error.message;
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Gagal Memulai Analisis',
                text: errorMessage,
                footer: '<small>Check browser console for more details</small>'
            });
            hideProgress();
        } finally {
            if (analyzeBtn) {
                analyzeBtn.disabled = false;
                validateAnalysisButton();
            }
        }
    }

    function showProgress() {
        const progressDiv = document.getElementById('analysisProgress');
        if (progressDiv) {
            progressDiv.style.display = 'block';
            updateProgress(10, 'Starting analysis...');
        }
    }

    function hideProgress() {
        const progressDiv = document.getElementById('analysisProgress');
        if (progressDiv) {
            progressDiv.style.display = 'none';
        }
    }

    function updateProgress(percentage, text) {
        const progressBar = document.querySelector('.progress-bar');
        const progressText = document.getElementById('progressText');
        
        if (progressBar) {
            progressBar.style.width = percentage + '%';
        }
        if (progressText) {
            progressText.textContent = text;
        }
    }

    async function pollAnalysisStatus() {
        if (!sessionId) return;

        try {
            const response = await fetch(`/api/ai/status/${sessionId}`);
            const data = await response.json();

            if (data.success) {
                const status = data.data.processing_status;
                const progress = data.data.progress || 0;
                const step = data.data.current_step || 'Processing...';
                
                updateProgress(progress, step);
                updateDebugInfo(`Analysis status: ${status} (${progress}%)`);
                
                if (status === 'completed') {
                    updateProgress(100, 'Analysis completed!');
                    setTimeout(() => {
                        hideProgress();
                        showResults();
                    }, 1000);
                } else if (status === 'failed') {
                    hideProgress();
                    Swal.fire({
                        icon: 'error',
                        title: 'Analisis Gagal',
                        text: data.data.error_message || 'Unknown error'
                    });
                    updateDebugInfo(`Analysis failed: ${data.data.error_message}`);
                } else {
                    // Continue polling
                    setTimeout(() => pollAnalysisStatus(), 3000);
                }
            }
        } catch (error) {
            console.error('Failed to poll status:', error);
            setTimeout(() => pollAnalysisStatus(), 5000);
        }
    }

    async function showResults() {
        try {
            const response = await fetch(`/api/ai/result/${sessionId}`);
            const data = await response.json();
            
            if (data.success) {
                const results = data.data;
                displayResultsModal(results);
                updateDebugInfo('Results displayed successfully');
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Failed to load results:', error);
            Swal.fire({
                icon: 'error',
                title: 'Gagal Memuat Hasil',
                text: 'Failed to load analysis results'
            });
        }
    }

    function displayResultsModal(results) {
        const resultsContainer = document.getElementById('analysisResults');
        if (!resultsContainer) {
            console.warn('Results container not found');
            return;
        }
        
        resultsContainer.innerHTML = `
            <div class="row">
                <div class="col-md-12 mb-4">
                    <h6><i class="fas fa-file-alt me-1"></i> Resume</h6>
                    <div class="bg-light p-3 rounded">${results.ai_resume || 'No resume generated'}</div>
                </div>
                <div class="col-md-6 mb-4">
                    <h6><i class="fas fa-heading me-1"></i> Title Suggestions</h6>
                    <div class="bg-light p-3 rounded">
                        ${formatTitleSuggestions(results.ai_judul_suggestions)}
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <h6><i class="fas fa-chart-bar me-1"></i> Analysis Metrics</h6>
                    <div class="bg-light p-3 rounded">
                        <strong>Tone:</strong> ${results.ai_tone_suggestion || 'N/A'}<br>
                        <strong>Scale:</strong> ${results.ai_skala_suggestion || 'N/A'}<br>
                        <strong>Provider:</strong> ${results.ai_provider || 'N/A'}<br>
                        <strong>Processing Time:</strong> ${results.processing_time || 0}s
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <h6><i class="fas fa-smile me-1"></i> Positive Narrative</h6>
                    <div class="bg-light p-3 rounded">${results.ai_narasi_positif || 'No positive narrative generated'}</div>
                </div>
                <div class="col-md-6 mb-4">
                    <h6><i class="fas fa-frown me-1"></i> Negative Narrative</h6>
                    <div class="bg-light p-3 rounded">${results.ai_narasi_negatif || 'No negative narrative generated'}</div>
                </div>
            </div>
        `;
        
        const modal = new bootstrap.Modal(document.getElementById('resultsModal'));
        modal.show();
    }

    function formatTitleSuggestions(suggestions) {
        if (!suggestions || !Array.isArray(suggestions)) {
            return 'No title suggestions generated';
        }
        
        return suggestions.map((title, index) => 
            `<div class="mb-2"><strong>${index + 1}.</strong> ${title}</div>`
        ).join('');
    }

    // ============================
    // EXISTING HELPER FUNCTIONS (Keep all original functions)
    // ============================
    
    function getAllURLs() {
        const inputs = document.querySelectorAll('input[name="urls[]"]');
        return Array.from(inputs)
            .map(input => input.value.trim())
            .filter(url => url);
    }

    function isValidURL(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }

    function getDomain(url) {
        try {
            return new URL(url).hostname;
        } catch (_) {
            return 'Invalid URL';
        }
    }

    function updateRemoveButtons() {
        const removeButtons = document.querySelectorAll('.url-input-group .btn-outline-danger');
        removeButtons.forEach((btn, index) => {
            const totalInputs = document.querySelectorAll('.url-input-group').length;
            btn.style.display = totalInputs > 1 ? 'block' : 'none';
        });
    }

    function clearAdditionalInputs() {
        const container = document.getElementById('url-container');
        if (container) {
            container.innerHTML = '';
        }
        urlCounter = 1;
        updateURLCount();
    }

    function updateURLCount() {
        const countElement = document.getElementById('url-count');
        if (countElement) {
            countElement.textContent = urlCounter;
        }
    }

    function updateDebugInfo(message) {
        const debugElement = document.getElementById('debug-last-action');
        if (debugElement) {
            debugElement.textContent = message;
        }
        
        const timestamp = new Date().toLocaleTimeString();
        console.log(`[${timestamp}] ${message}`);
    }

    function getEstimatedTime(mode, urlCount) {
        const timePerUrl = {
            'fast': 20,
            'balanced': 30,
            'accurate': 45
        };
        
        const baseTime = timePerUrl[mode] || 30;
        const totalSeconds = baseTime * urlCount;
        const minutes = Math.ceil(totalSeconds / 60);
        
        return `${minutes} menit`;
    }

    // ============================
    // EXISTING MAIN FUNCTIONS (Enhanced with provider validation)
    // ============================
    
    // Validate single URL
    window.validateSingleURL = function(input) {
        const statusDiv = input.closest('.url-input-group')?.querySelector('.url-status');
        if (!statusDiv) return;
        
        const url = input.value.trim();
        
        if (!url) {
            statusDiv.style.display = 'none';
            validateAnalysisButton(); // Add provider validation
            return;
        }
        
        const isValid = isValidURL(url);
        if (isValid) {
            statusDiv.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i>URL valid';
            statusDiv.className = 'url-status mt-1 small text-success';
        } else {
            statusDiv.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i>Format URL tidak valid';
            statusDiv.className = 'url-status mt-1 small text-danger';
        }
        statusDiv.style.display = 'block';
        validateAnalysisButton(); // Add provider validation
    };

    // Add new URL input field
    window.addNewURL = function() {
        console.log('➕ Adding new URL input...');
        updateDebugInfo('Adding URL input');
        
        if (urlCounter >= maxUrls) {
            Swal.fire({
                icon: 'warning',
                title: 'Batas Maksimal',
                text: `Maksimal ${maxUrls} URL per analisis`
            });
            return;
        }
        
        urlCounter++;
        const container = document.getElementById('url-container');
        
        if (!container) {
            alert('❌ URL container tidak ditemukan');
            return;
        }
        
        const newDiv = document.createElement('div');
        newDiv.className = 'url-input-group mb-3';
        newDiv.innerHTML = `
            <label class="form-label">URL ${urlCounter}:</label>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-link text-primary"></i>
                </span>
                <input type="url" name="urls[]" class="form-control url-input" 
                       placeholder="https://example.com/berita-${urlCounter}"
                       oninput="validateSingleURL(this)">
                <button type="button" class="btn btn-outline-danger" onclick="removeURL(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="url-status mt-1 small text-muted" style="display: none;"></div>
        `;
        
        container.appendChild(newDiv);
        updateRemoveButtons();
        updateURLCount();
        validateAnalysisButton(); // Add provider validation
        updateDebugInfo(`URL input ${urlCounter} added`);
        
        console.log(`✅ URL input ${urlCounter} added successfully`);
        
        // Focus on new input
        const newInput = newDiv.querySelector('input');
        if (newInput) {
            newInput.focus();
        }
    };

    // Remove URL input field
    window.removeURL = function(button) {
        console.log('🗑️ Removing URL input...');
        updateDebugInfo('Removing URL input');
        
        const group = button.closest('.url-input-group');
        if (group) {
            group.remove();
            urlCounter--;
            updateRemoveButtons();
            updateURLCount();
            validateAnalysisButton(); // Add provider validation
            updateDebugInfo('URL input removed');
            console.log('✅ URL input removed successfully');
        }
    };

    // Parse bulk URLs from textarea
    window.parseBulkURLs = function() {
        console.log('📄 Parsing bulk URLs...');
        updateDebugInfo('Parsing bulk URLs');
        
        const bulkTextarea = document.getElementById('bulk-urls');
        if (!bulkTextarea) {
            alert('❌ Textarea tidak ditemukan');
            return;
        }
        
        const bulkText = bulkTextarea.value.trim();
        if (!bulkText) {
            alert('⚠️ Masukkan URL terlebih dahulu di textarea');
            return;
        }
        
        const urls = bulkText.split('\n')
            .map(url => url.trim())
            .filter(url => url);
        
        console.log('URLs to parse:', urls);
        
        if (urls.length === 0) {
            alert('❌ Tidak ada URL valid yang ditemukan');
            return;
        }
        
        if (urls.length > maxUrls) {
            Swal.fire({
                icon: 'warning',
                title: 'Terlalu Banyak URL',
                text: `Maksimal ${maxUrls} URL. Hanya ${maxUrls} URL pertama yang akan digunakan.`
            });
        }
        
        // Clear existing additional inputs
        clearAdditionalInputs();
        
        // Process URLs
        const urlsToProcess = urls.slice(0, maxUrls);
        
        urlsToProcess.forEach((url, index) => {
            if (index === 0) {
                // Update first input
                const firstInput = document.querySelector('input[name="urls[]"]');
                if (firstInput) {
                    firstInput.value = url;
                    validateSingleURL(firstInput);
                }
            } else {
                // Add new inputs
                addNewURL();
                const inputs = document.querySelectorAll('input[name="urls[]"]');
                const lastInput = inputs[inputs.length - 1];
                if (lastInput) {
                    lastInput.value = url;
                    validateSingleURL(lastInput);
                }
            }
        });
        
        // Clear textarea
        bulkTextarea.value = '';
        
        Swal.fire({
            icon: 'success',
            title: 'URLs Berhasil Di-parse',
            text: `${urlsToProcess.length} URL berhasil ditambahkan`,
            timer: 2000,
            showConfirmButton: false
        });
        updateDebugInfo(`${urlsToProcess.length} URLs parsed successfully`);
    };

    // Keep the existing validateURLs function unchanged
    window.validateURLs = function() {
        console.log('✅ Validating URLs...');
        updateDebugInfo('Validating URLs');
        
        const urls = getAllURLs();
        if (urls.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'URL Kosong',
                text: 'Masukkan minimal 1 URL terlebih dahulu',
                confirmButtonColor: '#0d6efd'
            });
            return;
        }
        
        console.log('URLs to validate:', urls);
        
        // Show loading SweetAlert
        Swal.fire({
            title: 'Memvalidasi URLs...',
            html: `
                <div class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                    <span>Sedang memvalidasi ${urls.length} URL...</span>
                </div>
            `,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Simulate validation process (replace with actual validation if needed)
        setTimeout(() => {
            let validCount = 0;
            let invalidCount = 0;
            let validationResults = '';
            
            urls.forEach((url, index) => {
                const isValid = isValidURL(url);
                const domain = getDomain(url);
                
                if (isValid) {
                    validCount++;
                } else {
                    invalidCount++;
                }
                
                const statusIcon = isValid ? '✅' : '❌';
                const statusColor = isValid ? 'text-success' : 'text-danger';
                const truncatedUrl = url.length > 50 ? url.substring(0, 50) + '...' : url;
                
                validationResults += `
                    <div class="d-flex justify-content-between align-items-start border-bottom py-2 mb-2">
                        <div class="flex-grow-1 text-start">
                            <strong>URL ${index + 1}:</strong> ${statusIcon}
                            <br>
                            <small class="text-muted">Domain: ${domain}</small>
                            <br>
                            <small class="text-muted">${truncatedUrl}</small>
                            <br>
                            <small class="${statusColor}">
                                ${isValid ? 'Format URL benar dan dapat diproses' : 'Format URL tidak valid atau tidak dapat diakses'}
                            </small>
                        </div>
                    </div>
                `;
            });
            
            // Determine overall status
            const overallStatus = invalidCount === 0 ? 'success' : validCount > 0 ? 'warning' : 'error';
            const overallTitle = invalidCount === 0 ? 'Semua URL Valid!' : 
                            validCount > 0 ? 'Validasi Selesai dengan Peringatan' : 'Semua URL Tidak Valid';
            
            // Show results in SweetAlert
            Swal.fire({
                icon: overallStatus,
                title: overallTitle,
                html: `
                    <div class="mb-3">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="bg-success bg-opacity-10 p-2 rounded">
                                    <strong class="text-success">${validCount}</strong>
                                    <br>
                                    <small class="text-success">Valid</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-danger bg-opacity-10 p-2 rounded">
                                    <strong class="text-danger">${invalidCount}</strong>
                                    <br>
                                    <small class="text-danger">Invalid</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-start" style="max-height: 300px; overflow-y: auto;">
                        ${validationResults}
                    </div>
                    ${invalidCount > 0 ? 
                        '<div class="alert alert-warning mt-3 p-2"><i class="fas fa-exclamation-triangle me-1"></i><strong>Perhatian:</strong> URL yang tidak valid akan diabaikan saat analisis.</div>' : 
                        '<div class="alert alert-success mt-3 p-2"><i class="fas fa-check-circle me-1"></i><strong>Bagus!</strong> Semua URL siap untuk dianalisis.</div>'
                    }
                `,
                width: '600px',
                confirmButtonColor: '#0d6efd',
                confirmButtonText: validCount > 0 ? 'Lanjut Analisis' : 'Perbaiki URL',
                showCancelButton: true,
                cancelButtonText: 'Tutup',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed && validCount > 0) {
                    // Auto-trigger analysis if user confirms and there are valid URLs
                    Swal.fire({
                        icon: 'question',
                        title: 'Lanjut ke Analisis?',
                        text: `Memulai analisis untuk ${validCount} URL yang valid?`,
                        showCancelButton: true,
                        confirmButtonColor: '#0d6efd',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Mulai!',
                        cancelButtonText: 'Batalkan'
                    }).then((confirmResult) => {
                        if (confirmResult.isConfirmed) {
                            submitAnalysis();
                        }
                    });
                }
            });
            
            updateDebugInfo(`Validation completed: ${validCount} valid, ${invalidCount} invalid`);
        }, 1500);
    };

    // Enhanced submit analysis with provider support
    window.submitAnalysis = function() {
        console.log('🚀 Submitting analysis with provider support...');
        updateDebugInfo('Preparing AI analysis submission');

        const urls = getAllURLs();
        const validURLs = urls.filter(url => isValidURL(url));
        
        // Validate provider selection
        if (!selectedProvider) {
            Swal.fire({
                icon: 'warning',
                title: 'Provider Belum Dipilih',
                text: 'Pilih AI provider terlebih dahulu sebelum memulai analisis.',
                confirmButtonColor: '#0d6efd'
            });
            return;
        }
        
        // Validate URLs
        if (urls.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'URL Kosong',
                text: 'Masukkan minimal 1 URL berita untuk memulai analisis.',
                confirmButtonColor: '#0d6efd'
            });
            return;
        }
        
        if (validURLs.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'URL Tidak Valid',
                text: 'Tidak ada URL yang valid untuk dianalisis. Periksa kembali format URL Anda.',
                confirmButtonColor: '#0d6efd'
            });
            return;
        }

        const form = document.getElementById('ai-analysis-form');
        const analysisMode = form.querySelector('[name="analysis_mode"]')?.value || 'balanced';
        const estimatedTime = getEstimatedTime(analysisMode, validURLs.length);
        const urlListHtml = validURLs.slice(0, 5).map(url => `<li><small>${url.length > 50 ? url.substring(0, 50) + '...' : url}</small></li>`).join('');
        
        const providerName = providers[selectedProvider].name;
        const modelName = selectedModel || 'Default Model';

        Swal.fire({
            title: 'Konfirmasi Analisis AI',
            html: `
                <div class="text-start">
                    <p>Apakah Anda yakin ingin memulai analisis ini?</p>
                    <strong>Detail Analisis:</strong>
                    <ul>
                        <li><strong>AI Provider:</strong> ${providerName}</li>
                        <li><strong>Model:</strong> ${modelName}</li>
                        <li><strong>Jumlah URL:</strong> ${validURLs.length}</li>
                        <li><strong>Mode Analisis:</strong> ${analysisMode.charAt(0).toUpperCase() + analysisMode.slice(1)}</li>
                        <li><strong>Estimasi Waktu:</strong> ${estimatedTime}</li>
                    </ul>
                    <p class="mb-0"><strong>URLs:</strong></p>
                    <ul class="list-unstyled small ps-3">
                        ${urlListHtml}
                    </ul>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Mulai Analisis!',
            cancelButtonText: 'Batalkan'
        }).then((result) => {
            if (result.isConfirmed) {
                console.log('AI analysis confirmed by user');
                updateDebugInfo('AI analysis confirmed by user');
                
                // Start AI analysis instead of form submission
                startAIAnalysis(validURLs);
            } else {
                updateDebugInfo('AI analysis cancelled by user');
            }
        });
    };

    // ============================
    // INITIALIZATION
    // ============================
    
    async function initializeApplication() {
        updateDebugInfo('Application initializing...');
        
        // Initialize providers first
        await initializeProviders();
        
        // Initialize existing functionality
        updateURLCount();
        updateRemoveButtons();
        
        // Validate existing URLs on page load
        const existingInputs = document.querySelectorAll('input[name="urls[]"]');
        existingInputs.forEach(input => {
            if (input.value.trim()) {
                validateSingleURL(input);
            }
        });
        
        // Initial validation of analysis button
        validateAnalysisButton();
        
        // Add event listeners for URL inputs
        document.addEventListener('input', (e) => {
            if (e.target.classList.contains('url-input')) {
                validateSingleURL(e.target);
            }
        });
        
        updateDebugInfo('Application initialized successfully');
        console.log('✅ AI Isu Creator with Provider Support initialization complete!');
    }

    // Start initialization
    initializeApplication();
});
</script>
@endsection

@push('styles')
<style>
/* STYLING DISERAGAMKAN dengan ai-results.blade.php */
.url-input-group {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    transition: all 0.2s ease;
}

.url-input-group:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.url-status.text-success { font-weight: 500; }
.url-status.text-danger { font-weight: 500; }

.preview-item {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 12px;
}

#bulk-urls {
    font-family: 'Courier New', monospace;
    font-size: 0.9em;
    resize: vertical;
}

.badge { 
    font-size: 0.75em; 
}

/* CONSISTENT CARD HEADERS */
.card-header h5, .card-header h6 {
    margin-bottom: 0;
    font-weight: 600;
    display: flex;
    align-items: center;
}

/* CONSISTENT FORM STYLING */
.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

textarea.form-control {
    resize: vertical;
    min-height: 120px;
}

.progress {
    border-radius: 10px;
}

.alert-light {
    border-left: 4px solid #0dcaf0;
}

.alert-info {
    border-left: 4px solid #0dcaf0;
}

    /* RESPONSIVE DESIGN */
    @media (max-width: 768px) {
        .container-fluid {
            padding-left: 10px;
            padding-right: 10px;
        }
        
        .card-body {
            padding: 1rem !important;
        }
        
        .d-grid.gap-2.d-md-flex {
            display: flex !important;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .url-input-group {
            padding: 10px;
        }
        
        /* Hide system status on mobile */
        .col-lg-4 .card:last-child {
            display: none;
        }
    }

    /* ADDITIONAL FIXES */
    .container-fluid {
        max-width: 100%;
    }

    .row {
        margin: 0;
    }

}
.provider-card {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.provider-card:hover {
    border-color: #007bff;
    box-shadow: 0 4px 8px rgba(0,123,255,0.1);
}

.provider-card.selected {
    border-color: #007bff;
    background-color: #f8f9ff;
}

.provider-status {
    position: absolute;
    top: 10px;
    right: 10px;
}

.status-available {
    color: #28a745;
}

.status-unavailable {
    color: #dc3545;
}

.connection-success {
    color: #28a745;
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    border-radius: 5px;
    padding: 10px;
}

.connection-error {
    color: #721c24;
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    border-radius: 5px;
    padding: 10px;
}
</style>
@endpush
<!-- {{-- Working AI Functions - Implementasi fungsi yang sebenarnya --}} -->
@extends('layouts.app')

@section('title', 'AI Isu Creator - Working Functions')

@section('content')
<script>
console.log('🚀 Loading AI Isu Creator with working functions...');

// ============================
// GLOBAL VARIABLES
// ============================
let urlCounter = 1;
const maxUrls = 5;
let debugMode = true;

// ============================
// CORE WORKING FUNCTIONS
// ============================

// Test basic JavaScript
window.testBasicJS = function() {
    console.log('✅ testBasicJS called');
    updateDebugInfo('JavaScript test completed');
    alert('✅ JavaScript bekerja dengan baik!\n\n• Functions loaded\n• Event handling active\n• Ready for AI operations');
};

// Test all functions
window.testAllFunctions = function() {
    console.log('🧪 Testing all functions...');
    updateDebugInfo('Testing all functions');
    
    const functions = [
        'testBasicJS', 'addNewURL', 'removeURL', 'parseBulkURLs', 
        'previewURLs', 'validateURLs', 'submitAnalysis'
    ];
    
    const results = [];
    let allWorking = true;
    
    functions.forEach(funcName => {
        if (typeof window[funcName] === 'function') {
            results.push(`✅ ${funcName}: Available`);
        } else {
            results.push(`❌ ${funcName}: NOT FOUND`);
            allWorking = false;
        }
    });
    
    const status = allWorking ? '🎉 All functions ready!' : '⚠️ Some functions missing';
    alert('🧪 Function Test Results:\n\n' + results.join('\n') + '\n\n' + status);
};

// Add new URL input field
window.addNewURL = function() {
    console.log('➕ Adding new URL input...');
    updateDebugInfo('Adding URL input');
    
    if (urlCounter >= maxUrls) {
        alert(`⚠️ Maksimal ${maxUrls} URL per analisis`);
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
        updateDebugInfo('URL input removed');
        console.log('✅ URL input removed successfully');
    }
};

// Parse bulk URLs from textarea
window.parseBulkURLs = function() {
    console.log('🔄 Parsing bulk URLs...');
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
        alert(`⚠️ Maksimal ${maxUrls} URL. Hanya ${maxUrls} URL pertama yang akan digunakan.`);
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
    
    alert(`✅ ${urlsToProcess.length} URL berhasil di-parse dan ditambahkan`);
    updateDebugInfo(`${urlsToProcess.length} URLs parsed successfully`);
};

// Preview URLs functionality
window.previewURLs = function() {
    console.log('👁️ Previewing URLs...');
    updateDebugInfo('Previewing URLs');
    
    const urls = getAllURLs();
    if (urls.length === 0) {
        alert('⚠️ Masukkan minimal 1 URL terlebih dahulu');
        return;
    }
    
    console.log('URLs to preview:', urls);
    
    const previewCard = document.getElementById('preview-card');
    const previewContent = document.getElementById('preview-content');
    
    if (!previewCard || !previewContent) {
        // Fallback: show in alert
        let previewText = 'Preview URLs:\n\n';
        urls.forEach((url, index) => {
            const isValid = isValidURL(url);
            const domain = getDomain(url);
            previewText += `${index + 1}. ${domain}\n${url}\nStatus: ${isValid ? '✅ Valid' : '❌ Invalid'}\n\n`;
        });
        alert(previewText);
        return;
    }
    
    // Show preview card
    previewContent.innerHTML = `
        <div class="text-center py-3">
            <div class="spinner-border spinner-border-sm text-primary me-2"></div>
            <span>Loading preview...</span>
        </div>
    `;
    previewCard.style.display = 'block';
    
    // Simulate preview loading
    setTimeout(() => {
        let html = '';
        urls.forEach((url, index) => {
            const isValid = isValidURL(url);
            const domain = getDomain(url);
            const statusClass = isValid ? 'success' : 'danger';
            const statusIcon = isValid ? 'check-circle' : 'times-circle';
            
            html += `
                <div class="preview-item border-start border-3 border-${statusClass} ps-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">URL ${index + 1}</h6>
                            <small class="text-muted d-block">${domain}</small>
                            <small class="text-muted">${url.length > 50 ? url.substring(0, 50) + '...' : url}</small>
                        </div>
                        <span class="badge bg-${statusClass}">
                            <i class="fas fa-${statusIcon} me-1"></i>
                            ${isValid ? 'Valid' : 'Invalid'}
                        </span>
                    </div>
                    <div class="mt-2">
                        <small class="${isValid ? 'text-success' : 'text-danger'}">
                            ${isValid ? '✅ Format URL benar, siap untuk analisis' : '❌ Format URL tidak valid'}
                        </small>
                    </div>
                </div>
            `;
        });
        
        previewContent.innerHTML = html;
        updateDebugInfo(`Preview generated for ${urls.length} URLs`);
    }, 1000);
};

// Validate URLs functionality
window.validateURLs = function() {
    console.log('✅ Validating URLs...');
    updateDebugInfo('Validating URLs');
    
    const urls = getAllURLs();
    if (urls.length === 0) {
        alert('⚠️ Masukkan minimal 1 URL terlebih dahulu');
        return;
    }
    
    console.log('URLs to validate:', urls);
    
    // Check if Bootstrap modal is available
    const modalElement = document.getElementById('validationModal');
    if (typeof bootstrap === 'undefined' || !modalElement) {
        // Fallback: Show in alert
        let results = 'Hasil Validasi URL:\n\n';
        urls.forEach((url, index) => {
            const isValid = isValidURL(url);
            const domain = getDomain(url);
            results += `URL ${index + 1}: ${isValid ? '✅ Valid' : '❌ Invalid'}\n`;
            results += `Domain: ${domain}\n`;
            results += `URL: ${url}\n\n`;
        });
        alert(results);
        updateDebugInfo('Validation completed (fallback alert)');
        return;
    }
    
    // Show Bootstrap modal
    const modal = new bootstrap.Modal(modalElement);
    const results = document.getElementById('validation-results');
    
    if (results) {
        results.innerHTML = `
            <div class="text-center py-3">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                <span>Memvalidasi URLs...</span>
            </div>
        `;
    }
    
    modal.show();
    
    // Simulate validation process
    setTimeout(() => {
        if (results) {
            let html = '';
            urls.forEach((url, index) => {
                const isValid = isValidURL(url);
                const domain = getDomain(url);
                const statusClass = isValid ? 'success' : 'danger';
                const statusText = isValid ? 'Valid' : 'Invalid';
                
                html += `
                    <div class="d-flex justify-content-between align-items-start border-bottom py-3">
                        <div class="flex-grow-1">
                            <strong>URL ${index + 1}:</strong>
                            <br>
                            <small class="text-muted">Domain: ${domain}</small>
                            <br>
                            <small class="text-muted">${url.length > 60 ? url.substring(0, 60) + '...' : url}</small>
                            <br>
                            <small class="${isValid ? 'text-success' : 'text-danger'}">
                                ${isValid ? 'Format URL benar dan dapat diproses' : 'Format URL tidak valid atau tidak dapat diakses'}
                            </small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-${statusClass} fs-6">
                                <i class="fas fa-${isValid ? 'check' : 'times'} me-1"></i>
                                ${statusText}
                            </span>
                        </div>
                    </div>
                `;
            });
            
            results.innerHTML = html;
        }
        
        updateDebugInfo(`Validation completed for ${urls.length} URLs`);
    }, 1500);
};

// Submit analysis form
window.submitAnalysis = function() {
    console.log('🚀 Submitting analysis...');
    updateDebugInfo('Preparing submission');
    
    const urls = getAllURLs();
    if (urls.length === 0) {
        alert('⚠️ Masukkan minimal 1 URL terlebih dahulu');
        return;
    }
    
    const validURLs = urls.filter(url => isValidURL(url));
    if (validURLs.length === 0) {
        alert('❌ Tidak ada URL yang valid untuk dianalisis');
        return;
    }
    
    // Get form data
    const form = document.getElementById('ai-analysis-form');
    if (!form) {
        alert('❌ Form tidak ditemukan');
        return;
    }
    
    const analysisMode = form.querySelector('[name="analysis_mode"]')?.value || 'balanced';
    const aiProvider = form.querySelector('[name="ai_provider"]')?.value || 'auto';
    
    const confirmMessage = `🚀 Mulai Analisis AI?

📊 Detail Analisis:
• URLs: ${validURLs.length} valid dari ${urls.length} total
• Mode: ${analysisMode}
• Provider: ${aiProvider}

📝 URLs yang akan dianalisis:
${validURLs.slice(0, 3).join('\n')}${validURLs.length > 3 ? '\n...' : ''}

⏱️ Estimasi waktu: ${getEstimatedTime(analysisMode, validURLs.length)}

Lanjutkan analisis?`;
    
    if (confirm(confirmMessage)) {
        console.log('Form submission confirmed');
        console.log('URLs to analyze:', validURLs);
        console.log('Analysis mode:', analysisMode);
        console.log('AI provider:', aiProvider);
        
        updateDebugInfo('Form submission confirmed');
        
        // Disable submit button and show loading
        const submitBtn = document.querySelector('[onclick="submitAnalysis()"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Memulai Analisis...';
        }
        
        // Submit the form
        try {
            form.submit();
        } catch (error) {
            console.error('Form submission error:', error);
            alert('❌ Gagal submit form: ' + error.message);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-magic me-1"></i>Mulai Analisis AI';
            }
        }
    } else {
        updateDebugInfo('Form submission cancelled');
    }
};

// ============================
// HELPER FUNCTIONS
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

function validateSingleURL(input) {
    const statusDiv = input.closest('.url-input-group')?.querySelector('.url-status');
    if (!statusDiv) return;
    
    const url = input.value.trim();
    
    if (!url) {
        statusDiv.style.display = 'none';
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
}

function updateRemoveButtons() {
    const removeButtons = document.querySelectorAll('.url-input-group .btn-outline-danger');
    removeButtons.forEach((btn, index) => {
        // Always show remove button if there's more than one input
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
// INITIALIZATION
// ============================

document.addEventListener('DOMContentLoaded', function() {
    console.log('🎉 DOM loaded, initializing AI Isu Creator...');
    
    updateDebugInfo('Page initialized');
    updateURLCount();
    updateRemoveButtons();
    
    // Validate existing URLs on page load
    const existingInputs = document.querySelectorAll('input[name="urls[]"]');
    existingInputs.forEach(input => {
        if (input.value.trim()) {
            validateSingleURL(input);
        }
    });
    
    console.log('✅ AI Isu Creator initialization complete!');
});

console.log('✅ All AI functions loaded successfully!');
</script>

<div class="container-fluid py-4">
    <!-- Success Alert -->
    <div class="alert alert-success">
        <h5>✅ AI Isu Creator - Working Functions!</h5>
        <p>Semua fungsi AI sekarang berfungsi penuh dengan implementasi yang sebenarnya.</p>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5><i class="fas fa-robot me-2"></i>AI Isu Creator</h5>
                </div>
                <div class="card-body">
                    <!-- Quick Test Section -->
                    <!-- <div class="mb-4 p-3 bg-light border rounded">
                        <h6>🧪 Test Functions:</h6>
                        <div class="d-flex gap-2 flex-wrap">
                            <button onclick="testBasicJS()" class="btn btn-success btn-sm">
                                ✅ Test Basic JS
                            </button>
                            <button onclick="testAllFunctions()" class="btn btn-info btn-sm">
                                🧪 Test All Functions
                            </button>
                        </div>
                    </div> -->

                    <form id="ai-analysis-form" method="POST" action="{{ route('isu.ai.analyze') }}">
                        @csrf
                        
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
                                           value="https://www.kompas.com/sulawesi-selatan/read/2025/03/21/11523488/6-jemaah-umrah-wni-men"
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
                            <label class="form-label">Atau masukkan multiple URL (satu per baris):</label>
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
                            <!-- <div class="col-md-6">
                                <label class="form-label fw-bold">AI Provider:</label>
                                <select name="ai_provider" class="form-select">
                                    <option value="auto">Auto (Recommended)</option>
                                    <option value="groq">Groq (Llama 3.1)</option>
                                    <option value="openai">OpenAI GPT-4</option>
                                    <option value="claude">Anthropic Claude</option>
                                </select>
                            </div> -->
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 flex-wrap">
                            <!-- <button type="button" onclick="previewURLs()" class="btn btn-outline-info">
                                <i class="fas fa-eye me-1"></i>Preview URLs
                            </button> -->
                            <button type="button" onclick="validateURLs()" class="btn btn-outline-warning">
                                <i class="fas fa-check-circle me-1"></i>Validate URLs
                            </button>
                            <button type="button" onclick="submitAnalysis()" class="btn btn-primary">
                                <i class="fas fa-magic me-1"></i>Mulai Analisis AI
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Help Card -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h6><i class="fas fa-question-circle me-2"></i>Panduan Penggunaan</h6>
                </div>
                <div class="card-body small">
                    <ol>
                        <li><strong>Input URLs:</strong> Masukkan 1-5 URL berita yang valid</li>
                        <li><strong>Validate:</strong> Pastikan semua URL valid</li>
                        <li><strong>Analyze:</strong> Mulai analisis AI</li>
                    </ol>
                    
                    <div class="mt-3 p-2 bg-light rounded">
                        <strong>💡 Tips:</strong>
                        <ul class="mb-0 mt-1">
                            <li>Mode "Fast" untuk preview cepat</li>
                            <li>Mode "Accurate" untuk hasil terbaik</li>
                            <li>Gunakan URL dari sumber terpercaya</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Validation Modal -->
<div class="modal fade" id="validationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle me-2"></i>Hasil Validasi URL
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="validation-results">
                Loading...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
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

.badge { font-size: 0.75em; }

.card-header h5, .card-header h6 {
    margin-bottom: 0;
    font-weight: 600;
}

@media (max-width: 768px) {
    .d-flex.gap-2 {
        flex-direction: column;
    }
    
    .url-input-group {
        padding: 10px;
    }
}
</style>
@endpush
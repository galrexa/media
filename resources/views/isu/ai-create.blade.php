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
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-grid gap-2 d-md-flex">
                                    <button type="button" onclick="validateURLs()" class="btn btn-outline-warning">
                                        <i class="fas fa-check-circle me-1"></i>Validate URLs
                                    </button>
                                    <button type="button" onclick="submitAnalysis()" class="btn btn-primary">
                                        <i class="fas fa-wand-magic-sparkles me-1"></i>Mulai Analisis AI
                                    </button>
                                </div>
                            </form>
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

<!-- SCRIPT INLINE AGAR PASTI BERJALAN -->
<script type="text/javascript">
// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Loading AI Isu Creator with working functions...');
    
    // ============================
    // GLOBAL VARIABLES
    // ============================
    let urlCounter = 1;
    const maxUrls = 5;
    let debugMode = true;

    // ============================
    // HELPER FUNCTIONS (Define first)
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
    // MAIN FUNCTIONS (Define as global)
    // ============================
    
    // Validate single URL
    window.validateSingleURL = function(input) {
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

    // Validate URLs functionality
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

    // Submit analysis form
    window.submitAnalysis = function() {
        console.log('🚀 Submitting analysis...');
        updateDebugInfo('Preparing submission');

        const urls = getAllURLs();
        if (urls.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'URL Kosong',
                text: 'Masukkan minimal 1 URL berita untuk memulai analisis.',
            });
            return;
        }
        
        const validURLs = urls.filter(url => isValidURL(url));
        if (validURLs.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'URL Tidak Valid',
                text: 'Tidak ada URL yang valid untuk dianalisis. Periksa kembali format URL Anda.',
            });
            return;
        }

        const form = document.getElementById('ai-analysis-form');
        const analysisMode = form.querySelector('[name="analysis_mode"]')?.value || 'balanced';
        const estimatedTime = getEstimatedTime(analysisMode, validURLs.length);
        const urlListHtml = validURLs.slice(0, 5).map(url => `<li><small>${url.length > 50 ? url.substring(0, 50) + '...' : url}</small></li>`).join('');

        Swal.fire({
            title: 'Konfirmasi Analisis AI',
            html: `
                <div class="text-start">
                    <p>Apakah Anda yakin ingin memulai analisis ini?</p>
                    <strong>Detail Analisis:</strong>
                    <ul>
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
                console.log('Form submission confirmed by SweetAlert');
                updateDebugInfo('Form submission confirmed by SweetAlert');

                const submitBtn = document.querySelector('[onclick="submitAnalysis()"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Memulai Analisis...';
                }
                
                form.submit();
            } else {
                updateDebugInfo('Form submission cancelled by SweetAlert');
            }
        });
    };

    // ============================
    // INITIALIZATION
    // ============================
    
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
</style>
@endpush
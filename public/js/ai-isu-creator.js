/**
 * AI Isu Creator JavaScript Module
 * Handles URL validation, progress tracking, and AI workflow
 */

class AIIsuCreator {
    constructor() {
        this.urls = [];
        this.sessionId = null;
        this.pollInterval = null;
        this.maxUrls = 5;
        this.apiEndpoints = {
            analyze: "/isu/ai-analyze",
            status: "/isu/ai-status/",
            preview: "/isu/ai-preview",
            store: "/isu/ai-store",
        };

        this.init();
    }

    init() {
        this.bindEvents();
        this.setupCSRF();
    }

    setupCSRF() {
        // Setup CSRF token for all AJAX requests
        const token = document.querySelector('meta[name="csrf-token"]');
        if (token) {
            this.csrfToken = token.getAttribute("content");
        }
    }

    bindEvents() {
        // URL input events
        document.addEventListener("input", (e) => {
            if (e.target.classList.contains("url-input")) {
                this.handleUrlInput(e.target);
            }
        });

        // Add URL button
        const addUrlBtn = document.getElementById("add-url-btn");
        if (addUrlBtn) {
            addUrlBtn.addEventListener("click", () => this.addUrlInput());
        }

        // Remove URL buttons
        document.addEventListener("click", (e) => {
            if (e.target.closest(".remove-url")) {
                e.preventDefault();
                this.removeUrlInput(e.target.closest(".url-input-item"));
            }
        });

        // Bulk URL parsing
        const parseBulkBtn = document.getElementById("parse-bulk-urls");
        if (parseBulkBtn) {
            parseBulkBtn.addEventListener("click", () => this.parseBulkUrls());
        }

        // Preview button
        const previewBtn = document.getElementById("preview-btn");
        if (previewBtn) {
            previewBtn.addEventListener("click", () => this.previewUrls());
        }

        // Validate URLs button
        const validateBtn = document.getElementById("validate-urls-btn");
        if (validateBtn) {
            validateBtn.addEventListener("click", () => this.validateUrls());
        }

        // Form submission
        const form = document.getElementById("ai-analysis-form");
        if (form) {
            form.addEventListener("submit", (e) => this.handleFormSubmit(e));
        }
    }

    // URL Management Methods
    addUrlInput(value = "") {
        const container = document.getElementById("url-input-container");
        const currentInputs = container.querySelectorAll(".url-input-item");

        if (currentInputs.length >= this.maxUrls) {
            this.showAlert(
                `Maksimal ${this.maxUrls} URL per analisis.`,
                "warning"
            );
            return;
        }

        const newItem = document.createElement("div");
        newItem.className = "url-input-item mb-2";
        newItem.innerHTML = `
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-link text-muted"></i>
                </span>
                <input type="url" name="urls[]" class="form-control url-input" 
                       placeholder="https://example.com/berita-${
                           currentInputs.length + 1
                       }" 
                       value="${value}">
                <button type="button" class="btn btn-outline-danger remove-url">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="url-status mt-1" style="display: none;"></div>
        `;

        container.appendChild(newItem);
        this.updateRemoveButtons();
        this.updateSubmitButton();

        if (value) {
            this.validateSingleUrl(newItem.querySelector(".url-input"));
        }

        // Focus on new input
        newItem.querySelector(".url-input").focus();
    }

    removeUrlInput(item) {
        const container = document.getElementById("url-input-container");
        const items = container.querySelectorAll(".url-input-item");

        if (items.length > 1) {
            item.remove();
            this.updateRemoveButtons();
            this.updateSubmitButton();
        }
    }

    updateRemoveButtons() {
        const container = document.getElementById("url-input-container");
        const items = container.querySelectorAll(".url-input-item");
        const removeButtons = container.querySelectorAll(".remove-url");

        removeButtons.forEach((btn) => {
            btn.style.display = items.length > 1 ? "block" : "none";
        });
    }

    updateSubmitButton() {
        const submitBtn = document.getElementById("submit-btn");
        if (!submitBtn) return;

        const validUrls = this.getValidUrls();
        submitBtn.disabled = validUrls.length === 0;

        if (validUrls.length > 0) {
            submitBtn.innerHTML =
                '<i class="fas fa-magic me-1"></i>Mulai Analisis AI';
            submitBtn.classList.remove("btn-secondary");
            submitBtn.classList.add("btn-primary");
        } else {
            submitBtn.innerHTML =
                '<i class="fas fa-magic me-1"></i>Masukkan URL terlebih dahulu';
            submitBtn.classList.remove("btn-primary");
            submitBtn.classList.add("btn-secondary");
        }
    }

    parseBulkUrls() {
        const bulkTextarea = document.getElementById("bulk-urls");
        const bulkText = bulkTextarea.value.trim();

        if (!bulkText) {
            this.showAlert("Masukkan URL terlebih dahulu", "warning");
            return;
        }

        const urls = bulkText
            .split("\n")
            .map((url) => url.trim())
            .filter((url) => url && this.isValidUrl(url));

        if (urls.length === 0) {
            this.showAlert("Tidak ada URL valid yang ditemukan", "danger");
            return;
        }

        if (urls.length > this.maxUrls) {
            this.showAlert(
                `Maksimal ${this.maxUrls} URL. Hanya ${this.maxUrls} URL pertama yang akan digunakan.`,
                "warning"
            );
        }

        // Clear existing inputs
        this.clearAllUrls();

        // Add new inputs for each URL
        urls.slice(0, this.maxUrls).forEach((url, index) => {
            if (index === 0) {
                // Use the first input
                const firstInput = document.querySelector(
                    'input[name="urls[]"]'
                );
                if (firstInput) {
                    firstInput.value = url;
                    this.validateSingleUrl(firstInput);
                }
            } else {
                this.addUrlInput(url);
            }
        });

        bulkTextarea.value = "";
        this.showAlert(
            `${Math.min(urls.length, this.maxUrls)} URL berhasil ditambahkan`,
            "success"
        );
    }

    clearAllUrls() {
        const container = document.getElementById("url-input-container");
        const items = container.querySelectorAll(".url-input-item");

        items.forEach((item, index) => {
            if (index > 0) {
                item.remove();
            } else {
                const input = item.querySelector(".url-input");
                const status = item.querySelector(".url-status");
                input.value = "";
                status.style.display = "none";
            }
        });

        this.updateRemoveButtons();
        this.updateSubmitButton();
    }

    // URL Validation Methods
    handleUrlInput(input) {
        clearTimeout(input.validationTimeout);

        input.validationTimeout = setTimeout(() => {
            this.validateSingleUrl(input);
        }, 500); // Debounce validation
    }

    validateSingleUrl(input) {
        const statusDiv = input
            .closest(".url-input-item")
            .querySelector(".url-status");
        const url = input.value.trim();

        if (!url) {
            statusDiv.style.display = "none";
            this.updateSubmitButton();
            return;
        }

        if (!this.isValidUrl(url)) {
            this.showUrlStatus(statusDiv, "Format URL tidak valid", "invalid");
            this.updateSubmitButton();
            return;
        }

        // Show checking status
        this.showUrlStatus(statusDiv, "Memeriksa URL...", "checking");

        // Simulate URL accessibility check (replace with actual check)
        setTimeout(() => {
            if (this.isAccessibleUrl(url)) {
                this.showUrlStatus(
                    statusDiv,
                    "URL valid dan dapat diakses",
                    "valid"
                );
            } else {
                this.showUrlStatus(
                    statusDiv,
                    "URL mungkin tidak dapat diakses",
                    "warning"
                );
            }
            this.updateSubmitButton();
        }, 1000);
    }

    showUrlStatus(statusDiv, message, type) {
        const icons = {
            valid: "fas fa-check-circle",
            invalid: "fas fa-times-circle",
            warning: "fas fa-exclamation-triangle",
            checking: "fas fa-spinner fa-spin",
        };

        statusDiv.innerHTML = `<i class="${icons[type]} me-1"></i>${message}`;
        statusDiv.className = `url-status ${type} mt-1`;
        statusDiv.style.display = "block";
    }

    isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }

    isAccessibleUrl(url) {
        // Simplified check - in production, this should be a real API call
        const blockedDomains = ["localhost", "127.0.0.1", "example.com"];
        try {
            const urlObj = new URL(url);
            return !blockedDomains.includes(urlObj.hostname);
        } catch (_) {
            return false;
        }
    }

    getAllUrls() {
        const inputs = document.querySelectorAll('input[name="urls[]"]');
        return Array.from(inputs)
            .map((input) => input.value.trim())
            .filter((url) => url);
    }

    getValidUrls() {
        return this.getAllUrls().filter((url) => this.isValidUrl(url));
    }

    // Preview and Validation Methods
    async previewUrls() {
        const urls = this.getValidUrls();

        if (urls.length === 0) {
            this.showAlert(
                "Masukkan minimal 1 URL terlebih dahulu.",
                "warning"
            );
            return;
        }

        const previewCard = document.getElementById("preview-card");
        const previewContent = document.getElementById("preview-content");

        if (!previewCard || !previewContent) return;

        previewContent.innerHTML = this.getLoadingHTML("Memuat preview...");
        previewCard.style.display = "block";

        try {
            const response = await this.makeApiCall(this.apiEndpoints.preview, {
                urls: urls.slice(0, 2), // Preview first 2 URLs only
            });

            if (response.success) {
                this.displayPreviewResults(previewContent, response.previews);
            } else {
                throw new Error(response.message || "Gagal memuat preview");
            }
        } catch (error) {
            previewContent.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        }
    }

    displayPreviewResults(container, previews) {
        let html = "";

        previews.forEach((preview) => {
            const statusClass = preview.suitable?.suitable ? "" : "error";
            const statusIcon = preview.suitable?.suitable
                ? "check"
                : "exclamation-triangle";
            const statusColor = preview.suitable?.suitable
                ? "success"
                : "warning";

            html += `
                <div class="preview-item ${statusClass} mb-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="mb-0">${this.escapeHtml(preview.title)}</h6>
                        <i class="fas fa-${statusIcon} text-${statusColor}"></i>
                    </div>
                    <small class="text-muted d-block mb-2">
                        <i class="fas fa-globe me-1"></i>${preview.domain}
                    </small>
                    <p class="small mb-2">${this.escapeHtml(
                        preview.excerpt
                    )}</p>
                    ${
                        !preview.suitable?.suitable
                            ? `<div class="alert alert-warning small py-2 mb-0">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            ${
                                preview.suitable?.issues?.join(", ") ||
                                "Konten mungkin tidak cocok untuk analisis"
                            }
                        </div>`
                            : '<div class="text-success small"><i class="fas fa-check me-1"></i>Konten cocok untuk analisis</div>'
                    }
                </div>
            `;
        });

        container.innerHTML = html;
    }

    async validateUrls() {
        const urls = this.getAllUrls();

        if (urls.length === 0) {
            this.showAlert(
                "Masukkan minimal 1 URL terlebih dahulu.",
                "warning"
            );
            return;
        }

        const modal = new bootstrap.Modal(
            document.getElementById("validationModal")
        );
        const results = document.getElementById("validation-results");

        if (!modal || !results) return;

        results.innerHTML = this.getLoadingHTML("Memvalidasi URLs...");
        modal.show();

        try {
            // Validate each URL
            const validationPromises = urls.map((url) =>
                this.validateUrlAccessibility(url)
            );
            const validationResults = await Promise.all(validationPromises);

            this.displayValidationResults(results, urls, validationResults);
        } catch (error) {
            results.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        }
    }

    async validateUrlAccessibility(url) {
        // Simulate URL validation - replace with actual implementation
        return new Promise((resolve) => {
            setTimeout(() => {
                resolve({
                    url: url,
                    accessible: this.isAccessibleUrl(url),
                    status: Math.random() > 0.2 ? 200 : 404,
                    responseTime: Math.floor(Math.random() * 2000) + 500,
                });
            }, Math.random() * 1000 + 500);
        });
    }

    displayValidationResults(container, urls, results) {
        let html = "";

        results.forEach((result, index) => {
            const isValid = result.accessible && result.status === 200;
            const statusBadge = isValid
                ? '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Valid</span>'
                : '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Error</span>';

            html += `
                <div class="d-flex justify-content-between align-items-start border-bottom py-3">
                    <div class="flex-grow-1 me-3">
                        <strong>URL ${index + 1}:</strong>
                        <br>
                        <small class="text-muted">${this.escapeHtml(
                            result.url
                        )}</small>
                        <br>
                        <small class="text-muted">
                            Status: ${result.status} | Response: ${
                result.responseTime
            }ms
                        </small>
                    </div>
                    <div>
                        ${statusBadge}
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    // Form Submission and Progress Tracking
    async handleFormSubmit(e) {
        e.preventDefault();

        const form = e.target;
        const submitBtn = document.getElementById("submit-btn");
        const validUrls = this.getValidUrls();

        if (validUrls.length === 0) {
            this.showAlert(
                "Masukkan minimal 1 URL valid terlebih dahulu.",
                "danger"
            );
            return;
        }

        // Show loading state
        this.setSubmitButtonLoading(submitBtn, true);
        this.showProgressCard();

        try {
            // Submit form
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: "POST",
                body: formData,
                headers: {
                    "X-CSRF-TOKEN": this.csrfToken,
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            // Check if response is redirect or JSON
            const contentType = response.headers.get("content-type");

            if (contentType && contentType.includes("application/json")) {
                const data = await response.json();
                if (data.success) {
                    // Start polling for status if we have a session ID
                    if (data.session_id) {
                        this.sessionId = data.session_id;
                        this.startProgressPolling();
                    } else {
                        // Direct redirect
                        window.location.href = data.redirect_url;
                    }
                } else {
                    throw new Error(data.message || "Analisis gagal dimulai");
                }
            } else {
                // Handle redirect response
                window.location.href = response.url;
            }
        } catch (error) {
            this.setSubmitButtonLoading(submitBtn, false);
            this.hideProgressCard();
            this.showAlert("Error: " + error.message, "danger");
        }
    }

    setSubmitButtonLoading(button, isLoading) {
        if (isLoading) {
            button.disabled = true;
            button.innerHTML =
                '<i class="fas fa-spinner fa-spin me-1"></i>Memulai Analisis...';
        } else {
            button.disabled = false;
            button.innerHTML =
                '<i class="fas fa-magic me-1"></i>Mulai Analisis AI';
        }
    }

    showProgressCard() {
        const progressCard = document.getElementById("progress-card");
        if (progressCard) {
            progressCard.style.display = "block";
            this.updateProgress(10, "Memulai analisis...");
        }
    }

    hideProgressCard() {
        const progressCard = document.getElementById("progress-card");
        if (progressCard) {
            progressCard.style.display = "none";
        }
    }

    updateProgress(percentage, status, details = "") {
        const progressBar = document.getElementById("analysis-progress");
        const progressStatus = document.getElementById("progress-status");
        const progressDetails = document.getElementById("progress-details");

        if (progressBar) {
            progressBar.style.width = percentage + "%";
            progressBar.setAttribute("aria-valuenow", percentage);
        }

        if (progressStatus) {
            progressStatus.textContent = status;
        }

        if (progressDetails) {
            progressDetails.textContent = details;
        }
    }

    startProgressPolling() {
        if (!this.sessionId) return;

        this.updateProgress(20, "Mengekstrak konten dari URL...");

        this.pollInterval = setInterval(async () => {
            try {
                const response = await this.makeApiCall(
                    this.apiEndpoints.status + this.sessionId
                );

                if (response.status === "completed") {
                    this.updateProgress(
                        100,
                        "Analisis selesai!",
                        "Mengarahkan ke hasil..."
                    );
                    clearInterval(this.pollInterval);

                    setTimeout(() => {
                        window.location.href = `/isu/ai-results/${this.sessionId}`;
                    }, 1000);
                } else if (response.status === "failed") {
                    clearInterval(this.pollInterval);
                    this.updateProgress(
                        0,
                        "Analisis gagal",
                        response.error_message || ""
                    );
                    this.showAlert(
                        "Analisis gagal: " +
                            (response.error_message || "Unknown error"),
                        "danger"
                    );
                    this.setSubmitButtonLoading(
                        document.getElementById("submit-btn"),
                        false
                    );
                } else if (response.status === "processing") {
                    const progress = response.progress || 50;
                    this.updateProgress(
                        progress,
                        "Menganalisis konten dengan AI...",
                        "Proses ini memerlukan 1-3 menit"
                    );
                } else {
                    // Still pending
                    this.updateProgress(
                        30,
                        "Memproses URL...",
                        "Mengekstrak dan membersihkan konten"
                    );
                }
            } catch (error) {
                console.error("Error polling status:", error);
                clearInterval(this.pollInterval);
                this.showAlert(
                    "Error saat mengecek status: " + error.message,
                    "danger"
                );
                this.setSubmitButtonLoading(
                    document.getElementById("submit-btn"),
                    false
                );
            }
        }, 3000); // Poll every 3 seconds
    }

    // Utility Methods
    async makeApiCall(url, data = null) {
        const options = {
            method: data ? "POST" : "GET",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": this.csrfToken,
                Accept: "application/json",
            },
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }

    showAlert(message, type = "info") {
        // Create and show alert
        const alertDiv = document.createElement("div");
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText =
            "top: 20px; right: 20px; z-index: 9999; max-width: 400px;";
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(alertDiv);

        // Auto dismiss after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    getLoadingHTML(message) {
        return `
            <div class="text-center py-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mb-0">${message}</p>
            </div>
        `;
    }

    escapeHtml(text) {
        const div = document.createElement("div");
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
    // Only initialize on AI create page
    if (document.getElementById("ai-analysis-form")) {
        new AIIsuCreator();
    }
});

// Export for use in other scripts
window.AIIsuCreator = AIIsuCreator;

// resources/js/tone-analyzer.js
class ToneAnalyzer {
    constructor() {
        this.isAnalyzing = false;
        this.debounceTimer = null;
        this.confidenceThreshold = 70; // Minimum confidence untuk auto-select
        this.initializeElements();
        this.bindEvents();
    }

    initializeElements() {
        this.rangkumanEditor = null; // Will be set when CKEditor is ready
        this.toneRadios = document.querySelectorAll(".tone-radio");
        this.analyzeButton = this.createAnalyzeButton();
        this.confidenceIndicator = this.createConfidenceIndicator();
        this.explanationBox = this.createExplanationBox();

        // Insert UI elements into the tone section
        this.insertAnalysisUI();
    }

    createAnalyzeButton() {
        const button = document.createElement("button");
        button.type = "button";
        button.className = "btn btn-outline-primary btn-sm ms-2";
        button.innerHTML = '<i class="fas fa-robot me-1"></i> Analisis AI';
        button.title =
            "Gunakan AI untuk menganalisis tone berdasarkan rangkuman";
        return button;
    }

    createConfidenceIndicator() {
        const container = document.createElement("div");
        container.className = "mt-2";
        container.innerHTML = `
            <div class="confidence-indicator" style="display: none;">
                <div class="d-flex align-items-center">
                    <small class="text-muted me-2">Tingkat Keyakinan AI:</small>
                    <div class="progress flex-grow-1 me-2" style="height: 8px;">
                        <div class="progress-bar confidence-bar" style="width: 0%"></div>
                    </div>
                    <small class="confidence-text">0%</small>
                </div>
            </div>
        `;
        return container;
    }

    createExplanationBox() {
        const container = document.createElement("div");
        container.className = "mt-2";
        container.innerHTML = `
            <div class="ai-explanation" style="display: none;">
                <div class="alert alert-info alert-sm py-2">
                    <i class="fas fa-lightbulb me-1"></i>
                    <small class="explanation-text"></small>
                </div>
            </div>
        `;
        return container;
    }

    insertAnalysisUI() {
        const toneSection = document.querySelector(
            ".tone-segment-rounded"
        )?.parentElement;
        if (toneSection) {
            // Insert analyze button
            const labelElement = toneSection.querySelector(".form-label");
            if (labelElement) {
                labelElement.appendChild(this.analyzeButton);
            }

            // Insert indicators after tone segment
            toneSection.appendChild(this.confidenceIndicator);
            toneSection.appendChild(this.explanationBox);
        }
    }

    bindEvents() {
        // Event untuk tombol analisis
        this.analyzeButton.addEventListener("click", () => {
            this.performAnalysis();
        });

        // Auto-analysis dengan debouncing ketika rangkuman berubah
        // Akan di-bind ketika CKEditor ready
        this.setupAutoAnalysis();

        // Event ketika user manual memilih tone (untuk tracking)
        this.toneRadios.forEach((radio) => {
            radio.addEventListener("change", () => {
                if (radio.checked) {
                    this.hideAnalysisResults();
                    this.trackUserAction("manual_tone_selection", radio.value);
                }
            });
        });
    }

    setupAutoAnalysis() {
        // Setup akan dipanggil ketika CKEditor sudah ready
        document.addEventListener("ckeditor-ready", () => {
            if (window.editors && window.editors.rangkuman) {
                this.rangkumanEditor = window.editors.rangkuman;

                this.rangkumanEditor.model.document.on("change:data", () => {
                    // Debounce auto-analysis
                    clearTimeout(this.debounceTimer);
                    this.debounceTimer = setTimeout(() => {
                        const content = this.rangkumanEditor.getData();
                        const textContent = this.stripHtml(content);

                        if (textContent.length > 50) {
                            // Minimum length untuk auto-analysis
                            this.showAutoAnalysisHint();
                        }
                    }, 2000); // 2 detik delay
                });
            }
        });
    }

    async performAnalysis(isAuto = false) {
        if (this.isAnalyzing) return;

        try {
            const rangkumanContent = this.getRangkumanContent();

            if (!rangkumanContent || rangkumanContent.length < 10) {
                this.showNotification(
                    "Harap isi rangkuman terlebih dahulu (minimal 10 karakter)",
                    "warning"
                );
                return;
            }

            this.setAnalyzingState(true);

            const response = await fetch("/api/tone-analysis", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                    Accept: "application/json",
                },
                body: JSON.stringify({
                    rangkuman: rangkumanContent,
                }),
            });

            const result = await response.json();

            if (result.success) {
                this.displayAnalysisResult(result.data, isAuto);
                this.trackUserAction(
                    "ai_analysis_success",
                    result.data.tone_id
                );
            } else {
                throw new Error(result.message || "Analisis gagal");
            }
        } catch (error) {
            console.error("Tone analysis error:", error);
            this.showNotification(
                "Gagal menganalisis tone: " + error.message,
                "danger"
            );
            this.trackUserAction("ai_analysis_error", error.message);
        } finally {
            this.setAnalyzingState(false);
        }
    }

    getRangkumanContent() {
        if (this.rangkumanEditor) {
            return this.stripHtml(this.rangkumanEditor.getData());
        }

        // Fallback untuk textarea biasa
        const textarea = document.getElementById("rangkuman");
        return textarea ? textarea.value : "";
    }

    stripHtml(html) {
        const tmp = document.createElement("div");
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || "";
    }

    setAnalyzingState(analyzing) {
        this.isAnalyzing = analyzing;

        if (analyzing) {
            this.analyzeButton.disabled = true;
            this.analyzeButton.innerHTML =
                '<span class="spinner-border spinner-border-sm me-1"></span>Menganalisis...';
        } else {
            this.analyzeButton.disabled = false;
            this.analyzeButton.innerHTML =
                '<i class="fas fa-robot me-1"></i> Analisis AI';
        }
    }

    displayAnalysisResult(data, isAuto = false) {
        // Update confidence indicator
        this.updateConfidenceIndicator(data.confidence);

        // Show explanation
        this.showExplanation(data.explanation);

        // Auto-select tone jika confidence tinggi atau bukan auto-analysis
        if (!isAuto || data.confidence >= this.confidenceThreshold) {
            this.selectTone(data.tone_id, data.tone_name);

            if (isAuto && data.confidence >= this.confidenceThreshold) {
                this.showNotification(
                    `AI merekomendasikan tone "${data.tone_name}" dengan keyakinan ${data.confidence}%`,
                    "success"
                );
            }
        } else {
            // Jika confidence rendah, hanya tampilkan rekomendasi
            this.highlightRecommendedTone(data.tone_id);
            this.showNotification(
                `AI merekomendasikan tone "${data.tone_name}" (keyakinan: ${data.confidence}%). Klik untuk menggunakan.`,
                "info"
            );
        }
    }

    selectTone(toneId, toneName) {
        const targetRadio = document.getElementById(`tone_${toneId}`);
        if (targetRadio) {
            targetRadio.checked = true;
            targetRadio.dispatchEvent(new Event("change"));

            // Update visual styling
            this.updateSegmentColor();
        }
    }

    highlightRecommendedTone(toneId) {
        // Remove previous highlights
        document.querySelectorAll(".tone-recommendation").forEach((el) => {
            el.classList.remove("tone-recommendation");
        });

        // Add highlight to recommended tone
        const recommendedLabel = document.querySelector(
            `label[for="tone_${toneId}"]`
        );
        if (recommendedLabel) {
            recommendedLabel.classList.add("tone-recommendation");

            // Remove highlight after 5 seconds
            setTimeout(() => {
                recommendedLabel.classList.remove("tone-recommendation");
            }, 5000);
        }
    }

    updateConfidenceIndicator(confidence) {
        const indicator = this.confidenceIndicator.querySelector(
            ".confidence-indicator"
        );
        const progressBar = indicator.querySelector(".confidence-bar");
        const confidenceText = indicator.querySelector(".confidence-text");

        // Determine color based on confidence level
        let colorClass = "bg-danger";
        if (confidence >= 70) colorClass = "bg-success";
        else if (confidence >= 50) colorClass = "bg-warning";

        progressBar.className = `progress-bar ${colorClass}`;
        progressBar.style.width = `${confidence}%`;
        confidenceText.textContent = `${confidence}%`;

        indicator.style.display = "block";

        // Auto-hide after 10 seconds if confidence is very low
        if (confidence < 30) {
            setTimeout(() => {
                indicator.style.display = "none";
            }, 10000);
        }
    }

    showExplanation(explanation) {
        const explanationContainer =
            this.explanationBox.querySelector(".ai-explanation");
        const explanationText =
            explanationContainer.querySelector(".explanation-text");

        explanationText.textContent = explanation;
        explanationContainer.style.display = "block";

        // Auto-hide after 15 seconds
        setTimeout(() => {
            explanationContainer.style.display = "none";
        }, 15000);
    }

    hideAnalysisResults() {
        this.confidenceIndicator.querySelector(
            ".confidence-indicator"
        ).style.display = "none";
        this.explanationBox.querySelector(".ai-explanation").style.display =
            "none";
    }

    showAutoAnalysisHint() {
        if (this.isAnalyzing) return;

        // Show subtle hint that auto-analysis is available
        this.analyzeButton.classList.add("pulse-animation");
        setTimeout(() => {
            this.analyzeButton.classList.remove("pulse-animation");
        }, 2000);
    }

    updateSegmentColor() {
        // Menggunakan fungsi yang sudah ada di file asli
        if (typeof updateSegmentColor === "function") {
            updateSegmentColor();
        }
    }

    showNotification(message, type = "info") {
        // Menggunakan fungsi notifikasi yang sudah ada
        if (typeof showNotification === "function") {
            showNotification(message, type);
        } else {
            // Fallback sederhana
            alert(message);
        }
    }

    trackUserAction(action, value) {
        // Analytics tracking
        if (typeof gtag !== "undefined") {
            gtag("event", action, {
                value: value,
                custom_parameter: "tone_analysis",
            });
        }

        // Internal logging
        console.log("User action tracked:", action, value);
    }
}

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
    // Wait for CKEditor to be ready before initializing tone analyzer
    setTimeout(() => {
        window.toneAnalyzer = new ToneAnalyzer();

        // Trigger custom event when everything is ready
        document.dispatchEvent(new CustomEvent("ckeditor-ready"));
    }, 1000);
});

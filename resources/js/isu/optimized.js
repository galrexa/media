document.addEventListener("DOMContentLoaded", function () {
    // ===== INISIALISASI KOMPONEN =====

    // Inisialisasi tooltips
    initTooltips();

    // Inisialisasi sorting tabel
    initTableSorting();

    // Inisialisasi checkbox handler
    initCheckboxHandlers();

    // Inisialisasi aksi massal
    initMassActions();

    // Inisialisasi modal penolakan
    initRejectionModal();

    // Auto-submit filter form
    initAutoSubmitFilters();

    // ===== FUNGSI UTAMA =====

    // Inisialisasi tooltips
    function initTooltips() {
        const tooltipTriggerList = document.querySelectorAll(
            '[data-bs-toggle="tooltip"]'
        );
        tooltipTriggerList.forEach((el) => {
            new bootstrap.Tooltip(el, {
                html: true,
                boundary: document.body,
            });
        });
    }

    // Inisialisasi fungsi sorting tabel
    function initTableSorting() {
        const sortableHeaders = document.querySelectorAll(".sortable");

        sortableHeaders.forEach((header) => {
            header.addEventListener("click", function () {
                const sort = this.getAttribute("data-sort");
                let direction = "asc";

                // Toggle direction jika kolom yang sama
                if (this.getAttribute("data-direction") === "asc") {
                    direction = "desc";
                }

                // Update URL dengan parameter sorting
                const url = new URL(window.location.href);
                url.searchParams.set("sort", sort);
                url.searchParams.set("direction", direction);

                // Redirect ke URL baru
                window.location.href = url.toString();
            });
        });
    }

    // Inisialisasi checkbox handlers
    function initCheckboxHandlers() {
        // Checkbox "Select All"
        const selectAllCheckbox = document.getElementById("select-all");
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener("change", function () {
                const isChecked = this.checked;
                const checkboxes = document.querySelectorAll(".isu-checkbox");

                checkboxes.forEach((checkbox) => {
                    checkbox.checked = isChecked;
                });

                updateSelectedCount();
            });
        }

        // Checkbox individual
        const checkboxes = document.querySelectorAll(".isu-checkbox");
        checkboxes.forEach((checkbox) => {
            checkbox.addEventListener("change", updateSelectedCount);
        });
    }

    // Fungsi untuk mengupdate jumlah dan tampilan item terpilih
    function updateSelectedCount() {
        const selectedCheckboxes = document.querySelectorAll(
            ".isu-checkbox:checked"
        );
        const selectedCount = selectedCheckboxes.length;
        const selectedCountElement = document.getElementById("selected-count");
        const selectedActionsElement =
            document.querySelector(".selected-actions");

        // Update counter
        if (selectedCountElement) {
            selectedCountElement.textContent = selectedCount;
        }

        // Show/hide actions
        if (selectedActionsElement) {
            selectedActionsElement.style.display =
                selectedCount > 0 ? "block" : "none";
        }

        // Update "select all" state
        const selectAllCheckbox = document.getElementById("select-all");
        const allCheckboxes = document.querySelectorAll(".isu-checkbox");

        if (selectAllCheckbox && allCheckboxes.length > 0) {
            selectAllCheckbox.checked = selectedCount === allCheckboxes.length;
            selectAllCheckbox.indeterminate =
                selectedCount > 0 && selectedCount < allCheckboxes.length;
        }
    }

    // Inisialisasi handlers untuk aksi massal
    function initMassActions() {
        const actionButtons = document.querySelectorAll("[data-action]");

        actionButtons.forEach((button) => {
            button.addEventListener("click", function (e) {
                e.preventDefault();
                console.log(
                    "Action button clicked:",
                    this.getAttribute("data-action")
                );

                const action = this.getAttribute("data-action");
                const selectedIds = collectSelectedIds();

                if (selectedIds.length === 0) {
                    showNotification(
                        "Peringatan",
                        "Pilih minimal satu isu untuk diproses",
                        "warning"
                    );
                    return;
                }

                // Set nilai input di form
                document.getElementById("mass-action").value = action;
                // Konversi ke format JSON untuk controller
                document.getElementById("selected-ids").value =
                    JSON.stringify(selectedIds);

                console.log("Form data set:", {
                    action: action,
                    selectedIds: JSON.stringify(selectedIds),
                });

                // Jika aksi adalah reject, tampilkan modal
                if (action === "reject") {
                    return; // Modal ditampilkan oleh data-bs-toggle
                }

                // Konfirmasi tindakan
                if (
                    confirm(
                        `Apakah Anda yakin ingin ${getActionLabel(action)} ${
                            selectedIds.length
                        } isu yang dipilih?`
                    )
                ) {
                    console.log("Submitting form...");
                    document.getElementById("mass-action-form").submit();
                }
            });
        });
    }

    // Mengumpulkan ID isu yang dipilih
    function collectSelectedIds() {
        const selectedIds = [];
        document
            .querySelectorAll(".isu-checkbox:checked")
            .forEach((checkbox) => {
                selectedIds.push(checkbox.value);
            });
        return selectedIds;
    }

    // Mendapatkan label untuk aksi
    function getActionLabel(action) {
        switch (action) {
            case "delete":
                return "menghapus";
            case "send-to-verif1":
                return "mengirim ke Verifikator 1";
            case "send-to-verif2":
                return "mengirim ke Verifikator 2";
            case "publish":
                return "mempublikasikan";
            case "reject":
                return "menolak";
            case "export":
                return "mengekspor";
            default:
                return "memproses";
        }
    }

    // Inisialisasi handler modal penolakan
    function initRejectionModal() {
        // Submit alasan penolakan
        const confirmRejectButton = document.getElementById("confirm-reject");
        if (confirmRejectButton) {
            confirmRejectButton.addEventListener("click", function () {
                const reasonInput = document.getElementById(
                    "rejection-reason-input"
                );
                const reason = reasonInput.value.trim();

                if (!reason) {
                    reasonInput.classList.add("is-invalid");
                    return;
                }

                document.getElementById("rejection-reason").value = reason;
                document.getElementById("mass-action-form").submit();
            });
        }

        // Reset validasi saat input
        const reasonInput = document.getElementById("rejection-reason-input");
        if (reasonInput) {
            reasonInput.addEventListener("input", function () {
                if (this.value.trim()) {
                    this.classList.remove("is-invalid");
                }
            });
        }
    }

    // Inisialisasi auto-submit filter
    function initAutoSubmitFilters() {
        const autoSubmitFields = document.querySelectorAll(
            'select[name="status"], select[name="jenis_isu"]'
        );
        autoSubmitFields.forEach((field) => {
            field.addEventListener("change", function () {
                document.getElementById("searchForm").submit();
            });
        });
    }

    // ===== FUNGSI GLOBAL =====

    // Fungsi untuk menampilkan notifikasi
    window.showNotification = function (title, message, type = "info") {
        const notificationId = "notification-" + Date.now();
        const notification = document.createElement("div");
        notification.id = notificationId;
        notification.className = "toast show position-fixed bottom-0 end-0 m-3";
        notification.setAttribute("role", "alert");
        notification.setAttribute("aria-live", "assertive");
        notification.setAttribute("aria-atomic", "true");
        notification.style.zIndex = "9999";

        notification.innerHTML = `
            <div class="toast-header bg-${type} text-white">
                <strong class="me-auto">${title}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        `;

        document.body.appendChild(notification);

        // Hapus notifikasi setelah 5 detik
        setTimeout(() => {
            const notificationElement = document.getElementById(notificationId);
            if (notificationElement) {
                notificationElement.style.opacity = "0";
                notificationElement.style.transition = "opacity 0.3s ease";

                setTimeout(() => {
                    if (notificationElement.parentNode) {
                        notificationElement.parentNode.removeChild(
                            notificationElement
                        );
                    }
                }, 300);
            }
        }, 5000);
    };

    // Fungsi untuk menghapus isu dengan AJAX
    window.deleteIsu = function (event, element) {
        event.preventDefault();

        if (!confirm("Apakah Anda yakin ingin menghapus isu ini?")) {
            return;
        }

        console.log("Deleting isu at URL:", element.getAttribute("data-url"));

        const url = element.getAttribute("data-url");
        const token = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        // Buat formData untuk method spoofing
        const formData = new FormData();
        formData.append("_method", "DELETE");
        formData.append("_token", token);

        fetch(url, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": token,
                Accept: "application/json",
            },
            body: formData,
        })
            .then((response) => {
                console.log("Response status:", response.status);
                if (!response.ok) {
                    throw new Error(`HTTP Error: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                console.log("Response data:", data);
                if (data.success) {
                    // Animasi fadeout baris tabel
                    const row = element.closest("tr");
                    row.style.opacity = "0";
                    row.style.transition = "all 0.5s ease";

                    setTimeout(() => {
                        row.remove();
                        showNotification(
                            "Berhasil",
                            data.message || "Isu berhasil dihapus.",
                            "success"
                        );
                        updateRowCounts();
                    }, 500);
                } else {
                    showNotification(
                        "Gagal",
                        data.message || "Gagal menghapus isu.",
                        "danger"
                    );
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                showNotification(
                    "Error",
                    "Terjadi kesalahan saat menghapus isu: " + error.message,
                    "danger"
                );
            });
    };

    // Update jumlah baris setelah penghapusan
    function updateRowCounts() {
        // Update statistik jika ada
        const totalBadge = document.querySelector(
            '.badge[data-status="total"]'
        );
        if (totalBadge) {
            const currentCount = parseInt(
                totalBadge.textContent.replace(/[^\d]/g, "")
            );
            if (!isNaN(currentCount)) {
                totalBadge.textContent = totalBadge.textContent.replace(
                    currentCount,
                    currentCount - 1
                );
            }
        }

        // Periksa jika tabel kosong
        const tableBody = document.querySelector("tbody");
        if (tableBody && tableBody.querySelectorAll("tr").length === 0) {
            const tableContainer = tableBody.closest(".table-responsive");
            if (tableContainer) {
                // Tambahkan empty state jika semua baris telah dihapus
                const emptyState = document.createElement("div");
                emptyState.className = "empty-state";
                emptyState.innerHTML = `
                    <i class="fas fa-search-minus"></i>
                    <h5 class="mt-3">Tidak ada isu ditemukan</h5>
                    <p>Silakan buat isu baru atau ubah filter pencarian Anda.</p>
                    <a href="${
                        document.querySelector('a[href*="isu.create"]')?.href ||
                        "#"
                    }" class="btn btn-outline-primary mt-3">
                        <i class="fas fa-plus-circle me-2"></i> Tambah Isu Baru
                    </a>
                `;
                tableContainer.parentNode.replaceChild(
                    emptyState,
                    tableContainer
                );
            }
        }
    }
});

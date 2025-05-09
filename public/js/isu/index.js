/**
 * Modul JavaScript untuk halaman index isu
 * File ini berisi fungsi-fungsi untuk mengelola tab, checkbox, dan aksi massal
 */

/**
 * Utility function untuk menampilkan toast notification dengan SweetAlert
 * @param {string} message - Pesan yang akan ditampilkan
 * @param {string} type - Tipe notifikasi ('success', 'error', 'warning', 'info')
 */
function showToast(message, type = "info") {
    const Toast = Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        customClass: {
            container: "toast-container-admin",
        },
        didOpen: (toast) => {
            toast.addEventListener("mouseenter", Swal.stopTimer);
            toast.addEventListener("mouseleave", Swal.resumeTimer);
        },
    });

    Toast.fire({
        icon: type,
        title: message,
    });
}

/**
 * Utility function untuk menampilkan alert standar dengan SweetAlert
 * @param {string} title - Judul alert
 * @param {string} message - Pesan yang akan ditampilkan
 * @param {string} type - Tipe alert ('success', 'error', 'warning', 'info')
 * @param {Function} callback - Fungsi yang akan dijalankan setelah alert ditutup (opsional)
 */
function showAlert(title, message, type = "info", callback = null) {
    Swal.fire({
        title: title,
        text: message,
        icon: type,
        confirmButtonColor: type === "error" ? "#dc3545" : "#3085d6",
        customClass: {
            container: "toast-container-admin",
        },
    }).then(() => {
        if (callback && typeof callback === "function") {
            callback();
        }
    });
}

/**
 * Utility function untuk menampilkan konfirmasi dengan SweetAlert
 * @param {string} title - Judul konfirmasi
 * @param {string} message - Pesan yang akan ditampilkan
 * @param {Function} confirmCallback - Fungsi yang akan dijalankan jika user mengkonfirmasi
 * @param {string} type - Tipe konfirmasi ('question', 'warning', etc.)
 * @param {string} confirmButtonText - Teks untuk tombol konfirmasi
 * @param {string} confirmButtonColor - Warna untuk tombol konfirmasi
 */
function showConfirm(
    title,
    message,
    confirmCallback,
    type = "question",
    confirmButtonText = "Ya",
    confirmButtonColor = "#3085d6"
) {
    // Pastikan parameter yang benar diteruskan ke SweetAlert
    Swal.fire({
        title: title,
        text: message,
        icon: type,
        showCancelButton: true,
        confirmButtonColor: confirmButtonColor,
        cancelButtonColor: "#6c757d",
        confirmButtonText: confirmButtonText,
        cancelButtonText: "Batal",
        customClass: {
            container: "toast-container-admin",
        },
    }).then((result) => {
        if (result.isConfirmed && typeof confirmCallback === "function") {
            confirmCallback();
        }
    });
}

/**
 * Utility function untuk menampilkan loading dengan SweetAlert
 * @param {string} message - Pesan loading
 * @returns {Function} - Fungsi untuk menutup loading
 */
function showLoading(message = "Memproses...") {
    Swal.fire({
        title: message,
        html: "Mohon tunggu sebentar...",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
        customClass: {
            container: "toast-container-admin",
        },
    });

    return () => {
        Swal.close();
    };
}

/**
 * Inisialisasi semua fungsi ketika DOM sudah siap
 */
document.addEventListener("DOMContentLoaded", function () {
    // Inisialisasi tooltip
    initTooltips();

    // Inisialisasi manajemen tab
    initTabs();

    // Inisialisasi checkbox selection
    initCheckboxManager();

    // Inisialisasi mass action buttons
    initMassActionButtons();

    // Inisialisasi rejection modal
    initRejectModal();
});

/**
 * Inisialisasi tooltip Bootstrap
 */
function initTooltips() {
    const tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            html: true,
        });
    });
}

/**
 * Inisialisasi manajemen tab dan menjaga state tab aktif
 */
function initTabs() {
    // Tangkap perubahan tab
    const tabs = document.querySelectorAll("#issuTabs button");

    tabs.forEach((tab) => {
        tab.addEventListener("click", function () {
            // Simpan tab yang aktif ke session storage
            sessionStorage.setItem("activeIsuTab", this.id);

            // Update hidden input dengan nilai tab aktif
            const tabValue = this.id.replace("-tab", "");
            document.getElementById("active_tab_input").value = tabValue;

            // Update juga di form pencarian
            if (document.getElementById("search_active_tab")) {
                document.getElementById("search_active_tab").value = tabValue;
            }
        });
    });

    // Aktifkan tab sesuai session storage atau parameter URL
    activateTabFromState();

    // Tangani klik pada header kolom untuk sorting
    initSorting();
}

/**
 * Aktivasi tab berdasarkan state atau parameter URL
 */
function activateTabFromState() {
    // Cek dan set tab aktif dari session storage
    const activeTab = sessionStorage.getItem("activeIsuTab");
    if (activeTab) {
        const tabToActivate = document.getElementById(activeTab);
        if (tabToActivate) {
            const tabInstance = new bootstrap.Tab(tabToActivate);
            tabInstance.show();
        }
    }

    // Override dengan parameter URL jika ada
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has("lainnya") && !urlParams.has("strategis")) {
        document.getElementById("lainnya-tab").click();
    }
}

/**
 * Inisialisasi sorting untuk kolom tabel
 */
function initSorting() {
    const sortableHeaders = document.querySelectorAll(".sortable");
    sortableHeaders.forEach((header) => {
        header.addEventListener("click", function () {
            const sortField = this.getAttribute("data-sort");
            let direction = "asc";

            // Jika sudah di-sort dengan field yang sama, balik arahnya
            if (
                new URLSearchParams(window.location.search).get("sort") ===
                sortField
            ) {
                direction =
                    new URLSearchParams(window.location.search).get(
                        "direction"
                    ) === "asc"
                        ? "desc"
                        : "asc";
            }

            // Buat URL baru dengan parameter sort
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set("sort", sortField);
            urlParams.set("direction", direction);

            // Pastikan parameter active_tab disertakan
            const activeTab = document
                .querySelector("#issuTabs button.active")
                .id.replace("-tab", "");
            urlParams.set("active_tab", activeTab);

            // Redirect ke URL dengan parameter sorting
            window.location.href = `${
                window.location.pathname
            }?${urlParams.toString()}`;
        });
    });
}

/**
 * Inisialisasi pengelolaan checkbox dan tindakan massal
 */
function initCheckboxManager() {
    // Mengelola Select All
    document.querySelectorAll(".select-all").forEach(function (checkbox) {
        checkbox.addEventListener("change", function () {
            const tabId = this.id.replace("select-all-", "");
            const checkboxes = document.querySelectorAll(
                `.isu-checkbox[data-tab="${tabId}"]`
            );

            checkboxes.forEach((box) => {
                box.checked = this.checked;
            });

            updateSelectedCount(tabId);
        });
    });

    // Mengelola checkbox individu
    document.querySelectorAll(".isu-checkbox").forEach(function (checkbox) {
        checkbox.addEventListener("change", function () {
            const tabId = this.getAttribute("data-tab");
            updateSelectedCount(tabId);

            // Update select all checkbox
            const totalCheckboxes = document.querySelectorAll(
                `.isu-checkbox[data-tab="${tabId}"]`
            ).length;
            const checkedCheckboxes = document.querySelectorAll(
                `.isu-checkbox[data-tab="${tabId}"]:checked`
            ).length;

            document.getElementById(`select-all-${tabId}`).checked =
                totalCheckboxes === checkedCheckboxes && totalCheckboxes > 0;
        });
    });

    // Inisialisasi counter untuk setiap tab
    updateSelectedCount("strategis");
    updateSelectedCount("lainnya");
}

/**
 * Update counter dan tampilkan/sembunyikan action bar
 * @param {string} tabId - ID tab yang diupdate ('strategis' atau 'lainnya')
 */
function updateSelectedCount(tabId) {
    const checkedBoxes = document.querySelectorAll(
        `.isu-checkbox[data-tab="${tabId}"]:checked`
    );
    const count = checkedBoxes.length;

    // Update counter
    const counterElement = document.getElementById(`selected-count-${tabId}`);
    if (counterElement) {
        counterElement.textContent = count;
    }

    // Tampilkan/sembunyikan action bar
    const actionBar = document.querySelector(`#${tabId} .selected-actions`);
    if (actionBar) {
        if (count > 0) {
            actionBar.style.display = "block";
        } else {
            actionBar.style.display = "none";
        }
    }
}

/**
 * Inisialisasi event handler untuk tombol aksi massal
 */
function initMassActionButtons() {
    document
        .querySelectorAll(
            '[id^="delete-selected-"], [id^="send-to-verif1-selected-"], [id^="send-to-verif2-selected-"], [id^="publish-selected-"], [id^="export-selected-"], [id^="reject-selected-"]'
        )
        .forEach(function (button) {
            button.addEventListener("click", function (e) {
                e.preventDefault();

                const action = this.getAttribute("data-action");
                const tabId = this.id.split("-").pop();
                const selectedIds = Array.from(
                    document.querySelectorAll(
                        `.isu-checkbox[data-tab="${tabId}"]:checked`
                    )
                ).map((el) => el.value);

                if (selectedIds.length === 0) {
                    showAlert(
                        "Tidak Ada Item Dipilih",
                        "Pilih setidaknya satu isu.",
                        "warning"
                    );
                    return;
                }

                // Handler untuk aksi reject
                if (action === "reject") {
                    const rejectModal = document.getElementById("rejectModal");
                    const modalInstance = new bootstrap.Modal(rejectModal);
                    rejectModal.setAttribute(
                        "data-selected-ids",
                        JSON.stringify(selectedIds)
                    );
                    modalInstance.show();
                    return;
                }

                // Konfigurasi untuk setiap aksi
                let config = {};
                switch (action) {
                    case "delete":
                        config = {
                            title: "Hapus Isu",
                            text: `Apakah Anda yakin ingin menghapus ${selectedIds.length} isu yang dipilih?`,
                            icon: "warning",
                            confirmButtonText: "Hapus",
                            confirmButtonColor: "#dc3545",
                        };
                        break;
                    case "send-to-verif1":
                        config = {
                            title: "Kirim ke Verifikator 1",
                            text: `Apakah Anda yakin ingin mengirim ${selectedIds.length} isu ke Verifikator 1?`,
                            icon: "question",
                            confirmButtonText: "Kirim",
                            confirmButtonColor: "#3085d6",
                        };
                        break;
                    case "send-to-verif2":
                        config = {
                            title: "Kirim ke Verifikator 2",
                            text: `Apakah Anda yakin ingin mengirim ${selectedIds.length} isu ke Verifikator 2?`,
                            icon: "question",
                            confirmButtonText: "Kirim",
                            confirmButtonColor: "#3085d6",
                        };
                        break;
                    case "publish":
                        config = {
                            title: "Publikasikan Isu",
                            text: `Apakah Anda yakin ingin mempublikasikan ${selectedIds.length} isu yang dipilih?`,
                            icon: "question",
                            confirmButtonText: "Publikasi",
                            confirmButtonColor: "#28a745",
                        };
                        break;
                    case "export":
                        // Langsung submit untuk export
                        document.getElementById("mass-action").value = action;
                        document.getElementById("selected-ids").value =
                            JSON.stringify(selectedIds);
                        document.getElementById("mass-action-form").submit();
                        return;
                    default:
                        return;
                }

                // Tampilkan konfirmasi
                showConfirm(
                    config.title,
                    config.text,
                    () => {
                        const closeLoading = showLoading("Memproses...");
                        document.getElementById("mass-action").value = action;
                        document.getElementById("selected-ids").value =
                            JSON.stringify(selectedIds);
                        setTimeout(() => {
                            document
                                .getElementById("mass-action-form")
                                .submit();
                            closeLoading();
                        }, 200);
                    },
                    config.icon,
                    config.confirmButtonText,
                    config.confirmButtonColor
                );
            });
        });
}

/**
 * Inisialisasi modal penolakan isu
 */
function initRejectModal() {
    const rejectModal = document.getElementById("rejectModal");
    if (!rejectModal) return;

    const modalInstance = new bootstrap.Modal(rejectModal);
    const confirmRejectBtn = document.getElementById("confirm-reject");
    const rejectionReasonInput = document.getElementById(
        "rejection-reason-input"
    );
    const massActionInput = document.getElementById("mass-action");
    const selectedIdsInput = document.getElementById("selected-ids");
    const rejectionReasonHiddenInput =
        document.getElementById("rejection-reason");
    const massActionForm = document.getElementById("mass-action-form");

    if (confirmRejectBtn) {
        confirmRejectBtn.addEventListener("click", function () {
            const rejectionReason = rejectionReasonInput.value.trim();

            if (rejectionReason.length < 10) {
                rejectionReasonInput.classList.add("is-invalid");
                showAlert(
                    "Validasi Gagal",
                    "Alasan penolakan minimal 10 karakter.",
                    "error"
                );
                return;
            }

            rejectionReasonInput.classList.remove("is-invalid");
            massActionInput.value = "reject";
            const selectedIds = JSON.parse(
                rejectModal.getAttribute("data-selected-ids") || "[]"
            );

            if (!selectedIds.length) {
                showAlert("Error", "Tidak ada isu yang dipilih.", "error");
                return;
            }

            selectedIdsInput.value = JSON.stringify(selectedIds);
            rejectionReasonHiddenInput.value = rejectionReason;

            modalInstance.hide();

            showConfirm(
                "Konfirmasi Penolakan",
                `Apakah Anda yakin ingin menolak ${selectedIds.length} isu yang dipilih?`,
                () => {
                    const closeLoading = showLoading("Memproses Penolakan...");
                    setTimeout(() => {
                        massActionForm.submit();
                        closeLoading();
                    }, 200);
                },
                "warning",
                "Tolak", // Text tombol yang benar
                "#dc3545" // Warna tombol
            );
        });
    }

    rejectModal.addEventListener("hidden.bs.modal", function () {
        rejectionReasonInput.value = "";
        rejectionReasonInput.classList.remove("is-invalid");
    });

    const hasServerValidationError = document.getElementById(
        "rejection-validation-error"
    );
    if (hasServerValidationError && hasServerValidationError.value === "true") {
        modalInstance.show();
        rejectionReasonInput.classList.add("is-invalid");
    }
}

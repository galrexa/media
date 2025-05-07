/**
 * Modul JavaScript untuk halaman index isu
 * File ini berisi fungsi-fungsi untuk mengelola tab, checkbox, dan aksi massal
 */

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

    initAllStatus();
});

function initAllStatus() {
    // Get active tab from hidden input
    const activeTabInput = document.getElementById("active_tab_input");

    // Setup tabs based on user role
    const isVerificator =
        document.body.classList.contains("role-verifikator1") ||
        document.body.classList.contains("role-verifikator2");

    if (isVerificator) {
        // For verificator, we only have one tab (semua)
        const tab = document.getElementById("semua-tab");
        if (tab) {
            // Tab is already active by default
            const tabPane = document.getElementById("semua");
            if (tabPane) tabPane.classList.add("show", "active");
        }
    } else {
        // For other users, handle tab switching between strategis and lainnya
        const activeTab = activeTabInput.value || "strategis";
        const tab = document.getElementById(`${activeTab}-tab`);

        if (tab) {
            tab.classList.add("active");
            const tabPane = document.getElementById(activeTab);
            if (tabPane) tabPane.classList.add("show", "active");
        }

        // Add event listeners to tabs
        document
            .querySelectorAll(".custom-tab-link")
            .forEach(function (tabLink) {
                tabLink.addEventListener("click", function (e) {
                    const tabId = this.id.replace("-tab", "");
                    document.getElementById("search_active_tab").value = tabId;
                    activeTabInput.value = tabId;
                });
            });
    }
}

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
    // Handler untuk tombol aksi massal
    document
        .querySelectorAll(
            '[id^="delete-selected-"], [id^="send-to-verif1-selected-"], [id^="send-to-verif2-selected-"], [id^="publish-selected-"], [id^="export-selected-"]'
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
                    alert("Silakan pilih setidaknya satu isu.");
                    return;
                }

                let confirmMessage = "";

                // Set confirmation message based on action
                switch (action) {
                    case "delete":
                        confirmMessage = `Apakah Anda yakin ingin menghapus ${selectedIds.length} isu yang dipilih?`;
                        break;
                    case "send-to-verif1":
                        confirmMessage = `Apakah Anda yakin ingin mengirim ${selectedIds.length} isu ke Verifikator 1?`;
                        break;
                    case "send-to-verif2":
                        confirmMessage = `Apakah Anda yakin ingin mengirim ${selectedIds.length} isu ke Verifikator 2?`;
                        break;
                    case "publish":
                        confirmMessage = `Apakah Anda yakin ingin mempublikasikan ${selectedIds.length} isu yang dipilih?`;
                        break;
                    case "export":
                        // No confirmation needed for export
                        break;
                    default:
                        return; // Invalid action
                }

                if (confirmMessage && !confirm(confirmMessage)) {
                    return;
                }

                // Set form values
                document.getElementById("mass-action").value = action;
                document.getElementById("selected-ids").value =
                    JSON.stringify(selectedIds);

                // Submit form
                document.getElementById("mass-action-form").submit();
            });
        });
}

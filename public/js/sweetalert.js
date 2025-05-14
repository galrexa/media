// File: resources/js/sweetalert.js
import Swal from "sweetalert2";

// Buat SweetAlert tersedia secara global
window.Swal = Swal;

// Setup event listener untuk elemen dengan atribut data-confirm
document.addEventListener("DOMContentLoaded", function () {
    // Tangani delete confirmation
    document.querySelectorAll("[data-confirm]").forEach(function (element) {
        element.addEventListener("click", function (e) {
            e.preventDefault();

            const message =
                this.getAttribute("data-confirm") ||
                "Anda yakin ingin melanjutkan?";
            const href = this.getAttribute("href");

            Swal.fire({
                title: "Konfirmasi",
                text: message,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Ya",
                cancelButtonText: "Tidak",
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed && href) {
                    window.location.href = href;
                }
            });
        });
    });

    // Tangani form submission dengan konfirmasi
    document.querySelectorAll("form[data-confirm]").forEach(function (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault();

            const message =
                this.getAttribute("data-confirm") ||
                "Anda yakin ingin menyimpan data ini?";
            const formElement = this;

            Swal.fire({
                title: "Konfirmasi",
                text: message,
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Ya",
                cancelButtonText: "Tidak",
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    formElement.submit();
                }
            });
        });
    });
});

// Fitur Ajax Form Submit dengan Progress
function ajaxFormSubmit(formElement, options = {}) {
    const form =
        typeof formElement === "string"
            ? document.querySelector(formElement)
            : formElement;
    if (!form) return Promise.reject("Form not found");

    const formData = new FormData(form);
    const url = form.getAttribute("action") || window.location.href;
    const method = form.getAttribute("method") || "POST";

    Swal.fire({
        title: options.loadingTitle || "Memproses...",
        text: options.loadingText || "Mohon tunggu sebentar",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
    });

    return fetch(url, {
        method: method,
        body: formData,
        headers: {
            "X-Requested-With": "XMLHttpRequest",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            Swal.close();

            if (data.success) {
                Swal.fire({
                    title: data.title || "Berhasil!",
                    text: data.message || "Operasi berhasil dilakukan",
                    icon: "success",
                });

                if (options.onSuccess) {
                    options.onSuccess(data);
                }

                return data;
            } else {
                Swal.fire({
                    title: data.title || "Gagal!",
                    text:
                        data.message ||
                        "Terjadi kesalahan saat memproses permintaan Anda",
                    icon: "error",
                });

                if (options.onError) {
                    options.onError(data);
                }

                return Promise.reject(data);
            }
        })
        .catch((error) => {
            Swal.close();

            Swal.fire({
                title: "Error!",
                text: "Terjadi kesalahan pada sistem",
                icon: "error",
            });

            if (options.onError) {
                options.onError(error);
            }

            return Promise.reject(error);
        });
}

// Expose fungsi ke global scope
window.ajaxFormSubmit = ajaxFormSubmit;

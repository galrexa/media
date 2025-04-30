// resources/js/notification.js
document.addEventListener("DOMContentLoaded", function () {
    // Fungsi untuk memuat notifikasi
    function loadNotifications() {
        fetch("/notifications/get")
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    updateNotificationBadge(data.count);
                    updateNotificationList(data.notifications);
                }
            })
            .catch((error) =>
                console.error("Error loading notifications:", error)
            );
    }

    // Fungsi untuk memperbarui badge notifikasi
    function updateNotificationBadge(count) {
        const navBadge = document.querySelector(".notification-badge");
        const dropdownBadge = document.querySelector(".dropdown-header .badge");

        if (count > 0) {
            // Update badge di navbar
            if (navBadge) {
                navBadge.textContent = count;
                navBadge.style.display = "block";
            } else {
                const newBadge = document.createElement("span");
                newBadge.className = "badge bg-danger notification-badge";
                newBadge.textContent = count;
                document
                    .querySelector("#notificationDropdown")
                    .appendChild(newBadge);
            }

            // Update badge di dropdown header
            if (dropdownBadge) {
                dropdownBadge.textContent = count + " baru";
                dropdownBadge.style.display = "block";
            }

            // Tampilkan tombol "Tandai Semua Dibaca"
            const markAllReadBtn = document.querySelector(
                ".dropdown-footer a:last-child"
            );
            if (markAllReadBtn) {
                markAllReadBtn.style.display = "block";
            }
        } else {
            // Sembunyikan badge jika tidak ada notifikasi
            if (navBadge) navBadge.style.display = "none";
            if (dropdownBadge) dropdownBadge.style.display = "none";

            // Sembunyikan tombol "Tandai Semua Dibaca"
            const markAllReadBtn = document.querySelector(
                ".dropdown-footer a:last-child"
            );
            if (markAllReadBtn) {
                markAllReadBtn.style.display = "none";
            }
        }
    }

    // Fungsi untuk memperbarui daftar notifikasi
    function updateNotificationList(notifications) {
        const notificationList = document.getElementById("notification-list");

        if (!notificationList) return;

        if (notifications.length === 0) {
            notificationList.innerHTML = `
                <li class="empty-notification">
                    <p class="text-center py-3 text-muted">Tidak ada notifikasi</p>
                </li>
            `;
            return;
        }

        let notificationHtml = "";

        notifications.forEach((notification) => {
            let iconClass = "bi-info-circle";
            let bgClass = "bg-info";

            // Set icon dan background berdasarkan tipe notifikasi
            if (notification.tipe === "verifikasi") {
                iconClass = "bi-clipboard-check";
                bgClass = "bg-primary";
            } else if (notification.tipe === "tolak") {
                iconClass = "bi-x-circle";
                bgClass = "bg-danger";
            } else if (notification.tipe === "publikasi") {
                iconClass = "bi-check-circle";
                bgClass = "bg-success";
            }

            // Format waktu yang lebih user-friendly
            const date = new Date(notification.created_at);
            const timeAgo = timeSince(date);

            // Potong pesan yang terlalu panjang
            const message =
                notification.pesan.length > 60
                    ? notification.pesan.substring(0, 60) + "..."
                    : notification.pesan;

            notificationHtml += `
                <li class="notification-item">
                    <a href="/notifikasi/mark-as-read/${
                        notification.id
                    }" class="dropdown-item ${
                notification.is_read ? "" : "unread"
            }">
                        <div class="d-flex">
                            <div class="notification-icon ${bgClass}">
                                <i class="bi ${iconClass}"></i>
                            </div>
                            <div class="notification-content">
                                <h6 class="notification-title">${
                                    notification.judul
                                }</h6>
                                <p class="notification-text">${message}</p>
                                <small class="notification-time">${timeAgo}</small>
                            </div>
                        </div>
                    </a>
                </li>
            `;
        });

        notificationList.innerHTML = notificationHtml;
    }

    // Fungsi untuk menghitung waktu yang telah berlalu
    function timeSince(date) {
        const seconds = Math.floor((new Date() - date) / 1000);

        let interval = seconds / 31536000;
        if (interval > 1) {
            return Math.floor(interval) + " tahun yang lalu";
        }

        interval = seconds / 2592000;
        if (interval > 1) {
            return Math.floor(interval) + " bulan yang lalu";
        }

        interval = seconds / 86400;
        if (interval > 1) {
            return Math.floor(interval) + " hari yang lalu";
        }

        interval = seconds / 3600;
        if (interval > 1) {
            return Math.floor(interval) + " jam yang lalu";
        }

        interval = seconds / 60;
        if (interval > 1) {
            return Math.floor(interval) + " menit yang lalu";
        }

        return Math.floor(seconds) + " detik yang lalu";
    }

    // Load notifikasi saat halaman dimuat
    loadNotifications();

    // Atur polling untuk memperbarui notifikasi setiap 1 menit
    setInterval(loadNotifications, 60000);
});

(function () {
    const deleteLinkSelector = 'a[href*="hapus="]';
    const logoutLinkSelector = 'a[href*="logout.php"]';
    let pendingDeleteUrl = "";
    let pendingAction = "delete";

    function ensureDeleteModal() {
        let modal = document.getElementById("delete-confirm-modal");

        if (modal) {
            return modal;
        }

        const style = document.createElement("style");
        style.textContent = `
            .delete-confirm-backdrop {
                position: fixed;
                inset: 0;
                z-index: 9999;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 18px;
                background: rgba(15, 23, 42, 0.45);
            }

            .delete-confirm-backdrop.show {
                display: flex;
            }

            .delete-confirm-box {
                width: min(360px, 100%);
                background: #fff;
                color: #111827;
                border-radius: 12px;
                box-shadow: 0 24px 70px rgba(15, 23, 42, 0.25);
                border: 1px solid #e5e7eb;
                padding: 22px;
                text-align: center;
            }

            .delete-confirm-icon {
                width: 48px;
                height: 48px;
                border-radius: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 12px;
                background: #fee2e2;
                color: #dc2626;
                font-size: 22px;
            }

            .delete-confirm-title {
                margin: 0 0 8px;
                font-size: 18px;
                font-weight: 800;
            }

            .delete-confirm-message {
                margin: 0 0 20px;
                color: #4b5563;
                font-size: 14px;
                line-height: 1.5;
            }

            .delete-confirm-actions {
                display: flex;
                justify-content: center;
                gap: 10px;
            }

            .delete-confirm-actions button {
                min-width: 92px;
                border: 0;
                border-radius: 9px;
                padding: 10px 14px;
                cursor: pointer;
                font-weight: 700;
            }

            .delete-confirm-no {
                background: #e5e7eb;
                color: #111827;
            }

            .delete-confirm-yes {
                background: #dc2626;
                color: #fff;
            }
        `;
        document.head.appendChild(style);

        modal = document.createElement("div");
        modal.id = "delete-confirm-modal";
        modal.className = "delete-confirm-backdrop";
        modal.innerHTML = `
            <div class="delete-confirm-box" role="dialog" aria-modal="true" aria-labelledby="delete-confirm-title">
                <div class="delete-confirm-icon">!</div>
                <h3 class="delete-confirm-title" id="delete-confirm-title">Konfirmasi Hapus</h3>
                <p class="delete-confirm-message">Anda yakin menghapus data ini?</p>
                <div class="delete-confirm-actions">
                    <button type="button" class="delete-confirm-no">Tidak</button>
                    <button type="button" class="delete-confirm-yes">Iya</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        modal.querySelector(".delete-confirm-no").addEventListener("click", closeDeleteModal);
        modal.querySelector(".delete-confirm-yes").addEventListener("click", function () {
            if (pendingDeleteUrl) {
                window.location.href = pendingDeleteUrl;
            }
        });

        modal.addEventListener("click", function (event) {
            if (event.target === modal) {
                closeDeleteModal();
            }
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closeDeleteModal();
            }
        });

        return modal;
    }

    function openDeleteModal(url, message, title, action) {
        const modal = ensureDeleteModal();
        pendingDeleteUrl = url;
        pendingAction = action || "delete";
        modal.querySelector(".delete-confirm-title").textContent = title || "Konfirmasi Hapus";
        modal.querySelector(".delete-confirm-message").textContent = message || "Anda yakin menghapus data ini?";
        modal.classList.add("show");
    }

    function closeDeleteModal() {
        const modal = document.getElementById("delete-confirm-modal");
        pendingDeleteUrl = "";
        pendingAction = "delete";

        if (modal) {
            modal.classList.remove("show");
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(deleteLinkSelector).forEach(function (link) {
            link.removeAttribute("onclick");
        });
    });

    document.addEventListener("click", function (event) {
        const link = event.target.closest(deleteLinkSelector);
        const logoutLink = event.target.closest(logoutLinkSelector);

        if (!link && !logoutLink) {
            return;
        }

        event.preventDefault();
        if (logoutLink) {
            openDeleteModal(
                logoutLink.href,
                logoutLink.dataset.confirmMessage || "Anda yakin ingin keluar?",
                logoutLink.dataset.confirmTitle || "Konfirmasi Logout",
                "logout"
            );
            return;
        }

        openDeleteModal(
            link.href,
            link.dataset.confirmMessage || "Anda yakin menghapus data ini?",
            link.dataset.confirmTitle || "Konfirmasi Hapus",
            "delete"
        );
    });
})();

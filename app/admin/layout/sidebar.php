<?php
$current = basename($_SERVER['PHP_SELF']);

$akademik_pages = [
    'jadwal_kuliah.php',
    'jadwal_uas.php',
    'jadwal_uts.php',
    'prosedur_kp.php',
    'prosedur_frs.php',
    'prosedur_ta.php'
];

$chatbot_pages = [
    'faq.php',
    'log_percakapan.php'
];

$akademik_active = in_array($current, $akademik_pages, true);
$chatbot_active = in_array($current, $chatbot_pages, true);
?>

<style>
/* ================= SIDEBAR ADMIN ================= */

.admin-sidebar {
    width: 270px;
    height: 100vh;
    background: linear-gradient(180deg, #0f172a 0%, #172554 100%);
    color: #ffffff;
    padding: 20px 16px;
    position: fixed;
    left: 0;
    top: 0;
    overflow-y: auto;
    z-index: 999;
    box-shadow: 8px 0 24px rgba(15, 23, 42, 0.16);
}

.admin-sidebar::-webkit-scrollbar {
    width: 6px;
}

.admin-sidebar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.22);
    border-radius: 999px;
}

.admin-logo {
    padding: 10px 10px 18px;
    margin-bottom: 10px;
    border-bottom: 1px solid rgba(255,255,255,0.10);
}

.admin-logo-main {
    display: flex;
    align-items: center;
    gap: 12px;
}

.admin-logo-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    background: linear-gradient(135deg, #60a5fa, #2563eb);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 22px rgba(37, 99, 235, 0.35);
}

.admin-logo-text h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 800;
    letter-spacing: 0.5px;
}

.admin-logo-text small {
    display: block;
    margin-top: 3px;
    font-size: 12px;
    color: #cbd5e1;
}

.admin-nav {
    list-style: none;
    padding: 0;
    margin: 16px 0 0;
}

.admin-nav-item {
    margin-bottom: 7px;
}

.admin-nav-link,
.admin-nav-button {
    width: 100%;
    min-height: 46px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    text-decoration: none;
    color: #dbeafe;
    padding: 12px 13px;
    border-radius: 14px;
    font-size: 14px;
    font-weight: 650;
    transition: 0.22s ease;
    background: transparent;
    border: none;
    outline: none;
    cursor: pointer;
    text-align: left;
    font-family: inherit;
}

.admin-nav-link:hover,
.admin-nav-button:hover {
    background: rgba(255, 255, 255, 0.09);
    color: #ffffff;
}

.admin-nav-item.is-active > .admin-nav-link,
.admin-nav-item.is-active > .admin-nav-button,
.admin-nav-item.is-open > .admin-nav-button {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #ffffff;
    box-shadow: 0 10px 20px rgba(37, 99, 235, 0.24);
}

.admin-nav-left {
    display: flex;
    align-items: center;
    gap: 11px;
}

.admin-nav-left i {
    width: 18px;
    text-align: center;
    font-size: 15px;
}

.admin-arrow {
    font-size: 12px;
    transition: transform 0.22s ease;
}

.admin-nav-item.is-open .admin-arrow {
    transform: rotate(180deg);
}

/* SUBMENU */
.admin-submenu {
    list-style: none;
    padding: 7px 0 2px 14px;
    margin: 5px 0 0 0;
    display: none;
    border-left: 1px solid rgba(255,255,255,0.12);
    margin-left: 18px;
}

.admin-nav-item.is-open .admin-submenu {
    display: block;
}

.admin-submenu li {
    margin-bottom: 5px;
}

.admin-submenu a {
    display: block;
    text-decoration: none;
    color: #cbd5e1;
    padding: 9px 12px;
    border-radius: 11px;
    font-size: 13px;
    font-weight: 600;
    transition: 0.2s ease;
}

.admin-submenu a:hover {
    background: rgba(255,255,255,0.08);
    color: #ffffff;
}

.admin-submenu a.is-active {
    background: rgba(96, 165, 250, 0.22);
    color: #ffffff;
}

/* LOGOUT */
.admin-nav-item.admin-logout {
    margin-top: 18px;
    padding-top: 14px;
    border-top: 1px solid rgba(255,255,255,0.10);
}

.admin-nav-item.admin-logout .admin-nav-link {
    color: #fecaca;
}

.admin-nav-item.admin-logout .admin-nav-link:hover {
    background: rgba(239, 68, 68, 0.15);
    color: #ffffff;
}

/* MAIN CONTENT OFFSET */
.main-content {
    margin-left: 270px;
    min-height: 100vh;
}

/* RESPONSIVE */
@media (max-width: 992px) {
    .admin-sidebar {
        width: 250px;
    }

    .main-content {
        margin-left: 250px;
    }
}

@media (max-width: 768px) {
    .admin-sidebar {
        position: relative;
        width: 100%;
        height: auto;
        min-height: auto;
        border-radius: 0 0 22px 22px;
        padding: 16px;
    }

    .admin-logo {
        padding-bottom: 14px;
    }

    .admin-nav {
        margin-top: 12px;
    }

    .main-content {
        margin-left: 0;
    }
}
</style>

<aside class="admin-sidebar">

    <div class="admin-logo">
        <div class="admin-logo-main">
            <div class="admin-logo-icon">
                <i class="fa-solid fa-robot"></i>
            </div>

            <div class="admin-logo-text">
                <h2>CYRA</h2>
                <small>Admin Panel</small>
            </div>
        </div>
    </div>

    <ul class="admin-nav">

        <!-- DASHBOARD -->
        <li class="admin-nav-item <?= $current === 'dashboard.php' ? 'is-active' : '' ?>">
            <a href="dashboard.php" class="admin-nav-link">
                <span class="admin-nav-left">
                    <i class="fa-solid fa-house"></i>
                    <span>Dashboard</span>
                </span>
            </a>
        </li>

        <!-- AKADEMIK -->
        <li class="admin-nav-item <?= $akademik_active ? 'is-active is-open' : '' ?>">
            <button type="button" class="admin-nav-button js-admin-submenu">
                <span class="admin-nav-left">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <span>Akademik</span>
                </span>
                <i class="fa-solid fa-chevron-down admin-arrow"></i>
            </button>

            <ul class="admin-submenu">
                <li>
                    <a href="jadwal_kuliah.php" class="<?= $current === 'jadwal_kuliah.php' ? 'is-active' : '' ?>">
                        Jadwal Kuliah
                    </a>
                </li>

                <li>
                    <a href="jadwal_uts.php" class="<?= $current === 'jadwal_uts.php' ? 'is-active' : '' ?>">
                        Jadwal UTS
                    </a>
                </li>

                <li>
                    <a href="jadwal_uas.php" class="<?= $current === 'jadwal_uas.php' ? 'is-active' : '' ?>">
                        Jadwal UAS
                    </a>
                </li>

                <li>
                    <a href="prosedur_frs.php" class="<?= $current === 'prosedur_frs.php' ? 'is-active' : '' ?>">
                        Prosedur FRS
                    </a>
                </li>

                <li>
                    <a href="prosedur_kp.php" class="<?= $current === 'prosedur_kp.php' ? 'is-active' : '' ?>">
                        Prosedur KP
                    </a>
                </li>

                <li>
                    <a href="prosedur_ta.php" class="<?= $current === 'prosedur_ta.php' ? 'is-active' : '' ?>">
                        Prosedur TA
                    </a>
                </li>
            </ul>
        </li>

        <!-- DOSEN -->
        <li class="admin-nav-item <?= $current === 'dosen.php' ? 'is-active' : '' ?>">
            <a href="dosen.php" class="admin-nav-link">
                <span class="admin-nav-left">
                    <i class="fa-solid fa-user-tie"></i>
                    <span>Data Dosen</span>
                </span>
            </a>
        </li>

        <!-- KURIKULUM -->
        <li class="admin-nav-item <?= $current === 'kurikulum.php' ? 'is-active' : '' ?>">
            <a href="kurikulum.php" class="admin-nav-link">
                <span class="admin-nav-left">
                    <i class="fa-solid fa-book"></i>
    font-size: 12px;
    transition: transform 0.22s ease;
}

.admin-nav-item.is-open .admin-arrow {
    transform: rotate(180deg);
}

/* SUBMENU */
.admin-submenu {
    list-style: none;
    padding: 7px 0 2px 14px;
    margin: 5px 0 0 0;
    display: none;
    border-left: 1px solid rgba(255,255,255,0.12);
    margin-left: 18px;
}

.admin-nav-item.is-open .admin-submenu {
    display: block;
}

.admin-submenu li {
    margin-bottom: 5px;
}

.admin-submenu a {
    display: block;
    text-decoration: none;
    color: #cbd5e1;
    padding: 9px 12px;
    border-radius: 11px;
    font-size: 13px;
    font-weight: 600;
    transition: 0.2s ease;
}

.admin-submenu a:hover {
    background: rgba(255,255,255,0.08);
    color: #ffffff;
}

.admin-submenu a.is-active {
    background: rgba(96, 165, 250, 0.22);
    color: #ffffff;
}

/* LOGOUT */
.admin-nav-item.admin-logout {
    margin-top: 18px;
    padding-top: 14px;
    border-top: 1px solid rgba(255,255,255,0.10);
}

.admin-nav-item.admin-logout .admin-nav-link {
    color: #fecaca;
}

.admin-nav-item.admin-logout .admin-nav-link:hover {
    background: rgba(239, 68, 68, 0.15);
    color: #ffffff;
}

/* MAIN CONTENT OFFSET */
.main-content {
    margin-left: 270px;
    min-height: 100vh;
}

/* RESPONSIVE */
@media (max-width: 992px) {
    .admin-sidebar {
        width: 250px;
    }

    .main-content {
        margin-left: 250px;
    }
}

@media (max-width: 768px) {
    .admin-sidebar {
        position: relative;
        width: 100%;
        height: auto;
        min-height: auto;
        border-radius: 0 0 22px 22px;
        padding: 16px;
    }

    .admin-logo {
        padding-bottom: 14px;
    }

    .admin-nav {
        margin-top: 12px;
    }

    .main-content {
        margin-left: 0;
    }
}
</style>

<aside class="admin-sidebar">

    <div class="admin-logo">
        <div class="admin-logo-main">
            <div class="admin-logo-icon">
                <i class="fa-solid fa-robot"></i>
            </div>

            <div class="admin-logo-text">
                <h2>CYRA</h2>
                <small>Admin Panel</small>
            </div>
        </div>
    </div>

    <ul class="admin-nav">

        <!-- DASHBOARD -->
        <li class="admin-nav-item <?= $current === 'dashboard.php' ? 'is-active' : '' ?>">
            <a href="dashboard.php" class="admin-nav-link">
                <span class="admin-nav-left">
                    <i class="fa-solid fa-house"></i>
                    <span>Dashboard</span>
                </span>
            </a>
        </li>

        <!-- AKADEMIK -->
        <li class="admin-nav-item <?= $akademik_active ? 'is-active is-open' : '' ?>">
            <button type="button" class="admin-nav-button js-admin-submenu">
                <span class="admin-nav-left">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <span>Akademik</span>
                </span>
                <i class="fa-solid fa-chevron-down admin-arrow"></i>
            </button>

            <ul class="admin-submenu">
                <li>
                    <a href="jadwal_kuliah.php" class="<?= $current === 'jadwal_kuliah.php' ? 'is-active' : '' ?>">
                        Jadwal Kuliah
                    </a>
                </li>

                <li>
                    <a href="jadwal_uts.php" class="<?= $current === 'jadwal_uts.php' ? 'is-active' : '' ?>">
                        Jadwal UTS
                    </a>
                </li>

                <li>
                    <a href="jadwal_uas.php" class="<?= $current === 'jadwal_uas.php' ? 'is-active' : '' ?>">
                        Jadwal UAS
                    </a>
                </li>

                <li>
                    <a href="prosedur_frs.php" class="<?= $current === 'prosedur_frs.php' ? 'is-active' : '' ?>">
                        Prosedur FRS
                    </a>
                </li>

                <li>
                    <a href="prosedur_kp.php" class="<?= $current === 'prosedur_kp.php' ? 'is-active' : '' ?>">
                        Prosedur KP
                    </a>
                </li>

                <li>
                    <a href="prosedur_ta.php" class="<?= $current === 'prosedur_ta.php' ? 'is-active' : '' ?>">
                        Prosedur TA
                    </a>
                </li>
            </ul>
        </li>

        <!-- DOSEN -->
        <li class="admin-nav-item <?= $current === 'dosen.php' ? 'is-active' : '' ?>">
            <a href="dosen.php" class="admin-nav-link">
                <span class="admin-nav-left">
                    <i class="fa-solid fa-user-tie"></i>
                    <span>Data Dosen</span>
                </span>
            </a>
        </li>

        <!-- KURIKULUM -->
        <li class="admin-nav-item <?= $current === 'kurikulum.php' ? 'is-active' : '' ?>">
            <a href="kurikulum.php" class="admin-nav-link">
                <span class="admin-nav-left">
                    <i class="fa-solid fa-book"></i>
                    <span>Kurikulum</span>
                </span>
            </a>
        </li>

        <!-- CHATBOT -->
        <li class="admin-nav-item <?= $chatbot_active ? 'is-active is-open' : '' ?>">
            <button type="button" class="admin-nav-button js-admin-submenu">
                <span class="admin-nav-left">
                    <i class="fa-solid fa-comments"></i>
                    <span>Chatbot</span>
                </span>
                <i class="fa-solid fa-chevron-down admin-arrow"></i>
            </button>

            <ul class="admin-submenu">
                <li>
                    <a href="faq.php" class="<?= $current === 'faq.php' ? 'is-active' : '' ?>">
                        FAQ Chatbot
                    </a>
                </li>
                <li>
                    <a href="log_percakapan.php" class="<?= $current === 'log_percakapan.php' ? 'is-active' : '' ?>">
                        Log Percakapan
                    </a>
                </li>
            </ul>
        </li>

        <!-- LOGOUT -->
        <li class="admin-nav-item admin-logout">
            <a href="../auth/logout.php" class="admin-nav-link" data-confirm-title="Konfirmasi Logout" data-confirm-message="Anda yakin ingin keluar?">
                <span class="admin-nav-left">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Logout</span>
                </span>
            </a>
        </li>

    </ul>
</aside>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const toggles = document.querySelectorAll(".js-admin-submenu");

    toggles.forEach(function(toggle) {
        toggle.addEventListener("click", function () {
            const parent = this.closest(".admin-nav-item");
            parent.classList.toggle("is-open");
        });
    });
});
</script>

<script src="../../assets/js/admin-delete-confirm.js"></script>

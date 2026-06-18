<?php
// ================== AUTO LOAD DATABASE ==================
$dir = __DIR__;
$found = false;

while ($dir !== dirname($dir)) {
    if (file_exists($dir . '/config/database.php')) {
        require $dir . '/config/database.php';
        $found = true;
        break;
    }
    $dir = dirname($dir);
}

if (!$found || !isset($conn)) {
    die("File database.php tidak ditemukan atau koneksi gagal!");
}

// ================== SESSION ==================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['login'])) {
    header("Location: ../auth/login.php");
    exit;
}

$username = $_SESSION['username'] ?? 'Admin';

// ================== HELPER ==================
function countTable(mysqli $conn, string $table): int
{
    $tableSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM `$tableSafe`");

    if ($query) {
        $row = mysqli_fetch_assoc($query);
        return (int) ($row['total'] ?? 0);
    }

    return 0;
}

// ================== HITUNG DATA ==================
$count_jadwal = countTable($conn, 'jadwal_kuliah');
$count_faq    = countTable($conn, 'faq');
$count_dosen  = countTable($conn, 'dosen');
$count_matkul = countTable($conn, 'mata_kuliah');
$count_uts    = countTable($conn, 'jadwal_uts');
$count_uas    = countTable($conn, 'jadwal_uas');
$count_frs    = countTable($conn, 'prosedur_frs');
$count_kp     = countTable($conn, 'prosedur_kp');
$count_ta     = countTable($conn, 'prosedur_ta');

$count_prosedur = $count_frs + $count_kp + $count_ta;
$count_ujian    = $count_uts + $count_uas;
$count_total    = $count_jadwal + $count_faq + $count_dosen + $count_matkul + $count_ujian + $count_prosedur;

date_default_timezone_set('Asia/Jakarta');
$tanggal = date('d-m-Y');
$jam = date('H:i');
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard Admin</title>

<link rel="stylesheet" href="../../assets/css/admin.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
:root {
    --bg: #f4f7fb;
    --card: #ffffff;
    --text: #0f172a;
    --muted: #64748b;
    --border: #e5e7eb;

    --blue: #2563eb;
    --blue-2: #60a5fa;

    --green: #059669;
    --green-2: #34d399;

    --orange: #d97706;
    --orange-2: #fbbf24;

    --purple: #7c3aed;
    --purple-2: #a78bfa;

    --red: #dc2626;
    --red-soft: #fee2e2;

    --shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: "Segoe UI", Arial, sans-serif;
    background:
        radial-gradient(circle at top left, rgba(37, 99, 235, 0.10), transparent 26%),
        radial-gradient(circle at bottom right, rgba(124, 58, 237, 0.09), transparent 28%),
        var(--bg);
    color: var(--text);
}

.main-content {
    padding: 26px;
}

/* HEADER */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 18px;
}

.header-title h2 {
    margin: 0;
    font-size: 30px;
    font-weight: 850;
    letter-spacing: -0.5px;
}

.header-title p {
    margin: 5px 0 0;
    color: var(--muted);
    font-size: 14px;
}

.admin-box {
    display: flex;
    align-items: center;
    gap: 11px;
    background: rgba(255,255,255,0.9);
    border: 1px solid rgba(229,231,235,0.9);
    padding: 9px 10px;
    border-radius: 16px;
    box-shadow: var(--shadow);
    backdrop-filter: blur(8px);
}

.admin-avatar {
    width: 40px;
    height: 40px;
    border-radius: 13px;
    background: linear-gradient(135deg, var(--blue-2), var(--blue));
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 850;
    box-shadow: 0 9px 18px rgba(37, 99, 235, 0.25);
}

.admin-name small {
    display: block;
    color: var(--muted);
    font-size: 12px;
}

.admin-name strong {
    font-size: 14px;
    color: var(--text);
}

.logout-btn {
    background: var(--red-soft);
    color: var(--red);
    text-decoration: none;
    padding: 9px 12px;
    border-radius: 11px;
    font-size: 13px;
    font-weight: 750;
    transition: 0.2s ease;
}

.logout-btn:hover {
    background: var(--red);
    color: #ffffff;
}

/* MINI INFO */
.info-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    margin-bottom: 18px;
    padding: 16px 18px;
    border-radius: 18px;
    background: linear-gradient(135deg, #172554, #2563eb);
    color: #ffffff;
    box-shadow: 0 14px 32px rgba(37, 99, 235, 0.23);
}

.info-left {
    display: flex;
    align-items: center;
    gap: 13px;
}

.info-icon {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: rgba(255,255,255,0.16);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.info-text h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 850;
}

.info-text p {
    margin: 4px 0 0;
    font-size: 13px;
    color: rgba(255,255,255,0.82);
}

.info-time {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.15);
    padding: 9px 12px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 750;
    white-space: nowrap;
}

/* STATS */
.stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 18px;
}

.stat-card {
    position: relative;
    overflow: hidden;
    border-radius: 18px;
    padding: 18px;
    min-height: 118px;
    color: #ffffff;
    box-shadow: var(--shadow);
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    transition: 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
}

.stat-card::after {
    content: "";
    position: absolute;
    right: -32px;
    top: -32px;
    width: 105px;
    height: 105px;
    border-radius: 50%;
    background: rgba(255,255,255,0.18);
}

.stat-card.blue {
    background: linear-gradient(135deg, var(--blue), var(--blue-2));
}

.stat-card.green {
    background: linear-gradient(135deg, var(--green), var(--green-2));
}

.stat-card.orange {
    background: linear-gradient(135deg, var(--orange), var(--orange-2));
}

.stat-card.purple {
    background: linear-gradient(135deg, var(--purple), var(--purple-2));
}

.stat-card h3 {
    margin: 0;
    font-size: 32px;
    font-weight: 850;
    position: relative;
    z-index: 2;
}

.stat-card p {
    margin: 5px 0 0;
    font-size: 13px;
    font-weight: 650;
    color: rgba(255,255,255,0.90);
    position: relative;
    z-index: 2;
}

.stat-icon {
    width: 46px;
    height: 46px;
    border-radius: 15px;
    background: rgba(255,255,255,0.18);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    position: relative;
    z-index: 2;
}

/* MENU */
.menu-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
}

.menu-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 18px;
    box-shadow: var(--shadow);
    text-decoration: none;
    color: var(--text);
    transition: 0.2s ease;
    min-height: 145px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.menu-card:hover {
    transform: translateY(-3px);
    border-color: #bfdbfe;
    box-shadow: 0 15px 35px rgba(37, 99, 235, 0.13);
}

.menu-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.menu-icon {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
}

.menu-icon.blue {
    background: linear-gradient(135deg, var(--blue), var(--blue-2));
}

.menu-icon.green {
    background: linear-gradient(135deg, var(--green), var(--green-2));
}

.menu-icon.orange {
    background: linear-gradient(135deg, var(--orange), var(--orange-2));
}

.menu-icon.purple {
    background: linear-gradient(135deg, var(--purple), var(--purple-2));
}

.menu-arrow {
    color: #94a3b8;
    transition: 0.2s ease;
}

.menu-card:hover .menu-arrow {
    color: var(--blue);
    transform: translateX(3px);
}

.menu-card h4 {
    margin: 15px 0 6px;
    font-size: 16px;
    font-weight: 850;
}

.menu-card p {
    margin: 0;
    color: var(--muted);
    font-size: 13px;
    line-height: 1.45;
}

.menu-count {
    margin-top: 13px;
    width: fit-content;
    background: #f1f5f9;
    color: var(--text);
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
}

/* RESPONSIVE */
@media (max-width: 1200px) {
    .stats,
    .menu-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .main-content {
        padding: 16px;
    }

    .dashboard-header,
    .info-bar {
        flex-direction: column;
        align-items: flex-start;
    }

    .admin-box {
        width: 100%;
        flex-wrap: wrap;
    }

    .logout-btn {
        width: 100%;
        text-align: center;
    }

    .info-time {
        width: 100%;
        justify-content: center;
    }

    .stats,
    .menu-grid {
        grid-template-columns: 1fr;
    }

    .header-title h2 {
        font-size: 25px;
    }
}
</style>
</head>

<body>

<?php include 'layout/sidebar.php'; ?>

<div class="main-content">

    <div class="dashboard-header">
        <div class="header-title">
            <h2>Dashboard</h2>
            <p>Panel admin chatbot akademik CYRA</p>
        </div>

        <div class="admin-box">
            <div class="admin-avatar">
                <?= strtoupper(substr($username, 0, 1)) ?>
            </div>

            <div class="admin-name">
                <small>Login sebagai</small>
                <strong><?= htmlspecialchars($username) ?></strong>
            </div>

            <a href="../auth/logout.php" class="logout-btn" data-confirm-title="Konfirmasi Logout" data-confirm-message="Anda yakin ingin keluar?">
                <i class="fa fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </div>

    <div class="info-bar">
        <div class="info-left">
            <div class="info-icon">
                <i class="fa fa-robot"></i>
            </div>

            <div class="info-text">
                <h3>CYRA Admin</h3>
                <p>Kelola data akademik dan chatbot dari satu halaman.</p>
            </div>
        </div>

        <div class="info-time">
            <i class="fa fa-clock"></i>
            <?= $tanggal ?> | <?= $jam ?> WIB
        </div>
    </div>

    <div class="stats">
        <div class="stat-card blue">
            <div>
                <h3><?= $count_jadwal ?></h3>
                <p>Jadwal Kuliah</p>
            </div>
            <div class="stat-icon">
                <i class="fa fa-calendar-days"></i>
            </div>
        </div>

        <div class="stat-card green">
            <div>
                <h3><?= $count_dosen ?></h3>
                <p>Data Dosen</p>
            </div>
            <div class="stat-icon">
                <i class="fa fa-user-tie"></i>
            </div>
        </div>

        <div class="stat-card orange">
            <div>
                <h3><?= $count_faq ?></h3>
                <p>FAQ Chatbot</p>
            </div>
            <div class="stat-icon">
                <i class="fa fa-comments"></i>
            </div>
        </div>

        <div class="stat-card purple">
            <div>
                <h3><?= $count_total ?></h3>
                <p>Total Data</p>
            </div>
            <div class="stat-icon">
                <i class="fa fa-database"></i>
            </div>
        </div>
    </div>

    <div class="menu-grid">

        <a href="jadwal_kuliah.php" class="menu-card">
            <div>
                <div class="menu-top">
                    <div class="menu-icon blue">
                        <i class="fa fa-calendar"></i>
                    </div>
                    <div class="menu-arrow">
                        <i class="fa fa-arrow-right"></i>
                    </div>
                </div>

                <h4>Jadwal Kuliah</h4>
                <p>Kelola jadwal kuliah berdasarkan semester dan hari.</p>
            </div>

            <span class="menu-count"><?= $count_jadwal ?> data</span>
        </a>

        <a href="faq.php" class="menu-card">
            <div>
                <div class="menu-top">
                    <div class="menu-icon orange">
                        <i class="fa fa-comments"></i>
                    </div>
                    <div class="menu-arrow">
                        <i class="fa fa-arrow-right"></i>
                    </div>
                </div>

                <h4>FAQ Chatbot</h4>
                <p>Kelola pertanyaan dan jawaban chatbot CYRA.</p>
            </div>

            <span class="menu-count"><?= $count_faq ?> data</span>
        </a>

        <a href="dosen.php" class="menu-card">
            <div>
                <div class="menu-top">
                    <div class="menu-icon green">
                        <i class="fa fa-user-tie"></i>
                    </div>
                    <div class="menu-arrow">
                        <i class="fa fa-arrow-right"></i>
                    </div>
                </div>

                <h4>Data Dosen</h4>
                <p>Kelola informasi dosen program studi.</p>
            </div>

            <span class="menu-count"><?= $count_dosen ?> data</span>
        </a>

        <a href="kurikulum.php" class="menu-card">
            <div>
                <div class="menu-top">
                    <div class="menu-icon purple">
                        <i class="fa fa-book"></i>
                    </div>
                    <div class="menu-arrow">
                        <i class="fa fa-arrow-right"></i>
                    </div>
                </div>

                <h4>Kurikulum</h4>
                <p>Kelola mata kuliah, kode, semester, dan SKS.</p>
            </div>

            <span class="menu-count"><?= $count_matkul ?> data</span>
        </a>

    </div>

</div>

</body>
</html>

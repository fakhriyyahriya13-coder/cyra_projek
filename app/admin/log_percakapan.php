<?php
// ================= AUTO LOAD DATABASE =================
$dir = __DIR__;
$db_file = null;

while ($dir !== dirname($dir)) {
    if (file_exists($dir . '/config/database.php')) {
        $db_file = $dir . '/config/database.php';
        break;
    }
    $dir = dirname($dir);
}

if (!$db_file) {
    die("Database tidak ditemukan!");
}

require_once $db_file;

// ================= SESSION =================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['login'])) {
    header("Location: ../auth/login.php");
    exit;
}

$username = $_SESSION['username'] ?? 'Admin';

// ================= VALIDASI KONEKSI =================
if (!isset($conn) || !$conn) {
    die("Koneksi database gagal!");
}

// ================= HAPUS DATA =================
if (isset($_GET['hapus'])) {
    $id_log = (int) ($_GET['hapus'] ?? 0);

    if ($id_log > 0) {
        $stmt = $conn->prepare("DELETE FROM chat_logs WHERE id_log = ?");
        $stmt->bind_param("i", $id_log);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: log_percakapan.php");
    exit;
}

// ================= HAPUS SEMUA DATA =================
if (isset($_GET['hapus_semua'])) {
    $conn->query("TRUNCATE TABLE chat_logs");
    header("Location: log_percakapan.php");
    exit;
}

// ================= PENCARIAN DATA =================
$keyword = trim($_GET['keyword'] ?? '');
$limit = 100; // Limit to 100 latest logs for performance

if ($keyword !== '') {
    $search = "%" . $keyword . "%";

    $stmtLog = $conn->prepare("
        SELECT *
        FROM chat_logs
        WHERE user_message LIKE ?
           OR bot_response LIKE ?
           OR session_id LIKE ?
        ORDER BY id_log DESC
        LIMIT ?
    ");

    $stmtLog->bind_param("sssi", $search, $search, $search, $limit);
    $stmtLog->execute();
    $q = $stmtLog->get_result();
} else {
    $q = mysqli_query($conn, "SELECT * FROM chat_logs ORDER BY id_log DESC LIMIT $limit");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Log Percakapan</title>

<link rel="stylesheet" href="../../assets/css/admin.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
:root {
    --bg: #f4f7fb;
    --card: #ffffff;
    --text: #1f2937;
    --muted: #6b7280;
    --border: #e5e7eb;
    --shadow: 0 10px 30px rgba(15, 23, 42, 0.08);

    --blue: #3b82f6;
    --blue-dark: #2563eb;
    --orange: #f59e0b;
    --orange-dark: #d97706;
    --red: #ef4444;
    --red-dark: #dc2626;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: "Segoe UI", Arial, sans-serif;
    background:
        radial-gradient(circle at top left, #eaf2ff 0%, transparent 25%),
        radial-gradient(circle at bottom right, #eefcf6 0%, transparent 25%),
        var(--bg);
    color: var(--text);
}

/* ================= MAIN CONTENT ================= */
.main-content {
    padding: 28px;
    min-height: 100vh;
}

@media (min-width: 992px) {
    .main-content {
        margin-left: 260px;
    }
}

/* ================= TOPBAR ================= */
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.topbar-left h2 {
    margin: 0;
    font-size: 34px;
    font-weight: 900;
    color: #111827;
    letter-spacing: -0.5px;
}

.topbar-left p {
    margin: 6px 0 0;
    color: var(--muted);
    font-size: 15px;
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 14px;
    background: rgba(255,255,255,0.82);
    border: 1px solid rgba(255,255,255,0.7);
    padding: 12px 16px;
    border-radius: 18px;
    box-shadow: var(--shadow);
    backdrop-filter: blur(8px);
}

.admin-badge {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--blue), var(--blue-dark));
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 18px;
}

.admin-info {
    line-height: 1.2;
}

.admin-info small {
    display: block;
    color: var(--muted);
    font-size: 12px;
}

.admin-info strong {
    font-size: 15px;
    color: #111827;
}

/* ================= BUTTON ================= */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 15px;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    color: #fff;
    text-decoration: none;
    font-size: 14px;
    font-weight: 700;
    transition: 0.25s ease;
    white-space: nowrap;
}

.btn:hover {
    transform: translateY(-1px);
    opacity: 0.96;
}

.btn-blue { background: linear-gradient(45deg, var(--blue), var(--blue-dark)); }
.btn-orange { background: linear-gradient(45deg, var(--orange), var(--orange-dark)); }
.btn-red { background: linear-gradient(45deg, var(--red), var(--red-dark)); }
.btn-sm { padding: 9px 12px; font-size: 13px; }

/* ================= CARD & TOOLBAR ================= */
.card {
    background: rgba(255,255,255,0.78);
    padding: 22px;
    border-radius: 24px;
    box-shadow: var(--shadow);
    border: 1px solid rgba(255,255,255,0.7);
    backdrop-filter: blur(8px);
    margin-bottom: 24px;
}

.toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    margin-bottom: 18px;
    flex-wrap: wrap;
}

.search-box {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
}

.search-box input {
    width: 380px;
    max-width: 100%;
    padding: 13px 15px;
    border: 1px solid #d1d5db;
    border-radius: 15px;
    outline: none;
    font-size: 14px;
    background: #fff;
    transition: 0.2s ease;
}

.search-box input:focus {
    border-color: rgba(59, 130, 246, 0.45);
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.08);
}

/* ================= TABLE ================= */
.table-wrap {
    width: 100%;
    overflow-x: auto;
    border-radius: 18px;
    background: #fff;
    border: 1px solid #eef2f7;
}

.table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    min-width: 900px;
}

.table thead th {
    background: #f8fafc;
    color: #374151;
    padding: 15px 14px;
    font-size: 14px;
    font-weight: 800;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.table tbody td {
    padding: 15px 14px;
    border-top: 1px solid #f1f5f9;
    font-size: 14px;
    color: #374151;
    vertical-align: top;
    line-height: 1.6;
    word-break: break-word;
}

.table tbody tr:hover { background: #f9fbff; }
.empty-row td { text-align: center; color: var(--muted); padding: 28px 12px; }

.table th:nth-child(1), .table td:nth-child(1) { width: 70px; }
.table th:nth-child(2), .table td:nth-child(2) { width: 170px; }
.table th:nth-child(3), .table td:nth-child(3) { width: 25%; }
.table th:nth-child(4), .table td:nth-child(4) { width: auto; }
.table th:nth-child(5), .table td:nth-child(5) { width: 120px; }

.action-group { display: flex; gap: 8px; flex-wrap: wrap; }
.badge-waktu {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 6px;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 12px;
    font-weight: 700;
}
.session-id {
    font-size: 11px;
    color: #9ca3af;
    display: block;
    margin-top: 5px;
    word-break: break-all;
}

/* ================= RESPONSIVE ================= */
@media (max-width: 991px) {
    .main-content { margin-left: 0; padding: 20px; }
}

@media (max-width: 768px) {
    .main-content { padding: 18px; }
    .topbar-left h2 { font-size: 28px; }
    .topbar-right { width: 100%; justify-content: space-between; }
    .toolbar { align-items: stretch; }
    .search-box { width: 100%; }
    .search-box input { width: 100%; }
    .toolbar > .btn { width: 100%; }
}
</style>
</head>

<body>

<?php include 'layout/sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <div class="topbar-left">
            <h2>Log Percakapan</h2>
            <p>Memantau riwayat percakapan pengguna dan bot (Menampilkan maksimal 100 terbaru)</p>
        </div>

        <div class="topbar-right">
            <div class="admin-badge">
                <?= htmlspecialchars(strtoupper(substr($username, 0, 1))) ?>
            </div>

            <div class="admin-info">
                <small>Login sebagai</small>
                <strong><?= htmlspecialchars($username) ?></strong>
            </div>

            <a href="../auth/logout.php" class="btn btn-red" onclick="return confirm('Anda yakin ingin keluar?');">
                <i class="fa fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </div>

    <div class="card">
        <div class="toolbar">
            <form method="get" class="search-box">
                <input
                    type="text"
                    name="keyword"
                    placeholder="Cari kata kunci pesan atau sesi"
                    value="<?= htmlspecialchars($keyword) ?>"
                >

                <button type="submit" class="btn btn-blue">
                    <i class="fa fa-search"></i> Cari
                </button>

                <a href="log_percakapan.php" class="btn btn-orange">
                    <i class="fa fa-rotate"></i> Reset
                </a>
            </form>

            <a href="?hapus_semua=1" class="btn btn-red" onclick="return confirm('PERINGATAN: Anda yakin ingin MENGHAPUS SEMUA log percakapan? Tindakan ini tidak dapat dibatalkan.')">
                <i class="fa fa-trash-can"></i> Hapus Semua Log
            </a>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Waktu / Sesi</th>
                        <th>Pesan Pengguna</th>
                        <th>Balasan Bot</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php $no = 1; ?>

                    <?php if (!$q || mysqli_num_rows($q) == 0): ?>
                        <tr class="empty-row">
                            <td colspan="5">
                                <?= $keyword !== '' ? 'Log percakapan tidak ditemukan.' : 'Belum ada log percakapan.' ?>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php while ($d = mysqli_fetch_assoc($q)): ?>
                        <tr>
                            <td><?= $no++ ?></td>

                            <td>
                                <span class="badge-waktu">
                                    <i class="fa fa-clock"></i> <?= date('d M Y, H:i', strtotime($d['created_at'])) ?>
                                </span>
                                <span class="session-id" title="Session ID">
                                    ID: <?= htmlspecialchars(substr($d['session_id'], 0, 8)) ?>...
                                </span>
                            </td>

                            <td><strong><?= nl2br(htmlspecialchars($d['user_message'] ?? '-')) ?></strong></td>

                            <td><?= nl2br(htmlspecialchars($d['bot_response'] ?? '-')) ?></td>

                            <td>
                                <a
                                    href="?hapus=<?= (int)($d['id_log'] ?? 0) ?>"
                                    class="btn btn-red btn-sm"
                                    onclick="return confirm('Hapus log percakapan ini?')"
                                >
                                    <i class="fa fa-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                    <?php
                    if (isset($stmtLog) && $stmtLog instanceof mysqli_stmt) {
                        $stmtLog->close();
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>

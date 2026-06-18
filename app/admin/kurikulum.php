<?php
// ================= AUTO LOAD DATABASE =================
$dir = __DIR__;
$dbFile = null;

while ($dir !== dirname($dir)) {
    if (file_exists($dir . '/config/database.php')) {
        $dbFile = $dir . '/config/database.php';
        break;
    }
    $dir = dirname($dir);
}

if (!$dbFile) {
    die("File database.php tidak ditemukan!");
}

require_once $dbFile;

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
    die("Koneksi database gagal.");
}

// ================= HELPER =================
function tableColumns(mysqli $conn, string $table): array
{
    $cols = [];
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table`");

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $cols[] = $row['Field'];
        }
        mysqli_free_result($result);
    }

    return $cols;
}

function firstExistingColumn(array $columns, array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) {
            return $candidate;
        }
    }

    return null;
}

// ================= KONFIGURASI DINAMIS TABEL =================
$table = 'mata_kuliah';
$columns = tableColumns($conn, $table);

if (empty($columns)) {
    die("Tabel `mata_kuliah` tidak ditemukan atau tidak bisa dibaca.");
}

$idCol        = firstExistingColumn($columns, ['id_mk', 'id']);
$semesterCol  = firstExistingColumn($columns, ['id_semester', 'semester']);
$kodeCol      = firstExistingColumn($columns, ['kode_mk', 'kode_matkul', 'kode']);
$namaCol      = firstExistingColumn($columns, ['nama_mk', 'nama_matkul', 'nama_mata_kuliah', 'mata_kuliah']);
$sksCol       = firstExistingColumn($columns, ['sks']);

if (!$idCol || !$semesterCol || !$kodeCol || !$namaCol || !$sksCol) {
    die("Struktur tabel `mata_kuliah` belum sesuai. Pastikan ada kolom id, semester, kode, nama, dan sks.");
}

/* ================= TAMBAH ================= */
if (isset($_POST['simpan'])) {
    $semester = trim($_POST['semester'] ?? '');
    $kode     = trim($_POST['kode'] ?? '');
    $nama     = trim($_POST['nama'] ?? '');
    $sks      = (int) ($_POST['sks'] ?? 0);

    if ($semester !== '' && $kode !== '' && $nama !== '' && $sks > 0) {
        $sql = "INSERT INTO `$table` (`$semesterCol`, `$kodeCol`, `$namaCol`, `$sksCol`) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $semester, $kode, $nama, $sks);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: kurikulum.php");
    exit;
}

/* ================= UPDATE ================= */
if (isset($_POST['update'])) {
    $id       = (int) ($_POST['id'] ?? 0);
    $semester = trim($_POST['semester'] ?? '');
    $kode     = trim($_POST['kode'] ?? '');
    $nama     = trim($_POST['nama'] ?? '');
    $sks      = (int) ($_POST['sks'] ?? 0);

    if ($id > 0 && $semester !== '' && $kode !== '' && $nama !== '' && $sks > 0) {
        $sql = "
            UPDATE `$table`
            SET `$semesterCol` = ?,
                `$kodeCol` = ?,
                `$namaCol` = ?,
                `$sksCol` = ?
            WHERE `$idCol` = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssii", $semester, $kode, $nama, $sks, $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: kurikulum.php");
    exit;
}

/* ================= HAPUS ================= */
if (isset($_GET['hapus'])) {
    $id = (int) ($_GET['hapus'] ?? 0);

    if ($id > 0) {
        $sql = "DELETE FROM `$table` WHERE `$idCol` = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: kurikulum.php");
    exit;
}

/* ================= SEARCH ================= */
$cari = trim($_GET['cari'] ?? '');

if ($cari !== '') {
    $sql = "
        SELECT * FROM `$table`
        WHERE CAST(`$semesterCol` AS CHAR) LIKE ?
           OR `$kodeCol` LIKE ?
           OR `$namaCol` LIKE ?
           OR CAST(`$sksCol` AS CHAR) LIKE ?
        ORDER BY `$semesterCol` ASC, `$kodeCol` ASC
    ";

    $stmt = $conn->prepare($sql);
    $keyword = "%" . $cari . "%";
    $stmt->bind_param("ssss", $keyword, $keyword, $keyword, $keyword);
    $stmt->execute();
    $q = $stmt->get_result();
} else {
    $q = mysqli_query($conn, "
        SELECT * FROM `$table`
        ORDER BY `$semesterCol` ASC, `$kodeCol` ASC
    ");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Mata Kuliah</title>

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
    --yellow: #f59e0b;
    --yellow-dark: #d97706;
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

.main-content {
    padding: 28px;
}

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
    font-size: 30px;
    font-weight: 800;
    color: #111827;
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
    background: rgba(255,255,255,0.75);
    border: 1px solid rgba(255,255,255,0.7);
    padding: 10px 14px;
    border-radius: 14px;
    box-shadow: var(--shadow);
    backdrop-filter: blur(8px);
}

.admin-badge {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--blue), var(--blue-dark));
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
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

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 14px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    color: #fff;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    transition: 0.25s ease;
}

.btn:hover {
    transform: translateY(-1px);
    opacity: 0.96;
}

.btn-blue {
    background: linear-gradient(45deg, var(--blue), var(--blue-dark));
}

.btn-red {
    background: linear-gradient(45deg, var(--red), var(--red-dark));
}

.btn-yellow {
    background: linear-gradient(45deg, var(--yellow), var(--yellow-dark));
}

.btn-sm {
    padding: 8px 12px;
    font-size: 13px;
}

.card {
    background: rgba(255,255,255,0.72);
    padding: 22px;
    border-radius: 22px;
    box-shadow: var(--shadow);
    border: 1px solid rgba(255,255,255,0.7);
    backdrop-filter: blur(8px);
}

.toolbar {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 18px;
    flex-wrap: wrap;
}

.search-form {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
}

.search-form input {
    min-width: 260px;
    padding: 11px 14px;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    outline: none;
    background: #fff;
    font-size: 14px;
}

.search-form input:focus,
.modal-box input:focus,
.modal-box select:focus {
    border-color: rgba(59, 130, 246, 0.45);
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.08);
}

.table-wrap {
    overflow-x: auto;
    border-radius: 18px;
    background: #fff;
    border: 1px solid #eef2f7;
}

.table {
    width: 100%;
    border-collapse: collapse;
    min-width: 820px;
}

.table thead th {
    background: #f8fafc;
    color: #374151;
    padding: 14px 12px;
    font-size: 14px;
    font-weight: 700;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.table tbody td {
    padding: 14px 12px;
    border-top: 1px solid #f1f5f9;
    font-size: 14px;
    color: #374151;
    vertical-align: middle;
}

.table tbody tr:hover {
    background: #f9fbff;
}

.empty-row td {
    text-align: center;
    color: var(--muted);
    padding: 24px 12px;
}

.action-group {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

/* ================= MODAL POPUP ================= */

.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    justify-content: center;
    align-items: flex-start;
    z-index: 9999;
    padding: 24px 16px;
    overflow-y: auto;
}

.modal.show {
    display: flex;
}

.modal-box {
    background: #fff;
    width: 100%;
    max-width: 560px;
    max-height: calc(100vh - 48px);
    border-radius: 20px;
    box-shadow: 0 24px 70px rgba(0,0,0,0.25);
    animation: fadeInUp 0.2s ease;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(12px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    padding: 18px 24px;
    border-bottom: 1px solid #e5e7eb;
    background: #fff;
}

.modal-header h3 {
    margin: 0;
    font-size: 22px;
    color: #111827;
}

#kurikulumForm {
    padding: 20px 24px;
    overflow-y: auto;
}

.form-group {
    margin-bottom: 13px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
}

.modal-box input,
.modal-box select {
    width: 100%;
    height: 48px;
    padding: 10px 13px;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    outline: none;
    font-size: 14px;
    transition: 0.2s ease;
    background: #fff;
}

.modal-actions {
    position: sticky;
    bottom: -20px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin: 18px -24px -20px;
    padding: 16px 24px;
    background: #fff;
    border-top: 1px solid #e5e7eb;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .main-content {
        padding: 18px;
    }

    .topbar-left h2 {
        font-size: 26px;
    }

    .topbar-right {
        width: 100%;
        justify-content: space-between;
    }

    .search-form {
        width: 100%;
    }

    .search-form input {
        min-width: 100%;
    }

    .modal {
        padding: 14px;
    }

    .modal-box {
        max-width: 100%;
        max-height: calc(100vh - 28px);
        border-radius: 16px;
    }

    .modal-header {
        padding: 16px 18px;
    }

    .modal-header h3 {
        font-size: 20px;
    }

    #kurikulumForm {
        padding: 16px 18px;
    }

    .modal-actions {
        margin: 16px -18px -16px;
        padding: 14px 18px;
    }

    .modal-actions .btn {
        flex: 1;
    }
}
</style>
</head>

<body>

<?php include 'layout/sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <div class="topbar-left">
            <h2>Mata Kuliah</h2>
            <p>Kelola data mata kuliah program studi</p>
        </div>

        <div class="topbar-right">
            <div class="admin-badge">
                <?= htmlspecialchars(strtoupper(substr($username, 0, 1))) ?>
            </div>

            <div class="admin-info">
                <small>Login sebagai</small>
                <strong><?= htmlspecialchars($username) ?></strong>
            </div>

            <a href="../auth/logout.php" class="btn btn-red" data-confirm-title="Konfirmasi Logout" data-confirm-message="Anda yakin ingin keluar?">
                <i class="fa fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </div>

    <div class="card">
        <div class="toolbar">
            <form method="GET" class="search-form">
                <input
                    type="text"
                    name="cari"
                    placeholder="Cari semester, kode, nama mata kuliah, atau SKS..."
                    value="<?= htmlspecialchars($cari) ?>"
                >

                <button class="btn btn-blue" type="submit">
                    <i class="fa fa-magnifying-glass"></i> Cari
                </button>

                <a href="kurikulum.php" class="btn btn-yellow">
                    <i class="fa fa-rotate"></i> Reset
                </a>
            </form>

            <button onclick="openModal()" class="btn btn-blue" type="button">
                <i class="fa fa-plus"></i> Tambah Data
            </button>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:70px;">No</th>
                        <th>Semester</th>
                        <th>Kode</th>
                        <th>Nama Mata Kuliah</th>
                        <th style="width:90px;">SKS</th>
                        <th style="width:180px;">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!$q || mysqli_num_rows($q) == 0): ?>
                        <tr class="empty-row">
                            <td colspan="6">Data mata kuliah tidak ditemukan.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; ?>

                        <?php while ($d = mysqli_fetch_assoc($q)): ?>
                            <?php
                                $jsonData = htmlspecialchars(json_encode([
                                    'id'       => $d[$idCol] ?? '',
                                    'semester' => $d[$semesterCol] ?? '',
                                    'kode'     => $d[$kodeCol] ?? '',
                                    'nama'     => $d[$namaCol] ?? '',
                                    'sks'      => $d[$sksCol] ?? ''
                                ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                            ?>

                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($d[$semesterCol] ?? '') ?></td>
                                <td><?= htmlspecialchars($d[$kodeCol] ?? '') ?></td>
                                <td><?= htmlspecialchars($d[$namaCol] ?? '') ?></td>
                                <td><?= htmlspecialchars($d[$sksCol] ?? '') ?></td>
                                <td>
                                    <div class="action-group">
                                        <button
                                            class="btn btn-yellow btn-sm"
                                            type="button"
                                            onclick="editData(JSON.parse(this.dataset.kurikulum))"
                                            data-kurikulum="<?= $jsonData ?>"
                                        >
                                            <i class="fa fa-pen-to-square"></i> Edit
                                        </button>

                                        <a
                                            href="?hapus=<?= (int)($d[$idCol] ?? 0) ?>"
                                            class="btn btn-red btn-sm"
                                            onclick="return confirm('Hapus data ini?')"
                                        >
                                            <i class="fa fa-trash"></i> Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>

                    <?php if (isset($stmt) && $stmt instanceof mysqli_stmt) $stmt->close(); ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL TAMBAH / EDIT -->
<div class="modal" id="modal">
    <div class="modal-box">

        <div class="modal-header">
            <h3 id="judul">Tambah Data</h3>
        </div>

        <form method="post" id="kurikulumForm">
            <input type="hidden" name="id" id="id">

            <div class="form-group">
                <label for="semester">Semester</label>
                <select name="semester" id="semester" required>
                    <option value="">-- Pilih Semester --</option>
                    <option value="1">Semester 1</option>
                    <option value="2">Semester 2</option>
                    <option value="3">Semester 3</option>
                    <option value="4">Semester 4</option>
                    <option value="5">Semester 5</option>
                    <option value="6">Semester 6</option>
                    <option value="7">Semester 7</option>
                    <option value="8">Semester 8</option>
                </select>
            </div>

            <div class="form-group">
                <label for="kode">Kode Mata Kuliah</label>
                <input type="text" name="kode" id="kode" placeholder="Masukkan kode mata kuliah" required>
            </div>

            <div class="form-group">
                <label for="nama">Nama Mata Kuliah</label>
                <input type="text" name="nama" id="nama" placeholder="Masukkan nama mata kuliah" required>
            </div>

            <div class="form-group">
                <label for="sks">SKS</label>
                <input type="number" name="sks" id="sks" placeholder="Masukkan jumlah SKS" min="1" max="6" required>
            </div>

            <div class="modal-actions">
                <button type="button" onclick="closeModal()" class="btn btn-red">
                    <i class="fa fa-xmark"></i> Batal
                </button>

                <button type="submit" name="simpan" id="btnSubmit" class="btn btn-blue">
                    <i class="fa fa-save"></i> Simpan
                </button>
            </div>
        </form>

    </div>
</div>

<script>
const modal = document.getElementById("modal");
const kurikulumForm = document.getElementById("kurikulumForm");
const judul = document.getElementById("judul");
const btnSubmit = document.getElementById("btnSubmit");

function openModal() {
    modal.classList.add("show");

    kurikulumForm.reset();

    document.getElementById("id").value = "";
    document.getElementById("semester").value = "";

    btnSubmit.name = "simpan";
    btnSubmit.innerHTML = '<i class="fa fa-save"></i> Simpan';
    judul.innerText = "Tambah Data";
}

function closeModal() {
    modal.classList.remove("show");
}

window.addEventListener("click", function(e) {
    if (e.target === modal) {
        closeModal();
    }
});

document.addEventListener("keydown", function(e) {
    if (e.key === "Escape") {
        closeModal();
    }
});

function editData(data) {
    modal.classList.add("show");

    document.getElementById("id").value = data.id || "";
    document.getElementById("semester").value = data.semester || "";
    document.getElementById("kode").value = data.kode || "";
    document.getElementById("nama").value = data.nama || "";
    document.getElementById("sks").value = data.sks || "";

    btnSubmit.name = "update";
    btnSubmit.innerHTML = '<i class="fa fa-pen-to-square"></i> Update';
    judul.innerText = "Edit Data";
}
</script>

<script src="../../assets/js/admin-delete-confirm.js"></script>
</body>
</html>

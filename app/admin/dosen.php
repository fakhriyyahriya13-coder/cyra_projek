<?php
session_start();

/* ================= AUTO DATABASE ================= */
$dir = __DIR__;
$found = false;

while ($dir !== dirname($dir)) {
    if (file_exists($dir . '/config/database.php')) {
        include $dir . '/config/database.php';
        $found = true;
        break;
    }
    $dir = dirname($dir);
}

if (!$found || !isset($conn)) {
    die("Database tidak ditemukan!");
}

/* ================= CEK LOGIN ================= */
if (!isset($_SESSION['login'])) {
    header("Location: ../auth/login.php");
    exit;
}

$username = $_SESSION['username'] ?? 'Admin';

/* ================= TAMBAH ================= */
if (isset($_POST['simpan'])) {
    $nidn       = trim($_POST['nidn'] ?? '');
    $nama_dosen = trim($_POST['nama_dosen'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $no_hp      = trim($_POST['no_hp'] ?? '');

    if ($nidn !== '' && $nama_dosen !== '') {
        $stmt = $conn->prepare("
            INSERT INTO dosen (nidn, nama_dosen, email, no_hp) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssss", $nidn, $nama_dosen, $email, $no_hp);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: dosen.php");
    exit;
}

/* ================= UPDATE ================= */
if (isset($_POST['update'])) {
    $id_dosen   = (int) ($_POST['id_dosen'] ?? 0);
    $nidn       = trim($_POST['nidn'] ?? '');
    $nama_dosen = trim($_POST['nama_dosen'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $no_hp      = trim($_POST['no_hp'] ?? '');

    if ($id_dosen > 0 && $nidn !== '' && $nama_dosen !== '') {
        $stmt = $conn->prepare("
            UPDATE dosen 
            SET nidn = ?, nama_dosen = ?, email = ?, no_hp = ? 
            WHERE id_dosen = ?
        ");
        $stmt->bind_param("ssssi", $nidn, $nama_dosen, $email, $no_hp, $id_dosen);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: dosen.php");
    exit;
}

/* ================= HAPUS ================= */
if (isset($_GET['hapus'])) {
    $idHapus = (int) ($_GET['hapus'] ?? 0);

    if ($idHapus > 0) {
        $stmt = $conn->prepare("DELETE FROM dosen WHERE id_dosen = ?");
        $stmt->bind_param("i", $idHapus);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: dosen.php");
    exit;
}

/* ================= SEARCH ================= */
$cari = trim($_GET['cari'] ?? '');

if ($cari !== '') {
    $sql = "
        SELECT * FROM dosen
        WHERE nama_dosen LIKE ?
           OR nidn LIKE ?
           OR email LIKE ?
           OR no_hp LIKE ?
        ORDER BY id_dosen DESC
    ";

    $stmt = $conn->prepare($sql);
    $keyword = "%" . $cari . "%";
    $stmt->bind_param("ssss", $keyword, $keyword, $keyword, $keyword);
    $stmt->execute();
    $q = $stmt->get_result();
} else {
    $q = mysqli_query($conn, "SELECT * FROM dosen ORDER BY id_dosen DESC");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Data Dosen</title>

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

/* TOPBAR */
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

/* BUTTON */
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

/* CARD */
.card {
    background: rgba(255,255,255,0.72);
    padding: 22px;
    border-radius: 22px;
    box-shadow: var(--shadow);
    border: 1px solid rgba(255,255,255,0.7);
    backdrop-filter: blur(8px);
}

/* TOOLBAR */
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
.modal-box input:focus {
    border-color: rgba(59, 130, 246, 0.45);
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.08);
}

/* TABLE */
.table-wrap {
    overflow-x: auto;
    border-radius: 18px;
    background: #fff;
    border: 1px solid #eef2f7;
}

.table {
    width: 100%;
    border-collapse: collapse;
    min-width: 760px;
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
    max-width: 540px;
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

#dosenForm {
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

.modal-box input {
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

/* RESPONSIVE */
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

    #dosenForm {
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

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-left">
            <h2>Data Dosen</h2>
            <p>Manajemen data dosen program studi</p>
        </div>

        <div class="topbar-right">
            <div class="admin-badge">
                <?= strtoupper(substr($username, 0, 1)) ?>
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

    <!-- CARD -->
    <div class="card">

        <div class="toolbar">
            <form method="GET" class="search-form">
                <input 
                    type="text" 
                    name="cari" 
                    placeholder="Cari dosen berdasarkan nama, NIDN, email, atau no HP..." 
                    value="<?= htmlspecialchars($cari) ?>"
                >

                <button class="btn btn-blue" type="submit">
                    <i class="fa fa-magnifying-glass"></i> Cari
                </button>

                <a href="dosen.php" class="btn btn-yellow">
                    <i class="fa fa-rotate"></i> Reset
                </a>
            </form>

            <button onclick="openModal()" class="btn btn-blue" type="button">
                <i class="fa fa-plus"></i> Tambah Dosen
            </button>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:70px;">No</th>
                        <th>NIDN</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>No HP</th>
                        <th style="width:180px;">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php $no = 1; ?>

                    <?php if (!$q || mysqli_num_rows($q) == 0): ?>
                        <tr class="empty-row">
                            <td colspan="6">Data tidak ditemukan.</td>
                        </tr>
                    <?php endif; ?>

                    <?php while ($r = mysqli_fetch_assoc($q)): ?>
                        <?php
                            $jsonData = htmlspecialchars(json_encode([
                                'id_dosen'   => $r['id_dosen'],
                                'nidn'       => $r['nidn'],
                                'nama_dosen' => $r['nama_dosen'],
                                'email'      => $r['email'],
                                'no_hp'      => $r['no_hp']
                            ]), ENT_QUOTES, 'UTF-8');
                        ?>

                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($r['nidn'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['nama_dosen'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['email'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['no_hp'] ?? '') ?></td>
                            <td>
                                <div class="action-group">
                                    <button
                                        class="btn btn-yellow btn-sm"
                                        type="button"
                                        onclick="editData(JSON.parse(this.dataset.dosen))"
                                        data-dosen="<?= $jsonData ?>"
                                    >
                                        <i class="fa fa-pen-to-square"></i> Edit
                                    </button>

                                    <a 
                                        href="?hapus=<?= (int)$r['id_dosen'] ?>"
                                        class="btn btn-red btn-sm"
                                        onclick="return confirm('Yakin ingin menghapus data dosen ini?')"
                                    >
                                        <i class="fa fa-trash"></i> Hapus
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>

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
            <h3 id="judul">Tambah Dosen</h3>
        </div>

        <form method="post" id="dosenForm">
            <input type="hidden" name="id_dosen" id="id">

            <div class="form-group">
                <label for="nidn">NIDN</label>
                <input type="text" name="nidn" id="nidn" placeholder="Masukkan NIDN" required>
            </div>

            <div class="form-group">
                <label for="nama">Nama Dosen</label>
                <input type="text" name="nama_dosen" id="nama" placeholder="Masukkan nama dosen" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" placeholder="Masukkan email">
            </div>

            <div class="form-group">
                <label for="hp">No HP</label>
                <input type="text" name="no_hp" id="hp" placeholder="Masukkan nomor HP">
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
const dosenForm = document.getElementById("dosenForm");
const judul = document.getElementById("judul");
const btnSubmit = document.getElementById("btnSubmit");

function openModal() {
    modal.classList.add("show");

    dosenForm.reset();

    document.getElementById("id").value = "";

    btnSubmit.name = "simpan";
    btnSubmit.innerHTML = '<i class="fa fa-save"></i> Simpan';
    judul.innerText = "Tambah Dosen";
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

    document.getElementById("id").value = data.id_dosen;
    document.getElementById("nidn").value = data.nidn ?? "";
    document.getElementById("nama").value = data.nama_dosen ?? "";
    document.getElementById("email").value = data.email ?? "";
    document.getElementById("hp").value = data.no_hp ?? "";

    btnSubmit.name = "update";
    btnSubmit.innerHTML = '<i class="fa fa-pen-to-square"></i> Update';
    judul.innerText = "Edit Dosen";
}
</script>

<script src="../../assets/js/admin-delete-confirm.js"></script>
</body>
</html>

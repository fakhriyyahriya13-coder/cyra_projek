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

// ================= TAMBAH DATA =================
if (isset($_POST['simpan'])) {
    $pertanyaan = trim($_POST['pertanyaan'] ?? '');
    $jawaban    = trim($_POST['jawaban'] ?? '');
    $kategori   = trim($_POST['kategori'] ?? '');

    if ($pertanyaan !== '' && $jawaban !== '' && $kategori !== '') {
        $stmt = $conn->prepare("INSERT INTO faq (pertanyaan, jawaban, kategori) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $pertanyaan, $jawaban, $kategori);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: faq.php");
    exit;
}

// ================= UPDATE DATA =================
if (isset($_POST['update'])) {
    $id_faq     = (int) ($_POST['id_faq'] ?? 0);
    $pertanyaan = trim($_POST['pertanyaan'] ?? '');
    $jawaban    = trim($_POST['jawaban'] ?? '');
    $kategori   = trim($_POST['kategori'] ?? '');

    if ($id_faq > 0 && $pertanyaan !== '' && $jawaban !== '' && $kategori !== '') {
        $stmt = $conn->prepare("UPDATE faq SET pertanyaan = ?, jawaban = ?, kategori = ? WHERE id_faq = ?");
        $stmt->bind_param("sssi", $pertanyaan, $jawaban, $kategori, $id_faq);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: faq.php");
    exit;
}

// ================= HAPUS DATA =================
if (isset($_GET['hapus'])) {
    $id_faq = (int) ($_GET['hapus'] ?? 0);

    if ($id_faq > 0) {
        $stmt = $conn->prepare("DELETE FROM faq WHERE id_faq = ?");
        $stmt->bind_param("i", $id_faq);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: faq.php");
    exit;
}

// ================= PENCARIAN DATA =================
$keyword = trim($_GET['keyword'] ?? '');

if ($keyword !== '') {
    $search = "%" . $keyword . "%";

    $stmtFaq = $conn->prepare("
        SELECT *
        FROM faq
        WHERE kategori LIKE ?
           OR pertanyaan LIKE ?
           OR jawaban LIKE ?
        ORDER BY id_faq DESC
    ");

    $stmtFaq->bind_param("sss", $search, $search, $search);
    $stmtFaq->execute();
    $q = $stmtFaq->get_result();
} else {
    $q = mysqli_query($conn, "SELECT * FROM faq ORDER BY id_faq DESC");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Kelola FAQ</title>

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

/* Kalau sidebar fixed lebar 260px */
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

.btn-blue {
    background: linear-gradient(45deg, var(--blue), var(--blue-dark));
}

.btn-orange {
    background: linear-gradient(45deg, var(--orange), var(--orange-dark));
}

.btn-red {
    background: linear-gradient(45deg, var(--red), var(--red-dark));
}

.btn-sm {
    padding: 9px 12px;
    font-size: 13px;
}

/* ================= CARD ================= */
.card {
    background: rgba(255,255,255,0.78);
    padding: 22px;
    border-radius: 24px;
    box-shadow: var(--shadow);
    border: 1px solid rgba(255,255,255,0.7);
    backdrop-filter: blur(8px);
    margin-bottom: 24px;
}

/* ================= TOOLBAR ================= */
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

.search-box input:focus,
.modal-box input:focus,
.modal-box textarea:focus,
.modal-box select:focus {
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

.table tbody tr:hover {
    background: #f9fbff;
}

.table th:nth-child(1),
.table td:nth-child(1) {
    width: 70px;
}

.table th:nth-child(2),
.table td:nth-child(2) {
    width: 150px;
}

.table th:nth-child(3),
.table td:nth-child(3) {
    width: 28%;
}

.table th:nth-child(4),
.table td:nth-child(4) {
    width: auto;
}

.table th:nth-child(5),
.table td:nth-child(5) {
    width: 190px;
}

.empty-row td {
    text-align: center;
    color: var(--muted);
    padding: 28px 12px;
}

.action-group {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.badge-kategori {
    display: inline-block;
    padding: 7px 12px;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 12px;
    font-weight: 800;
}

/* ================= MODAL POPUP ================= */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    justify-content: center;
    align-items: flex-start;
    z-index: 99999;
    padding: 30px 16px;
    overflow-y: auto;
}

.modal-overlay.show {
    display: flex;
}

.modal-box {
    width: 100%;
    max-width: 760px;
    max-height: calc(100vh - 60px);
    background: #fff;
    border-radius: 22px;
    box-shadow: 0 25px 70px rgba(15, 23, 42, 0.25);
    animation: popup 0.22s ease;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

@keyframes popup {
    from {
        transform: translateY(16px);
        opacity: 0;
    }

    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    display: flex;
    justify-content: flex-start;
    align-items: center;
    padding: 18px 24px;
    border-bottom: 1px solid #e5e7eb;
    background: #fff;
}

.modal-header h3 {
    margin: 0;
    font-size: 22px;
    color: #111827;
}

#faqForm {
    padding: 20px 24px;
    overflow-y: auto;
}

.form-grid {
    display: grid;
    gap: 14px;
}

.form-group label {
    display: block;
    margin-bottom: 7px;
    font-size: 13px;
    font-weight: 700;
    color: #374151;
}

.modal-box textarea,
.modal-box select,
.modal-box input[type="text"] {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #d1d5db;
    border-radius: 14px;
    outline: none;
    font-size: 14px;
    font-family: inherit;
    background: #fff;
    transition: 0.2s ease;
}

.modal-box select,
.modal-box input[type="text"] {
    height: 48px;
}

.modal-box textarea {
    min-height: 150px;
    max-height: 280px;
    resize: vertical;
    line-height: 1.6;
}

.form-actions {
    position: sticky;
    bottom: -20px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
    margin: 18px -24px -20px;
    padding: 16px 24px;
    background: #fff;
    border-top: 1px solid #e5e7eb;
}

/* ================= RESPONSIVE ================= */
@media (max-width: 991px) {
    .main-content {
        margin-left: 0;
        padding: 20px;
    }
}

@media (max-width: 768px) {
    .main-content {
        padding: 18px;
    }

    .topbar-left h2 {
        font-size: 28px;
    }

    .topbar-right {
        width: 100%;
        justify-content: space-between;
    }

    .toolbar {
        align-items: stretch;
    }

    .search-box {
        width: 100%;
    }

    .search-box input {
        width: 100%;
    }

    .toolbar > .btn {
        width: 100%;
    }

    .table {
        min-width: 780px;
    }

    .modal-overlay {
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

    #faqForm {
        padding: 16px 18px;
    }

    .form-actions {
        margin: 16px -18px -16px;
        padding: 14px 18px;
    }

    .form-actions .btn {
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
            <h2>Kelola FAQ</h2>
            <p>Manajemen pertanyaan dan jawaban chatbot CYRA</p>
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
            <form method="get" class="search-box">
                <input
                    type="text"
                    name="keyword"
                    placeholder="Cari kategori, pertanyaan, atau jawaban"
                    value="<?= htmlspecialchars($keyword) ?>"
                >

                <button type="submit" class="btn btn-blue">
                    <i class="fa fa-search"></i> Cari
                </button>

                <a href="faq.php" class="btn btn-orange">
                    <i class="fa fa-rotate"></i> Reset
                </a>
            </form>

            <button type="button" class="btn btn-blue" onclick="openModal()">
                <i class="fa fa-plus"></i> Tambah Data
            </button>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kategori</th>
                        <th>Pertanyaan</th>
                        <th>Jawaban</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php $no = 1; ?>

                    <?php if (!$q || mysqli_num_rows($q) == 0): ?>
                        <tr class="empty-row">
                            <td colspan="5">
                                <?= $keyword !== '' ? 'Data FAQ tidak ditemukan.' : 'Belum ada data FAQ.' ?>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php while ($d = mysqli_fetch_assoc($q)): ?>
                        <?php
                            $dataFaq = [
                                'id_faq'     => $d['id_faq'] ?? '',
                                'kategori'   => $d['kategori'] ?? '',
                                'pertanyaan' => $d['pertanyaan'] ?? '',
                                'jawaban'    => $d['jawaban'] ?? ''
                            ];

                            $jsonData = htmlspecialchars(
                                json_encode($dataFaq, JSON_UNESCAPED_UNICODE),
                                ENT_QUOTES,
                                'UTF-8'
                            );
                        ?>

                        <tr>
                            <td><?= $no++ ?></td>

                            <td>
                                <span class="badge-kategori">
                                    <?= htmlspecialchars($d['kategori'] ?? '-') ?>
                                </span>
                            </td>

                            <td><?= nl2br(htmlspecialchars($d['pertanyaan'] ?? '-')) ?></td>

                            <td><?= nl2br(htmlspecialchars($d['jawaban'] ?? '-')) ?></td>

                            <td>
                                <div class="action-group">
                                    <button
                                        type="button"
                                        class="btn btn-orange btn-sm"
                                        onclick="editData(JSON.parse(this.dataset.faq))"
                                        data-faq="<?= $jsonData ?>"
                                    >
                                        <i class="fa fa-pen-to-square"></i> Edit
                                    </button>

                                    <a
                                        href="?hapus=<?= (int)($d['id_faq'] ?? 0) ?>"
                                        class="btn btn-red btn-sm"
                                        onclick="return confirm('Hapus FAQ ini?')"
                                    >
                                        <i class="fa fa-trash"></i> Hapus
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                    <?php
                    if (isset($stmtFaq) && $stmtFaq instanceof mysqli_stmt) {
                        $stmtFaq->close();
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL TAMBAH / EDIT -->
<div id="faqModal" class="modal-overlay">
    <div class="modal-box">

        <div class="modal-header">
            <h3 id="modalTitle">Tambah FAQ Baru</h3>
        </div>

        <form method="post" class="form-grid" id="faqForm">
            <input type="hidden" name="id_faq" id="id_faq">

            <div class="form-group">
                <label for="kategori">Kategori</label>
                <select id="kategori" name="kategori" required>
                    <option value="">-- Pilih Kategori --</option>
                    <option value="Umum">Umum</option>
                    <option value="FRS">FRS</option>
                    <option value="PEMBAYARAN">PEMBAYARAN</option>
                    <option value="KP">KP</option>
                    <option value="TA">TA</option>
                    <option value="JADWAL">JADWAL</option>
                    <option value="KURIKULUM">KURIKULUM</option>
                    <option value="DOSEN">DOSEN</option>
                    <option value="PROSEDUR">PROSEDUR</option>
                </select>
            </div>

            <div class="form-group">
                <label for="pertanyaan">Pertanyaan</label>
                <textarea
                    id="pertanyaan"
                    name="pertanyaan"
                    placeholder="Masukkan pertanyaan FAQ..."
                    required
                ></textarea>
            </div>

            <div class="form-group">
                <label for="jawaban">Jawaban</label>
                <textarea
                    id="jawaban"
                    name="jawaban"
                    placeholder="Masukkan jawaban FAQ..."
                    required
                ></textarea>
            </div>

            <div class="form-actions">
                <button type="button" onclick="closeModal()" class="btn btn-orange">
                    <i class="fa fa-xmark"></i> Batal
                </button>

                <button type="submit" name="simpan" id="btnSubmit" class="btn btn-blue">
                    <i class="fa fa-floppy-disk"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const faqModal = document.getElementById("faqModal");
const faqForm = document.getElementById("faqForm");
const modalTitle = document.getElementById("modalTitle");
const btnSubmit = document.getElementById("btnSubmit");

function openModal() {
    faqModal.classList.add("show");

    faqForm.reset();

    document.getElementById("id_faq").value = "";
    document.getElementById("kategori").value = "";

    btnSubmit.name = "simpan";
    btnSubmit.innerHTML = '<i class="fa fa-floppy-disk"></i> Simpan';
    modalTitle.innerText = "Tambah FAQ Baru";
}

function closeModal() {
    faqModal.classList.remove("show");
}

function editData(data) {
    faqModal.classList.add("show");
    const kategori = data.kategori === "UMUM" ? "Umum" : (data.kategori || "");

    document.getElementById("id_faq").value = data.id_faq || "";
    document.getElementById("kategori").value = kategori;
    document.getElementById("pertanyaan").value = data.pertanyaan || "";
    document.getElementById("jawaban").value = data.jawaban || "";

    btnSubmit.name = "update";
    btnSubmit.innerHTML = '<i class="fa fa-pen-to-square"></i> Update';
    modalTitle.innerText = "Edit FAQ";
}

window.addEventListener("click", function(e) {
    if (e.target === faqModal) {
        closeModal();
    }
});

document.addEventListener("keydown", function(e) {
    if (e.key === "Escape") {
        closeModal();
    }
});
</script>

<script src="../../assets/js/admin-delete-confirm.js"></script>
</body>
</html>

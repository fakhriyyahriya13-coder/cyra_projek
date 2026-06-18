<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$username = $_SESSION['username'] ?? 'Admin';

$hari = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
$bulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
$tanggal_sekarang = $hari[date("w")] . ", " . date("j") . " " . $bulan[date("n")] . " " . date("Y");
?>

<div class="topbar">

    <!-- LEFT -->
    <div>
        <h2>Dashboard Admin</h2>
        <p><?= $tanggal_sekarang; ?></p>
    </div>

    <!-- RIGHT -->
    <div class="topbar-right">

        <span>
            <i class="fa fa-user-circle"></i>
            <?= htmlspecialchars($username); ?>
        </span>

        <a href="../auth/logout.php" class="btn-logout"
           data-confirm-title="Konfirmasi Logout"
           data-confirm-message="Anda yakin ingin keluar?">
            <i class="fa fa-sign-out-alt"></i> Logout
        </a>

    </div>

</div>

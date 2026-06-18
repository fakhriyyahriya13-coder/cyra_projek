<?php
session_start();

/*
|--------------------------------------------------------------------------
| CEK LOGIN ADMIN
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| KONEKSI DATABASE
|--------------------------------------------------------------------------
*/
$dir = __DIR__;
$found = false;

while ($dir !== dirname($dir)) {
    if (file_exists($dir . '/config/database.php')) {
        require_once $dir . '/config/database.php';
        $found = true;
        break;
    }

    $dir = dirname($dir);
}

if (!$found || !isset($conn)) {
    die("File config/database.php tidak ditemukan atau koneksi database gagal!");
}

/*
|--------------------------------------------------------------------------
| AMBIL ID USER DARI SESSION
|--------------------------------------------------------------------------
*/
$id_user = $_SESSION['id_user'] ?? 0;

if ($id_user <= 0) {
    session_destroy();
    header("Location: ../auth/login.php");
    exit;
}

$pesan_sukses = "";
$pesan_error = "";

/*
|--------------------------------------------------------------------------
| PROSES UPDATE AKUN
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password_baru = trim($_POST['password_baru'] ?? '');
    $konfirmasi_password = trim($_POST['konfirmasi_password'] ?? '');

    if ($nama === '' || $email === '' || $username === '') {
        $pesan_error = "Nama, email, dan username wajib diisi!";
    } else {

        /*
        |--------------------------------------------------------------------------
        | CEK USERNAME SUDAH DIPAKAI USER LAIN ATAU BELUM
        |--------------------------------------------------------------------------
        */
        $cek_username = mysqli_prepare(
            $conn,
            "SELECT id_user FROM users WHERE username = ? AND id_user != ? LIMIT 1"
        );

        if (!$cek_username) {
            $pesan_error = "Query cek username gagal: " . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param($cek_username, "si", $username, $id_user);
            mysqli_stmt_execute($cek_username);
            $result_username = mysqli_stmt_get_result($cek_username);

            if ($result_username && mysqli_num_rows($result_username) > 0) {
                $pesan_error = "Username sudah digunakan user lain!";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CEK EMAIL SUDAH DIPAKAI USER LAIN ATAU BELUM
        |--------------------------------------------------------------------------
        */
        if ($pesan_error === "") {
            $cek_email = mysqli_prepare(
                $conn,
                "SELECT id_user FROM users WHERE email = ? AND id_user != ? LIMIT 1"
            );

            if (!$cek_email) {
                $pesan_error = "Query cek email gagal: " . mysqli_error($conn);
            } else {
                mysqli_stmt_bind_param($cek_email, "si", $email, $id_user);
                mysqli_stmt_execute($cek_email);
                $result_email = mysqli_stmt_get_result($cek_email);

                if ($result_email && mysqli_num_rows($result_email) > 0) {
                    $pesan_error = "Email sudah digunakan user lain!";
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE DATA TANPA PASSWORD
        |--------------------------------------------------------------------------
        */
        if ($pesan_error === "" && $password_baru === '' && $konfirmasi_password === '') {
            $update = mysqli_prepare(
                $conn,
                "UPDATE users SET nama = ?, email = ?, username = ? WHERE id_user = ?"
            );

            if (!$update) {
                $pesan_error = "Query update akun gagal: " . mysqli_error($conn);
            } else {
                mysqli_stmt_bind_param($update, "sssi", $nama, $email, $username, $id_user);

                if (mysqli_stmt_execute($update)) {
                    $_SESSION['nama'] = $nama;
                    $_SESSION['email'] = $email;
                    $_SESSION['username'] = $username;

                    $pesan_sukses = "Akun berhasil diperbarui!";
                } else {
                    $pesan_error = "Akun gagal diperbarui: " . mysqli_error($conn);
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE DATA DENGAN PASSWORD BARU
        |--------------------------------------------------------------------------
        */
        if ($pesan_error === "" && ($password_baru !== '' || $konfirmasi_password !== '')) {
            if ($password_baru !== $konfirmasi_password) {
                $pesan_error = "Konfirmasi password tidak sama!";
            } elseif (strlen($password_baru) < 5) {
                $pesan_error = "Password minimal 5 karakter!";
            } else {
                $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);

                $update = mysqli_prepare(
                    $conn,
                    "UPDATE users SET nama = ?, email = ?, username = ?, password = ? WHERE id_user = ?"
                );

                if (!$update) {
                    $pesan_error = "Query update akun gagal: " . mysqli_error($conn);
                } else {
                    mysqli_stmt_bind_param($update, "ssssi", $nama, $email, $username, $password_hash, $id_user);

                    if (mysqli_stmt_execute($update)) {
                        $_SESSION['nama'] = $nama;
                        $_SESSION['email'] = $email;
                        $_SESSION['username'] = $username;

                        $pesan_sukses = "Akun dan password berhasil diperbarui!";
                    } else {
                        $pesan_error = "Akun gagal diperbarui: " . mysqli_error($conn);
                    }
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| AMBIL DATA USER TERBARU
|--------------------------------------------------------------------------
*/
$stmt = mysqli_prepare(
    $conn,
    "SELECT id_user, nama, email, username, role FROM users WHERE id_user = ? LIMIT 1"
);

if (!$stmt) {
    die("Query ambil user gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) < 1) {
    session_destroy();
    header("Location: ../auth/login.php");
    exit;
}

$user = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Pengaturan Akun Admin</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #f1f5f9;
    color: #1f2937;
}

.container {
    max-width: 720px;
    margin: 50px auto;
    padding: 0 20px;
}

.card {
    background: #ffffff;
    border-radius: 18px;
    padding: 32px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

.header {
    margin-bottom: 28px;
}

.header h2 {
    margin: 0 0 8px;
    font-size: 28px;
    color: #111827;
}

.header p {
    margin: 0;
    color: #6b7280;
    font-size: 15px;
}

.form-group {
    margin-bottom: 18px;
}

label {
    display: block;
    margin-bottom: 7px;
    font-weight: 600;
    color: #374151;
}

input {
    width: 100%;
    padding: 13px 14px;
    border: 1px solid #d1d5db;
    border-radius: 11px;
    font-size: 15px;
    outline: none;
    background: #fff;
}

input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
}

.password-box {
    position: relative;
}

.password-box input {
    padding-right: 48px;
}

.eye {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    user-select: none;
}

.info {
    font-size: 13px;
    color: #6b7280;
    margin-top: 6px;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
    padding: 13px;
    border-radius: 10px;
    margin-bottom: 18px;
    font-size: 14px;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    padding: 13px;
    border-radius: 10px;
    margin-bottom: 18px;
    font-size: 14px;
}

.actions {
    display: flex;
    gap: 12px;
    margin-top: 25px;
}

button {
    border: none;
    background: #2563eb;
    color: white;
    padding: 13px 20px;
    border-radius: 11px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 700;
}

button:hover {
    background: #1d4ed8;
}

.btn-back {
    text-decoration: none;
    background: #e5e7eb;
    color: #111827;
    padding: 13px 20px;
    border-radius: 11px;
    font-size: 15px;
    font-weight: 700;
}

.btn-back:hover {
    background: #d1d5db;
}

.badge {
    display: inline-block;
    padding: 5px 10px;
    background: #dbeafe;
    color: #1d4ed8;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 700;
    margin-top: 8px;
}

@media (max-width: 600px) {
    .container {
        margin: 25px auto;
    }

    .card {
        padding: 24px;
    }

    .actions {
        flex-direction: column;
    }

    button,
    .btn-back {
        width: 100%;
        text-align: center;
    }
}
</style>
</head>

<body>

<div class="container">
    <div class="card">

        <div class="header">
            <h2>Pengaturan Akun Admin</h2>
            <p>Ubah nama, username, email, dan password akun admin.</p>
            <span class="badge">Role: <?php echo htmlspecialchars($user['role']); ?></span>
        </div>

        <?php if ($pesan_sukses !== ""): ?>
            <div class="alert-success"><?php echo htmlspecialchars($pesan_sukses); ?></div>
        <?php endif; ?>

        <?php if ($pesan_error !== ""): ?>
            <div class="alert-error"><?php echo htmlspecialchars($pesan_error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Nama</label>
                <input type="text" name="nama" value="<?php echo htmlspecialchars($user['nama']); ?>" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                <div class="info">Username ini yang dipakai untuk login admin.</div>
            </div>

            <div class="form-group">
                <label>Password Baru</label>
                <div class="password-box">
                    <input type="password" name="password_baru" id="password_baru" placeholder="Kosongkan jika tidak ingin mengganti password">
                    <span class="eye" onclick="togglePassword('password_baru', this)">👁</span>
                </div>
                <div class="info">Minimal 5 karakter. Boleh huruf, angka, atau campuran.</div>
            </div>

            <div class="form-group">
                <label>Konfirmasi Password Baru</label>
                <div class="password-box">
                    <input type="password" name="konfirmasi_password" id="konfirmasi_password" placeholder="Ulangi password baru">
                    <span class="eye" onclick="togglePassword('konfirmasi_password', this)">👁</span>
                </div>
            </div>

            <div class="actions">
                <button type="submit">Simpan Perubahan</button>
                <a href="dashboard.php" class="btn-back">Kembali</a>
            </div>
        </form>

    </div>
</div>

<script>
function togglePassword(id, icon) {
    const input = document.getElementById(id);

    if (input.type === "password") {
        input.type = "text";
        icon.textContent = "🙈";
    } else {
        input.type = "password";
        icon.textContent = "👁";
    }
}
</script>

</body>
</html>
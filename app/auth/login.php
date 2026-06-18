<?php
session_start();

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
| JIKA SUDAH LOGIN
|--------------------------------------------------------------------------
*/
if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    header("Location: ../admin/dashboard.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| MODE HALAMAN
|--------------------------------------------------------------------------
*/
$lupa_sandi = isset($_GET['lupa_sandi']) && $_GET['lupa_sandi'] == '1';

$pesan_error = "";
$pesan_sukses = "";

/*
|--------------------------------------------------------------------------
| PROSES UBAH PASSWORD ADMIN
|--------------------------------------------------------------------------
| Bisa cari akun berdasarkan:
| - username
| - email
| - nama
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ubah_password'])) {
    $lupa_sandi = true;

    $username = trim($_POST['username'] ?? '');
    $password_baru = trim($_POST['password_baru'] ?? '');
    $konfirmasi_password = trim($_POST['konfirmasi_password'] ?? '');

    if ($username === '' || $password_baru === '' || $konfirmasi_password === '') {
        $pesan_error = "Semua data wajib diisi!";
    } elseif ($password_baru !== $konfirmasi_password) {
        $pesan_error = "Konfirmasi password tidak sama!";
    } elseif (strlen($password_baru) < 5) {
        $pesan_error = "Password baru minimal 5 karakter!";
    } else {

        /*
        |--------------------------------------------------------------------------
        | CEK AKUN DI TABEL users
        |--------------------------------------------------------------------------
        */
        $cek = mysqli_prepare(
            $conn,
            "SELECT id_user, nama, email, username, role 
             FROM users 
             WHERE username = ? OR email = ? OR nama = ?
             LIMIT 1"
        );

        if (!$cek) {
            $pesan_error = "Query cek akun gagal: " . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param($cek, "sss", $username, $username, $username);
            mysqli_stmt_execute($cek);

            $result = mysqli_stmt_get_result($cek);

            if (!$result || mysqli_num_rows($result) < 1) {
                $pesan_error = "Akun tidak ditemukan! Cek username, email, atau nama.";
            } else {
                $user = mysqli_fetch_assoc($result);

                /*
                |--------------------------------------------------------------------------
                | CEK ROLE ADMIN
                |--------------------------------------------------------------------------
                */
                if (($user['role'] ?? '') !== 'admin') {
                    $pesan_error = "Akun ditemukan, tetapi role bukan admin. Role saat ini: " . htmlspecialchars($user['role']);
                } else {
                    $id_user = $user['id_user'];

                    /*
                    |--------------------------------------------------------------------------
                    | HASH PASSWORD BARU
                    |--------------------------------------------------------------------------
                    */
                    $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);

                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE PASSWORD KE DATABASE
                    |--------------------------------------------------------------------------
                    */
                    $update = mysqli_prepare(
                        $conn,
                        "UPDATE users SET password = ? WHERE id_user = ?"
                    );

                    if (!$update) {
                        $pesan_error = "Query update password gagal: " . mysqli_error($conn);
                    } else {
                        mysqli_stmt_bind_param($update, "si", $password_hash, $id_user);

                        if (mysqli_stmt_execute($update)) {
                            $_SESSION['reset_success'] = "Password berhasil diubah. Silakan login dengan password baru.";
                            header("Location: login.php");
                            exit;
                        } else {
                            $pesan_error = "Password gagal diubah: " . mysqli_error($conn);
                        }
                    }
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| PESAN SUKSES RESET PASSWORD
|--------------------------------------------------------------------------
*/
if (isset($_SESSION['reset_success'])) {
    $pesan_sukses = $_SESSION['reset_success'];
    unset($_SESSION['reset_success']);
}

/*
|--------------------------------------------------------------------------
| PESAN ERROR LOGIN
|--------------------------------------------------------------------------
*/
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'kosong') {
        $pesan_error = "Username dan password wajib diisi!";
    } elseif ($_GET['error'] === 'bukan_admin') {
        $pesan_error = "Akun ini bukan admin!";
    } else {
        $pesan_error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?php echo $lupa_sandi ? 'Lupa Sandi' : 'Login Admin'; ?></title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: 'Segoe UI', Arial, sans-serif;
    background-color: #e5e5e5;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

/* BOX */
.login-box {
    background: #f4f4f4;
    padding: 45px 42px;
    width: 450px;
    border-radius: 18px;
    box-shadow: 0 12px 35px rgba(0,0,0,0.18);
    text-align: center;
}

/* TITLE */
.login-box h2 {
    margin: 0 0 8px;
    font-size: 32px;
    color: #000;
    font-weight: 800;
}

.login-box p {
    color: #666;
    margin: 0 0 28px;
    font-size: 18px;
}

/* INFO */
.info-text {
    font-size: 14px;
    color: #666;
    margin-top: -12px;
    margin-bottom: 24px;
    line-height: 1.5;
}

/* INPUT */
.input-group {
    margin-bottom: 18px;
    position: relative;
}

.input-group input {
    width: 100%;
    padding: 15px 48px 15px 15px;
    border-radius: 11px;
    border: 1px solid #ccc;
    outline: none;
    font-size: 15px;
    background: #fff;
}

.input-group input:focus {
    border-color: #1e2bb8;
    box-shadow: 0 0 0 3px rgba(30, 43, 184, 0.12);
}

/* EYE */
.eye-icon {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 18px;
    user-select: none;
    color: #555;
}

.eye-icon:hover {
    color: #1e2bb8;
}

/* FORGOT */
.forgot-password {
    text-align: right;
    margin-top: -7px;
    margin-bottom: 25px;
}

.forgot-password a {
    color: #1e2bb8;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
}

.forgot-password a:hover {
    text-decoration: underline;
}

/* BUTTON */
button,
.btn {
    background: #1e2bb8;
    color: white;
    border: none;
    padding: 14px;
    width: 65%;
    border-radius: 11px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.25s;
    text-decoration: none;
    display: inline-block;
}

button:hover,
.btn:hover {
    background: #16208f;
}

/* ALERT */
.error {
    color: #b00020;
    background: #ffe6e9;
    padding: 13px;
    border-radius: 9px;
    margin-top: 20px;
    font-size: 14px;
    text-align: center;
    line-height: 1.5;
}

.success {
    color: #087a25;
    background: #e6ffed;
    padding: 13px;
    border-radius: 9px;
    margin-top: 20px;
    font-size: 14px;
    text-align: center;
    line-height: 1.5;
}

/* BACK */
.back-link {
    margin-top: 24px;
}

.back-link a {
    color: #1e2bb8;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
}

.back-link a:hover {
    text-decoration: underline;
}

/* RESPONSIVE */
@media (max-width: 520px) {
    .login-box {
        width: calc(100% - 30px);
        padding: 35px 25px;
    }

    button,
    .btn {
        width: 100%;
    }
}
</style>
</head>

<body>

<div class="login-box">

    <?php if ($lupa_sandi): ?>

        <h2>Lupa Sandi?</h2>
        <p>Ubah Password Admin</p>

        <div class="info-text">
            Masukkan username, email, atau nama akun admin. Lalu buat password baru.
        </div>

        <form action="login.php?lupa_sandi=1" method="post">
            <div class="input-group">
                <input 
                    type="text" 
                    name="username" 
                    placeholder="Username / Email / Nama Admin" 
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    required
                >
            </div>

            <div class="input-group">
                <input 
                    type="password" 
                    name="password_baru" 
                    id="password_baru" 
                    placeholder="Password Baru" 
                    required
                >
                <span class="eye-icon" onclick="togglePassword('password_baru', this)">👁</span>
            </div>

            <div class="input-group">
                <input 
                    type="password" 
                    name="konfirmasi_password" 
                    id="konfirmasi_password" 
                    placeholder="Konfirmasi Password Baru" 
                    required
                >
                <span class="eye-icon" onclick="togglePassword('konfirmasi_password', this)">👁</span>
            </div>

            <button type="submit" name="ubah_password">Ubah Password</button>
        </form>

        <?php if ($pesan_error !== ""): ?>
            <div class="error"><?php echo htmlspecialchars($pesan_error); ?></div>
        <?php endif; ?>

        <div class="back-link">
            <a href="login.php">Kembali ke Login</a>
        </div>

    <?php else: ?>

        <h2>Login Admin</h2>
        <p>Sistem Informasi Admin</p>

        <form action="proses_login.php" method="post">
            <div class="input-group">
                <input 
                    type="text" 
                    name="username" 
                    placeholder="Username atau Email" 
                    required
                >
            </div>

            <div class="input-group">
                <input 
                    type="password" 
                    name="password" 
                    id="password_login" 
                    placeholder="Password" 
                    required
                >
                <span class="eye-icon" onclick="togglePassword('password_login', this)">👁</span>
            </div>

            <div class="forgot-password">
                <a href="login.php?lupa_sandi=1">Lupa Sandi?</a>
            </div>

            <button type="submit">Login</button>
        </form>

        <?php if ($pesan_error !== ""): ?>
            <div class="error"><?php echo htmlspecialchars($pesan_error); ?></div>
        <?php endif; ?>

        <?php if ($pesan_sukses !== ""): ?>
            <div class="success"><?php echo htmlspecialchars($pesan_sukses); ?></div>
        <?php endif; ?>

    <?php endif; ?>

</div>

<script>
function togglePassword(inputId, icon) {
    const input = document.getElementById(inputId);

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
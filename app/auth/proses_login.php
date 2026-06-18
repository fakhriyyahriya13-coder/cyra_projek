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
| CEGAH AKSES SELAIN POST
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| AMBIL INPUT
|--------------------------------------------------------------------------
*/
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    header("Location: login.php?error=kosong");
    exit;
}

/*
|--------------------------------------------------------------------------
| AMBIL DATA USER ADMIN
|--------------------------------------------------------------------------
| Bisa login pakai username atau email.
|--------------------------------------------------------------------------
*/
$query = "
    SELECT id_user, nama, email, username, password, role 
    FROM users 
    WHERE username = ? OR email = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    die("Query login gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "ss", $username, $username);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) !== 1) {
    header("Location: login.php?error=salah");
    exit;
}

$user = mysqli_fetch_assoc($result);

/*
|--------------------------------------------------------------------------
| CEK ROLE ADMIN
|--------------------------------------------------------------------------
*/
if (($user['role'] ?? '') !== 'admin') {
    header("Location: login.php?error=bukan_admin");
    exit;
}

$password_db = $user['password'];
$password_benar = false;

/*
|--------------------------------------------------------------------------
| CEK PASSWORD HASH
|--------------------------------------------------------------------------
*/
if (password_verify($password, $password_db)) {
    $password_benar = true;
}

/*
|--------------------------------------------------------------------------
| CEK PASSWORD PLAIN TEXT LAMA
|--------------------------------------------------------------------------
| Contoh di database masih: admin123
| Jika benar, otomatis diubah menjadi hash.
|--------------------------------------------------------------------------
*/
if (!$password_benar && $password === $password_db) {
    $password_benar = true;

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $update = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id_user = ?");

    if (!$update) {
        die("Query update password gagal: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($update, "si", $password_hash, $user['id_user']);
    mysqli_stmt_execute($update);
}

/*
|--------------------------------------------------------------------------
| JIKA PASSWORD SALAH
|--------------------------------------------------------------------------
*/
if (!$password_benar) {
    header("Location: login.php?error=salah");
    exit;
}

/*
|--------------------------------------------------------------------------
| SET SESSION LOGIN
|--------------------------------------------------------------------------
*/
$_SESSION['login'] = true;
$_SESSION['id_user'] = $user['id_user'];
$_SESSION['nama'] = $user['nama'];
$_SESSION['email'] = $user['email'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];

/*
|--------------------------------------------------------------------------
| MASUK DASHBOARD ADMIN
|--------------------------------------------------------------------------
*/
header("Location: ../admin/dashboard.php");
exit;
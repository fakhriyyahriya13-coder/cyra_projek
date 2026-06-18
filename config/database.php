<?php
/*
|--------------------------------------------------------------------------
| KONFIGURASI DATABASE
|--------------------------------------------------------------------------
| Sesuaikan jika nama database / user / password berubah.
*/
if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
}

if (!defined('DB_USER')) {
    define('DB_USER', getenv('DB_USER') ?: 'root');
}

if (!defined('DB_PASS')) {
    define('DB_PASS', getenv('DB_PASS') ?: '');
}

if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('DB_NAME') ?: 'cyra');
}

/*
|--------------------------------------------------------------------------
| KONEKSI DATABASE
|--------------------------------------------------------------------------
*/
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

/*
|--------------------------------------------------------------------------
| CEK KONEKSI
|--------------------------------------------------------------------------
*/
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

/*
|--------------------------------------------------------------------------
| SET CHARSET
|--------------------------------------------------------------------------
*/
if (!mysqli_set_charset($conn, "utf8mb4")) {
    die("Gagal mengatur charset database: " . mysqli_error($conn));
}

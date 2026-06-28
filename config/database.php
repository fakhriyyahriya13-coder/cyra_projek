<?php
/*
|--------------------------------------------------------------------------
| KONFIGURASI DATABASE
|--------------------------------------------------------------------------
| Sesuaikan jika nama database / user / password berubah.
*/
$envFile = dirname(__DIR__) . '/.vercel.env.local';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            putenv(trim($name) . '=' . trim($value));
        }
    }
}
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

if (!defined('DB_PORT')) {
    define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
}

if (!defined('DB_SSL')) {
    define('DB_SSL', filter_var(getenv('DB_SSL') ?: false, FILTER_VALIDATE_BOOL));
}

if (!defined('DB_SSL_CA_PATH')) {
    define('DB_SSL_CA_PATH', trim((string)(getenv('DB_SSL_CA_PATH') ?: '')));
}

if (!defined('DB_SSL_CA_BASE64')) {
    define('DB_SSL_CA_BASE64', trim((string)(getenv('DB_SSL_CA_BASE64') ?: '')));
}

/*
|--------------------------------------------------------------------------
| KONEKSI DATABASE
|--------------------------------------------------------------------------
*/
$conn = mysqli_init();

if ($conn instanceof mysqli) {
    mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 8);
    $sslCaPath = DB_SSL_CA_PATH;

    if (DB_SSL && $sslCaPath === '' && DB_SSL_CA_BASE64 !== '') {
        $decodedCa = base64_decode(DB_SSL_CA_BASE64, true);

        if ($decodedCa !== false && trim($decodedCa) !== '') {
            $caDirectory = rtrim(sys_get_temp_dir(), "\\/") . DIRECTORY_SEPARATOR . 'cyra';

            if (!is_dir($caDirectory)) {
                @mkdir($caDirectory, 0700, true);
            }

            $sslCaPath = $caDirectory . DIRECTORY_SEPARATOR . 'mysql-ca.pem';
            @file_put_contents($sslCaPath, $decodedCa, LOCK_EX);
        }
    }

    if (DB_SSL && $sslCaPath !== '' && is_file($sslCaPath)) {
        mysqli_ssl_set($conn, null, null, $sslCaPath, null, null);
    }

    $clientFlags = DB_SSL ? MYSQLI_CLIENT_SSL : 0;

    if (DB_SSL && $sslCaPath !== '' && defined('MYSQLI_CLIENT_SSL_VERIFY_SERVER_CERT')) {
        $clientFlags |= MYSQLI_CLIENT_SSL_VERIFY_SERVER_CERT;
    }

    $connected = false;
    try {
        $connected = @mysqli_real_connect(
            $conn,
            DB_HOST,
            DB_USER,
            DB_PASS,
            DB_NAME,
            DB_PORT,
            null,
            $clientFlags
        );
    } catch (mysqli_sql_exception $e) {
        $connected = false;
    }

    if (!$connected) {
        $conn = false;
    }
}

/*
|--------------------------------------------------------------------------
| CEK KONEKSI
|--------------------------------------------------------------------------
*/
if (!$conn) {
    $isVercel = getenv('VERCEL') === '1' || getenv('VERCEL_ENV') !== false;

    if (!$isVercel && !defined('CYRA_NO_DB_DIE')) {
        die("Koneksi database gagal: " . mysqli_connect_error());
    }

    return;
}

/*
|--------------------------------------------------------------------------
| SET CHARSET
|--------------------------------------------------------------------------
*/
if (!mysqli_set_charset($conn, "utf8mb4")) {
    die("Gagal mengatur charset database: " . mysqli_error($conn));
}

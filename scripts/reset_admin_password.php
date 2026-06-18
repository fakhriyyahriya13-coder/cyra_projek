<?php

require_once dirname(__DIR__) . '/config/database.php';

$username = $argv[1] ?? 'admin';
$password = $argv[2] ?? 'admin123';

if (strlen($password) < 5) {
    echo "Password minimal 5 karakter.\n";
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = mysqli_prepare($conn, 'UPDATE users SET password = ? WHERE username = ?');

if (!$stmt) {
    echo "Query gagal: " . mysqli_error($conn) . "\n";
    exit(1);
}

mysqli_stmt_bind_param($stmt, 'ss', $hash, $username);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_affected_rows($stmt) < 1) {
    echo "User '$username' tidak ditemukan atau password sama.\n";
    exit(1);
}

echo "Password user '$username' berhasil direset.\n";
echo "Password baru: $password\n";

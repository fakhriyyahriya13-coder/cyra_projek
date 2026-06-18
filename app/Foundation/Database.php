<?php

require_once __DIR__ . '/Paths.php';

function cyraRequireDatabase(): mysqli
{
    require cyraBasePath('config/database.php');

    if (!isset($conn) || !$conn instanceof mysqli) {
        throw new RuntimeException('Koneksi database gagal.');
    }

    return $conn;
}

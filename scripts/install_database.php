<?php

require_once dirname(__DIR__) . '/config/database.php';

function runSqlFile(mysqli $conn, string $path): void
{
    if (!file_exists($path)) {
        throw new RuntimeException("File SQL tidak ditemukan: $path");
    }

    $sql = file_get_contents($path);

    if ($sql === false || trim($sql) === '') {
        return;
    }

    if (!mysqli_multi_query($conn, $sql)) {
        throw new RuntimeException(mysqli_error($conn));
    }

    do {
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    } while (mysqli_more_results($conn) && mysqli_next_result($conn));
}

try {
    runSqlFile($conn, dirname(__DIR__) . '/database/schema.sql');
    runSqlFile($conn, dirname(__DIR__) . '/database/seeders/default_data.sql');

    echo "Database CYRA berhasil disiapkan.\n";
    echo "Database aktif: " . DB_NAME . "\n";
    echo "Login admin default:\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";

    $tables = mysqli_query($conn, 'SHOW TABLES');

    if ($tables) {
        echo "\nTabel tersedia:\n";

        while ($row = mysqli_fetch_row($tables)) {
            echo "- " . $row[0] . "\n";
        }
    }
} catch (Throwable $e) {
    echo "Gagal menyiapkan database: " . $e->getMessage() . "\n";
    exit(1);
}

<?php

$sourceHost = getenv('SOURCE_DB_HOST') ?: 'localhost';
$sourcePort = (int)(getenv('SOURCE_DB_PORT') ?: 3306);
$sourceUser = getenv('SOURCE_DB_USER') ?: 'root';
$sourcePass = getenv('SOURCE_DB_PASS') ?: '';
$sourceName = getenv('SOURCE_DB_NAME') ?: 'cyra';
$append = in_array('--append', $argv, true);

$source = @mysqli_connect(
    $sourceHost,
    $sourceUser,
    $sourcePass,
    $sourceName,
    $sourcePort
);

if (!$source instanceof mysqli) {
    fwrite(STDERR, 'Koneksi database sumber gagal: ' . mysqli_connect_error() . PHP_EOL);
    exit(1);
}

mysqli_set_charset($source, 'utf8mb4');

require dirname(__DIR__) . '/config/database.php';

if (!isset($conn) || !$conn instanceof mysqli) {
    fwrite(STDERR, 'Koneksi database target Aiven gagal.' . PHP_EOL);
    exit(1);
}

$target = $conn;
$tables = [
    'users',
    'dosen',
    'mata_kuliah',
    'jadwal_kuliah',
    'jadwal_uts',
    'jadwal_uas',
    'prosedur_frs',
    'prosedur_kp',
    'prosedur_ta',
    'faq',
];

function runSqlFileForMigration(mysqli $connection, string $path): void
{
    $sql = file_get_contents($path);

    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException("File SQL tidak dapat dibaca: $path");
    }

    if (!mysqli_multi_query($connection, $sql)) {
        throw new RuntimeException(mysqli_error($connection));
    }

    do {
        if ($result = mysqli_store_result($connection)) {
            mysqli_free_result($result);
        }
    } while (mysqli_more_results($connection) && mysqli_next_result($connection));
}

function migrationTableColumns(mysqli $connection, string $table): array
{
    $result = mysqli_query($connection, "SHOW COLUMNS FROM `$table`");

    if (!$result) {
        return [];
    }

    $columns = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $columns[] = $row['Field'];
    }

    return $columns;
}

function copyMigrationTable(
    mysqli $source,
    mysqli $target,
    string $table,
    bool $append
): int {
    $sourceColumns = migrationTableColumns($source, $table);
    $targetColumns = migrationTableColumns($target, $table);
    $columns = array_values(array_intersect($sourceColumns, $targetColumns));

    if ($columns === []) {
        return 0;
    }

    if (!$append && !mysqli_query($target, "TRUNCATE TABLE `$table`")) {
        throw new RuntimeException("Gagal mengosongkan tabel $table: " . mysqli_error($target));
    }

    $columnSql = implode(', ', array_map(
        static fn (string $column): string => "`$column`",
        $columns
    ));
    $query = mysqli_query($source, "SELECT $columnSql FROM `$table`");

    if (!$query) {
        throw new RuntimeException("Gagal membaca tabel $table: " . mysqli_error($source));
    }

    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $insert = mysqli_prepare(
        $target,
        "INSERT INTO `$table` ($columnSql) VALUES ($placeholders)"
    );

    if (!$insert) {
        throw new RuntimeException("Gagal menyiapkan insert $table: " . mysqli_error($target));
    }

    $types = str_repeat('s', count($columns));
    $count = 0;

    while ($row = mysqli_fetch_assoc($query)) {
        $values = [];

        foreach ($columns as $column) {
            $values[] = $row[$column];
        }

        mysqli_stmt_bind_param($insert, $types, ...$values);

        if (!mysqli_stmt_execute($insert)) {
            throw new RuntimeException("Gagal menyalin tabel $table: " . mysqli_stmt_error($insert));
        }

        $count++;
    }

    mysqli_stmt_close($insert);

    return $count;
}

try {
    runSqlFileForMigration($target, dirname(__DIR__) . '/database/schema.sql');
    mysqli_begin_transaction($target);
    mysqli_query($target, 'SET FOREIGN_KEY_CHECKS=0');

    $counts = [];

    foreach ($tables as $table) {
        $counts[$table] = copyMigrationTable($source, $target, $table, $append);
    }

    mysqli_query($target, 'SET FOREIGN_KEY_CHECKS=1');
    mysqli_commit($target);

    echo 'Migrasi MySQL lokal ke Aiven berhasil.' . PHP_EOL;
    echo 'Mode: ' . ($append ? 'append' : 'fresh target') . PHP_EOL;

    foreach ($counts as $table => $count) {
        echo "- $table: $count baris" . PHP_EOL;
    }
} catch (Throwable $e) {
    mysqli_rollback($target);
    fwrite(STDERR, 'Migrasi gagal: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

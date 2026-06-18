<?php
/*
 * Database utility helpers used by academic responses.
 * Extracted from app/cyra/webhook.php to keep the webhook endpoint small.
 */

/* =========================================================
   DATABASE HELPER
========================================================= */
function tableExists($conn, $table)
{
    $table = mysqli_real_escape_string($conn, $table);
    $q = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return $q && mysqli_num_rows($q) > 0;
}

function columnExists($conn, $table, $column)
{
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);

    $q = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $q && mysqli_num_rows($q) > 0;
}

function orderByExisting($conn, $table, $columns)
{
    $order = [];

    foreach ($columns as $column) {
        if (columnExists($conn, $table, $column)) {
            $order[] = "`$column` ASC";
        }
    }

    if (empty($order)) {
        return "";
    }

    return " ORDER BY " . implode(", ", $order);
}

function bindParams($stmt, $types, $values)
{
    if ($types === '' || empty($values)) {
        return true;
    }

    $refs = [];
    $refs[] = $types;

    foreach ($values as $key => $value) {
        $refs[] = &$values[$key];
    }

    return call_user_func_array([$stmt, 'bind_param'], $refs);
}

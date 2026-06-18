<?php
/*
 * Native bootstrap for CYRA.
 * This keeps the project framework-like without changing legacy URLs.
 */

require_once dirname(__DIR__) . '/app/Foundation/Paths.php';

return [
    'base_path' => cyraBasePath(),
    'app_path' => cyraAppPath(),
    'storage_path' => cyraBasePath('storage'),
];

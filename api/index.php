<?php

$projectRoot = dirname(__DIR__);
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$requestPath = '/' . ltrim($requestPath, '/');

if (getenv('VERCEL') === '1' || getenv('VERCEL_ENV') !== false) {
    $sessionPath = rtrim(sys_get_temp_dir(), "\\/") . DIRECTORY_SEPARATOR . 'cyra_sessions';

    if (!is_dir($sessionPath)) {
        @mkdir($sessionPath, 0700, true);
    }

    session_save_path($sessionPath);
}

$routes = [
    '/' => $projectRoot . '/index.php',
    '/index.php' => $projectRoot . '/index.php',
    '/cek_php.php' => $projectRoot . '/cek_php.php',
    '/cek_koneksi.php' => $projectRoot . '/cek_koneksi.php',
    '/app/cyra/chatbot.php' => $projectRoot . '/app/cyra/chatbot.php',
    '/app/cyra/webhook.php' => $projectRoot . '/app/cyra/webhook.php',
    '/app/auth/login.php' => $projectRoot . '/app/auth/login.php',
    '/app/auth/proses_login.php' => $projectRoot . '/app/auth/proses_login.php',
    '/app/auth/logout.php' => $projectRoot . '/app/auth/logout.php',
];

foreach (glob($projectRoot . '/app/admin/*.php') ?: [] as $adminFile) {
    $routes['/app/admin/' . basename($adminFile)] = $adminFile;
}

$target = $routes[$requestPath] ?? null;

if ($target === null || !is_file($target)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Halaman tidak ditemukan.';
    exit;
}

chdir(dirname($target));
require $target;

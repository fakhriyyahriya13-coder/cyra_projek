<?php
/*
 * Route map for the future native front controller.
 * Current legacy URLs still run directly.
 */

return [
    'GET /' => 'index.php',
    'GET /admin/login' => 'app/auth/login.php',
    'GET /admin/dashboard' => 'app/admin/dashboard.php',
    'GET /admin/log_percakapan' => 'app/admin/log_percakapan.php',
];

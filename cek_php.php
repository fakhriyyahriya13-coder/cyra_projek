<?php
header('Content-Type: text/plain; charset=utf-8');

echo "PHP version used by web server: " . PHP_VERSION . PHP_EOL;
echo "PHP executable/SAPI: " . PHP_SAPI . PHP_EOL;
echo "Required PHP version for CYRA Composer dependencies: >= 8.2.0" . PHP_EOL . PHP_EOL;

if (PHP_VERSION_ID < 80200) {
    echo "Status: TIDAK COCOK" . PHP_EOL;
    echo "Solusi: gunakan XAMPP/PHP 8.2 atau lebih baru." . PHP_EOL;
    exit;
}

echo "Status: COCOK" . PHP_EOL;

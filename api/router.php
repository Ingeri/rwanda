<?php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if (str_starts_with($path, '/api')) {
    require __DIR__ . '/index.php';
    exit;
}

if ($path === '/doc' || $path === '/doc/') {
    require __DIR__ . '/../doc/index.html';
    exit;
}

$file = __DIR__ . '/..' . $path;
if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/../index.html';

<?php
// Router for PHP built-in server: php -S localhost:8000 router.php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 1. API Routing
if ($uri === '/api/config') {
    require __DIR__ . '/api/config.php';
    exit;
}

if ($uri === '/api/auth/status') {
    $_GET['action'] = 'status';
    require __DIR__ . '/api/auth.php';
    exit;
}

if ($uri === '/api/auth/login') {
    $_GET['action'] = 'login';
    require __DIR__ . '/api/auth.php';
    exit;
}

if (strpos($uri, '/api/files/') === 0) {
    $action = substr($uri, strlen('/api/files/'));
    $_GET['action'] = $action;
    require __DIR__ . '/api/files.php';
    exit;
}

if (strpos($uri, '/api/stream') === 0) {
    require __DIR__ . '/api/stream.php';
    exit;
}

if ($uri === '/api/execute') {
    require __DIR__ . '/api/execute.php';
    exit;
}

if ($uri === '/api/autocomplete') {
    require __DIR__ . '/api/autocomplete.php';
    exit;
}

if ($uri === '/api/banner') {
    require __DIR__ . '/api/banner.php';
    exit;
}

// 2. Static files
$publicDir = __DIR__ . '/public';
if ($uri === '/' || $uri === '') {
    $filePath = $publicDir . '/index.html';
    if (file_exists($filePath)) {
        header('Content-Type: text/html');
        readfile($filePath);
        exit;
    }
}

$requested = $publicDir . $uri;
if (file_exists($requested) && !is_dir($requested)) {
    $ext = pathinfo($requested, PATHINFO_EXTENSION);
    $mimes = [
        'html' => 'text/html',
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'json' => 'application/json',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'svg'  => 'image/svg+xml',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf'
    ];
    if (isset($mimes[$ext])) {
        header('Content-Type: ' . $mimes[$ext]);
    }
    readfile($requested);
    exit;
}

// Fallback to public/index.html
if (file_exists($publicDir . '/index.html')) {
    header('Content-Type: text/html');
    readfile($publicDir . '/index.html');
    exit;
}

http_response_code(404);
echo "404 Not Found";

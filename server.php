<?php
// CLI Entrypoint to start the Pure-PHP WebSocket Terminal Server
// Run with: php server.php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/websocket.php';

$host = SERVER_HOST;
$port = SERVER_PORT;

// Allow command line argument override: php server.php 8080
if (!empty($argv[1])) {
    $port = (int)$argv[1];
}

$server = new WebSocketProcessServer($host, $port);
$server->start();

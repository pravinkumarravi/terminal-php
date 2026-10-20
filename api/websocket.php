<?php
// Pure PHP RFC-6455 WebSocket Server & Process Manager
// Zero external dependencies. Works on PHP 7.4 - 8.5+

class WebSocketProcessServer {
    private $host;
    private $port;
    private $masterSocket;
    private $clients = [];       // socket_id => resource
    private $handshakes = [];    // socket_id => bool
    private $sessions = [];      // session_id => session data
    private $clientSession = []; // socket_id => session_id
    private $running = true;

    public function __construct($host = '0.0.0.0', $port = 8080) {
        $this->host = $host;
        $this->port = $port;
    }

    public function start() {
        $context = stream_context_create([
            'socket' => ['so_reuseport' => 1, 'so_reuseaddr' => 1]
        ]);

        $this->masterSocket = @stream_socket_server(
            "tcp://{$this->host}:{$this->port}",
            $errno,
            $errstr,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            $context
        );

        if (!$this->masterSocket) {
            die("❌ Failed to bind server on {$this->host}:{$this->port} - $errstr ($errno)\n");
        }

        stream_set_blocking($this->masterSocket, false);

        echo "========================================================\n";
        echo "🚀 PHP Native WebSocket Terminal Server running!\n";
        echo "📡 Listening on: ws://{$this->host}:{$this->port}\n";
        echo "🖥️  PHP Version: " . PHP_VERSION . " (" . PHP_OS . ")\n";
        echo "========================================================\n";

        while ($this->running) {
            $read = array_merge([$this->masterSocket], array_values($this->clients));
            // Add process stdout/stderr pipes to stream_select
            $procPipes = [];
            foreach ($this->sessions as $sid => $sess) {
                if (!empty($sess['pipes'][1]) && is_resource($sess['pipes'][1])) {
                    $procPipes[] = $sess['pipes'][1];
                }
                if (!empty($sess['pipes'][2]) && is_resource($sess['pipes'][2])) {
                    $procPipes[] = $sess['pipes'][2];
                }
            }
            $read = array_merge($read, $procPipes);
            $write = null;
            $except = null;

            $numChanged = @stream_select($read, $write, $except, 0, 20000); // 20ms timeout

            if ($numChanged === false) {
                usleep(10000);
                continue;
            }

            // 1. Check for incoming client connections
            if (in_array($this->masterSocket, $read)) {
                $newSocket = @stream_socket_accept($this->masterSocket, 0);
                if ($newSocket) {
                    stream_set_blocking($newSocket, false);
                    $id = (int)$newSocket;
                    $this->clients[$id] = $newSocket;
                    $this->handshakes[$id] = false;
                }
                $key = array_search($this->masterSocket, $read);
                unset($read[$key]);
            }

            // 2. Read from clients (WebSocket packets or handshakes)
            foreach ($this->clients as $id => $socket) {
                if (in_array($socket, $read)) {
                    if (!$this->handshakes[$id]) {
                        $this->performHandshake($id, $socket);
                    } else {
                        $this->readClientMessage($id, $socket);
                    }
                    $key = array_search($socket, $read);
                    if ($key !== false) unset($read[$key]);
                }
            }

            // 3. Read from process output pipes
            foreach ($this->sessions as $sid => &$sess) {
                $hasOutput = false;
                // STDOUT
                if (!empty($sess['pipes'][1]) && in_array($sess['pipes'][1], $read)) {
                    $out = @fread($sess['pipes'][1], 8192);
                    if ($out !== false && $out !== '') {
                        $this->appendSessionBuffer($sid, $out);
                        $this->broadcastToSession($sid, ['type' => 'data', 'sessionId' => $sid, 'data' => $out]);
                        $hasOutput = true;
                    }
                }
                // STDERR
                if (!empty($sess['pipes'][2]) && in_array($sess['pipes'][2], $read)) {
                    $err = @fread($sess['pipes'][2], 8192);
                    if ($err !== false && $err !== '') {
                        $this->appendSessionBuffer($sid, $err);
                        $this->broadcastToSession($sid, ['type' => 'data', 'sessionId' => $sid, 'data' => $err]);
                        $hasOutput = true;
                    }
                }

                // Check process termination
                if (!empty($sess['process']) && is_resource($sess['process'])) {
                    $status = proc_get_status($sess['process']);
                    if (!$status['running'] && !$sess['isExited']) {
                        $sess['isExited'] = true;
                        $sess['exitCode'] = $status['exitcode'];
                        $this->broadcastToSession($sid, [
                            'type' => 'exit',
                            'sessionId' => $sid,
                            'exitCode' => $status['exitcode']
                        ]);
                    }
                }
            }
            unset($sess);
        }
    }

    private function performHandshake($clientId, $socket) {
        $headers = '';
        while (($line = fgets($socket)) !== false) {
            $headers .= $line;
            if ($line === "\r\n") break;
        }

        if (preg_match('/Sec-WebSocket-Key:\s*(.*)\r\n/i', $headers, $match)) {
            $key = trim($match[1]);
            $acceptKey = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

            $upgrade = "HTTP/1.1 101 Switching Protocols\r\n" .
                       "Upgrade: websocket\r\n" .
                       "Connection: Upgrade\r\n" .
                       "Sec-WebSocket-Accept: $acceptKey\r\n\r\n";

            @fwrite($socket, $upgrade);
            $this->handshakes[$clientId] = true;
        } else {
            $this->handleHttpRequest($clientId, $socket, $headers);
        }
    }

    private function handleHttpRequest($clientId, $socket, $headers) {
        if (!preg_match('#^(GET|POST|DELETE)\s+([^\s]+)\s+HTTP/#', $headers, $m)) {
            $this->disconnectClient($clientId);
            return;
        }

        $method = $m[1];
        $uri = parse_url($m[2], PHP_URL_PATH);
        $publicDir = dirname(__DIR__) . '/public';

        // 1. API: /api/config
        if ($uri === '/api/config') {
            ob_start();
            require dirname(__DIR__) . '/api/config.php';
            $body = ob_get_clean();
            $this->sendHttpResponse($socket, 200, 'application/json; charset=utf-8', $body);
            $this->disconnectClient($clientId);
            return;
        }

        // 2. API: /api/auth/*
        if (strpos($uri, '/api/auth/') === 0) {
            $_GET['action'] = substr($uri, strlen('/api/auth/'));
            ob_start();
            require dirname(__DIR__) . '/api/auth.php';
            $body = ob_get_clean();
            $this->sendHttpResponse($socket, 200, 'application/json; charset=utf-8', $body);
            $this->disconnectClient($clientId);
            return;
        }

        // 3. API: /api/files/*
        if (strpos($uri, '/api/files/') === 0) {
            $_GET['action'] = substr($uri, strlen('/api/files/'));
            ob_start();
            require dirname(__DIR__) . '/api/files.php';
            $body = ob_get_clean();
            $this->sendHttpResponse($socket, 200, 'application/json; charset=utf-8', $body);
            $this->disconnectClient($clientId);
            return;
        }

        // 4. API: /api/execute
        if ($uri === '/api/execute') {
            ob_start();
            require dirname(__DIR__) . '/api/execute.php';
            $body = ob_get_clean();
            $this->sendHttpResponse($socket, 200, 'text/plain; charset=utf-8', $body);
            $this->disconnectClient($clientId);
            return;
        }

        // 5. API: /api/autocomplete
        if ($uri === '/api/autocomplete') {
            ob_start();
            require dirname(__DIR__) . '/api/autocomplete.php';
            $body = ob_get_clean();
            $this->sendHttpResponse($socket, 200, 'application/json; charset=utf-8', $body);
            $this->disconnectClient($clientId);
            return;
        }

        // 6. API: /api/banner
        if ($uri === '/api/banner') {
            ob_start();
            require dirname(__DIR__) . '/api/banner.php';
            $body = ob_get_clean();
            $this->sendHttpResponse($socket, 200, 'application/json; charset=utf-8', $body);
            $this->disconnectClient($clientId);
            return;
        }

        // 7. Static Files
        if ($uri === '/' || $uri === '') {
            $file = $publicDir . '/index.html';
        } else {
            $file = $publicDir . $uri;
        }

        if (file_exists($file) && !is_dir($file)) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $mimes = [
                'html' => 'text/html; charset=utf-8',
                'css'  => 'text/css; charset=utf-8',
                'js'   => 'application/javascript; charset=utf-8',
                'json' => 'application/json; charset=utf-8',
                'png'  => 'image/png',
                'svg'  => 'image/svg+xml',
                'woff' => 'font/woff',
                'woff2'=> 'font/woff2',
                'ttf'  => 'font/ttf'
            ];
            $mime = $mimes[$ext] ?? 'application/octet-stream';
            $content = file_get_contents($file);
            $this->sendHttpResponse($socket, 200, $mime, $content);
        } else {
            $this->sendHttpResponse($socket, 404, 'text/plain', '404 Not Found');
        }

        $this->disconnectClient($clientId);
    }

    private function sendHttpResponse($socket, $code, $mime, $body) {
        $statusText = $code === 200 ? 'OK' : ($code === 404 ? 'Not Found' : 'Error');
        $resp = "HTTP/1.1 {$code} {$statusText}\r\n" .
                "Content-Type: {$mime}\r\n" .
                "Content-Length: " . strlen($body) . "\r\n" .
                "Connection: close\r\n\r\n" .
                $body;
        @fwrite($socket, $resp);
    }

    private function readClientMessage($clientId, $socket) {
        $header = @fread($socket, 2);
        if ($header === false || strlen($header) < 2) {
            $this->disconnectClient($clientId);
            return;
        }

        $byte1 = ord($header[0]);
        $byte2 = ord($header[1]);
        $opcode = $byte1 & 0x0F;
        $isMasked = ($byte2 & 0x80) !== 0;
        $payloadLen = $byte2 & 0x7F;

        // Disconnect or Ping
        if ($opcode === 0x8) { // Close
            $this->disconnectClient($clientId);
            return;
        }
        if ($opcode === 0x9) { // Ping -> Pong
            @fwrite($socket, chr(0x8A) . chr(0x00));
            return;
        }

        if ($payloadLen === 126) {
            $ext = @fread($socket, 2);
            $payloadLen = unpack('n', $ext)[1];
        } elseif ($payloadLen === 127) {
            $ext = @fread($socket, 8);
            $payloadLen = unpack('J', $ext)[1];
        }

        $mask = $isMasked ? @fread($socket, 4) : '';
        $payload = '';
        while (strlen($payload) < $payloadLen) {
            $chunk = @fread($socket, min(8192, $payloadLen - strlen($payload)));
            if ($chunk === false || $chunk === '') break;
            $payload .= $chunk;
        }

        if ($isMasked && strlen($mask) === 4) {
            for ($i = 0; $i < strlen($payload); $i++) {
                $payload[$i] = chr(ord($payload[$i]) ^ ord($mask[$i % 4]));
            }
        }

        $this->handleJsonMessage($clientId, $payload);
    }

    private function handleJsonMessage($clientId, $json) {
        $msg = json_decode($json, true);
        if (!$msg || !isset($msg['type'])) return;

        switch ($msg['type']) {
            case 'create':
                $sid = $msg['sessionId'] ?? ('php-term-' . bin2hex(random_bytes(4)));
                $shellId = $msg['shellId'] ?? 'default';
                $session = $this->createSession($sid, $shellId, $msg['cwd'] ?? null, $msg['title'] ?? 'PHP Terminal');
                $this->clientSession[$clientId] = $sid;
                $this->sendWsMessage($clientId, [
                    'type' => 'created',
                    'session' => [
                        'id' => $sid,
                        'shell' => $session['shellName'],
                        'title' => $session['title'],
                        'pid' => $session['pid']
                    ]
                ]);
                break;

            case 'attach':
                $sid = $msg['sessionId'];
                if (isset($this->sessions[$sid])) {
                    $this->clientSession[$clientId] = $sid;
                    $sess = $this->sessions[$sid];
                    $this->sendWsMessage($clientId, [
                        'type' => 'attached',
                        'session' => [
                            'id' => $sid,
                            'shell' => $sess['shellName'],
                            'title' => $sess['title'],
                            'pid' => $sess['pid']
                        ]
                    ]);
                    // Replay buffer
                    if (!empty($sess['buffer'])) {
                        $this->sendWsMessage($clientId, [
                            'type' => 'replay',
                            'sessionId' => $sid,
                            'data' => implode('', $sess['buffer'])
                        ]);
                    }
                } else {
                    // Create if not exists
                    $session = $this->createSession($sid, $msg['shellId'] ?? 'default', $msg['cwd'] ?? null, $msg['title'] ?? 'PHP Terminal');
                    $this->clientSession[$clientId] = $sid;
                    $this->sendWsMessage($clientId, [
                        'type' => 'created',
                        'session' => [
                            'id' => $sid,
                            'shell' => $session['shellName'],
                            'title' => $session['title'],
                            'pid' => $session['pid']
                        ]
                    ]);
                }
                break;

            case 'data':
                $sid = $msg['sessionId'] ?? ($this->clientSession[$clientId] ?? null);
                if ($sid && isset($this->sessions[$sid]) && !empty($this->sessions[$sid]['pipes'][0])) {
                    @fwrite($this->sessions[$sid]['pipes'][0], $msg['data']);
                }
                break;

            case 'close':
                $sid = $msg['sessionId'] ?? ($this->clientSession[$clientId] ?? null);
                if ($sid) $this->killSession($sid);
                break;

            case 'ping':
                $this->sendWsMessage($clientId, ['type' => 'pong', 'timestamp' => time() * 1000]);
                break;
        }
    }

    private function createSession($sid, $shellId, $cwd, $title) {
        $shells = getAvailableShells();
        $selected = null;
        foreach ($shells as $s) {
            if ($s['id'] === $shellId) { $selected = $s; break; }
        }
        if (!$selected) $selected = $shells[0];

        $cmd = $selected['path'];
        if (!empty($selected['args'])) {
            $cmd .= ' ' . implode(' ', $selected['args']);
        }

        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w']  // stderr
        ];

        $env = array_merge(getenv(), [
            'TERM' => 'xterm-256color',
            'COLORTERM' => 'truecolor'
        ]);

        $workingDir = $cwd && is_dir($cwd) ? $cwd : DEFAULT_CWD;
        $proc = proc_open($cmd, $descriptors, $pipes, $workingDir, $env);

        if (is_resource($proc)) {
            stream_set_blocking($pipes[1], false);
            stream_set_blocking($pipes[2], false);
            $status = proc_get_status($proc);
            $pid = $status['pid'] ?? 0;
        } else {
            $pipes = [];
            $pid = 0;
        }

        $session = [
            'id' => $sid,
            'title' => $title,
            'shellName' => $selected['name'],
            'cmd' => $cmd,
            'process' => $proc,
            'pipes' => $pipes,
            'pid' => $pid,
            'buffer' => [],
            'isExited' => false,
            'exitCode' => null
        ];

        $this->sessions[$sid] = $session;
        return $session;
    }

    private function appendSessionBuffer($sid, $data) {
        if (!isset($this->sessions[$sid])) return;
        $this->sessions[$sid]['buffer'][] = $data;
        if (count($this->sessions[$sid]['buffer']) > 500) {
            array_shift($this->sessions[$sid]['buffer']);
        }
    }

    private function broadcastToSession($sid, $data) {
        $payload = json_encode($data);
        foreach ($this->clientSession as $cid => $s) {
            if ($s === $sid && isset($this->clients[$cid])) {
                $this->sendWsRaw($this->clients[$cid], $payload);
            }
        }
    }

    private function sendWsMessage($clientId, $data) {
        if (!isset($this->clients[$clientId])) return;
        $this->sendWsRaw($this->clients[$clientId], json_encode($data));
    }

    private function sendWsRaw($socket, $text) {
        $b1 = 0x81; // FIN + text opcode
        $len = strlen($text);

        if ($len <= 125) {
            $header = pack('CC', $b1, $len);
        } elseif ($len <= 65535) {
            $header = pack('CCn', $b1, 126, $len);
        } else {
            $header = pack('CCJ', $b1, 127, $len);
        }

        @fwrite($socket, $header . $text);
    }

    private function killSession($sid) {
        if (!isset($this->sessions[$sid])) return;
        $sess = $this->sessions[$sid];
        if (is_resource($sess['process'])) {
            foreach ($sess['pipes'] as $p) {
                if (is_resource($p)) @fclose($p);
            }
            @proc_terminate($sess['process']);
            @proc_close($sess['process']);
        }
        unset($this->sessions[$sid]);
    }

    private function disconnectClient($clientId) {
        if (isset($this->clients[$clientId])) {
            @fclose($this->clients[$clientId]);
            unset($this->clients[$clientId]);
            unset($this->handshakes[$clientId]);
            unset($this->clientSession[$clientId]);
        }
    }
}

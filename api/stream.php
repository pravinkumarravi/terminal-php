<?php
// HTTP SSE (Server-Sent Events) Streaming fallback for environments without WebSocket support
require_once __DIR__ . '/../config.php';
requireAuth();

$action = $_GET['action'] ?? ($_POST['action'] ?? 'stream');
$sessionId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['sessionId'] ?? ($_POST['sessionId'] ?? 'default'));

// Session files
$sessionMetaFile = SESSION_DIR . DIRECTORY_SEPARATOR . 'meta_' . $sessionId . '.json';
$sessionLogFile = SESSION_DIR . DIRECTORY_SEPARATOR . 'out_' . $sessionId . '.log';
$sessionInFile = SESSION_DIR . DIRECTORY_SEPARATOR . 'in_' . $sessionId . '.pipe';

// 1. Write stdin to process
if ($action === 'write') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $data = $input['data'] ?? '';

    if ($data !== '') {
        file_put_contents($sessionInFile, $data, FILE_APPEND | LOCK_EX);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Empty data']);
    }
    exit;
}

// 2. Kill session
if ($action === 'kill') {
    header('Content-Type: application/json');
    if (file_exists($sessionMetaFile)) {
        $meta = json_decode(file_get_contents($sessionMetaFile), true);
        if (!empty($meta['pid'])) {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                @exec("taskkill /F /PID {$meta['pid']} 2>&1");
            } else {
                @posix_kill($meta['pid'], SIGKILL);
            }
        }
        @unlink($sessionMetaFile);
    }
    echo json_encode(['success' => true]);
    exit;
}

// 3. SSE Output Stream
if ($action === 'stream') {
    // Disable output buffering
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no'); // For Nginx

    // Check if session process is already active or needs spawning
    if (!file_exists($sessionMetaFile)) {
        $shells = getAvailableShells();
        $shellId = $_GET['shellId'] ?? 'default';
        $selected = null;
        foreach ($shells as $s) {
            if ($s['id'] === $shellId) { $selected = $s; break; }
        }
        if (!$selected) $selected = $shells[0];

        $cmd = $selected['path'];
        if (!empty($selected['args'])) {
            $cmd .= ' ' . implode(' ', $selected['args']);
        }

        // Initialize log file
        file_put_contents($sessionLogFile, '');
        touch($sessionInFile);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', $sessionLogFile, 'a'],
            2 => ['file', $sessionLogFile, 'a']
        ];

        $cwd = !empty($_GET['cwd']) && is_dir($_GET['cwd']) ? $_GET['cwd'] : DEFAULT_CWD;
        $proc = proc_open($cmd, $descriptors, $pipes, $cwd, array_merge(getenv(), [
            'TERM' => 'xterm-256color',
            'COLORTERM' => 'truecolor'
        ]));

        if (is_resource($proc)) {
            $status = proc_get_status($proc);
            $meta = [
                'pid' => $status['pid'],
                'shell' => $selected['name'],
                'startTime' => time()
            ];
            file_put_contents($sessionMetaFile, json_encode($meta));
        }
    }

    echo "event: ready\n";
    echo "data: " . json_encode(['sessionId' => $sessionId, 'status' => 'connected']) . "\n\n";
    flush();

    $offset = (int)($_GET['offset'] ?? 0);
    clearstatcache(true, $sessionLogFile);
    $fileSize = file_exists($sessionLogFile) ? filesize($sessionLogFile) : 0;

    if ($fileSize > $offset) {
        $fp = fopen($sessionLogFile, 'rb');
        if ($fp) {
            fseek($fp, $offset);
            $chunk = fread($fp, $fileSize - $offset);
            fclose($fp);
            $offset = $fileSize;

            if ($chunk !== false && $chunk !== '') {
                echo "event: data\n";
                echo "data: " . json_encode(['data' => $chunk, 'offset' => $offset]) . "\n\n";
                flush();
            }
        }
    }
    exit;
}

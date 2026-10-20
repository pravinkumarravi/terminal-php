<?php
// Command execution endpoint for HTTP mode (php -S, Apache, shared hosting)
require_once __DIR__ . '/../config.php';
requireAuth();

// Disable output buffering for live streaming
while (ob_get_level()) ob_end_clean();

$input = json_decode(file_get_contents('php://input'), true);
$cmd = trim($input['cmd'] ?? ($_POST['cmd'] ?? ''));
$cwd = trim($input['cwd'] ?? ($_POST['cwd'] ?? DEFAULT_CWD));

if (!is_dir($cwd)) {
    $cwd = DEFAULT_CWD;
}

// 1. Handle clear
if ($cmd === 'clear' || $cmd === 'cls') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'output' => "\033[2J\033[H",
        'cwd' => $cwd,
        'clear' => true
    ]);
    exit;
}

// 2. Handle cd
if (preg_match('/^\s*cd\s*(.*)$/i', $cmd, $matches)) {
    header('Content-Type: application/json; charset=utf-8');
    $target = trim($matches[1]);
    if ($target === '' || $target === '~') {
        $newCwd = getenv('USERPROFILE') ?: (getenv('HOME') ?: $cwd);
    } elseif ($target === '..') {
        $newCwd = dirname($cwd);
    } else {
        // Strip quotes if any
        $target = trim($target, '"\'');
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && preg_match('/^[a-zA-Z]:[\\\\\/]/', $target)) {
            $newCwd = $target;
        } elseif ($target[0] === '/' || $target[0] === '\\') {
            $newCwd = $target;
        } else {
            $newCwd = $cwd . DIRECTORY_SEPARATOR . $target;
        }
    }

    $real = realpath($newCwd);
    if ($real && is_dir($real)) {
        echo json_encode(['output' => '', 'cwd' => $real, 'error' => false]);
    } else {
        echo json_encode(['output' => "cd: cannot find directory: $target\r\n", 'cwd' => $cwd, 'error' => true]);
    }
    exit;
}

// 3. Execute command with streaming chunked output
if (!headers_sent()) {
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
}

$isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

// Build command
if ($isWin) {
    // Run via cmd /c with active directory
    $fullCmd = 'cd /d ' . escapeshellarg($cwd) . ' && ' . $cmd . ' 2>&1';
} else {
    $fullCmd = 'cd ' . escapeshellarg($cwd) . ' && ' . $cmd . ' 2>&1';
}

$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w']
];

$proc = proc_open($fullCmd, $descriptors, $pipes, $cwd, getenv());

if (is_resource($proc)) {
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    while (true) {
        $status = proc_get_status($proc);
        $read = [$pipes[1], $pipes[2]];
        $write = null;
        $except = null;

        $ready = @stream_select($read, $write, $except, 0, 50000); // 50ms
        if ($ready) {
            foreach ($read as $r) {
                $chunk = fread($r, 4096);
                if ($chunk !== false && $chunk !== '') {
                    // Normalize newlines for xterm.js (\n -> \r\n)
                    $chunk = preg_replace('/(?<!\r)\n/', "\r\n", $chunk);
                    echo $chunk;
                    flush();
                }
            }
        }

        if (!$status['running']) {
            // Read remaining
            $rem1 = stream_get_contents($pipes[1]);
            $rem2 = stream_get_contents($pipes[2]);
            if ($rem1) echo preg_replace('/(?<!\r)\n/', "\r\n", $rem1);
            if ($rem2) echo preg_replace('/(?<!\r)\n/', "\r\n", $rem2);
            flush();
            break;
        }

        usleep(20000);
    }

    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($proc);
} else {
    echo "Failed to execute command: $cmd\r\n";
}

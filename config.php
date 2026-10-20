<?php
// Configuration for PHP Web Terminal

// Authentication: Set a password or leave empty for open local access
define('TERMINAL_PASSWORD', getenv('TERMINAL_PASSWORD') ?: '');

// Server Host & Port (for standalone CLI WebSocket daemon)
define('SERVER_HOST', getenv('TERMINAL_HOST') ?: '0.0.0.0');
define('SERVER_PORT', (int)(getenv('TERMINAL_PORT') ?: 8000));

// Default Starting Working Directory
define('DEFAULT_CWD', getenv('TERMINAL_CWD') ?: __DIR__);

// Session temporary directory for persistent processes and buffers
define('SESSION_DIR', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php_term_sessions');
if (!is_dir(SESSION_DIR)) {
    @mkdir(SESSION_DIR, 0777, true);
}

// Detect Available Operating System Shells
function getAvailableShells() {
    $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $shells = [];

    if ($isWin) {
        $ps = 'C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe';
        if (file_exists($ps)) {
            $shells[] = [
                'id' => 'powershell',
                'name' => 'PowerShell',
                'path' => $ps,
                'args' => ['-NoProfile', '-ExecutionPolicy', 'Bypass'],
                'default' => true
            ];
        }

        $cmd = 'C:\\Windows\\System32\\cmd.exe';
        if (file_exists($cmd)) {
            $shells[] = ['id' => 'cmd', 'name' => 'Command Prompt', 'path' => $cmd, 'default' => count($shells) === 0];
        }

        $gitBash = 'C:\\Program Files\\Git\\bin\\bash.exe';
        $gitBash86 = 'C:\\Program Files (x86)\\Git\\bin\\bash.exe';
        if (file_exists($gitBash)) {
            $shells[] = ['id' => 'gitbash', 'name' => 'Git Bash', 'path' => $gitBash, 'args' => ['--login', '-i']];
        } elseif (file_exists($gitBash86)) {
            $shells[] = ['id' => 'gitbash', 'name' => 'Git Bash', 'path' => $gitBash86, 'args' => ['--login', '-i']];
        }

        $wsl = 'C:\\Windows\\System32\\wsl.exe';
        if (file_exists($wsl)) {
            $shells[] = ['id' => 'wsl', 'name' => 'WSL (Linux)', 'path' => $wsl];
        }
    } else {
        $userShell = getenv('SHELL');
        if ($userShell && file_exists($userShell)) {
            $shells[] = ['id' => basename($userShell), 'name' => strtoupper(basename($userShell)), 'path' => $userShell, 'default' => true];
        }

        $bash = '/bin/bash';
        if (file_exists($bash) && !in_array($bash, array_column($shells, 'path'))) {
            $shells[] = ['id' => 'bash', 'name' => 'Bash', 'path' => $bash, 'default' => count($shells) === 0];
        }

        $sh = '/bin/sh';
        if (file_exists($sh) && !in_array($sh, array_column($shells, 'path'))) {
            $shells[] = ['id' => 'sh', 'name' => 'Sh', 'path' => $sh];
        }
    }

    if (empty($shells)) {
        $shells[] = ['id' => 'default', 'name' => 'Default Shell', 'path' => $isWin ? 'powershell.exe' : '/bin/sh', 'default' => true];
    }

    return $shells;
}

// Authentication Helpers
function isAuthRequired() {
    return defined('TERMINAL_PASSWORD') && TERMINAL_PASSWORD !== '';
}

function verifyPassword($password) {
    if (!isAuthRequired()) return true;
    return $password === TERMINAL_PASSWORD;
}

function generateToken() {
    $token = bin2hex(random_bytes(24));
    file_put_contents(SESSION_DIR . DIRECTORY_SEPARATOR . 'token_' . $token, time());
    return $token;
}

function validateToken($token) {
    if (!isAuthRequired()) return true;
    if (empty($token)) return false;
    $file = SESSION_DIR . DIRECTORY_SEPARATOR . 'token_' . preg_replace('/[^a-f0-9]/', '', $token);
    if (file_exists($file)) {
        // Token valid for 7 days
        if (time() - filemtime($file) < 7 * 86400) {
            return true;
        }
        @unlink($file);
    }
    return false;
}

function getRequestToken() {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (strpos($auth, 'Bearer ') === 0) {
        return substr($auth, 7);
    }
    if (!empty($_GET['token'])) {
        return $_GET['token'];
    }
    if (!empty($_COOKIE['term_token'])) {
        return $_COOKIE['term_token'];
    }
    return null;
}

function requireAuth() {
    if (!isAuthRequired()) return;
    $token = getRequestToken();
    if (!validateToken($token)) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized', 'authRequired' => true]);
        exit;
    }
}

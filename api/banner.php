<?php
// Dynamic OS Shell Banner Provider
// Reads authentic OS/shell banners directly from Windows, Linux, and macOS.
require_once __DIR__ . '/../config.php';

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

$shellId = $_GET['shellId'] ?? 'default';
$shells = getAvailableShells();
$selected = null;
foreach ($shells as $s) {
    if ($s['id'] === $shellId) {
        $selected = $s;
        break;
    }
}
if (!$selected && !empty($shells)) {
    $selected = $shells[0];
}

$banner = '';
$family = PHP_OS_FAMILY; // 'Windows', 'Linux', 'Darwin', 'BSD', etc.

if ($family === 'Windows') {
    $shellName = $selected['name'] ?? '';
    $shellPath = $selected['path'] ?? '';

    if ($shellId === 'cmd' || stripos($shellName, 'Command') !== false || stripos($shellPath, 'cmd.exe') !== false) {
        // Read actual Windows version directly from cmd.exe ver
        $ver = trim(@shell_exec('cmd.exe /c ver 2>&1') ?: '');
        if (!$ver) {
            $ver = 'Microsoft Windows';
        }
        $banner = $ver . "\r\n(c) Microsoft Corporation. All rights reserved.\r\n";
    } elseif ($shellId === 'gitbash' || stripos($shellName, 'Git') !== false || stripos($shellPath, 'bash.exe') !== false) {
        // Read actual Git Bash (MSYS2/MinGW) version and uname
        $gitBash = (file_exists($shellPath)) ? $shellPath : 'C:\\Program Files\\Git\\bin\\bash.exe';
        if (file_exists($gitBash)) {
            $rawVer = trim(@shell_exec('"' . $gitBash . '" -c "bash --version | head -n 1" 2>&1') ?: '');
            $rawUname = trim(@shell_exec('"' . $gitBash . '" -c "uname -srm" 2>&1') ?: '');
            $parts = array_filter([$rawVer, $rawUname]);
            $banner = implode("\r\n", $parts) . "\r\n";
        }
        if (!$banner) {
            $banner = "GNU bash (MSYS2/Git for Windows)\r\n";
        }
    } else {
        // PowerShell / PowerShell Core
        $ps = (file_exists($shellPath)) ? $shellPath : 'powershell.exe';
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = @proc_open('"' . $ps . '"', $descriptors, $pipes, null, getenv());
        if (is_resource($proc)) {
            fwrite($pipes[0], "exit\r\n");
            fflush($pipes[0]);
            fclose($pipes[0]);
            $raw = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($proc);

            $lines = explode("\n", trim($raw));
            // Remove the interactive prompt line (e.g. "PS C:\...> exit")
            while (!empty($lines)) {
                $last = trim(end($lines));
                if (preg_match('/^(PS\s+.*>|exit$)/i', $last)) {
                    array_pop($lines);
                } else {
                    break;
                }
            }
            $banner = implode("\r\n", array_map('trim', $lines)) . "\r\n";
        }

        if (!$banner) {
            // Fallback to querying host version table
            $psVer = trim(@shell_exec('powershell -NoProfile -Command "$PSVersionTable.PSVersion.ToString()" 2>&1') ?: '');
            $banner = "Windows PowerShell" . ($psVer ? " [Version $psVer]" : "") . "\r\nCopyright (C) Microsoft Corporation. All rights reserved.\r\n";
        }
    }
} elseif ($family === 'Darwin') {
    // macOS
    $swVers = trim(@shell_exec('sw_vers 2>&1') ?: '');
    $uname = trim(@shell_exec('uname -srm 2>&1') ?: '');
    $shellVer = '';
    $sh = $selected['path'] ?? (getenv('SHELL') ?: '/bin/zsh');
    if (file_exists($sh)) {
        $shellVer = trim(@shell_exec(escapeshellcmd($sh) . ' --version 2>&1 | head -n 1') ?: '');
    }
    $parts = array_filter([$swVers, $uname, $shellVer]);
    $banner = implode("\r\n", $parts) . "\r\n";
} else {
    // Linux / Unix / BSD
    $osName = '';
    if (file_exists('/etc/os-release')) {
        $osData = @parse_ini_file('/etc/os-release');
        if (!empty($osData['PRETTY_NAME'])) {
            $osName = $osData['PRETTY_NAME'];
        }
    }
    if (!$osName && file_exists('/etc/issue')) {
        $osName = trim(explode("\n", @file_get_contents('/etc/issue'))[0] ?? '');
    }

    $uname = trim(@shell_exec('uname -srmo 2>&1') ?: (@shell_exec('uname -srm 2>&1') ?: ''));
    $shellVer = '';
    $sh = $selected['path'] ?? (getenv('SHELL') ?: '/bin/bash');
    if ($sh) {
        $shellVer = trim(@shell_exec(escapeshellcmd($sh) . ' --version 2>&1 | head -n 1') ?: '');
    }

    $parts = array_filter([$osName, $uname, $shellVer]);
    $banner = implode("\r\n", $parts) . "\r\n";
}

echo json_encode(['banner' => $banner]);

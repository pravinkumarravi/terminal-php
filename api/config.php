<?php
require_once __DIR__ . '/../config.php';

if (!headers_sent()) {
    @header('Content-Type: application/json; charset=utf-8');
}

$shells = getAvailableShells();
$defaultShell = null;
foreach ($shells as $s) {
    if (!empty($s['default'])) {
        $defaultShell = $s;
        break;
    }
}
if (!$defaultShell) {
    $defaultShell = $shells[0] ?? ['id' => 'default', 'name' => 'Default'];
}

// System stats
$freeMem = 'N/A';
$totalMem = 'N/A';

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Windows memory estimation
    $wmic = @shell_exec('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value 2>nul');
    if ($wmic) {
        if (preg_match('/FreePhysicalMemory=(\d+)/', $wmic, $mFree) && preg_match('/TotalVisibleMemorySize=(\d+)/', $wmic, $mTotal)) {
            $freeMem = round($mFree[1] / 1024) . ' MB';
            $totalMem = round($mTotal[1] / 1024) . ' MB';
        }
    }
} else {
    // Linux /proc/meminfo
    if (file_exists('/proc/meminfo')) {
        $meminfo = file_get_contents('/proc/meminfo');
        if (preg_match('/MemTotal:\s+(\d+)/', $meminfo, $mTotal) && preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $mAvail)) {
            $freeMem = round($mAvail[1] / 1024) . ' MB';
            $totalMem = round($mTotal[1] / 1024) . ' MB';
        }
    }
}

echo json_encode([
    'authRequired' => isAuthRequired(),
    'availableShells' => $shells,
    'defaultShell' => $defaultShell,
    'defaultCwd' => DEFAULT_CWD,
    'backend' => 'php',
    'system' => [
        'hostname' => gethostname(),
        'platform' => PHP_OS,
        'php_version' => PHP_VERSION,
        'uptime' => 'N/A',
        'memory' => [
            'total' => $totalMem,
            'free' => $freeMem
        ]
    ]
]);

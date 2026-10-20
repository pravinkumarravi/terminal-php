<?php
require_once __DIR__ . '/../config.php';

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

$input = json_decode(file_get_contents('php://input'), true);
$prefix = trim($input['prefix'] ?? ($_GET['prefix'] ?? ($_POST['prefix'] ?? '')));
$cwd = trim($input['cwd'] ?? ($_GET['cwd'] ?? ($_POST['cwd'] ?? DEFAULT_CWD)));

if (!is_dir($cwd)) {
    $cwd = DEFAULT_CWD;
}

$suggestions = [];

// Extract directory path and filename from prefix
$lastSlash = strrpos($prefix, '/');
$lastBackslash = strrpos($prefix, '\\');
$lastSeparator = max($lastSlash !== false ? $lastSlash : -1, $lastBackslash !== false ? $lastBackslash : -1);

if ($lastSeparator !== -1) {
    $dirPart = substr($prefix, 0, $lastSeparator + 1);
    $filename = substr($prefix, $lastSeparator + 1);
    $searchDir = $cwd . DIRECTORY_SEPARATOR . $dirPart;
} else {
    $dirPart = '';
    $filename = $prefix;
    $searchDir = $cwd;
}

$realSearchDir = realpath($searchDir);
if ($realSearchDir && is_dir($realSearchDir)) {
    $files = @scandir($realSearchDir);
    if ($files) {
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            if ($filename === '' || stripos($file, $filename) === 0) {
                $fullPath = $realSearchDir . DIRECTORY_SEPARATOR . $file;
                $suggestion = $dirPart . $file;

                if (is_dir($fullPath)) {
                    $suggestion .= '/';
                }

                $suggestions[] = $suggestion;
            }
        }
    }
}

sort($suggestions, SORT_STRING | SORT_FLAG_CASE);

echo json_encode($suggestions);

<?php
require_once __DIR__ . '/../config.php';
requireAuth();

$action = $_GET['action'] ?? '';

function formatBytes($bytes) {
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 1) . ' ' . $units[$i];
}

function resolveSafePath($p) {
    if (empty($p)) return DEFAULT_CWD;
    $real = realpath($p);
    return $real !== false ? $real : $p;
}

// 1. List directory
if ($action === 'list') {
    header('Content-Type: application/json; charset=utf-8');
    $dir = resolveSafePath($_GET['path'] ?? '');

    if (!is_dir($dir)) {
        http_response_code(404);
        echo json_encode(['error' => 'Directory not found']);
        exit;
    }

    $parent = dirname($dir);
    $items = [];

    if ($parent !== $dir) {
        $items[] = [
            'name' => '..',
            'path' => $parent,
            'isDirectory' => true,
            'size' => '',
            'mtime' => '',
            'isParent' => true
        ];
    }

    $entries = @scandir($dir) ?: [];
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $full = $dir . DIRECTORY_SEPARATOR . $entry;
        $isDir = is_dir($full);
        $size = $isDir ? 0 : (@filesize($full) ?: 0);
        $mtime = @filemtime($full);

        $items[] = [
            'name' => $entry,
            'path' => $full,
            'isDirectory' => $isDir,
            'size' => $isDir ? '' : formatBytes($size),
            'bytes' => $size,
            'mtime' => $mtime ? date('Y-m-d H:i', $mtime) : '',
            'ext' => strtolower(pathinfo($entry, PATHINFO_EXTENSION) ? '.' . pathinfo($entry, PATHINFO_EXTENSION) : '')
        ];
    }

    usort($items, function ($a, $b) {
        if (!empty($a['isParent'])) return -1;
        if (!empty($b['isParent'])) return 1;
        if ($a['isDirectory'] && !$b['isDirectory']) return -1;
        if (!$a['isDirectory'] && $b['isDirectory']) return 1;
        return strcasecmp($a['name'], $b['name']);
    });

    echo json_encode([
        'currentPath' => $dir,
        'parentPath' => $parent !== $dir ? $parent : null,
        'items' => $items
    ]);
    exit;
}

// 2. Download file
if ($action === 'download') {
    $file = resolveSafePath($_GET['path'] ?? '');
    if (!file_exists($file) || is_dir($file)) {
        http_response_code(404);
        echo 'File not found';
        exit;
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}

// 3. Read file text
if ($action === 'read') {
    header('Content-Type: application/json; charset=utf-8');
    $file = resolveSafePath($_GET['path'] ?? '');
    if (!file_exists($file) || is_dir($file)) {
        http_response_code(404);
        echo json_encode(['error' => 'File not found']);
        exit;
    }

    if (filesize($file) > 2 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'File too large (> 2MB)']);
        exit;
    }

    $content = file_get_contents($file);
    echo json_encode([
        'path' => $file,
        'name' => basename($file),
        'content' => $content,
        'size' => filesize($file)
    ]);
    exit;
}

// 4. Save file text
if ($action === 'save') {
    header('Content-Type: application/json; charset=utf-8');
    $input = json_decode(file_get_contents('php://input'), true);
    $file = $input['filePath'] ?? '';
    $content = $input['content'] ?? '';

    if (empty($file)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing filePath']);
        exit;
    }

    if (@file_put_contents($file, $content) !== false) {
        echo json_encode(['success' => true, 'path' => $file]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to write file. Check directory permissions.']);
    }
    exit;
}

// 5. Upload files
if ($action === 'upload') {
    header('Content-Type: application/json; charset=utf-8');
    $dest = resolveSafePath($_POST['destination'] ?? DEFAULT_CWD);
    if (!is_dir($dest)) {
        @mkdir($dest, 0777, true);
    }

    $uploaded = [];
    if (!empty($_FILES['files']['name'])) {
        $names = (array)$_FILES['files']['name'];
        $tmpNames = (array)$_FILES['files']['tmp_name'];

        for ($i = 0; $i < count($names); $i++) {
            if (!empty($tmpNames[$i]) && is_uploaded_file($tmpNames[$i])) {
                $targetFile = $dest . DIRECTORY_SEPARATOR . basename($names[$i]);
                if (move_uploaded_file($tmpNames[$i], $targetFile)) {
                    $uploaded[] = $names[$i];
                }
            }
        }
    }

    echo json_encode(['success' => true, 'filesUploaded' => $uploaded]);
    exit;
}

// 6. Delete file or directory
if ($action === 'delete') {
    header('Content-Type: application/json; charset=utf-8');
    $input = json_decode(file_get_contents('php://input'), true);
    $target = resolveSafePath($input['path'] ?? '');

    if (!file_exists($target)) {
        http_response_code(404);
        echo json_encode(['error' => 'Target not found']);
        exit;
    }

    function removeRecursive($path) {
        if (is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $f) {
                if ($f === '.' || $f === '..') continue;
                removeRecursive($path . DIRECTORY_SEPARATOR . $f);
            }
            return rmdir($path);
        }
        return unlink($path);
    }

    if (removeRecursive($target)) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);

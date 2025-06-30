<?php
// =====================================================================
// SECURITY CONFIGURATION
// Set TERMINAL_PASSWORD to a strong password to protect this terminal
// Leave empty ('') to allow unrestricted access (NOT RECOMMENDED)
// =====================================================================
define('TERMINAL_PASSWORD', ''); // Change this to a strong password!

// Password protection logic
session_start();

if (TERMINAL_PASSWORD !== '') {
    // Check if user is already authenticated
    if (!isset($_SESSION['terminal_authenticated']) || $_SESSION['terminal_authenticated'] !== true) {
        // Check if password is being submitted
        if (isset($_POST['terminal_password'])) {
            if ($_POST['terminal_password'] === TERMINAL_PASSWORD) {
                $_SESSION['terminal_authenticated'] = true;
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $password_error = 'Invalid password. Please try again.';
            }
        }
        
        // Show password prompt
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Terminal Access - Authentication Required</title>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    background: linear-gradient(135deg, #0c0c0c 0%, #1a1a1a 100%);
                    font-family: 'Courier New', Courier, monospace;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    color: #ffffff;
                }
                
                .auth-container {
                    background: rgba(0, 0, 0, 0.8);
                    border: 2px solid #00ff00;
                    border-radius: 10px;
                    padding: 40px;
                    box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);
                    text-align: center;
                    max-width: 400px;
                    width: 90%;
                }
                
                .terminal-icon {
                    font-size: 48px;
                    color: #00ff00;
                    margin-bottom: 20px;
                }
                
                h1 {
                    color: #00ff00;
                    margin-bottom: 10px;
                    font-size: 24px;
                }
                
                .subtitle {
                    color: #888;
                    margin-bottom: 30px;
                    font-size: 14px;
                }
                
                .form-group {
                    margin-bottom: 20px;
                    text-align: left;
                }
                
                label {
                    display: block;
                    margin-bottom: 8px;
                    color: #00ff00;
                    font-weight: bold;
                }
                
                input[type="password"] {
                    width: 100%;
                    padding: 12px;
                    background: #1a1a1a;
                    border: 1px solid #333;
                    border-radius: 5px;
                    color: #ffffff;
                    font-family: 'Courier New', Courier, monospace;
                    font-size: 16px;
                    box-sizing: border-box;
                }
                
                input[type="password"]:focus {
                    outline: none;
                    border-color: #00ff00;
                    box-shadow: 0 0 5px rgba(0, 255, 0, 0.3);
                }
                
                .submit-btn {
                    width: 100%;
                    padding: 12px;
                    background: #00ff00;
                    color: #000000;
                    border: none;
                    border-radius: 5px;
                    font-family: 'Courier New', Courier, monospace;
                    font-size: 16px;
                    font-weight: bold;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }
                
                .submit-btn:hover {
                    background: #00cc00;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 8px rgba(0, 255, 0, 0.3);
                }
                
                .error-message {
                    background: rgba(255, 0, 0, 0.1);
                    border: 1px solid #ff0000;
                    color: #ff6666;
                    padding: 10px;
                    border-radius: 5px;
                    margin-bottom: 20px;
                    font-size: 14px;
                }
                
                .warning {
                    background: rgba(255, 165, 0, 0.1);
                    border: 1px solid #ffa500;
                    color: #ffcc66;
                    padding: 15px;
                    border-radius: 5px;
                    margin-top: 20px;
                    font-size: 12px;
                    text-align: left;
                }
                
                .warning-icon {
                    color: #ffa500;
                    margin-right: 8px;
                }
            </style>
        </head>
        <body>
            <div class="auth-container">
                <div class="terminal-icon">ЁЯЦея╕П</div>
                <h1>Terminal Access</h1>
                <div class="subtitle">Authentication Required</div>
                
                <?php if (isset($password_error)): ?>
                    <div class="error-message">
                        тЭМ <?php echo htmlspecialchars($password_error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="terminal_password">Password:</label>
                        <input type="password" id="terminal_password" name="terminal_password" 
                               placeholder="Enter terminal password" required autofocus>
                    </div>
                    
                    <button type="submit" class="submit-btn">Access Terminal</button>
                </form>
                
                <div class="warning">
                    <span class="warning-icon">тЪая╕П</span>
                    <strong>Security Notice:</strong> This terminal allows arbitrary command execution on the server. 
                    Only authorized users should have access to this interface.
                </div>
            </div>
            
            <script>
                // Auto-focus password field and handle Enter key
                document.getElementById('terminal_password').focus();
                
                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        document.querySelector('form').submit();
                    }
                });
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}

// =====================================================================
// SECURITY CONFIGURATION
// =====================================================================
// Set a password to protect this terminal. Leave empty to disable password protection.
// WARNING: This script allows arbitrary command execution on your server!
define('TERMINAL_PASSWORD', ''); // Set your password here, e.g., 'mySecurePassword123'

// Password protection logic
session_start();

if (TERMINAL_PASSWORD !== '') {
    // Check if password verification is being submitted
    if (isset($_POST['terminal_password'])) {
        if ($_POST['terminal_password'] === TERMINAL_PASSWORD) {
            $_SESSION['terminal_authenticated'] = true;
            // Redirect to prevent password from staying in POST data
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $password_error = 'Invalid password. Access denied.';
            $_SESSION['terminal_authenticated'] = false;
        }
    }
    
    // Check if user is authenticated
    if (!isset($_SESSION['terminal_authenticated']) || $_SESSION['terminal_authenticated'] !== true) {
        // Show password prompt
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Terminal Access - Authentication Required</title>
            <style>
                body {
                    font-family: 'Courier New', Courier, monospace;
                    background: #000;
                    color: #00ff00;
                    margin: 0;
                    padding: 0;
                    height: 100vh;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    flex-direction: column;
                }
                .auth-container {
                    background: #111;
                    border: 2px solid #00ff00;
                    border-radius: 8px;
                    padding: 30px;
                    text-align: center;
                    box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);
                    max-width: 400px;
                    width: 90%;
                }
                .logo {
                    font-size: 24px;
                    font-weight: bold;
                    margin-bottom: 20px;
                    color: #ffffff;
                }
                .warning {
                    color: #ff6b6b;
                    font-size: 14px;
                    margin-bottom: 20px;
                    padding: 10px;
                    background: rgba(255, 107, 107, 0.1);
                    border: 1px solid #ff6b6b;
                    border-radius: 4px;
                }
                .form-group {
                    margin: 20px 0;
                    text-align: left;
                }
                label {
                    display: block;
                    margin-bottom: 8px;
                    color: #00ff00;
                    font-weight: bold;
                }
                input[type="password"] {
                    width: 100%;
                    padding: 12px;
                    background: #000;
                    border: 1px solid #00ff00;
                    border-radius: 4px;
                    color: #00ff00;
                    font-family: 'Courier New', Courier, monospace;
                    font-size: 16px;
                    box-sizing: border-box;
                }
                input[type="password"]:focus {
                    outline: none;
                    border-color: #ffffff;
                    box-shadow: 0 0 5px rgba(0, 255, 0, 0.5);
                }
                button {
                    width: 100%;
                    padding: 12px;
                    background: #00ff00;
                    color: #000;
                    border: none;
                    border-radius: 4px;
                    font-family: 'Courier New', Courier, monospace;
                    font-size: 16px;
                    font-weight: bold;
                    cursor: pointer;
                    transition: background 0.3s;
                }
                button:hover {
                    background: #00cc00;
                }
                .error {
                    color: #ff6b6b;
                    margin-top: 10px;
                    padding: 8px;
                    background: rgba(255, 107, 107, 0.1);
                    border-radius: 4px;
                    font-size: 14px;
                }
                .info {
                    color: #888;
                    font-size: 12px;
                    margin-top: 15px;
                    line-height: 1.4;
                }
            </style>
        </head>
        <body>
            <div class="auth-container">
                <div class="logo">ЁЯФТ TERMINAL ACCESS</div>
                
                <div class="warning">
                    <strong>тЪая╕П RESTRICTED ACCESS</strong><br>
                    This terminal allows system command execution.<br>
                    Authentication required for security.
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="terminal_password">Enter Password:</label>
                        <input type="password" 
                               id="terminal_password" 
                               name="terminal_password" 
                               required 
                               autocomplete="off"
                               autofocus>
                    </div>
                    
                    <button type="submit">Access Terminal</button>
                    
                    <?php if (isset($password_error)): ?>
                        <div class="error"><?php echo htmlspecialchars($password_error); ?></div>
                    <?php endif; ?>
                </form>
                
                <div class="info">
                    Tip: To disable this password protection, set TERMINAL_PASSWORD to an empty string in the PHP file.
                </div>
            </div>
            
            <script>
                // Auto-focus password field
                document.getElementById('terminal_password').focus();
                
                // Enter key handler
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        document.querySelector('form').submit();
                    }
                });
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}

// Check if the request is a POST request with autocomplete action.
if (isset($_POST['action']) && $_POST['action'] === 'autocomplete') {
    $prefix = $_POST['prefix'] ?? '';
    $cwd = $_POST['cwd'] ?? getcwd();
    
    // Ensure the provided directory exists
    if (!is_dir($cwd)) {
        $cwd = getcwd();
    }
    
    $suggestions = [];
    
    // Extract directory path and filename from prefix
    $lastSlash = strrpos($prefix, '/');
    $lastBackslash = strrpos($prefix, '\\');
    $lastSeparator = max($lastSlash, $lastBackslash);
    
    if ($lastSeparator !== false) {
        $dir = substr($prefix, 0, $lastSeparator + 1);
        $filename = substr($prefix, $lastSeparator + 1);
        $searchDir = $cwd . DIRECTORY_SEPARATOR . $dir;
    } else {
        $dir = '';
        $filename = $prefix;
        $searchDir = $cwd;
    }
    
    // Get directory listing
    if (is_dir($searchDir)) {
        $files = scandir($searchDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            if (empty($filename) || stripos($file, $filename) === 0) {
                $fullPath = $searchDir . DIRECTORY_SEPARATOR . $file;
                $suggestion = $dir . $file;
                
                // Add trailing slash for directories
                if (is_dir($fullPath)) {
                    $suggestion .= '/';
                }
                
                $suggestions[] = $suggestion;
            }
        }
    }
    
    // Sort suggestions alphabetically
    sort($suggestions, SORT_STRING | SORT_FLAG_CASE);
    
    // Limit suggestions to prevent overwhelming output
    $suggestions = array_slice($suggestions, 0, 50);
    
    header('Content-Type: application/json');
    echo json_encode($suggestions);
    exit;
}

/**
 * Custom ls command handler with ANSI colors for xterm.js
 */
function handleCustomLsCommand($command, $cwd) {
    if (!is_dir($cwd)) {
        $cwd = getcwd();
    }
    
    // Parse ls command options
    $parts = explode(' ', trim($command));
    $showHidden = false;
    $longFormat = false;
    $targetDir = $cwd;
    
    // Parse command arguments
    for ($i = 1; $i < count($parts); $i++) {
        $arg = $parts[$i];
        if ($arg === '-a' || $arg === '-all') {
            $showHidden = true;
        } elseif ($arg === '-l') {
            $longFormat = true;
        } elseif ($arg === '-la' || $arg === '-al') {
            $showHidden = true;
            $longFormat = true;
        } elseif (!empty($arg) && $arg[0] !== '-') {
            $fullPath = $cwd . DIRECTORY_SEPARATOR . $arg;
            if (is_dir($fullPath)) {
                $targetDir = $fullPath;
            } elseif (is_dir($arg)) {
                $targetDir = $arg;
            }
        }
    }
    
    // Get directory listing
    $files = scandir($targetDir);
    if ($files === false) {
        return "ls: cannot access '$targetDir': No such file or directory\n";
    }
    
    // Filter files based on options
    $filteredFiles = [];
    foreach ($files as $file) {
        if (!$showHidden && $file[0] === '.') {
            continue;
        }
        $filteredFiles[] = $file;
    }
    
    // Sort files
    sort($filteredFiles, SORT_STRING | SORT_FLAG_CASE);
    
    $output = '';
    
    if ($longFormat) {
        // Long format listing
        foreach ($filteredFiles as $file) {
            $fullPath = $targetDir . DIRECTORY_SEPARATOR . $file;
            $stat = stat($fullPath);
            
            if ($stat === false) {
                continue; // Skip files that can't be stat'd
            }
            
            // Get proper Unix-style permissions
            $perms = getFilePermissions($fullPath, $stat['mode']);
            
            // Get octal permission numbers
            $octalPerms = getOctalPermissions($stat['mode']);
            
            // Number of hard links
            $links = isset($stat['nlink']) ? $stat['nlink'] : 1;
            
            // Owner and group (simplified for cross-platform compatibility)
            $owner = function_exists('posix_getpwuid') && isset($stat['uid']) ? 
                     (posix_getpwuid($stat['uid'])['name'] ?? $stat['uid']) : 
                     (isset($stat['uid']) ? $stat['uid'] : 'user');
            $group = function_exists('posix_getgrgid') && isset($stat['gid']) ? 
                     (posix_getgrgid($stat['gid'])['name'] ?? $stat['gid']) : 
                     (isset($stat['gid']) ? $stat['gid'] : 'group');
            
            // File size
            $size = $stat['size'];
            $sizeFormatted = formatFileSize($size);
            
            // Last modified time
            $mtime = date('M d H:i', $stat['mtime']);
            
            // Color-coded filename with ANSI codes
            $coloredName = getAnsiColoredFilename($file, $fullPath);
            
            // Format: permissions [octal] links owner group size date filename
            $output .= sprintf("%-10s \x1b[33m[%s]\x1b[0m %3d %-8s %-8s %8s %s %s\n", 
                              $perms, $octalPerms, $links, $owner, $group, $sizeFormatted, $mtime, $coloredName);
        }
    } else {
        // Grid format for better space utilization
        $termWidth = 80; // Default terminal width
        $maxFileLength = 0;
        
        // Find the longest filename for column width calculation
        foreach ($filteredFiles as $file) {
            $maxFileLength = max($maxFileLength, strlen($file));
        }
        
        $columnWidth = min($maxFileLength + 2, 20); // Max 20 chars per column
        $columns = max(1, intval($termWidth / $columnWidth));
        
        $coloredFiles = [];
        foreach ($filteredFiles as $file) {
            $fullPath = $targetDir . DIRECTORY_SEPARATOR . $file;
            $coloredFiles[] = getAnsiColoredFilename($file, $fullPath);
        }
        
        // Output in columns
        for ($i = 0; $i < count($coloredFiles); $i += $columns) {
            $row = array_slice($coloredFiles, $i, $columns);
            $output .= implode('  ', array_map(function($item) use ($columnWidth) {
                // Strip ANSI codes for length calculation, then pad
                $plainItem = preg_replace('/\033\[[0-9;]*m/', '', $item);
                $padding = max(0, $columnWidth - strlen($plainItem));
                return $item . str_repeat(' ', $padding);
            }, $row));
            $output .= "\n";
        }
    }
    
    return $output;
}

/**
 * Get ANSI color-coded filename for xterm.js
 */
function getAnsiColoredFilename($filename, $fullPath) {
    if (is_dir($fullPath)) {
        // Directories: bold blue
        return "\033[1;34m{$filename}\033[0m";
    } elseif (is_executable($fullPath) || preg_match('/\.(exe|bat|com|cmd|sh|py|pl|rb|php|js)$/i', $filename)) {
        // Executables: bold green
        return "\033[1;32m{$filename}\033[0m";
    } elseif (preg_match('/\.(zip|tar|gz|bz2|xz|7z|rar|deb|rpm)$/i', $filename)) {
        // Archives: bold red
        return "\033[1;31m{$filename}\033[0m";
    } elseif (preg_match('/\.(jpg|jpeg|png|gif|bmp|svg|webp)$/i', $filename)) {
        // Images: bold magenta
        return "\033[1;35m{$filename}\033[0m";
    } elseif (preg_match('/\.(mp3|wav|flac|ogg|mp4|avi|mkv|mov)$/i', $filename)) {
        // Media files: bold cyan
        return "\033[1;36m{$filename}\033[0m";
    } elseif (preg_match('/\.(txt|md|readme|log)$/i', $filename)) {
        // Text files: normal white
        return "\033[0;37m{$filename}\033[0m";
    } else {
        // Regular files: default color
        return $filename;
    }
}

/**
 * Format file size in human-readable format
 */
function formatFileSize($size) {
    if ($size < 1024) {
        return $size . 'B';
    } elseif ($size < 1024 * 1024) {
        return round($size / 1024, 1) . 'K';
    } elseif ($size < 1024 * 1024 * 1024) {
        return round($size / (1024 * 1024), 1) . 'M';
    } else {
        return round($size / (1024 * 1024 * 1024), 1) . 'G';
    }
}

/**
 * Get octal permission numbers from file mode
 * @param int $mode The file mode from stat()
 * @return string Octal permission string (e.g., "644", "755")
 */
function getOctalPermissions($mode) {
    // Extract permission bits (last 9 bits for owner, group, other)
    $perms = $mode & 0777;
    return sprintf('%o', $perms);
}

/**
 * Convert file mode to Unix-style permission string
 * @param string $filePath The path to the file
 * @param int $mode The file mode from stat()
 * @return string Unix-style permission string (e.g., "-rwxr-xr-x")
 */
function getFilePermissions($filePath, $mode) {
    $perms = '';
    
    // File type
    if (($mode & 0xC000) === 0xC000) {
        $perms = 's'; // Socket
    } elseif (($mode & 0xA000) === 0xA000) {
        $perms = 'l'; // Symbolic link
    } elseif (($mode & 0x8000) === 0x8000) {
        $perms = '-'; // Regular file
    } elseif (($mode & 0x6000) === 0x6000) {
        $perms = 'b'; // Block special
    } elseif (($mode & 0x4000) === 0x4000) {
        $perms = 'd'; // Directory
    } elseif (($mode & 0x2000) === 0x2000) {
        $perms = 'c'; // Character special
    } elseif (($mode & 0x1000) === 0x1000) {
        $perms = 'p'; // FIFO pipe
    } else {
        $perms = '?'; // Unknown
    }
    
    // Owner permissions
    $perms .= ($mode & 0x0100) ? 'r' : '-';
    $perms .= ($mode & 0x0080) ? 'w' : '-';
    $perms .= ($mode & 0x0040) ? 
              (($mode & 0x0800) ? 's' : 'x') : 
              (($mode & 0x0800) ? 'S' : '-');
    
    // Group permissions
    $perms .= ($mode & 0x0020) ? 'r' : '-';
    $perms .= ($mode & 0x0010) ? 'w' : '-';
    $perms .= ($mode & 0x0008) ? 
              (($mode & 0x0400) ? 's' : 'x') : 
              (($mode & 0x0400) ? 'S' : '-');
    
    // Other permissions
    $perms .= ($mode & 0x0004) ? 'r' : '-';
    $perms .= ($mode & 0x0002) ? 'w' : '-';
    $perms .= ($mode & 0x0001) ? 
              (($mode & 0x0200) ? 't' : 'x') : 
              (($mode & 0x0200) ? 'T' : '-');
    
    return $perms;
}

// Handle logout request
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    unset($_SESSION['terminal_authenticated']);
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Check if the request is for streaming command execution
if (isset($_POST['cmd']) && isset($_POST['stream']) && $_POST['stream'] === 'true') {
    // Set headers for Server-Sent Events
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Cache-Control');
    
    // Disable output buffering for real-time streaming
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Get the command and the current working directory from the POST data.
    $command = $_POST['cmd'];
    $cwd = $_POST['cwd'] ?? getcwd();
    
    // Ensure the provided directory exists
    if (!is_dir($cwd)) {
        $cwd = getcwd();
    }
    
    // Function to send SSE data
    function sendSSE($event, $data) {
        echo "event: $event\n";
        echo "data: " . json_encode($data) . "\n\n";
        flush();
    }
    
    try {
        // Handle custom ls command with colors
        if (preg_match('/^\s*ls(\s|$)/', $command)) {
            $output = handleCustomLsCommand($command, $cwd);
            sendSSE('output', ['output' => $output, 'cwd' => $cwd, 'error' => false]);
            sendSSE('complete', ['success' => true]);
            exit;
        }
        
        // Handle clear command
        if (trim($command) === 'clear') {
            sendSSE('output', ['output' => "\033[2J\033[H", 'cwd' => $cwd, 'error' => false]);
            sendSSE('complete', ['success' => true]);
            exit;
        }
        
        // Handle directory changes
        if (preg_match('/^\s*cd\s*(.*)$/', $command, $matches)) {
            $targetDir = trim($matches[1]);
            
            if (empty($targetDir) || $targetDir === '~') {
                $newCwd = $_SERVER['HOME'] ?? $cwd;
            } elseif ($targetDir === '..') {
                $newCwd = dirname($cwd);
            } elseif ($targetDir[0] === '/') {
                $newCwd = $targetDir;
            } else {
                $newCwd = $cwd . DIRECTORY_SEPARATOR . $targetDir;
            }
            
            $newCwd = realpath($newCwd);
            
            if ($newCwd && is_dir($newCwd)) {
                sendSSE('output', ['output' => '', 'cwd' => $newCwd, 'error' => false]);
            } else {
                sendSSE('output', ['output' => "cd: no such file or directory: $targetDir\n", 'cwd' => $cwd, 'error' => true]);
            }
            sendSSE('complete', ['success' => true]);
            exit;
        }
        
        // Execute command with real-time output streaming
        $full_command = 'cd ' . escapeshellarg($cwd) . ' && ' . $command . ' 2>&1';
        
        // Use popen for real-time streaming
        $process = popen($full_command, 'r');
        
        if ($process) {
            // Stream output in real-time
            while (!feof($process)) {
                $chunk = fread($process, 1024); // Read in 1KB chunks
                if ($chunk !== false && $chunk !== '') {
                    sendSSE('chunk', ['data' => $chunk]);
                }
                usleep(10000); // Small delay to prevent overwhelming the client
            }
            
            $exit_code = pclose($process);
            sendSSE('complete', ['success' => $exit_code === 0, 'exit_code' => $exit_code]);
        } else {
            sendSSE('error', ['message' => 'Failed to execute command']);
        }
        
    } catch (Exception $e) {
        sendSSE('error', ['message' => $e->getMessage()]);
    }
    
    exit;
}

// Check if the request is a POST request with a 'cmd' parameter (non-streaming).
if (isset($_POST['cmd'])) {
    // Set headers for JSON response
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    
    // Get the command and the current working directory from the POST data.
    $command = $_POST['cmd'];
    $cwd = $_POST['cwd'] ?? getcwd();
    
    // Ensure the provided directory exists
    if (!is_dir($cwd)) {
        $cwd = getcwd();
    }
    
    $response = [
        'output' => '',
        'cwd' => $cwd,
        'error' => false
    ];
    
    try {
        // Handle custom ls command with colors
        if (preg_match('/^\s*ls(\s|$)/', $command)) {
            $response['output'] = handleCustomLsCommand($command, $cwd);
            echo json_encode($response);
            exit;
        }
        
        // Handle clear command
        if (trim($command) === 'clear') {
            $response['output'] = "\033[2J\033[H"; // ANSI clear screen and move cursor to home
            echo json_encode($response);
            exit;
        }
        
        // For directory changes, we need to handle them specially
        if (preg_match('/^\s*cd\s*(.*)$/', $command, $matches)) {
            $targetDir = trim($matches[1]);
            
            if (empty($targetDir) || $targetDir === '~') {
                $newCwd = $_SERVER['HOME'] ?? $cwd;
            } elseif ($targetDir === '..') {
                $newCwd = dirname($cwd);
            } elseif ($targetDir[0] === '/') {
                // Absolute path
                $newCwd = $targetDir;
            } else {
                // Relative path
                $newCwd = $cwd . DIRECTORY_SEPARATOR . $targetDir;
            }
            
            // Normalize the path
            $newCwd = realpath($newCwd);
            
            if ($newCwd && is_dir($newCwd)) {
                $response['cwd'] = $newCwd;
                $response['output'] = '';
            } else {
                $response['output'] = "cd: no such file or directory: $targetDir\n";
                $response['error'] = true;
            }
            
            echo json_encode($response);
            exit;
        }
        
        // Execute other commands
        $full_command = 'cd ' . escapeshellarg($cwd) . ' && ' . $command . ' 2>&1';
        
        ob_start();
        $output = shell_exec($full_command);
        ob_end_clean();
        
        $response['output'] = $output ?: '';
        
    } catch (Exception $e) {
        $response['output'] = "Error: " . $e->getMessage() . "\n";
        $response['error'] = true;
    }
    
    echo json_encode($response);
    exit;
}

// =====================================================================
// HTML Frontend with Xterm.js
// This part is rendered on the initial page load.
// =====================================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xterm.js Web Terminal</title>
    
    <!-- Xterm.js CSS and JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm@5.3.0/css/xterm.css" />
    <script src="https://cdn.jsdelivr.net/npm/xterm@5.3.0/lib/xterm.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xterm-addon-fit@0.8.0/lib/xterm-addon-fit.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xterm-addon-web-links@0.9.0/lib/xterm-addon-web-links.js"></script>
    
    <style>
        :root {
            /* Global spinner font size - used for CSS elements if needed */
            --spinner-font-size: 22px;
        }
        
        body {
            margin: 0;
            padding: 0;
            background: #000;
            font-family: 'Courier New', Courier, monospace;
            overflow: hidden;
        }
        
        #terminal-container {
            width: 100vw;
            height: 100vh;
            background: #000;
        }
        
        .terminal {
            width: 100%;
            height: 100%;
        }
        
        /* Spinner styling */
        .spinner-char {
            font-size: var(--spinner-font-size);
            font-weight: bold;
        }
        
        /* Custom scrollbar for terminal */
        .xterm-viewport::-webkit-scrollbar {
            width: 8px;
        }
        
        .xterm-viewport::-webkit-scrollbar-track {
            background: #1a1a1a;
        }
        
        .xterm-viewport::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 4px;
        }
        
        .xterm-viewport::-webkit-scrollbar-thumb:hover {
            background: #777;
        }
        
        /* Loading overlay */
        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            color: #00ff00;
            font-size: 18px;
            z-index: 1000;
        }
        
        .loading-spinner {
            border: 3px solid #333;
            border-top: 3px solid #00ff00;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin-right: 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php if (TERMINAL_PASSWORD !== ''): ?>
    <div id="logout-container" style="position: fixed; top: 10px; right: 10px; z-index: 2000;">
        <a href="?logout=1" id="logout-btn" style="
            background: rgba(255, 0, 0, 0.8);
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            border: 1px solid #ff4444;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        " onmouseover="this.style.background='rgba(255, 0, 0, 0.9)'" 
           onmouseout="this.style.background='rgba(255, 0, 0, 0.8)'"
           onclick="return confirm('Are you sure you want to logout?')">
            ЁЯЪк Logout
        </a>
    </div>
    <?php endif; ?>
    
    <div id="loading-overlay">
        <div class="loading-spinner"></div>
        <div>Initializing Terminal...</div>
    </div>
    
    <div id="terminal-container"></div>
    
    <script>
        /**
         * XtermTerminal Class
         * 
         * Features:
         * - Real-time command output streaming using Server-Sent Events
         * - Fallback to regular fetch for compatibility
         * - Global spinner configuration with size control
         * 
         * Global Configuration:
         * - Change spinnerSize property to control spinner appearance
         * - Sizes: 14 (normal), 16 (large), 18+ (extra large)
         * - Use setSpinnerSize(size) method to change dynamically
         */
        class XtermTerminal {
            constructor() {
                this.term = null;
                this.fitAddon = null;
                this.webLinksAddon = null;
                this.cwd = '<?php echo addslashes(getcwd()); ?>';
                this.currentCommand = '';
                this.commandHistory = [];
                this.historyIndex = -1;
                this.isLoading = false;
                this.completionSuggestions = [];
                this.completionIndex = -1;
                this.originalCommand = '';
                this.isCompleting = false;
                // Global spinner configuration
                this.spinnerSize = 18; // Font size for spinner in pixels
                this.spinnerCleared = false; // Track if spinner has been cleared
                
                this.init();
            }
            
            async init() {
                // Update CSS variable for spinner size
                document.documentElement.style.setProperty('--spinner-font-size', this.spinnerSize + 'px');
                
                // Create terminal instance
                this.term = new Terminal({
                    cursorBlink: true,
                    cursorStyle: 'block',
                    fontFamily: '"Fira Code", "Cascadia Code", "SF Mono", Consolas, "Liberation Mono", Menlo, Monaco, "Courier New", monospace',
                    fontSize: 14,
                    lineHeight: 1.2,
                    theme: {
                        background: '#000000',
                        foreground: '#ffffff',
                        cursor: '#ffffff',
                        cursorAccent: '#000000',
                        selection: '#444444',
                        black: '#000000',
                        red: '#cd3131',
                        green: '#0dbc79',
                        yellow: '#e5e510',
                        blue: '#2472c8',
                        magenta: '#bc3fbc',
                        cyan: '#11a8cd',
                        white: '#e5e5e5',
                        brightBlack: '#666666',
                        brightRed: '#f14c4c',
                        brightGreen: '#23d18b',
                        brightYellow: '#f5f543',
                        brightBlue: '#3b8eea',
                        brightMagenta: '#d670d6',
                        brightCyan: '#29b8db',
                        brightWhite: '#ffffff'
                    },
                    allowTransparency: false,
                    convertEol: true,
                    scrollback: 1000,
                    tabStopWidth: 4
                });
                
                // Create addons
                this.fitAddon = new FitAddon.FitAddon();
                this.webLinksAddon = new WebLinksAddon.WebLinksAddon();
                
                // Load addons
                this.term.loadAddon(this.fitAddon);
                this.term.loadAddon(this.webLinksAddon);
                
                // Open terminal
                this.term.open(document.getElementById('terminal-container'));
                
                // Fit terminal to container
                this.fitAddon.fit();
                
                // Handle window resize
                window.addEventListener('resize', () => {
                    this.fitAddon.fit();
                });
                
                // Handle terminal input
                this.term.onData(this.handleInput.bind(this));
                
                // Hide loading overlay
                document.getElementById('loading-overlay').style.display = 'none';
                
                // Show welcome message and prompt
                this.showWelcome();
                this.showPrompt();
                
                // Focus terminal
                this.term.focus();
            }
            
            showWelcome() {
                const welcome = [
                    '\x1b[1;33m _____                   _             _   ____  _   _ ____  \x1b[0m',
                    '\x1b[1;33m|_   _|__ _ __ _ __ ___ (_)_ __   __ _| | |  _ \\| | | |  _ \\ \x1b[0m',
                    '\x1b[1;33m  | |/ _ \\ \'__| \'_ ` _ \\| | \'_ \\ / _` | | | |_) | |_| | |_) |\x1b[0m',
                    '\x1b[1;33m  | |  __/ |  | | | | | | | | | | (_| | | |  __/|  _  |  __/ \x1b[0m',
                    '\x1b[1;33m  |_|\\___|_|  |_| |_| |_|_|_| |_|\\__,_|_| |_|   |_| |_|_|    \x1b[0m',
                    '',
                    '\x1b[1;33mWelcome to the authentic terminal experience!\x1b[0m',
                    '\x1b[32mтЬУ Real-time command output streaming\x1b[0m',
                    '\x1b[36mCurrent directory: \x1b[1;37m' + this.cwd + '\x1b[0m',
                    '',
                    '\x1b[1;31mтЪая╕П  SECURITY WARNING:\x1b[0m \x1b[31mThis script allows arbitrary command execution.\x1b[0m',
                    '\x1b[31mPassword-protect or delete it when not in use.\x1b[0m',
                    '',
                    '\x1b[1;34mShortcuts:\x1b[0m',
                    '  \x1b[36mтЖС/тЖУ\x1b[0m     Command history',
                    '  \x1b[36mTab\x1b[0m     Auto-complete',
                    '  \x1b[36mCtrl+C\x1b[0m  Interrupt/Clear',
                    '  \x1b[36mCtrl+L\x1b[0m  Clear screen',
                    '  \x1b[36mCtrl+V\x1b[0m  Paste from clipboard',
                    '',
                    '\x1b[90m' + 'тФА'.repeat(64) + '\x1b[0m',
                    ''
                ];
                
                welcome.forEach(line => {
                    this.term.writeln(line);
                });
            }
            
            showPrompt() {
                if (this.isLoading) return;
                
                // Create a colorful prompt
                const user = 'user';
                const host = 'webterm';
                const shortCwd = this.getShortPath(this.cwd);
                
                const prompt = `\x1b[1;32m${user}@${host}\x1b[0m:\x1b[1;34m${shortCwd}\x1b[0m$ `;
                this.term.write(prompt);
            }
            
            getShortPath(path) {
                const homeDir = '<?php echo addslashes($_SERVER["HOME"] ?? ""); ?>';
                if (homeDir && path.startsWith(homeDir)) {
                    return '~' + path.substring(homeDir.length);
                }
                return path;
            }
            
            handleInput(data) {
                if (this.isLoading) return;
                
                const code = data.charCodeAt(0);
                
                // Handle special keys
                switch (code) {
                    case 13: // Enter
                        this.executeCommand();
                        break;
                        
                    case 9: // Tab
                        this.handleTabCompletion();
                        break;
                        
                    case 3: // Ctrl+C
                        this.handleInterrupt();
                        break;
                        
                    case 12: // Ctrl+L
                        this.clearScreen();
                        break;
                        
                    case 22: // Ctrl+V (paste)
                        this.handlePaste();
                        break;
                        
                    case 127: // Backspace
                        this.handleBackspace();
                        break;
                        
                    case 27: // Escape sequences (arrow keys, etc.)
                        this.handleEscapeSequence(data);
                        break;
                        
                    default:
                        // Regular character input
                        if (code >= 32 && code <= 126) {
                            this.addCharacter(data);
                        }
                        break;
                }
            }
            
            handleEscapeSequence(data) {
                if (data === '\x1b[A') { // Up arrow
                    this.navigateHistory(-1);
                } else if (data === '\x1b[B') { // Down arrow
                    this.navigateHistory(1);
                } else if (data === '\x1b[C') { // Right arrow
                    // Move cursor right (if implemented)
                } else if (data === '\x1b[D') { // Left arrow
                    // Move cursor left (if implemented)
                }
            }
            
            addCharacter(char) {
                this.currentCommand += char;
                this.term.write(char);
                this.resetCompletion();
            }
            
            async handlePaste() {
                try {
                    // Check if clipboard API is available
                    if (navigator.clipboard && navigator.clipboard.readText) {
                        const text = await navigator.clipboard.readText();
                        if (text) {
                            // Clean the text (remove newlines and non-printable characters)
                            const cleanText = text.replace(/[\r\n]/g, ' ').replace(/[^\x20-\x7E]/g, '');
                            this.currentCommand += cleanText;
                            this.term.write(cleanText);
                            this.resetCompletion();
                        }
                    } else {
                        // Fallback: show a message that manual paste is needed
                        this.term.write('\x1b[33m(Use right-click to paste)\x1b[0m');
                        setTimeout(() => {
                            // Clear the message after 2 seconds
                            this.term.write('\r\x1b[K');
                            this.showPrompt();
                            this.term.write(this.currentCommand);
                        }, 2000);
                    }
                } catch (error) {
                    console.error('Paste failed:', error);
                    // Show fallback message
                    this.term.write('\x1b[33m(Use right-click to paste)\x1b[0m');
                    setTimeout(() => {
                        this.term.write('\r\x1b[K');
                        this.showPrompt();
                        this.term.write(this.currentCommand);
                    }, 2000);
                }
            }
            
            handleBackspace() {
                if (this.currentCommand.length > 0) {
                    this.currentCommand = this.currentCommand.slice(0, -1);
                    this.term.write('\b \b');
                    this.resetCompletion();
                }
            }
            
            clearCurrentLine() {
                // Move to beginning of line and clear it
                this.term.write('\r\x1b[K');
            }
            
            redrawCurrentLine() {
                this.clearCurrentLine();
                this.showPrompt();
                this.term.write(this.currentCommand);
            }
            
            navigateHistory(direction) {
                if (this.commandHistory.length === 0) return;
                
                if (direction === -1) { // Up
                    if (this.historyIndex === -1) {
                        this.historyIndex = this.commandHistory.length - 1;
                    } else if (this.historyIndex > 0) {
                        this.historyIndex--;
                    }
                } else { // Down
                    if (this.historyIndex === -1) return;
                    
                    this.historyIndex++;
                    if (this.historyIndex >= this.commandHistory.length) {
                        this.historyIndex = -1;
                        this.currentCommand = '';
                        this.redrawCurrentLine();
                        return;
                    }
                }
                
                this.currentCommand = this.commandHistory[this.historyIndex];
                this.redrawCurrentLine();
                this.resetCompletion();
            }
            
            async handleTabCompletion() {
                if (!this.isCompleting) {
                    // Start new completion
                    this.originalCommand = this.currentCommand;
                    this.completionSuggestions = await this.getCompletions(this.currentCommand);
                    this.completionIndex = -1;
                    this.isCompleting = true;
                }
                
                if (this.completionSuggestions.length === 0) {
                    this.resetCompletion();
                    return;
                }
                
                if (this.completionSuggestions.length === 1) {
                    // Single match - complete it
                    this.currentCommand = this.completionSuggestions[0];
                    this.redrawCurrentLine();
                    this.resetCompletion();
                } else {
                    // Multiple matches - cycle through them
                    this.completionIndex = (this.completionIndex + 1) % this.completionSuggestions.length;
                    this.currentCommand = this.completionSuggestions[this.completionIndex];
                    this.redrawCurrentLine();
                    
                    // Show completion count
                    this.term.write(`\x1b[90m (${this.completionIndex + 1}/${this.completionSuggestions.length})\x1b[0m`);
                }
            }
            
            async getCompletions(command) {
                const parts = command.split(' ');
                const lastPart = parts[parts.length - 1];
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'autocomplete');
                    formData.append('prefix', lastPart);
                    formData.append('cwd', this.cwd);
                    
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const suggestions = await response.json();
                    
                    // Complete the full command with suggestions
                    return suggestions.map(suggestion => {
                        const commandParts = parts.slice(0, -1);
                        commandParts.push(suggestion);
                        return commandParts.join(' ');
                    });
                } catch (error) {
                    console.error('Completion error:', error);
                    return [];
                }
            }
            
            resetCompletion() {
                this.isCompleting = false;
                this.completionSuggestions = [];
                this.completionIndex = -1;
                this.originalCommand = '';
            }
            
            handleInterrupt() {
                if (this.isLoading) {
                    this.term.writeln('\n\x1b[31m^C\x1b[0m');
                    this.term.writeln('\x1b[33mCommand interrupted (HTTP mode - cannot kill process)\x1b[0m');
                } else {
                    this.term.writeln('\n\x1b[31m^C\x1b[0m');
                }
                
                this.currentCommand = '';
                this.resetCompletion();
                this.isLoading = false;
                this.showPrompt();
            }
            
            getSpinnerChars() {
                if (this.spinnerSize >= 18) {
                    // Extra large: Use the most visible Braille characters
                    return ['тг╛', 'тг╜', 'тг╗', 'тв┐', 'тб┐', 'тгЯ', 'тгп', 'тг╖'];
                } else if (this.spinnerSize >= 16) {
                    // Large: More visible Braille characters
                    return ['тг╛', 'тг╜', 'тг╗', 'тв┐', 'тб┐', 'тгЯ', 'тгп', 'тг╖'];
                } else {
                    // Normal size: Original Braille characters
                    return ['таЛ', 'таЩ', 'та╣', 'та╕', 'та╝', 'та┤', 'таж', 'таз', 'таЗ', 'таП'];
                }
            }
            
            getSpinnerStyle() {
                // Enhanced spinner styling for better visibility
                if (this.spinnerSize >= 16) {
                    // For larger sizes, use bold white with potential background
                    return '\x1b[1;37m'; // Bold bright white
                } else {
                    return '\x1b[37m'; // Normal white
                }
            }
            
            setSpinnerSize(size) {
                this.spinnerSize = size;
                // Update CSS variable
                document.documentElement.style.setProperty('--spinner-font-size', size + 'px');
            }
            
            clearScreen() {
                this.term.write('\x1b[2J\x1b[H');
                this.showPrompt();
            }
            
            async executeCommand() {
                const command = this.currentCommand.trim();
                
                this.term.writeln(''); // Move to next line
                
                if (!command) {
                    this.showPrompt();
                    return;
                }
                
                // Add to history
                if (this.commandHistory[this.commandHistory.length - 1] !== command) {
                    this.commandHistory.push(command);
                }
                this.historyIndex = -1;
                this.resetCompletion();
                
                // Handle built-in commands
                if (command === 'clear' || command === 'cls') {
                    this.clearScreen();
                    this.currentCommand = '';
                    return;
                }
                
                this.isLoading = true;
                this.currentCommand = '';
                this.spinnerCleared = false; // Reset spinner state
                
                // Start spinner animation immediately
                let spinnerState = 0;
                const spinnerChars = this.getSpinnerChars();
                const spinnerStyle = this.getSpinnerStyle();
                
                // Show initial spinner
                this.term.write(`${spinnerStyle}${spinnerChars[spinnerState]} \x1b[90m\x1b[0m`);
                
                const spinnerInterval = setInterval(() => {
                    if (!this.isLoading) {
                        clearInterval(spinnerInterval);
                        return;
                    }
                    
                    spinnerState = (spinnerState + 1) % spinnerChars.length;
                    
                    // Update spinner at beginning of current line
                    this.term.write(`\r${spinnerStyle}${spinnerChars[spinnerState]} \x1b[90m\x1b[0m \x1b[K`);
                }, 100);
                
                try {
                    // Use streaming for real-time output, with fallback to regular fetch
                    let streamingSuccessful = false;
                    try {
                        await this.executeCommandStreaming(command, spinnerInterval);
                        streamingSuccessful = true;
                    } catch (streamError) {
                        console.warn('Streaming failed, falling back to regular fetch:', streamError);
                        clearInterval(spinnerInterval);
                        this.term.write('\r\x1b[K'); // Clear spinner
                        this.isLoading = true; // Reset loading state for fallback
                        await this.executeCommandFallback(command, null); // Don't pass spinner interval
                        streamingSuccessful = true;
                    }
                    
                    // If neither streaming nor fallback worked, show an error
                    if (!streamingSuccessful) {
                        throw new Error('Both streaming and fallback failed');
                    }
                } catch (error) {
                    // Stop spinner and clear loading indicator
                    clearInterval(spinnerInterval);
                    this.term.write('\r\x1b[K');
                    this.term.writeln('\x1b[31mError: Network request failed\x1b[0m');
                    console.error('Command execution error:', error);
                    this.isLoading = false;
                }
                
                this.showPrompt();
            }
            
            async executeCommandStreaming(command, spinnerInterval) {
                return new Promise((resolve, reject) => {
                    const formData = new FormData();
                    formData.append('cmd', command);
                    formData.append('cwd', this.cwd);
                    formData.append('stream', 'true');
                    
                    const xhr = new XMLHttpRequest();
                    let outputBuffer = '';
                    let spinnerCleared = false;
                    let hasOutput = false;
                    let isSSEResponse = false;
                    
                    xhr.open('POST', window.location.href, true);
                    
                    xhr.onreadystatechange = () => {
                        if (xhr.readyState === XMLHttpRequest.LOADING || xhr.readyState === XMLHttpRequest.DONE) {
                            const response = xhr.responseText;
                            const newData = response.substring(outputBuffer.length);
                            outputBuffer = response;
                            
                            if (newData) {
                                hasOutput = true;
                                
                                // Check if this is an SSE response
                                if (newData.includes('event:') || newData.includes('data:')) {
                                    isSSEResponse = true;
                                }
                                
                                this.processStreamData(newData, spinnerInterval, () => {
                                    spinnerCleared = true;
                                });
                            }
                            
                            if (xhr.readyState === XMLHttpRequest.DONE) {
                                // If we got a response but it wasn't SSE format and we didn't process it yet
                                if (hasOutput && !isSSEResponse && !spinnerCleared && outputBuffer.trim()) {
                                    console.warn('Received non-SSE response in streaming mode');
                                    reject(new Error('Non-SSE response received'));
                                    return;
                                }
                                
                                // If no output was received through streaming, clear spinner
                                if (!spinnerCleared && !hasOutput) {
                                    clearInterval(spinnerInterval);
                                    this.term.write('\r\x1b[K'); // Clear spinner line
                                }
                                this.isLoading = false;
                                
                                // Check for successful completion but no output
                                if (xhr.status === 200 && !hasOutput) {
                                    console.warn('Command completed but no streaming output received');
                                }
                                
                                resolve();
                            }
                        }
                    };
                    
                    xhr.ontimeout = () => {
                        clearInterval(spinnerInterval);
                        this.term.write('\r\x1b[K');
                        this.isLoading = false;
                        reject(new Error('Request timeout'));
                    };
                    
                    xhr.onerror = () => {
                        clearInterval(spinnerInterval);
                        this.term.write('\r\x1b[K');
                        this.isLoading = false;
                        reject(new Error('Network error'));
                    };
                    
                    // Set timeout to 30 seconds
                    xhr.timeout = 30000;
                    xhr.send(formData);
                });
            }
            
            processStreamData(data, spinnerInterval, onSpinnerCleared) {
                const lines = data.split('\n');
                
                for (const line of lines) {
                    if (line.trim() === '') continue; // Skip empty lines
                    
                    if (line.startsWith('event: ') || line.startsWith('data: ')) {
                        if (line.startsWith('data: ')) {
                            try {
                                const jsonData = line.substring(6);
                                if (jsonData.trim()) {
                                    const eventData = JSON.parse(jsonData);
                                    this.handleStreamEvent(eventData, spinnerInterval, onSpinnerCleared);
                                }
                            } catch (e) {
                                console.warn('Failed to parse SSE data:', line, e);
                                // If JSON parsing fails, treat as raw output
                                if (!this.spinnerCleared) {
                                    this.term.write('\r\x1b[K\n');
                                    this.spinnerCleared = true;
                                    clearInterval(spinnerInterval);
                                    onSpinnerCleared();
                                }
                                this.term.write(line.substring(6));
                            }
                        }
                    } else if (line.startsWith('{') && (line.includes('"output"') || line.includes('"cwd"'))) {
                        // Handle JSON response that wasn't properly formatted as SSE
                        try {
                            const jsonResponse = JSON.parse(line);
                            if (!this.spinnerCleared) {
                                this.term.write('\r\x1b[K\n');
                                this.spinnerCleared = true;
                                clearInterval(spinnerInterval);
                                onSpinnerCleared();
                            }
                            
                            // Update CWD if provided
                            if (jsonResponse.cwd) {
                                this.cwd = jsonResponse.cwd;
                            }
                            
                            // Display output if available
                            if (jsonResponse.output) {
                                this.writeWithAnsi(jsonResponse.output);
                            }
                        } catch (e) {
                            console.warn('Failed to parse JSON response:', line, e);
                            // Fallback to raw display
                            if (!this.spinnerCleared) {
                                this.term.write('\r\x1b[K\n');
                                this.spinnerCleared = true;
                                clearInterval(spinnerInterval);
                                onSpinnerCleared();
                            }
                            this.term.writeln('\x1b[31mReceived malformed response\x1b[0m');
                        }
                    } else {
                        // Handle non-SSE formatted responses (fallback for regular responses)
                        if (line.trim()) {
                            if (!this.spinnerCleared) {
                                this.term.write('\r\x1b[K\n');
                                this.spinnerCleared = true;
                                clearInterval(spinnerInterval);
                                onSpinnerCleared();
                            }
                            this.term.writeln(line);
                        }
                    }
                }
            }
            
            handleStreamEvent(eventData, spinnerInterval, onSpinnerCleared) {
                if (eventData.data) {
                    // Real-time chunk output - clear spinner and move to new line first
                    if (!this.spinnerCleared) {
                        this.term.write('\r\x1b[K\n'); // Clear spinner line then go to new line
                        this.spinnerCleared = true;
                        clearInterval(spinnerInterval);
                        onSpinnerCleared();
                    }
                    this.term.write(eventData.data);
                } else if (eventData.output !== undefined) {
                    // Complete output (for fast commands)
                    if (!this.spinnerCleared) {
                        clearInterval(spinnerInterval);
                        this.spinnerCleared = true;
                        onSpinnerCleared();
                        // Always add newline if there's any output (even empty string)
                        this.term.write('\r\x1b[K\n'); // Clear spinner line then go to new line
                    }
                    // Show output even if it's empty (some commands legitimately return empty output)
                    if (eventData.output) {
                        this.writeWithAnsi(eventData.output);
                    }
                    
                    // Update current working directory
                    if (eventData.cwd) {
                        this.cwd = eventData.cwd;
                    }
                } else if (eventData.message) {
                    // Error message
                    if (!this.spinnerCleared) {
                        this.term.write('\r\x1b[K\n'); // Clear spinner line then go to new line
                        this.spinnerCleared = true;
                        clearInterval(spinnerInterval);
                        onSpinnerCleared();
                    }
                    this.term.writeln('\x1b[31mError: ' + eventData.message + '\x1b[0m');
                } else if (eventData.success !== undefined) {
                    // Command completed - clear spinner if not already cleared
                    if (!this.spinnerCleared) {
                        clearInterval(spinnerInterval);
                        this.term.write('\r\x1b[K'); // Clear spinner line completely, no newline
                        this.spinnerCleared = true;
                        onSpinnerCleared();
                    }
                }
            }
            
            async executeCommandFallback(command, spinnerInterval) {
                const formData = new FormData();
                formData.append('cmd', command);
                formData.append('cwd', this.cwd);
                
                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const result = await response.json();
                    
                    // Clear spinner completely (only if not already cleared)
                    if (this.isLoading && spinnerInterval) {
                        clearInterval(spinnerInterval);
                        this.term.write('\r\x1b[K'); // Clear spinner line
                    }
                    this.isLoading = false;
                    
                    // Update current working directory
                    if (result.cwd) {
                        this.cwd = result.cwd;
                    }
                    
                    // Display output - always show output if present, even if it's just whitespace
                    if (result.output !== undefined) {
                        if (result.output.trim()) {
                            this.term.write('\n'); // Add newline before output
                            this.writeWithAnsi(result.output);
                        } else if (result.output.length > 0) {
                            // Output exists but is only whitespace - still show it
                            this.term.write('\n');
                            this.term.write(result.output);
                        }
                    }
                    
                    if (result.error) {
                        // Error styling is handled by ANSI codes in the output
                        console.warn('Command executed with error flag:', result);
                    }
                    
                } catch (error) {
                    // Clear spinner if it exists
                    if (this.isLoading && spinnerInterval) {
                        clearInterval(spinnerInterval);
                        this.term.write('\r\x1b[K');
                    }
                    this.isLoading = false;
                    
                    console.error('Fallback execution failed:', error);
                    this.term.write('\n');
                    this.term.writeln('\x1b[31mError: Failed to execute command via fallback method\x1b[0m');
                    this.term.writeln('\x1b[31m' + error.message + '\x1b[0m');
                }
            }
            
            writeWithAnsi(text) {
                // xterm.js handles ANSI codes natively, so we can write directly
                const lines = text.split('\n');
                lines.forEach((line, index) => {
                    if (index === lines.length - 1 && line === '') {
                        // Don't write empty last line (avoid extra newline)
                        return;
                    }
                    this.term.writeln(line);
                });
            }
        }
        
        // Initialize terminal when page loads
        document.addEventListener('DOMContentLoaded', () => {
            new XtermTerminal();
        });
    </script>
</body>
</html>

<?php
// =====================================================================
// PHP Backend Logic
// This part handles the AJAX requests sent from the Vue.js frontend.
// =====================================================================

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
            
            // Linux-like completion logic
            if (empty($filename) || $filename === '*') {
                // Show all files if empty or wildcard
                $fullPath = $searchDir . DIRECTORY_SEPARATOR . $file;
                $suggestion = $dir . $file;
                
                // Add trailing slash for directories
                if (is_dir($fullPath)) {
                    $suggestion .= '/';
                }
                
                $suggestions[] = $suggestion;
            } else {
                // Only show files that start with the prefix (case-insensitive)
                if (stripos($file, $filename) === 0) {
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
    }
    
    // Sort suggestions alphabetically (Linux-like)
    sort($suggestions, SORT_STRING | SORT_FLAG_CASE);
    
    // Limit suggestions to prevent overwhelming output
    $suggestions = array_slice($suggestions, 0, 50);
    
    header('Content-Type: text/plain');
    echo implode("\n", $suggestions);
    exit;
}

/**
 * Custom ls command handler with Ubuntu-like colors
 */
function handleCustomLsCommand($command, $cwd) {
    // Ensure the provided directory exists
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
            // It's a directory path
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
        echo "ls: cannot access '$targetDir': No such file or directory\n";
        echo "\n__CWD_END__";
        echo $cwd;
        return;
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
    
    if ($longFormat) {
        // Long format listing
        foreach ($filteredFiles as $file) {
            $fullPath = $targetDir . DIRECTORY_SEPARATOR . $file;
            $stat = stat($fullPath);
            
            // File permissions
            $perms = '';
            if (is_dir($fullPath)) {
                $perms = 'd';
            } elseif (is_link($fullPath)) {
                $perms = 'l';
            } else {
                $perms = '-';
            }
            
            // Basic permission display (simplified for cross-platform compatibility)
            $perms .= is_readable($fullPath) ? 'r' : '-';
            $perms .= is_writable($fullPath) ? 'w' : '-';
            $perms .= is_executable($fullPath) ? 'x' : '-';
            $perms .= is_readable($fullPath) ? 'r' : '-';
            $perms .= is_writable($fullPath) ? 'w' : '-';
            $perms .= is_executable($fullPath) ? 'x' : '-';
            $perms .= is_readable($fullPath) ? 'r' : '-';
            $perms .= is_writable($fullPath) ? 'w' : '-';
            $perms .= is_executable($fullPath) ? 'x' : '-';
            
            // File size (formatted for readability)
            $size = $stat['size'];
            $sizeFormatted = formatFileSize($size);
            
            // Last modified time
            $mtime = date('M d H:i', $stat['mtime']);
            
            // Color-coded filename
            $coloredName = getColoredFilename($file, $fullPath);
            
            echo sprintf("%-10s %8s %s %s\n", $perms, $sizeFormatted, $mtime, $coloredName);
        }
    } else {
        // Simple format - just filenames with colors, one per line
        $output = '';
        
        foreach ($filteredFiles as $file) {
            $fullPath = $targetDir . DIRECTORY_SEPARATOR . $file;
            $coloredName = getColoredFilename($file, $fullPath);
            
            // Add the colored filename with a newline
            $output .= $coloredName . "\n";
        }
        
        echo $output;
    }
    
    echo "\n__CWD_END__";
    echo $cwd;
}

/**
 * Get color-coded filename with ANSI escape codes
 */
function getColoredFilename($filename, $fullPath) {
    // Ubuntu-like colors using ANSI escape codes
    $reset = "\033[0m";
    
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

// Check if the request is a POST request with a 'cmd' parameter.
if (isset($_POST['cmd'])) {
    // --- REAL-TIME STREAMING SETUP ---
    // Disable gzip compression and output buffering for real-time output.
    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', 1);
    }
    @ini_set('zlib.output_compression', 0);
    @ini_set('implicit_flush', 1);

    // Set headers for streaming plain text
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    
    // Clear all existing output buffers
    while (@ob_end_flush());
    ob_implicit_flush(true);
    
    // Get the command and the current working directory from the POST data.
    $command = $_POST['cmd'];
    $cwd = $_POST['cwd'];
    
    // Handle custom ls command with colors
    if (preg_match('/^\s*ls(\s|$)/', $command)) {
        handleCustomLsCommand($command, $cwd);
        exit;
    }

    // --- SECURITY WARNING ---
    // The use of functions like passthru() or shell_exec() is extremely
    // dangerous if this file is publicly accessible. Anyone could run
    // any command on your server.

    // Ensure the provided directory exists and is a directory.
    // Fallback to the script's directory if it's invalid.
    if (!is_dir($cwd)) {
        $cwd = getcwd();
    }

    // '2>&1' redirects stderr to stdout, so you see errors in the terminal output.
    $full_command = 'cd ' . escapeshellarg($cwd) . ' && ' . $command . ' 2>&1';
    
    // Use proc_open to execute the command and get pipes to its stdout.
    $descriptorspec = array(
       0 => array("pipe", "r"),  // stdin
       1 => array("pipe", "w"),  // stdout
       2 => array("pipe", "w")   // stderr (redirected to stdout)
    );

    $process = proc_open($full_command, $descriptorspec, $pipes, $cwd);

    if (is_resource($process)) {
        // We don't need stdin
        fclose($pipes[0]);

        // Set pipes to non-blocking for better real-time streaming
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        // Read from both stdout and stderr in real-time
        while (true) {
            $status = proc_get_status($process);
            
            // Read from stdout
            $output = fread($pipes[1], 8192);
            if ($output !== false && $output !== '') {
                echo $output;
                flush();
            }
            
            // Read from stderr
            $error = fread($pipes[2], 8192);
            if ($error !== false && $error !== '') {
                echo $error;
                flush();
            }
            
            // Break if process is no longer running and no more output
            if (!$status['running'] && $output === '' && $error === '') {
                break;
            }
            
            // Small delay to prevent excessive CPU usage
            usleep(10000); // 10ms
        }
        
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        // Close the process
        proc_close($process);
    }

    // After the stream, send our special marker to separate output from CWD.
    echo "\n__CWD_END__";
    flush();
    
    // For directory changes, we need to detect the new working directory
    // Check if this was a 'cd' command or similar directory-changing command
    if (preg_match('/^\s*cd\s+/', $command)) {
        // Run a simple pwd command to get the current directory
        $pwd_command = 'cd ' . escapeshellarg($cwd) . ' && ' . $command . ' && pwd 2>/dev/null';
        $new_cwd = shell_exec($pwd_command);
        if ($new_cwd && is_dir(trim($new_cwd))) {
            echo trim($new_cwd);
        } else {
            echo $cwd; // Fallback to the old CWD if cd failed
        }
    } else {
        // For non-cd commands, just return the current directory
        echo $cwd;
    }
    flush();
    
    // Stop script execution to prevent the HTML below from being sent in the AJAX response.
    exit;
}

// =====================================================================
// HTML, CSS (Tailwind), and Vue.js Frontend
// This part is rendered on the initial page load.
// =====================================================================
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Terminal</title>
    <!-- Tailwind CSS for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Vue.js for interactivity -->
    <script src="https://unpkg.com/vue@3"></script>
    <!-- Fira Code font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Simple styling for a terminal look and feel */
        body {
            background-color: #1a1b26; /* Dark theme */
            overflow-x: hidden; /* Prevent horizontal scrolling on body */
            font-family: 'Fira Code', monospace; /* Use Fira Code font */
        }
        /* Make the input field blend seamlessly with the terminal line */
        #command-input {
            background: transparent;
            border: none;
            outline: none;
            color: #c0caf5; /* Light text color */
            width: 100%;
            font-family: 'Fira Code', monospace;
        }
        /* Custom scrollbar for a better look */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #2a2c3a;
        }
        ::-webkit-scrollbar-thumb {
            background: #4e526b;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #7a80a2;
        }
        /* Custom spinner animation */
        .spinner {
            display: inline-block;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        /* Loading state styling */
        #command-input:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        /* Allow text selection in terminal output and ensure text wrapping */
        .terminal-output {
            user-select: text;
            -webkit-user-select: text;
            -moz-user-select: text;
            -ms-user-select: text;
            word-wrap: break-word;
            word-break: break-all;
            overflow-wrap: break-word;
        }
        /* Highlight selected text */
        ::selection {
            background-color: #4a5568;
            color: #fff;
        }
        ::-moz-selection {
            background-color: #4a5568;
            color: #fff;
        }
        /* Prevent horizontal overflow on the main app container */
        #app {
            max-width: 100vw;
            overflow-x: hidden;
            box-sizing: border-box;
        }
        /* Ensure input doesn't cause overflow */
        #command-input {
            min-width: 0;
            max-width: 100%;
        }
        /* Ensure pre elements wrap text properly */
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            word-break: break-all;
            overflow-wrap: break-word;
            max-width: 100%;
            font-family: 'Fira Code', monospace;
        }
        /* Ubuntu-like directory styling */
        .directory {
            color: #5c7cfa !important;
            font-weight: 600;
        }
        .executable {
            color: #51cf66 !important;
            font-weight: 500;
        }
        .symlink {
            color: #22d3ee !important;
        }
        .compressed {
            color: #f06292 !important;
        }
    </style>
</head>
<body class="h-full text-sm text-[#c0caf5]" style="font-family: 'Fira Code', monospace;">

<div id="app" class="h-full p-4 flex flex-col relative" @click="handleClick">
    <!-- "Copied!" message -->
    <div v-if="copySuccessMessage" class="absolute top-2 right-2 bg-green-600 text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg transition-opacity duration-300 z-10">
        {{ copySuccessMessage }}
    </div>

    <!-- Terminal Output Area -->
    <div class="flex-grow overflow-y-auto overflow-x-hidden terminal-output" ref="output">
        <div class="mb-2">
            <h1 class="text-lg text-[#7aa2f7] font-bold">PHP Web Terminal</h1>
            <p class="text-[#a9b1d6]">
                Welcome! Current directory is <span class="font-bold text-yellow-300"><?php echo getcwd(); ?></span>.
            </p>
            <p class="text-xs text-red-400 mt-2">
                <span class="font-bold">SECURITY WARNING:</span> This script allows arbitrary command execution.
                Password-protect or delete it when not in use.
            </p>
            <p class="text-xs text-blue-300 mt-1">
                <span class="font-bold">Shortcuts:</span> 
                ↑/↓ arrows (command history), Tab (auto-complete), Ctrl+C (clear/interrupt), Ctrl+A (select all)
            </p>
            <hr class="border-gray-700 my-2">
        </div>
        <!-- History of commands and outputs will be rendered here -->
        <div v-for="item in history" :key="item.id" class="terminal-output">
            <div class="flex flex-wrap">
                <span class="text-[#bb9af7]">{{ item.prompt }}</span>
                <span class="pl-2 text-[#c0caf5] break-all">{{ item.command }}</span>
                <!-- Show spinner next to the currently running command -->
                <span v-if="isLoading && item.id === history[history.length - 1].id" class="text-[#7aa2f7] ml-2 spinner">{{ currentSpinner }}</span>
            </div>
            <pre class="whitespace-pre-wrap text-[#a9b1d6] leading-snug terminal-output" v-html="item.output"></pre>
        </div>
    </div>

    <!-- Input Line -->
    <div class="flex items-start mt-2 flex-wrap">
        <span class="text-[#bb9af7] flex-shrink-0">{{ prompt }}</span>
        <input
            type="text"
            id="command-input"
            class="pl-2 flex-1"
            v-model="currentCommand"
            @keyup.enter="runCommand"
            @keyup.up="showPreviousCommand"
            @keyup.down="showNextCommand"
            @keydown.tab.prevent="handleTabCompletion"
            @keydown.ctrl.c.prevent="interruptCommand"
            @blur="focusInput"
            @focus="isTextSelected = false"
            ref="input"
            autocomplete="off"
            autofocus
            :disabled="isLoading"
        />
        <!-- Loading spinner appears when command is running -->
        <span v-if="isLoading" class="text-[#7aa2f7] ml-2 text-lg spinner">{{ currentSpinner }}</span>
        <!-- Completion indicator -->
        <span v-if="isCompleting && completionSuggestions.length > 0" class="text-[#f7768e] ml-2 text-xs">
            {{ currentCompletionIndex + 1 }}/{{ completionSuggestions.length }}
        </span>
    </div>
</div>

<script>
    const app = Vue.createApp({
        data() {
            return {
                history: [], // Stores { id, prompt, command, output }
                currentCommand: '',
                commandHistory: [], // Stores just the command strings for up/down arrows
                historyIndex: -1,
                cwd: '<?php echo addslashes(getcwd()); ?>', // Initial working directory from PHP
                promptUser: 'user@host', // A static user/host string
                isLoading: false,
                copySuccessMessage: '',
                // Loading Animation State
                spinnerFrames: ['|', '/', '─', '\\'],
                currentSpinnerFrame: 0,
                spinnerInterval: null,
                // Auto-completion state
                completionSuggestions: [],
                currentCompletionIndex: -1,
                isCompleting: false,
                originalCommand: '',
            };
        },
        computed: {
            /**
             * Generates the command prompt string.
             */
            prompt() {
                // Replace the PHP home directory with '~' for a classic look
                const homeDir = '<?php echo addslashes($_SERVER["HOME"] ?? ""); ?>';
                let displayCwd = this.cwd;
                if (homeDir && displayCwd.startsWith(homeDir)) {
                    displayCwd = '~' + displayCwd.substring(homeDir.length);
                }
                return `${this.promptUser}:${displayCwd}$ `;
            },

            /**
             * Gets the current spinner character for the loading animation.
             */
            currentSpinner() {
                return this.spinnerFrames[this.currentSpinnerFrame];
            }
        },
        watch: {
            // Watch for changes in loading state to refocus
            isLoading(newVal, oldVal) {
                if (oldVal === true && newVal === false) {
                    // Command just finished, ensure focus
                    this.forceFocus();
                }
            },
            
            // Watch for changes in currentCommand to maintain focus
            currentCommand(newVal, oldVal) {
                // Reset completion if user is typing (not using tab completion)
                if (!this.isCompleting) {
                    this.resetCompletion();
                }
                this.focusInput();
            }
        },
        methods: {
            /**
             * Focuses the command input field with a small delay to ensure it works reliably.
             */
            focusInput() {
                this.$nextTick(() => {
                    if (this.$refs.input) {
                        this.$refs.input.focus();
                    }
                });
            },

            /**
             * Force focus with additional delay for stubborn cases.
             */
            forceFocus() {
                setTimeout(() => {
                    this.focusInput();
                }, 10);
            },

            /**
             * Handles tab completion for commands and file paths (Linux-like behavior).
             */
            async handleTabCompletion() {
                const command = this.currentCommand;
                const parts = command.split(' ');
                
                if (!this.isCompleting) {
                    // Start new completion
                    this.originalCommand = command;
                    this.isCompleting = true;
                    this.currentCompletionIndex = -1;
                    
                    if (parts.length === 1) {
                        // Complete command names
                        this.completionSuggestions = await this.getCommandCompletions(parts[0]);
                    } else {
                        // Complete file/directory names with Linux-like behavior
                        const lastPart = parts[parts.length - 1];
                        this.completionSuggestions = await this.getLinuxStyleFileCompletions(lastPart);
                    }
                }
                
                if (this.completionSuggestions.length === 1) {
                    // Exact match - auto-complete it
                    if (parts.length === 1) {
                        this.currentCommand = this.completionSuggestions[0];
                    } else {
                        const newParts = [...parts];
                        newParts[newParts.length - 1] = this.completionSuggestions[0];
                        this.currentCommand = newParts.join(' ');
                    }
                    this.isCompleting = false;
                } else if (this.completionSuggestions.length > 1) {
                    // Multiple matches - cycle through them
                    this.currentCompletionIndex = (this.currentCompletionIndex + 1) % this.completionSuggestions.length;
                    
                    if (parts.length === 1) {
                        this.currentCommand = this.completionSuggestions[this.currentCompletionIndex];
                    } else {
                        const newParts = [...parts];
                        newParts[newParts.length - 1] = this.completionSuggestions[this.currentCompletionIndex];
                        this.currentCommand = newParts.join(' ');
                    }
                } else {
                    // No completions found - do nothing (Linux-like behavior)
                    this.isCompleting = false;
                }
            },

            /**
             * Gets command completions from a predefined list.
             */
            async getCommandCompletions(prefix) {
                const commonCommands = [
                    'ls', 'dir', 'cd', 'pwd', 'mkdir', 'rmdir', 'rm', 'cp', 'mv', 'cat', 'echo',
                    'grep', 'find', 'chmod', 'chown', 'ps', 'kill', 'top', 'htop', 'df', 'du',
                    'tar', 'zip', 'unzip', 'wget', 'curl', 'git', 'npm', 'node', 'php', 'python',
                    'python3', 'java', 'javac', 'gcc', 'make', 'cmake', 'vim', 'nano', 'emacs',
                    'clear', 'exit', 'history', 'which', 'whereis', 'man', 'help', 'sudo', 'su'
                ];
                
                if (!prefix) return commonCommands.slice(0, 10);
                
                return commonCommands.filter(cmd => cmd.startsWith(prefix.toLowerCase()));
            },

            /**
             * Gets file/directory completions from the server (Linux-style behavior).
             */
            async getLinuxStyleFileCompletions(prefix) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'autocomplete');
                    formData.append('prefix', prefix);
                    formData.append('cwd', this.cwd);
                    
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData,
                    });
                    
                    if (response.ok) {
                        const suggestions = await response.text();
                        const allSuggestions = suggestions.split('\n').filter(s => s.trim());
                        
                        // Linux-like behavior: only return suggestions if there's an exact prefix match
                        if (prefix === '' || prefix.includes('*')) {
                            // Show all files/directories if empty or contains wildcard
                            return allSuggestions;
                        } else {
                            // Only return exact matches that start with the prefix
                            const exactMatches = allSuggestions.filter(suggestion => {
                                const baseName = suggestion.replace(/\/$/, ''); // Remove trailing slash for comparison
                                return baseName.toLowerCase().startsWith(prefix.toLowerCase());
                            });
                            
                            // If there's exactly one match that completes the prefix, return it
                            if (exactMatches.length === 1) {
                                return exactMatches;
                            }
                            
                            // If there are multiple matches that start with the same prefix, return them
                            if (exactMatches.length > 1) {
                                return exactMatches;
                            }
                            
                            // No exact matches - return empty (Linux-like: do nothing)
                            return [];
                        }
                    }
                } catch (error) {
                    console.error('Autocomplete error:', error);
                }
                return [];
            },

            /**
             * Gets file/directory completions from the server.
             */
            async getFileCompletions(prefix) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'autocomplete');
                    formData.append('prefix', prefix);
                    formData.append('cwd', this.cwd);
                    
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData,
                    });
                    
                    if (response.ok) {
                        const suggestions = await response.text();
                        return suggestions.split('\n').filter(s => s.trim());
                    }
                } catch (error) {
                    console.error('Autocomplete error:', error);
                }
                return [];
            },

            /**
             * Resets completion state when user types or navigates.
             */
            resetCompletion() {
                this.isCompleting = false;
                this.completionSuggestions = [];
                this.currentCompletionIndex = -1;
                this.originalCommand = '';
            },

            /**
             * Interrupts the current running command (Ctrl+C).
             */
            interruptCommand() {
                if (this.isLoading) {
                    // If a command is running, we can't easily interrupt it with the current setup
                    // This would require WebSocket implementation for true interruption
                    this.showCopyMessage('Ctrl+C (interruption not implemented in HTTP mode)');
                } else {
                    // Clear current input
                    this.currentCommand = '';
                    this.resetCompletion();
                }
            },

            /**
             * Starts the spinner animation.
             */
            startSpinner() {
                this.currentSpinnerFrame = 0;
                this.spinnerInterval = setInterval(() => {
                    this.currentSpinnerFrame = (this.currentSpinnerFrame + 1) % this.spinnerFrames.length;
                }, 200); // Change frame every 200ms for simple characters
            },

            /**
             * Stops the spinner animation.
             */
            stopSpinner() {
                if (this.spinnerInterval) {
                    clearInterval(this.spinnerInterval);
                    this.spinnerInterval = null;
                }
            },
            
            /**
             * Scrolls the output container to the bottom.
             */
            scrollToBottom() {
                this.$nextTick(() => {
                    const outputEl = this.$refs.output;
                    if (outputEl) {
                        outputEl.scrollTop = outputEl.scrollHeight;
                    }
                    // Ensure focus is maintained after scrolling
                    this.focusInput();
                });
            },
            
            /**
             * Handles clicks on the main container to focus the input.
             */
            handleClick() {
                this.focusInput();
            },

            /**
             * Handles the execution of a command.
             */
            async runCommand() {
                if (this.isLoading) return; // Don't run commands if one is already in progress

                const command = this.currentCommand.trim();
                if (!command) {
                     // Add an empty line to history for an empty command
                    this.history.push({
                        id: Date.now(),
                        prompt: this.prompt,
                        command: '',
                        output: ''
                    });
                    this.scrollToBottom();
                    this.focusInput(); // Focus after empty command
                    return;
                };

                // Add to command history for up/down arrow navigation
                this.commandHistory.push(command);
                this.historyIndex = this.commandHistory.length;

                // Handle 'clear' as a frontend-only command
                if (command.toLowerCase() === 'clear') {
                    this.history = [];
                    this.currentCommand = '';
                    this.focusInput(); // Focus after clear command
                    return;
                }
                
                // Add the command to the display history immediately
                const historyId = Date.now();
                this.history.push({
                    id: historyId,
                    prompt: this.prompt,
                    command: command,
                    output: '' // Start with empty output
                });
                
                this.isLoading = true;
                this.currentCommand = '';
                this.startSpinner(); // Start the spinner animation
                this.scrollToBottom();

                // Prepare data for the fetch request
                const formData = new FormData();
                formData.append('cmd', command);
                formData.append('cwd', this.cwd);
                
                try {
                    const response = await fetch('', { // Post to the same file
                        method: 'POST',
                        body: formData,
                    });

                    if (!response.ok) {
                       throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    // --- REAL-TIME STREAM HANDLING ---
                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();
                    let fullOutput = '';
                    
                    const historyItem = this.history.find(h => h.id === historyId);

                    while (true) {
                        const { value, done } = await reader.read();
                        if (done) {
                            break; // Stream finished
                        }
                        
                        const chunk = decoder.decode(value, { stream: true });
                        fullOutput += chunk;

                        // Update the history item's output in real-time
                        if (historyItem) {
                            // Find the CWD marker in the full output
                            const endMarkerIndex = fullOutput.indexOf('__CWD_END__');
                            if (endMarkerIndex !== -1) {
                                // If marker found, display only the part before it
                                const outputPart = fullOutput.substring(0, endMarkerIndex);
                                historyItem.output = this.ansiToHtml(outputPart);
                                // Don't break here, continue reading for CWD info
                            } else {
                                // Otherwise, display everything received so far
                                historyItem.output = this.ansiToHtml(fullOutput);
                            }
                        }
                        this.scrollToBottom();
                    }
                    
                    // Once the stream is finished, parse the full output for CWD
                    const parts = fullOutput.split('__CWD_END__');
                    const finalOutput = parts[0] || '';
                    const newCwd = parts[1] ? parts[1].trim() : this.cwd;
                    
                    if (historyItem) {
                         historyItem.output = this.ansiToHtml(finalOutput);
                    }
                    this.cwd = newCwd;

                } catch (error) {
                    // Handle network errors or other issues
                    const historyItem = this.history.find(h => h.id === historyId);
                     if (historyItem) {
                        historyItem.output = `<span class="text-red-400">Error: ${error.message}</span>`;
                    }
                } finally {
                    this.isLoading = false;
                    this.stopSpinner(); // Stop the spinner animation
                    this.scrollToBottom();
                    this.forceFocus(); // Force focus after command completion
                }
            },

            /**
             * Navigates to the previous command in history.
             */
            showPreviousCommand() {
                this.resetCompletion(); // Reset completion when navigating history
                
                if (this.historyIndex > 0) {
                    this.historyIndex--;
                    this.currentCommand = this.commandHistory[this.historyIndex];
                } else if (this.historyIndex === -1 && this.commandHistory.length > 0) {
                    // First time pressing up arrow
                    this.historyIndex = this.commandHistory.length - 1;
                    this.currentCommand = this.commandHistory[this.historyIndex];
                }
                this.focusInput(); // Ensure focus after navigation
            },
            
            /**
             * Navigates to the next command in history.
             */
            showNextCommand() {
                this.resetCompletion(); // Reset completion when navigating history
                
                if (this.historyIndex < this.commandHistory.length - 1 && this.historyIndex !== -1) {
                    this.historyIndex++;
                    this.currentCommand = this.commandHistory[this.historyIndex];
                } else {
                    // Reached the end, clear the command
                    this.historyIndex = this.commandHistory.length;
                    this.currentCommand = '';
                }
                this.focusInput(); // Ensure focus after navigation
            },
            
            /**
             * Displays a temporary message for copy success/failure.
             */
            showCopyMessage(message) {
                this.copySuccessMessage = message;
                setTimeout(() => {
                    this.copySuccessMessage = '';
                    this.focusInput(); // Focus after message disappears
                }, 1500); // Message disappears after 1.5 seconds
            },

            /**
             * Enhanced converter for ANSI color codes and formatting to HTML spans.
             * Also handles Ubuntu-like file type coloring.
             */
            ansiToHtml(text) {
                const ansiColors = {
                    // Standard colors
                    '30': '#000000', '31': '#cd0000', '32': '#00cd00', '33': '#cdcd00',
                    '34': '#0000ee', '35': '#cd00cd', '36': '#00cdcd', '37': '#e5e5e5',
                    // Bright colors
                    '90': '#7f7f7f', '91': '#ff0000', '92': '#00ff00', '93': '#ffff00',
                    '94': '#5c5cff', '95': '#ff00ff', '96': '#00ffff', '97': '#ffffff',
                    // Bold colors (1;3x)
                    '1;30': '#555753', '1;31': '#ff5555', '1;32': '#55ff55', '1;33': '#ffff55',
                    '1;34': '#5555ff', '1;35': '#ff55ff', '1;36': '#55ffff', '1;37': '#ffffff'
                };
                
                // Escape HTML to prevent XSS
                let safeText = document.createElement('div');
                safeText.innerText = text;
                safeText = safeText.innerHTML;

                // Handle various ANSI escape sequences
                let processedText = safeText
                    // Bold color codes (1;3xm)
                    .replace(/\u001b\[1;(\d+)m(.*?)\u001b\[0m/g, (match, code, content) => {
                        const colorKey = '1;' + code;
                        const color = ansiColors[colorKey] || ansiColors[code] || 'inherit';
                        return `<span style="color:${color}; font-weight: bold;">${content}</span>`;
                    })
                    // Regular color codes with reset
                    .replace(/\u001b\[(\d+)m(.*?)\u001b\[0m/g, (match, code, content) => {
                        const color = ansiColors[code] || 'inherit';
                        return `<span style="color:${color}">${content}</span>`;
                    })
                    // Bold color codes without explicit reset
                    .replace(/\u001b\[1;(\d+)m([^\u001b]*)/g, (match, code, content) => {
                        const colorKey = '1;' + code;
                        const color = ansiColors[colorKey] || ansiColors[code] || 'inherit';
                        return `<span style="color:${color}; font-weight: bold;">${content}</span>`;
                    })
                    // Regular color codes without explicit reset
                    .replace(/\u001b\[(\d+)m([^\u001b]*)/g, (match, code, content) => {
                        const color = ansiColors[code] || 'inherit';
                        return `<span style="color:${color}">${content}</span>`;
                    })
                    // Bold text
                    .replace(/\u001b\[1m(.*?)\u001b\[0m/g, '<strong>$1</strong>')
                    .replace(/\u001b\[1m([^\u001b]*)/g, '<strong>$1</strong>')
                    // Clear screen
                    .replace(/\u001b\[2J/g, '')
                    // Clear line
                    .replace(/\u001b\[K/g, '')
                    // Reset all formatting
                    .replace(/\u001b\[0m/g, '</span>')
                    // Remove other escape sequences
                    .replace(/\u001b\[[0-9;]*[A-Za-z]/g, '');

                // Apply Ubuntu-like file type coloring for non-ANSI text
                if (!processedText.includes('<span')) {
                    processedText = this.applyUbuntuFileColors(processedText);
                }
                
                return processedText;
            },

            /**
             * Applies Ubuntu-like file type coloring to text output.
             */
            applyUbuntuFileColors(text) {
                // Skip if text already has HTML spans (already processed)
                if (text.includes('<span')) {
                    return text;
                }
                
                // Split text into lines for processing
                const lines = text.split('\n');
                const processedLines = lines.map(line => {
                    // Skip empty lines or lines that look like system output
                    if (!line.trim() || line.includes(':') || line.includes('$')) {
                        return line;
                    }
                    
                    // Split line into words (file/directory names)
                    const words = line.split(/(\s+)/);
                    const processedWords = words.map(word => {
                        const trimmed = word.trim();
                        if (!trimmed || trimmed.match(/^\s+$/)) {
                            return word; // Return whitespace as-is
                        }
                        
                        // Check if it's a directory (ends with /)
                        if (trimmed.endsWith('/')) {
                            return `<span class="directory">${word}</span>`;
                        }
                        
                        // Check if it's an executable file
                        if (trimmed.match(/\.(exe|sh|bat|com|cmd|py|pl|rb|php|js|bin)$/i)) {
                            return `<span class="executable">${word}</span>`;
                        }
                        
                        // Check if it's a compressed file
                        if (trimmed.match(/\.(zip|tar|gz|bz2|xz|7z|rar|deb|rpm)$/i)) {
                            return `<span class="compressed">${word}</span>`;
                        }
                        
                        // Check for symbolic links (indicated by -> in ls -la output)
                        if (line.includes(' -> ')) {
                            const parts = line.split(' -> ');
                            if (parts.length === 2) {
                                return line.replace(parts[0], `<span class="symlink">${parts[0]}</span>`);
                            }
                        }
                        
                        return word;
                    });
                    
                    return processedWords.join('');
                });
                
                return processedLines.join('\n');
            }
        },
        mounted() {
            this.focusInput();
            
            // Add global event listeners to maintain focus
            document.addEventListener('click', () => {
                // Small delay to allow other click handlers to complete
                setTimeout(() => {
                    this.focusInput();
                }, 50);
            });
            
            // Focus when the window regains focus
            window.addEventListener('focus', () => {
                this.focusInput();
            });
            
            // Focus when user returns to the tab
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    this.focusInput();
                }
            });
            
            // Prevent losing focus during key navigation
            document.addEventListener('keydown', (e) => {
                // Handle Ctrl+C for copying when text is selected
                if (e.ctrlKey && e.key === 'c') {
                    const selectedText = window.getSelection().toString();
                    if (selectedText.length > 0) {
                        // Let the browser handle the copy, then show message
                        setTimeout(() => {
                            this.showCopyMessage('Copied with Ctrl+C!');
                        }, 100);
                        return; // Don't prevent default, let browser copy
                    }
                    // If no text selected and input is focused, treat as interrupt command
                    if (document.activeElement === this.$refs.input) {
                        e.preventDefault();
                        this.interruptCommand();
                        return;
                    }
                }
                
                // Handle Ctrl+A to select all terminal content
                if (e.ctrlKey && e.key === 'a' && document.activeElement !== this.$refs.input) {
                    e.preventDefault();
                    const outputEl = this.$refs.output;
                    if (outputEl) {
                        const range = document.createRange();
                        range.selectNodeContents(outputEl);
                        const selection = window.getSelection();
                        selection.removeAllRanges();
                        selection.addRange(range);
                        this.showCopyMessage('All content selected - Ctrl+C to copy');
                    }
                    return;
                }
                
                // If focus is lost and it's not a special key, refocus
                if (document.activeElement !== this.$refs.input && 
                    !['Tab', 'F1', 'F2', 'F3', 'F4', 'F5', 'F6', 'F7', 'F8', 'F9', 'F10', 'F11', 'F12'].includes(e.key)) {
                    this.focusInput();
                }
            });
        },
        beforeUnmount() {
            // Clean up spinner interval if component is destroyed
            this.stopSpinner();
        }
    });

    app.mount('#app');
</script>

</body>
</html>

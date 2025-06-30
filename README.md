# PHP Web Terminal

![PHP Web Terminal](terminal-php.png)

This project provides two different web-based terminal implementations built with PHP for the backend:

1. **vue-terminal.php** - A feature-rich terminal using Vue.js, Tailwind CSS, and vanilla JavaScript
2. **terminal.php** - A modern terminal using xterm.js for an authentic terminal experience

Both terminals allow you to execute shell commands directly from your web browser with real-time output.

**⚠️ SECURITY WARNING:** This script allows arbitrary command execution on the server where it is hosted. **It is highly recommended to password-protect this file or delete it when not in active use to prevent unauthorized access and potential security breaches.**

---

## Features

### Both Terminals
* **Real-time Command Execution:** Execute shell commands and see the output streamed in real time
* **Current Working Directory (CWD) Tracking:** Maintains and displays the current working directory
* **Command History:** Navigate through previously executed commands using Up/Down arrow keys
* **Tab Completion:** Intelligent file and directory name completion
* **Single File:** Each terminal is contained within one PHP file for easy deployment
* **Security Warning System:** Clear warnings about security implications

### vue-terminal.php (Vue.js Terminal)
* **Modern Frontend Stack:** Vue.js for reactive UI and Tailwind CSS for styling
* **Real-time Output Streaming:** See command output as it happens with spinner animation
* **Built-in File Editor:** Nano-like text editor for editing files directly in the terminal
* **Enhanced Copy/Paste:** Custom context menu with improved clipboard functionality
* **Linux-style Command Environment:** Ubuntu-like color coding for ls output
* **Responsive Design:** Clean, modern interface that works on various screen sizes

### terminal.php (Xterm.js Terminal)
* **Authentic Terminal Experience:** Uses xterm.js for true terminal look and feel
* **Full ANSI Color Support:** Complete support for terminal colors and escape sequences
* **Native Terminal Features:** Proper cursor handling, scrollback buffer, and terminal emulation
* **Web Links Support:** Clickable URLs in terminal output
* **Optimized Performance:** Efficient rendering and memory usage
* **True Terminal Shortcuts:** Native Ctrl+C, Ctrl+L, and other terminal shortcuts

---

## Getting Started

### Prerequisites

* A web server (e.g., Apache, Nginx) with PHP installed.
* PHP 7.x or higher (with `proc_open` function enabled).

### Installation

1. **Save the files:** Save either `vue-terminal.php` or `terminal.php` (or both) in your web server's document root or a subfolder accessible via a web browser.
2. **Access in Browser:** 
   - For Vue.js terminal: Navigate to `http://localhost/vue-terminal.php`
   - For xterm.js terminal: Navigate to `http://localhost/terminal.php`

### Which Terminal to Use?

- **Choose `vue-terminal.php`** if you want a modern, feature-rich interface with built-in file editing capabilities
- **Choose `terminal.php`** if you prefer an authentic terminal experience with full ANSI support and native terminal feel

---

## Usage

### Basic Usage (Both Terminals)
1. Open the terminal file in your browser
2. The prompt will show your current working directory
3. Type any shell command (e.g., `ls -la`, `pwd`, `echo Hello World`)
4. Press `Enter` to execute the command
5. Use **Up/Down arrow keys** to cycle through command history
6. Use **Tab** for file/directory name completion
7. Type `clear` to clear the terminal output

### vue-terminal.php Specific Features
- **File Editor:** Type `nano filename.txt` to open the built-in editor
- **Copy/Paste:** Right-click for context menu with copy/paste options
- **Real-time Output:** Watch commands execute with live streaming output

### terminal.php Specific Features
- **Terminal Shortcuts:** 
  - `Ctrl+C` to interrupt commands
  - `Ctrl+L` to clear screen
- **ANSI Colors:** Full support for colored output
- **Authentic Feel:** True terminal cursor and scrollback behavior

---

## How it Works

Both terminals intelligently combine PHP for server-side command execution with modern JavaScript frameworks for dynamic, interactive frontends.

### PHP Backend (Both Terminals)

* Handles AJAX `POST` requests with command parameters
* Executes commands using `shell_exec()` or `proc_open()` for real-time output
* Manages current working directory tracking across requests
* Provides tab completion suggestions via separate AJAX endpoints
* Implements custom `ls` command with ANSI color support for xterm.js

### vue-terminal.php Frontend (Vue.js)

* Vue.js manages reactive UI and real-time output streaming
* Tailwind CSS provides modern, responsive styling
* Custom file editor implementation with syntax highlighting
* Enhanced copy/paste functionality with context menus
* Real-time command execution with loading indicators

### terminal.php Frontend (Xterm.js)

* Uses xterm.js library for authentic terminal emulation
* Native ANSI escape sequence processing
* True terminal cursor and scrollback behavior
* Web links addon for clickable URLs
* Fit addon for responsive terminal sizing

---

## Important Security Considerations

As stated multiple times, this script is inherently insecure if exposed publicly without proper authentication.

* **Authentication:** Implement robust authentication (e.g., HTTP Basic Auth, session-based login) to restrict access to authorized users only.
* **Whitelisting Commands:** For production environments, consider modifying the PHP script to only allow a predefined whitelist of safe commands, instead of executing arbitrary input.
* **Principle of Least Privilege:** Run your web server and PHP process with the absolute minimum necessary permissions.
* **Monitoring:** Regularly monitor your server logs for unusual activity.
* **Remove When Not Needed:** The safest approach is to remove this file from your web server entirely when it's not being actively used for development or debugging.

---

## Contributing

Feel free to fork this repository, suggest improvements, or submit pull requests.

---

## License

This project is open-source and available under the MIT License.
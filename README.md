# PHP Web Terminal

![PHP Web Terminal](terminal-php.png)

A high-performance, standalone, browser-based terminal emulator built entirely with **PHP** and **xterm.js**. **Requires zero Node.js, zero npm, and zero Composer dependencies.**

Offers two operational modes:
1. **Interactive WebSocket Mode**: Built-in pure PHP RFC-6455 WebSocket daemon (`php server.php`) for full duplex real-time streaming, persistent processes, and live typing.
2. **Standard HTTP Mode**: Works with any standard PHP web server (`php -S localhost:8000 router.php`, Apache, Nginx, or cPanel hosting) with live streaming execution, Tab autocomplete, and client-side line editing.

---

## ✨ Features

* **Zero External Dependencies**: Pure PHP backend with bundled HTML5, CSS3, and JavaScript frontend.
* **Dual Deployment Modes**:
  * **Daemon Mode (`server.php`)**: Native event-loop RFC-6455 WebSocket server supporting multiple concurrent clients and real-time PTY simulation.
  * **Web Server Mode (`router.php`)**: Runs directly on PHP's built-in web server or Apache without daemon setup.
* **VS Code Aesthetic & Layout**:
  * Modern titlebar with clean, icon-only layout controls (Single, Vertical Split, Horizontal Split, Search, Fullscreen, Settings).
  * Tab bar with unified `+ | Shell` split-button pill.
* **Authentic Dynamic OS Banners**:
  * Windows CMD (`ver`), PowerShell native startup header, and Git Bash.
  * Linux (`/etc/os-release`, `uname -srmo`).
  * macOS (`sw_vers`, `uname -srm`).
* **Interactive Shell & Tab Autocomplete**:
  * Live command typing, command history (Up/Down arrow keys), and Ctrl+C interrupt handling.
  * Tab completion for files and directories.
* **File Explorer & Transfer**:
  * Integrated sliding drawer for file browsing, file viewing/editing, file upload/download, and delete.
  * Features the official [file-icons/vscode](https://github.com/file-icons/vscode) file type icons.
* **Local JetBrains Mono Font**: Bundled offline font with instant rendering and zero latency.
* **Terminal Themes**: Tokyo Night, One Dark, Dracula, Monokai, Nord, and GitHub Dark.

---

## 🚀 Quick Start

### Option A: Standard PHP Web Server (Easiest)
Run directly from your project directory:
```bash
php -S localhost:8000 router.php
```
Open **http://localhost:8000** in your browser.

### Option B: High-Performance WebSocket Daemon
Run the pure PHP WebSocket process server:
```bash
php server.php
```
By default, the server listens on `http://0.0.0.0:8000` (serving both HTTP assets and WebSocket connections).

---

## 📁 Project Structure

```
terminal-php/
├── api/
│   ├── auth.php           # Token-based authentication handler
│   ├── autocomplete.php   # Tab file/directory completion endpoint
│   ├── banner.php         # Real dynamic OS shell banner detection
│   ├── config.php         # System configuration & shell discovery
│   ├── execute.php        # Real-time streaming command execution
│   ├── files.php          # File manager (list, read, save, upload, download, delete)
│   ├── stream.php         # Process streaming helper
│   └── websocket.php      # RFC-6455 pure PHP WebSocket server engine
├── public/
│   ├── css/
│   │   ├── file-icons.min.css # File-icons CSS
│   │   └── terminal.css       # Core terminal theme & layout styles
│   ├── fonts/             # JetBrains Mono & File-Icons woff2 fonts
│   ├── js/
│   │   ├── app.js             # Client application coordinator
│   │   ├── file-browser.js    # File Explorer drawer controller
│   │   ├── file-icons.min.js  # File-icons resolution engine
│   │   ├── terminal-tab.js    # xterm.js tab session manager
│   │   └── themes.js          # Color theme definitions
│   └── index.html         # Terminal single-page web app
├── config.php             # Master settings (shells, session dir, auth)
├── router.php             # Router for php -S localhost:8000 router.php
├── server.php             # CLI daemon entrypoint for php server.php
├── .gitignore
└── README.md
```

---

## ⚙️ Configuration

Settings can be customized in [`config.php`](file:///d:/php/terminal-php/config.php) or via environment variables:
* `TERMINAL_PASSWORD`: Master password for authentication (leave empty for open access).
* `TERMINAL_HOST`: Server listening IP (default: `0.0.0.0`).
* `TERMINAL_PORT`: Server port (default: `8000`).
* `TERMINAL_CWD`: Default starting working directory.

---

## 🔒 Security Notice

This terminal executes commands with the permissions of the PHP user. For production or public servers:
1. Always set a strong `TERMINAL_PASSWORD` in `config.php`.
2. Restrict server network access via firewall or reverse proxy.
3. Use HTTPS / WSS for encrypted transit.

---

## 📄 License
MIT License.
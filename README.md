# PHP Web Terminal

![PHP Web Terminal](terminal-php.png)

This project provides a simple, single-file web-based terminal built with PHP for the backend and Vue.js, Tailwind CSS, and vanilla JavaScript for the frontend. It allows you to execute shell commands directly from your web browser, with real-time output streaming.

**⚠️ SECURITY WARNING:** This script allows arbitrary command execution on the server where it is hosted. **It is highly recommended to password-protect this file or delete it when not in active use to prevent unauthorized access and potential security breaches.**

---

## Features

* **Real-time Command Execution:** Execute shell commands and see the output streamed in real time.
* **Current Working Directory (CWD) Tracking:** The terminal maintains and displays the current working directory, updating it automatically after `cd` commands.
* **Command History:** Navigate through previously executed commands using the Up/Down arrow keys.
* **Basic ANSI Color Support:** Output from commands with ANSI color codes (like `ls --color=auto`) will be rendered with basic color styling.
* **Custom Context Menu:** Right-click within the terminal to access "Copy" and "Paste" functionalities.
* **Single File:** All logic (PHP backend, HTML, CSS, JavaScript) is contained within one `index.php` file, making it easy to deploy.
* **Modern Frontend Stack:** Utilizes Vue.js for reactive UI, and Tailwind CSS for utility-first styling.

---

## Getting Started

### Prerequisites

* A web server (e.g., Apache, Nginx) with PHP installed.
* PHP 7.x or higher (with `proc_open` function enabled).

### Installation

1.  **Save the file:** Save the provided code as `index.php` (or any other `.php` file) in your web server's document root or a subfolder accessible via a web browser.
2.  **Access in Browser:** Open your web browser and navigate to the URL where you placed the `index.php` file (e.g., `http://localhost/index.php` or `http://yourdomain.com/terminal/index.php`).

---

## Usage

1.  Upon opening the `index.php` file in your browser, you'll see a terminal interface.
2.  The prompt will show your current working directory.
3.  Type any shell command (e.g., `ls -la`, `pwd`, `echo Hello World`) into the input field at the bottom.
4.  Press `Enter` to execute the command.
5.  The output will appear in the main terminal area.
6.  Use the **Up arrow key** to cycle through previous commands.
7.  Use the **Down arrow key** to cycle through more recent commands or clear the input.
8.  Type `clear` and press `Enter` to clear the terminal output.
9.  **Right-click** anywhere in the terminal to bring up a context menu with "Copy" (for selected text) and "Paste" options.

---

## How it Works

The project intelligently combines PHP for server-side command execution and a modern JavaScript framework (Vue.js) for a dynamic, interactive frontend.

### PHP Backend

* When an AJAX `POST` request is received with a `cmd` parameter, the PHP script executes the command using `proc_open()` for real-time output streaming.
* It handles disabling output buffering and gzip compression to ensure immediate feedback.
* The `cwd` parameter in the POST request allows the PHP script to change its working directory before executing the command, simulating a persistent terminal session.
* A special marker (`__CWD_END__`) is used to separate the command output from the new current working directory sent back to the frontend.

### Frontend (HTML, CSS, Vue.js)

* The HTML provides the basic structure of the terminal.
* Tailwind CSS is used for minimal and efficient styling, giving it a dark, terminal-like appearance.
* Vue.js manages the reactive UI:
    * It maintains the `history` array to display past commands and their outputs.
    * It sends AJAX `POST` requests to the same `index.php` file with the command and current working directory.
    * It uses `fetch` API with `response.body.getReader()` to consume the PHP output stream chunk by chunk, updating the terminal in real time.
    * It includes basic logic to convert ANSI escape codes in the command output into HTML `<span>` elements for colored text.
    * Keyboard shortcuts for command history (`Up`/`Down` arrows) and a custom right-click context menu for copy/paste are implemented.

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
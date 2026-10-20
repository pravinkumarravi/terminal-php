class TerminalTab {
  constructor(options = {}) {
    this.id = options.id || ('tab-' + Math.random().toString(36).substring(2, 9));
    this.title = options.title || 'Terminal';
    this.shellId = options.shellId || 'default';
    this.shellName = options.shellName || 'Shell';
    this.app = options.app;
    this.isActive = false;
    this.hasUnread = false;
    this.pid = null;

    // Line editor state for HTTP mode
    this.currentLine = '';
    this.history = [];
    this.historyIndex = -1;
    this.isExecuting = false;
    this.cwd = this.app.config ? this.app.config.defaultCwd : '';
    this.lineEditorInitialized = false;

    this.initDOM();
    this.initXterm();
    this.setupResizeObserver();
  }

  initDOM() {
    this.tabElement = document.createElement('div');
    this.tabElement.className = 'term-tab';
    this.tabElement.id = `tab-btn-${this.id}`;
    this.tabElement.innerHTML = `
      <span class="tab-indicator"></span>
      <span class="tab-icon">❯_</span>
      <span class="tab-title" title="${this.title}">${this.title}</span>
      <span class="tab-badge" style="display:none">●</span>
      <button class="tab-close-btn" title="Close Session">×</button>
    `;

    this.tabElement.addEventListener('click', (e) => {
      if (e.target.classList.contains('tab-close-btn')) {
        e.stopPropagation();
        this.app.closeTab(this.id);
      } else {
        this.app.setActiveTab(this.id);
      }
    });

    this.tabElement.querySelector('.tab-title').addEventListener('dblclick', (e) => {
      e.stopPropagation();
      const currentTitle = this.title;
      const newTitle = prompt('Rename tab:', currentTitle);
      if (newTitle && newTitle.trim()) {
        this.setTitle(newTitle.trim());
      }
    });

    this.paneElement = document.createElement('div');
    this.paneElement.className = 'term-pane';
    this.paneElement.id = `pane-${this.id}`;

    this.terminalContainer = document.createElement('div');
    this.terminalContainer.className = 'xterm-mount';
    this.paneElement.appendChild(this.terminalContainer);
  }

  initXterm() {
    const settings = this.app.getSettings();
    const theme = window.TerminalThemes[settings.theme] || window.TerminalThemes.tokyoNight;

    this.term = new Terminal({
      cursorBlink: settings.cursorBlink !== false,
      cursorStyle: settings.cursorStyle || 'block',
      fontSize: settings.fontSize || 14,
      fontFamily: settings.fontFamily || "'JetBrains Mono', monospace",
      theme: theme,
      scrollback: 5000,
      allowTransparency: true,
      smoothScrollDuration: 100,
      windowsMode: navigator.platform.indexOf('Win') !== -1,
      drawBoldTextInBrightColors: true
    });

    this.fitAddon = new FitAddon.FitAddon();
    this.term.loadAddon(this.fitAddon);

    if (window.WebLinksAddon && window.WebLinksAddon.WebLinksAddon) {
      this.term.loadAddon(new window.WebLinksAddon.WebLinksAddon());
    }

    if (window.SearchAddon && window.SearchAddon.SearchAddon) {
      this.searchAddon = new window.SearchAddon.SearchAddon();
      this.term.loadAddon(this.searchAddon);
    }

    try {
      if (window.CanvasAddon && window.CanvasAddon.CanvasAddon) {
        this.term.loadAddon(new window.CanvasAddon.CanvasAddon());
      }
    } catch (e) {
      console.warn('Canvas addon initialization skipped:', e);
    }

    this.term.open(this.terminalContainer);

    // Handle user keystrokes
    this.term.onData((data) => {
      if (this.app.connectionMode === 'ws') {
        this.app.sendWebSocket({
          type: 'data',
          sessionId: this.id,
          data: data
        });
      } else {
        this.handleLineInput(data);
      }
    });

    this.term.onSelectionChange(() => {
      if (this.app.getSettings().copyOnSelect && this.term.hasSelection()) {
        const text = this.term.getSelection();
        if (text) {
          navigator.clipboard.writeText(text);
          this.app.showToast('Copied to clipboard', 'info', 1000);
        }
      }
    });
  }

  async initLineEditor() {
    if (this.lineEditorInitialized) return;
    this.lineEditorInitialized = true;
    this.currentLine = '';
    this.history = [];
    this.historyIndex = -1;
    this.isExecuting = false;
    this.cwd = this.app.config ? this.app.config.defaultCwd : '';

    try {
      const res = await fetch(`/api/banner?shellId=${encodeURIComponent(this.shellId || '')}`, {
        headers: { 'Authorization': `Bearer ${this.app.getToken()}` }
      });
      const data = await res.json();
      if (data && data.banner) {
        this.term.write(data.banner.replace(/(?<!\r)\n/g, '\r\n'));
      }
    } catch (e) {}

    this.showPrompt();
  }

  showPrompt() {
    const p = this.cwd ? this.cwd : 'C:\\';
    if (this.shellId === 'gitbash' || this.shellName.includes('Bash')) {
      const gitPath = p.replace(/\\/g, '/');
      this.term.write(`\r\n\x1b[1;32muser@webterm\x1b[0m \x1b[1;35mMINGW64\x1b[0m \x1b[1;33m${gitPath}\x1b[0m\r\n$ `);
    } else if (this.shellId === 'cmd' || this.shellName.includes('Command')) {
      this.term.write(`\r\n${p}> `);
    } else {
      this.term.write(`\r\n\x1b[1;37mPS \x1b[0m\x1b[1;36m${p}\x1b[0m> `);
    }
  }

  handleLineInput(data) {
    if (this.isExecuting) return;

    if (data === '\t' || data.charCodeAt(0) === 9) { // Tab autocomplete
      this.handleTabCompletion();
      return;
    }

    this.resetCompletion();

    if (data === '\r' || data === '\n') {
      this.executeLine();
      return;
    }

    if (data === '\x03') { // Ctrl+C
      this.term.write('^C');
      this.currentLine = '';
      this.showPrompt();
      return;
    }

    if (data === '\x0c') { // Ctrl+L
      this.term.clear();
      this.showPrompt();
      this.term.write(this.currentLine);
      return;
    }

    if (data === '\x7f' || data === '\b') { // Backspace
      if (this.currentLine.length > 0) {
        this.currentLine = this.currentLine.slice(0, -1);
        this.term.write('\b \b');
      }
      return;
    }

    if (data === '\x1b[A') { // Up arrow
      if (this.history.length === 0) return;
      if (this.historyIndex === -1) {
        this.historyIndex = this.history.length - 1;
      } else if (this.historyIndex > 0) {
        this.historyIndex--;
      }
      this.replaceCurrentLine(this.history[this.historyIndex]);
      return;
    }

    if (data === '\x1b[B') { // Down arrow
      if (this.historyIndex === -1) return;
      if (this.historyIndex < this.history.length - 1) {
        this.historyIndex++;
        this.replaceCurrentLine(this.history[this.historyIndex]);
      } else {
        this.historyIndex = -1;
        this.replaceCurrentLine('');
      }
      return;
    }

    // Regular typing
    if (data.length === 1 && data.charCodeAt(0) >= 32) {
      this.currentLine += data;
      this.term.write(data);
    } else if (data.length > 1 && !data.startsWith('\x1b')) {
      this.currentLine += data;
      this.term.write(data);
    }
  }

  redrawCurrentLine() {
    this.term.write('\r\x1b[K');
    const p = this.cwd ? this.cwd : 'C:\\';
    if (this.shellId === 'gitbash' || this.shellName.includes('Bash')) {
      const gitPath = p.replace(/\\/g, '/');
      this.term.write(`\x1b[1;32muser@webterm\x1b[0m \x1b[1;35mMINGW64\x1b[0m \x1b[1;33m${gitPath}\x1b[0m\r\n$ `);
    } else if (this.shellId === 'cmd' || this.shellName.includes('Command')) {
      this.term.write(`${p}> `);
    } else {
      this.term.write(`\x1b[1;37mPS \x1b[0m\x1b[1;36m${p}\x1b[0m> `);
    }
    this.term.write(this.currentLine);
  }

  replaceCurrentLine(newText) {
    this.currentLine = newText;
    this.redrawCurrentLine();
  }

  async handleTabCompletion() {
    if (!this.isCompleting) {
      this.originalCommand = this.currentLine;
      const parts = this.currentLine.split(' ');
      const lastPart = parts[parts.length - 1];
      this.completionSuggestions = await this.getCompletions(lastPart);
      this.completionIndex = -1;
      this.isCompleting = true;
    }

    if (!this.completionSuggestions || this.completionSuggestions.length === 0) {
      this.resetCompletion();
      return;
    }

    const parts = this.originalCommand.split(' ');

    if (this.completionSuggestions.length === 1) {
      parts[parts.length - 1] = this.completionSuggestions[0];
      this.replaceCurrentLine(parts.join(' '));
      this.resetCompletion();
    } else {
      this.completionIndex = (this.completionIndex + 1) % this.completionSuggestions.length;
      parts[parts.length - 1] = this.completionSuggestions[this.completionIndex];
      this.replaceCurrentLine(parts.join(' '));
      this.term.write(`\x1b[90m (${this.completionIndex + 1}/${this.completionSuggestions.length})\x1b[0m`);
    }
  }

  async getCompletions(prefix) {
    try {
      const res = await fetch(`/api/autocomplete?prefix=${encodeURIComponent(prefix)}&cwd=${encodeURIComponent(this.cwd || '')}`, {
        headers: { 'Authorization': `Bearer ${this.app.getToken()}` }
      });
      return await res.json();
    } catch (e) {
      return [];
    }
  }

  resetCompletion() {
    this.isCompleting = false;
    this.completionSuggestions = [];
    this.completionIndex = -1;
    this.originalCommand = '';
  }

  async executeLine() {
    const cmd = this.currentLine.trim();
    this.term.write('\r\n');

    if (!cmd) {
      this.showPrompt();
      return;
    }

    this.history.push(cmd);
    this.historyIndex = -1;
    this.currentLine = '';
    this.isExecuting = true;

    try {
      const response = await fetch('/api/execute', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${this.app.getToken()}`
        },
        body: JSON.stringify({ cmd: cmd, cwd: this.cwd })
      });

      const contentType = response.headers.get('content-type') || '';
      if (contentType.includes('application/json')) {
        const json = await response.json();
        if (json.clear) {
          this.term.clear();
        } else if (json.output) {
          this.term.write(json.output);
        }
        if (json.cwd) {
          this.cwd = json.cwd;
        }
      } else {
        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        while (true) {
          const { done, value } = await reader.read();
          if (done) break;
          const text = decoder.decode(value, { stream: true });
          this.term.write(text);
        }
      }
    } catch (err) {
      this.term.write(`\x1b[31mExecution error: ${err.message}\x1b[0m\r\n`);
    } finally {
      this.isExecuting = false;
      this.showPrompt();
    }
  }

  setupResizeObserver() {
    this.resizeObserver = new ResizeObserver(() => {
      if (this.isActive && this.paneElement.offsetParent !== null) {
        this.fit();
      }
    });
    this.resizeObserver.observe(this.paneElement);
  }

  fit() {
    try {
      if (!this.fitAddon || !this.paneElement.offsetParent) return;
      this.fitAddon.fit();
      const dims = this.fitAddon.proposeDimensions();
      if (dims && dims.cols && dims.rows) {
        if (this.app.connectionMode === 'ws') {
          this.app.sendWebSocket({
            type: 'resize',
            sessionId: this.id,
            cols: dims.cols,
            rows: dims.rows
          });
        }
        this.app.updateDimensionsDisplay(dims.cols, dims.rows);
      }
    } catch (e) {}
  }

  write(data) {
    if (this.term) {
      this.term.write(data);
    }
    if (!this.isActive) {
      this.hasUnread = true;
      const badge = this.tabElement.querySelector('.tab-badge');
      if (badge) badge.style.display = 'inline-block';
    }
  }

  clear() {
    if (this.term) {
      this.term.clear();
      if (this.app.connectionMode !== 'ws') {
        this.showPrompt();
      }
    }
  }

  focus() {
    if (this.term) {
      this.term.focus();
    }
  }

  setTitle(title) {
    this.title = title;
    const titleEl = this.tabElement.querySelector('.tab-title');
    if (titleEl) {
      titleEl.textContent = title;
      titleEl.title = title;
    }
  }

  setShellInfo(shellName, pid) {
    this.shellName = shellName;
    this.pid = pid;
    this.tabElement.querySelector('.tab-icon').textContent = shellName.includes('Bash') ? 'bash$' : 'ps>';
  }

  setActive(active) {
    this.isActive = active;
    if (active) {
      this.tabElement.classList.add('active');
      this.paneElement.classList.add('active');
      this.hasUnread = false;
      const badge = this.tabElement.querySelector('.tab-badge');
      if (badge) badge.style.display = 'none';

      setTimeout(() => {
        this.fit();
        this.focus();
      }, 50);
    } else {
      this.tabElement.classList.remove('active');
      this.paneElement.classList.remove('active');
    }
  }

  applySettings(settings) {
    if (!this.term) return;
    const theme = window.TerminalThemes[settings.theme] || window.TerminalThemes.tokyoNight;

    this.term.options.theme = theme;
    this.term.options.fontSize = settings.fontSize || 14;
    this.term.options.fontFamily = settings.fontFamily || "'JetBrains Mono', monospace";
    this.term.options.cursorBlink = settings.cursorBlink !== false;
    this.term.options.cursorStyle = settings.cursorStyle || 'block';

    this.fit();
  }

  search(query, options = {}) {
    if (!this.searchAddon) return false;
    if (!query) return false;
    if (options.backward) {
      return this.searchAddon.findPrevious(query, options);
    }
    return this.searchAddon.findNext(query, options);
  }

  destroy() {
    if (this.resizeObserver) {
      this.resizeObserver.disconnect();
    }
    if (this.term) {
      this.term.dispose();
    }
    if (this.tabElement && this.tabElement.parentNode) {
      this.tabElement.parentNode.removeChild(this.tabElement);
    }
    if (this.paneElement && this.paneElement.parentNode) {
      this.paneElement.parentNode.removeChild(this.paneElement);
    }
  }
}

window.TerminalTab = TerminalTab;

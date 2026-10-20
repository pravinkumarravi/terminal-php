class TerminalApp {
  constructor() {
    this.tabs = new Map();
    this.activeTabId = null;
    this.ws = null;
    this.token = localStorage.getItem('term_token') || '';
    this.config = null;
    this.settings = this.loadSettings();
    this.splitMode = 'none';
    this.connectionMode = 'ws'; // 'ws' or 'sse'
    this.sseSources = new Map();

    this.initDOMReferences();
    this.init();
  }

  loadSettings() {
    const defaults = {
      theme: 'tokyoNight',
      fontSize: 14,
      fontFamily: "'JetBrains Mono', monospace",
      cursorBlink: true,
      cursorStyle: 'block',
      copyOnSelect: false,
      wsPort: 8080
    };
    try {
      const saved = localStorage.getItem('term_settings');
      return saved ? Object.assign({}, defaults, JSON.parse(saved)) : defaults;
    } catch (e) {
      return defaults;
    }
  }

  saveSettings() {
    localStorage.setItem('term_settings', JSON.stringify(this.settings));
    this.applySettingsToAllTabs();
  }

  getSettings() {
    return this.settings;
  }

  getToken() {
    return this.token;
  }

  initDOMReferences() {
    this.tabsContainer = document.getElementById('tabs-list');
    this.panesContainer = document.getElementById('panes-container');
    this.statusConn = document.getElementById('status-connection');
    this.statusShell = document.getElementById('status-shell');
    this.statusDims = document.getElementById('status-dims');
    this.statusMem = document.getElementById('status-mem');
    this.authModal = document.getElementById('auth-modal');
    this.settingsModal = document.getElementById('settings-modal');
    this.searchBar = document.getElementById('search-bar');
    this.searchInput = document.getElementById('search-input');
    this.toastContainer = document.getElementById('toast-container');
  }

  async init() {
    try {
      const res = await fetch('/api/config');
      this.config = await res.json();
      this.populateShellOptions();
      this.updateSystemMemoryDisplay();
      setInterval(() => this.updateSystemMemoryDisplay(), 10000);
    } catch (err) {
      console.error('Failed to load server config:', err);
    }

    if (this.config && this.config.authRequired) {
      const authRes = await fetch(`/api/auth/status?token=${encodeURIComponent(this.token)}`);
      const authData = await authRes.json();
      if (!authData.isAuthenticated) {
        this.showAuthModal();
        return;
      }
    }

    this.fileBrowser = new FileBrowser(this);
    this.setupEventListeners();
    this.connect();
  }

  showAuthModal() {
    this.authModal.classList.add('open');
    const pwdInput = document.getElementById('auth-password-input');
    const loginBtn = document.getElementById('auth-submit-btn');
    const errorEl = document.getElementById('auth-error-msg');

    const handleLogin = async () => {
      errorEl.textContent = '';
      const password = pwdInput.value;
      try {
        const res = await fetch('/api/auth/login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ password })
        });
        const data = await res.json();
        if (data.success) {
          this.token = data.token;
          localStorage.setItem('term_token', this.token);
          this.authModal.classList.remove('open');
          this.init();
        } else {
          errorEl.textContent = data.error || 'Invalid password';
        }
      } catch (e) {
        errorEl.textContent = 'Connection error: ' + e.message;
      }
    };

    loginBtn.onclick = handleLogin;
    pwdInput.onkeydown = (e) => {
      if (e.key === 'Enter') handleLogin();
    };
    pwdInput.focus();
  }

  populateShellOptions() {
    const shellSelect = document.getElementById('new-tab-shell-select');
    if (!shellSelect || !this.config || !this.config.availableShells) return;

    shellSelect.innerHTML = '';
    this.config.availableShells.forEach(shell => {
      const opt = document.createElement('option');
      opt.value = shell.id;
      opt.textContent = shell.name + (shell.default ? ' (Default)' : '');
      if (shell.default) opt.selected = true;
      shellSelect.appendChild(opt);
    });
  }

  connect() {
    this.updateConnectionStatus('connecting');

    // Connect WebSocket directly to current host & port (e.g. ws://localhost:8000/ws)
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    const currentHost = window.location.host; // includes hostname and current port
    const wsUrl = `${protocol}//${currentHost}/ws?token=${encodeURIComponent(this.token || '')}`;

    let socketOpened = false;

    try {
      this.ws = new WebSocket(wsUrl);

      this.ws.onopen = () => {
        socketOpened = true;
        this.connectionMode = 'ws';
        this.updateConnectionStatus('connected', 'WebSocket');
        this.startHeartbeat();

        if (this.tabs.size === 0) {
          const defaultShellId = this.config ? this.config.defaultShell.id : 'default';
          this.createNewTab(defaultShellId);
        } else {
          for (const [id, tab] of this.tabs.entries()) {
            this.sendWebSocket({
              type: 'attach',
              sessionId: id,
              shellId: tab.shellId
            });
          }
        }
      };

      this.ws.onmessage = (event) => {
        this.handleWebSocketMessage(event.data);
      };

      this.ws.onclose = () => {
        if (!socketOpened) {
          console.log('WebSocket not available on this port. Activating Interactive HTTP mode.');
          this.activateHttpMode();
        } else {
          this.updateConnectionStatus('disconnected');
          this.stopHeartbeat();
          setTimeout(() => this.connect(), 3000);
        }
      };

      this.ws.onerror = () => {
        if (!socketOpened) {
          this.activateHttpMode();
        }
      };
    } catch (e) {
      this.activateHttpMode();
    }
  }

  activateHttpMode() {
    this.connectionMode = 'http';
    this.updateConnectionStatus('connected', 'Interactive HTTP');

    if (this.tabs.size === 0) {
      const defaultShellId = this.config ? this.config.defaultShell.id : 'default';
      this.createNewTab(defaultShellId);
    } else {
      for (const tab of this.tabs.values()) {
        tab.initLineEditor();
      }
    }
  }

  startHeartbeat() {
    this.pingInterval = setInterval(() => {
      if (this.ws && this.ws.readyState === WebSocket.OPEN) {
        this.pingStart = Date.now();
        this.ws.send(JSON.stringify({ type: 'ping' }));
      }
    }, 15000);
  }

  stopHeartbeat() {
    if (this.pingInterval) {
      clearInterval(this.pingInterval);
      this.pingInterval = null;
    }
  }

  sendWebSocket(msg) {
    if (this.connectionMode === 'ws' && this.ws && this.ws.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify(msg));
    } else if (this.connectionMode === 'sse') {
      if (msg.type === 'data') {
        this.sendSseInput(msg.sessionId, msg.data);
      }
    }
  }

  async sendSseInput(sessionId, data) {
    try {
      await fetch('/api/stream?action=write', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${this.token}`
        },
        body: JSON.stringify({ sessionId, data })
      });
    } catch (e) {
      // ignore
    }
  }

  handleWebSocketMessage(raw) {
    try {
      const msg = JSON.parse(raw);
      switch (msg.type) {
        case 'data':
        case 'replay': {
          const tab = this.tabs.get(msg.sessionId);
          if (tab) tab.write(msg.data);
          break;
        }
        case 'created':
        case 'attached': {
          const tab = this.tabs.get(msg.session.id);
          if (tab) {
            tab.setShellInfo(msg.session.shell, msg.session.pid);
            if (this.activeTabId === tab.id) {
              this.updateShellStatus(msg.session.shell, msg.session.pid);
            }
          }
          break;
        }
        case 'exit': {
          const tab = this.tabs.get(msg.sessionId);
          if (tab) {
            tab.write(`\r\n\x1b[33m[Process completed with exit code ${msg.exitCode}]\x1b[0m\r\n`);
          }
          break;
        }
        case 'pong': {
          if (this.pingStart) {
            const latency = Date.now() - this.pingStart;
            const latencyEl = document.getElementById('status-latency');
            if (latencyEl) latencyEl.textContent = `${latency}ms`;
          }
          break;
        }
      }
    } catch (e) {
      console.error('Failed to parse WebSocket message:', e);
    }
  }

  createNewTab(shellId, customTitle) {
    const selectedShell = this.config && this.config.availableShells ?
      this.config.availableShells.find(s => s.id === shellId) : null;
    const shellName = selectedShell ? selectedShell.name : 'Terminal';
    const tabCount = this.tabs.size + 1;
    const title = customTitle || `${shellName} ${tabCount}`;

    const tab = new TerminalTab({
      app: this,
      shellId: shellId || 'default',
      shellName: shellName,
      title: title
    });

    this.tabs.set(tab.id, tab);
    this.tabsContainer.appendChild(tab.tabElement);
    this.panesContainer.appendChild(tab.paneElement);

    this.setActiveTab(tab.id);

    if (this.connectionMode === 'ws') {
      this.sendWebSocket({
        type: 'create',
        sessionId: tab.id,
        shellId: tab.shellId,
        cols: tab.term.cols || 80,
        rows: tab.term.rows || 24,
        title: title
      });
    } else {
      tab.initLineEditor();
    }

    return tab;
  }

  setActiveTab(tabId) {
    if (!this.tabs.has(tabId)) return;
    this.activeTabId = tabId;

    for (const [id, tab] of this.tabs.entries()) {
      tab.setActive(id === tabId);
    }

    const activeTab = this.tabs.get(tabId);
    if (activeTab) {
      this.updateShellStatus(activeTab.shellName, activeTab.pid);
    }
  }

  closeTab(tabId) {
    if (this.tabs.size <= 1) {
      this.showToast('Cannot close the last terminal tab', 'warning');
      return;
    }

    const tab = this.tabs.get(tabId);
    if (!tab) return;

    if (this.connectionMode === 'ws') {
      this.sendWebSocket({ type: 'close', sessionId: tabId });
    } else {
      const sse = this.sseSources.get(tabId);
      if (sse) { sse.close(); this.sseSources.delete(tabId); }
      fetch('/api/stream?action=kill', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sessionId: tabId })
      });
    }

    tab.destroy();
    this.tabs.delete(tabId);

    if (this.activeTabId === tabId) {
      const remainingIds = Array.from(this.tabs.keys());
      this.setActiveTab(remainingIds[remainingIds.length - 1]);
    }
  }

  getActiveTab() {
    return this.tabs.get(this.activeTabId);
  }

  setupEventListeners() {
    const newTabBtn = document.getElementById('new-tab-btn');
    const shellSelect = document.getElementById('new-tab-shell-select');
    if (newTabBtn) {
      newTabBtn.addEventListener('click', () => {
        const shellId = shellSelect ? shellSelect.value : 'default';
        this.createNewTab(shellId);
      });
    }

    const toggleFilesBtn = document.getElementById('toggle-files-btn');
    if (toggleFilesBtn) {
      toggleFilesBtn.addEventListener('click', () => this.fileBrowser.toggle());
    }

    const toggleSearchBtn = document.getElementById('toggle-search-btn');
    if (toggleSearchBtn) {
      toggleSearchBtn.addEventListener('click', () => {
        if (this.searchBar && this.searchBar.classList.contains('open')) {
          this.closeSearchBar();
        } else {
          this.openSearchBar();
        }
      });
    }

    document.querySelectorAll('.split-btn').forEach(btn => {
      btn.addEventListener('click', () => this.setSplitMode(btn.dataset.mode));
    });

    const settingsBtn = document.getElementById('settings-btn');
    const closeSettingsBtn = document.getElementById('settings-close-btn');
    const saveSettingsBtn = document.getElementById('settings-save-btn');

    if (settingsBtn) {
      settingsBtn.addEventListener('click', () => this.openSettingsModal());
    }
    if (closeSettingsBtn) {
      closeSettingsBtn.addEventListener('click', () => this.settingsModal.classList.remove('open'));
    }
    if (saveSettingsBtn) {
      saveSettingsBtn.addEventListener('click', () => this.saveSettingsFromModal());
    }

    document.querySelectorAll('.snippet-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const cmd = btn.dataset.cmd;
        const active = this.getActiveTab();
        if (active && cmd) {
          if (cmd === 'CLEAR') {
            active.clear();
          } else if (cmd === 'SIGINT') {
            this.sendWebSocket({ type: 'data', sessionId: active.id, data: '\x03' });
          } else {
            this.sendWebSocket({ type: 'data', sessionId: active.id, data: cmd + '\r' });
          }
        }
      });
    });

    const fullscreenBtn = document.getElementById('fullscreen-btn');
    if (fullscreenBtn) {
      fullscreenBtn.addEventListener('click', () => this.toggleFullscreen());
    }

    const searchNextBtn = document.getElementById('search-next-btn');
    const searchPrevBtn = document.getElementById('search-prev-btn');
    const searchCloseBtn = document.getElementById('search-close-btn');

    if (searchNextBtn) searchNextBtn.addEventListener('click', () => this.executeSearch(false));
    if (searchPrevBtn) searchPrevBtn.addEventListener('click', () => this.executeSearch(true));
    if (searchCloseBtn) searchCloseBtn.addEventListener('click', () => this.closeSearchBar());
    if (this.searchInput) {
      this.searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') this.executeSearch(e.shiftKey);
        else if (e.key === 'Escape') this.closeSearchBar();
      });
    }

    window.addEventListener('keydown', (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'f') {
        e.preventDefault();
        this.openSearchBar();
      }
      if (e.altKey && e.key.toLowerCase() === 't') {
        e.preventDefault();
        const shellId = shellSelect ? shellSelect.value : 'default';
        this.createNewTab(shellId);
      }
      if (e.altKey && e.key.toLowerCase() === 'w') {
        e.preventDefault();
        if (this.activeTabId) this.closeTab(this.activeTabId);
      }
      if (e.altKey && e.key.toLowerCase() === 'b') {
        e.preventDefault();
        this.fileBrowser.toggle();
      }
    });
  }

  setSplitMode(mode) {
    this.splitMode = mode;
    this.panesContainer.className = 'panes-container ' + (mode !== 'none' ? `split-${mode}` : '');
    document.querySelectorAll('.split-btn').forEach(b => {
      b.classList.toggle('active', b.dataset.mode === mode);
    });

    const tabsArr = Array.from(this.tabs.values());
    if (mode !== 'none') {
      tabsArr.forEach((tab, index) => {
        if (index < 2) {
          tab.paneElement.classList.add('active');
          tab.fit();
        } else {
          tab.paneElement.classList.remove('active');
        }
      });
    } else {
      this.setActiveTab(this.activeTabId);
    }
  }

  openSearchBar() {
    this.searchBar.classList.add('open');
    const toggleSearchBtn = document.getElementById('toggle-search-btn');
    if (toggleSearchBtn) toggleSearchBtn.classList.add('active');
    this.searchInput.focus();
    this.searchInput.select();
  }

  closeSearchBar() {
    this.searchBar.classList.remove('open');
    const toggleSearchBtn = document.getElementById('toggle-search-btn');
    if (toggleSearchBtn) toggleSearchBtn.classList.remove('active');
    const active = this.getActiveTab();
    if (active) active.focus();
  }

  executeSearch(backward) {
    const query = this.searchInput.value;
    const active = this.getActiveTab();
    if (active && query) active.search(query, { backward });
  }

  openSettingsModal() {
    const themeSelect = document.getElementById('setting-theme');
    const fontSelect = document.getElementById('setting-font');
    const fontSizeInput = document.getElementById('setting-font-size');
    const blinkCheckbox = document.getElementById('setting-cursor-blink');
    const cursorStyleSelect = document.getElementById('setting-cursor-style');
    const copySelectCheckbox = document.getElementById('setting-copy-select');

    if (themeSelect) themeSelect.value = this.settings.theme;
    if (fontSelect) fontSelect.value = this.settings.fontFamily;
    if (fontSizeInput) fontSizeInput.value = this.settings.fontSize;
    if (blinkCheckbox) blinkCheckbox.checked = this.settings.cursorBlink;
    if (cursorStyleSelect) cursorStyleSelect.value = this.settings.cursorStyle;
    if (copySelectCheckbox) copySelectCheckbox.checked = this.settings.copyOnSelect;

    this.settingsModal.classList.add('open');
  }

  saveSettingsFromModal() {
    const themeSelect = document.getElementById('setting-theme');
    const fontSelect = document.getElementById('setting-font');
    const fontSizeInput = document.getElementById('setting-font-size');
    const blinkCheckbox = document.getElementById('setting-cursor-blink');
    const cursorStyleSelect = document.getElementById('setting-cursor-style');
    const copySelectCheckbox = document.getElementById('setting-copy-select');

    this.settings = {
      theme: themeSelect ? themeSelect.value : 'tokyoNight',
      fontFamily: fontSelect ? fontSelect.value : "'JetBrains Mono', monospace",
      fontSize: fontSizeInput ? parseInt(fontSizeInput.value, 10) : 14,
      cursorBlink: blinkCheckbox ? blinkCheckbox.checked : true,
      cursorStyle: cursorStyleSelect ? cursorStyleSelect.value : 'block',
      copyOnSelect: copySelectCheckbox ? copySelectCheckbox.checked : false,
      wsPort: 8080
    };

    this.saveSettings();
    this.settingsModal.classList.remove('open');
    this.showToast('Settings saved successfully', 'success');
  }

  applySettingsToAllTabs() {
    for (const tab of this.tabs.values()) {
      tab.applySettings(this.settings);
    }
  }

  toggleFullscreen() {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen().catch(err => {
        this.showToast('Fullscreen error: ' + err.message, 'error');
      });
    } else {
      if (document.exitFullscreen) document.exitFullscreen();
    }
  }

  updateConnectionStatus(status, label = '') {
    if (!this.statusConn) return;
    const text = this.statusConn.querySelector('.status-text');
    this.statusConn.className = 'status-item status-' + status;
    if (status === 'connected') {
      text.textContent = label ? `Connected (${label})` : 'Connected';
    } else if (status === 'connecting') {
      text.textContent = 'Connecting...';
    } else {
      text.textContent = 'Disconnected';
    }
  }

  updateShellStatus(shellName, pid) {
    if (this.statusShell) {
      this.statusShell.textContent = `${shellName}${pid ? ` (PID: ${pid})` : ''}`;
    }
  }

  updateDimensionsDisplay(cols, rows) {
    if (this.statusDims) {
      this.statusDims.textContent = `${cols} × ${rows}`;
    }
  }

  async updateSystemMemoryDisplay() {
    if (!this.statusMem) return;
    try {
      const res = await fetch('/api/config');
      const data = await res.json();
      if (data.system && data.system.memory) {
        this.statusMem.textContent = `RAM: ${data.system.memory.free} free / ${data.system.memory.total}`;
      }
    } catch (e) { }
  }

  showToast(message, type = 'info', duration = 3000) {
    if (!this.toastContainer) return;
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;

    this.toastContainer.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => {
        if (toast.parentNode) toast.parentNode.removeChild(toast);
      }, 300);
    }, duration);
  }
}

window.addEventListener('DOMContentLoaded', () => {
  window.app = new TerminalApp();
});

class FileBrowser {
  constructor(app) {
    this.app = app;
    this.currentPath = '';
    this.drawerEl = document.getElementById('file-drawer');
    this.fileListEl = document.getElementById('file-list');
    this.pathDisplayEl = document.getElementById('file-current-path');
    this.fileEditorModal = document.getElementById('editor-modal');
    this.editorTextarea = document.getElementById('editor-textarea');
    this.editorPathTitle = document.getElementById('editor-file-path');
    this.currentEditingPath = null;

    this.initEvents();
  }

  initEvents() {
    // Refresh button
    const refreshBtn = document.getElementById('file-refresh-btn');
    if (refreshBtn) {
      refreshBtn.addEventListener('click', () => this.loadDirectory(this.currentPath));
    }

    // Close drawer button
    const closeBtn = document.getElementById('file-drawer-close-btn');
    if (closeBtn) {
      closeBtn.addEventListener('click', () => this.toggle(false));
    }

    // Upload button
    const uploadInput = document.getElementById('file-upload-input');
    const uploadBtn = document.getElementById('file-upload-btn');
    if (uploadBtn && uploadInput) {
      uploadBtn.addEventListener('click', () => uploadInput.click());
      uploadInput.addEventListener('change', (e) => this.handleUpload(e.target.files));
    }

    // Save editor button
    const saveBtn = document.getElementById('editor-save-btn');
    if (saveBtn) {
      saveBtn.addEventListener('click', () => this.saveCurrentFile());
    }

    // Close editor button
    const closeEditorBtn = document.getElementById('editor-close-btn');
    if (closeEditorBtn) {
      closeEditorBtn.addEventListener('click', () => this.closeEditor());
    }

    // Drag and drop onto document / terminal to upload
    window.addEventListener('dragover', (e) => {
      e.preventDefault();
      e.stopPropagation();
      document.body.classList.add('drag-active');
    });

    window.addEventListener('dragleave', (e) => {
      e.preventDefault();
      if (e.relatedTarget === null) {
        document.body.classList.remove('drag-active');
      }
    });

    window.addEventListener('drop', (e) => {
      e.preventDefault();
      e.stopPropagation();
      document.body.classList.remove('drag-active');
      if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        this.handleUpload(e.dataTransfer.files);
      }
    });
  }

  toggle(forceState) {
    if (forceState !== undefined) {
      this.drawerEl.classList.toggle('open', forceState);
    } else {
      this.drawerEl.classList.toggle('open');
    }

    const toggleFilesBtn = document.getElementById('toggle-files-btn');
    if (toggleFilesBtn) {
      toggleFilesBtn.classList.toggle('active', this.drawerEl.classList.contains('open'));
    }

    if (this.drawerEl.classList.contains('open') && !this.currentPath) {
      this.loadDirectory('');
    }
  }

  async loadDirectory(targetPath) {
    this.fileListEl.innerHTML = '<div class="file-loading"><span class="spinner"></span> Loading files...</div>';
    try {
      const token = this.app.getToken();
      const url = `/api/files/list?path=${encodeURIComponent(targetPath || '')}&token=${encodeURIComponent(token || '')}`;
      const res = await fetch(url);
      if (!res.ok) {
        throw new Error(`Failed to load directory (${res.status})`);
      }
      const data = await res.json();
      this.currentPath = data.currentPath;
      this.pathDisplayEl.textContent = data.currentPath;
      this.renderList(data.items);
    } catch (err) {
      this.fileListEl.innerHTML = `<div class="file-error">Error: ${err.message}</div>`;
      this.app.showToast(err.message, 'error');
    }
  }

  renderList(items) {
    if (!items || items.length === 0) {
      this.fileListEl.innerHTML = '<div class="file-empty">Folder is empty</div>';
      return;
    }

    this.fileListEl.innerHTML = '';
    items.forEach(item => {
      const row = document.createElement('div');
      row.className = 'file-item';

      const defaultIcon = item.isDirectory 
        ? '<svg class="folder-svg" width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M14.5 3H7.71l-.85-.85L6.5 2h-5l-.5.5v11l.5.5h13l.5-.5v-10l-.5-.5zm-.5 10H2V3h4.29l.86.85.35.15H14v9z"/></svg>' 
        : '<i class="icon default-icon"></i>';

      row.innerHTML = `
        <div class="file-item-left">
          <span class="file-icon">${defaultIcon}</span>
          <span class="file-name" title="${item.name}">${item.name}</span>
        </div>
        <div class="file-item-right">
          <span class="file-size">${item.size || ''}</span>
          <div class="file-actions">
            ${!item.isDirectory ? `
              <button class="file-action-btn edit-btn" title="View/Edit">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                  <path d="M13.23 1h-1.46L3.52 9.25l-.16.22L1 13.59 2.41 15l4.12-2.36.22-.16L15 4.23V2.77L13.23 1zM2.41 13.59l1.51-3 1.49 1.49-3 1.51zm3.7-2.21L4.62 9.89l7.15-7.15 1.49 1.49-7.15 7.15z"/>
                </svg>
              </button>
              <button class="file-action-btn dl-btn" title="Download">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                  <path fill-rule="evenodd" clip-rule="evenodd" d="M8.5 1.5v7.79l2.15-2.14.71.71L7.5 11.71 3.65 7.86l.71-.71 2.14 2.14V1.5h2zm-6 11h11v1h-11v-1z"/>
                </svg>
              </button>
            ` : ''}
            ${!item.isParent ? `
              <button class="file-action-btn del-btn" title="Delete">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                  <path fill-rule="evenodd" clip-rule="evenodd" d="M11 3h3v1h-1v9.5l-.5.5h-9l-.5-.5V4H2V3h3V1.5l.5-.5h5l.5.5V3zm-7 1v9h8V4H4zm2 2h1v6H6V6zm3 0h1v6H9V6zm1-4.5H6V3h4V1.5z"/>
                </svg>
              </button>
            ` : ''}
          </div>
        </div>
      `;

      // Apply File-Icons Theme (https://github.com/file-icons/vscode)
      if (window.FileIcons) {
        const query = item.isDirectory ? (item.name + '/') : item.name;
        window.FileIcons.getClass(query).then(cls => {
          if (cls && (!item.isDirectory || cls !== 'icon default-icon')) {
            const iconSpan = row.querySelector('.file-icon');
            if (iconSpan) {
              iconSpan.innerHTML = `<i class="${cls}"></i>`;
            }
          }
        }).catch(() => {});
      }

      // Click to enter directory or preview file
      row.querySelector('.file-item-left').addEventListener('click', () => {
        if (item.isDirectory) {
          this.loadDirectory(item.path);
        } else {
          this.openEditor(item.path);
        }
      });

      // Actions
      if (!item.isDirectory) {
        row.querySelector('.edit-btn').addEventListener('click', (e) => {
          e.stopPropagation();
          this.openEditor(item.path);
        });
        row.querySelector('.dl-btn').addEventListener('click', (e) => {
          e.stopPropagation();
          this.downloadFile(item.path);
        });
      }

      if (!item.isParent) {
        row.querySelector('.del-btn').addEventListener('click', (e) => {
          e.stopPropagation();
          this.deleteItem(item.path, item.name, item.isDirectory);
        });
      }

      this.fileListEl.appendChild(row);
    });
  }



  downloadFile(filePath) {
    const token = this.app.getToken();
    const url = `/api/files/download?path=${encodeURIComponent(filePath)}&token=${encodeURIComponent(token || '')}`;
    const link = document.createElement('a');
    link.href = url;
    link.download = '';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  async openEditor(filePath) {
    try {
      const token = this.app.getToken();
      const url = `/api/files/read?path=${encodeURIComponent(filePath)}&token=${encodeURIComponent(token || '')}`;
      const res = await fetch(url);
      if (!res.ok) {
        const err = await res.json();
        throw new Error(err.error || 'Cannot open file');
      }
      const data = await res.json();
      this.currentEditingPath = data.path;
      this.editorPathTitle.textContent = data.path;
      this.editorTextarea.value = data.content;
      this.fileEditorModal.classList.add('open');
    } catch (err) {
      this.app.showToast(err.message, 'error');
    }
  }

  async saveCurrentFile() {
    if (!this.currentEditingPath) return;
    try {
      const token = this.app.getToken();
      const content = this.editorTextarea.value;
      const res = await fetch('/api/files/save', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({ filePath: this.currentEditingPath, content })
      });
      const data = await res.json();
      if (data.success) {
        this.app.showToast('File saved successfully!', 'success');
      } else {
        throw new Error(data.error || 'Failed to save');
      }
    } catch (err) {
      this.app.showToast(err.message, 'error');
    }
  }

  closeEditor() {
    this.fileEditorModal.classList.remove('open');
    this.currentEditingPath = null;
  }

  async handleUpload(files) {
    if (!files || files.length === 0) return;
    const token = this.app.getToken();
    const formData = new FormData();
    formData.append('destination', this.currentPath || '');

    for (let i = 0; i < files.length; i++) {
      formData.append('files', files[i]);
    }

    this.app.showToast(`Uploading ${files.length} file(s)...`, 'info');

    try {
      const res = await fetch('/api/files/upload', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`
        },
        body: formData
      });
      const data = await res.json();
      if (data.success) {
        this.app.showToast(`Uploaded ${data.filesUploaded.length} file(s)!`, 'success');
        this.loadDirectory(this.currentPath);
      } else {
        throw new Error(data.error || 'Upload failed');
      }
    } catch (err) {
      this.app.showToast(err.message, 'error');
    }
  }

  async deleteItem(targetPath, name, isDir) {
    const confirmMsg = `Are you sure you want to delete ${isDir ? 'directory' : 'file'} "${name}"?`;
    if (!confirm(confirmMsg)) return;

    try {
      const token = this.app.getToken();
      const res = await fetch('/api/files/delete', {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({ path: targetPath })
      });
      const data = await res.json();
      if (data.success) {
        this.app.showToast(`Deleted ${name}`, 'success');
        this.loadDirectory(this.currentPath);
      } else {
        throw new Error(data.error || 'Delete failed');
      }
    } catch (err) {
      this.app.showToast(err.message, 'error');
    }
  }
}

window.FileBrowser = FileBrowser;

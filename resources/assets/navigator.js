/*!
 * Storage Navigator – browser script (no build step, no dependencies).
 *
 * The page works without it: links open folders and download files. This script adds
 * keyboard navigation, live search, the preview, layout and colour-scheme switches,
 * uploads and the write dialogs. It never builds HTML from strings: every file name
 * reaches the page through textContent or attributes.
 */
(function () {
  'use strict';

  const configElement = document.getElementById('sn-config');
  if (!configElement) {
    return;
  }

  const config = JSON.parse(configElement.textContent || '{}');
  const messages = config.messages || {};
  const root = document.documentElement;
  const isConsole = config.layout === 'console';
  document.addEventListener('pointerdown', function () { document.body.classList.remove('sn-kb'); });

  /** Translated message with {placeholders} replaced. */
  function t(key, params) {
    let message = Object.prototype.hasOwnProperty.call(messages, key) ? messages[key] : key;
    if (params) {
      Object.keys(params).forEach(function (name) {
        message = message.split('{' + name + '}').join(String(params[name]));
      });
    }
    return message;
  }

  function setCookie(name, value) {
    const secure = location.protocol === 'https:' ? '; Secure' : '';
    if (value === null) {
      document.cookie = name + '=; Path=/; Max-Age=0; SameSite=Lax' + secure;
    } else {
      document.cookie = name + '=' + encodeURIComponent(value) + '; Path=/; Max-Age=31536000; SameSite=Lax' + secure;
    }
  }

  function formatBytes(bytes) {
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let value = bytes;
    let unit = 0;
    while (value >= 1024 && unit < units.length - 1) {
      value /= 1024;
      unit++;
    }
    const decimals = unit === 0 || value >= 10 ? 0 : 1;
    const separator = messages['number.decimal'] || '.';
    return value.toFixed(decimals).replace('.', separator) + ' ' + units[unit];
  }

  function element(tag, className, text) {
    const node = document.createElement(tag);
    if (className) {
      node.className = className;
    }
    if (text !== undefined) {
      node.textContent = text;
    }
    return node;
  }

  /* ------------------------------------------------------------------ toasts */

  const toastRegion = document.querySelector('[data-sn-toasts]');

  function toast(message, options) {
    if (!toastRegion) {
      return null;
    }
    const opts = options || {};
    const node = element('div', 'sn-toast' + (opts.error ? ' sn-toast--error' : ''));
    const text = element('span', 'sn-toast__text', message);
    node.appendChild(text);
    let bar = null;
    if (opts.progress) {
      const track = element('div', 'sn-toast__progress');
      bar = element('span');
      track.appendChild(bar);
      node.appendChild(track);
    }
    if (opts.error) {
      node.setAttribute('role', 'alert');
    }
    toastRegion.appendChild(node);
    let timer = null;
    const handle = {
      update: function (newMessage, fraction) {
        text.textContent = newMessage;
        if (bar && typeof fraction === 'number') {
          bar.style.width = Math.round(fraction * 100) + '%';
        }
      },
      close: function () {
        clearTimeout(timer);
        node.remove();
      },
    };
    if (!opts.progress) {
      timer = setTimeout(handle.close, opts.error ? 8000 : 4000);
    }
    return handle;
  }

  // A toast queued before a reload (after a successful write).
  try {
    const pending = sessionStorage.getItem('sn-toast');
    if (pending) {
      sessionStorage.removeItem('sn-toast');
      toast(pending);
    }
  } catch (e) { /* storage unavailable */ }

  function reloadWithToast(message) {
    try {
      sessionStorage.setItem('sn-toast', message);
    } catch (e) { /* ignore */ }
    location.reload();
  }

  /* ------------------------------------------------------------------ entries */

  const list = document.querySelector('[data-sn-entries]');
  const items = list ? Array.prototype.slice.call(list.querySelectorAll('[data-sn-entry], [data-sn-parent]')) : [];
  const info = document.querySelector('[data-sn-info]');
  let active = null;

  function visibleItems() {
    return items.filter(function (item) { return !item.hidden; });
  }

  function linkOf(item) {
    return item ? item.querySelector('[data-sn-entry-link]') : null;
  }

  function setActive(item, focus) {
    if (!item) {
      return;
    }
    if (active && active !== item) {
      active.classList.remove('is-active');
      const previous = linkOf(active);
      if (previous) {
        previous.setAttribute('tabindex', '-1');
      }
    }
    active = item;
    item.classList.add('is-active');
    const link = linkOf(item);
    if (link) {
      link.setAttribute('tabindex', '0');
      if (focus) {
        link.focus({ preventScroll: true });
        item.scrollIntoView({ block: 'nearest' });
      }
    }
    if (info) {
      updateInfo(item);
    }
  }

  function updateInfo(item) {
    const parts = info.querySelectorAll('[data-sn-info-field]');
    parts.forEach(function (part) {
      const field = part.getAttribute('data-sn-info-field');
      if (item.hasAttribute('data-sn-parent')) {
        part.textContent = field === 'name' ? '..' : '';
        return;
      }
      part.textContent = item.getAttribute('data-' + field) || '';
    });
  }

  if (items.length) {
    items.forEach(function (item) {
      const link = linkOf(item);
      if (link) {
        link.setAttribute('tabindex', '-1');
        link.addEventListener('focus', function () { setActive(item, false); });
      }
    });
    const preferred = items.filter(function (item) { return item.hasAttribute('data-sn-entry'); })[0] || items[0];
    setActive(preferred, false);
  }

  function columnsPerRow() {
    if (!list || list.getAttribute('data-view') !== 'grid') {
      return 1;
    }
    const visible = visibleItems();
    if (visible.length < 2) {
      return 1;
    }
    const top = visible[0].offsetTop;
    let count = 0;
    for (let i = 0; i < visible.length && visible[i].offsetTop === top; i++) {
      count++;
    }
    return Math.max(1, count);
  }

  function move(delta) {
    const visible = visibleItems();
    if (!visible.length) {
      return;
    }
    let index = visible.indexOf(active);
    index = index === -1 ? 0 : Math.min(visible.length - 1, Math.max(0, index + delta));
    setActive(visible[index], true);
  }

  function entryData(item) {
    return {
      name: item.getAttribute('data-name') || '',
      path: item.getAttribute('data-path') || '',
      type: item.getAttribute('data-type'),
      preview: item.getAttribute('data-preview'),
      raw: item.getAttribute('data-raw'),
      download: item.getAttribute('data-download'),
      size: item.getAttribute('data-size') || '',
      modified: item.getAttribute('data-modified') || '',
    };
  }

  function openItem(item, preferPreview) {
    if (!item) {
      return;
    }
    if (item.hasAttribute('data-preview') && (preferPreview || item.getAttribute('data-type') === 'file')) {
      openPreview(item);
      return;
    }
    const link = linkOf(item);
    if (link && link.tagName === 'A') {
      link.click();
    }
  }

  if (list) {
    // A click on a previewable file opens the preview instead of downloading it.
    list.addEventListener('click', function (event) {
      const target = event.target;
      const actionButton = target.closest('[data-sn-action]');
      const item = target.closest('[data-sn-entry]');
      if (actionButton && item) {
        event.preventDefault();
        runEntryAction(actionButton.getAttribute('data-sn-action'), item);
        return;
      }
      const link = target.closest('[data-sn-entry-link]');
      if (link && item && item.hasAttribute('data-preview') && !event.ctrlKey && !event.metaKey && !event.shiftKey && event.button === 0) {
        event.preventDefault();
        setActive(item, false);
        openPreview(item);
      }
    });

    list.querySelectorAll('img.sn-thumb').forEach(watchThumb);
  }

  function watchThumb(img) {
    img.addEventListener('error', function () { img.classList.add('is-broken'); });
  }

  function runEntryAction(action, item) {
    setActive(item, false);
    switch (action) {
      case 'preview':
        openPreview(item);
        break;
      case 'rename':
        openRename(item);
        break;
      case 'delete':
        openDelete(item);
        break;
      case 'more': {
        const expanded = !item.classList.contains('is-expanded');
        items.forEach(function (other) {
          other.classList.remove('is-expanded');
          const toggle = other.querySelector('[data-sn-action="more"]');
          if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
          }
        });
        item.classList.toggle('is-expanded', expanded);
        item.querySelector('[data-sn-action="more"]').setAttribute('aria-expanded', expanded ? 'true' : 'false');
        break;
      }
      case 'download': {
        const url = item.getAttribute('data-download');
        if (url) {
          location.href = url;
        }
        break;
      }
      default:
        break;
    }
  }

  /* ------------------------------------------------------------------ search */

  const search = document.querySelector('[data-sn-search]');
  const noMatches = document.querySelector('[data-sn-no-matches]');

  function applyFilter(query) {
    const needle = query.trim().toLocaleLowerCase();
    let shown = 0;
    items.forEach(function (item) {
      if (item.hasAttribute('data-sn-parent')) {
        item.hidden = needle !== '';
        return;
      }
      const match = needle === '' || (item.getAttribute('data-name') || '').toLocaleLowerCase().indexOf(needle) !== -1;
      item.hidden = !match;
      if (match) {
        shown++;
      }
    });
    if (noMatches) {
      noMatches.hidden = shown > 0 || needle === '' || items.length === 0;
    }
    if (active && active.hidden) {
      const visible = visibleItems();
      if (visible.length) {
        setActive(visible[0], false);
      }
    }
    const url = new URL(location.href);
    if (needle === '') {
      url.searchParams.delete('q');
    } else {
      url.searchParams.set('q', query.trim());
    }
    history.replaceState(null, '', url.toString());
  }

  if (search) {
    const form = search.form;
    // Server-side search already applied: the live filter takes over from here.
    if (search.value !== '' && items.length) {
      applyFilter(search.value);
    }
    search.addEventListener('input', function () { applyFilter(search.value); });
    search.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        if (search.value !== '') {
          search.value = '';
          applyFilter('');
        } else {
          search.blur();
          const link = linkOf(active);
          if (link) {
            link.focus();
          }
        }
        event.stopPropagation();
      } else if (event.key === 'ArrowDown' || event.key === 'Enter') {
        event.preventDefault();
        const visible = visibleItems().filter(function (item) { return !item.hasAttribute('data-sn-parent'); });
        if (visible.length) {
          setActive(visible[0], true);
        }
      }
    });
    if (form) {
      form.addEventListener('submit', function (event) {
        if (items.length) {
          event.preventDefault();
        }
      });
    }
  }

  /* ------------------------------------------------------------------ view and colour scheme */

  document.querySelectorAll('[data-sn-view]').forEach(function (button) {
    button.addEventListener('click', function () { setView(button.getAttribute('data-sn-view')); });
  });

  function setView(view) {
    if (!list) {
      return;
    }
    list.setAttribute('data-view', view);
    document.querySelectorAll('[data-sn-view]').forEach(function (button) {
      button.setAttribute('aria-pressed', button.getAttribute('data-sn-view') === view ? 'true' : 'false');
    });
    const columns = document.querySelector('.sn-columns');
    if (columns) {
      columns.setAttribute('aria-hidden', view === 'grid' ? 'true' : 'false');
    }
    if (view === 'grid') {
      items.forEach(function (item) {
        const raw = item.getAttribute('data-raw');
        const icon = item.querySelector('.sn-entry__icon');
        if (raw && item.getAttribute('data-preview') === 'image' && icon && !icon.querySelector('img')) {
          const img = element('img', 'sn-thumb');
          img.alt = '';
          img.loading = 'lazy';
          img.decoding = 'async';
          img.src = raw;
          watchThumb(img);
          icon.insertBefore(img, icon.firstChild);
        }
      });
    }
    setCookie('sn_view', view === 'grid' ? 'grid' : null);
  }

  const schemeButton = document.querySelector('[data-sn-action="scheme"]');
  if (schemeButton) {
    schemeButton.addEventListener('click', function () {
      const order = ['auto', 'light', 'dark'];
      const current = root.getAttribute('data-scheme') || 'auto';
      const next = order[(order.indexOf(current) + 1) % order.length];
      root.setAttribute('data-scheme', next);
      setCookie('sn_scheme', next === 'auto' ? null : next);
      const label = t('scheme.toggle') + ': ' + t('scheme.' + next);
      schemeButton.setAttribute('title', label);
      schemeButton.setAttribute('aria-label', label);
    });
  }

  /* ------------------------------------------------------------------ dialogs */

  function openDialog(dialog) {
    if (!dialog || dialog.open) {
      return;
    }
    dialog.showModal();
  }

  document.querySelectorAll('dialog.sn-dialog').forEach(function (dialog) {
    dialog.addEventListener('click', function (event) {
      if (event.target.closest('[data-sn-close]')) {
        dialog.close();
        return;
      }
      // A click on the backdrop closes the dialog.
      if (event.target === dialog) {
        const rect = dialog.getBoundingClientRect();
        const inside = event.clientX >= rect.left && event.clientX <= rect.right && event.clientY >= rect.top && event.clientY <= rect.bottom;
        if (!inside) {
          dialog.close();
        }
      }
    });
    dialog.addEventListener('close', function () {
      const link = linkOf(active);
      if (link) {
        link.focus({ preventScroll: true });
      }
    });
  });

  const mkdirDialog = document.getElementById('sn-mkdir');
  const renameDialog = document.getElementById('sn-rename');
  const deleteDialog = document.getElementById('sn-delete');
  const shortcutsDialog = document.getElementById('sn-shortcuts');

  function resetForm(dialog) {
    const error = dialog.querySelector('[data-sn-form-error]');
    if (error) {
      error.hidden = true;
      error.textContent = '';
    }
    const submit = dialog.querySelector('[type="submit"]');
    if (submit) {
      submit.disabled = false;
    }
  }

  function openMkdir() {
    if (!mkdirDialog) {
      return;
    }
    resetForm(mkdirDialog);
    const input = mkdirDialog.querySelector('input[name="name"]');
    input.value = '';
    openDialog(mkdirDialog);
    input.focus();
  }

  function openRename(item) {
    if (!renameDialog || !item.hasAttribute('data-can-rename')) {
      return;
    }
    const data = entryData(item);
    resetForm(renameDialog);
    renameDialog.querySelector('input[name="path"]').value = data.path;
    const input = renameDialog.querySelector('input[name="name"]');
    input.value = data.name;
    openDialog(renameDialog);
    input.focus();
    // Select the name without its extension, like desktop file managers.
    const dot = data.type === 'file' ? data.name.lastIndexOf('.') : -1;
    input.setSelectionRange(0, dot > 0 ? dot : data.name.length);
  }

  function openDelete(item) {
    if (!deleteDialog || !item.hasAttribute('data-can-delete')) {
      return;
    }
    const data = entryData(item);
    resetForm(deleteDialog);
    deleteDialog.querySelector('input[name="path"]').value = data.path;
    deleteDialog.querySelector('[data-sn-delete-title]').textContent = t('dialog.delete.title', { name: data.name });
    deleteDialog.querySelector('[data-sn-delete-text]').textContent = t(data.type === 'directory' ? 'dialog.delete.folder' : 'dialog.delete.file');
    openDialog(deleteDialog);
    deleteDialog.querySelector('[type="submit"]').focus();
  }

  function openShortcuts() {
    openDialog(shortcutsDialog);
  }

  async function postForm(url, formData) {
    let response;
    try {
      response = await fetch(url, {
        method: 'POST',
        body: formData,
        headers: { 'Accept': 'application/json', 'X-CSRF-Token': config.csrf || '' },
        credentials: 'same-origin',
      });
    } catch (e) {
      throw new Error(t('js.network'));
    }
    let payload = null;
    try {
      payload = await response.json();
    } catch (e) {
      payload = null;
    }
    if (!response.ok || !payload || payload.ok !== true) {
      throw new Error(payload && payload.error ? payload.error : t('js.error', { status: response.status }));
    }
    return payload;
  }

  document.querySelectorAll('form[data-sn-form]').forEach(function (form) {
    form.addEventListener('submit', async function (event) {
      event.preventDefault();
      const dialog = form.closest('dialog');
      const error = form.querySelector('[data-sn-form-error]');
      const submit = form.querySelector('[type="submit"]');
      submit.disabled = true;
      try {
        const result = await postForm(form.getAttribute('action'), new FormData(form));
        reloadWithToast(result.message || '');
      } catch (e) {
        submit.disabled = false;
        if (error) {
          error.textContent = e.message;
          error.hidden = false;
        } else {
          toast(e.message, { error: true });
        }
        const input = form.querySelector('input[name="name"]');
        if (input && dialog && dialog.open) {
          input.focus();
        }
      }
    });
  });

  /* ------------------------------------------------------------------ upload */

  const uploadForm = document.querySelector('[data-sn-upload-form]');
  const uploadInput = document.querySelector('[data-sn-upload-input]');
  const dropzone = document.querySelector('[data-sn-dropzone]');

  function chooseFiles() {
    if (uploadInput) {
      uploadInput.value = '';
      uploadInput.click();
    }
  }

  function upload(files) {
    if (!uploadForm || !files.length) {
      return;
    }
    const max = config.maxUploadBytes || 0;
    for (let i = 0; i < files.length; i++) {
      if (max && files[i].size > max) {
        toast(t('upload.too_large', { name: files[i].name, max: formatBytes(max) }), { error: true });
        return;
      }
    }
    const data = new FormData(uploadForm);
    data.delete('files[]');
    for (let i = 0; i < files.length; i++) {
      data.append('files[]', files[i], files[i].name);
    }
    const progressText = function (percent) {
      return files.length === 1
        ? t('upload.progress_one', { name: files[0].name, percent: percent })
        : t('upload.progress_other', { count: files.length, percent: percent });
    };
    const progress = toast(progressText(0), { progress: true });
    const request = new XMLHttpRequest();
    request.open('POST', uploadForm.getAttribute('action'));
    request.setRequestHeader('Accept', 'application/json');
    request.setRequestHeader('X-CSRF-Token', config.csrf || '');
    request.upload.addEventListener('progress', function (event) {
      if (event.lengthComputable && progress) {
        const fraction = event.loaded / event.total;
        progress.update(progressText(Math.round(fraction * 100)), fraction);
      }
    });
    request.addEventListener('load', function () {
      let payload = null;
      try {
        payload = JSON.parse(request.responseText);
      } catch (e) {
        payload = null;
      }
      if (progress) {
        progress.close();
      }
      if (request.status >= 200 && request.status < 300 && payload && payload.ok) {
        reloadWithToast(payload.message || '');
      } else {
        toast(payload && payload.error ? payload.error : t('js.error', { status: request.status }), { error: true });
      }
    });
    request.addEventListener('error', function () {
      if (progress) {
        progress.close();
      }
      toast(t('js.network'), { error: true });
    });
    request.send(data);
  }

  if (uploadInput) {
    uploadInput.addEventListener('change', function () { upload(uploadInput.files); });
  }

  if (dropzone) {
    let depth = 0;
    const hasFiles = function (event) {
      return event.dataTransfer && Array.prototype.indexOf.call(event.dataTransfer.types, 'Files') !== -1;
    };
    window.addEventListener('dragenter', function (event) {
      if (!hasFiles(event)) {
        return;
      }
      event.preventDefault();
      depth++;
      dropzone.hidden = false;
    });
    window.addEventListener('dragover', function (event) {
      if (hasFiles(event)) {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'copy';
      }
    });
    window.addEventListener('dragleave', function (event) {
      if (!hasFiles(event)) {
        return;
      }
      depth = Math.max(0, depth - 1);
      if (depth === 0) {
        dropzone.hidden = true;
      }
    });
    window.addEventListener('drop', function (event) {
      if (!hasFiles(event)) {
        return;
      }
      event.preventDefault();
      depth = 0;
      dropzone.hidden = true;
      upload(event.dataTransfer.files);
    });
  }

  /* ------------------------------------------------------------------ preview */

  const previewDialog = document.getElementById('sn-preview');
  const previewBody = previewDialog ? previewDialog.querySelector('[data-sn-preview-body]') : null;
  let previewItem = null;
  let previewRequest = 0;

  function previewables() {
    return visibleItems().filter(function (item) { return item.hasAttribute('data-preview'); });
  }

  function message(text) {
    return element('p', 'sn-preview__message', text);
  }

  async function openPreview(item) {
    if (!previewDialog || !item || !item.hasAttribute('data-preview')) {
      return;
    }
    previewItem = item;
    setActive(item, false);
    const data = entryData(item);
    const request = ++previewRequest;

    previewDialog.querySelector('.sn-preview__name').textContent = data.name;
    previewDialog.querySelector('.sn-preview__meta').textContent = [data.size, data.modified].filter(Boolean).join(', ');
    const download = previewDialog.querySelector('[data-sn-preview-download]');
    download.hidden = !data.download;
    if (data.download) {
      download.href = data.download;
    }
    previewDialog.querySelector('[data-sn-preview-open]').href = data.raw;
    const siblings = previewables();
    previewDialog.querySelectorAll('[data-sn-preview-nav]').forEach(function (button) {
      button.hidden = siblings.length < 2;
    });

    previewBody.replaceChildren(message(t('preview.loading')));
    openDialog(previewDialog);

    let content;
    switch (data.preview) {
      case 'image': {
        content = element('img');
        content.alt = data.name;
        content.src = data.raw;
        content.addEventListener('error', function () {
          if (request === previewRequest) {
            previewBody.replaceChildren(message(t('preview.failed')));
          }
        });
        break;
      }
      case 'video':
      case 'audio': {
        content = element(data.preview);
        content.controls = true;
        content.preload = 'metadata';
        content.src = data.raw;
        break;
      }
      case 'pdf': {
        content = element('iframe');
        content.title = data.name;
        content.src = data.raw;
        break;
      }
      case 'text': {
        try {
          content = await loadText(data);
        } catch (e) {
          content = message(e.message || t('preview.failed'));
        }
        break;
      }
      default:
        content = message(t('preview.unavailable'));
    }
    if (request === previewRequest) {
      previewBody.replaceChildren(content);
      previewBody.scrollTop = 0;
      if (data.preview !== 'video' && data.preview !== 'audio') {
        previewBody.focus({ preventScroll: true });
      }
    }
  }

  function stepPreview(delta) {
    const siblings = previewables();
    if (siblings.length < 2) {
      return;
    }
    const index = siblings.indexOf(previewItem);
    const next = siblings[(index + delta + siblings.length) % siblings.length];
    openPreview(next);
  }

  if (previewDialog) {
    previewDialog.querySelectorAll('[data-sn-preview-nav]').forEach(function (button) {
      button.addEventListener('click', function () { stepPreview(parseInt(button.getAttribute('data-sn-preview-nav'), 10)); });
    });
    previewDialog.addEventListener('keydown', function (event) {
      const tag = event.target.tagName;
      if (tag === 'VIDEO' || tag === 'AUDIO') {
        return;
      }
      if (event.key === 'ArrowRight' || (event.key === 'ArrowDown' && isConsole)) {
        event.preventDefault();
        stepPreview(1);
      } else if (event.key === 'ArrowLeft' || (event.key === 'ArrowUp' && isConsole)) {
        event.preventDefault();
        stepPreview(-1);
      } else if (event.key === ' ' && tag !== 'BUTTON' && tag !== 'A') {
        event.preventDefault();
        previewDialog.close();
      }
    });
    previewDialog.addEventListener('close', function () {
      previewRequest++;
      // Stop media playback.
      previewBody.replaceChildren();
    });
  }

  async function loadText(data) {
    let response;
    try {
      response = await fetch(data.raw, { credentials: 'same-origin' });
    } catch (e) {
      throw new Error(t('js.network'));
    }
    if (!response.ok) {
      let error = t('preview.failed');
      if (response.status === 413) {
        error = t('preview.too_large');
      }
      throw new Error(error);
    }
    const buffer = await response.arrayBuffer();
    const bytes = new Uint8Array(buffer);
    for (let i = 0; i < Math.min(bytes.length, 8000); i++) {
      if (bytes[i] === 0) {
        throw new Error(t('preview.unavailable'));
      }
    }
    const text = new TextDecoder('utf-8').decode(bytes).replace(/\r\n?/g, '\n');
    const lines = text.split('\n');
    if (lines.length > 1 && lines[lines.length - 1] === '') {
      lines.pop();
    }
    const meta = previewDialog.querySelector('.sn-preview__meta');
    meta.textContent = [data.size, t('preview.lines', { count: lines.length }), data.modified].filter(Boolean).join(', ');

    const extension = (data.name.indexOf('.') > 0 ? data.name.split('.').pop() : data.name.replace(/^\./, '')).toLowerCase();
    const language = languageFor(extension);
    const pre = element('pre', 'sn-code' + (language === 'plain' ? ' sn-code--wrap' : ''));
    const tokens = text.length <= 400000 ? highlight(text, language) : [[null, text]];
    renderLines(pre, tokens);
    return pre;
  }

  /* ------------------------------------------------------------------ syntax highlighting */

  const LANGUAGES = {
    clike: ['js', 'mjs', 'cjs', 'ts', 'tsx', 'jsx', 'java', 'kt', 'kts', 'scala', 'c', 'h', 'cpp', 'cc', 'hpp', 'cs', 'go', 'rs', 'swift', 'dart', 'groovy', 'gradle', 'proto', 'graphql', 'gql'],
    php: ['php', 'phtml', 'inc'],
    hash: ['py', 'rb', 'sh', 'bash', 'zsh', 'fish', 'pl', 'r', 'ps1', 'tf', 'hcl', 'nginx', 'dockerfile', 'makefile', 'gitignore', 'gitattributes', 'editorconfig', 'htaccess', 'ex', 'exs'],
    markup: ['html', 'htm', 'xhtml', 'xml', 'svg', 'vue', 'svelte', 'twig', 'blade', 'xsl', 'plist'],
    json: ['json', 'jsonl', 'lock', 'webmanifest'],
    config: ['yml', 'yaml', 'toml', 'ini', 'cfg', 'conf', 'env', 'properties'],
    css: ['css', 'scss', 'sass', 'less'],
    sql: ['sql'],
  };

  const KEYWORDS = new Set((
    'abstract and as async await break case catch class const continue def default defer delete do echo elif else enum export extends ' +
    'final finally fn for foreach from func function go if implements import in instanceof interface is lambda let match module namespace ' +
    'new not or package private protected public readonly return self static struct super switch this throw trait try type typeof use ' +
    'var void while with yield true false null nil none undefined True False None fun val when object impl mut pub crate where loop ' +
    'require include then fi done esac local unset declare begin end rescue unless until elsif'
  ).split(' '));

  const SQL_KEYWORDS = new Set((
    'select from where and or not insert into values update set delete create table index view drop alter add column primary key foreign ' +
    'references join left right inner outer full on group by order having limit offset as distinct union all case when then else end is ' +
    'null like in between exists default unique constraint begin commit rollback transaction asc desc count sum avg min max'
  ).split(' '));

  function languageFor(extension) {
    for (const language in LANGUAGES) {
      if (LANGUAGES[language].indexOf(extension) !== -1) {
        return language;
      }
    }
    return 'plain';
  }

  const STRING = String.raw`"(?:[^"\\\n]|\\.)*"|'(?:[^'\\\n]|\\.)*'`;
  const NUMBER = String.raw`\b(?:0x[\da-fA-F]+|\d[\d_]*(?:\.\d+)?(?:[eE][+-]?\d+)?)\b`;

  const RULES = {
    clike: [['c', String.raw`\/\*[\s\S]*?\*\/|\/\/[^\n]*`], ['s', STRING + '|`(?:[^`\\\\]|\\\\.)*`'], ['n', NUMBER], ['w', String.raw`[A-Za-z_]\w*`]],
    php: [['c', String.raw`\/\*[\s\S]*?\*\/|\/\/[^\n]*|#(?!\[)[^\n]*`], ['s', STRING], ['v', String.raw`\$[A-Za-z_]\w*`], ['n', NUMBER], ['t', String.raw`<\?php|\?>`], ['w', String.raw`[A-Za-z_]\w*`]],
    hash: [['s', String.raw`"""[\s\S]*?"""|'''[\s\S]*?'''`], ['c', String.raw`#[^\n]*`], ['s', STRING + '|`[^`\\n]*`'], ['v', String.raw`\$\{?[A-Za-z_]\w*\}?`], ['n', NUMBER], ['w', String.raw`[A-Za-z_]\w*`]],
    markup: [['c', String.raw`<!--[\s\S]*?-->|\{#[\s\S]*?#\}`], ['t', String.raw`<\/?[A-Za-z][\w:.-]*|\/?>|\{\{|\}\}|\{%|%\}`], ['a', String.raw`[\w:@.-]+(?==)`], ['s', STRING]],
    json: [['a', String.raw`"(?:[^"\\\n]|\\.)*"(?=\s*:)`], ['s', String.raw`"(?:[^"\\\n]|\\.)*"`], ['n', String.raw`-?` + NUMBER], ['k', String.raw`\b(?:true|false|null)\b`]],
    config: [['c', String.raw`(?:^|(?<=\s))[#;][^\n]*`], ['t', String.raw`^\s*\[[^\]\n]*\]`], ['a', String.raw`^[ \t-]*[\w.$"'-][\w.$ "'-]*?(?=\s*[:=])`], ['s', STRING], ['k', String.raw`\b(?:true|false|null|yes|no|on|off)\b`], ['n', NUMBER]],
    css: [['c', String.raw`\/\*[\s\S]*?\*\/|\/\/[^\n]*`], ['s', STRING], ['k', String.raw`@[\w-]+`], ['a', String.raw`[\w-]+(?=\s*:[^:{]*[;}\n])`], ['n', String.raw`#[\da-fA-F]{3,8}\b|-?\d*\.?\d+(?:px|em|rem|%|vh|vw|s|ms|deg|fr)?\b`], ['v', String.raw`\$[\w-]+|var\(--[\w-]+\)`]],
    sql: [['c', String.raw`--[^\n]*|\/\*[\s\S]*?\*\/`], ['s', String.raw`'(?:[^']|'')*'`], ['n', NUMBER], ['w', String.raw`[A-Za-z_]\w*`]],
  };

  const compiled = {};

  /** Splits text into [class, text] tokens; class null means plain text. */
  function highlight(text, language) {
    const rules = RULES[language];
    if (!rules) {
      return [[null, text]];
    }
    if (!compiled[language]) {
      compiled[language] = new RegExp(rules.map(function (rule) { return '(' + rule[1] + ')'; }).join('|'), 'gm');
    }
    const pattern = compiled[language];
    pattern.lastIndex = 0;
    const tokens = [];
    let last = 0;
    let match;
    while ((match = pattern.exec(text)) !== null) {
      if (match[0] === '') {
        pattern.lastIndex++;
        continue;
      }
      if (match.index > last) {
        tokens.push([null, text.slice(last, match.index)]);
      }
      let kind = null;
      for (let i = 1; i < match.length; i++) {
        if (match[i] !== undefined) {
          kind = rules[i - 1][0];
          break;
        }
      }
      if (kind === 'w') {
        const word = match[0];
        const isKeyword = language === 'sql' ? SQL_KEYWORDS.has(word.toLowerCase()) : KEYWORDS.has(word);
        kind = isKeyword ? 'k' : null;
      }
      tokens.push([kind, match[0]]);
      last = pattern.lastIndex;
    }
    if (last < text.length) {
      tokens.push([null, text.slice(last)]);
    }
    return tokens;
  }

  function renderLines(pre, tokens) {
    const fragment = document.createDocumentFragment();
    let line = element('span');
    tokens.forEach(function (token) {
      const parts = token[1].split('\n');
      parts.forEach(function (part, index) {
        if (index > 0) {
          line.appendChild(document.createTextNode('\n'));
          fragment.appendChild(line);
          line = element('span');
        }
        if (part === '') {
          return;
        }
        if (token[0]) {
          line.appendChild(element('span', 'tok-' + token[0], part));
        } else {
          line.appendChild(document.createTextNode(part));
        }
      });
    });
    if (line.childNodes.length || !fragment.childNodes.length) {
      fragment.appendChild(line);
    }
    pre.appendChild(fragment);
  }

  /* ------------------------------------------------------------------ toolbar buttons */

  document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-sn-action]');
    if (!button || button.closest('[data-sn-entry]')) {
      return;
    }
    const action = button.getAttribute('data-sn-action');
    switch (action) {
      case 'upload':
        chooseFiles();
        break;
      case 'mkdir':
        openMkdir();
        break;
      case 'shortcuts':
        openShortcuts();
        break;
      case 'download':
        if (active && active.getAttribute('data-type') === 'directory' && active.getAttribute('data-zip')) {
          location.href = active.getAttribute('data-zip');
          break;
        }
        if (active && active.hasAttribute('data-sn-entry')) {
          runEntryAction(action, active);
        }
        break;
      case 'preview':
      case 'rename':
      case 'delete':
        if (active && active.hasAttribute('data-sn-entry')) {
          runEntryAction(action, active);
        }
        break;
      case 'zip': {
        const url = active && active.getAttribute('data-type') === 'directory' ? active.getAttribute('data-zip') : button.getAttribute('data-url');
        if (url) {
          location.href = url;
        }
        break;
      }
      case 'parent': {
        const parent = document.querySelector('[data-sn-parent] [data-sn-entry-link]');
        if (parent) {
          parent.click();
        }
        break;
      }
      default:
        return;
    }
    event.preventDefault();
  });

  /* ------------------------------------------------------------------ keyboard */

  const FUNCTION_KEYS = {
    F1: 'shortcuts',
    F2: 'zip',
    F3: 'preview',
    F5: 'download',
    F6: 'rename',
    F7: 'mkdir',
    F8: 'delete',
    F9: 'upload',
  };

  document.addEventListener('keydown', function (event) {
    if (event.key.indexOf('Arrow') === 0 || event.key === 'Tab') {
      document.body.classList.add('sn-kb');
    }
    if (event.defaultPrevented || (event.altKey && event.key !== 'ArrowUp') || event.ctrlKey || event.metaKey) {
      return;
    }
    if (document.querySelector('dialog[open]')) {
      return;
    }
    const target = event.target;
    const typing = target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement || target.isContentEditable;

    if (isConsole && FUNCTION_KEYS[event.key]) {
      const button = document.querySelector('[data-sn-fkey="' + event.key + '"]');
      if (button && !button.disabled) {
        event.preventDefault();
        button.click();
      }
      return;
    }
    if (typing) {
      return;
    }

    const grid = columnsPerRow();
    switch (event.key) {
      case 'ArrowDown':
        event.preventDefault();
        move(grid);
        break;
      case 'ArrowUp':
        event.preventDefault();
        if (event.altKey) {
          goParent();
        } else {
          move(-grid);
        }
        break;
      case 'ArrowRight':
        if (grid > 1) {
          event.preventDefault();
          move(1);
        }
        break;
      case 'ArrowLeft':
        if (grid > 1) {
          event.preventDefault();
          move(-1);
        }
        break;
      case 'Home':
        event.preventDefault();
        setActive(visibleItems()[0], true);
        break;
      case 'End': {
        event.preventDefault();
        const visible = visibleItems();
        setActive(visible[visible.length - 1], true);
        break;
      }
      case 'PageDown':
        event.preventDefault();
        move(10 * grid);
        break;
      case 'PageUp':
        event.preventDefault();
        move(-10 * grid);
        break;
      case 'Enter':
        if (active && (!target.closest || !target.closest('button, a[href]:not([data-sn-entry-link])'))) {
          event.preventDefault();
          openItem(active, false);
        }
        break;
      case ' ':
        if (active && active.hasAttribute('data-preview')) {
          event.preventDefault();
          openPreview(active);
        }
        break;
      case 'Backspace':
        event.preventDefault();
        goParent();
        break;
      case 'Delete':
        if (active && active.hasAttribute('data-sn-entry')) {
          event.preventDefault();
          openDelete(active);
        }
        break;
      case 'F2':
        if (active && active.hasAttribute('data-sn-entry')) {
          event.preventDefault();
          openRename(active);
        }
        break;
      case '/':
        if (search) {
          event.preventDefault();
          search.focus();
          search.select();
        }
        break;
      case '?':
        event.preventDefault();
        openShortcuts();
        break;
      case 'u':
      case 'U':
        if (uploadForm) {
          event.preventDefault();
          chooseFiles();
        }
        break;
      case 'n':
      case 'N':
        if (mkdirDialog) {
          event.preventDefault();
          openMkdir();
        }
        break;
      default:
        break;
    }
  });

  function goParent() {
    const parent = document.querySelector('[data-sn-parent] [data-sn-entry-link]');
    if (parent) {
      parent.click();
    }
  }
})();

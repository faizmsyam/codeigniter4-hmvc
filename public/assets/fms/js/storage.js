/**
 * FMS Storage Browser + Users & Access
 * S3/MinIO-like object browser, drag-drop upload, share, delete, metadata
 */
(function () {
  'use strict';

  /**
   * Shared HTML escaper (single source of truth).
   * Previously defined locally inside initAudit / initBrowser / initUsers,
   * so callers in other scopes (e.g. initMimeSettings) hit `esc is not defined`.
   */
  function esc(value) {
    if (window.FMS && typeof window.FMS.escape === 'function') return window.FMS.escape(value);
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  const settingsRoot = document.getElementById('fmsStorageSettingsRoot');

  function initStorageTheme() {
    const toggle = document.getElementById('fmsStorageThemeToggle');
    if (!toggle) return;
    function sync() {
      const dark = document.documentElement.getAttribute('data-theme-mode') === 'dark';
      toggle.innerHTML = '<i class="ph-duotone ' + (dark ? 'ph-sun' : 'ph-moon') + '"></i>';
      toggle.title = dark ? 'Light mode' : 'Dark mode';
      toggle.setAttribute('aria-label', dark ? 'Aktifkan light mode' : 'Aktifkan dark mode');
    }
    toggle.addEventListener('click', function () {
      const dark = document.documentElement.getAttribute('data-theme-mode') === 'dark';
      if (window.FMSTheme && typeof window.FMSTheme.setPreference === 'function') window.FMSTheme.setPreference(dark ? 'light' : 'dark');
      else document.documentElement.setAttribute('data-theme-mode', dark ? 'light' : 'dark');
      sync();
    });
    sync();
  }

  function initPasswordToggles() {
    document.querySelectorAll('[data-fms-password-toggle]').forEach(function (button) {
      button.addEventListener('click', function () {
        const input = document.getElementById(button.dataset.fmsPasswordToggle);
        if (!input) return;
        const visible = input.type === 'text';
        input.type = visible ? 'password' : 'text';
        button.innerHTML = '<i class="ph-duotone ' + (visible ? 'ph-eye' : 'ph-eye-slash') + '"></i>';
        button.setAttribute('aria-label', visible ? 'Tampilkan password' : 'Sembunyikan password');
      });
    });
  }

  initStorageTheme();
  initPasswordToggles();
  if (settingsRoot) {
    const settingsForm = document.getElementById('fmsStoragePassword');
    if (settingsForm) {
      settingsForm.addEventListener('submit', function (event) {
        event.preventDefault();
        FMS.post(settingsRoot.dataset.passwordUrl, Object.fromEntries(new FormData(settingsForm)), { blockUI: true })
          .then(function () {
            FMS.toast('Password berhasil diubah.', true);
            settingsForm.reset();
          })
          .catch(function (error) {
            FMS.toast((error && error.message) || 'Gagal mengubah password.', false);
          });
      });
    }
  }

  function initMimeSettings() {
    const mimeRoot = document.getElementById('fmsStorageMimeRoot');
    if (!mimeRoot) return;
    const listEl = document.getElementById('fmsStorageMimeList');
    const saveBtn = document.getElementById('fmsStorageMimeSave');
    const allBtn = document.getElementById('fmsStorageMimeAll');
    const noneBtn = document.getElementById('fmsStorageMimeNone');
    const reloadBtn = document.getElementById('fmsStorageMimeReload');
    const noteEl = document.getElementById('fmsStorageMimeNote');
    const url = mimeRoot.dataset.settingsUrl;
    let available = [];

    function fmtBytes(bytes) {
      if (!bytes) return '';
      const units = ['B', 'KB', 'MB', 'GB'];
      let i = 0, n = bytes;
      while (n >= 1024 && i < units.length - 1) { n /= 1024; i++; }
      return (Math.round(n * 10) / 10) + ' ' + units[i];
    }

    function render() {
      if (!available.length) {
        listEl.innerHTML = '<div class="text-center text-muted py-3">Belum ada tipe MIME yang tersedia.</div>';
        return;
      }
      listEl.innerHTML = available.map(function (item) {
        const checked = item.enabled ? ' checked' : '';
        return '<div class="form-check fms-storage-mime-item">'
          + '<input class="form-check-input" type="checkbox" id="fmsMime_' + esc(item.key) + '" value="' + esc(item.mime) + '" data-mime="' + esc(item.mime) + '"' + checked + '>'
          + '<label class="form-check-label" for="fmsMime_' + esc(item.key) + '">'
          + '<span class="fms-storage-mime-label">' + esc(item.label) + '</span>'
          + '<code class="fms-storage-code">' + esc(item.mime) + '</code>'
          + '<small class="text-muted">.' + esc(item.extension) + ' &middot; max ' + fmtBytes(item.maxBytes) + '</small>'
          + '</label></div>';
      }).join('');
    }

    function load() {
      FMS.get(url, {}, { blockUI: true })
        .then(function (result) {
          const data = (result && result.data) || result || {};
          const allowed = data.allowed_mime_types || [];
          available = (data.available_mime_types || []).map(function (item) {
            item.enabled = allowed.indexOf(item.mime) !== -1;
            return item;
          });
          render();
          if (!allowed.length) noteEl.textContent = 'Belum ada MIME aktif - semua upload akan ditolak sampai kamu menyimpan pilihan.';
          else noteEl.textContent = allowed.length + ' tipe MIME aktif.';
        })
        .catch(function (error) {
          listEl.innerHTML = '<div class="text-center text-danger py-3">Gagal memuat daftar MIME.</div>';
          FMS.toast((error && error.message) || 'Gagal memuat daftar MIME.', false);
        });
    }

    function collect() {
      return Array.prototype.slice.call(listEl.querySelectorAll('input[type=checkbox]:checked'))
        .map(function (cb) { return cb.dataset.mime; });
    }

    function setAll(state) {
      listEl.querySelectorAll('input[type=checkbox]').forEach(function (cb) { cb.checked = state; });
    }

    saveBtn.addEventListener('click', function () {
      FMS.post(url, { allowed_mime_types: collect() }, { blockUI: true })
        .then(function (result) {
          const data = (result && result.data) || result || {};
          const saved = data.allowed_mime_types || [];
          FMS.toast('MIME diizinkan disimpan (' + saved.length + ' tipe).', true);
          load();
        })
        .catch(function (error) {
          FMS.toast((error && error.message) || 'Gagal menyimpan MIME.', false);
        });
    });
    allBtn.addEventListener('click', function () { setAll(true); });
    noneBtn.addEventListener('click', function () { setAll(false); });
    reloadBtn.addEventListener('click', load);

    load();
  }

  const root = document.getElementById('fmsStorageRoot');
  const usersRoot = document.getElementById('fmsStorageUsersRoot');

  if (root) initBrowser(root);
  if (usersRoot) initUsers(usersRoot);
  initMimeSettings();
  initStorageConfig();

  /* ── Konfigurasi Storage (driver, path, S3) — baca/tulis storage_settings ── */
  function initStorageConfig() {
    const card = document.getElementById('fmsStorageConfigRoot');
    if (!card) return;
    const url = card.dataset.settingsUrl;
    const el = function (id) { return document.getElementById(id); };
    const noteEl = el('fmsStorageConfigNote');

    function toggleDriver() {
      const isS3 = el('fmsStorageDriver').value === 's3';
      el('fmsStorageS3Fields').style.display = isS3 ? '' : 'none';
      el('fmsStorageLocalFields').style.display = isS3 ? 'none' : '';
    }

    function load() {
      FMS.get(url, {}, { blockUI: true })
        .then(function (result) {
          const cfg = (result && result.storage_config) || {};
          el('fmsStorageDriver').value = cfg.storage_driver || 'local';
          el('fmsStorageCfgRoot').value = cfg.storage_root || '';
          el('fmsStorageCfgBucket').value = cfg.bucket_name || '';
          el('fmsStorageCfgPrefix').value = cfg.object_key_prefix || '';
          el('fmsStorageS3Region').value = cfg.s3_region || '';
          el('fmsStorageS3Endpoint').value = cfg.s3_endpoint_host || '';
          el('fmsStorageS3Key').value = cfg.s3_access_key_id || '';
          el('fmsStorageS3Secret').value = '';   /* secret tidak pernah ditampilkan */
          el('fmsStorageS3PathStyle').checked = (cfg.s3_path_style === 'true' || cfg.s3_path_style === true || cfg.s3_path_style === '1');
          el('fmsStorageS3UseTls').checked = (cfg.s3_use_tls === 'true' || cfg.s3_use_tls === true || cfg.s3_use_tls === '1');
          toggleDriver();
          noteEl.textContent = 'Driver aktif: ' + (cfg.storage_driver || 'local');
        })
        .catch(function (error) {
          FMS.toast((error && error.message) || 'Gagal memuat konfigurasi storage.', false);
        });
    }

    el('fmsStorageDriver').addEventListener('change', toggleDriver);

    el('fmsStorageConfigSave').addEventListener('click', function () {
      const payload = {
        storage_config: {
          storage_driver: el('fmsStorageDriver').value,
          storage_root: el('fmsStorageCfgRoot').value,
          bucket_name: el('fmsStorageCfgBucket').value,
          object_key_prefix: el('fmsStorageCfgPrefix').value,
          s3_region: el('fmsStorageS3Region').value,
          s3_endpoint_host: el('fmsStorageS3Endpoint').value,
          s3_access_key_id: el('fmsStorageS3Key').value,
          s3_path_style: el('fmsStorageS3PathStyle').checked ? 'true' : 'false',
          s3_use_tls: el('fmsStorageS3UseTls').checked ? 'true' : 'false'
        }
      };
      /* Secret hanya dikirim kalau benar-benar diisi. */
      const secret = el('fmsStorageS3Secret').value;
      if (secret) payload.storage_config.s3_secret_access_key = secret;

      FMS.post(url, payload, { blockUI: true })
        .then(function () {
          FMS.toast('Konfigurasi storage disimpan.', true);
          load();
        })
        .catch(function (error) {
          FMS.toast((error && error.message) || 'Gagal menyimpan konfigurasi.', false);
        });
    });

    el('fmsStorageConfigReload').addEventListener('click', load);
    load();
  }

  /* Sidebar off-canvas (mobile): toggle + backdrop + auto-close saat resize ke desktop. */
  (function initSidebarToggle() {
    const shell = document.querySelector('.fms-storage-shell');
    const toggle = document.getElementById('fmsStorageSidebarToggle');
    const backdrop = document.getElementById('fmsStorageSidebarBackdrop');
    if (!shell || !toggle) return;

    function close() { shell.classList.remove('fms-sidebar-open'); }

    toggle.addEventListener('click', function () {
      shell.classList.toggle('fms-sidebar-open');
    });
    if (backdrop) backdrop.addEventListener('click', close);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') close();
    });
    window.addEventListener('resize', function () {
      if (window.innerWidth > 991.98) close();
    });
  })();

  function initAudit(root) {
    const api = root.dataset.apiUrl;
    const table = document.getElementById('fmsStorageAudit');
    const refresh = document.getElementById('fmsStorageAuditRefresh');
    if (!table) return;

    function loadAudit() {
      FMS.get(api + '/audit', { limit: 100, offset: 0 }, { blockUI: true })
        .then(function (result) {
          const rows = Array.isArray(result) ? result : ((result && result.items) || []);
          table.innerHTML = '';
          rows.forEach(function (row) {
            const metadata = row.metadata && Object.keys(row.metadata).length ? JSON.stringify(row.metadata) : '-';
            const tr = document.createElement('tr');
            tr.innerHTML = '<td>' + esc(row.created_at) + '</td>'
              + '<td>' + esc(row.username || row.full_name || 'system') + '</td>'
              + '<td><span class="badge bg-body-secondary text-body">' + esc(row.event) + '</span></td>'
              + '<td><code class="fms-storage-code">' + esc(row.object_key || '-') + '</code></td>'
              + '<td><small>' + esc(metadata) + '</small></td>';
            table.appendChild(tr);
          });
          if (!table.children.length) table.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Belum ada aktivitas.</td></tr>';
        })
        .catch(function (error) {
          FMS.toast((error && error.message) || 'Gagal memuat audit log.', false);
        });
    }

    if (refresh) refresh.addEventListener('click', loadAudit);
    loadAudit();
  }

  if (root && document.getElementById('fmsStorageAudit')) initAudit(root);

  /* ============================== OBJECT BROWSER ============================== */
  function initBrowser(root) {
    const api = root.dataset.apiUrl;
    const body = document.getElementById('fmsStorageObjects');
    /* Guard: initBrowser butuh element inti Object Browser.
       Kalau tidak ada (mis. halaman Settings/Users), keluar diam-diam. */
    if (!body || !api) return;
    const breadcrumb = document.getElementById('fmsStorageBreadcrumb');
    const bucketName = document.getElementById('fmsStorageBucketName');
    const uploadInput = document.getElementById('fmsStorageFile');
    const refreshBtn = document.getElementById('fmsStorageRefresh');
    const newFolderBtn = document.getElementById('fmsStorageNewFolder');
    const dropzone = document.getElementById('fmsStorageDropzone');
    let currentPrefix = '';
    let currentSearch = '';
    let currentSort = 'name';
    let currentDirection = 'asc';
    let currentPage = 1;
    const perPage = 100;

    /* Permission user (dikirim backend via data-storage-permissions di <body>). */
    const PERMS = (function () {
      const raw = document.querySelector('[data-storage-permissions]');
      const list = raw ? String(raw.dataset.storagePermissions || '').split(',').filter(Boolean) : [];
      return {
        upload: list.indexOf('storage.upload') !== -1,
        edit: list.indexOf('storage.edit') !== -1,
        del: list.indexOf('storage.delete') !== -1,
        share: list.indexOf('storage.share') !== -1
      };
    })();

    function fmt(n) {
      n = Number(n) || 0;
      return n < 1024 ? n + ' B' : n < 1048576 ? (n / 1024).toFixed(1) + ' KB' : (n / 1048576).toFixed(1) + ' MB';
    }

    function renderBreadcrumb(prefix) {
      const parts = prefix ? prefix.split('/').filter(Boolean) : [];
      if (parts.length === 0) {
        breadcrumb.style.display = 'none';
        return;
      }
      breadcrumb.style.display = 'block';
      const ol = breadcrumb.querySelector('ol');
      ol.innerHTML = '<li class="breadcrumb-item"><a href="#" data-prefix="">Root</a></li>';
      let accumulated = '';
      parts.forEach(function (part, i) {
        accumulated += part + (i < parts.length - 1 ? '/' : '');
        const li = document.createElement('li');
        li.className = 'breadcrumb-item' + (i === parts.length - 1 ? ' active' : '');
        if (i === parts.length - 1) {
          li.textContent = part;
        } else {
          const a = document.createElement('a');
          a.href = '#';
          a.textContent = part;
          a.dataset.prefix = accumulated + '/';
          li.appendChild(a);
        }
        ol.appendChild(li);
      });
    }

    function load(prefix, page) {
      currentPrefix = prefix || '';
      currentPage = page || 1;
      FMS.get(api + '/objects', {
        prefix: currentPrefix,
        search: currentSearch,
        sort: currentSort,
        direction: currentDirection,
        page: currentPage,
        per_page: perPage
      }, { blockUI: true })
        .then(function (result) {
          if (result.bucket) bucketName.textContent = result.bucket;
          renderBreadcrumb(currentPrefix);
          body.innerHTML = '';
          (result.folders || []).forEach(function (f) {
            const tr = document.createElement('tr');
            tr.className = 'fms-storage-row-folder';
            const folderKey = encodeURIComponent(f.prefix);
            let folderActions = '<td class="text-end fms-storage-row-actions">';
            if (PERMS.edit) folderActions += '<button type="button" class="btn btn-sm btn-outline-secondary fms-storage-list-action me-1" data-action="rename" data-kind="folder" data-key="' + esc(folderKey) + '" title="Rename folder" aria-label="Rename folder"><i class="ph-duotone ph-pencil-simple"></i></button>';
            if (PERMS.del) folderActions += '<button type="button" class="btn btn-sm btn-outline-danger fms-storage-list-action" data-action="delete-folder" data-kind="folder" data-key="' + esc(folderKey) + '" title="Hapus folder kosong" aria-label="Hapus folder"><i class="ph-duotone ph-trash"></i></button>';
            folderActions += '</td>';
            tr.innerHTML = '<td><i class="ph-duotone ph-folder me-2 text-warning"></i><span class="fms-storage-row-name">' + esc(f.name) + '</span></td><td>Folder</td><td>—</td><td>—</td>' + folderActions;
            tr.onclick = function (event) {
              if (event.target.closest('[data-action]')) return;
              load(f.prefix);
            };
            body.appendChild(tr);
          });
          (result.files || []).forEach(function (o) {
            const tr = document.createElement('tr');
            const key = encodeURIComponent(o.key);
            let fileActions = '<td class="text-end fms-storage-row-actions">';
            fileActions += '<button type="button" class="btn btn-sm btn-outline-info fms-storage-list-action me-1" data-action="preview" data-key="' + key + '" title="Preview" aria-label="Preview"><i class="ph-duotone ph-eye"></i></button>';
            fileActions += '<button type="button" class="btn btn-sm btn-outline-secondary fms-storage-list-action me-1" data-action="metadata" data-key="' + key + '" title="Detail" aria-label="Detail"><i class="ph-duotone ph-info"></i></button>';
            if (PERMS.edit) fileActions += '<button type="button" class="btn btn-sm btn-outline-secondary fms-storage-list-action me-1" data-action="rename" data-key="' + key + '" title="Rename" aria-label="Rename"><i class="ph-duotone ph-pencil-simple"></i></button>';
            if (PERMS.share) fileActions += '<button type="button" class="btn btn-sm btn-outline-primary fms-storage-list-action me-1" data-action="share" data-key="' + key + '" title="Share" aria-label="Share"><i class="ph-duotone ph-share-network"></i></button>';
            if (PERMS.del) fileActions += '<button type="button" class="btn btn-sm btn-outline-danger fms-storage-list-action" data-action="delete" data-key="' + key + '" title="Hapus" aria-label="Hapus"><i class="ph-duotone ph-trash"></i></button>';
            fileActions += '</td>';
            tr.innerHTML = '<td><i class="ph-duotone ph-file me-2"></i><span class="fms-storage-row-name">' + esc(o.name) + '</span></td>'
              + '<td>' + esc(o.mime) + '</td><td>' + fmt(o.size) + '</td><td>' + esc(o.updated_at) + '</td>'
              + fileActions;
            body.appendChild(tr);
          });
          if (!body.children.length) {
            body.innerHTML = '<tr><td colspan="5" class="text-center text-muted">' + (currentPrefix ? 'Folder kosong.' : 'Bucket kosong.') + '</td></tr>';
          }
          renderPagination(result.pagination || {});
        })
        .catch(function (error) {
          FMS.toast((error && error.message) || 'Gagal memuat object.', false);
        });
    }

      function renderPagination(pagination) {
        let paginationRoot = document.getElementById('fmsStoragePagination');
        if (!paginationRoot) {
          paginationRoot = document.createElement('div');
          paginationRoot.id = 'fmsStoragePagination';
          paginationRoot.className = 'mt-3';
          body.closest('.card-body').appendChild(paginationRoot);
        }
        FMS.pagination({
          target: paginationRoot,
          total: Number(pagination.total || 0),
          perPage: Number(pagination.per_page || perPage),
          page: Number(pagination.page || currentPage),
          onChange: function (page) { load(currentPrefix, page); }
        });
      }

    const knownFolders = Object.create(null);

    function ensureFolder(prefix) {
      if (!prefix || prefix === currentPrefix) return Promise.resolve();
      const segments = String(prefix).split('/').filter(Boolean);
      let chain = Promise.resolve();
      let accumulated = '';
      segments.forEach(function (segment) {
        accumulated = accumulated ? accumulated + '/' + segment : segment;
        const target = accumulated;
        chain = chain.then(function () {
          if (knownFolders[target]) return undefined;
          const parent = target.split('/').slice(0, -1).join('/');
          return FMS.post(api + '/objects/folder', { prefix: parent, name: segment }, { blockUI: false })
            .then(function () { knownFolders[target] = true; })
            .catch(function () { knownFolders[target] = true; /* sudah ada / race — abaikan */ });
        });
      });
      return chain;
    }

    function uploadFile(file, prefix, options) {
      if (!file) return Promise.resolve(false);
      const opts = options || {};
      const targetPrefix = prefix === undefined ? currentPrefix : prefix;
      return ensureFolder(targetPrefix).then(function () {
        const form = new FormData();
        form.append('file', file);
        form.append('prefix', targetPrefix);
        return FMS.post(api + '/objects', form, { blockUI: true })
          .then(function () {
            if (!opts.quiet) FMS.toast('Upload berhasil: ' + file.name, true);
            if (!opts.skipReload) load(currentPrefix, currentPage);
            return true;
          })
          .catch(function (error) {
            FMS.toast((error && error.message) || ('Upload gagal: ' + file.name), false);
            return false;   /* laporan gagal ke pemanggil — jangan di-swallow */
          });
      });
    }

    /* Antrian serial + satu kali refresh di akhir.
       Ringkasan sukses/gagal dihitung dari hasil nyata tiap file. */
    function runQueue(items) {
      if (!items.length) return Promise.resolve();
      const single = items.length === 1;
      let successCount = 0;
      let failureCount = 0;
      let chain = Promise.resolve();
      items.forEach(function (item) {
        chain = chain.then(function () {
          /* File tunggal: per-file toast & reload langsung.
             Banyak file: senyap per-file, ringkas di akhir. */
          return uploadFile(item.file, item.prefix, { quiet: !single, skipReload: !single })
            .then(function (ok) { ok ? successCount++ : failureCount++; });
        });
      });
      return chain.then(function () {
        if (!single) {
          if (successCount > 0) FMS.toast(successCount + ' file berhasil diupload.', true);
          if (failureCount > 0) FMS.toast(failureCount + ' file gagal diupload.', false);
        }
        load(currentPrefix, 1);
      });
    }

    /* Modal rename (tanpa window.prompt) */
    function promptRename(title, currentName, onConfirm) {
      const modal = document.createElement('div');
      modal.className = 'modal fade fms-storage-prompt';
      modal.innerHTML = '<div class="modal-dialog modal-dialog-centered modal-sm">'
        + '<div class="modal-content"><form class="fms-storage-prompt-form">'
        + '<div class="modal-header"><h6 class="modal-title fms-storage-title">' + esc(title) + '</h6>'
        + '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>'
        + '<div class="modal-body"><label class="form-label" for="fmsStorageRenameInput">Nama baru</label>'
        + '<input type="text" class="form-control" id="fmsStorageRenameInput" value="' + esc(currentName) + '" autocomplete="off" required>'
        + '<div class="invalid-feedback">Nama wajib diisi.</div></div>'
        + '<div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>'
        + '<button type="submit" class="btn btn-primary btn-sm">Simpan</button></div>'
        + '</form></div></div>';
      document.body.appendChild(modal);
      const instance = new bootstrap.Modal(modal);
      const form = modal.querySelector('form');
      const input = modal.querySelector('#fmsStorageRenameInput');
      form.addEventListener('submit', function (event) {
        event.preventDefault();
        const value = input.value.trim();
        if (!value) { input.classList.add('is-invalid'); return; }
        if (value === currentName) { instance.hide(); return; }
        instance.hide();
        onConfirm(value);
      });
      input.addEventListener('input', function () { input.classList.remove('is-invalid'); });
      modal.addEventListener('shown.bs.modal', function () { input.focus(); input.select(); });
      modal.addEventListener('hidden.bs.modal', function () { document.body.removeChild(modal); });
      instance.show();
    }

    if (breadcrumb) {
      breadcrumb.addEventListener('click', function (e) {
        if (e.target.tagName === 'A' && e.target.dataset.prefix !== undefined) {
          e.preventDefault();
          load(e.target.dataset.prefix);
        }
      });
    }

    const searchInput = document.getElementById('fmsStorageSearch');
    const sortSelect = document.getElementById('fmsStorageSort');
    let searchTimer = null;

    if (searchInput) {
      searchInput.addEventListener('input', function () {
        if (searchTimer) window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(function () {
          currentSearch = searchInput.value.trim();
          load(currentPrefix, 1);
        }, 250);
      });
    }

    if (sortSelect) {
      sortSelect.addEventListener('change', function () {
        currentSort = sortSelect.value;
        load(currentPrefix, 1);
      });
    }

    if (refreshBtn) refreshBtn.addEventListener('click', function () { load(currentPrefix, currentPage); });

    if (newFolderBtn) {
      newFolderBtn.addEventListener('click', function () {
        promptRename('Buat Folder', '', function (name) {
          FMS.post(api + '/objects/folder', { prefix: currentPrefix, name: name }, { blockUI: true })
            .then(function () {
              FMS.toast('Folder "' + name + '" dibuat.', true);
              load(currentPrefix, 1);
            })
            .catch(function (error) {
              FMS.toast((error && error.message) || 'Gagal membuat folder.', false);
            });
        });
      });
    }

    if (uploadInput) {
      uploadInput.addEventListener('change', function (e) {
        const files = e.target.files || [];
        for (let i = 0; i < files.length; i++) uploadFile(files[i]);
        e.target.value = '';
      });
    }

    /* Drag & drop: file DAN folder (webkit entries) — pertahankan struktur. */
    function entryFile(entry) {
      return new Promise(function (resolve) { entry.file(resolve, function () { resolve(null); }); });
    }

    function walkEntry(entry, basePrefix, out) {
      return new Promise(function (resolve) {
        if (entry.isFile) {
          entryFile(entry).then(function (file) {
            if (file) out.push({ file: file, prefix: basePrefix });
            resolve();
          });
          return;
        }
        if (!entry.isDirectory) { resolve(); return; }
        const dirPrefix = (basePrefix ? basePrefix + '/' : '') + safeSegment(entry.name);
        const reader = entry.createReader();
        const children = [];
        (function readBatch() {
          reader.readEntries(function (entries) {
            if (!entries.length) {
              Promise.all(children.map(function (child) { return walkEntry(child, dirPrefix, out); })).then(resolve);
              return;
            }
            for (let i = 0; i < entries.length; i++) children.push(entries[i]);
            readBatch();
          }, function () { resolve(); });
        })();
      });
    }

    function safeSegment(name) {
      return String(name || '').replace(/[^A-Za-z0-9_-]+/g, '_').replace(/^[_-]+|[_-]+$/g, '') || 'folder';
    }

    function uploadEntries(entries) {
      const collected = [];
      Promise.all(entries.map(function (entry) {
        if (entry.isFile) {
          return entryFile(entry).then(function (file) {
            if (file) collected.push({ file: file, prefix: currentPrefix });
          });
        }
        return walkEntry(entry, currentPrefix, collected);
      })).then(function () { return runQueue(collected); });
    }

    if (dropzone) {
      /* Tanpa izin upload, dropzone tidak dirender — jangan pasang handler. */
      if (!PERMS.upload) {
        dropzone.remove();
      } else {
      ['dragenter', 'dragover'].forEach(function (eventName) {
        dropzone.addEventListener(eventName, function (e) {
          e.preventDefault();
          e.stopPropagation();
          dropzone.classList.add('is-dragover');
        });
      });
      ['dragleave', 'drop'].forEach(function (eventName) {
        dropzone.addEventListener(eventName, function (e) {
          e.preventDefault();
          e.stopPropagation();
          dropzone.classList.remove('is-dragover');
        });
      });
      dropzone.addEventListener('drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.remove('is-dragover');
        const transfer = e.dataTransfer;
        if (!transfer) return;
        const items = transfer.items;
        if (items && items.length && typeof items[0].webkitGetAsEntry === 'function') {
          const entries = [];
          for (let i = 0; i < items.length; i++) {
            const entry = items[i].webkitGetAsEntry();
            if (entry) entries.push(entry);
          }
          if (entries.length) { uploadEntries(entries); return; }
        }
        const files = transfer.files;
        if (!files || !files.length) {
          FMS.toast('Tidak ada file terdeteksi pada drop.', false);
          return;
        }
        const collected = [];
        for (let i = 0; i < files.length; i++) collected.push({ file: files[i], prefix: currentPrefix });
        runQueue(collected);
      });
      dropzone.addEventListener('click', function () {
        if (uploadInput) uploadInput.click();
      });
      }
    }

    body.addEventListener('click', function (e) {
      const action = e.target.closest('[data-action]');
      if (!action) return;
      const key = decodeURIComponent(action.dataset.key || '');
      const act = action.dataset.action;

      /* Guard permission (kalau tombol lolos gate server-side — tidak seharusnya). */
      if ((act === 'rename') && !PERMS.edit) { FMS.toast('Tidak punya izin edit.', false); return; }
      if ((act === 'delete' || act === 'delete-folder') && !PERMS.del) { FMS.toast('Tidak punya izin hapus.', false); return; }
      if (act === 'share' && !PERMS.share) { FMS.toast('Tidak punya izin share.', false); return; }
      /* Preview & metadata: tidak butuh permission khusus (baca saja). */

      if (act === 'rename') {
        const isFolder = action.dataset.kind === 'folder';
        const currentName = key.split('/').pop();
        promptRename(isFolder ? 'Rename Folder' : 'Rename Object', currentName, function (name) {
          FMS.post(api + '/objects/rename', { key: key, name: name, type: isFolder ? 'folder' : 'object' }, { blockUI: true })
            .then(function () {
              FMS.toast((isFolder ? 'Folder' : 'Object') + ' berhasil di-rename.', true);
              load(currentPrefix, currentPage);
            })
            .catch(function (error) {
              FMS.toast((error && error.message) || 'Rename gagal.', false);
            });
        });
        return;
      }
      if (act === 'delete-folder') {
        const folderPrefix = key.replace(/\/+$/, '');
        FMS.confirm({
          title: 'Hapus Folder',
          message: 'Folder "' + folderPrefix.split('/').pop() + '" akan dihapus. Hanya folder kosong yang bisa dihapus.',
          confirmLabel: 'Hapus',
          cancelLabel: 'Batal',
          loadingLabel: 'Menghapus...',
          variant: 'danger',
          action: function () {
            return FMS.del(api + '/objects', { key: folderPrefix })
              .then(function () {
                FMS.toast('Folder berhasil dihapus.', true);
                load(currentPrefix, currentPage);
              });
          }
        });
        return;
      }
      if (act === 'delete') {
        FMS.confirm({
          title: 'Konfirmasi Hapus',
          message: 'Object ini akan dihapus permanen. Lanjutkan?',
          confirmLabel: 'Hapus',
          cancelLabel: 'Batal',
          loadingLabel: 'Menghapus...',
          variant: 'danger',
          action: function () {
            return FMS.del(api + '/objects', { key: key })
              .then(function () {
                FMS.toast('Object berhasil dihapus.', true);
                load(currentPrefix, currentPage);
              });
          }
        });
      }

      if (act === 'metadata' || act === 'preview') {
        FMS.get(api + '/objects/metadata', { key: key }, { blockUI: true })
          .then(function (meta) {
            if (act === 'preview') {
              const previewUrl = api + '/preview?key=' + encodeURIComponent(key);
              const previewWindow = window.open(previewUrl, '_blank', 'noopener');
              if (!previewWindow) FMS.toast('Popup preview diblokir browser.', false);
              return;
            }
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = '<div class="modal-dialog modal-dialog-centered"><div class="modal-content">'
              + '<div class="modal-header"><h5 class="modal-title fms-storage-title">Object Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>'
              + '<div class="modal-body"><dl class="row mb-0">'
              + '<dt class="col-sm-4">Key</dt><dd class="col-sm-8"><code class="fms-storage-code">' + esc(meta.key) + '</code></dd>'
              + '<dt class="col-sm-4">Type</dt><dd class="col-sm-8">' + esc(meta.mime) + '</dd>'
              + '<dt class="col-sm-4">Size</dt><dd class="col-sm-8">' + fmt(meta.size) + '</dd>'
              + '<dt class="col-sm-4">Modified</dt><dd class="col-sm-8">' + esc(meta.updated_at) + '</dd>'
              + '<dt class="col-sm-4">ETag</dt><dd class="col-sm-8"><code class="fms-storage-code">' + esc(meta.etag) + '</code></dd>'
              + '<dt class="col-sm-4">SHA256</dt><dd class="col-sm-8"><code class="fms-storage-code-sm">' + esc(meta.sha256) + '</code></dd>'
              + '</dl></div>'
              + '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>'
              + '<a href="' + esc(meta.download_url) + '" class="btn btn-primary" target="_blank">Download</a></div>'
              + '</div></div>';
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            modal.addEventListener('hidden.bs.modal', function () { document.body.removeChild(modal); });
          })
          .catch(function (error) {
            FMS.toast((error && error.message) || 'Gagal memuat metadata.', false);
          });
      }

      if (act === 'share') {
        FMS.post(api + '/share', { key: key, ttl: 3600 }, { blockUI: true })
          .then(function (result) {
            if (result.url) {
              if (navigator.clipboard) navigator.clipboard.writeText(result.url);
              FMS.toast('Share URL disalin ke clipboard.', true);
            }
          })
          .catch(function (error) {
            FMS.toast((error && error.message) || 'Share gagal.', false);
          });
      }

    });

    load();
  }

  /* ============================== USERS & ACCESS ============================== */
  function initUsers(root) {
    const api = root.dataset.apiUrl;
    const usersBody = document.getElementById('fmsStorageUsers');
    const rolesBody = document.getElementById('fmsStorageRoles');
    const userModal = new bootstrap.Modal(document.getElementById('fmsStorageUserModal'));
    const roleModal = new bootstrap.Modal(document.getElementById('fmsStorageRoleModal'));
    const userForm = document.getElementById('fmsStorageUserForm');
    const roleForm = document.getElementById('fmsStorageRoleForm');

    let state = { users: [], roles: [], permissions: [] };
    let editingUserId = null;
    let usersPage = 1;
    const usersPerPage = 10;

    function load() {
      FMS.get(api + '/users', {}, { blockUI: true })
        .then(function (data) {
          state = data;
          renderUsers();
          renderRoles();
        })
        .catch(function (error) {
          FMS.toast((error && error.message) || 'Gagal memuat user storage.', false);
        });
    }

    function renderUsers() {
      usersBody.innerHTML = '';
      const allUsers = Array.isArray(state.users) ? state.users : [];
      const totalPages = Math.max(1, Math.ceil(allUsers.length / usersPerPage));
      usersPage = Math.min(Math.max(1, usersPage), totalPages);
      const visibleUsers = allUsers.slice((usersPage - 1) * usersPerPage, usersPage * usersPerPage);
      visibleUsers.forEach(function (u) {
        const tr = document.createElement('tr');
        const roles = (u.roles || []).map(function (r) {
          return '<span class="badge bg-secondary-subtle text-body me-1">' + esc(r.name) + '</span>';
        }).join('');
        tr.innerHTML = '<td><i class="ph-duotone ph-user me-2"></i>' + esc(u.username)
          + (u.is_super_admin ? ' <span class="badge bg-danger">Super Admin</span>' : '') + '</td>'
          + '<td>' + esc(u.full_name || '-') + '</td>'
          + '<td><span class="badge ' + (u.status === 'active' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning') + '">' + esc(u.status) + '</span></td>'
          + '<td>' + roles + '</td>'
          + '<td>' + esc(u.last_login_at || '-') + '</td>'
          + '<td class="text-end fms-storage-row-actions">'
          + '<button type="button" class="btn btn-sm btn-outline-primary fms-storage-list-action edit-user" data-id="' + u.id + '" title="Edit user" aria-label="Edit user"><i class="ph-duotone ph-pencil-simple"></i></button>'
          + (u.is_super_admin ? '' : ' <button type="button" class="btn btn-sm btn-outline-danger fms-storage-list-action delete-user" data-id="' + u.id + '" title="Hapus user" aria-label="Hapus user"><i class="ph-duotone ph-trash"></i></button>')
          + '</td>';
        usersBody.appendChild(tr);
      });
      if (!visibleUsers.length) {
        usersBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Belum ada user.</td></tr>';
      }
      const pager = document.getElementById('fmsStorageUsersPagination');
      if (pager) {
        FMS.pagination({
          target: pager,
          total: allUsers.length,
          perPage: usersPerPage,
          page: usersPage,
          onChange: function (page) { usersPage = page; renderUsers(); }
        });
      }
    }

    function renderRoles() {
      rolesBody.innerHTML = '';
      state.roles.forEach(function (r) {
        const perms = (r.permissions || []).map(function (p) {
          return '<span class="badge bg-body-secondary text-body me-1 mb-1">' + esc(p) + '</span>';
        }).join(' ');
        const tr = document.createElement('tr');
        tr.innerHTML = '<td><i class="ph-duotone ph-shield-check me-2"></i>' + esc(r.name) + '</td>'
          + '<td>' + perms + '</td>'
          + '<td class="text-end fms-storage-row-actions">'
          + '<button type="button" class="btn btn-sm btn-outline-primary fms-storage-list-action edit-role" data-id="' + r.id + '" title="Edit role" aria-label="Edit role"><i class="ph-duotone ph-pencil-simple"></i></button>'
          + (r.name === 'super_admin' ? '' : ' <button type="button" class="btn btn-sm btn-outline-danger fms-storage-list-action delete-role" data-id="' + r.id + '" title="Hapus role" aria-label="Hapus role"><i class="ph-duotone ph-trash"></i></button>')
          + '</td>';
        rolesBody.appendChild(tr);
      });
      if (!rolesBody.children.length) {
        rolesBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Belum ada role.</td></tr>';
      }
    }

    function renderRoleCheckboxes(container, selected) {
      container.innerHTML = '';
      /* super_admin adalah system role: tidak pernah assignable lewat checkbox,
         selalu disembunyikan dari modal Tambah/Edit User. */
      const roles = (Array.isArray(state.roles) ? state.roles : []).filter(function (r) { return r.name !== 'super_admin'; });
      if (!roles.length) {
        container.innerHTML = '<div class="text-muted small">Belum ada role. Buat role baru di kartu <strong>Storage Roles</strong> terlebih dahulu.</div>';
        return;
      }
      roles.forEach(function (r) {
        const div = document.createElement('div');
        div.className = 'form-check';
        div.innerHTML = '<input class="form-check-input" type="checkbox" value="' + r.id + '" id="role-' + r.id + '"'
          + (selected.indexOf(r.id) !== -1 ? ' checked' : '') + '>'
          + '<label class="form-check-label" for="role-' + r.id + '">' + esc(r.name) + '</label>';
        container.appendChild(div);
      });
    }

    function renderPermissionCheckboxes(container, selected) {
      container.innerHTML = '';
      state.permissions.forEach(function (p) {
        const div = document.createElement('div');
        div.className = 'form-check';
        const id = 'perm-' + String(p.key).replace(/[^A-Za-z0-9_-]/g, '-');
        div.innerHTML = '<input class="form-check-input" type="checkbox" value="' + esc(p.key) + '" id="' + id + '"'
          + (selected.indexOf(p.key) !== -1 ? ' checked' : '') + '>'
          + '<label class="form-check-label" for="' + id + '">' + esc(p.key) + '</label>';
        container.appendChild(div);
      });
    }

    document.getElementById('fmsStorageUserCreate').addEventListener('click', function () {
      editingUserId = null;
      userForm.reset();
      document.getElementById('fmsStorageUserUsername').disabled = false;
      document.getElementById('fmsStorageUserPassword').required = true;
      renderRoleCheckboxes(document.getElementById('fmsStorageUserRoles'), []);
      userModal.show();
    });

    document.getElementById('fmsStorageRoleCreate').addEventListener('click', function () {
      roleForm.reset();
      renderPermissionCheckboxes(document.getElementById('fmsStorageRolePermissions'), []);
      roleModal.show();
    });

    usersBody.addEventListener('click', function (e) {
      const trigger = e.target.closest('.edit-user, .delete-user');
      if (!trigger) return;
      const id = parseInt(trigger.dataset.id, 10);
      if (!id) return;

      if (trigger.classList.contains('edit-user')) {
        const user = state.users.find(function (u) { return u.id === id; });
        if (!user) return;
        editingUserId = id;
        userForm.reset();
        document.getElementById('fmsStorageUserUsername').value = user.username;
        document.getElementById('fmsStorageUserUsername').disabled = true;
        document.getElementById('fmsStorageUserFullName').value = user.full_name || '';
        document.getElementById('fmsStorageUserEmail').value = user.email || '';
        document.getElementById('fmsStorageUserStatus').value = user.status;
        document.getElementById('fmsStorageUserPassword').required = false;
        renderRoleCheckboxes(document.getElementById('fmsStorageUserRoles'), (user.roles || []).map(function (r) { return r.id; }));
        userModal.show();
      }

      if (trigger.classList.contains('delete-user')) {
        FMS.confirm({
          title: 'Konfirmasi Hapus',
          message: 'User storage ini akan dihapus beserta session-nya. Lanjutkan?',
          confirmLabel: 'Hapus',
          cancelLabel: 'Batal',
          loadingLabel: 'Menghapus...',
          variant: 'danger',
          action: function () {
            return FMS.del(api + '/users/' + id)
              .then(function () {
                FMS.toast('User berhasil dihapus.', true);
                load();
              });
          }
        });
      }
    });

    rolesBody.addEventListener('click', function (e) {
      const trigger = e.target.closest('.edit-role, .delete-role');
      if (!trigger) return;
      const id = parseInt(trigger.dataset.id, 10);
      if (!id) return;

      if (trigger.classList.contains('edit-role')) {
        const role = state.roles.find(function (r) { return r.id === id; });
        if (!role) return;
        roleForm.reset();
        document.getElementById('fmsStorageRoleName').value = role.name;
        renderPermissionCheckboxes(document.getElementById('fmsStorageRolePermissions'), role.permissions || []);
        roleModal.show();
      }

      if (trigger.classList.contains('delete-role')) {
        FMS.confirm({
          title: 'Konfirmasi Hapus',
          message: 'Role ini akan dihapus dari semua user. Lanjutkan?',
          confirmLabel: 'Hapus',
          cancelLabel: 'Batal',
          loadingLabel: 'Menghapus...',
          variant: 'danger',
          action: function () {
            return FMS.del(api + '/roles/' + id)
              .then(function () {
                FMS.toast('Role berhasil dihapus.', true);
                load();
              });
          }
        });
      }
    });

    document.getElementById('fmsStorageUserSave').addEventListener('click', function () {
      if (!userForm.checkValidity()) {
        userForm.reportValidity();
        return;
      }
      const data = {
        full_name: document.getElementById('fmsStorageUserFullName').value,
        email: document.getElementById('fmsStorageUserEmail').value,
        status: document.getElementById('fmsStorageUserStatus').value,
        password: document.getElementById('fmsStorageUserPassword').value,
        role_ids: Array.prototype.map.call(
          document.querySelectorAll('#fmsStorageUserRoles input:checked'),
          function (input) { return parseInt(input.value, 10); }
        )
      };
      if (!editingUserId) data.username = document.getElementById('fmsStorageUserUsername').value;
      if (!data.password) delete data.password;

      const request = editingUserId
        ? FMS.put(api + '/users/' + editingUserId, data)
        : FMS.post(api + '/users', data);

      request.then(function () {
        FMS.toast(editingUserId ? 'User berhasil diperbarui.' : 'User berhasil dibuat.', true);
        userModal.hide();
        load();
      }).catch(function (error) {
        FMS.toast((error && error.message) || 'Gagal menyimpan user.', false);
      });
    });

    document.getElementById('fmsStorageRoleSave').addEventListener('click', function () {
      if (!roleForm.checkValidity()) {
        roleForm.reportValidity();
        return;
      }
      const data = {
        name: document.getElementById('fmsStorageRoleName').value,
        permissions: Array.prototype.map.call(
          document.querySelectorAll('#fmsStorageRolePermissions input:checked'),
          function (input) { return input.value; }
        )
      };
      FMS.post(api + '/roles', data)
        .then(function () {
          FMS.toast('Role berhasil disimpan.', true);
          roleModal.hide();
          load();
        })
        .catch(function (error) {
          FMS.toast((error && error.message) || 'Gagal menyimpan role.', false);
        });
    });

    /* Guard global: cegah browser menavigasi ke file/folder yang di-drop di luar dropzone. */
    ['dragover', 'drop'].forEach(function (name) {
      window.addEventListener(name, function (e) { e.preventDefault(); }, false);
    });

    load();
  }
})();

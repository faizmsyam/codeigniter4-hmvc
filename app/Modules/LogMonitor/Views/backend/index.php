<?php
/**
 * Log Monitor — read-only viewer untuk writable/logs.
 * Data dimuat melalui API agar halaman tetap ringan.
 */
?>
<div id="logMonitorApp"
     data-files-url="<?php echo esc(site_url('api/v1/log-monitor/files'), 'attr'); ?>"
     data-read-url="<?php echo esc(site_url('api/v1/log-monitor/read'), 'attr'); ?>"
     data-stats-url="<?php echo esc(site_url('api/v1/log-monitor/stats'), 'attr'); ?>"
     data-download-url="<?php echo esc(site_url('api/v1/log-monitor/download'), 'attr'); ?>">

  <div class="row g-3 mb-3">
    <div class="col-md-3 col-sm-6">
      <div class="card custom-card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <span class="avatar avatar-lg bg-primary-transparent text-primary"><i class="ph-duotone ph-files fs-24"></i></span>
          <div>
            <p class="mb-1 text-muted fs-12 text-uppercase fw-semibold">Total File</p>
            <h4 class="mb-0" id="logTotalFiles">0</h4>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card custom-card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <span class="avatar avatar-lg bg-info-transparent text-info"><i class="ph-duotone ph-hard-drives fs-24"></i></span>
          <div>
            <p class="mb-1 text-muted fs-12 text-uppercase fw-semibold">Total Ukuran</p>
            <h4 class="mb-0" id="logTotalSize">0 B</h4>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card custom-card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <span class="avatar avatar-lg bg-success-transparent text-success"><i class="ph-duotone ph-clock-clockwise fs-24"></i></span>
          <div>
            <p class="mb-1 text-muted fs-12 text-uppercase fw-semibold">Terakhir Update</p>
            <h6 class="mb-0" id="logLastUpdate">—</h6>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card custom-card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <span class="avatar avatar-lg bg-warning-transparent text-warning"><i class="ph-duotone ph-folder-open fs-24"></i></span>
          <div class="overflow-hidden">
            <p class="mb-1 text-muted fs-12 text-uppercase fw-semibold">Folder</p>
            <h6 class="mb-0 text-truncate" id="logBasePath">writable/logs</h6>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-4 col-xl-3">
      <div class="card custom-card h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
          <div>
            <h5 class="mb-0">File Log</h5>
            <small class="text-muted">writable/logs</small>
          </div>
          <button type="button" class="btn btn-sm btn-icon btn-primary-light" id="logRefreshFiles" title="Refresh daftar file">
            <i class="ph-duotone ph-arrows-clockwise"></i>
          </button>
        </div>
        <div class="card-body p-0">
          <div class="p-3 border-bottom">
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="ph-duotone ph-magnifying-glass"></i></span>
              <input type="text" class="form-control" id="logFileFilter" placeholder="Cari nama file...">
            </div>
          </div>
          <div id="logFileList" class="list-group list-group-flush log-file-list">
            <div class="p-4 text-center text-muted">
              <div class="spinner-border spinner-border-sm mb-2" role="status"></div>
              <div>Memuat file...</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-8 col-xl-9">
      <div class="card custom-card h-100">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div class="overflow-hidden">
            <h5 class="mb-1 text-truncate" id="logViewerTitle">Pilih file log</h5>
            <div class="d-flex align-items-center gap-3 text-muted fs-12" id="logViewerMeta">
              <span><i class="ph-duotone ph-file-text me-1"></i>Belum ada file dipilih</span>
            </div>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-sm btn-secondary-light" id="logAutoRefresh" disabled>
              <i class="ph-duotone ph-play me-1"></i>Auto Refresh
            </button>
            <button type="button" class="btn btn-sm btn-primary-light" id="logRefreshContent" disabled>
              <i class="ph-duotone ph-arrows-clockwise me-1"></i>Refresh
            </button>
            <button type="button" class="btn btn-sm btn-success-light" id="logDownload" disabled>
              <i class="ph-duotone ph-download-simple me-1"></i>Download
            </button>
          </div>
        </div>

        <div class="card-header py-2">
          <div class="row g-2 w-100 align-items-center">
            <div class="col-md-6">
              <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="ph-duotone ph-magnifying-glass"></i></span>
                <input type="search" class="form-control" id="logSearch" placeholder="Cari dalam isi log..." disabled>
                <button class="btn btn-outline-secondary" type="button" id="logSearchClear" disabled title="Hapus pencarian">
                  <i class="ph-duotone ph-x"></i>
                </button>
              </div>
            </div>
            <div class="col-md-3">
              <select class="form-select form-select-sm" id="logLineLimit" disabled>
                <option value="100">100 baris</option>
                <option value="500" selected>500 baris</option>
                <option value="1000">1.000 baris</option>
                <option value="5000">5.000 baris</option>
              </select>
            </div>
            <div class="col-md-3 text-md-end">
              <span class="badge bg-light text-dark border" id="logLineCount">0 baris</span>
            </div>
          </div>
        </div>

        <div class="card-body p-0 position-relative">
          <div id="logViewerEmpty" class="d-flex flex-column align-items-center justify-content-center text-muted" style="min-height:480px">
            <i class="ph-duotone ph-file-magnifying-glass fs-48 mb-3"></i>
            <h6 class="mb-1">Belum ada file dipilih</h6>
            <p class="mb-0 fs-13">Pilih file log di sebelah kiri untuk melihat isinya.</p>
          </div>
          <div id="logViewerLoading" class="d-none position-absolute top-0 start-0 w-100 h-100 align-items-center justify-content-center bg-white bg-opacity-75" style="z-index:10">
            <div class="text-center">
              <div class="spinner-border text-primary mb-2" role="status"></div>
              <div class="fs-13 text-muted">Membaca log...</div>
            </div>
          </div>
          <pre id="logViewerContent" class="d-none log-viewer mb-0"><code></code></pre>
        </div>

        <div class="card-footer d-none align-items-center justify-content-between flex-wrap gap-2" id="logPagination">
          <small class="text-muted" id="logPageInfo">—</small>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-primary" id="logPrevPage" disabled>
              <i class="ph-duotone ph-caret-left me-1"></i>Sebelumnya
            </button>
            <button type="button" class="btn btn-outline-primary" id="logNextPage" disabled>
              Berikutnya<i class="ph-duotone ph-caret-right ms-1"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .log-file-list { max-height: 620px; overflow-y: auto; }
  .log-file-item { cursor: pointer; transition: background-color .15s ease, border-color .15s ease; }
  .log-file-item:hover { background: var(--primary005); }
  .log-file-item.active { background: var(--primary01); border-left: 3px solid var(--primary-color); }
  .log-file-item .file-name { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: .8rem; word-break: break-all; }
  .log-viewer { min-height: 480px; max-height: 680px; overflow: auto; padding: 1rem; background: #0d1117; color: #c9d1d9; font: 12px/1.6 ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; white-space: pre-wrap; word-break: break-word; }
  .log-viewer .log-error { color:#ff7b72; font-weight:600; }
  .log-viewer .log-warn { color:#d29922; font-weight:600; }
  .log-viewer .log-info { color:#58a6ff; font-weight:600; }
  .log-viewer .log-debug { color:#8b949e; font-weight:600; }
  .log-viewer .log-time { color:#a5d6ff; }
  [data-theme-mode="light"] .log-viewer { background:#f6f8fa; color:#24292f; }
  [data-theme-mode="light"] .log-viewer .log-error { color:#cf222e; }
  [data-theme-mode="light"] .log-viewer .log-warn { color:#9a6700; }
  [data-theme-mode="light"] .log-viewer .log-info { color:#0969da; }
  [data-theme-mode="light"] .log-viewer .log-debug { color:#57606a; }
  [data-theme-mode="light"] .log-viewer .log-time { color:#0550ae; }
</style>

<script>
(function () {
  'use strict';

  function initLogMonitor() {
    const app = document.getElementById('logMonitorApp');
    if (!app || !window.FMS) return;

  const urls = {
    files: app.dataset.filesUrl,
    read: app.dataset.readUrl,
    stats: app.dataset.statsUrl,
    download: app.dataset.downloadUrl
  };

  const els = {
    fileList: document.getElementById('logFileList'),
    fileFilter: document.getElementById('logFileFilter'),
    refreshFiles: document.getElementById('logRefreshFiles'),
    totalFiles: document.getElementById('logTotalFiles'),
    totalSize: document.getElementById('logTotalSize'),
    lastUpdate: document.getElementById('logLastUpdate'),
    basePath: document.getElementById('logBasePath'),
    viewerTitle: document.getElementById('logViewerTitle'),
    viewerMeta: document.getElementById('logViewerMeta'),
    viewerEmpty: document.getElementById('logViewerEmpty'),
    viewerLoading: document.getElementById('logViewerLoading'),
    viewerContent: document.getElementById('logViewerContent'),
    viewerCode: document.querySelector('#logViewerContent code'),
    refreshContent: document.getElementById('logRefreshContent'),
    autoRefresh: document.getElementById('logAutoRefresh'),
    download: document.getElementById('logDownload'),
    search: document.getElementById('logSearch'),
    searchClear: document.getElementById('logSearchClear'),
    lineLimit: document.getElementById('logLineLimit'),
    lineCount: document.getElementById('logLineCount'),
    pagination: document.getElementById('logPagination'),
    pageInfo: document.getElementById('logPageInfo'),
    prevPage: document.getElementById('logPrevPage'),
    nextPage: document.getElementById('logNextPage')
  };

  let files = [];
  let selectedFile = '';
  let offset = 0;
  let totalLines = 0;
  let hasMore = false;
  let autoRefreshTimer = null;
  let searchTimer = null;

  function formatBytes(bytes) {
    const units = ['B', 'KB', 'MB', 'GB'];
    let value = Number(bytes || 0);
    let unit = 0;
    while (value >= 1024 && unit < units.length - 1) { value /= 1024; unit++; }
    return (unit === 0 ? value : value.toFixed(1)) + ' ' + units[unit];
  }

  function escapeText(value) {
    const span = document.createElement('span');
    span.textContent = String(value == null ? '' : value);
    return span.innerHTML;
  }

  function showLoading(show) {
    els.viewerLoading.classList.toggle('d-none', !show);
    els.viewerLoading.classList.toggle('d-flex', show);
  }

  function renderFiles() {
    const query = els.fileFilter.value.trim().toLowerCase();
    const filtered = files.filter(function (file) {
      return !query || file.name.toLowerCase().includes(query);
    });

    if (!filtered.length) {
      els.fileList.innerHTML = '<div class="p-4 text-center text-muted"><i class="ph-duotone ph-folder-dashed fs-32 d-block mb-2"></i>Tidak ada file log.</div>';
      return;
    }

    els.fileList.innerHTML = filtered.map(function (file) {
      const active = file.name === selectedFile ? ' active' : '';
      return '<button type="button" class="list-group-item list-group-item-action log-file-item' + active + '" data-file="' + escapeText(file.name) + '">' +
        '<div class="d-flex align-items-start gap-2">' +
          '<i class="ph-duotone ph-file-text fs-20 text-primary mt-1"></i>' +
          '<div class="overflow-hidden flex-grow-1">' +
            '<div class="file-name fw-medium">' + escapeText(file.name) + '</div>' +
            '<div class="d-flex justify-content-between text-muted fs-11 mt-1"><span>' + formatBytes(file.size) + '</span><span>' + escapeText(file.modified_date) + '</span></div>' +
          '</div>' +
        '</div>' +
      '</button>';
    }).join('');

    els.fileList.querySelectorAll('[data-file]').forEach(function (button) {
      button.addEventListener('click', function () { selectFile(button.dataset.file); });
    });
  }

  function loadFiles() {
    return FMS.ajax({ url: urls.files, method: 'GET', blockUI: false }).then(function (data) {
      files = Array.isArray(data.files) ? data.files : [];
      const totalSize = files.reduce(function (sum, file) { return sum + Number(file.size || 0); }, 0);
      els.totalFiles.textContent = files.length;
      els.totalSize.textContent = formatBytes(totalSize);
      els.lastUpdate.textContent = files.length ? files[0].modified_date : '—';
      els.basePath.textContent = data.base_path || 'writable/logs';
      els.basePath.title = data.base_path || '';
      renderFiles();
    }).catch(function (error) {
      els.fileList.innerHTML = '<div class="p-4 text-center text-danger">' + escapeText(error.message || 'Gagal memuat file log.') + '</div>';
    });
  }

  function selectFile(name) {
    selectedFile = name;
    offset = 0;
    els.viewerTitle.textContent = name;
    els.viewerEmpty.classList.add('d-none');
    els.viewerContent.classList.remove('d-none');
    [els.refreshContent, els.autoRefresh, els.download, els.search, els.searchClear, els.lineLimit].forEach(function (el) { el.disabled = false; });
    renderFiles();
    loadContent();
  }

  function loadContent(silent) {
    if (!selectedFile) return Promise.resolve();
    const limit = Number(els.lineLimit.value || 500);
    const search = els.search.value.trim();
    if (!silent) showLoading(true);

    const query = new URLSearchParams({
      path: selectedFile,
      offset: String(offset),
      limit: String(limit)
    });
    if (search) query.set('search', search);

    return FMS.ajax({ url: urls.read + '?' + query.toString(), method: 'GET', blockUI: false }).then(function (data) {
      const lines = Array.isArray(data.lines) ? data.lines : [];
      totalLines = Number(data.total_lines || 0);
      hasMore = Boolean(data.has_more);
      els.viewerCode.innerHTML = lines.length ? lines.join('\n') : '<span class="text-muted">Tidak ada baris yang cocok.</span>';
      els.lineCount.textContent = totalLines.toLocaleString('id-ID') + ' baris';
      els.pageInfo.textContent = totalLines ? 'Baris ' + (offset + 1).toLocaleString('id-ID') + '–' + Math.min(offset + limit, totalLines).toLocaleString('id-ID') + ' dari ' + totalLines.toLocaleString('id-ID') : '0 baris';
      els.prevPage.disabled = offset <= 0;
      els.nextPage.disabled = !hasMore;
      els.pagination.classList.remove('d-none');
      els.pagination.classList.add('d-flex');
      els.viewerMeta.innerHTML = '<span><i class="ph-duotone ph-list-numbers me-1"></i>' + totalLines.toLocaleString('id-ID') + ' baris</span><span><i class="ph-duotone ph-clock me-1"></i>Diperbarui sekarang</span>';
      if (!silent) els.viewerContent.scrollTop = 0;
    }).catch(function (error) {
      els.viewerCode.textContent = error.message || 'Gagal membaca log.';
      FMS.toast(error.message || 'Gagal membaca log.', false);
    }).finally(function () {
      showLoading(false);
    });
  }

  function toggleAutoRefresh() {
    if (autoRefreshTimer) {
      clearInterval(autoRefreshTimer);
      autoRefreshTimer = null;
      els.autoRefresh.classList.remove('btn-danger-light');
      els.autoRefresh.classList.add('btn-secondary-light');
      els.autoRefresh.innerHTML = '<i class="ph-duotone ph-play me-1"></i>Auto Refresh';
      return;
    }
    autoRefreshTimer = setInterval(function () { loadContent(true); }, 5000);
    els.autoRefresh.classList.remove('btn-secondary-light');
    els.autoRefresh.classList.add('btn-danger-light');
    els.autoRefresh.innerHTML = '<i class="ph-duotone ph-stop me-1"></i>Stop Auto';
  }

  els.refreshFiles.addEventListener('click', loadFiles);
  els.fileFilter.addEventListener('input', renderFiles);
  els.refreshContent.addEventListener('click', function () { loadContent(false); });
  els.autoRefresh.addEventListener('click', toggleAutoRefresh);
  els.download.addEventListener('click', function () {
    if (!selectedFile) return;
    var token = '';
    var tokenType = 'Bearer';
    try {
      token = sessionStorage.getItem('fms_access_token') || '';
      tokenType = sessionStorage.getItem('fms_token_type') || 'Bearer';
    } catch (_) {}

    var url = urls.download + '?path=' + encodeURIComponent(selectedFile);
    var headers = { 'X-Requested-With': 'XMLHttpRequest' };
    if (token) headers.Authorization = tokenType + ' ' + token;
    FMS.blockUI();

    window.fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      headers: headers
    }).then(function (res) {
      if (!res.ok) {
        return res.text().then(function (text) {
          var message = 'Download gagal (HTTP ' + res.status + ').';
          try {
            var payload = JSON.parse(text);
            if (payload && payload.message) message = payload.message;
          } catch (_) {}
          throw new Error(message);
        });
      }
      return res.blob();
    }).then(function (blob) {
      var objectUrl = URL.createObjectURL(blob);
      var anchor = document.createElement('a');
      anchor.href = objectUrl;
      anchor.download = selectedFile.split('/').pop();
      anchor.style.display = 'none';
      document.body.appendChild(anchor);
      anchor.click();
      document.body.removeChild(anchor);
      window.setTimeout(function () { URL.revokeObjectURL(objectUrl); }, 1000);
      FMS.toast('Download dimulai.', true);
    }).catch(function (error) {
      FMS.toast(error.message || 'Download gagal.', false);
    }).finally(function () {
      FMS.unblockUI();
    });
  });
  els.search.addEventListener('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function () { offset = 0; loadContent(false); }, 400);
  });
  els.searchClear.addEventListener('click', function () {
    els.search.value = '';
    offset = 0;
    loadContent(false);
    els.search.focus();
  });
  els.lineLimit.addEventListener('change', function () { offset = 0; loadContent(false); });
  els.prevPage.addEventListener('click', function () {
    offset = Math.max(0, offset - Number(els.lineLimit.value || 500));
    loadContent(false);
  });
  els.nextPage.addEventListener('click', function () {
    if (!hasMore) return;
    offset += Number(els.lineLimit.value || 500);
    loadContent(false);
  });

  window.addEventListener('beforeunload', function () {
    if (autoRefreshTimer) clearInterval(autoRefreshTimer);
  });

  loadFiles();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLogMonitor);
  } else {
    initLogMonitor();
  }
}());
</script>

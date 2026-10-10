/**
 * FMS Storage Browser
 * S3/MinIO-like object browser with folder navigation, upload, share, delete
 */
(function () {
  'use strict';

  const root = document.getElementById('fmsStorageRoot');
  if (!root) return;

  const api = root.dataset.apiUrl;
  const body = document.getElementById('fmsStorageObjects');
  const breadcrumb = document.getElementById('fmsStorageBreadcrumb');
  const bucketName = document.getElementById('fmsStorageBucketName');
  const uploadInput = document.getElementById('fmsStorageFile');
  const refreshBtn = document.getElementById('fmsStorageRefresh');
  let currentPrefix = '';

  function fmt(n) {
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
    parts.forEach((part, i) => {
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

  function load(prefix = '') {
    currentPrefix = prefix;
    FMS.get(api + '/objects', { prefix: prefix }, { blockUI: true })
      .then(function (result) {
        if (result.bucket) bucketName.textContent = result.bucket;
        renderBreadcrumb(prefix);
        body.innerHTML = '';
        (result.folders || []).forEach(f => {
          const tr = document.createElement('tr');
          tr.style.cursor = 'pointer';
          tr.innerHTML = '<td><i class="ph-duotone ph-folder me-2 text-warning"></i>' + f.name + '</td><td>Folder</td><td>—</td><td>—</td><td class="text-end"></td>';
          tr.onclick = () => load(f.prefix);
          body.appendChild(tr);
        });
        (result.files || []).forEach(o => {
          const tr = document.createElement('tr');
          tr.innerHTML = '<td><i class="ph-duotone ph-file me-2"></i>' + o.name + '</td><td>' + o.mime + '</td><td>' + fmt(o.size) + '</td><td>' + o.updated_at + '</td><td class="text-end"><button class="btn btn-sm btn-outline-info me-1 preview" data-key="' + encodeURIComponent(o.key) + '">Preview</button><button class="btn btn-sm btn-outline-primary me-1 share" data-key="' + encodeURIComponent(o.key) + '">Share</button><button class="btn btn-sm btn-outline-danger delete" data-key="' + encodeURIComponent(o.key) + '">Hapus</button></td>';
          body.appendChild(tr);
        });
        if (!body.children.length) {
          body.innerHTML = '<tr><td colspan="5" class="text-center text-muted">' + (prefix ? 'Folder kosong.' : 'Bucket kosong.') + '</td></tr>';
        }
      })
      .catch(function (error) {
        FMS.toast((error && error.message) || 'Gagal memuat object.', false);
      });
  }

  breadcrumb.addEventListener('click', function (e) {
    if (e.target.tagName === 'A' && e.target.dataset.prefix !== undefined) {
      e.preventDefault();
      load(e.target.dataset.prefix);
    }
  });

  refreshBtn.addEventListener('click', function () {
    load(currentPrefix);
  });

  uploadInput.addEventListener('change', function (e) {
    if (!e.target.files[0]) return;
    const form = new FormData();
    form.append('file', e.target.files[0]);
    form.append('prefix', currentPrefix);
    FMS.post(api + '/objects', form, { blockUI: true })
      .then(function () {
        FMS.toast('Upload berhasil.', true);
        load(currentPrefix);
      })
      .catch(function (error) {
        FMS.toast((error && error.message) || 'Upload gagal.', false);
      });
    e.target.value = '';
  });

  body.addEventListener('click', function (e) {
    const key = decodeURIComponent(e.target.dataset.key || '');
    if (!key) return;

    if (e.target.classList.contains('delete')) {
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
              load(currentPrefix);
            });
        }
      });
    }

    if (e.target.classList.contains('share')) {
      FMS.post(api + '/share', { key: key, ttl: 3600 }, { blockUI: true })
        .then(function (result) {
          if (result.url) {
            navigator.clipboard.writeText(result.url);
            FMS.toast('Share URL disalin ke clipboard.', true);
          }
        })
        .catch(function (error) {
          FMS.toast((error && error.message) || 'Share gagal.', false);
        });
    }

    if (e.target.classList.contains('preview')) {
      FMS.get(api + '/objects/metadata', { key: key }, { blockUI: true })
        .then(function (meta) {
          const modal = document.createElement('div');
          modal.className = 'modal fade';
          modal.innerHTML = '<div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Object Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><dl class="row"><dt class="col-sm-4">Key</dt><dd class="col-sm-8"><code style="word-break:break-all">' + meta.key + '</code></dd><dt class="col-sm-4">Type</dt><dd class="col-sm-8">' + meta.mime + '</dd><dt class="col-sm-4">Size</dt><dd class="col-sm-8">' + fmt(meta.size) + '</dd><dt class="col-sm-4">Modified</dt><dd class="col-sm-8">' + meta.updated_at + '</dd><dt class="col-sm-4">ETag</dt><dd class="col-sm-8"><code>' + meta.etag + '</code></dd><dt class="col-sm-4">SHA256</dt><dd class="col-sm-8"><code style="word-break:break-all;font-size:11px">' + meta.sha256 + '</code></dd></dl></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><a href="' + meta.download_url + '" class="btn btn-primary" target="_blank">Download</a></div></div></div>';
          document.body.appendChild(modal);
          const bsModal = new bootstrap.Modal(modal);
          bsModal.show();
          modal.addEventListener('hidden.bs.modal', function () {
            document.body.removeChild(modal);
          });
        })
        .catch(function (error) {
          FMS.toast((error && error.message) || 'Failed loading metadata.', false);
        });
    }
  });

  load();
})();

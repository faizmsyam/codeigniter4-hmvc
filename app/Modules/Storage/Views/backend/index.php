<div class="row g-3">
  <div class="col-12">
    <div class="card custom-card">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <h4 class="mb-1">FMS Storage</h4>
          <p class="text-muted mb-0">Bucket: <span id="fmsStorageBucketName">—</span> · User: <?= esc((string)($storageUser['username'] ?? '')) ?></p>
        </div>
        <a href="<?= esc(site_url('fms-storage/logout'), 'attr') ?>" class="btn btn-outline-danger">Logout</a>
      </div>
    </div>
  </div>
  <div class="col-12">
    <div class="card custom-card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0">Object Browser</h5>
          <nav id="fmsStorageBreadcrumb" aria-label="breadcrumb" class="mt-1" style="display:none">
            <ol class="breadcrumb mb-0"></ol>
          </nav>
        </div>
        <div>
          <label class="btn btn-primary mb-0">
            <i class="ph-duotone ph-upload-simple"></i> Upload
            <input id="fmsStorageFile" type="file" hidden>
          </label>
          <button id="fmsStorageRefresh" class="btn btn-light ms-2"><i class="ph-duotone ph-arrows-clockwise"></i></button>
        </div>
      </div>
      <div class="card-body">
        <div id="fmsStorageMessage" class="alert d-none"></div>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>Nama</th>
                <th>Tipe</th>
                <th>Ukuran</th>
                <th>Diperbarui</th>
                <th class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody id="fmsStorageObjects">
              <tr><td colspan="5" class="text-center text-muted">Memuat...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const api = '<?= esc(site_url('api/v1/storage'), 'js') ?>';
  const body = document.getElementById('fmsStorageObjects');
  const breadcrumb = document.getElementById('fmsStorageBreadcrumb');
  const bucketName = document.getElementById('fmsStorageBucketName');
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
      .then(function(result) {
        if (result.bucket) bucketName.textContent = result.bucket;
        renderBreadcrumb(prefix);
        body.innerHTML = '';
        (result.folders || []).forEach(f => {
          const tr = document.createElement('tr');
          tr.style.cursor = 'pointer';
          tr.innerHTML = '<td><i class="ph-duotone ph-folder me-2 text-warning"></i>' + f.name + '/</td><td>—</td><td>—</td><td>—</td><td></td>';
          tr.onclick = () => load(f.prefix + '/');
          body.appendChild(tr);
        });
        (result.files || []).forEach(o => {
          const tr = document.createElement('tr');
          tr.innerHTML = '<td><i class="ph-duotone ph-file me-2"></i>' + o.name + '</td><td>' + o.mime + '</td><td>' + fmt(o.size) + '</td><td>' + o.updated_at + '</td><td class="text-end"><button class="btn btn-sm btn-outline-primary share" data-key="' + encodeURIComponent(o.key) + '">Share</button> <button class="btn btn-sm btn-outline-danger delete" data-key="' + encodeURIComponent(o.key) + '">Hapus</button></td>';
          body.appendChild(tr);
        });
        if (!body.children.length) {
          body.innerHTML = '<tr><td colspan="5" class="text-center text-muted">' + (prefix ? 'Folder kosong.' : 'Bucket kosong.') + '</td></tr>';
        }
      })
      .catch(function(error) {
        FMS.toast((error && error.message) || 'Gagal memuat object.', false);
      });
  }

  breadcrumb.addEventListener('click', function(e) {
    if (e.target.tagName === 'A' && e.target.dataset.prefix !== undefined) {
      e.preventDefault();
      load(e.target.dataset.prefix);
    }
  });

  document.getElementById('fmsStorageRefresh').addEventListener('click', function() {
    load(currentPrefix);
  });

  document.getElementById('fmsStorageFile').addEventListener('change', function(e) {
    if (!e.target.files[0]) return;
    const form = new FormData();
    form.append('file', e.target.files[0]);
    form.append('prefix', currentPrefix);
    FMS.post(api + '/objects', form, { blockUI: true })
      .then(function() {
        FMS.toast('Upload berhasil.', true);
        load(currentPrefix);
      })
      .catch(function(error) {
        FMS.toast((error && error.message) || 'Upload gagal.', false);
      });
    e.target.value = '';
  });

  body.addEventListener('click', function(e) {
    const key = decodeURIComponent(e.target.dataset.key || '');
    if (e.target.classList.contains('delete')) {
      if (confirm('Hapus object ini?')) {
        FMS.del(api + '/objects', { key: key }, { blockUI: true })
          .then(function() {
            load(currentPrefix);
          })
          .catch(function(error) {
            FMS.toast((error && error.message) || 'Hapus gagal.', false);
          });
      }
    }
    if (e.target.classList.contains('share')) {
      FMS.post(api + '/share', { key: key, ttl: 3600 }, { blockUI: true })
        .then(function(result) {
          navigator.clipboard.writeText(result.url);
          FMS.toast('Share URL disalin.', true);
        })
        .catch(function(error) {
          FMS.toast((error && error.message) || 'Share gagal.', false);
        });
    }
  });

  load();
});
</script>

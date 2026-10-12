<div class="row g-3" id="fmsStorageRoot" data-api-url="<?= esc(site_url('api/v1/storage'), 'attr') ?>">
  <div class="col-12">
    <div class="card custom-card fms-storage-card">
      <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h4 class="mb-1 fms-storage-title">FMS Storage</h4>
          <p class="text-muted mb-0">Bucket: <span id="fmsStorageBucketName">&mdash;</span> &middot; User: <?= esc((string) ($storageUser['username'] ?? '')) ?></p>
        </div>
        <span class="badge bg-primary-subtle text-primary fms-storage-badge"><i class="ph-duotone ph-shield-check me-1"></i>Session aktif</span>
      </div>
    </div>
  </div>
  <div class="col-12">
    <div id="fmsStorageDropzone" class="fms-storage-dropzone">
      <span class="fms-storage-dropzone-icon"><i class="ph-duotone ph-cloud-arrow-up"></i></span>
      <div class="fw-semibold fms-storage-title">Tarik file ke sini untuk upload</div>
      <small class="text-muted">File akan masuk ke folder aktif &middot; klik untuk memilih file</small>
    </div>
  </div>
  <div class="col-12">
    <div class="card custom-card fms-storage-card">
      <div class="card-header fms-storage-toolbar fms-storage-toolbar-head row">
        <div class="fms-storage-toolbar-title col-md-12 mb-2">
          <h5 class="mb-0 fms-storage-title">Object Browser</h5>
          <nav id="fmsStorageBreadcrumb" aria-label="Breadcrumb folder" class="mt-1">
            <ol class="breadcrumb mb-0"></ol>
          </nav>
        </div>
        <div class="fms-storage-toolbar-actions col-md-12">
          <div class="input-group input-group-sm fms-storage-search">
            <span class="input-group-text"><i class="ph-duotone ph-magnifying-glass"></i></span>
            <input id="fmsStorageSearch" type="search" class="form-control" placeholder="Cari object..." aria-label="Cari object">
          </div>
          <select id="fmsStorageSort" class="form-select form-select-sm fms-storage-sort" aria-label="Urutkan">
            <option value="name">Nama</option>
            <option value="size">Ukuran</option>
            <option value="updated_at">Diperbarui</option>
            <option value="mime">Tipe</option>
          </select>
          <?php $perms = $userPermissions ?? []; ?>
          <?php if (in_array('storage.upload', $perms, true)): ?>
          <label class="btn btn-primary btn-sm fms-storage-upload mb-0">
            <i class="ph-duotone ph-upload-simple me-1"></i>Upload
            <input id="fmsStorageFile" type="file" multiple hidden>
          </label>
          <?php endif; ?>
          <?php if (in_array('storage.edit', $perms, true)): ?>
          <button id="fmsStorageNewFolder" type="button" class="btn btn-outline-primary btn-sm" title="Buat folder" aria-label="Buat folder">
            <i class="ph-duotone ph-folder-simple-plus me-1"></i>Folder
          </button>
          <?php endif; ?>

          <button id="fmsStorageRefresh" type="button" class="btn btn-outline-secondary btn-sm fms-storage-icon-btn" title="Muat ulang" aria-label="Muat ulang"><i class="ph-duotone ph-arrows-clockwise"></i></button>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle fms-storage-table">
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
        <div id="fmsStoragePagination" class="mt-3"></div>
      </div>
    </div>
  </div>
</div>

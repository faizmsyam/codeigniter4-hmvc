<div class="row g-3" id="fmsStorageRoot" data-api-url="<?= esc(site_url('api/v1/storage'), 'attr') ?>">
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

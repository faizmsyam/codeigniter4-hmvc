<div class="row g-3" id="fmsStorageAuditRoot" data-api-url="<?= esc(site_url('api/v1/storage'), 'attr') ?>">
  <div class="col-12">
    <div class="card custom-card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0">Activity Log</h5>
          <small class="text-muted">Jejak audit upload, hapus, rename, share, dan perubahan setting</small>
        </div>
        <button id="fmsStorageAuditRefresh" class="btn btn-outline-secondary btn-sm" title="Muat ulang" aria-label="Muat ulang"><i class="ph-duotone ph-arrows-clockwise me-1"></i>Muat ulang</button>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle text-wrap">
            <thead>
              <tr>
                <th style="width:170px;">Waktu</th>
                <th style="width:140px;">User</th>
                <th style="width:150px;">Event</th>
                <th style="min-width:200px;">Object</th>
                <th style="min-width:250px;">Detail</th>
              </tr>
            </thead>
            <tbody id="fmsStorageAudit">
              <tr><td colspan="5" class="text-center text-muted">Memuat...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

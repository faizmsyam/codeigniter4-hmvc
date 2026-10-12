<div class="row justify-content-center" id="fmsStorageSettingsRoot" data-password-url="<?= esc(site_url('api/v1/storage/profile/password'), 'attr') ?>">
  <div class="col-lg-8">
    <div class="card custom-card">
      <div class="card-header"><h5 class="mb-0">FMS Storage Settings</h5></div>
      <div class="card-body">
        <h6>Account</h6>
        <dl class="row">
          <dt class="col-sm-4">Username</dt><dd class="col-sm-8"><?= esc((string) ($storageUser['username'] ?? '')) ?></dd>
          <dt class="col-sm-4">Nama</dt><dd class="col-sm-8"><?= esc((string) ($storageUser['full_name'] ?? '')) ?></dd>
          <dt class="col-sm-4">Role</dt><dd class="col-sm-8"><?= !empty($storageUser['is_super_admin']) ? 'Super Administrator' : 'Storage User' ?></dd>
        </dl>
        <hr>
        <h6>Ganti Password</h6>
        <form id="fmsStoragePassword">
          <div class="mb-3">
            <label class="form-label" for="fmsStorageCurrentPassword">Password saat ini</label>
            <div class="input-group">
              <input class="form-control" id="fmsStorageCurrentPassword" name="current_password" type="password" placeholder="Password saat ini" required>
              <button class="btn btn-outline-secondary" type="button" data-fms-password-toggle="fmsStorageCurrentPassword" aria-label="Tampilkan password"><i class="ph-duotone ph-eye"></i></button>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="fmsStorageNewPassword">Password baru</label>
            <div class="input-group">
              <input class="form-control" id="fmsStorageNewPassword" name="new_password" type="password" minlength="12" placeholder="Password baru, minimal 12 karakter" required>
              <button class="btn btn-outline-secondary" type="button" data-fms-password-toggle="fmsStorageNewPassword" aria-label="Tampilkan password"><i class="ph-duotone ph-eye"></i></button>
            </div>
          </div>
          <button class="btn btn-primary" type="submit">Simpan Password</button>
        </form>
      </div>
    </div>
  </div>

  <?php if (!empty($canManageUsers)): ?>
  <div class="col-lg-8">
    <div class="card custom-card mt-3" id="fmsStorageMimeRoot" data-settings-url="<?= esc(site_url('api/v1/storage/settings'), 'attr') ?>">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Tipe MIME Diizinkan</h5>
        <button class="btn btn-light btn-sm" type="button" id="fmsStorageMimeReload" title="Muat ulang" aria-label="Muat ulang"><i class="ph-duotone ph-arrows-clockwise"></i></button>
      </div>
      <div class="card-body">
        <p class="text-muted mb-2">Centang tipe file yang boleh di-upload ke storage. Hanya tipe tercentang yang diterima.</p>
        <div id="fmsStorageMimeList" class="fms-storage-mime-list" role="group" aria-label="Daftar MIME">
          <div class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>Memuat...</div>
        </div>
        <div class="d-flex align-items-center gap-2 mt-3">
          <button class="btn btn-primary btn-sm" type="button" id="fmsStorageMimeSave"><i class="ph-duotone ph-floppy-disk me-1"></i>Simpan MIME</button>
          <button class="btn btn-outline-secondary btn-sm" type="button" id="fmsStorageMimeAll">Pilih semua</button>
          <button class="btn btn-outline-secondary btn-sm" type="button" id="fmsStorageMimeNone">Kosongkan</button>
        </div>
        <div class="small text-muted mt-2" id="fmsStorageMimeNote"></div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

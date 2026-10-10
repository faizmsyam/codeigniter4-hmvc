<?php
/* Meta title diatur oleh FMSStorageAuthController sebelum layout dirender. */
?>
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center bg-light">
  <div class="card custom-card shadow-sm" style="max-width:460px;width:100%">
    <div class="card-body p-4">
      <div class="text-center mb-4"><i class="ph-duotone ph-cloud-arrow-up fs-48 text-primary"></i><h3 class="mt-2">FMS Storage</h3><p class="text-muted">Login storage terpisah dari FMS Admin</p></div>
      <?php if ($message=session()->getFlashdata('storage_error')): ?><div class="alert alert-danger"><?= esc($message) ?></div><?php endif; ?>
      <?php if ($message=session()->getFlashdata('storage_success')): ?><div class="alert alert-success"><?= esc($message) ?></div><?php endif; ?>
      <form method="post" action="<?= esc(site_url('fms-storage/login'),'attr') ?>">
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label" for="identifier">Username / Email</label><input class="form-control" id="identifier" name="identifier" required autocomplete="username"></div>
        <div class="mb-4"><label class="form-label" for="password">Password</label><input class="form-control" type="password" id="password" name="password" required autocomplete="current-password"></div>
        <button class="btn btn-primary w-100" type="submit">Masuk ke Storage</button>
      </form>
      <div class="text-center mt-3"><a href="<?= esc(site_url('fms-auth/in'),'attr') ?>" class="text-muted small">Kembali ke FMS Admin</a></div>
    </div>
  </div>
</div>

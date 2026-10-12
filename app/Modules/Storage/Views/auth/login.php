<?php
/* Meta title diatur oleh FMSStorageAuthController sebelum layout dirender. */
?>
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center">
  <button type="button" id="fmsStorageThemeToggle" class="btn btn-light shadow-sm position-fixed top-0 end-0 m-3" aria-label="Aktifkan dark mode" title="Dark mode">
    <i class="ph-duotone ph-moon"></i>
  </button>
  <div class="card custom-card shadow-sm" style="max-width:460px;width:100%">
    <div class="card-body p-4">
      <div class="text-center mb-4">
        <h3 class="mt-2">FMS Storage</h3>
        <p class="text-muted">Login untuk pengelolaan storage</p>
      </div>
      <?php if (!empty($error)): ?><div class="alert alert-danger"><?= esc($error) ?></div><?php endif; ?>
      <?php if (!empty($success)): ?><div class="alert alert-success"><?= esc($success) ?></div><?php endif; ?>
      <?php if ($message = session()->getFlashdata('storage_error')): ?><div class="alert alert-danger"><?= esc($message) ?></div><?php endif; ?>
      <?php if ($message = session()->getFlashdata('storage_success')): ?><div class="alert alert-success"><?= esc($message) ?></div><?php endif; ?>
      <form method="post" action="<?= esc(site_url('fms-storage/login'), 'attr') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= esc((string) ($next ?? ''), 'attr') ?>">
        <div class="mb-3"><label class="form-label" for="identifier">Username / Email</label><input class="form-control" id="identifier" name="identifier" required autocomplete="username"></div>
        <div class="mb-4"><label class="form-label" for="password">Password</label>
          <div class="input-group"><input class="form-control" type="password" id="password" name="password" required autocomplete="current-password"><button class="btn btn-outline-secondary" type="button" data-fms-password-toggle="password" aria-label="Tampilkan password"><i class="ph-duotone ph-eye"></i></button></div>
        </div>
        <button class="btn btn-primary w-100" type="submit">Masuk ke Storage</button>
      </form>
      <div class="text-center mt-3"><a href="<?= esc(site_url('fms-auth/in'), 'attr') ?>" class="text-muted small">Kembali ke FMS Admin</a></div>
    </div>
  </div>
</div>
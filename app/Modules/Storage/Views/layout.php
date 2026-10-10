<?php ob_start('minifier') ?>
<!doctype html>
<html lang="id" data-storage-shell="true">
<?= view('_partials/head', [
    'meta' => $meta ?? [],
    'appBrand' => $appBrand ?? [],
    'fmsLinks' => $fmsLinks ?? '',
    'fmsScripts' => $fmsScripts ?? '',
]) ?>
<body class="fms-storage-shell">
  <div class="fms-storage-app min-vh-100 d-flex flex-column">
    <header class="fms-storage-header border-bottom bg-white">
      <div class="container-fluid px-4 py-3 d-flex align-items-center justify-content-between">
        <a class="text-decoration-none d-flex align-items-center gap-2" href="<?= esc(site_url('fms-storage'), 'attr') ?>">
          <span class="avatar avatar-md bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"><i class="ph-duotone ph-hard-drives fs-20"></i></span>
          <span><strong class="d-block text-dark">FMS Storage</strong><small class="text-muted">Private object storage</small></span>
        </a>
        <div class="d-flex align-items-center gap-3">
          <span class="text-muted small"><i class="ph-duotone ph-user me-1"></i><?= esc((string) ($storageUser['username'] ?? '')) ?></span>
          <a class="btn btn-outline-danger btn-sm" href="<?= esc(site_url('fms-storage/logout'), 'attr') ?>"><i class="ph-duotone ph-sign-out me-1"></i>Keluar</a>
        </div>
      </div>
    </header>
    <div class="flex-grow-1 d-flex">
      <aside class="fms-storage-sidebar border-end bg-light-subtle p-3" style="width:250px;">
        <div class="text-uppercase text-muted fw-semibold small mb-2">Storage</div>
        <nav class="nav nav-pills flex-column gap-1">
          <a class="nav-link <?= ($storagePage ?? '') === 'browser' ? 'active' : 'text-dark' ?>" href="<?= esc(site_url('fms-storage'), 'attr') ?>"><i class="ph-duotone ph-folder-open me-2"></i>Object Browser</a>
          <a class="nav-link <?= ($storagePage ?? '') === 'settings' ? 'active' : 'text-dark' ?>" href="<?= esc(site_url('fms-storage/settings'), 'attr') ?>"><i class="ph-duotone ph-gear me-2"></i>Pengaturan</a>
        </nav>
        <hr>
        <div class="small text-muted"><div class="fw-semibold mb-1">Storage session</div><div class="d-flex justify-content-between"><span>Status</span><span class="text-success">Aktif</span></div></div>
      </aside>
      <main class="flex-grow-1 p-4">
        <div class="container-fluid px-0" style="max-width:1500px;">
          <?= $content ?? '' ?>
        </div>
      </main>
    </div>
    <footer class="border-top bg-white py-3 text-center small text-muted">FMS Storage · <?= date('Y') ?></footer>
  </div>
  <?= $fmsBottomScripts ?? '' ?>
</body>
</html>
<?php ob_end_flush() ?>

<?php ob_start('minifier') ?>
<!doctype html>
<html lang="id" data-storage-shell="true" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="transparent" data-width="fullwidth" data-menu-styles="transparent" data-page-style="flat" data-toggled="close" data-vertical-style="doublemenu" data-bg-img="bgimg1">
<?= view('_partials/head', [
    'meta' => $meta ?? [],
    'appBrand' => $appBrand ?? [],
    'fmsLinks' => $fmsLinks ?? '',
    'fmsScripts' => $fmsScripts ?? '',
]) ?>
<body class="fms-storage-shell">
  <div class="fms-storage-app min-vh-100 d-flex flex-column">
    <header class="fms-storage-header border-bottom bg-body">
      <div class="container-fluid px-4 py-3 d-flex align-items-center justify-content-between">
        <a class="text-decoration-none d-flex align-items-center gap-2" href="<?= esc(site_url('fms-storage'), 'attr') ?>">
          <span class="avatar avatar-md bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"><i class="ph-duotone ph-hard-drives fs-20"></i></span>
          <span><strong class="d-block text-body">FMS Storage</strong><small class="text-muted">Private object storage</small></span>
        </a>
        <div class="d-flex align-items-center gap-2">
          <button type="button" id="fmsStorageSidebarToggle" class="btn btn-outline-secondary btn-sm d-lg-none fms-storage-sidebar-toggle" aria-label="Buka menu" title="Menu"><i class="ph-duotone ph-list"></i></button>
          <span class="text-muted small d-none d-sm-inline"><i class="ph-duotone ph-user me-1"></i><?= esc((string) ($storageUser['username'] ?? '')) ?></span>
          <button type="button" id="fmsStorageThemeToggle" class="btn btn-outline-secondary btn-sm" aria-label="Aktifkan dark mode" title="Dark mode"><i class="ph-duotone ph-moon"></i></button>
          <a class="btn btn-outline-danger btn-sm" href="<?= esc(site_url('fms-storage/logout'), 'attr') ?>"><i class="ph-duotone ph-sign-out me-1"></i>Keluar</a>
        </div>
      </div>
    </header>
    <div class="flex-grow-1 d-flex fms-storage-body">
      <aside class="fms-storage-sidebar border-end bg-body-tertiary p-3" id="fmsStorageSidebar" style="width:250px;">
        <div class="text-uppercase text-muted fw-semibold small mb-2">Storage</div>
        <nav class="nav nav-pills flex-column gap-1">
          <a class="nav-link <?= ($storagePage ?? '') === 'browser' ? 'active' : 'text-body' ?>" href="<?= esc(site_url('fms-storage'), 'attr') ?>"><i class="ph-duotone ph-folder-open me-2"></i>Object Browser</a>
          <a class="nav-link <?= ($storagePage ?? '') === 'settings' ? 'active' : 'text-body' ?>" href="<?= esc(site_url('fms-storage/settings'), 'attr') ?>"><i class="ph-duotone ph-gear me-2"></i>Pengaturan</a>
          <?php if (!empty($canManageUsers)): ?><a class="nav-link <?= ($storagePage ?? '') === 'users' ? 'active' : 'text-body' ?>" href="<?= esc(site_url('fms-storage/users'), 'attr') ?>"><i class="ph-duotone ph-users-three me-2"></i>Users &amp; Access</a><?php endif; ?>
        </nav>
        <hr>
        <div class="small text-muted"><div class="fw-semibold mb-1">Storage session</div><div class="d-flex justify-content-between"><span>Status</span><span class="text-success">Aktif</span></div></div>
      </aside>
      <div class="fms-storage-sidebar-backdrop" id="fmsStorageSidebarBackdrop"></div>
      <main class="flex-grow-1 p-4 fms-storage-main">
        <div class="container-fluid px-0" style="max-width:1500px;">
          <nav class="fms-storage-page-breadcrumb mb-3" aria-label="Breadcrumb">
            <ol class="breadcrumb mb-0">
              <li class="breadcrumb-item"><a href="<?= esc(site_url('fms-storage'), 'attr') ?>"><i class="ph-duotone ph-hard-drives me-1"></i>FMS Storage</a></li>
              <?php if (($storagePage ?? 'browser') === 'users'): ?>
                <li class="breadcrumb-item active" aria-current="page">Users &amp; Access</li>
              <?php elseif (($storagePage ?? 'browser') === 'settings'): ?>
                <li class="breadcrumb-item active" aria-current="page">Pengaturan</li>
              <?php else: ?>
                <li class="breadcrumb-item active" aria-current="page">Object Browser</li>
              <?php endif; ?>
            </ol>
          </nav>
          <div class="fms-storage-page-content">
            <?= $content ?? '' ?>
          </div>
        </div>
      </main>
    </div>
    <footer class="border-top bg-body py-3 text-center small text-muted">FMS Storage · <?= date('Y') ?></footer>
  </div>
  <?= $fmsBottomScripts ?? '' ?>
</body>
</html>
<?php ob_end_flush() ?>

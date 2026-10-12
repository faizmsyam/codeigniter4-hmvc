<?php ob_start('minifier') ?>
<!doctype html>
<html lang="id" data-storage-auth="true" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="transparent" data-width="fullwidth" data-menu-styles="transparent" data-page-style="flat" data-toggled="close" data-vertical-style="doublemenu" data-bg-img="bgimg1">
<?= view('_partials/head', [
    'meta' => $meta ?? [],
    'appBrand' => $appBrand ?? [],
    'fmsLinks' => $fmsLinks ?? '',
    'fmsScripts' => $fmsScripts ?? '',
]) ?>
<body class="fms-storage-auth">
  <main class="min-vh-100 d-flex align-items-center justify-content-center p-3">
    <div class="w-100" style="max-width:460px;">
      <?= $content ?? '' ?>
      <div class="text-center small text-muted mt-3">FMS Storage · <?= date('Y') ?></div>
    </div>
  </main>
  <?= $fmsBottomScripts ?? '' ?>
</body>
</html>
<?php ob_end_flush() ?>
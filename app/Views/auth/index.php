<?php ob_start("minifier") ?>
<!DOCTYPE html>
<html lang="en" lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="transparent"
  data-width="fullwidth" data-menu-styles="transparent" data-page-style="flat" data-toggled="close"
  data-vertical-style="doublemenu" data-toggled="double-menu-open">

<?php echo view('_partials/head') ?>

<body>
  <?php
    try {
      echo $content ?? '';
    } catch (\Exception $error) {
      
    }
  ?>
  <?php echo isset($fmsBottomScripts) ? $fmsBottomScripts : '' ?>
  <script src="<?php echo esc(base_url('assets/fms/js/pwa.js?v=2'), 'attr') ?>"></script>
</body>

</html>
<?php ob_end_flush() ?>
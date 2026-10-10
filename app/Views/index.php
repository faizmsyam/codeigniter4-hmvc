<?php ob_start("minifier") ?>
<!DOCTYPE html>
<html lang="en">

<?php echo view('_partials/head') ?>

<body>
  <?php
    try {
      echo $content ?? '';
    } catch (\Exception $error) {
      
    }
  ?>
  <script src="<?php echo esc(base_url('assets/fms/js/pwa.js?v=2'), 'attr') ?>"></script>
</body>

</html>
<?php ob_end_flush() ?>
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
</body>

</html>
<?php ob_end_flush() ?>
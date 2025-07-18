<?php ob_start("minifier") ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title><?php echo esc($meta['title']) ?></title>
  <meta name="description" content="<?php echo esc($meta['description']) ?>">
  <meta name="keywords" content="<?php echo esc($meta['keywords']) ?>">
  <meta name="author" content="<?php echo esc($meta['author']) ?>">
  <meta name="signature" content="<?php echo esc($meta['signature']) ?>">

  <meta property="og:title" content="<?php echo esc($meta['title']) ?>">
  <meta property="og:description" content="<?php echo esc($meta['description']) ?>">
  <meta property="og:image" content="<?php echo esc($meta['image']) ?>">
  <meta property="og:url" content="<?php echo esc($meta['url']) ?>">

  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?php echo esc($meta['title']) ?>">
  <meta name="twitter:description" content="<?php echo esc($meta['description']) ?>">
  <meta name="twitter:image" content="<?php echo esc($meta['image']) ?>">

  <meta name="<?php echo csrf_token() ?>" content="<?php echo csrf_hash() ?>">

  <link rel="shortcut icon" type="image/png" href="/favicon.ico">

  <?php echo isset($fmsLinks) ? $fmsLinks : '' ?>

  <?php echo isset($fmsScripts) ? $fmsScripts : '' ?>
</head>

<body style="overflow: hidden;">
  <?php
    try {
      echo $content;
    } catch (\Exception $error) {
      
    }
  ?>
</body>

</html>
<?php ob_end_flush() ?>
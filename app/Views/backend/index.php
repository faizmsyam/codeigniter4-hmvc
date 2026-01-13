<?php ob_start("minifier") ?>
<!DOCTYPE html>
<html lang="en" lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="transparent"
  data-width="fullwidth" data-menu-styles="transparent" data-page-style="flat" data-toggled="close"
  data-vertical-style="doublemenu" data-toggled="double-menu-open">

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

<body>
  <div class="progress-top-bar"></div>

  <?php echo view('backend/_partials/switcher') ?>

  <div id="loader">
    <img src="<?php echo assets('img/media', 'loader.svg') ?>" alt="">
  </div>

  <div class="page">
    <?php echo view('backend/_partials/header') ?>
    <?php echo view('backend/_partials/sidebar') ?>

    <div class="main-content app-content">
      <div class="container-fluid page-container main-body-container">

        <div class="page-header-breadcrumb mb-3">
          <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">App</h1>
            <ol class="breadcrumb mb-0">
              <li class="breadcrumb-item"><a href="<?php echo site_url('app') ?>">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">App</li>
            </ol>
          </div>
        </div>

        <?php
        try {
          echo $content;
        } catch (\Exception $error) {
        }
        ?>
      </div>
    </div>
    <?php echo view('backend/_partials/footer') ?>

    <div class="modal fade" id="header-responsive-search" tabindex="-1" aria-labelledby="header-responsive-search"
      aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-body">
            <div class="input-group">
              <input type="text" class="form-control border-end-0" placeholder="Search Anything ..."
                aria-label="Search Anything ..." aria-describedby="button-addon2">
              <button class="btn btn-primary" type="button" id="button-addon2"><i
                  class="bi bi-search"></i></button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="scrollToTop">
    <span class="arrow lh-1"><i class="ti ti-arrow-big-up fs-18"></i></span>
  </div>
  <div id="responsive-overlay"></div>

  <?php echo isset($fmsBottomScripts) ? $fmsBottomScripts : '' ?>
</body>

</html>
<?php ob_end_flush() ?>
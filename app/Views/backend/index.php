<?php ob_start("minifier") ?>
<!DOCTYPE html>
<html lang="en" lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="transparent"
  data-width="fullwidth" data-menu-styles="transparent" data-page-style="flat" data-toggled="close"
  data-vertical-style="doublemenu" data-toggled="double-menu-open" data-bg-img="bgimg1">

<?php echo view('_partials/head') ?>

<body>
  <div class="progress-top-bar"></div>

  <?php echo view('backend/_partials/switcher') ?>

  <div id="loader">
    <img src="<?php echo fmsAssets('img/media', 'loader.svg') ?>" alt="">
  </div>

  <div class="page">
    <?php echo view('backend/_partials/header') ?>
    <?php echo view('backend/_partials/sidebar') ?>
    <div class="main-content app-content">
      <div class="container-fluid page-container main-body-container">
        <?php echo view('backend/_partials/breadcrumb') ?>
        <?php
          try {
            echo $content ?? '';
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
              <input type="text" class="form-control border-end-0" placeholder="Search ..." id="header-search-mobile"
                aria-label="Search ..." aria-describedby="button-addon2" autocomplete="off">
              <button class="btn btn-primary" type="button" id="button-addon2"><i
                  class="bi bi-search"></i></button>
            </div>
            <div class="header-search-dropdown" id="header-search-results-mobile" style="display:none"></div>
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
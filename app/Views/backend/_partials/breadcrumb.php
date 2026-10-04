<div class="page-header-breadcrumb mb-3">
  <div class="d-flex align-center justify-content-between flex-wrap">

    <h1 class="page-title fw-medium fs-18 mb-0">
      <?php echo esc($pageTitle ?? '') ?>
    </h1>

    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item">
        <a href="<?php echo site_url(ROUTE_ADMIN) ?>">Home</a>
      </li>

      <?php if (!empty($breadcrumbs)) : ?>
        <?php $breadcrumbCount = count($breadcrumbs); ?>
        <?php foreach ($breadcrumbs as $index => $crumb): ?>
          <?php
            $crumbUrl = trim((string) ($crumb->url ?? ''));
            if ($crumbUrl !== '' && $crumbUrl !== '#' && !str_starts_with($crumbUrl, 'http')) {
                $crumbUrl = site_url(trim(ROUTE_ADMIN . '/' . trim($crumbUrl, '/'), '/'));
            }
          ?>
          <?php if ($index === $breadcrumbCount - 1): ?>
            <li class="breadcrumb-item active" aria-current="page">
              <?php echo esc($crumb->name) ?>
            </li>
          <?php else: ?>
            <li class="breadcrumb-item">
              <a href="<?php echo $crumbUrl !== '' ? esc($crumbUrl, 'attr') : '#' ?>">
                <?php echo esc($crumb->name) ?>
              </a>
            </li>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </ol>

  </div>
</div>
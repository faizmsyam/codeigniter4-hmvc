<aside class="app-sidebar sticky" id="sidebar">

  <!-- Start::main-sidebar-header -->
  <div class="main-sidebar-header">
    <a href="<?php echo site_url() ?>" class="header-logo">
      <img src="<?php echo esc((string)(($appBrand['logo_url'] ?? '') ?: fmsAssets('img/media', 'logo.png'))) ?>" alt="<?php echo esc((string)($appBrand['name'] ?? 'logo')) ?>" class="desktop-logo">
      <img src="<?php echo esc((string)(($appBrand['logo_light_url'] ?? '') ?: fmsAssets('img/media', 'logo.png'))) ?>" alt="<?php echo esc((string)($appBrand['name'] ?? 'logo')) ?>" class="toggle-dark">
      <img src="<?php echo esc((string)(($appBrand['logo_light_url'] ?? '') ?: fmsAssets('img/media', 'logo.png'))) ?>" alt="<?php echo esc((string)($appBrand['name'] ?? 'logo')) ?>" class="desktop-dark">
      <img src="<?php echo esc((string)(($appBrand['logo_url'] ?? '') ?: fmsAssets('img/media', 'logo.png'))) ?>" alt="<?php echo esc((string)($appBrand['name'] ?? 'logo')) ?>" class="toggle-logo">
    </a>
  </div>
  <!-- End::main-sidebar-header -->

  <!-- Start::main-sidebar -->
  <div class="main-sidebar" id="sidebar-scroll">

    <!-- Start::nav -->
    <nav class="main-menu-container nav nav-pills flex-column sub-open">
      <div class="slide-left" id="slide-left">
        <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24"
          viewBox="0 0 24 24">
          <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path>
        </svg>
      </div>

      <ul class="main-menu">

        <!-- category -->
        <li class="slide__category">
          <span class="category-name">Main</span>
        </li>

        <?php if (isset($menus) && $menus) : ?>
          <?php foreach ($menus as $menu): ?>
            <?php
            $hasChild = isset($menu->children);
            $isActive = fmsMenuIsActive($menu->url, ($currentUri ?? ''));
            $isOpen   = $hasChild && fmsMenuHasActiveChild($menu, ($currentUri ?? ''));
            $menuUrl = (string) ($menu->url ?? '');
            $isExternalUrl = str_starts_with($menuUrl, 'http://') || str_starts_with($menuUrl, 'https://');
            $resolvedMenuUrl = $isExternalUrl ? $menuUrl : site_url($menuUrl);
            $targetAttribute = ! empty($menu->target_blank) ? ' target="_blank" rel="noopener noreferrer"' : '';
            ?>

            <?php if (!$hasChild): ?>
              <!-- ================= MENU TANPA ANAK ================= -->
              <li class="slide">
                <a href="<?php echo esc($resolvedMenuUrl) ?>"<?php echo $targetAttribute ?>
                  class="side-menu__item <?php echo $isActive ? 'active' : '' ?>">
                  <?php echo fmsMenuSvgWithClass($menu->icon, 'side-menu__icon') ?>
                  <span class="side-menu__label"><?php echo esc($menu->name) ?></span>
                </a>
              </li>

            <?php else: ?>
              <!-- ================= MENU DENGAN ANAK ================= -->
              <li class="slide has-sub <?php echo ($isActive || $isOpen) ? 'open active' : '' ?>">
                <a href="javascript:void(0);"
                  class="side-menu__item <?php echo ($isActive || $isOpen) ? 'active' : '' ?>">
                  <?php echo fmsMenuSvgWithClass($menu->icon, 'side-menu__icon') ?>
                  <span class="side-menu__label"><?php echo esc($menu->name) ?></span>
                  <i class="ri-arrow-right-s-line side-menu__angle"></i>
                </a>

                <ul class="slide-menu child1">

                  <li class="slide side-menu__label1">
                    <a href="javascript:void(0)"><?php echo esc($menu->name) ?></a>
                  </li>

                  <?php foreach ($menu->children as $child): ?>
                    <?php
                    $childHasSub = isset($child->children);
                    $childActive = fmsMenuIsActive($child->url, ($currentUri ?? ''));
                    $childOpen   = $childHasSub && fmsMenuHasActiveChild($child, ($currentUri ?? ''));
                    ?>

                    <?php if (!$childHasSub): ?>
                      <!-- ===== CHILD LEVEL 1 TANPA SUB ===== -->
                      <?php
                      $childMenuUrl = (string) ($child->url ?? '');
                      $isChildExternalUrl = str_starts_with($childMenuUrl, 'http://') || str_starts_with($childMenuUrl, 'https://');
                      $resolvedChildMenuUrl = $isChildExternalUrl ? $childMenuUrl : site_url($childMenuUrl);
                      $childTargetAttribute = ! empty($child->target_blank) ? ' target="_blank" rel="noopener noreferrer"' : '';
                      ?>
                      <li class="slide <?php echo $childActive ? 'open active' : '' ?>">
                        <a href="<?php echo esc($resolvedChildMenuUrl) ?>"<?php echo $childTargetAttribute ?>
                          class="side-menu__item <?php echo $childActive ? 'active' : '' ?>">
                          <?php echo fmsMenuSvgWithClass($child->icon, 'side-menu-doublemenu__icon') ?>
                          <?php echo esc($child->name) ?>
                        </a>
                      </li>

                    <?php else: ?>
                      <!-- ===== CHILD LEVEL 1 DENGAN SUB ===== -->
                      <li class="slide has-sub <?php echo ($childActive || $childOpen) ? 'open active' : '' ?>">
                        <a href="javascript:void(0);"
                          class="side-menu__item <?php echo ($childActive || $childOpen) ? 'active' : '' ?>">
                          <?php echo fmsMenuSvgWithClass($child->icon, 'side-menu-doublemenu__icon') ?>
                          <?php echo esc($child->name) ?>
                          <i class="ri-arrow-right-s-line side-menu__angle"></i>
                        </a>

                        <ul class="slide-menu child2">
                          <?php foreach ($child->children as $sub): ?>
                            <?php
                            $subActive = fmsMenuIsActive($sub->url, ($currentUri ?? ''));
                            $subMenuUrl = (string) ($sub->url ?? '');
                            $isSubExternalUrl = str_starts_with($subMenuUrl, 'http://') || str_starts_with($subMenuUrl, 'https://');
                            $resolvedSubMenuUrl = $isSubExternalUrl ? $subMenuUrl : site_url($subMenuUrl);
                            $subTargetAttribute = ! empty($sub->target_blank) ? ' target="_blank" rel="noopener noreferrer"' : '';
                            ?>
                            <li class="slide <?php echo $subActive ? 'active' : '' ?>">
                              <a href="<?php echo esc($resolvedSubMenuUrl) ?>"<?php echo $subTargetAttribute ?>
                                class="side-menu__item <?php echo $subActive ? 'active' : '' ?>">
                                <?php echo esc($sub->name) ?>
                              </a>
                            </li>
                          <?php endforeach; ?>
                        </ul>
                      </li>
                    <?php endif; ?>

                  <?php endforeach; ?>
                </ul>
              </li>
            <?php endif; ?>

          <?php endforeach; ?>
        <?php endif; ?>
      </ul>

      <ul class="doublemenu_bottom-menu main-menu mb-0 border-top">
        <!-- Start::slide -->
        <li class="slide">
          <a href="javascript:void(0);" class="side-menu__item layout-setting-doublemenu">
            <span class="light-layout">
              <!-- Start::header-link-icon -->
              <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon"
                viewBox="0 0 256 256">
                <rect width="256" height="256" fill="none" />
                <path
                  d="M108.11,28.11A96.09,96.09,0,0,0,227.89,147.89,96,96,0,1,1,108.11,28.11Z"
                  opacity="0.2" />
                <path
                  d="M108.11,28.11A96.09,96.09,0,0,0,227.89,147.89,96,96,0,1,1,108.11,28.11Z"
                  fill="none" stroke="currentColor" stroke-linecap="round"
                  stroke-linejoin="round" stroke-width="16" />
              </svg>
              <!-- End::header-link-icon -->
            </span>
            <span class="dark-layout">
              <!-- Start::header-link-icon -->
              <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon"
                viewBox="0 0 256 256">
                <rect width="256" height="256" fill="none" />
                <circle cx="128" cy="128" r="56" opacity="0.2" />
                <line x1="128" y1="40" x2="128" y2="32" fill="none" stroke="currentColor"
                  stroke-linecap="round" stroke-linejoin="round" stroke-width="16" />
                <circle cx="128" cy="128" r="56" fill="none" stroke="currentColor"
                  stroke-linecap="round" stroke-linejoin="round" stroke-width="16" />
                <line x1="64" y1="64" x2="56" y2="56" fill="none" stroke="currentColor"
                  stroke-linecap="round" stroke-linejoin="round" stroke-width="16" />
                <line x1="64" y1="192" x2="56" y2="200" fill="none" stroke="currentColor"
                  stroke-linecap="round" stroke-linejoin="round" stroke-width="16" />
                <line x1="192" y1="64" x2="200" y2="56" fill="none" stroke="currentColor"
                  stroke-linecap="round" stroke-linejoin="round" stroke-width="16" />
                <line x1="192" y1="192" x2="200" y2="200" fill="none" stroke="currentColor"
                  stroke-linecap="round" stroke-linejoin="round" stroke-width="16" />
                <line x1="40" y1="128" x2="32" y2="128" fill="none" stroke="currentColor"
                  stroke-linecap="round" stroke-linejoin="round" stroke-width="16" />
                <line x1="128" y1="216" x2="128" y2="224" fill="none" stroke="currentColor"
                  stroke-linecap="round" stroke-linejoin="round" stroke-width="16" />
                <line x1="216" y1="128" x2="224" y2="128" fill="none" stroke="currentColor"
                  stroke-linecap="round" stroke-linejoin="round" stroke-width="16" />
              </svg>
              <!-- End::header-link-icon -->
            </span>
            <span class="side-menu__label">Theme Settings</span>
          </a>
        </li>
        <!-- End::slide -->
        <!-- Start::slide -->
        <li class="slide">
          <a href="javascript:void(0);" class="side-menu__item" data-fms-logout="<?php echo esc(site_url('api/v1/auth/logout'), 'attr') ?>" data-fms-login="<?php echo esc(site_url('fms-auth/in'), 'attr') ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256">
              <rect width="256" height="256" fill="none" />
              <path
                d="M48,40H208a16,16,0,0,1,16,16V200a16,16,0,0,1-16,16H48a0,0,0,0,1,0,0V40A0,0,0,0,1,48,40Z"
                opacity="0.2" />
              <polyline points="112 40 48 40 48 216 112 216" fill="none" stroke="currentColor"
                stroke-linecap="round" stroke-linejoin="round" stroke-width="16" />
              <line x1="112" y1="128" x2="224" y2="128" fill="none" stroke="currentColor"
                stroke-linecap="round" stroke-linejoin="round" stroke-width="16" />
              <polyline points="184 88 224 128 184 168" fill="none" stroke="currentColor"
                stroke-linecap="round" stroke-linejoin="round" stroke-width="16" />
            </svg>
            <span class="side-menu__label">Logout</span>
          </a>
        </li>
        <!-- End::slide -->
        <!-- Start::slide -->
        <li class="slide">
          <a href="<?php echo site_url(ROUTE_ADMIN . '/profile') ?>" class="side-menu__item p-1 rounded-circle mb-0">
            <span class="avatar avatar-md avatar-rounded">
              <?php $headerAvatarUrl = trim((string) ($backendUser['avatar_url'] ?? '')); ?>
              <?php $headerAvatarFallback = trim((string) ($backendUser['avatar'] ?? '')); ?>
              <?php $headerAvatarDisplay = $headerAvatarUrl !== '' ? $headerAvatarUrl : (str_starts_with($headerAvatarFallback, 'http') ? $headerAvatarFallback : ''); ?>
              <?php if ($headerAvatarDisplay !== ''): ?>
                <img src="<?php echo esc($headerAvatarDisplay) ?>" alt="">
              <?php else: ?>
                <span class="avatar avatar-sm avatar-rounded bg-primary text-fixed-white fw-bold d-flex align-items-center justify-content-center">
                  <?php echo esc(strtoupper(substr((string) ($backendUser['full_name'] ?? $backendUser['username'] ?? 'U'), 0, 1))) ?>
                </span>
              <?php endif; ?>
            </span>
          </a>
        </li>
        <!-- End::slide -->
      </ul>
      <div class="slide-right" id="slide-right"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191"
          width="24" height="24" viewBox="0 0 24 24">
          <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"></path>
        </svg>
      </div>
    </nav>
    <!-- End::nav -->

  </div>
  <!-- End::main-sidebar -->

</aside>
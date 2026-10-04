<?php

$brandIdentity = ['name' => 'FMS App Starter'];
try {
    $brandIdentity = (new \App\Modules\Brand\Services\FMSBrandIdentityService())->get();
} catch (\Throwable) {
    /* Error rendering must survive unavailable brand storage. */
}

$brandName = trim((string) ($brandIdentity['name'] ?? '')) ?: 'FMS App Starter';
$brandLogoUrl = (string) ($brandIdentity['logo_url'] ?? base_url('brand/logo'));
$brandLogoLightUrl = (string) ($brandIdentity['logo_light_url'] ?? base_url('brand/logo-light'));
$brandFaviconUrl = (string) ($brandIdentity['favicon_url'] ?? base_url('brand/favicon'));

$backendPrimaryRgb = '98,95,253';
$backendThemeStylesheets = [
    'assets/fms/css/styles.css',
    'assets/fms/css/styles-dark.css',
];

foreach ($backendThemeStylesheets as $stylesheet) {
    $stylesheetPath = ROOTPATH . 'public/' . $stylesheet;
    if (! is_file($stylesheetPath)) {
        continue;
    }

    $stylesheetSource = @file_get_contents($stylesheetPath);
    if ($stylesheetSource === false) {
        continue;
    }

    $primaryMatches = [];
    if (preg_match_all('/--primary-rgb:\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})/', $stylesheetSource, $primaryMatches, PREG_SET_ORDER) > 0) {
        $rgbMatches = $primaryMatches[array_key_last($primaryMatches)];
        $backendPrimaryRgb = ((int) $rgbMatches[1]) . ',' . ((int) $rgbMatches[2]) . ',' . ((int) $rgbMatches[3]);
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="fms-primary-rgb" content="<?php echo esc($backendPrimaryRgb) ?>">
  <meta name="theme-color" content="<?php echo esc('#'.implode('', array_map(function($v){return str_pad(dechex((int)$v),2,'0',STR_PAD_LEFT);}, explode(',', $backendPrimaryRgb)))) ?>">
  <style>
    :root {
      --fms-backend-primary-rgb: <?php echo esc($backendPrimaryRgb) ?>;
    }
  </style>
  <title><?php echo esc($meta['title'] ?? lang('Errors.pageNotFound')) ?></title>
  <meta name="fms-brand-name" content="<?php echo esc($brandName) ?>">
  <meta name="fms-logo" content="<?php echo esc($brandLogoUrl) ?>">
  <meta name="fms-logo-light" content="<?php echo esc($brandLogoLightUrl) ?>">
  <link rel="icon" href="<?php echo esc($brandFaviconUrl, 'attr') ?>">
  <link rel="shortcut icon" href="<?php echo esc($brandFaviconUrl, 'attr') ?>">
  <link rel="stylesheet" href="<?php echo esc(base_url('assets/fms/css/fms-error.css'), 'attr') ?>">
  <style>
    .sr-only {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border: 0;
    }
  </style>
  <script>
    (function (window, document) {
      'use strict';

      var root = document.documentElement;
      var brandName = document.querySelector('meta[name="fms-brand-name"]')?.content || 'FMS App Starter';

      function readPreference() {
        try {
          var saved = window.localStorage.getItem('fmsthemepreference');
          if (saved === 'light' || saved === 'dark') return saved;
          if (window.localStorage.getItem('fmsdarktheme') || window.localStorage.getItem('bodyBgRGB')) return 'dark';
        } catch (error) { /* Penyimpanan browser tidak tersedia */ }
        return 'system';
      }

      function resolvedTheme(preference) {
        if (preference === 'light' || preference === 'dark') return preference;
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
      }

      function applyTheme(preference) {
        var resolved = resolvedTheme(preference);
        root.setAttribute('data-fms-error-theme', resolved);
        return resolved;
      }

      window.FMSErrorTheme = {
        brandName: brandName,
        getPreference: readPreference,
        apply: function () {
          return applyTheme(readPreference());
        },
        setPreference: function (preference) {
          var normalized = preference === 'light' || preference === 'dark' ? preference : 'system';
          try {
            if (normalized === 'system') {
              window.localStorage.removeItem('fmsthemepreference');
              window.localStorage.removeItem('fmsdarktheme');
            } else {
              window.localStorage.setItem('fmsthemepreference', normalized);
              if (normalized === 'dark') window.localStorage.setItem('fmsdarktheme', 'true');
              else window.localStorage.removeItem('fmsdarktheme');
            }
          } catch (error) { /* Tema tetap diterapkan untuk sesi berjalan */ }
          return applyTheme(normalized);
        }
      };

      window.FMSErrorTheme.apply();

      if (window.matchMedia) {
        var followSystem = function () {
          if (readPreference() === 'system') applyTheme('system');
        };
        if (typeof window.matchMedia.addEventListener === 'function') {
          window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', followSystem);
        } else if (typeof window.matchMedia.addListener === 'function') {
          window.matchMedia('(prefers-color-scheme: dark)').addListener(followSystem);
        }
      }
    }(window, document));
    (function (window, document) {
      'use strict';

      var root = document.documentElement;
      var primaryMeta = document.querySelector('meta[name="fms-primary-rgb"]');

      function isValidRgb(value) {
        return /^\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*$/.test(String(value || ''));
      }

      function splitRgb(value) {
        return String(value)
          .split(',')
          .map(function (part) {
            return Math.max(0, Math.min(255, parseInt(part.trim(), 10) || 0));
          });
      }

      function applyPrimary(rgb) {
        var channels = splitRgb(rgb);

        root.style.setProperty('--fms-error-primary', 'rgb(' + channels[0] + ',' + channels[1] + ',' + channels[2] + ')');
        root.style.setProperty('--fms-error-primary-soft', 'rgba(' + channels[0] + ',' + channels[1] + ',' + channels[2] + ', 0.14)');
        root.style.setProperty(
          '--fms-error-primary-strong',
          'rgba(' + channels[0] + ',' + channels[1] + ',' + channels[2] + ', 0.24)'
        );
      }

      function backendPrimary() {
        var backendValue = null;

        try {
          backendValue = getComputedStyle(root).getPropertyValue('--fms-backend-primary-rgb');
        } catch (error) {
          backendValue = null;
        }

        return isValidRgb(backendValue) ? backendValue.trim() : '98,95,253';
      }

      function syncPrimary() {
        var localPrimary = window.localStorage ? window.localStorage.getItem('primaryRGB') : null;
        var primaryRgb = isValidRgb(localPrimary) ? localPrimary.trim() : backendPrimary();
        applyPrimary(primaryRgb);
        return primaryRgb;
      }

      window.FMSErrorPrimary = {
        apply: syncPrimary,
        current: syncPrimary()
      };

      if (primaryMeta) {
        var metaObserver = new MutationObserver(syncPrimary);
        metaObserver.observe(primaryMeta, { attributes: true, attributeFilter: ['content'] });
      }
    }(window, document));
  </script>
</head>
<body class="fms-error-page">
  <div class="fms-error-grid" aria-hidden="true"></div>

  <main class="fms-error-shell">
    <div class="fms-error-toolbar">
      <a class="fms-error-brand" href="<?php echo esc(base_url(), 'attr') ?>">
        <img class="fms-error-brand-logo" src="<?php echo esc($brandLogoUrl, 'attr') ?>" alt="<?php echo esc($brandName, 'attr') ?>" width="26" height="26" loading="lazy">
        <img class="fms-error-brand-logo fms-error-brand-logo-light" src="<?php echo esc($brandLogoLightUrl, 'attr') ?>" alt="<?php echo esc($brandName, 'attr') ?>" width="26" height="26" loading="lazy">
        <span><?php echo esc($brandName) ?></span>
      </a>

      <button type="button" class="fms-error-theme" id="fmsErrorThemeToggle" aria-label="Ganti tema">
        <span id="fmsErrorThemeIcon" aria-hidden="true">Tema</span>
        <span id="fmsErrorThemeLabel">Memuat...</span>
      </button>
    </div>

    <div class="fms-error-card">
      <div class="fms-error-visual" aria-hidden="true">
        <div class="fms-error-orbit"></div>
        <div class="fms-error-code"><?php echo esc($code ?? 404) ?></div>
      </div>

      <div class="fms-error-body">
        <div class="fms-error-kicker"><?php echo esc($meta['kicker'] ?? lang('Errors.errorKicker')) ?></div>
        <h1 class="fms-error-title"><?php echo esc($meta['title'] ?? lang('Errors.pageNotFound')) ?></h1>
        <p class="fms-error-message">
          <?php if (ENVIRONMENT !== 'production') : ?>
            <?php echo nl2br(esc($message)) ?>
          <?php else : ?>
            <?php echo esc($meta['production_message'] ?? lang('Errors.sorryCannotFind')) ?>
          <?php endif; ?>
        </p>

        <div class="fms-error-actions">
          <button type="button" class="fms-error-button fms-error-button-primary" style="color: white;" onclick="history.back()">
            Kembali
          </button>
          <a class="fms-error-button" href="<?php echo esc(base_url(), 'attr') ?>">
            Beranda
          </a>
          <button type="button" class="fms-error-button" id="fmsErrorRetry">
            Muat ulang
          </button>
        </div>

        <div class="fms-error-footer">
          <span><?php echo esc(lang('Errors.statusCode') ?? 'Kode status') ?>: <strong><?php echo esc($code ?? 404) ?></strong></span>
          <span>Klik tombol kanan atas untuk ganti tema</span>
        </div>
      </div>
    </div>
  </main>

  <script>
    (function (window, document) {
      'use strict';

      var toggle = document.getElementById('fmsErrorThemeToggle');
      var icon = document.getElementById('fmsErrorThemeIcon');
      var label = document.getElementById('fmsErrorThemeLabel');
      var retry = document.getElementById('fmsErrorRetry');
      var theme = window.FMSErrorTheme || {};

      function updateLabel(resolved) {
        if (!icon || !label) return;
        if (resolved === 'dark') {
          icon.textContent = '🌙';
          label.textContent = 'Gelap aktif';
          return;
        }
        icon.textContent = '☀️';
        label.textContent = 'Terang aktif';
      }

      updateLabel(theme.apply ? theme.apply() : 'light');

      if (toggle) {
        toggle.addEventListener('click', function () {
          var current = theme.getPreference ? theme.getPreference() : 'system';
          var next = current === 'dark' ? 'light' : 'dark';
          updateLabel(theme.setPreference ? theme.setPreference(next) : next);
        });
      }

      if (retry) {
        retry.addEventListener('click', function () {
          window.location.reload();
        });
      }
    }(window, document));
  </script>
</body>
</html>

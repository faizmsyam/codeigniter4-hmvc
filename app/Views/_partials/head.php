<?php

$fmsPrimaryRgb = '98,95,253';
$fmsStylesheetPath = ROOTPATH . 'public/assets/fms/css/styles.css';
if (is_file($fmsStylesheetPath)) {
    $fmsStylesheet = @file_get_contents($fmsStylesheetPath);
    $fmsPrimaryMatches = [];
    if ($fmsStylesheet !== false && preg_match_all('/--primary-rgb:\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})/', $fmsStylesheet, $fmsPrimaryMatches, PREG_SET_ORDER) > 0) {
        $fmsPrimaryMatch = $fmsPrimaryMatches[array_key_last($fmsPrimaryMatches)];
        $fmsPrimaryRgb = ((int) $fmsPrimaryMatch[1]) . ',' . ((int) $fmsPrimaryMatch[2]) . ',' . ((int) $fmsPrimaryMatch[3]);
    }
}

$fmsPrimaryHex = '#' . implode('', array_map(
    static fn (string $channel): string => str_pad(dechex(max(0, min(255, (int) trim($channel)))), 2, '0', STR_PAD_LEFT),
    explode(',', $fmsPrimaryRgb),
));
?>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title><?php echo esc($meta['title'] ?? '') ?></title>
  <meta name="description" content="<?php echo esc($meta['description'] ?? '') ?>">
  <meta name="keywords" content="<?php echo esc($meta['keywords'] ?? '') ?>">
  <meta name="author" content="<?php echo esc($meta['author'] ?? '') ?>">
  <meta name="signature" content="<?php echo esc($meta['signature'] ?? '') ?>">
  <meta name="theme-color" content="<?php echo esc($fmsPrimaryHex) ?>" id="fmsThemeColorMeta">
  <meta name="fms-primary-rgb" content="<?php echo esc($fmsPrimaryRgb) ?>">
  <meta name="fms-base-url" content="<?php echo esc(rtrim(base_url(), '/') . '/', 'attr') ?>">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="<?php echo esc((string) (($appBrand['name'] ?? '') ?: 'FMS App Starter')) ?>">
  <link rel="manifest" href="<?php echo esc(site_url('manifest.webmanifest'), 'attr') ?>">
  <link rel="apple-touch-icon" sizes="192x192" href="<?php echo esc(site_url('brand/pwa-icon/192'), 'attr') ?>">

  <meta property="og:title" content="<?php echo esc($meta['title'] ?? '') ?>">
  <meta property="og:description" content="<?php echo esc($meta['description'] ?? '') ?>">
  <meta property="og:image" content="<?php echo esc($meta['image'] ?? '') ?>">
  <meta property="og:url" content="<?php echo esc($meta['url'] ?? '') ?>">

  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?php echo esc($meta['title'] ?? '') ?>">
  <meta name="twitter:description" content="<?php echo esc($meta['description'] ?? '') ?>">
  <meta name="twitter:image" content="<?php echo esc($meta['image'] ?? '') ?>">

  <meta name="<?php echo csrf_token() ?>" content="<?php echo csrf_hash() ?>">
  <meta name="fms-logo" content="<?php echo esc((string) (($appBrand['logo_url'] ?? '') ?: fmsAssets('img/media', 'logo.png'))) ?>">
  <meta name="fms-brand-name" content="<?php echo esc((string) (($appBrand['name'] ?? '') ?: 'FMS App Starter')) ?>">

  <script>
    (function (window, document) {
      'use strict';

      var root = document.documentElement;
      var systemTheme = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
      var defaultPrimaryHex = <?php echo json_encode($fmsPrimaryHex) ?>;
      var defaultPrimaryRgb = <?php echo json_encode($fmsPrimaryRgb) ?>;

      function primaryToHex(rgbValue) {
        var parts = String(rgbValue || '').split(',');
        if (parts.length !== 3) return null;

        var hex = '';
        for (var i = 0; i < parts.length; i++) {
          var channel = Math.max(0, Math.min(255, parseInt(parts[i].trim(), 10) || 0));
          hex += ('0' + channel.toString(16)).slice(-2);
        }

        return '#' + hex;
      }

      function readPrimaryRgb() {
        try {
          var saved = window.localStorage.getItem('primaryRGB');
          if (saved && /^\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*$/.test(saved)) return saved;
        } catch (error) { /* Penyimpanan browser tidak tersedia */ }
        return defaultPrimaryRgb;
      }

      function applyPrimaryMeta(rgbValue) {
        var meta = document.getElementById('fmsThemeColorMeta');
        var hex = primaryToHex(rgbValue) || defaultPrimaryHex;
        if (meta) meta.setAttribute('content', hex);
        root.style.setProperty('--primary-rgb', rgbValue || defaultPrimaryRgb);
      }

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
        return systemTheme && systemTheme.matches ? 'dark' : 'light';
      }

      function applyTheme(preference) {
        var resolved = resolvedTheme(preference);
        root.setAttribute('data-theme-mode', resolved);
        root.setAttribute('data-fms-theme-preference', preference);
        return resolved;
      }

      window.FMSTheme = {
        getPreference: readPreference,
        readPrimary: readPrimaryRgb,
        applyPrimary: function (rgbValue) {
          applyPrimaryMeta(rgbValue === undefined ? readPrimaryRgb() : rgbValue);
          return primaryToHex(rgbValue === undefined ? readPrimaryRgb() : rgbValue) || defaultPrimaryHex;
        },
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

      window.FMSTheme.apply();
      window.FMSTheme.applyPrimary();

      if (systemTheme) {
        var followSystem = function () {
          if (readPreference() === 'system') applyTheme('system');
        };
        if (typeof systemTheme.addEventListener === 'function') systemTheme.addEventListener('change', followSystem);
        else if (typeof systemTheme.addListener === 'function') systemTheme.addListener(followSystem);
      }
    }(window, document));
  </script>

  <link rel="shortcut icon" href="<?php echo esc((string) (($appBrand['favicon_url'] ?? '') ?: fmsAssets('img/media', 'favicon.ico'))) ?>">

  <?php echo isset($fmsLinks) ? $fmsLinks : '' ?>

  <?php echo isset($fmsScripts) ? $fmsScripts : '' ?>
</head>

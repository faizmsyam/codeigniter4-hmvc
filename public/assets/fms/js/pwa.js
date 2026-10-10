(function (window, document) {
  'use strict';

  if (!('serviceWorker' in navigator)) return;

  var standalone = Boolean(
    (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches)
    || window.navigator.standalone === true
  );
  var launchedFromPwa = new URLSearchParams(window.location.search).get('source') === 'pwa';
  var pwaContext = standalone || launchedFromPwa;
  var basePath = (document.querySelector('meta[name="fms-base-url"]') || {}).content || '/';
  var swUrl = new URL('sw.js', basePath).toString();

  function tellServiceWorker(active) {
    if (navigator.serviceWorker.controller) {
      navigator.serviceWorker.controller.postMessage({
        type: 'FMS_PWA_CONTEXT',
        active: Boolean(active)
      });
    }
  }

  window.addEventListener('load', function () {
    if (!pwaContext) {
      /* Browser biasa tidak mendaftarkan atau memperbarui sw.js. */
      tellServiceWorker(false);
      return;
    }

    navigator.serviceWorker.register(swUrl, { scope: basePath })
      .then(function (registration) {
        tellServiceWorker(true);
        registration.addEventListener('updatefound', function () {
          var worker = registration.installing;
          if (!worker) return;
          worker.addEventListener('statechange', function () {
            if (worker.state === 'installed' && navigator.serviceWorker.controller) {
              document.dispatchEvent(new CustomEvent('fms:pwa-update-ready', { detail: registration }));
            }
          });
        });
      })
      .catch(function (error) {
        console.warn('FMS PWA registration failed:', error);
      });
  });

  window.FMSPWA = {
    canInstall: function () { return false; },
    install: function () { return Promise.resolve(false); }
  };

  window.addEventListener('appinstalled', function () {
    document.documentElement.removeAttribute('data-pwa-installable');
  });
}(window, document));


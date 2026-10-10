/**
 * FMS.js — vanilla JavaScript utility library for the FMS backend.
 *
 * Global namespace: window.FMS
 *   FMS.ajax / FMS.get / FMS.post / FMS.put / FMS.patch / FMS.del
 *   FMS.toast / FMS.escape / FMS.debounce
 *   FMS.form / FMS.pagination
 *
 * Kontrak FMS.ajax (kompatibel Promise + jQuery-style):
 *   FMS.ajax({ url, method, data, async, cache, processData, contentType,
 *     headers, timeout, credentials, signal, fetchCache, dataType, context,
 *     beforeSend, success, error, complete, statusCode })
 *   async default true; async:false = synchronous XHR tetap kembalikan Promise settled.
 *   cache default true (perilaku lama GET); cache:false = cache-buster + no-store.
 *   beforeSend boleh return false untuk membatalkan request.
 */
(function (window, document) {
  'use strict';

  var FMS = window.FMS || {};

  function storedAccessToken() {
    try {
      return sessionStorage.getItem('fms_access_token') || '';
    } catch (storageError) {
      return '';
    }
  }

  function storedTokenType() {
    try {
      return sessionStorage.getItem('fms_token_type') || 'Bearer';
    } catch (storageError) {
      return 'Bearer';
    }
  }

  function storeAccessToken(payload) {
    var accessToken = payload && payload.access_token ? String(payload.access_token) : '';
    if (!accessToken) return false;

    try {
      sessionStorage.setItem('fms_access_token', accessToken);
      sessionStorage.setItem('fms_token_type', payload.token_type ? String(payload.token_type) : 'Bearer');
    } catch (storageError) {
      return false;
    }

    return true;
  }

  function clearStoredAccessToken() {
    try {
      sessionStorage.removeItem('fms_access_token');
      sessionStorage.removeItem('fms_token_type');
    } catch (storageError) { /* abaikan */ }
  }

  var CSRF_NAME = null;
  var CSRF_HASH = null;
  var CSRF_HEADER = 'FMS-CSRF-TOKEN';

  function metaCsrf() {
    var node = document.querySelector('meta[name="csrf_fms"]');
    if (!node) {
      var all = document.querySelectorAll('meta[name][content]');
      for (var i = 0; i < all.length; i++) {
        if (/csrf/i.test(all[i].getAttribute('name') || '')) { node = all[i]; break; }
      }
    }
    if (node) { CSRF_NAME = node.getAttribute('name'); CSRF_HASH = node.getAttribute('content'); }
    return node;
  }

  function cookieValue(name) {
    var encodedName = encodeURIComponent(name) + '=';
    var cookies = document.cookie ? document.cookie.split(';') : [];

    for (var index = 0; index < cookies.length; index++) {
      var cookie = cookies[index].trim();
      if (cookie.indexOf(encodedName) === 0) return decodeURIComponent(cookie.slice(encodedName.length));
    }

    return null;
  }

  function currentCsrfHash() {
    var cookieHash = cookieValue('csrf_cookie_fms');
    if (cookieHash) CSRF_HASH = cookieHash;

    return CSRF_HASH;
  }

  function updateCsrf(response) {
    if (!response || !response.headers) return;
    try {
      var fresh = response.headers.get('X-CSRF-TOKEN') || response.headers.get('FMS-CSRF-TOKEN');
      if (fresh) CSRF_HASH = fresh;
    } catch (error) { /* header not exposed */ }

    var rotated = cookieValue('csrf_cookie_fms');
    if (rotated) CSRF_HASH = rotated;
  }

  function selectorOf(target) {
    if (typeof target === 'string') return document.querySelector(target);
    return target || null;
  }

  FMS.escape = function (value) {
    if (value === null || value === undefined) return '';
    return String(value)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  };

  FMS.escapeHtml = FMS.escape;

  FMS.debounce = function (fn, wait) {
    var timer = null;
    return function () {
      var context = this, args = arguments;
      window.clearTimeout(timer);
      timer = window.setTimeout(function () { fn.apply(context, args); }, wait || 250);
    };
  };

  FMS.serialize = function (data) {
    if (typeof data === 'string') return data;
    var pairs = [];
    Object.keys(data || {}).forEach(function (key) {
      var value = data[key];
      if (value === null || value === undefined) return;
      if (Array.isArray(value)) {
        value.forEach(function (entry) { pairs.push(encodeURIComponent(key + '[]') + '=' + encodeURIComponent(entry)); });
        return;
      }
      pairs.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
    });
    return pairs.join('&');
  };

  var blockUIRequestCount = 0;
  var blockUIHideTimer = null;
  var blockUITypingTimer = null;
  var blockUIShownAt = 0;
  var BLOCK_UI_MINIMUM_MS = 0;

  function injectBlockUIStyles() {
    if (document.getElementById('fmsBlockUIStyles')) return;

    var style = document.createElement('style');
    style.id = 'fmsBlockUIStyles';
    style.textContent = `
      #fmsBlockUI {
        --fms-blockui-primary: var(--primary-color, #845adf);
        position: fixed;
        inset: 0;
        z-index: 20000;
        display: flex;
        align-items: center;
        justify-content: center;
        background: color-mix(in srgb, var(--default-body-bg-color, #040a18) 54%, transparent);
        backdrop-filter: blur(16px) saturate(135%);
        -webkit-backdrop-filter: blur(16px) saturate(135%);
        opacity: 0;
        pointer-events: none;
        will-change: opacity;
        transform: translateZ(0);
      }
      #fmsBlockUI.is-visible {
        opacity: 1;
        pointer-events: auto;
      }
      #fmsBlockUI .fms-blockui-panel {
        position: relative;
        isolation: isolate;
        display: flex;
        min-width: 190px;
        min-height: 170px;
        padding: 18px;
        border: 0;
        background: transparent;
        box-shadow: none;
        flex-direction: column;
        align-items: center;
        justify-content: center;
      }
      #fmsBlockUI .fms-blockui-panel::before {
        content: '';
        position: absolute;
        z-index: -1;
        width: 136px;
        height: 82px;
        top: 30px;
        border-radius: 42%;
        background: color-mix(in srgb, var(--fms-blockui-primary) 17%, transparent);
        filter: blur(30px);
        animation: fmsBlockUIGlow 1.65s ease-in-out infinite;
      }
      #fmsBlockUI .fms-blockui-logo-wrap {
        position: relative;
        display: flex;
        width: 102px;
        height: 102px;
        align-items: center;
        justify-content: center;
      }
      #fmsBlockUI .fms-blockui-ring {
        position: absolute;
        inset: 1px;
        border-radius: 50%;
        background: conic-gradient(from 0deg,
          transparent 0 18%,
          color-mix(in srgb, var(--fms-blockui-primary) 30%, transparent) 28%,
          var(--fms-blockui-primary) 48%,
          color-mix(in srgb, var(--fms-blockui-primary) 60%, transparent) 63%,
          transparent 75% 100%);
        -webkit-mask: radial-gradient(circle, transparent 64%, #000 66%, #000 70%, transparent 72%);
        mask: radial-gradient(circle, transparent 64%, #000 66%, #000 70%, transparent 72%);
        animation: fmsBlockUISpin 1.05s cubic-bezier(.55, .1, .45, .9) infinite;
      }
      #fmsBlockUI .fms-blockui-logo {
        display: block;
        width: 53px;
        height: 53px;
        object-fit: contain;
        color: var(--fms-blockui-primary);
        animation: fmsBlockUIFloat 1.55s ease-in-out infinite;
      }
      #fmsBlockUI .fms-blockui-brand {
        max-width: 230px;
        margin-top: 15px;
        overflow: hidden;
        color: var(--default-text-color, #1b2a41);
        font-size: 14px;
        font-weight: 600;
        letter-spacing: .04em;
        text-align: center;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      #fmsBlockUI .fms-blockui-status {
        display: flex;
        width: 100%;
        min-height: 19px;
        margin-top: 6px;
        align-items: center;
        justify-content: center;
        text-align: center;
      }
      #fmsBlockUI .fms-blockui-status-text {
        position: relative;
        display: inline-block;
        min-width: 1px;
        padding-inline: 3px;
        color: var(--text-muted, #6c757d);
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0;
        line-height: 1.5;
        text-transform: none;
        white-space: nowrap;
      }
      #fmsBlockUI .fms-blockui-status-text::after {
        content: '';
        position: absolute;
        top: .1em;
        right: 0;
        width: 1px;
        height: 1.25em;
        background: var(--fms-blockui-primary);
        animation: fmsBlockUICaret .7s step-end infinite;
      }
      body.fms-blockui-active {
        overflow: hidden !important;
        cursor: wait !important;
      }
      body.fms-blockui-active *,
      #fmsBlockUI,
      #fmsBlockUI * {
        cursor: wait !important;
      }
      @keyframes fmsBlockUISpin {
        to { transform: rotate(360deg); }
      }
      @keyframes fmsBlockUIFloat {
        0%, 100% { transform: translateY(2px) scale(.96); opacity: .86; }
        50% { transform: translateY(-3px) scale(1.04); opacity: 1; }
      }
      @keyframes fmsBlockUIGlow {
        0%, 100% { transform: scale(.84); opacity: .4; }
        50% { transform: scale(1.12); opacity: .78; }
      }
      @keyframes fmsBlockUICaret {
        0%, 45% { opacity: 1; }
        46%, 100% { opacity: 0; }
      }
      @media (prefers-reduced-motion: reduce) {
        #fmsBlockUI,
        #fmsBlockUI .fms-blockui-panel,
        #fmsBlockUI .fms-blockui-panel::before,
        #fmsBlockUI .fms-blockui-logo { transition: none; animation: none; }
        #fmsBlockUI .fms-blockui-status-text,
        #fmsBlockUI .fms-blockui-status-text::after {
          transition: none;
          animation: none;
        }
        #fmsBlockUI .fms-blockui-status-text::after { opacity: 0; }
        #fmsBlockUI .fms-blockui-ring { animation-duration: 1.8s; }
      }
    `;
    document.head.appendChild(style);
  }

  function stopBlockUITyping() {
    if (blockUITypingTimer) {
      window.clearTimeout(blockUITypingTimer);
      blockUITypingTimer = null;
    }
  }

  function startBlockUITyping(overlay) {
    var status = overlay ? overlay.querySelector('.fms-blockui-status-text') : null;
    if (!status) return;
    if (blockUITypingTimer) return;

    var fullText = 'Sedang memproses...';
    var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reducedMotion || status.dataset.typingComplete === 'true') {
      status.textContent = fullText;
      status.dataset.typingComplete = 'true';
      return;
    }

    var TYPE_INTERVAL_MS = 78;
    var startedAt = Date.now();
    var index = 0;
    status.textContent = '';

    function typeNext() {
      var elapsed = Date.now() - startedAt;
      index = Math.min(fullText.length, Math.max(1, Math.floor(elapsed / TYPE_INTERVAL_MS)));
      status.textContent = fullText.slice(0, index);

      if (index >= fullText.length) {
        status.textContent = fullText;
        status.dataset.typingComplete = 'true';
        blockUITypingTimer = null;
        return;
      }

      blockUITypingTimer = window.setTimeout(typeNext, 78);
    }

    blockUITypingTimer = window.setTimeout(typeNext, 78);
  }

  function ensureBlockUI() {
    var overlay = document.getElementById('fmsBlockUI');
    if (overlay) return overlay;

    injectBlockUIStyles();

    var logoMeta = document.querySelector('meta[name="fms-logo"]');
    var brandMeta = document.querySelector('meta[name="fms-brand-name"]');
    var logoSrc = logoMeta ? (logoMeta.getAttribute('content') || '') : '';
    var brandName = brandMeta ? (brandMeta.getAttribute('content') || 'FMS') : 'FMS';

    overlay = document.createElement('div');
    overlay.id = 'fmsBlockUI';
    overlay.setAttribute('role', 'status');
    overlay.setAttribute('aria-live', 'polite');
    overlay.setAttribute('aria-hidden', 'true');
    overlay.innerHTML = `
      <div class="fms-blockui-overlay fms-blockui-panel">
        <div class="fms-blockui-logo-wrap">
          <span class="fms-blockui-ring" aria-hidden="true"></span>
          ${logoSrc
            ? `<img class="fms-blockui-logo" src="${FMS.escapeHtml(logoSrc)}" alt="${FMS.escapeHtml(brandName)}">`
            : `<span class="fms-blockui-logo d-flex align-items-center justify-content-center fw-bold fs-3">F</span>`}
        </div>
        <div class="fms-blockui-brand">${FMS.escapeHtml(brandName)}</div>
        <div class="fms-blockui-status">
          <span class="fms-blockui-status-text">Sedang memproses...</span>
        </div>
      </div>
    `;
    document.body.appendChild(overlay);
    return overlay;
  }

  /* Pre-render agar klik AJAX pertama tidak menunggu pembuatan DOM/style/logo */
  if (document.body) {
    ensureBlockUI();
  } else {
    document.addEventListener('DOMContentLoaded', ensureBlockUI, { once: true });
  }

  FMS.blockUI = function () {
    blockUIRequestCount += 1;
    if (blockUIHideTimer) {
      window.clearTimeout(blockUIHideTimer);
      blockUIHideTimer = null;
    }

    var currentOverlay = document.getElementById('fmsBlockUI');
    if (currentOverlay && document.body.classList.contains('fms-blockui-active')) {
      /* Sudah tampil: tetap hitung request paralel supaya tidak hilang prematur */
      return currentOverlay;
    }

    var overlay = ensureBlockUI();
    blockUIShownAt = blockUIShownAt || Date.now();
    overlay.setAttribute('aria-hidden', 'false');
    document.body.classList.add('fms-blockui-active');
    startBlockUITyping(overlay);
    overlay.classList.add('is-visible');
    return overlay;
  };

  FMS.unblockUI = function (force) {
    blockUIRequestCount = force === true ? 0 : Math.max(0, blockUIRequestCount - 1);
    if (blockUIRequestCount > 0) return;

    var overlay = document.getElementById('fmsBlockUI');
    if (!overlay) return;

    var elapsed = blockUIShownAt ? Date.now() - blockUIShownAt : BLOCK_UI_MINIMUM_MS;
    var delay = force === true ? 0 : Math.max(0, BLOCK_UI_MINIMUM_MS - elapsed);
    blockUIHideTimer = window.setTimeout(function () {
      stopBlockUITyping();
      var status = overlay.querySelector('.fms-blockui-status-text');
      if (status) {
        status.textContent = '';
        delete status.dataset.typingComplete;
      }
      overlay.classList.remove('is-visible');
      overlay.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('fms-blockui-active');
      blockUIShownAt = 0;
      blockUIHideTimer = null;
    }, delay);
  };

  FMS.ajax = function (config) {
    var options = config || {};
    var hookContext = Object.prototype.hasOwnProperty.call(options, 'context') ? options.context : null;
    var beforeSendHook = typeof options.beforeSend === 'function' ? options.beforeSend : null;
    var successHook = typeof options.success === 'function' ? options.success : null;
    var errorHook = typeof options.error === 'function' ? options.error : null;
    var completeHook = typeof options.complete === 'function' ? options.complete : null;
    var statusCodeHooks = (options.statusCode && typeof options.statusCode === 'object') ? options.statusCode : {};
    var dataTypeOption = String(options.dataType || 'json').toLowerCase();
    var isAsync = options.async === undefined || options.async === null ? true : options.async !== false;
    var useCache = options.cache === undefined || options.cache === null ? true : options.cache !== false;
    var processData = options.processData === undefined || options.processData === null ? true : options.processData !== false;
    var requestHandle = { aborted: false, status: 0, response: null, abort: function () { requestHandle.aborted = true; } };
    if (typeof options.xhr === 'function') { try { options.xhr(requestHandle); } catch (callbackError) { /* abaikan */ } }
    var method = (options.method || options.type || 'GET').toUpperCase();
    var mutationMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];
    var isMutation = mutationMethods.indexOf(method) !== -1;
    /* Block UI default untuk semua request; caller dapat override blockUI:false. */
    var shouldBlockUI = options.blockUI !== undefined ? options.blockUI !== false : true;

    var url = options.url;
    var requestData = options.data;

    /* Semua mutation API wajib membawa JSON, termasuk DELETE tanpa payload. */
    if (isMutation && (requestData === undefined || requestData === null)) {
      requestData = {};
    }

    var headers = Object.assign({ 'X-Requested-With': 'XMLHttpRequest' }, options.headers || {});
    if (useCache === false) {
      if (!headers['Cache-Control'] && !headers['cache-control']) headers['Cache-Control'] = 'no-store';
      if (!headers.Pragma && !headers.pragma) headers.Pragma = 'no-cache';
    }
    var accessToken = storedAccessToken();
    if (accessToken && !headers.Authorization && !headers.authorization) {
      headers.Authorization = storedTokenType() + ' ' + accessToken;
    }
    var token = options.csrf === false ? null : (options.csrfToken || currentCsrfHash());
    var tokenName = options.csrfHeader || CSRF_HEADER;

    if (token) headers[tokenName] = token;

    var controller = typeof window.AbortController === 'function' ? new window.AbortController() : null;
    var timeout = Math.max(0, Number(options.timeout === undefined ? 30000 : options.timeout));
    var timeoutHandle = controller && timeout > 0 ? window.setTimeout(function () { controller.abort(); }, timeout) : null;
    var requestDataType = options.contentType || 'application/json';
    var fetchOptions = {
      method: method,
      credentials: options.credentials || 'same-origin',
      headers: headers,
      cache: useCache === false ? 'no-store' : (options.fetchCache || 'default')
    };
    if (options.signal) fetchOptions.signal = options.signal;
    else if (controller) fetchOptions.signal = controller.signal;

    if (requestData !== undefined && requestData !== null && method !== 'GET') {
      if (typeof window.FormData === 'function' && requestData instanceof window.FormData) {
        /* Multipart: teruskan FormData apa adanya, jangan set Content-Type manual */
        fetchOptions.body = requestData;
      } else {
        headers['Content-Type'] = requestDataType;
        fetchOptions.body = requestDataType === 'application/x-www-form-urlencoded'
          ? FMS.serialize(requestData)
          : JSON.stringify(requestData);
      }
    }

    /* Query string: processData:false membiarkan caller mengatur url sendiri. */
    if (method === 'GET' && processData !== false) {
      var query = {};
      if (options.data && typeof options.data === 'object') {
        Object.keys(options.data).forEach(function (key) { query[key] = options.data[key]; });
      }
      if (useCache === false) query._ = Date.now() + '' + Math.floor(Math.random() * 1000000);
      var pairs = Object.keys(query)
        .filter(function (key) { return query[key] !== null && query[key] !== '' && query[key] !== undefined; })
        .map(function (key) { return encodeURIComponent(key) + '=' + encodeURIComponent(query[key]); });
      if (pairs.length) url += (url.indexOf('?') === -1 ? '?' : '&') + pairs.join('&');
    }

    function fireBeforeSend() {
      if (requestHandle.aborted) return false;
      if (typeof beforeSendHook !== 'function') return true;
      var gate = beforeSendHook.call(hookContext, requestHandle, {
        url: url,
        method: method,
        headers: headers,
        data: requestData,
        async: isAsync,
        cache: useCache
      });
      return gate !== false && !requestHandle.aborted;
    }

    function fireSuccess(data, textStatus, responseLike) {
      if (typeof successHook !== 'function') return;
      successHook.call(hookContext, data, textStatus, responseLike || requestHandle);
      fireComplete(responseLike || requestHandle, textStatus);
    }

    function fireError(responseLike, error, textStatus) {
      if (typeof errorHook === 'function') {
        errorHook.call(hookContext, responseLike || requestHandle, error, textStatus);
      }
      var status = Number(error && error.status ? error.status : 0);
      if (status && typeof statusCodeHooks[status] === 'function') {
        statusCodeHooks[status].call(hookContext, responseLike || requestHandle, error, textStatus);
      }
      fireComplete(responseLike || requestHandle, textStatus);
    }

    function fireComplete(responseLike, textStatus) {
      if (typeof completeHook !== 'function') return;
      completeHook.call(hookContext, responseLike || requestHandle, textStatus === undefined ? 'complete' : textStatus);
    }

    function normalizeError(error) {
      if (error instanceof Error) return error;
      var wrapped = new Error(String(error || 'Request failed'));
      return wrapped;
    }

    function classifyResponse(response, text) {
      var payload = null;
      try { payload = text ? JSON.parse(text) : null; } catch (parseError) { payload = null; }
      if (payload && payload.csrf_token) CSRF_HASH = String(payload.csrf_token);
      var contentType = String(response && response.headers && typeof response.headers.get === 'function'
        ? (response.headers.get('Content-Type') || response.headers.get('content-type') || '')
        : '');
      if (!payload && response.ok && contentType.toLowerCase().indexOf('text/csv') !== -1) return text;
      if (!payload) {
        if (!response.ok) {
          var failure = normalizeError(new Error('HTTP ' + response.status));
          failure.status = response.status;
          throw failure;
        }
        return text;
      }
      if (!response.ok || payload.status === false) {
        var problem = new Error(payload.message || ('HTTP ' + response.status));
        problem.status = response.status;
        problem.payload = payload;
        problem.errors = (payload.data && payload.data.errors) ? payload.data.errors : {};
        throw problem;
      }
      return typeof payload.data === 'undefined' ? payload : payload.data;
    }

    function syncRequest() {
      if (shouldBlockUI) FMS.blockUI();

      if (!fireBeforeSend()) {
        var aborted = new Error(options.abortedErrorMessage || 'Permintaan dibatalkan.');
        aborted.aborted = true;
        aborted.status = 0;
        fireError(requestHandle, aborted, 'abort');
        fireComplete(requestHandle, 'abort');
        if (shouldBlockUI) FMS.unblockUI();
        return Promise.resolve(undefined).then(function () { throw aborted; });
      }

      var xhr = new window.XMLHttpRequest();
      xhr.open(method, url, false);
      Object.keys(headers).forEach(function (name) { xhr.setRequestHeader(name, headers[name]); });
      try {
        xhr.send(method === 'GET' ? null : (fetchOptions.body === undefined ? null : fetchOptions.body));
      } catch (sendError) {
        var network = new Error(options.networkErrorMessage || 'Tidak dapat terhubung ke server.');
        network.network = true;
        fireError(requestHandle, network, 'error');
        if (shouldBlockUI) FMS.unblockUI();
        return Promise.resolve(undefined).then(function () { throw network; });
      }

      requestHandle.status = xhr.status;
      requestHandle.response = xhr;

      if (xhr.status === 0) {
        var blocked = new Error(options.networkErrorMessage || 'Tidak dapat terhubung ke server.');
        blocked.network = true;
        blocked.status = 0;
        fireError(requestHandle, blocked, 'error');
        if (shouldBlockUI) FMS.unblockUI();
        return Promise.resolve(undefined).then(function () { throw blocked; });
      }

      var data = null;
      try {
        data = classifyResponse({ ok: xhr.status >= 200 && xhr.status < 300, status: xhr.status, headers: null }, xhr.responseText);
      } catch (classificationError) {
        var failureError = normalizeError(classificationError);
        fireError(requestHandle, failureError, 'error');
        if (shouldBlockUI) FMS.unblockUI();
        return Promise.resolve(undefined).then(function () { throw failureError; });
      }

      fireSuccess(data, 'success', requestHandle);
      if (shouldBlockUI) FMS.unblockUI();
      return Promise.resolve(data);
    }

    if (isAsync === false) {
      if (typeof window.XMLHttpRequest !== 'function') {
        return Promise.reject(new Error('Transport synchronous tidak tersedia di lingkungan ini.'));
      }
      return syncRequest();
    }

    if (!fireBeforeSend()) {
      var abortedError = new Error(options.abortedErrorMessage || 'Permintaan dibatalkan.');
      abortedError.aborted = true;
      abortedError.status = 0;
      fireError(requestHandle, abortedError, 'abort');
      fireComplete(requestHandle, 'abort');
      if (shouldBlockUI) FMS.unblockUI();
      return Promise.reject(abortedError);
    }

    if (shouldBlockUI) FMS.blockUI();

    var request = window.fetch(url, fetchOptions);

    function refreshAccessToken() {
      var refreshEndpoint = refreshUrl();
      if (!refreshEndpoint) return Promise.reject(new Error('Endpoint refresh token tidak tersedia.'));

      var refreshCsrfToken = cookieValue('fms_csrf') || currentCsrfHash();
      var refreshHeaders = {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json'
      };
      if (refreshCsrfToken) refreshHeaders['FMS-CSRF-TOKEN'] = refreshCsrfToken;

      return window.fetch(refreshEndpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: refreshHeaders,
        body: '{}'
      }).then(function (refreshResponse) {
        return refreshResponse.text().then(function (refreshText) {
          var refreshPayload = null;
          try { refreshPayload = refreshText ? JSON.parse(refreshText) : null; } catch (refreshError) { refreshPayload = null; }

          if (!refreshResponse.ok || !refreshPayload || refreshPayload.status === false) {
            var refreshFailure = new Error(refreshPayload && refreshPayload.message ? refreshPayload.message : 'Sesi berakhir. Silakan masuk kembali.');
            refreshFailure.status = refreshResponse.status;
            throw refreshFailure;
          }

          var refreshData = refreshPayload && refreshPayload.data ? refreshPayload.data : refreshPayload;
          if (refreshData && refreshData.csrf_token) CSRF_HASH = String(refreshData.csrf_token);
          return refreshData;
        });
      });
    }

    function refreshUrl() {
      var baseUrl = String(url);
      if (baseUrl.indexOf('/api/v1/') === -1) return null;
      if (/\/api\/v1\/auth\/(login|refresh|logout)(?:[/?#]|$)/.test(baseUrl)) return null;

      var origin = '';
      if (baseUrl.indexOf('http://') === 0 || baseUrl.indexOf('https://') === 0) {
        origin = baseUrl.slice(0, baseUrl.indexOf('/', 8));
      } else {
        origin = window.location.origin;
      }

      return origin + '/api/v1/auth/refresh';
    }

    return request.then(function (response) {
      if (timeoutHandle) window.clearTimeout(timeoutHandle);
      updateCsrf(response);
      return response.text().then(function (text) {
        var payload = null;
        try { payload = text ? JSON.parse(text) : null; } catch (error) { payload = null; }

        if (payload && payload.csrf_token) CSRF_HASH = String(payload.csrf_token);

        /* Respons non-JSON seperti CSV dikembalikan sebagai teks mentah. */
        var contentType = response && response.headers && typeof response.headers.get === 'function'
          ? String(response.headers.get('Content-Type') || response.headers.get('content-type') || '')
          : '';
        if (!payload && response.ok && contentType.toLowerCase().indexOf('text/csv') !== -1) {
          return text;
        }

        if (!payload) {
          if (!response.ok) {
            var failure = new Error('HTTP ' + response.status);
            failure.status = response.status;
            throw failure;
          }
          return text;
        }

        if (!response.ok || payload.status === false) {
          var problem = new Error(payload.message || ('HTTP ' + response.status));
          problem.status = response.status;
          problem.payload = payload;
          problem.errors = (payload.data && payload.data.errors) ? payload.data.errors : {};

          /* Access token invalid/kedaluwarsa memakai 401. 403 adalah penolakan izin final. */
          if (response.status === 401 && !options._fmsAuthRetried && refreshUrl()) {
            options._fmsAuthRetried = true;
            return refreshAccessToken().then(function (refreshPayload) {
              storeAccessToken(refreshPayload);
              var retryFetchOptions = Object.assign({}, fetchOptions);
              retryFetchOptions.headers = Object.assign({}, headers, {
                Authorization: storedTokenType() + ' ' + storedAccessToken()
              });
              return window.fetch(url, retryFetchOptions);
            }).then(function (retryResponse) {
              updateCsrf(retryResponse);
              return retryResponse.text().then(function (retryText) {
                var retryPayload = null;
                try { retryPayload = retryText ? JSON.parse(retryText) : null; } catch (retryParseError) { retryPayload = null; }

                if (!retryResponse.ok || !retryPayload || retryPayload.status === false) {
                  var retryFailure = new Error(retryPayload && retryPayload.message ? retryPayload.message : ('HTTP ' + retryResponse.status));
                  retryFailure.status = retryResponse.status;
                  retryFailure.payload = retryPayload || null;
                  throw retryFailure;
                }

                return typeof retryPayload.data === 'undefined' ? retryPayload : retryPayload.data;
              });
            }).catch(function (refreshError) {
              var loginPath = (window.FMS && window.FMS.config && window.FMS.config.loginUrl)
                ? window.FMS.config.loginUrl
                : '/fms-auth/in';
              if (Number(refreshError && refreshError.status ? refreshError.status : 0) !== 401) {
                throw refreshError;
              }
              clearStoredAccessToken();
              var currentPath = window.location && window.location.pathname ? window.location.pathname : '';
              if (currentPath.indexOf('/fms-auth/in') === -1) {
                window.location.href = loginPath;
              }
              throw refreshError;
            });
                  }

          throw problem;
        }

        return typeof payload.data === 'undefined' ? payload : payload.data;
      }).then(function (data) {
        if (typeof successHook === 'function') {
          requestHandle.status = response.status;
          requestHandle.response = response;
          fireSuccess(data, 'success', response);
        }
        return data;
      });
    }).catch(function (error) {
      if (timeoutHandle) window.clearTimeout(timeoutHandle);
      if (error && error.name === 'AbortError') {
        var timeoutError = new Error(options.timeoutErrorMessage || 'Permintaan melewati batas waktu.');
        timeoutError.timeout = true;
        fireError(requestHandle, timeoutError, 'timeout');
        throw timeoutError;
      }
      if (error instanceof TypeError) {
        var network = new Error(options.networkErrorMessage || 'Tidak dapat terhubung ke server.');
        network.network = true;
        fireError(requestHandle, network, 'error');
        throw network;
      }
      fireError(requestHandle, error, 'error');
      throw error;
    }).finally(function () {
      if (shouldBlockUI) FMS.unblockUI();
    });
  };

  ['get', 'post', 'put', 'patch', 'del'].forEach(function (verb) {
    FMS[verb] = function (url, data, options) {
      var settings = Object.assign({}, options || {});
      settings.url = url;
      settings.data = verb === 'del' && (data === undefined || data === null) ? {} : data;
      settings.method = verb === 'del' ? 'DELETE' : verb.toUpperCase();
      return FMS.ajax(settings);
    };
  });

  FMS.logout = function (logoutUrl, loginUrl) {
    var done = function () {
      try {
        sessionStorage.removeItem('fms_access_token');
        sessionStorage.removeItem('fms_token_type');
      } catch (storageError) { /* abaikan */ }

      window.location.href = loginUrl || '/fms-auth/in';
    };

    if (!logoutUrl) {
      done();
      return Promise.resolve(null);
    }

    var refreshCsrfToken = cookieValue('fms_csrf') || currentCsrfHash();

    return FMS.post(logoutUrl, {}, {
      blockUI: true,
      csrfToken: refreshCsrfToken
    }).then(done, done);
  };

  document.addEventListener('click', function (event) {
    var trigger = event && event.target && event.target.closest ? event.target.closest('[data-fms-logout]') : null;
    if (!trigger) return;

    event.preventDefault();
    FMS.logout(trigger.getAttribute('data-fms-logout'), trigger.getAttribute('data-fms-login'));
  });

  FMS.confirm = function (config) {
    var options = config || {};
    var title = options.title !== undefined ? options.title : 'Konfirmasi';
    var message = options.message !== undefined ? options.message : 'Apakah Anda yakin?';
    var description = options.description !== undefined ? options.description : '';
    var confirmLabel = options.confirmLabel !== undefined ? options.confirmLabel : 'Ya, Lanjutkan';
    var loadingLabel = options.loadingLabel !== undefined ? options.loadingLabel : 'Memproses...';
    var cancelLabel = options.cancelLabel !== undefined ? options.cancelLabel : 'Batal';
    var variant = options.variant !== undefined ? options.variant : 'danger';
    var action = typeof options.action === 'function' ? options.action : null;

    var existing = document.getElementById('fmsConfirmModal');
    if (existing && window.bootstrap && window.bootstrap.Modal) {
      var existingInstance = window.bootstrap.Modal.getInstance(existing);
      if (existingInstance) existingInstance.dispose();
    }
    if (existing) existing.remove();

    var modal = document.createElement('div');
    modal.id = 'fmsConfirmModal';
    modal.className = 'modal fade';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML = ''
      + '<div class="modal-dialog modal-sm modal-dialog-centered">'
      +   '<div class="modal-content">'
      +     '<div class="modal-header text-' + variant + '">'
      +       '<h6 class="modal-title">' + FMS.escapeHtml(title) + '</h6>'
      +       '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>'
      +     '</div>'
      +     '<div class="modal-body">'
      +       '<p class="mb-0">' + FMS.escapeHtml(message) + '</p>'
      +       (description ? '<small class="d-block text-muted mt-2">' + FMS.escapeHtml(description) + '</small>' : '')
      +     '</div>'
      +     '<div class="modal-footer">'
      +       '<button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">' + FMS.escapeHtml(cancelLabel) + '</button>'
      +       '<button type="button" class="btn btn-' + variant + ' btn-sm" id="fmsConfirmOk">'
      +         '<span id="fmsConfirmText">' + FMS.escapeHtml(confirmLabel) + '</span>'
      +         '<span id="fmsConfirmSpinner" class="spinner-border spinner-border-sm ms-1 d-none"></span>'
      +       '</button>'
      +     '</div>'
      +   '</div>'
      + '</div>';
    document.body.appendChild(modal);

    return new Promise(function (resolve) {
      var bootstrapModal = window.bootstrap && window.bootstrap.Modal ? new window.bootstrap.Modal(modal) : null;
      if (!bootstrapModal) {
        resolve(false);
        modal.remove();
        return;
      }

      var settled = false;
      var running = false;
      var confirmButton = modal.querySelector('#fmsConfirmOk');
      var confirmText = modal.querySelector('#fmsConfirmText');
      var confirmSpinner = modal.querySelector('#fmsConfirmSpinner');
      var done = function (value) {
        if (settled) return;
        settled = true;
        resolve(value);
      };
      var close = function (value) {
        done(value);
        bootstrapModal.hide();
      };
      var setLoading = function (loading) {
        running = loading;
        confirmButton.disabled = loading;
        confirmText.textContent = loading ? loadingLabel : confirmLabel;
        confirmSpinner.classList.toggle('d-none', !loading);
      };

      confirmButton.addEventListener('click', function () {
        if (!action) {
          close(true);
          return;
        }
        setLoading(true);
        Promise.resolve().then(action).then(function (result) {
          close(result === undefined ? true : result);
        }).catch(function (error) {
          setLoading(false);
          FMS.toast(error && error.message ? error.message : 'Proses gagal.', false);
        });
      });
      modal.addEventListener('hide.bs.modal', function (event) {
        if (running && !settled) event.preventDefault();
      });
      modal.addEventListener('hidden.bs.modal', function () {
        done(false);
        bootstrapModal.dispose();
        modal.remove();
      });

      bootstrapModal.show();
    });
  };

  FMS.initSessionGuard = function (config) {
    var options = config || {};
    var statusUrl = options.statusUrl || '/api/v1/auth/session-status';
    var continueUrl = options.continueUrl || '/api/v1/auth/continue-session';
    var loginUrl = options.loginUrl || '/fms-auth/in';
    var pollMilliseconds = Math.max(5000, Number(options.pollMilliseconds || 30000));
    var warningSeconds = Math.max(30, Number(options.warningSeconds || 300));
    var idleMinutes = Math.max(1, Number(options.idleMinutes || 5));
    var idleMilliseconds = idleMinutes * 60 * 1000;
    var prompted = false;
    var stopped = false;
    var lastActivity = Date.now();

    /* Perhatikan kegiatan user agar tidak poll sia-sia. */
    function touchActivity() { lastActivity = Date.now(); }
    var activityEvents = ['mousedown', 'keydown', 'touchstart', 'scroll', 'mousemove'];
    activityEvents.forEach(function (type) {
      document.addEventListener(type, touchActivity, { passive: true });
    });

    function forceLogout() {
      if (stopped) return;
      stopped = true;
      activityEvents.forEach(function (type) {
        document.removeEventListener(type, touchActivity);
      });
      try {
        sessionStorage.removeItem('fms_access_token');
        sessionStorage.removeItem('fms_token_type');
      } catch (storageError) { /* abaikan */ }
      window.location.href = loginUrl;
    }

    function continueSession() {
      var csrfToken = cookieValue('fms_csrf') || currentCsrfHash();
      return window.fetch(continueUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/json',
          'FMS-CSRF-TOKEN': csrfToken
        },
        body: '{}'
      }).then(function (response) {
        return response.text().then(function (text) {
          var payload = null;
          try { payload = text ? JSON.parse(text) : null; } catch (parseError) { payload = null; }
          if (!response.ok || !payload || payload.status === false) throw new Error('Sesi berakhir.');
          return payload.data || payload;
        });
      });
    }

    function askToContinue(remainingSeconds) {
      if (prompted || stopped) return;
      prompted = true;
      var minutes = Math.max(1, Math.ceil(remainingSeconds / 60));
      FMS.confirm({
        title: 'Sesi Akan Berakhir',
        message: 'Sesi Anda akan berakhir sekitar ' + minutes + ' menit lagi. Lanjutkan sesi?',
        confirmLabel: 'Lanjutkan Sesi',
        cancelLabel: 'Keluar',
        variant: 'warning'
      }).then(function (confirmed) {
        if (!confirmed) {
          FMS.logout('/api/v1/auth/logout', loginUrl);
          return;
        }
        return continueSession().then(function () {
          prompted = false;
          lastActivity = Date.now();
          FMS.toast('Sesi berhasil dilanjutkan.', true);
        }).catch(forceLogout);
      });
    }

    function check() {
      if (stopped || document.visibilityState === 'hidden') return;

      /* Baru cek sesi jika user tidak ada kegiatan selama idleMinutes. */
      var idleMs = Date.now() - lastActivity;
      if (idleMs < idleMilliseconds) return;

      window.fetch(statusUrl, {
        method: 'GET',
        credentials: 'same-origin',
        cache: 'no-store',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      }).then(function (response) {
        return response.text().then(function (text) {
          var payload = null;
          try { payload = text ? JSON.parse(text) : null; } catch (parseError) { payload = null; }
          if (response.status === 401 || !payload || payload.status === false || !payload.data || payload.data.active !== true) {
            forceLogout();
            return;
          }
          var remainingSeconds = Number(payload.data.remaining_seconds || 0);
          if (remainingSeconds <= 0) {
            forceLogout();
          } else if (remainingSeconds <= warningSeconds) {
            askToContinue(remainingSeconds);
          }
        });
      }).catch(function () { /* Gangguan jaringan bukan alasan mengeluarkan user. */ });
    }

    check();
    var intervalId = window.setInterval(check, pollMilliseconds);
    document.addEventListener('visibilitychange', check);
    if (typeof window.addEventListener === 'function') {
      window.addEventListener('beforeunload', function () {
        window.clearInterval(intervalId);
        activityEvents.forEach(function (type) {
          document.removeEventListener(type, touchActivity);
        });
      });
    }

    return { check: check, stop: function () { stopped = true; window.clearInterval(intervalId); } };
  };

  FMS.toast = function (message, type, delay) {
    var host = document.getElementById('fmsToastHost');
    if (!host) {
      host = document.createElement('div');
      host.id = 'fmsToastHost';
      host.className = 'toast-container position-fixed top-0 end-0 p-3';
      host.style.zIndex = '1080';
      document.body.appendChild(host);
    }

    var cleanType = typeof type === 'string' ? type.toLowerCase().trim() : type;
    var rawType = cleanType === true ? 'success' : (cleanType === false ? 'danger' : (cleanType || 'secondary'));
    var validVariants = { 'danger': true, 'warning': true, 'info': true, 'success': true, 'secondary': true };
    var variant = validVariants[rawType] ? rawType : 'secondary';

    var logoBase = document.querySelector('meta[name="fms-logo"]');
    var logoSrc  = logoBase ? logoBase.getAttribute('content') : '';

    var brandMeta = document.querySelector('meta[name="fms-brand-name"]');
    var brandName = (brandMeta && brandMeta.getAttribute('content')) ? brandMeta.getAttribute('content') : 'FMS';

    var node = document.createElement('div');
    node.className = 'toast colored-toast bg-' + variant + '-transparent text-' + variant + ' mb-2';
    node.setAttribute('role', 'alert');
    node.setAttribute('aria-live', 'assertive');
    node.setAttribute('aria-atomic', 'true');

    var img = logoSrc
      ? '<img class="bd-placeholder-img rounded me-2" src="' + logoSrc + '" alt="FMS" width="20" height="20">'
      : '<i class="ri-notification-3-line me-2"></i>';

    var safeBrand = FMS.escapeHtml(brandName);

    node.innerHTML = ''
      + '<div class="toast-header bg-' + variant + '">'
      +   img
      +   '<strong class="me-auto text-white">' + safeBrand + '</strong>'
      +   '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>'
      + '</div>'
      + '<div class="toast-body"></div>';

    node.querySelector('.toast-body').textContent = String(message === undefined ? '' : message);
    host.appendChild(node);

    if (window.bootstrap && window.bootstrap.Toast) {
      var instance = new window.bootstrap.Toast(node, { delay: delay || 3500 });
      instance.show();
      node.addEventListener('hidden.bs.toast', function () { node.remove(); });
    } else {
      window.setTimeout(function () { node.remove(); }, delay || 3500);
    }
    return node;
  };

  FMS.form = {
    values: function (target) {
      var form = selectorOf(target);
      if (!form) return {};
      var values = {};
      var fields = form.querySelectorAll('input[name], select[name], textarea[name]');
      Array.prototype.forEach.call(fields, function (field) {
        if (field.disabled) return;
        var name = field.getAttribute('name');
        if (!name) return;
        if (field.type === 'checkbox') { values[name] = field.checked ? (field.value || 1) : 0; return; }
        if (field.type === 'radio') { if (field.checked) values[name] = field.value; return; }
        values[name] = field.value;
      });
      return values;
    },
    setValues: function (target, data) {
      var form = selectorOf(target);
      if (!form) return;
      Object.keys(data || {}).forEach(function (key) {
        var field = form.querySelector('[name="' + key + '"]');
        if (!field) return;
        if (field.type === 'checkbox') { field.checked = !!Number(data[key]); return; }
        field.value = data[key] === null || data[key] === undefined ? '' : data[key];
      });
    },
    reset: function (target) {
      var form = selectorOf(target);
      if (form && typeof form.reset === 'function') form.reset();
    },
    errors: function (target, errors, fieldMap) {
      var form = selectorOf(target);
      if (!form) return;

      var unmapped = [];
      Object.keys(errors || {}).forEach(function (field) {
        var messages = Array.isArray(errors[field]) ? errors[field] : [errors[field]];
        var text = messages
          .filter(function (message) { return message !== null && message !== undefined && String(message) !== ''; })
          .map(String)
          .join(' ');
        if (!text) return;

        /* Cari input berdasarkan name attribute (standar baru) dalam form. */
        var escapedField = (window.CSS && typeof window.CSS.escape === 'function')
          ? window.CSS.escape(field)
          : String(field).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
        var input = form.querySelector('[name="' + escapedField + '"]');

        /* Fallback: cari berdasarkan fieldMap ID atau nama field sebagai ID (legacy compat). */
        if (!input) {
          var inputId = fieldMap ? fieldMap[field] : field;
          input = inputId ? document.getElementById(inputId) : null;
        }
        if (!input) { unmapped.push(text); return; }

        /* Tulis pesan ke .invalid-feedback sibling dari input dalam .mb-3. */
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
        var fieldGroup = input.closest('.mb-3') || input.closest('.form-group') || input.parentElement;
        if (fieldGroup) {
          var feedback = fieldGroup.querySelector('.invalid-feedback');
          if (feedback) { feedback.textContent = text; }
        }
      });

      if (unmapped.length) {
        var banner = form.querySelector('[data-fms-form-error]');
        if (banner) {
          banner.textContent = unmapped.join(' ');
          banner.classList.remove('d-none');
        } else {
          FMS.toast(unmapped.join(' '), false);
        }
      }
    },
    clearErrors: function (target, fieldMap) {
      var form = selectorOf(target);
      if (!form) return;
      form.classList.remove('was-validated');

      var banner = form.querySelector('[data-fms-form-error]');
      if (banner) { banner.textContent = ''; banner.classList.add('d-none'); }

      /* Clear semua field yang punya .invalid-feedback sibling di dalam .mb-3. */
      var groups = form.querySelectorAll('.mb-3, .form-group');
      Array.prototype.forEach.call(groups, function (group) {
        var fb = group.querySelector('.invalid-feedback');
        if (fb) fb.textContent = '';
        var inputs = group.querySelectorAll('input, select, textarea');
        Array.prototype.forEach.call(inputs, function (input) {
          input.classList.remove('is-invalid', 'is-valid');
          if (typeof input.setCustomValidity === 'function') input.setCustomValidity('');
        });
      });

      /* Legacy: fieldMap based clear untuk form lama. */
      if (fieldMap) {
        Object.keys(fieldMap).forEach(function (field) {
          var inputId = fieldMap[field];
          var input = document.getElementById(inputId);
          if (input) {
            input.classList.remove('is-invalid', 'is-valid');
            if (typeof input.setCustomValidity === 'function') input.setCustomValidity('');
            var feedback = document.getElementById(inputId + 'Err');
            if (feedback) feedback.textContent = '';
          }
        });
      }
    },
    clearFieldError: function (input) {
      if (!input || !input.id) return;
      input.classList.remove('is-invalid', 'is-valid');
      if (typeof input.setCustomValidity === 'function') input.setCustomValidity('');
      var fieldGroup = input.closest('.mb-3') || input.closest('.form-group') || input.parentElement;
      if (fieldGroup) {
        var feedback = fieldGroup.querySelector('.invalid-feedback');
        if (feedback) feedback.textContent = '';
      }
      var legacyFeedback = document.getElementById(input.id + 'Err');
      if (legacyFeedback) legacyFeedback.textContent = '';
    }
  };

  FMS.pagination = function (config) {
    var host = selectorOf(config.target);
    var total = Number(config.total || 0);
    var perPage = Math.max(1, Number(config.perPage || 10));
    var currentPage = Math.max(1, Number(config.page || 1));
    var totalPages = Math.max(1, Math.ceil(total / perPage));
    if (currentPage > totalPages) currentPage = totalPages;

    if (!host) {
      return { page: currentPage, perPage: perPage, totalPages: totalPages, total: total };
    }

    host.innerHTML = '';
    if (totalPages <= 1) {
      var info = document.createElement('div');
      info.className = 'text-muted fs-13';
      info.textContent = total === 0 ? 'Tidak ada data.' : ('Menampilkan ' + total + ' dari ' + total + ' data');
      host.appendChild(info);
      return { page: currentPage, perPage: perPage, totalPages: totalPages, total: total };
    }

    var list = document.createElement('ul');
    list.className = 'pagination pagination-sm mb-0';

    var changeTo = function (page) {
      var targetPage = Math.min(totalPages, Math.max(1, Number(page || 1)));
      if (typeof config.onChange === 'function') config.onChange(targetPage, perPage);
    };

    var addItem = function (label, page, disabled, active) {
      var li = document.createElement('li');
      li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
      var link = document.createElement('a');
      link.className = 'page-link';
      link.href = 'javascript:void(0);';
      link.textContent = label;
      if (!disabled && !active) {
        link.addEventListener('click', function () { changeTo(page); });
      }
      li.appendChild(link);
      list.appendChild(li);
      return li;
    };

    var jumpItem = function () {
      var li = document.createElement('li');
      li.className = 'page-item active';
      li.style.display = 'inline-flex';
      li.style.alignItems = 'center';

      var input = document.createElement('input');
      input.type = 'number';
      input.min = '1';
      input.max = String(totalPages);
      input.value = String(currentPage);
      input.className = 'form-control form-control-sm';
      input.style.width = '50px';
      input.style.color = 'var(--default-text-color)';
      input.style.backgroundColor = 'var(--form-control-bg)';
      input.style.border = '0';
      input.style.borderTop = '1px solid var(--default-border)';
      input.style.borderBottom = '1px solid var(--default-border)';
      input.style.padding = '0.3rem 1rem';
      input.style.borderRadius = '0';
      input.setAttribute('aria-label', 'Nomor halaman');

      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'btn btn-primary btn-sm';
      button.borderRadius = '0';
      button.style.padding = '0.33rem 0.6rem';
      button.title = 'Lompat ke halaman';
      button.innerHTML = '<i class=\"ri-search-line\"></i>';
      button.addEventListener('click', function () { changeTo(input.value); });

      input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') button.click();
      });
      input.addEventListener('input', function () {
        input.value = String(input.value).replace(/[^0-9]/g, '');
      });

      li.appendChild(input);
      li.appendChild(button);
      list.appendChild(li);
      return li;
    };

    addItem('First', 1, currentPage <= 1, false);
    addItem('«', currentPage - 1, currentPage <= 1, false);

    var start = Math.max(1, currentPage - 2);
    var end = Math.min(totalPages, start + 4);
    start = Math.max(1, end - 4);
    for (var page = start; page <= end; page++) {
      if (page === currentPage) jumpItem();
      else addItem(String(page), page, false, false);
    }

    addItem('»', currentPage + 1, currentPage >= totalPages, false);
    addItem('Last', totalPages, currentPage >= totalPages, false);

    var wrapper = document.createElement('div');
    wrapper.className = 'd-flex align-items-center justify-content-between flex-column flex-sm-row flex-wrap gap-2';
    var counter = document.createElement('div');
    counter.className = 'fs-13';
    var from = total === 0 ? 0 : ((currentPage - 1) * perPage) + 1;
    var to = Math.min(total, currentPage * perPage);
    counter.textContent = 'Menampilkan ' + from + ' - ' + to + ' dari ' + total + ' data';
    wrapper.appendChild(counter);
    wrapper.appendChild(list);
    host.appendChild(wrapper);

    return { page: currentPage, perPage: perPage, totalPages: totalPages, total: total };
  };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', metaCsrf);
  else metaCsrf();

  window.FMS = FMS;
}(window, document));

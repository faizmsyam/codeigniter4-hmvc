/**
 * Pencarian menu pada header.
 *
 * Sumber data: tautan sidebar yang sedang tampil (sudah difilter permission
 * oleh FMSAdminMenuAccessService). Jadi menu yang di-hide karena gate
 * "Boleh dibuka" OFF tidak akan pernah muncul di hasil pencarian.
 *
 * Perilaku:
 *  - mengetik = filter langsung (tanpa request);
 *  - Enter = buka hasil teratas;
 *  - klik hasil = navigasi;
 *  - Esc / klik di luar = tutup dropdown.
 */
(function (window, document) {
  'use strict';

  var SELECTOR_ITEM = '.app-sidebar .main-menu .side-menu__item';
  var MAX_RESULTS = 8;

  function normalize(value) {
    return String(value === null || value === undefined ? '' : value)
      .toLowerCase()
      .replace(/\s+/g, ' ')
      .trim();
  }

  function collectMenuItems() {
    var nodes = document.querySelectorAll(SELECTOR_ITEM);
    var items = [];
    var seen = {};

    Array.prototype.forEach.call(nodes, function (node) {
      var labelNode = node.querySelector('.side-menu__label');
      var href = node.getAttribute('href') || '';

      if (node.getAttribute('data-bs-toggle') === 'sidebar') return;
      if (node.closest('.doublemenu_bottom-menu')) return;

      var label = '';
      if (labelNode) {
        label = labelNode.textContent;
      } else {
        /* Child level 1 memakai teks langsung di dalam <a>. */
        label = node.textContent;
      }

      label = String(label || '').replace(/\s+/g, ' ').trim();

      if (!label || !href || href.indexOf('javascript:') === 0) return;

      var isExternal = href.indexOf('http://') === 0 || href.indexOf('https://') === 0;
      var url = href;

      if (!isExternal) {
        try {
          url = new window.URL(href, window.location.origin).href;
        } catch (urlError) {
          url = href;
        }
      }

      var key = normalize(label) + '|' + url;
      if (seen[key]) return;
      seen[key] = true;

      items.push({
        label: label,
        url: url,
        external: isExternal,
        targetBlank: node.getAttribute('target') === '_blank',
        search: normalize(label)
      });
    });

    return items;
  }

  function findMatches(items, query) {
    var needle = normalize(query);
    if (needle === '') return [];

    var starts = [];
    var contains = [];

    items.forEach(function (item) {
      var position = item.search.indexOf(needle);
      if (position === 0) {
        starts.push(item);
        return;
      }
      if (position > -1) contains.push(item);
    });

    return starts.concat(contains).slice(0, MAX_RESULTS);
  }

  function goTo(item) {
    if (!item) return;
    if (item.external) {
      if (item.targetBlank) window.open(item.url, '_blank', 'noopener');
      else window.location.href = item.url;
      return;
    }
    window.location.href = item.url;
  }

  function bindSearch(input, panel) {
    if (!input || !panel) return;

    var items = null;
    var highlighted = -1;

    function ensureItems() {
      if (items === null) items = collectMenuItems();
      return items;
    }

    function close() {
      panel.style.display = 'none';
      panel.innerHTML = '';
      highlighted = -1;
    }

    function render(matches) {
      if (!matches.length) {
        panel.innerHTML = '<div class="header-search-empty">Menu tidak ditemukan</div>';
        panel.style.display = 'block';
        highlighted = -1;
        return;
      }

      var html = '';
      matches.forEach(function (item, index) {
        var active = index === highlighted ? ' is-highlighted' : '';
        html += '<a href="' + escapeAttribute(item.url) + '" class="header-search-item' + active + '" data-index="' + index + '"' +
          (item.external && item.targetBlank ? ' target="_blank" rel="noopener noreferrer"' : '') + '>' +
          '<i class="bi bi-arrow-return-right me-2"></i>' + escapeHtml(item.label) +
          '</a>';
      });

      panel.innerHTML = html;
      panel.style.display = 'block';
    }

    function escapeHtml(value) {
      return String(value)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function escapeAttribute(value) {
      return escapeHtml(value);
    }

    function refresh() {
      var matches = findMatches(ensureItems(), input.value);
      highlighted = matches.length ? 0 : -1;
      if (normalize(input.value) === '') {
        close();
        return;
      }
      render(matches);
    }

    function submit() {
      var matches = findMatches(ensureItems(), input.value);
      var chosen = highlighted > -1 ? matches[highlighted] : matches[0];
      if (chosen) goTo(chosen);
    }

    input.addEventListener('input', refresh);

    input.addEventListener('focus', function () {
      if (normalize(input.value) !== '') refresh();
    });

    input.addEventListener('keydown', function (event) {
      var matches = findMatches(ensureItems(), input.value);

      if (event.key === 'ArrowDown') {
        if (!matches.length) return;
        event.preventDefault();
        highlighted = (highlighted + 1) % matches.length;
        render(matches);
        return;
      }

      if (event.key === 'ArrowUp') {
        if (!matches.length) return;
        event.preventDefault();
        highlighted = highlighted <= 0 ? matches.length - 1 : highlighted - 1;
        render(matches);
        return;
      }

      if (event.key === 'Enter') {
        event.preventDefault();
        submit();
        return;
      }

      if (event.key === 'Escape') {
        close();
      }
    });

    panel.addEventListener('click', function (event) {
      var link = event.target.closest('.header-search-item');
      if (!link) return;
      event.preventDefault();
      var index = parseInt(link.getAttribute('data-index'), 10);
      var matches = findMatches(ensureItems(), input.value);
      goTo(matches[index]);
    });

    panel.addEventListener('mousemove', function (event) {
      var link = event.target.closest('.header-search-item');
      if (!link) return;
      var index = parseInt(link.getAttribute('data-index'), 10);
      if (index === highlighted) return;
      highlighted = index;
      render(findMatches(ensureItems(), input.value));
    });

    var icon = document.getElementById('header-search-icon');
    if (icon) {
      icon.addEventListener('click', function () {
        input.focus();
        refresh();
      });
    }

    document.addEventListener('click', function (event) {
      if (panel.contains(event.target) || event.target === input) return;
      if (icon && icon.contains(event.target)) return;
      close();
    });

    /* Tutup modal mobile saat navigasi via Enter. */
    input.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter') return;
      var modal = input.closest('.modal');
      if (!modal) return;
      var instance = window.bootstrap && window.bootstrap.Modal ? window.bootstrap.Modal.getInstance(modal) : null;
      if (instance) instance.hide();
    });
  }

  function injectStyles() {
    if (document.getElementById('fmsHeaderSearchStyles')) return;

    var style = document.createElement('style');
    style.id = 'fmsHeaderSearchStyles';
    style.textContent = `
      .header-search { position: relative; }
      .header-search-dropdown {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        right: 0;
        z-index: 1200;
        min-width: 260px;
        padding: 6px;
        border: 1px solid var(--default-border, rgba(0,0,0,.08));
        border-radius: 8px;
        background: var(--custom-white, #fff);
        box-shadow: 0 12px 28px rgba(0,0,0,.14);
        max-height: 320px;
        overflow-y: auto;
      }
      .header-search-item {
        display: flex;
        align-items: center;
        gap: 2px;
        padding: 8px 10px;
        border-radius: 6px;
        color: var(--default-text-color, #212529);
        font-size: 13px;
        text-decoration: none;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      .header-search-item:hover,
      .header-search-item.is-highlighted {
        background: var(--primary01, rgba(132,90,223,.12));
        color: var(--primary-color, #845adf);
      }
      .header-search-empty {
        padding: 10px;
        color: var(--text-muted, #6c757d);
        font-size: 13px;
        text-align: center;
      }
      .modal .header-search-dropdown {
        position: static;
        margin-top: 8px;
        box-shadow: none;
      }
    `;
    document.head.appendChild(style);
  }

  function init() {
    if (!document.getElementById('header-search') && !document.getElementById('header-search-mobile')) return;

    injectStyles();
    bindSearch(document.getElementById('header-search'), document.getElementById('header-search-results'));
    bindSearch(document.getElementById('header-search-mobile'), document.getElementById('header-search-results-mobile'));
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}(window, document));

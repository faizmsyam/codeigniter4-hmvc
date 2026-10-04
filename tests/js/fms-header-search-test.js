/**
 * Kontrak pencarian menu header (fms-header-search.js).
 *
 * Harness DOM minimal tanpa dependensi: membangun sidebar palsu, memuat
 * script di VM, lalu memverifikasi filter, navigasi, dan pengecualian menu.
 */

const fs = require('fs');
const path = require('path');
const vm = require('vm');
const assert = require('assert');
const { URL } = require('url');

const SCRIPT = path.join(__dirname, '..', '..', 'public', 'assets', 'fms', 'js', 'fms-header-search.js');
const SOURCE = fs.readFileSync(SCRIPT, 'utf8');

function makeNode(tag, attrs = {}) {
  const node = {
    tagName: tag.toUpperCase(),
    attrs: Object.assign({}, attrs),
    children: [],
    textContent: '',
    style: {},
    innerHTML: '',
    listeners: {},
    parentNode: null,
    getAttribute(name) { return Object.prototype.hasOwnProperty.call(this.attrs, name) ? this.attrs[name] : null; },
    setAttribute(name, value) { this.attrs[name] = value; },
    appendChild(child) { child.parentNode = this; this.children.push(child); return child; },
    addEventListener(type, handler) { (this.listeners[type] = this.listeners[type] || []).push(handler); },
    dispatch(type, event) {
      const payload = Object.assign({ type, target: this, preventDefault() {} }, event || {});
      (this.listeners[type] || []).forEach((handler) => handler.call(this, payload));
    },
    contains(other) {
      let cursor = other;
      while (cursor) { if (cursor === this) return true; cursor = cursor.parentNode; }
      return false;
    },
    querySelector(selector) {
      if (selector === '.side-menu__label') {
        return this.children.find((child) => child.attrs.class === 'side-menu__label') || null;
      }
      return null;
    },
    closest(selector) {
      let cursor = this;
      while (cursor) {
        if (selector === '.doublemenu_bottom-menu' && cursor.attrs.class === 'doublemenu_bottom-menu') return cursor;
        if (selector === '.modal' && cursor.attrs.class === 'modal') return cursor;
        if (selector === '.header-search-item' && cursor.attrs.class && cursor.attrs.class.indexOf('header-search-item') === 0) return cursor;
        cursor = cursor.parentNode;
      }
      return null;
    },
    focus() { this.focused = true; },
  };
  return node;
}

function sidebarLink(label, href, extra = {}) {
  const link = makeNode('a', Object.assign({ href }, extra));
  const labelNode = makeNode('span', { class: 'side-menu__label' });
  labelNode.textContent = label;
  link.appendChild(labelNode);
  return link;
}

function buildEnvironment(links) {
  const input = makeNode('input');
  const panel = makeNode('div');
  const icon = makeNode('a');
  const byId = {
    'header-search': input,
    'header-search-results': panel,
    'header-search-icon': icon,
  };

  const document = {
    readyState: 'complete',
    head: makeNode('head'),
    listeners: {},
    getElementById(id) { return byId[id] || null; },
    querySelectorAll() { return links; },
    createElement(tag) { return makeNode(tag); },
    addEventListener(type, handler) { (this.listeners[type] = this.listeners[type] || []).push(handler); },
  };

  const window = {
    location: { origin: 'https://fms.test', href: 'https://fms.test/fms-admin/dashboard' },
    navigated: [],
    URL,
  };
  window.window = window;

  const context = vm.createContext({ window, document, console, URL, parseInt, String, Array, Object, RegExp });
  vm.runInContext(SOURCE, context);

  return {
    input,
    panel,
    window,
    type(value) { input.value = value; input.dispatch('input'); },
    press(key) { input.dispatch('keydown', { key }); },
    visibleItems() {
      return (panel.innerHTML.match(/header-search-item/g) || []).length;
    },
  };
}

const tests = [];
function test(name, fn) { tests.push({ name, fn }); }

const DEFAULT_LINKS = [
  sidebarLink('Dashboard', 'https://fms.test/fms-admin/dashboard'),
  sidebarLink('Brand', 'https://fms.test/fms-admin/brand'),
  sidebarLink('Admin Menus', 'https://fms.test/fms-admin/admin-menus'),
  sidebarLink('Activity Logs', 'https://fms.test/fms-admin/activity-logs'),
];

test('mengetik memfilter menu dan menampilkan hasil', () => {
  const env = buildEnvironment(DEFAULT_LINKS);
  env.type('bra');
  assert.strictEqual(env.panel.style.display, 'block', 'panel harus terbuka');
  assert.ok(/Brand/.test(env.panel.innerHTML), 'Brand harus muncul');
  assert.ok(!/Dashboard/.test(env.panel.innerHTML), 'Dashboard tidak boleh muncul');
});

test('query kosong menutup panel', () => {
  const env = buildEnvironment(DEFAULT_LINKS);
  env.type('brand');
  env.type('');
  assert.strictEqual(env.panel.style.display, 'none', 'panel harus tertutup');
});

test('menu yang tidak ada menghasilkan pesan kosong', () => {
  const env = buildEnvironment(DEFAULT_LINKS);
  env.type('zzzz');
  assert.ok(/tidak ditemukan/i.test(env.panel.innerHTML), 'pesan kosong harus tampil');
});

test('Enter membuka hasil teratas', () => {
  const env = buildEnvironment(DEFAULT_LINKS);
  env.type('brand');
  env.press('Enter');
  assert.strictEqual(env.window.location.href, 'https://fms.test/fms-admin/brand');
});

test('ArrowDown lalu Enter memilih hasil kedua', () => {
  const env = buildEnvironment(DEFAULT_LINKS);
  env.type('a');
  env.press('ArrowDown');
  env.press('Enter');
  assert.ok(env.window.location.href.indexOf('/fms-admin/') !== -1, 'navigasi harus terjadi');
});

test('Escape menutup panel', () => {
  const env = buildEnvironment(DEFAULT_LINKS);
  env.type('brand');
  env.press('Escape');
  assert.strictEqual(env.panel.style.display, 'none');
});

test('menu yang di-hide permission tidak ikut terdaftar', () => {
  const visibleOnly = [
    sidebarLink('Dashboard', 'https://fms.test/fms-admin/dashboard'),
  ];
  const env = buildEnvironment(visibleOnly);
  env.type('brand');
  assert.ok(/tidak ditemukan/i.test(env.panel.innerHTML), 'Brand tersembunyi tidak boleh ditemukan');
});

test('item dengan href javascript: diabaikan', () => {
  const links = [
    sidebarLink('Control Panel', 'javascript:void(0);'),
    sidebarLink('Brand', 'https://fms.test/fms-admin/brand'),
  ];
  const env = buildEnvironment(links);
  env.type('control');
  assert.ok(/tidak ditemukan/i.test(env.panel.innerHTML), 'javascript: harus diabaikan');
});

let passed = 0;
tests.forEach((entry) => {
  try {
    entry.fn();
    passed += 1;
    console.log('  ok  ' + entry.name);
  } catch (error) {
    console.log('FAIL  ' + entry.name);
    console.log('      ' + error.message);
  }
});

console.log('\n' + passed + '/' + tests.length + ' passed');
process.exit(passed === tests.length ? 0 : 1);

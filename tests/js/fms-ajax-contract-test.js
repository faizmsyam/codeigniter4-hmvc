/**
 * Kontrak publik FMS.ajax — harness browser-minimal (tanpa dependensi).
 *
 * Menjalankan fms.js di dalam context VM dengan stub fetch / XMLHttpRequest,
 * lalu memverifikasi opsi baru (async, cache, hooks) tanpa mengubah caller lama.
 *
 * Jalankan: node tests/js/fms-ajax-contract-test.js
 */
'use strict';

const fs = require('fs');
const path = require('path');
const vm = require('vm');
const assert = require('assert');

const FMS_JS = path.resolve(__dirname, '..', '..', 'public', 'assets', 'fms', 'js', 'fms.js');

function createEnvironment(fetchImpl, xhrImpl) {
  const windowObject = {};
  const readyCallbacks = [];

  const elementStub = () => ({
    style: {},
    dataset: {},
    classList: { add() {}, remove() {}, contains() { return false; } },
    setAttribute() {},
    getAttribute() { return null; },
    appendChild() {},
    remove() {},
    addEventListener() {},
    querySelector() { return null; },
    querySelectorAll() { return []; },
    insertBefore() {},
    focus() {}
  });

  const documentStub = {
    cookie: '',
    readyState: 'complete',
    querySelector() { return null; },
    querySelectorAll() { return []; },
    getElementById() { return null; },
    createElement() { return elementStub(); },
    createTextNode() { return {}; },
    head: { appendChild() {} },
    body: { appendChild() {}, classList: { add() {}, remove() {} } },
    addEventListener(type, callback) { readyCallbacks.push(callback); }
  };

  windowObject.document = documentStub;
  windowObject.location = { origin: 'http://fms.test', pathname: '/', href: 'http://fms.test/' };
  windowObject.setTimeout = setTimeout;
  windowObject.clearTimeout = clearTimeout;
  windowObject.setInterval = setInterval;
  windowObject.clearInterval = clearInterval;
  windowObject.addEventListener = () => {};
  windowObject.matchMedia = () => ({ matches: false });
  windowObject.XMLHttpRequest = xhrImpl;
  windowObject.fetch = fetchImpl;

  const sessionStorageStub = {
    store: {},
    getItem(key) { return Object.prototype.hasOwnProperty.call(this.store, key) ? this.store[key] : null; },
    setItem(key, value) { this.store[key] = String(value); },
    removeItem(key) { delete this.store[key]; }
  };

  const sandbox = {
    window: windowObject,
    document: documentStub,
    sessionStorage: sessionStorageStub,
    Promise,
    setTimeout,
    clearTimeout,
    Date,
    JSON,
    Math,
    Object,
    Array,
    Error,
    TypeError,
    encodeURIComponent,
    decodeURIComponent,
    console
  };
  sandbox.globalThis = sandbox;

  vm.createContext(sandbox);
  vm.runInContext(fs.readFileSync(FMS_JS, 'utf8'), sandbox, { filename: 'fms.js' });

  return { FMS: sandbox.window.FMS, window: windowObject, storage: sessionStorageStub, sandbox };
}

function jsonResponse(body, status = 200, contentType = 'application/json') {
  const text = typeof body === 'string' ? body : JSON.stringify(body);
  return {
    ok: status >= 200 && status < 300,
    status,
    headers: { get(name) { return String(name).toLowerCase() === 'content-type' ? contentType : null; } },
    text() { return Promise.resolve(text); }
  };
}

const tests = [];
function test(name, run) { tests.push({ name, run }); }

/* ── Default async:true memakai fetch, cache default mempertahankan URL ── */
test('default async true memakai fetch dan cache default tidak menambah query', async () => {
  const calls = [];
  const env = createEnvironment((url, init) => {
    calls.push({ url, init });
    return Promise.resolve(jsonResponse({ status: true, data: { ok: 1 } }));
  });

  const data = await env.FMS.ajax({ url: '/api/v1/brand', method: 'GET' });
  assert.deepStrictEqual(data, { ok: 1 });
  assert.strictEqual(calls.length, 1);
  assert.strictEqual(calls[0].url, '/api/v1/brand');
  assert.strictEqual(calls[0].init.cache, 'default');
});

/* ── cache:false memasang cache-buster + header no-store ── */
test('cache false menambah cache buster dan header no-store', async () => {
  const calls = [];
  const env = createEnvironment((url, init) => {
    calls.push({ url, init });
    return Promise.resolve(jsonResponse({ status: true, data: [] }));
  });

  await env.FMS.ajax({ url: '/api/v1/users', method: 'GET', data: { page: 2 }, cache: false });

  assert.ok(/[?&]_=\d+/.test(calls[0].url), 'cache-buster harus ada di query');
  assert.ok(/page=2/.test(calls[0].url), 'data GET tetap diserialisasi');
  assert.strictEqual(calls[0].init.cache, 'no-store');
  assert.strictEqual(calls[0].init.headers['Cache-Control'], 'no-store');
  assert.strictEqual(calls[0].init.headers.Pragma, 'no-cache');
});

/* ── beforeSend dapat mengubah header dan membatalkan request ── */
test('beforeSend dapat menambah header', async () => {
  let seenHeader = null;
  const env = createEnvironment((url, init) => {
    seenHeader = init.headers['X-Trace'];
    return Promise.resolve(jsonResponse({ status: true, data: null }));
  });

  await env.FMS.ajax({
    url: '/api/v1/brand',
    beforeSend(xhr, settings) { settings.headers['X-Trace'] = 'fms-test'; }
  });

  assert.strictEqual(seenHeader, 'fms-test');
});

test('beforeSend return false membatalkan request tanpa memanggil server', async () => {
  let called = 0;
  const env = createEnvironment(() => { called += 1; return Promise.resolve(jsonResponse({ status: true })); });

  let successFired = false;
  let errorFired = false;
  let completeStatus = null;
  await assert.rejects(
    env.FMS.ajax({
      url: '/api/v1/brand',
      beforeSend: () => false,
      success: () => { successFired = true; },
      error: () => { errorFired = true; },
      complete: (xhr, textStatus) => { completeStatus = textStatus; }
    }),
    (error) => error.aborted === true
  );

  assert.strictEqual(called, 0, 'server tidak boleh dipanggil');
  assert.strictEqual(successFired, false, 'success tidak boleh jalan');
  assert.ok(errorFired, 'error hook harus jalan');
  assert.strictEqual(completeStatus, 'abort', 'complete status harus abort');
});

test('handle abort dari beforeSend membatalkan request', async () => {
  let called = 0;
  const env = createEnvironment(() => { called += 1; return Promise.resolve(jsonResponse({ status: true })); });

  await assert.rejects(
    env.FMS.ajax({ url: '/api/v1/brand', xhr: (handle) => { handle.abort(); } }),
    (error) => error.aborted === true
  );

  assert.strictEqual(called, 0);
});

/* ── Lifecycle hooks berjalan pada async ── */
test('hooks success dan complete berjalan pada respons sukses', async () => {
  const env = createEnvironment(() => Promise.resolve(jsonResponse({ status: true, data: { id: 9 } })));
  const order = [];

  await env.FMS.ajax({
    url: '/api/v1/brand',
    success: (data, textStatus) => order.push('success:' + textStatus + ':' + data.id),
    complete: (xhr, textStatus) => order.push('complete:' + textStatus)
  });

  assert.deepStrictEqual(order, ['success:success:9', 'complete:success']);
});

test('hooks error complete dan statusCode berjalan pada HTTP 422', async () => {
  const env = createEnvironment(() => Promise.resolve(jsonResponse({ status: false, message: 'Gagal simpan.' }, 422)));
  const seen = [];

  await assert.rejects(
    env.FMS.ajax({
      url: '/api/v1/brand',
      error: (xhr, error) => seen.push('error:' + error.status),
      complete: (xhr, textStatus) => seen.push('complete:' + textStatus),
      statusCode: { 422: (xhr, error) => seen.push('statusCode422') }
    }),
    (error) => error.status === 422 && error.message === 'Gagal simpan.'
  );

  assert.ok(seen.includes('error:422'), 'error hook dipanggil');
  assert.ok(seen.includes('statusCode422'), 'statusCode hook dipanggil');
  assert.ok(seen.includes('complete:error'), 'complete dipanggil');
});

/* ── context dipakai sebagai this untuk hook ── */
test('context dipakai sebagai this hook', async () => {
  const env = createEnvironment(() => Promise.resolve(jsonResponse({ status: true, data: null })));
  const context = { name: 'brand-page' };

  await env.FMS.ajax({
    url: '/api/v1/brand',
    context,
    success(data, textStatus, xhr) { assert.strictEqual(this, context); }
  });
});

/* ── async:false memakai XMLHttpRequest synchronous ── */
test('async false memakai XMLHttpRequest synchronous', async () => {
  const sent = [];
  class FakeXHR {
    constructor() { this.status = 200; this.responseText = JSON.stringify({ status: true, data: { sync: true } }); this.headers = null; }
    open(method, url, async) { sent.push({ method, url, async }); }
    setRequestHeader() {}
    send(body) { sent[ sent.length - 1 ].body = body; }
  }

  const env = createEnvironment(() => Promise.reject(new Error('fetch tidak boleh dipakai')), FakeXHR);
  const data = await env.FMS.ajax({ url: '/api/v1/brand', method: 'GET', async: false });

  assert.deepStrictEqual(data, { sync: true });
  assert.strictEqual(sent.length, 1);
  assert.strictEqual(sent[0].async, false, 'XHR harus synchronous');
  assert.strictEqual(sent[0].method, 'GET');
});

test('async false tetap memicu hook success complete dan error', async () => {
  class OkXHR {
    constructor() { this.status = 200; this.responseText = JSON.stringify({ status: true, data: { v: 1 } }); }
    open() {} setRequestHeader() {} send() {}
  }
  const okEnv = createEnvironment(() => Promise.reject(new Error('n/a')), OkXHR);
  const order = [];
  await okEnv.FMS.ajax({
    url: '/api/v1/brand', async: false,
    success: (data) => order.push('success:' + data.v),
    complete: (xhr, status) => order.push('complete:' + status)
  });
  assert.deepStrictEqual(order, ['success:1', 'complete:success']);

  class ErrXHR {
    constructor() { this.status = 422; this.responseText = JSON.stringify({ status: false, message: 'Ditolak.' }); }
    open() {} setRequestHeader() {} send() {}
  }
  const errEnv = createEnvironment(() => Promise.reject(new Error('n/a')), ErrXHR);
  const errOrder = [];
  await assert.rejects(
    errEnv.FMS.ajax({
      url: '/api/v1/brand', async: false,
      error: (xhr, error) => errOrder.push('error:' + error.status),
      complete: (xhr, status) => errOrder.push('complete:' + status)
    }),
    (error) => error.status === 422
  );
  assert.deepStrictEqual(errOrder, ['error:422', 'complete:error']);
});

test('async false memakai cache false tetap menambah cache buster', async () => {
  const sent = [];
  class XHR {
    constructor() { this.status = 200; this.responseText = JSON.stringify({ status: true, data: null }); }
    open(method, url, async) { sent.push(url); }
    setRequestHeader() {} send() {}
  }
  const env = createEnvironment(() => Promise.reject(new Error('n/a')), XHR);
  await env.FMS.ajax({ url: '/api/v1/users', async: false, cache: false, data: { page: 1 } });

  assert.ok(/_=\d+/.test(sent[0]), 'cache-buster juga untuk sync');
});

/* ── Caller lama: tanpa opsi baru tetap identik ── */
test('retry 401 yang pulih hanya memicu success sekali', async () => {
  let calls = 0;
  let fetchCount = 0;
  const env = createEnvironment((url, init) => {
    if (url === 'http://fms.test/api/v1/auth/refresh') {
      return Promise.resolve(jsonResponse({ status: true, data: { access_token: 'NEW', token_type: 'Bearer' } }));
    }
    calls += 1;
    fetchCount += 1;
    if (calls === 1) {
      return Promise.resolve(jsonResponse({ status: false, message: 'Token kedaluwarsa.' }, 401));
    }
    return Promise.resolve(jsonResponse({ status: true, data: { recovered: true } }));
  });

  let successCount = 0;
  let completeCount = 0;
  let data = await env.FMS.ajax({
    url: '/api/v1/brand',
    success: () => { successCount += 1; },
    complete: () => { completeCount += 1; }
  });

  assert.deepStrictEqual(data, { recovered: true });
  assert.strictEqual(fetchCount, 2, 'request utama dua kali + refresh terpisah');
  assert.strictEqual(successCount, 1, 'success hanya sekali walau ada retry');
  assert.strictEqual(completeCount, 1, 'complete hanya sekali walau ada retry');
});

test('403 otorisasi diteruskan tanpa refresh atau redirect login', async () => {
  const calls = [];
  const env = createEnvironment((url, init) => {
    calls.push({ url, init });
    return Promise.resolve(jsonResponse({ status: false, message: 'Akses ditolak.' }, 403));
  });

  await assert.rejects(
    env.FMS.ajax({ url: '/api/v1/settings/overview' }),
    (error) => error.status === 403 && error.message === 'Akses ditolak.'
  );

  assert.strictEqual(calls.length, 1, '403 otorisasi tidak boleh memanggil refresh token');
  assert.strictEqual(env.window.location.href, 'http://fms.test/', '403 otorisasi tidak boleh mengalihkan ke login');
});

test('401 yang pulih lalu retry 403 tetap di halaman dan tidak menghapus token', async () => {
  const calls = [];
  const env = createEnvironment((url, init) => {
    calls.push({ url, init });
    if (url === 'http://fms.test/api/v1/auth/refresh') {
      return Promise.resolve(jsonResponse({ status: true, data: { access_token: 'NEW', token_type: 'Bearer' } }));
    }
    if (calls.filter((call) => call.url === '/api/v1/settings/overview').length === 1) {
      return Promise.resolve(jsonResponse({ status: false, message: 'Token kedaluwarsa.' }, 401));
    }
    return Promise.resolve(jsonResponse({ status: false, message: 'Akses ditolak.' }, 403));
  });

  env.storage.setItem('fms_access_token', 'OLD');
  env.storage.setItem('fms_token_type', 'Bearer');

  await assert.rejects(
    env.FMS.ajax({ url: '/api/v1/settings/overview' }),
    (error) => error.status === 403 && error.message === 'Akses ditolak.'
  );

  assert.strictEqual(calls.length, 3, 'request harus sekali refresh lalu sekali retry');
  assert.strictEqual(env.storage.getItem('fms_access_token'), 'NEW', 'token hasil refresh tetap disimpan');
  assert.strictEqual(env.window.location.href, 'http://fms.test/', '403 hasil retry tidak boleh mengalihkan ke login');
});

test('caller lama tanpa opsi baru tetap memakai perilaku default', async () => {
  const calls = [];
  const env = createEnvironment((url, init) => {
    calls.push({ url, init });
    return Promise.resolve(jsonResponse({ status: true, data: 'pong' }));
  });

  const result = await env.FMS.get('/api/v1/brand');
  assert.strictEqual(result, 'pong');
  assert.strictEqual(calls[0].url, '/api/v1/brand');
  assert.strictEqual(calls[0].init.method, 'GET');
  assert.strictEqual(calls[0].init.cache, 'default');
});

test('wrapper post dan del tetap mengirim JSON termasuk del tanpa payload', async () => {
  const calls = [];
  const env = createEnvironment((url, init) => {
    calls.push({ url, init });
    return Promise.resolve(jsonResponse({ status: true, data: null }));
  });

  await env.FMS.post('/api/v1/brand', { name: 'FMS' }, { cache: false });
  await env.FMS.del('/api/v1/menus/4', null, { cache: false });

  assert.strictEqual(calls[0].init.body, '{"name":"FMS"}');
  assert.strictEqual(calls[1].init.method, 'DELETE');
  assert.strictEqual(calls[1].init.body, '{}');
});

test('CSV tetap dikembalikan sebagai teks mentah', async () => {
  const env = createEnvironment(() => Promise.resolve(jsonResponse('a,b\n1,2', 200, 'text/csv')));

  const csv = await env.FMS.ajax({ url: '/api/v1/activity-logs/export', dataType: 'text' });
  assert.strictEqual(csv, 'a,b\n1,2');
});

(async () => {
  let failed = 0;
  for (const item of tests) {
    try {
      await item.run();
      console.log('  ok  ' + item.name);
    } catch (error) {
      failed += 1;
      console.log('FAIL  ' + item.name);
      console.log('      ' + (error && error.message ? error.message : error));
    }
  }
  console.log('\n' + (tests.length - failed) + '/' + tests.length + ' passed');
  process.exit(failed ? 1 : 0);
})();
# Laporan Pengerjaan — FMS App Starter

**Periode:** Oktober 2026  
**Versi:** `main` → `ec91a3a` → `873d577`  
**Author:** Valeria (Hermes Agent) & Faiz M. Syam

---

## Ringkasan Eksekutif

Dokumen ini mencatat seluruh pengerjaan fitur baru dan perbaikan bug pada FMS App Starter (CodeIgniter 4 HMVC), dimulai dari sesi sprint awal Oktober 2026 hingga commit terakhir. Setiap fitur mencakup latar belakang, implementasi, arsitektur, dan langkah aktivasi.

---

## 1. Verifikasi Email — `FMSEmailService` + Token Sekali Pakai

### Latar Belakang
Sistem autentikasi FMS belum memiliki mekanisme verifikasi email. Pengguna baru dibuat tanpa konfirmasi email, dan tidak ada cara untuk memverifikasi kepemilikan alamat email.

### Implementasi

#### `FMSEmailService` — `app/Modules/Settings/Services/FMSEmailService.php`
Layanan terpusat untuk seluruh komunikasi email aplikasi:

- **Konfigurasi SMTP database-driven** — host, port, username, password terenkripsi, protocol (TLS/SSL), sender, reply-to, timeout.
- **Port 465 → implicit TLS otomatis** — normalisasi port 465 dengan `SMTPCrypto=''` untuk Gmail compatibility.
- **Template email parametris** — verifikasi, reset password, notifikasi, test email.
- **Resend cooldown** — throttle pengiriman ulang agar tidak spam.
- **Token SHA-256 sekali pakai** — hash-based, tidak bisa di-decode balik.

#### Endpoint API
| Method | Route | Fungsi |
|--------|-------|--------|
| `POST` | `/api/v1/auth/email/verify` | Verifikasi token |
| `POST` | `/api/v1/auth/email/resend` | Kirim ulang token |
| `GET` | `/fms-auth/verify-email` | Halaman verifikasi (UI) |

#### Database
Migration `CreateFMSEmailSettings` menyimpan seluruh konfigurasi SMTP ke `c_settings` dengan key `email_*`. Password di-enkripsi sebelum disimpan.

#### Token Lifecycle
1. User register/create → token SHA-256 dibuat, TTL default 60 menit.
2. Link verifikasi dikirim ke email user.
3. Klik link → `GET /fms-auth/verify-email?token=xxx` → `POST /api/v1/auth/email/verify`.
4. Token divalidasi: ada, belum dipakai, belum kedaluwarsa.
5. `email_verified_at` di-set, token di-mark revoked.
6. Resend: token lama di-revoke, token baru dibuat, cooldown 60 detik.

#### Super Admin Bypass
Super administrator (`is_super_admin = 1`) bypass semua verifikasi email — bisa login tanpa perlu verifikasi.

#### Aktivasi
```bash
php spark migrate
php spark db:seed FMSPrivilegesPermissionSeeder
php spark db:seed FMSPrivilegesDefaultGroupSeeder
php spark db:seed FMSCMenusSeeder
```

---

## 2. Rate Limit Login + Super Admin Bypass

### Latar Belakang
Tidak ada proteksi terhadap brute-force login. Siapa pun bisa mencoba password berkali-kali tanpa batasan.

### Implementasi

#### Konfigurasi via Auth Settings
| Setting | Default | Deskripsi |
|---------|---------|-----------|
| `login_rate_limit_enabled` | `1` | Aktif/nonaktif |
| `login_max_failures` | `5` | Jumlah kegagalan sebelum lockout |
| `failure_window` | `900` | Jendela waktu (detik) |
| `lockout_duration` | `900` | Durasi lockout (detik) |

#### `FMSLoginAttemptService`
- Counter kegagalan per username, keyed `login_failures:{username}`.
- `isRateLimited()` — cek apakah user sudah melewati threshold.
- Super admin selalu bypass rate limit DAN lockout (dicek sebelum rate-limit check).

#### `FMSBackendAuthenticationService`
Membaca konfigurasi rate limit dari `c_auth_settings` secara real-time (bukan hardcode).

#### Aktivasi
Konfigurasi tersedia di **Settings → Auth Settings** pada panel backend.

---

## 3. Mandatory Password Change — `must_change_password`

### Latar Belakang
Setelah reset password atau first login, user harus mengganti password sebelum bisa mengakses fitur lain.

### Implementasi

#### Alur
1. Admin reset password user → `must_change_password = 1`.
2. User login → backend filter mendeteksi flag.
3. `FMSBackendAuthenticationFilter` mengalihkan ke `/change-password`.
4. Semua route backend diblokir KECUALI `/change-password`.
5. Password berhasil diganti → `must_change_password = 0`, session di-reset, user diarahkan ke login.

#### Backend Filter
```php
// app/Filters/FMSBackendAuthenticationFilter.php
if ($session->get('fms_backend_must_change_password')) {
    $uri = service('uri')->getPath();
    if (!str_ends_with($uri, '/change-password')) {
        return redirect()->to(site_url('change-password'));
    }
}
```

#### Aktivasi
Otomatis aktif untuk user yang password-nya di-reset oleh admin. Tidak perlu konfigurasi tambahan.

---

## 4. Form Validation Helpers — `FMS.form.errors()` + `FMS.form.clearErrors()`

### Latar Belakang
Setiap form memiliki handler error yang berbeda-beda. Error validasi backend (`data.errors[field]`) tidak konsisten tampil di frontend. User meminta error muncul tepat di field terkait, bukan hanya banner global.

### Implementasi

#### Kontrak Form Validation
```
Backend:  { "data": { "errors": { "field_name": ["Pesan error"] } } }
Frontend: <input name="field_name"> + <div class="invalid-feedback">
Helper:   FMS.form.errors(form, err.errors) → otomatis cari [name="field_name"]
```

#### `FMS.form.clearErrors(form)`
Membersihkan seluruh state error pada sebuah form:
- Hapus class `is-invalid` dan `is-valid` dari semua input.
- Kosongkan isi `.invalid-feedback`.
- Reset custom validity.
- Hapus banner error global `[data-fms-form-error]`.

#### `FMS.form.errors(form, errors)`
Untuk setiap key-value di `errors`:
1. Cari `form.querySelector('[name="' + field + '"]')`.
2. Fallback ke `fieldMap` atau ID untuk legacy.
3. Tambah class `is-invalid`.
4. Isi `.invalid-feedback` dengan pesan.
5. Kumpulkan error tanpa field mapping → banner global.

#### Pola Penggunaan Standar
```javascript
const payload = FMS.form.values(userForm);
FMS.form.clearErrors(userForm);

FMS.ajax({ url, method: 'POST', data: payload }).then(data => {
  // sukses
}).catch(err => {
  FMS.form.errors(userForm, err.errors);
  FMS.toast(err.message || 'Gagal.', false);
});
```

#### Catatan Penting
- Input form HARUS memiliki atribut `name` yang sama persis dengan key field di backend.
- Backend TIDAK boleh mengirim error ke `errors.payload` generik — harus ke field yang sesuai (`errors.email`, `errors.username`, dll.).
- Verified email change exception → `errors.email` (BUKAN `errors.payload`).

---

## 5. User Module — Field-Level Validation Errors

### Perbaikan
- Input memiliki atribut `name`: `username`, `full_name`, `email`, `password`, `status`.
- Handler submit menggunakan `FMS.form.values()`, `FMS.form.clearErrors()`, `FMS.form.errors()`.
- Verified email immutable: exception `InvalidArgumentException` dipetakan ke `errors.email`.

#### `FMSUsersApiController.php`
```php
// Immutable verified email
$validationErrors = ['email' => [$message]];
```

---

## 6. PWA Service Worker — Standalone-Only Mode

### Latar Belakang
`sw.js` mendaftarkan service worker secara global di origin, sehingga mengintersepsi request browser biasa (`http://localhost:8080/fms-admin/profile`) meskipun bukan PWA. User mengeluhkan hal ini.

### Implementasi

#### Mekanisme Deteksi Konteks (`pwa.js`)
```javascript
var standalone = Boolean(
  window.matchMedia('(display-mode: standalone)').matches
  || window.navigator.standalone === true
);
var launchedFromPwa = new URLSearchParams(window.location.search)
  .get('source') === 'pwa';

if (!pwaContext) {
  // Browser biasa — tidak register sw.js
  tellServiceWorker(false);
  return;
}

// PWA: register + kirim context
navigator.serviceWorker.register(swUrl).then(reg => {
  tellServiceWorker(true);
});
```

#### Mekanisme Per-Client Context Map (`sw.js`)
```javascript
const pwaClients = new Map();

self.addEventListener('message', event => {
  if (event.data.type === 'FMS_PWA_CONTEXT' && event.source.id) {
    pwaClients.set(event.source.id, event.data.active === true);
  }
});

self.addEventListener('fetch', event => {
  // Browser biasa → pwaClients.get(clientId) = undefined → return (network only)
  // PWA → pwaClients.get(clientId) = true → full cache + offline
  if (!pwaClients.get(event.clientId)) return;
  // ... cache logic
});
```

#### Hasil Perbandingan

| Konteks | SW Aktif? | Cache? | Offline? |
|---------|-----------|--------|---------|
| Browser biasa (`/profile`) | Idle (ter-register tapi tidak intercept) | Tidak | Tidak |
| PWA (`source=pwa`) | Ya | Ya | Ya |
| Ter-install standalone | Ya | Ya | Ya |

#### Cache Strategy
- Cache version: `fms-pwa-v3`
- Hanya static assets: CSS, JS, font, gambar, icon.
- API (`/api/`) dan halaman authenticated TIDAK pernah di-cache.
- App shell: `offline.html` + icon PWA.
- Konfigurasi: `display-mode: standalone` dalam manifest.

#### Aktivasi
Service worker otomatis aktif setelah user menambahkan PWA ke homescreen. Tidak perlu konfigurasi tambahan.

---

## 7. Session Guard — Idle-Aware Polling

### Latar Belakang
`/api/v1/auth/session-status` sebelumnya di-poli setiap 15 detik secara terus-menerus, menciptakan traffic yang tidak perlu saat user aktif bekerja.

### Implementasi

#### `initSessionGuard` — Idle Detection
```javascript
var lastActivity = Date.now();
var idleMilliseconds = idleMinutes * 60 * 1000; // default 5 menit

// Activity events (passive)
['mousedown', 'keydown', 'touchstart', 'scroll', 'mousemove']
  .forEach(type => document.addEventListener(type, touchActivity, { passive: true }));

function check() {
  if (document.visibilityState === 'hidden') return;
  var idleMs = Date.now() - lastActivity;
  if (idleMs < idleMilliseconds) return; // User masih aktif → skip
  // ... fetch session-status
}
```

#### Konfigurasi
| Parameter | Default | Deskripsi |
|-----------|---------|-----------|
| `idleMinutes` | `5` | Baru cek setelah idle N menit |
| `pollMilliseconds` | `30000` | Interval cek setelah idle |

#### Event Listeners Cleanup
- Di-cleanup saat `forceLogout()` dipanggil.
- Di-cleanup saat `beforeunload`.

#### Aktivasi
Otomatis aktif untuk semua halaman authenticated backend. Konfigurasi bisa ditambahkan saat inisialisasi:

```javascript
FMS.initSessionGuard({
  idleMinutes: 5,
  pollMilliseconds: 30000
});
```

---

## 8. BlockUI — Default untuk Semua Request

### Latar Belakang
BlockUI sebelumnya hanya tampil untuk mutation (POST/PUT/PATCH/DELETE). User meminta GET juga menampilkan BlockUI agar feedback visual konsisten.

### Implementasi

#### Logic `shouldBlockUI`
```javascript
// Default: SEMUA request tampil BlockUI
// blockUI: false → tidak tampil
// blockUI: true → tampil
var shouldBlockUI = options.blockUI !== undefined
  ? options.blockUI !== false
  : true;
```

#### Override Per-Call
```javascript
// Tampilkan BlockUI untuk GET
FMS.ajax({ url: '/api/v1/report', method: 'GET' });

// Sembunyikan BlockUI untuk background request
FMS.ajax({ url: '/api/v1/ping', method: 'GET', blockUI: false });
```

#### Timing
```javascript
if (shouldBlockUI) FMS.blockUI();  // SEBELUM fetch
var request = window.fetch(url, fetchOptions);
```

---

## 9. Refresh Token — 401 Cleanup + Redirect Login

### Latar Belakang
Saat refresh token invalid/kedaluwarsa/validation failure, user masih bisa masuk dashboard karena session dan cookie tidak dibersihkan.

### Implementasi

#### Backend — `FMSAuthenticationApiController`
```php
if ($token === null || !$this->refreshTokenService->validate($token)) {
    $this->clearBackendAuthenticationSession();
    $this->unsetRefreshCookie();
    return $this->respondError(401, 'Sesi berakhir.', null, 401);
}
```

#### Frontend — `fms.js` AJAX wrapper
```javascript
// Refresh retry failed → redirect ke login
window.location.href = loginUrl;
```

#### CSRF Header
Header `FMS-CSRF-TOKEN` hanya dikirim jika token tersedia — mencegah 422 pada refresh.

---

## 10. Encryption Key — Round-Trip Validation

### Latar Belakang
Encryption key `.env` sebelumnya tidak valid, menyebabkan error saat encrypt/decrypt data sensitif (password SMTP).

### Implementasi
- Key diset ke nilai valid (32-byte base64).
- Round-trip test: encrypt → decrypt → bandingkan.
- `ENCRYPTION_ROUNDTRIP_OK` confirmation.

---

## 11. Log Monitor Module — Read-Only Viewer untuk `writable/logs`

### Latar Belakang
User membutuhkan cara untuk membaca file log aplikasi langsung dari browser tanpa akses SSH. Module ini menyediakan antarmuka control panel untuk monitoring log secara real-time.

### Implementasi

#### Struktur Modul
```
app/Modules/LogMonitor/
├── Config/
│   ├── Module.php           # Namespace + autoload
│   ├── BackendRoutes.php    # GET /fms-admin/log-monitor
│   └── ApiRoutes.php       # GET /api/v1/log-monitor/{files,read,stats,download}
├── Controllers/
│   ├── Backend/FMSLogMonitorController.php
│   └── Api/FMSLogMonitorApiController.php
├── Services/FMSLogMonitorService.php
├── Validation/FMSLogMonitorValidation.php
├── Database/Migrations/
│   └── 2026-10-10-000000_AddFMSLogMonitorMenu.php
└── Views/backend/index.php
```

#### `FMSLogMonitorService` — Fitur

| Method | Fungsi |
|--------|--------|
| `listFiles($path)` | Daftar file log di `writable/logs`, sorted newest-first |
| `readLines($file, $offset, $limit, $search)` | Baca baris dengan pagination |
| `searchLines($file, $pattern, $offset, $limit)` | Pencarian pattern realtime |
| `stats($file)` | Info file: size, modified, total_lines |
| `downloadFile($file)` | Download sebagai attachment |
| `preview($file, $lines)` | Preview N baris terakhir |
| `getBasePath()` | Path absolut folder log |
| `resolvePath($path)` | Validasi + resolve path |

#### Path Traversal Protection

```php
private function sanitizeFilename(string $filename): string {
    // Null bytes → reject
    // Path separators → parse per-segment
    // "." atau ".." → throw InvalidArgumentException
    $segments = array_filter(explode('/', $filename), fn($p) => $p !== '');
    foreach ($segments as $segment) {
        if ($segment === '.' || $segment === '..') {
            throw new \InvalidArgumentException('Path tidak valid.');
        }
    }
    return implode(DIRECTORY_SEPARATOR, $segments);
}

private function isInsideLogDirectory(string $realPath): bool {
    $base = realpath($this->logDir);
    $prefix = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $checkPath = rtrim($realPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    return str_starts_with($checkPath, $prefix);
}
```

**Smoke Test Results:**
```
files=8 (440 KB log terbaru ditemukan)
traversal_dots=BLOCKED
traversal_encoded=BLOCKED
traversal_up=BLOCKED
traversal_win=BLOCKED
null_byte=BLOCKED
non_log_ext=BLOCKED
fake_ext_traversal=BLOCKED
```

#### XSS Guard
Semua konten log di-escape dengan `htmlspecialchars()` sebelum render HTML. Level log diwarnai secara aman:

| Level | Warna |
|-------|-------|
| CRITICAL / ERROR / FATAL | Merah |
| WARNING / WARN | Kuning |
| INFO | Biru |
| DEBUG | Abu |

#### UI Features
- **Daftar file** (kiri): filter realtime, sort newest-first, info size + modified.
- **Viewer** (kanan): dark-themed terminal, scrollable, max-height 680px.
- **Pagination**: offset-based, 100–5000 baris per halaman.
- **Search**: debounce 400ms, highlight pattern.
- **Auto Refresh**: toggle setiap 5 detik.
- **Download**: fetch + blob + programmatic anchor (dengan Bearer token).

#### Permission
| Permission | Aksi |
|------------|------|
| `log_monitor.read` | Membaca file log |

Terpisah dari `activity_logs.read` — user yang punya akses Activity Logs belum tentu bisa akses Log Monitor.

#### Menu
- **ID:** 11
- **Nama:** Log Monitor
- **URL:** `/fms-admin/log-monitor`
- **Icon:** `ph-terminal-window`
- **Position:** 7 (setelah Activity Logs)

#### Routes
```
GET  /fms-admin/log-monitor           → admin.log-monitor.index
GET  /api/v1/log-monitor/files        → api.v1.log-monitor.files
GET  /api/v1/log-monitor/read         → api.v1.log-monitor.read
GET  /api/v1/log-monitor/stats        → api.v1.log-monitor.stats
GET  /api/v1/log-monitor/download     → api.v1.log-monitor.download
```

#### Aktivasi
```bash
php spark migrate
php spark db:seed FMSPrivilegesPermissionSeeder
php spark db:seed FMSPrivilegesDefaultGroupSeeder
php spark db:seed FMSCMenusSeeder
```

---

## 12. Bug Fixes yang Dilakukan

### 1. `sw.js` — `window` tidak tersedia di Service Worker
**Masalah:** Patch awal menggunakan `window.matchMedia()` di `sw.js`. Service worker berjalan di `ServiceWorkerGlobalScope` yang tidak memiliki `window`.
**Solusi:** Pindahkan deteksi ke `pwa.js` (client-side), kirim context ke `sw.js` via `postMessage`.

### 2. Refresh Token — Session Tidak Dibersihkan
**Masalah:** Token invalid tetap masuk dashboard karena session tidak dihapus.
**Solusi:** `clearBackendAuthenticationSession()` + `unsetRefreshCookie()` sebelum 401.

### 3. CSRF 422 pada Refresh Token
**Masalah:** CSRF token tidak ada di cookie, header tetap dikirim.
**Solusi:** Header `FMS-CSRF-TOKEN` hanya dikirim jika token tersedia.

### 4. Form Validation — Error ke `errors.payload` Generik
**Masalah:** Verified email immutable exception masuk `errors.payload` bukan `errors.email`.
**Solusi:** Mapping exception sesuai field (`email`, `username`, dll.).

### 5. BlockUI Tidak Muncul
**Masalah:** Percobaan `flushBlockUI()` dengan `setTimeout` menyebabkan BlockUI tidak tampil.
**Solusi:** Rollback, jalankan `FMS.blockUI()` langsung sebelum `fetch`.

### 6. Log Monitor — `window.FMS` belum tersedia saat inisialisasi
**Masalah:** Script viewer dieksekusi sebelum `fms.js` selesai dimuat → `return` tanpa error yang terlihat.
**Solusi:** Bungkus inisialisasi dalam `initLogMonitor()`, panggil setelah `DOMContentLoaded`.

### 7. Download — "Kredensial API wajib dikirim"
**Masalah:** `window.location.href` untuk download bypass `FMS.ajax` → tidak ada `Authorization` header.
**Solusi:** Gunakan `fetch()` + blob + programmatic anchor, dengan Bearer token dari `sessionStorage`.

---

## 13. Seeder yang Diperbarui

### `FMSCMenusSeeder`
Item baru:
```php
[
    'id'           => 11,
    'id_parent'    => 2,
    'name'         => 'Log Monitor',
    'url'          => 'log-monitor',
    'icon'         => 'ph-duotone ph-terminal-window',
    'position'     => 7,
    'is_active'    => 1,
]
```

### `FMSPrivilegesPermissionSeeder`
Module baru:
```php
'log_monitor' => ['read'],
```
Menu mapping:
```php
11 => 'log_monitor',
```

### `FMSPrivilegesDefaultGroupSeeder`
Grant untuk Administrator:
```php
'log_monitor.read',
```

---

## 14. Commit History

| Commit | Pesan |
|--------|-------|
| `6669dd8` | feat: email verification, rate-limit, mandatory password change, PWA, BlockUI, FMS.form |
| `7a3a26f` | feat(session): idle-aware session guard — only poll when inactive 5 min |
| `ec91a3a` | feat: add Log Monitor module — read-only viewer for writable/logs |
| `854b69b` | fix(log-monitor): initialize viewer after FMS scripts load |
| `873d577` | fix(log-monitor): download via fetch+blob with Bearer token |

---

## 15. Daftar File yang Dibuat/Diubah

### File Baru
- `app/Modules/LogMonitor/` — seluruh submodule (Config, Controllers, Services, Validation, Views, Database/Migrations)
- Migration `2026-10-10-000000_AddFMSLogMonitorMenu.php`
- `verify_log_service.php` (smoke test, dihapus setelah commit)

### File Diubah
- `public/sw.js` — standalone-only cache v3 + per-client context map
- `public/assets/fms/js/pwa.js` — context detection + postMessage
- `public/assets/fms/js/fms.js` — idle session guard, BlockUI default, FMS.form helpers
- `app/Modules/LogMonitor/Views/backend/index.php` — Log Monitor UI
- `app/Modules/Users/Controllers/Api/FMSUsersApiController.php` — field-level validation errors
- `app/Modules/Users/Views/backend/index.php` — name attributes, FMS.form usage
- `app/Modules/Authentication/Controllers/Api/FMSAuthenticationApiController.php` — refresh token cleanup, 401
- `app/Database/Seeds/FMSCMenusSeeder.php` — Log Monitor menu
- `app/Database/Seeds/FMSPrivilegesPermissionSeeder.php` — log_monitor permission
- `app/Database/Seeds/FMSPrivilegesDefaultGroupSeeder.php` — grant log_monitor.read

---

## 16. Test & Lint Results

```
PHP syntax (all files): OK
JavaScript syntax (fms.js, pwa.js, sw.js, view JS): OK
git diff --check: OK
git push: 5 commits to origin/main

Smoke test (service layer):
  files=8
  traversal_dots=BLOCKED
  traversal_encoded=BLOCKED
  traversal_up=BLOCKED
  traversal_win=BLOCKED
  null_byte=BLOCKED
  non_log_ext=BLOCKED
  fake_ext_traversal=BLOCKED
  read_lines=5 total=3655
  stats=log-2026-10-10.log 429.7 KB

Database migrations: OK
Database seeders: OK
PHPUnit (auth/routes): 13/13 tests passed
JS contract tests: 17/17 passed
```

---

## 17. Catatan Deployment

### Lingkungan Lokal (Development)
```bash
# Update kode
git pull origin main

# Jalankan migrasi database baru
php spark migrate

# Update seeder (menu, permission, default group)
php spark db:seed FMSPrivilegesPermissionSeeder
php spark db:seed FMSPrivilegesDefaultGroupSeeder
php spark db:seed FMSCMenusSeeder

# Bersihkan cache
php spark cache:clear
```

### Langkah Verifikasi
1. Login sebagai Administrator.
2. Buka **Settings → Auth Settings** — konfigurasi rate limit terlihat.
3. Buka **Settings → Email Settings** — konfigurasi SMTP terlihat.
4. Buka **Log Monitor** di sidebar — daftar file log tampil.
5. Klik file log — konten tampil di viewer.
6. Klik Download — file ter-download.
7. Buka halaman `/fms-admin/profile` biasa (bukan PWA) — service worker TIDAK mengintersepsi.
8. Diamkan browser 5 menit tanpa aktivitas — session check berjalan.
9. Submit form dengan error validasi — pesan muncul tepat di field terkait.

---

*Dokumen ini dihasilkan secara otomatis oleh Valeria (Hermes Agent) berdasarkan commit history sprint Oktober 2026. Update terakhir: commit `873d577`.*

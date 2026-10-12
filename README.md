# FMS CodeIgniter 4 HMVC

Fondasi aplikasi modular berbasis CodeIgniter 4 dengan arsitektur API-first, vertical-slice HMVC, dan antarmuka admin FMS.

> Status: fondasi HMVC dan API-first aktif. Modul Users, UserGroups, Privileges, AdminMenus, Profile, Brand, dan Activity Logs menggunakan controller Backend render-only dengan browser client yang memanggil API same-origin. Beberapa integrasi lanjutan tetap dikembangkan.

## Teknologi

- PHP `^8.1`
- CodeIgniter `4.7.4`
- Composer
- MySQL/MariaDB dengan InnoDB dan `utf8mb4`
- PHPUnit 10
- Bootstrap 5
- OpenAPI 3.1 untuk dokumentasi API target

## Arsitektur

Setiap domain ditempatkan dalam satu modul agar sistem tetap mudah dipelihara ketika membesar.

```text
app/Modules/Users/
├── Config/
│   ├── ApiRoutes.php
│   └── BackendRoutes.php
├── Controllers/
│   ├── Api/
│   │   └── FMSUsersApiController.php
│   └── Backend/
│       └── FMSUsersBackendController.php
├── Models/
├── Services/
├── Validation/
└── Views/
    └── backend/
        └── index.php
```

Pola yang sama digunakan oleh modul:

- `Authentication`
- `Users`
- `UserGroups`
- `Brand`
- `AdminMenus`
- `Privileges`
- `Profile`
- `ActivityLogs`
- `Uploads`

Pembagian tanggung jawab:

- `Controllers/Api` menerima request HTTP dan mengembalikan JSON.
- `Controllers/Backend` hanya merender shell atau view HTML.
- `Services` menjadi satu-satunya tempat business rules domain.
- `Models` menangani akses database dan hanya dipakai melalui service.
- `Validation` menetapkan kontrak input.
- `Views` menjadi presentation layer dan tidak mengakses database.

Controller Backend tidak memanggil controller API sebagai class dan tidak menggunakan cURL internal. Browser memanggil API same-origin secara langsung melalui `FMS.ajax`:

```text
GET /fms-admin/user-groups
        ↓
FMSUserGroupsBackendController::index()
        ↓
UserGroups/Views/backend/index.php
        ↓ FMS.get('/api/v1/user-groups')
FMSUserGroupsApiController::index()
        ↓
FMSUserGroupService
        ↓
FMSUserGroupModel
```

Backend controller pada modul API-first hanya berisi method `index()` yang memanggil `requireBackendPermission()` lalu `fmsLayout()`. Contohnya:

- `app/Modules/UserGroups/Controllers/Backend/FMSUserGroupsBackendController.php`
- `app/Modules/Privileges/Controllers/Backend/FMSPrivilegesBackendController.php`
- `app/Modules/Users/Controllers/Backend/FMSUsersBackendController.php`

## Route

Route dipisahkan per modul:

- `Config/ApiRoutes.php` dimuat satu kali di dalam group `/api/v1`.
- `Config/BackendRoutes.php` dimuat satu kali di dalam group `/fms-admin`.
- Auto-routing harus dinonaktifkan.
- Setiap endpoint harus memiliki HTTP verb, nama route, controller, dan filter yang eksplisit.

Contoh endpoint yang saat ini digunakan:

```text
/api/v1/auth/*
/api/v1/users/*
/api/v1/user-groups/*
/api/v1/brand/*
/api/v1/menus/*
/api/v1/groups/*
/api/v1/permissions/*
/api/v1/privileges/*
/api/v1/activity-logs/*
/api/v1/uploads/*
```

Route backend hanya menjadi route render halaman:

```text
/fms-admin/users
/fms-admin/user-groups
/fms-admin/privileges
```

CRUD, listing, mapping permission, perubahan status, restore, dan detail resource diproses oleh endpoint API, bukan route backend.

`v1` adalah versi kontrak API. Perubahan yang memutus kompatibilitas nantinya dibuat pada `/api/v2` tanpa langsung merusak client lama.

## Kontrak response API

Semua response aplikasi menggunakan envelope terpusat melalui `FMSApiController` dengan struktur berikut:

```json
{
  "code": 200,
  "status": true,
  "message": "Request berhasil.",
  "data": {},
  "signature": {
    "algorithm": "Ed25519",
    "key_id": "fms-response-default",
    "payload_hash": "...",
    "value": "..."
  }
}
```

Nilai `code` selalu sama dengan HTTP status.

HTTP status tetap mengikuti semantik HTTP. Detail exception, SQL, path server, credential, token, dan secret tidak boleh masuk response.

## Autentikasi dan otorisasi

- JWT access token berumur pendek.
- Opaque refresh token dengan rotation, replay detection, dan revocation.
- API key mesin-ke-mesin dengan format `fms_<key-id>_<random-secret>`.
- Basic Auth hanya untuk machine client pada route terbatas.
- RBAC deny-by-default melalui group, permission, dan menu permission.
- Object-level authorization pada setiap resource.
- Rate limiting, lockout, audit, dan secret redaction.
- Verifikasi email dapat dikonfigurasi terpisah untuk pendaftaran publik dan akun yang dibuat admin.

Browser menyimpan access token hanya di memory. Refresh token berada pada cookie `HttpOnly`, `Secure`, dan `SameSite=Strict`. Token tidak disimpan di `localStorage` atau `sessionStorage`.

## Konvensi database

Prefix global database:

```text
fms_
```

Kategori tabel:

- Configuration/reference: `fms_c_*`
- Master data: `fms_m_*`
- Transaction, pivot, token, session, dan log: `fms_t_*`

Contoh:

```text
fms_c_brands
fms_c_menus
fms_c_permissions
fms_m_users
fms_t_user_groups
fms_t_activity_logs
fms_t_api_refresh_tokens
```

Model dan migration memakai nama logical tanpa prefix global, misalnya `m_users` atau `t_activity_logs`. CodeIgniter menambahkan `DBPrefix = 'fms_'`; jangan menulis `fms_` kembali pada property `$table` karena akan menghasilkan prefix ganda.

## Upload gambar

Semua upload gambar wajib melewati pipeline bersama:

- Validasi MIME, magic bytes, decoder, dimensi, pixel count, ukuran, dan jumlah file.
- Tolak path traversal, symlink, nama file client, polyglot, file rusak, dan decompression bomb.
- Auto-orient lalu hapus metadata EXIF/IPTC/XMP.
- Re-encode menjadi WebP statis.
- Nama file menggunakan prefix `fms_` dan identifier acak.
- Folder upload yang dibuat aplikasi menggunakan prefix `fms_`.
- Simpan di luar web root atau private object storage.
- Gunakan temporary file, decode ulang hasil, SHA-256, dan atomic rename.
- Authorization, quota, rate limit, audit, transaksi database, serta orphan cleanup wajib diterapkan.

## Template dan aset admin

Dokumentasi/referensi view admin berada di:

```text
templates-admin/html/
```

Folder tersebut hanya referensi dan tidak menjadi runtime asset. Implementasi final ditempatkan pada:

```text
app/Views/**
app/Modules/*/Views/**
```

Aset runtime berada di:

```text
public/assets/fms/
```

Ketentuan view:

- Gunakan `site_url()`, `base_url()`, atau `fmsAssets()` untuk URL.
- Escape output dinamis dengan `esc()`.
- Gunakan tag PHP lengkap `<?php echo esc(...); ?>`.
- Gunakan CSRF CodeIgniter untuk form/cookie-authenticated mutation.
- Jangan mengacu langsung ke `templates-admin` dari runtime view.
- Branding runtime berasal dari modul Brand atau fallback konfigurasi FMS.
- Meta `theme-color` backend mengikuti nilai terakhir `--primary-rgb` pada `public/assets/fms/css/styles.css`, dikonversi menjadi hex, lalu mengikuti `localStorage.primaryRGB` bila tersedia melalui `FMSTheme.applyPrimary()`.

## Halaman error

Halaman HTML error yang interaktif berada di:

```text
app/Views/errors/html/_error_layout.php
app/Views/errors/html/error_400.php
app/Views/errors/html/error_404.php
app/Views/errors/html/production.php
public/assets/fms/css/fms-error.css
```

Ketentuan error view:

- Wrapper `error_404.php`, `error_400.php`, dan `production.php` hanya menyiapkan `$meta` lalu memanggil `_error_layout.php`.
- Layout membaca identitas brand dari `FMSBrandIdentityService` dengan fallback `brand/logo`, `brand/logo-light`, dan `brand/favicon`.
- Warna primary mengikuti `--primary-rgb` terakhir dari `public/assets/fms/css/styles.css`, sama seperti layout backend.
- Kartu error memakai efek glassmorphism dengan `backdrop-filter` dan surface transparan.
- Tidak ada shortcut keyboard tema; perubahan tema hanya melalui tombol pada error layout.
- Halaman exception debug `error_exception.php` tidak diubah.

## Standar penulisan PHP

Nama variabel, parameter, property, alias query, dan nilai loop harus lengkap serta deskriptif.

Dilarang:

```php
$req;
$res;
$usr;
$cfg;
$db;
$q;
$tmp;
$val;
$i;
$e;
```

Gunakan:

```php
$request;
$response;
$user;
$configuration;
$databaseConnection;
$queryBuilder;
$temporaryFilePath;
$validatedData;
$index;
$exception;
```

Akronim domain resmi seperti `FMS`, `API`, `JWT`, `URL`, `UUID`, dan `CSRF` diperbolehkan.

Semua output PHP pada view wajib memakai tag lengkap:

```php
<?php echo esc($userName); ?>
```

Short echo tag berikut dilarang:

```php
<?= esc($userName) ?>
```

## Panduan Memulai Cepat (Quick Start Setelah Clone)

Ikuti langkah-langkah berikut secara berurutan saat pertama kali melakukan clone repository:

### 1. Clone Repository & Putuskan Remote Starter
Clone repository ke folder proyek baru Anda:
```bash
git clone https://github.com/faizmsyam/codeigniter4-hmvc.git nama-proyek-anda
cd nama-proyek-anda
```

> ⚠️ **PENTING: Segera Ganti Remote Origin (Cegah Push Balik ke Repo Starter)**:
> Repository ini adalah template starter. Jangan pernah melakukan `git push` ke repository starter ini agar tidak merusak codebase template. Segera arahkan remote origin ke repository Git proyek baru Anda:
> ```bash
> git remote set-url origin https://github.com/username-anda/repo-proyek-baru.git
> # Verifikasi remote sudah berubah:
> git remote -v
> ```
> *(Jika belum membuat repository baru di GitHub/GitLab, Anda dapat memutuskan remote sementara dengan `git remote remove origin`).*

### 2. Pasang Dependensi Composer
```bash
composer install
```

### 3. Salin & Konfigurasi Berkas Environment (`.env`)
Salin file template environment bawaan:
```bash
cp env .env
```
Buka file `.env` dan sesuaikan pengaturan dasar berikut:
```ini
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = db_starter_ci4
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.DBPrefix = fms_
database.default.port = 3306
FMS_HASHIDS_SALT = FMS-2026-fmsprojects.my.id-ID-Obfuscation-9c7b4e2a
FMS_HASHIDS_MIN_LENGTH = 12
```
> **Catatan**: Pastikan database MySQL sudah dibuat (misal: `CREATE DATABASE db_starter_ci4;`). `FMS_HASHIDS_SALT` wajib minimal 32 karakter untuk enkripsi obfuscation ID menu & user group. Jangan pernah melakukan commit file `.env` atau kredensial rahasia ke repository.

### 4. Generate Kriptografi Key (Ed25519 Response, JWT & RSA Encryption)
Aplikasi membutuhkan material kriptografi lokal di `writable/keys/`. Generate seluruh key secara berurutan:
```bash
php spark fms:keys:generate --scope=response
php spark fms:keys:generate --scope=jwt
php spark fms:encryption:generate
```
> **Catatan**: Service enkripsi bawaan (`service('encrypter')`) secara otomatis membaca `writable/keys/encryption.key`. Jadi Anda **tidak perlu lagi** menyetel `encryption.key` manual di file `.env`. Cukup jalankan perintah generate di atas!
Key akan tersimpan di `writable/keys/` dan tidak di-commit ke repository (sudah di-ignore di `.gitignore`).

> **Penting**: Aplikasi akan **terblokir total** (HTTP 500 di semua endpoint dan halaman) jika salah satu key tersebut tidak ada atau kosong. Pesan errornya langsung menunjukkan key yang hilang dan perintah generate-nya:
> ```
> Konfigurasi Kunci / Salt FMS tidak lengkap:
>   - JWT private key (fms-jwt-active.key) tidak ada di /path/writable/keys/fms-jwt-active.key
> Pastikan FMS_HASHIDS_SALT diaktifkan di .env dan jalankan generate keys:
>   php spark fms:keys:generate --scope=response
>   php spark fms:keys:generate --scope=jwt
>   php spark fms:encryption:generate
> ```
> Perintah ini idempoten — aman dijalankan berulang. Tambahkan `--force` hanya jika ingin menimpa key lama dengan key baru.

### 5. Berikan Hak Akses Tulis (Permissions) Direktori `writable/`
Pastikan direktori cache, session, dan log dapat ditulis oleh web server / CLI:
```bash
chmod -R 777 writable/
```

### 6. Jalankan Migrasi Database
Jalankan migrasi CodeIgniter 4 untuk membuat seluruh tabel sistem dan seed otomatis bawaan:
```bash
php spark migrate
```
*(Catatan: Migrasi FMS sudah otomatis menyertakan seeder default privilese, menu, akun awal, dan pengaturan modul storage).*

### 7. Jalankan Server Pengembangan
Jalankan local development server bawaan CodeIgniter:
```bash
php spark serve
```
Aplikasi dapat diakses melalui browser:
- **Halaman Utama**: `http://localhost:8080`
- **Dashboard Backend Admin**: `http://localhost:8080/fms-admin`
- **Modul FMS Object Storage**: `http://localhost:8080/fms-storage`

---

## Penjelasan Variabel Lingkungan (`.env`) & Konfigurasi

Bagi yang baru melakukan clone, berikut penjelasan mengenai blok-blok konfigurasi di `.env`:

### 1. `fmsuploads.*` (Pengaturan Storage)
- **Apakah wajib diisi di `.env`?** **TIDAK WAJIB.**
- **Mengapa?** Modul FMS Object Storage sudah memiliki **UI Pengaturan Storage (FMS Storage Settings)** di menu backend yang menyimpan pengaturannya langsung ke database (`storage_settings`). Driver storage (Local atau S3/MinIO), credential S3, presigned URL, hingga whitelist MIME semuanya dikonfigurasi melalui panel tersebut.
- Baris `fmsuploads.*` di `.env` hanyalah **fallback darurat** jika database belum terisi atau jika Anda ingin meng-override pengaturan storage via environment server (misal di CI/CD pipeline). Untuk pemakaian normal, biarkan default/commented.

### 2. `FMSApiSecurity.*` (Keamanan Baseline API)
- **Apakah perlu diaktifkan di `.env`?** **TIDAK PERLU.**
- **Mengapa?** Filter keamanan API (`FMSApiSecurityBaselineFilter`) **sudah aktif secara otomatis** di seluruh endpoint `api/*` via `app/Config/Filters.php`. Konfigurasi default di `app/Config/FMSApiSecurity.php` sudah disetel aman untuk production:
  - Batas maksimal JSON body: 1MB (`1048576` bytes)
  - Batas maksimal upload: 8MB (`8388608` bytes)
  - Memerlukan header JSON untuk endpoint API non-GET
  - Pengaturan cookie refresh token HttpOnly & SameSite=Strict
- Anda hanya perlu mengaktifkan dan mengubah baris `FMSApiSecurity.*` di `.env` jika aplikasi Anda membutuhkan limit upload yang lebih besar (misal file > 8MB) atau ingin mengaktifkan CORS (`allowCrossOriginRequests = true`).

### 3. `FMS_HASHIDS_SALT` & `FMS_HASHIDS_MIN_LENGTH`
- **Wajib aktif di `.env`**. Digunakan untuk enkripsi auto-increment ID menu dan user groups agar tidak mudah ditebak di URL/API. Minimal 32 karakter.

---

## Perintah modul

Generator modul yang tersedia:

```bash
php spark make:module NamaModul
# atau
php spark fms:make:module NamaModul
```

Generator ini otomatis membuat kerangka modul MVC ber-prefix seragam `FMS`:

```text
app/Modules/NamaModul/
├── Config/
│   └── Routes.php
├── Controllers/
│   └── FMSNamaModulController.php
├── Models/
│   └── FMSNamaModulModel.php
└── Views/
    └── index.php
```

### Cara pakai lengkap

1. Jalankan generator dari root proyek:

   ```bash
   php spark make:module Reports
   ```

2. Generator membuat modul pada `app/Modules/Reports` dan menulis file ber-prefix seragam FMS:
   - `Controllers/FMSReportsController.php` (class `FMSReportsController extends FMSController`)
   - `Models/FMSReportsModel.php` (class `FMSReportsModel extends FMSModel` dengan table `fms_reports`)
   - `Config/Routes.php` (otomatis memetakan route GET `/reports` ke `FMSReportsController::index`)
   - `Views/index.php`

3. Periksa hasilnya:

   ```bash
   find app/Modules/Reports -maxdepth 3 -type f | sort
   php -l app/Modules/Reports/Controllers/FMSReportsController.php
   php -l app/Modules/Reports/Models/FMSReportsModel.php
   ```

4. Untuk modul API-first, ubah struktur menjadi vertical slice FMS:

   ```text
   app/Modules/Reports/
   ├── Config/
   │   ├── ApiRoutes.php
   │   └── BackendRoutes.php
   ├── Controllers/
   │   ├── Api/
   │   │   └── FMSReportsApiController.php
   │   └── Backend/
   │       └── FMSReportsBackendController.php
   ├── Models/
   │   └── FMSReportModel.php
   ├── Services/
   │   └── FMSReportService.php
   ├── Validation/
   └── Views/
       └── backend/
           └── index.php
   ```

5. Pindahkan route API ke `Config/ApiRoutes.php`. File tersebut dimuat di dalam group `/api/v1`, jadi route ditulis relatif terhadap prefix tersebut:

   ```php
   <?php

   use App\Modules\Reports\Controllers\Api\FMSReportsApiController;
   use CodeIgniter\Router\RouteCollection;

   /** @var RouteCollection $routes */
   $routes->group('reports', ['filter' => 'fms-api-authentication'], static function (RouteCollection $routes): void {
       $routes->get('/', [FMSReportsApiController::class, 'index']);
       $routes->post('/', [FMSReportsApiController::class, 'create']);
       $routes->get('(:segment)', [FMSReportsApiController::class, 'show']);
       $routes->patch('(:segment)', [FMSReportsApiController::class, 'update']);
       $routes->delete('(:segment)', [FMSReportsApiController::class, 'destroy']);
   });
   ```

6. Jadikan `Config/BackendRoutes.php` hanya sebagai route render halaman:

   ```php
   <?php

   use App\Modules\Reports\Controllers\Backend\FMSReportsBackendController;
   use CodeIgniter\Router\RouteCollection;

   /** @var RouteCollection $routes */
   $routes->get('reports', [FMSReportsBackendController::class, 'index']);
   ```

7. Controller Backend hanya merender shell:

   ```php
   <?php

   namespace App\Modules\Reports\Controllers\Backend;

   use App\Core\FMSBackendController;

   final class FMSReportsBackendController extends FMSBackendController
   {
       public function index(): string
       {
           $this->requireBackendPermission('reports.read');

           return $this->fmsLayout('index');
       }
   }
   ```

8. Controller API harus extends `FMSApiController`, melakukan permission check, dan mendelegasikan business rule ke service. Jangan memindahkan query atau business rule ke view maupun controller Backend.

9. Pada view, panggil API dengan `FMS.ajax`/`FMS.get`/`FMS.post`/`FMS.patch`/`FMS.del`, bukan cURL internal, controller Backend JSON, atau `fetch` manual:

   ```javascript
   FMS.get(`/api/v1/reports?page=${page}`).then(function (body) {
       renderRows(body.items || []);
   });
   ```

10. Tambahkan permission module ke katalog permission dan default group, lalu jalankan seeder sesuai workflow proyek:

    ```bash
    php spark db:seed FMSPrivilegesPermissionSeeder
    php spark routes | grep -E 'api/v1/reports|fms-admin/reports'
    ```

11. Pastikan route Backend hanya memiliki satu baris render dan semua operasi berada di API:

    ```bash
    php spark routes | grep 'api/v1/reports'
    php spark routes | grep 'fms-admin/reports'
    grep -RIn 'reports/data\|Controllers/Backend.*data\|Controllers/Backend.*store' app/Modules/Reports
    ```

Generator tidak boleh dipakai untuk menimpa modul yang sudah ada tanpa backup. Sebelum menjalankan ulang dengan nama sama, periksa status Git dan gunakan modul baru atau hapus folder hasil generator secara sengaja.

> Catatan ekspektasi: template bawaan generator masih menulis `Config/Routes.php`, controller tunggal `FMSController`, model `FMSModel` dengan tabel placeholder, dan view placeholder. Semua itu hanya titik awal. Modul dianggap API-first hanya bila sudah mengikuti langkah 4–11 di atas.


## Dokumentasi API & API Client (Bruno & Postman)

Repository ini menyertakan koleksi API lengkap untuk seluruh endpoint `api/v1` di folder `api-docs/` dengan dukungan penuh untuk **Bruno** dan **Postman**.

```text
api-docs/
├── FMS_Starter_API.postman_collection.json   # Full Collection Postman v2.1 (70+ endpoint, auto-token script)
├── FMS_Local.postman_environment.json        # Environment Postman (baseUrl, user demo & superadmin)
└── bruno/                                    # Native Bruno Collection (Git-friendly text format)
    ├── bruno.json
    ├── environments/
    │   └── Local.bru
    ├── 01_Authentication/
    ├── 02_Users/
    ├── 03_Profile/
    ├── 04_UserGroups/
    ├── 05_Privileges_and_Permissions/
    ├── 06_AdminMenus/
    ├── 07_FMS_Object_Storage/
    ├── 08_System_Settings/
    ├── 09_Activity_Logs_and_Log_Monitor/
    └── 10_Brand_Identity/
```

### Cara Pakai di Bruno (Sangat Direkomendasikan)
1. Buka aplikasi **Bruno**.
2. Klik **Open Collection** lalu pilih folder `api-docs/bruno`.
3. Pilih Environment **Local** di pojok kanan atas.
4. Buka folder `01_Authentication` -> jalankan request **Login (Admin)**. Token akses (`accessToken`) akan otomatis tersimpan ke environment runtime Bruno dan langsung siap dipakai untuk seluruh request authenticated lainnya!

### Cara Pakai di Postman
1. Buka **Postman** -> klik **Import**.
2. Pilih file `api-docs/FMS_Starter_API.postman_collection.json` dan `api-docs/FMS_Local.postman_environment.json`.
3. Aktifkan environment **FMS Starter - Local Environment**.
4. Jalankan request **Login (Admin)** di folder `01_Authentication`. Script test otomatis mengekstrak `access_token` ke variabel `{{accessToken}}`.

## Pengujian

Jalankan test suite:

```bash
composer test
```

Pemeriksaan tambahan yang wajib dilakukan saat implementasi selesai:

```bash
composer validate --strict
composer audit
php spark routes
php spark migrate:status
```

Quality gate harus memeriksa:

- Duplicate route atau route yang masuk group salah.
- CRUD backend yang seharusnya berada pada controller API.
- Akses model/database dari controller Backend atau view.
- Short echo tag `<?=`.
- Nama variabel ambigu yang dilarang.
- Token browser pada `localStorage`/`sessionStorage`.
- Secret atau credential pada repository, log, dan response.
- Branding lama serta URL aset di luar `/assets/fms/`.

## Status repository saat ini

Source code saat ini memiliki fondasi HMVC dan API-first yang aktif:

- Users API-first dengan login/session-related operations, verifikasi email, unlock, restore, group assignment, dan session management.
- UserGroups API-first dengan list, detail, create, update, delete, restore, status, dan members.
- Privileges API-first dengan overview matrix dan mapping permission group.
- AdminMenus API-first dengan CRUD, reorder, dan action management.
- Profile API-first dengan profile, avatar, password, group switching, dan activity logs.
- Brand dan Activity Logs memakai response envelope API terpusat.
- `FMS.ajax` mendukung async default, cache control, CSRF/Bearer, retry refresh, timeout, abort, dan lifecycle hooks.
- Error page 400/404/production memakai layout FMS interaktif dengan branding dinamis, favicon brand, primary color backend, dan glassmorphism.
- Backend controller untuk modul API-first hanya merender shell halaman.

Masih perlu dilakukan secara bertahap: browser/e2e verification penuh, shimmer loading global, dan penyelesaian integrasi lanjutan yang tercatat pada rencana proyek.

## Cara Mengganti Remote Origin ke Git Lain (Repository Baru)

Jika setelah clone starter ini Anda ingin menyambungkannya ke repository Git proyek Anda sendiri (GitHub, GitLab, Bitbucket, atau Git server lokal):

### 1. Periksa Remote Git Saat Ini
```bash
git remote -v
```

### 2. Ubah URL Remote Origin
Ganti URL origin dengan repository baru Anda (pilih HTTPS atau SSH):

```bash
# Menggunakan HTTPS:
git remote set-url origin https://github.com/username-anda/nama-repo-baru.git

# ATAU menggunakan SSH:
git remote set-url origin git@github.com:username-anda/nama-repo-baru.git
```

> **Alternatif (Hapus lalu Tambah Ulang)**:
> ```bash
> git remote remove origin
> git remote add origin https://github.com/username-anda/nama-repo-baru.git
> ```

### 3. Verifikasi Perubahan Remote
```bash
git remote -v
```
Pastikan `origin (fetch)` dan `origin (push)` sudah menunjuk ke URL repository baru.

### 4. Push Seluruh Kode ke Repository Baru
```bash
# Pastikan berada di branch main
git branch -M main

# Push branch main dan jadikan upstream default
git push -u origin main

# (Opsional) Push seluruh tags jika ingin menyertakan versi rilis starter
git push --tags
```

> **Tips Jika Repository Baru di GitHub Sudah Memiliki File (README / License)**:
> Jika repository baru dibuat dengan opsi centang *Add a README file*, satukan history sebelum push:
> ```bash
> git pull origin main --allow-unrelated-histories --rebase
> git push -u origin main
> ```
> Atau jika repository baru memang disiapkan kosong untuk menerima codebase starter ini sepenuhnya:
> ```bash
> git push -u origin main --force
> ```

### 5. Rekomendasi Proteksi untuk Pemilik Template (Maintainer)
Agar repository starter utama (`faizmsyam/codeigniter4-hmvc`) tidak bisa diotak-atik atau tertimpa oleh developer lain yang clone:
1. **Jadikan Template Repository**: Buka repository di GitHub -> **Settings** -> centang **Template repository**. Pengguna cukup menekan tombol hijau **"Use this template"** untuk membuat repo baru tanpa risiko menyentuh repo starter.
2. **Aktifkan Branch Protection pada `main`**: Buka **Settings** -> **Branches** -> tambah rule untuk branch `main`:
   - Centang *Require a pull request before merging*.
   - Centang *Do not allow bypassing the above settings*.
   - Centang *Block force pushes*.

## Lisensi

Kode aplikasi, integrasi, konfigurasi, dan branding FMS mengikuti ketentuan lisensi proyek. Library pihak ketiga tetap mengikuti lisensi masing-masing dan informasi lisensinya tidak boleh dihapus dari inventaris internal.

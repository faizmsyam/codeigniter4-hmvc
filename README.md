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

## Instalasi pengembangan

Pasang dependency:

```bash
composer install
```

Salin environment template:

```bash
cp env .env
```

Atur sekurang-kurangnya konfigurasi berikut pada `.env` lokal:

```ini
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = fms_application
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.DBPrefix = fms_
database.default.port = 3306
```

Jangan commit `.env`, private signing key, API key, Basic Auth password, JWT private key, atau secret lain.

Jalankan migration dan seeder setelah implementasi schema tersedia:

```bash
php spark migrate
php spark db:seed FMSDatabaseSeeder
```

Jalankan aplikasi:

```bash
php spark serve
```

Alamat default:

```text
http://localhost:8080
```

Admin menggunakan prefix:

```text
/fms-admin
```

## Perintah modul

Generator modul yang tersedia:

```bash
php spark make:module NamaModul
```

Generator tersebut hanya membuat kerangka dasar modul dan belum otomatis memenuhi seluruh kontrak API-first FMS. Struktur hasil generator saat ini mencakup:

```text
app/Modules/NamaModul/
├── Config/
│   └── Routes.php
├── Controllers/
│   └── NamaModulController.php
├── Models/
│   └── NamaModulModel.php
└── Views/
    └── index.php
```

### Cara pakai lengkap

1. Jalankan generator dari root proyek:

   ```bash
   php spark make:module Reports
   ```

2. Generator membuat modul pada `app/Modules/Reports` dan menulis file dasar. Nama modul sebaiknya PascalCase tanpa spasi, misalnya `Reports`, `UserGroups`, atau `ActivityLogs`.

3. Periksa hasilnya:

   ```bash
   find app/Modules/Reports -maxdepth 3 -type f | sort
   php -l app/Modules/Reports/Controllers/ReportsController.php
   php -l app/Modules/Reports/Models/ReportsModel.php
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

## Lisensi

Kode aplikasi, integrasi, konfigurasi, dan branding FMS mengikuti ketentuan lisensi proyek. Library pihak ketiga tetap mengikuti lisensi masing-masing dan informasi lisensinya tidak boleh dihapus dari inventaris internal.

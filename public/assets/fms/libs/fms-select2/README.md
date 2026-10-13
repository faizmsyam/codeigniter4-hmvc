# FMS Select2

Pengganti **vanilla JavaScript** (tanpa jQuery) untuk Select2 4.x, disesuaikan
dengan CSS template-admin FMS. API-nya kompatibel dengan Select2 4.x sehingga
kode lama yang memakai `.select2({...})` tetap berjalan (ada shim jQuery opsional).

- **Nol dependensi** — hanya `fetch()` bawaan browser.
- **API kompatibel Select2 4.x** + alias gaya Select2 3.x (`formatResult`, `ajax.results`, `quietMillis`, …).
- **Sudah termasuk**: single/multiple, clear, templating, AJAX remote + pagination,
  infinite scroll, pencarian lokal, keyboard, RTL, dark mode template.

---

## Daftar isi

1. [Struktur file](#struktur-file)
2. [Cara pakai dasar](#cara-pakai-dasar)
3. [Multiple](#multiple)
4. [Templating](#templating)
5. [AJAX (remote data)](#ajax-remote-data)
6. [Infinite scroll & pagination](#infinite-scroll--pagination)
7. [Referensi opsi](#referensi-opsi)
8. [Referensi API instance](#referensi-api-instance)
9. [Event](#event)
10. [Kompatibilitas jQuery (shim)](#kompatibilitas-jquery-shim)
11. [Tema & CSS](#tema--css)
12. [Build ulang file minify](#build-ulang-file-minify)

---

## Struktur file

```
libs/fms-select2/
├── js/
│   ├── fms-select2.js        # sumber (vanilla)
│   └── fms-select2.min.js    # versi minify (dipakai produksi)
├── css/
│   ├── fms-select2.css       # sumber gaya (warna/radius dari template-admin)
│   └── fms-select2.min.css   # versi minify (dipakai produksi)
├── demo.html                 # contoh dasar (non-AJAX)
├── demo-ajax.html            # contoh AJAX + pagination (gaya Select2 3.x)
└── README.md
```

> **Desain**: `fms-select2.css` meniru tampilan Select2 3.4.5 (kotak ber-border,
> chevron, chip, dropdown `.select2-drop`) tetapi memakai class `.fms-select2-*`
> dan warna/radius dari template (var CSS di bawah). Ikon (chevron, silang,
> spinner) memakai **inline SVG** — tidak butuh file gambar.

---

## Cara pakai dasar

```html
<link rel="stylesheet" href="/assets/fms/libs/fms-select2/css/fms-select2.min.css">
<script src="/assets/fms/libs/fms-select2/js/fms-select2.min.js"></script>

<select class="my-select">
  <option value="1">Satu</option>
  <option value="2">Dua</option>
</select>

<script>
  const [s] = FMSSelect2.init('.my-select', {
    placeholder: 'Pilih…',
    allowClear: true
  });
  s.on('change', (v) => console.log('nilai:', v));
</script>
```

`FMSSelect2.init(selector, options)` menerima **string selector**, **elemen DOM**,
atau **koleksi NodeList/array**, dan mengembalikan **array instance**.
Elemen yang sudah diinisialisasi tidak akan di-init ulang (idempoten).

---

## Multiple

Tambahkan atribut `multiple` pada `<select>`; library otomatis mendeteksinya.

```html
<select class="my-multi" multiple>
  <option value="a" selected>A</option>
  <option value="b">B</option>
</select>
<script>
  FMSSelect2.init('.my-multi', { maximumSelectionLength: 3 });
</script>
```

- `maximumSelectionLength` membatasi jumlah pilihan (0 = tanpa batas).
- Nilai `val()` berupa **array** untuk multiple, **string** untuk single.
- Chip yang dipilih otomatis dibuatkan `<option selected>` di `<select>` asli.

---

## Templating

`templateResult` (daftar dropdown) dan `templateSelection` (nilai terpilih)
menerima objek `{ id, text, element, disabled, selected, ...fieldKustom }`.
Boleh mengembalikan **string HTML**, **elemen DOM**, atau objek jQuery.

```js
function formatState(state) {
  if (!state.id) return state.text;
  return '<span><img src="/img/' + state.element.value + '.jpg"> ' + state.text + '</span>';
}

FMSSelect2.init('.my-select', {
  templateResult: formatState,
  templateSelection: formatState
});
```

Field kustom dari item AJAX (mis. `nip`, `jabatan`) ikut diteruskan ke
`templateResult` lewat `state.nip`, `state.jabatan`, dst.

> **Keamanan**: HTML dari template di-escape secara default. Untuk membiarkan
> HTML mentah, set `escapeMarkup: (m) => m`.

---

## AJAX (remote data)

Kode gaya Select2 3.x **tetap jalan** — library menormalkan alias 3.x → 4.x:

| Select2 3.x | Select2 4.x (internal) |
|---|---|
| `formatResult` | `templateResult` |
| `formatSelection` | `templateSelection` |
| `escapeMarkup` | dihormati apa adanya |
| `ajax.quietMillis` | `ajax.delay` |
| `ajax.results(data, page)` | `ajax.processResults(data, page)` |

```js
FMSSelect2.init('#kepada', {
  multiple: true,
  placeholder: 'Tujuan Surat ...',
  minimumInputLength: 0,              // 3 = query baru jalan setelah 3 huruf
  ajax: {
    type: 'GET',
    url: baseUrl + 'masterdata/list-pegawai',
    dataType: 'JSON',
    quietMillis: 100,                 // = delay (ms)
    data: function (term, page) {     // parameter request
      return { q: term, page: page, kode_unor: kodeUnor };
    },
    results: function (data, page) {  // = processResults
      return { results: data.data, more: (page * 20) < data.total };
    }
  },
  formatResult:    (d) => formatResultSelectPegawai(d),
  formatSelection: (d) => formatSelectionSelectPegawai(d)
});
```

### Transport kustom (opsional)

Ganti `fetch()` dengan AJAX sendiri (mis. `$.ajax` atau mock test):

```js
ajax: {
  url: '...',
  transport: function (options, done, fail) {
    // options.data = hasil ajax.data(term, page)
    $.ajax({ url: options.url, data: options.data })
      .done((res) => done(res))
      .fail((xhr) => fail(xhr));
  }
}
```

### Perilaku AJAX

- **Pencarian remote** otomatis di-debounce memakai `delay`/`quietMillis`.
- **Halaman pertama** menampilkan pesan `Searching…` (dengan spinner).
- **Item terpilih** dibuatkan `<option data-fms-ajax="1">` agar nilai ikut
  ter-submit sebagai form biasa, dan dibersihkan saat deselect.
- **Cache label** (`ajaxCache`) menjaga chip tetap punya teks meski item
  sudah lepas dari daftar.

---

## Infinite scroll & pagination

Saat server mengembalikan `more: true`:

- Scroll dropdown sampai dasar → halaman berikutnya **dimuat otomatis**
  (muncul baris `Memuat…` + spinner di dasar daftar), lalu hasil **ditambahkan**
  (bukan mengganti) halaman sebelumnya.
- Item **"Muat lebih banyak…"** juga tetap dirender sebagai fallback klik manual
  (bisa dimatikan dengan tidak memakai `more`).

Contoh respons server:

```json
{ "data": [ { "id": 1, "text": "Budi", "nip": "19900101" } ], "total": 45 }
```

`more` dihitung sebagai `(page * perPage) < total`.

---

## Referensi opsi

| Opsi | Tipe | Default | Keterangan |
|---|---|---|---|
| `placeholder` | string | `''` | Teks placeholder. |
| `allowClear` | bool | `false` | Tombol silang untuk mengosongkan (single). |
| `multiple` | bool | dari atribut `multiple` | Mode multi-pilih. |
| `search` | bool | `true` | Aktifkan kotak pencarian. |
| `minimumResultsForSearch` | number | `0` | Sembunyikan search bila jumlah opsi < nilai ini (single). |
| `minimumInputLength` | number | `0` | Minimal karakter sebelum query (berguna untuk AJAX). |
| `maximumSelectionLength` | number | `0` | Batas jumlah pilihan (0 = tanpa batas). |
| `closeOnSelect` | bool | `true` | Tutup dropdown setelah memilih. |
| `disabled` | bool | dari elemen | Nonaktifkan widget. |
| `dir` | `'ltr'`/`'rtl'` | dari atribut / dokumen | Arah teks. |
| `width` | string/number | `'100%'` | Lebar container (mis. `'resolve'`, `300`, `'50%'`). |
| `dropdownParent` | Element | `null` | Elemen induk dropdown (untuk kasus khusus, mis. di dalam modal). Default menempel di dalam container agar border menyatu. |
| `templateResult` | fn | `null` | Format item di daftar dropdown. |
| `templateSelection` | fn | `null` | Format nilai terpilih. |
| `escapeMarkup` | fn | escape bawaan | Biarkan HTML mentah dengan `(m) => m`. |
| `ajax` | object | `null` | Konfigurasi remote (lihat bagian AJAX). |
| `language.noResults` | fn | `'No results found'` | Pesan kosong. |
| `language.searching` | fn | `'Searching…'` | Pesan saat mencari. |
| `language.inputTooShort` | fn | — | Pesan saat kurang karakter. |

**Alias Select2 3.x yang didukung**: `formatResult`, `formatSelection`,
`quietMillis`, `ajax.results`, `ajax.data(term, page)`, `ajax.url`,
`ajax.type`, `ajax.dataType`, `ajax.transport`.

---

## Referensi API instance

Instance didapat dari `FMSSelect2.init(...)[i]` atau `FMSSelect2.getInstance(el)`.

| Method | Keterangan |
|---|---|
| `val()` / `val(v)` | Ambil / set nilai (string single, array multiple). |
| `open()` / `close()` / `toggle()` | Buka / tutup / balik dropdown. |
| `select(v)` / `deselect(v)` | Pilih / batalkan satu nilai. |
| `clear()` | Kosongkan semua nilai. |
| `enable()` / `disable()` | Aktifkan / nonaktifkan. |
| `on(ev, fn)` / `off(ev, fn)` / `trigger(ev)` | Event pada `<select>` asli. |
| `destroy()` | Lepas widget, kembalikan `<select>` ke keadaan semula. |
| `prop(name)` / `prop(name, value)` | Shorthand untuk properti elemen (mis. `disabled`). |

Static:

- `FMSSelect2.init(selector, options) → Instance[]`
- `FMSSelect2.getInstance(el) → Instance | null`

---

## Event

Dipicu pada **`<select>` asli** via `CustomEvent`:

| Event | Kapan |
|---|---|
| `change` | Nilai berubah (menyatu dengan perilaku native). |
| `fms-select2:open` | Dropdown dibuka. |
| `fms-select2:close` | Dropdown ditutup. |
| `fms-select2:select` | Satu item dipilih. |
| `fms-select2:unselect` | Satu item dibatalkan. |
| `fms-select2:clear` | Semua nilai dikosongkan. |

```js
const [s] = FMSSelect2.init('#x');
s.on('fms-select2:select', (v) => console.log('dipilih', v));
```

---

## Kompatibilitas jQuery (shim)

Tersedia dua jalur:

1. **jQuery asli terpasang** → plugin `.select2()` otomatis dipasang pada
   `jQuery.fn`, jadi `$('.x').select2({...})`, `.select2('val', v)`,
   `.select2('destroy')` tetap jalan.
2. **Tanpa jQuery** → library menyediakan `$` minimal dengan
   `.select2()`, `.val()`, `.prop()`, `.on()`, `.off()`, `.trigger()`, `.each()`.

Contoh kode lama yang tetap kompatibel:

```js
$('#kepada').select2({
  multiple: true,
  placeholder: 'Tujuan Surat ...',
  ajax: { /* ... */ }
});
```

---

## Tema & CSS

Gaya memakai **variabel CSS template-admin** berikut (light & dark otomatis
mengikuti `[data-theme-mode]`):

`--input-border`, `--form-control-bg`, `--default-text-color`, `--text-muted`,
`--default-background`, `--default-border`, `--primary-color`, `--gray-2`.

Highlight item terpilih memakai `--primary-color` dengan lapisan gelap dan teks
putih. Untuk mengganti warna tanpa mengubah library, cukup override variabel
template atau tambahkan aturan pada `.fms-select2-*`.

---

## Build ulang file minify

```bash
cd public/assets/fms/libs/fms-select2
npx --yes terser js/fms-select2.js -c -m -o js/fms-select2.min.js
npx --yes clean-css-cli -o css/fms-select2.min.css css/fms-select2.css
```

Selalu jalankan `node --check js/fms-select2.js` (dan `.min.js`) setelah mengubah
sumber untuk memastikan tidak ada error sintaks.

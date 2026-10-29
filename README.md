```markdown
## Backend (Bagian B2 — Flat-File JSON)

Proyek ini memiliki backend PHP, **tanpa basis data** — seluruh data
tersimpan di berkas JSON pada folder `data/`.

### Struktur Data

- `data/produk.json` — daftar produk (dan info situs/menu) - `data/transaksi.json` — daftar transaksi (rincian produk bersarang di dalamnya) - `data/pengguna.json` — daftar akun admin - `data/.htaccess` — memproteksi folder ini dari akses langsung lewat browser

### Endpoint API

| Endpoint         | Metode | Keterangan                           |
| ---------------- | ------ | ------------------------------------ |
| `api/produk.php` | GET    | Seluruh data produk, situs, dan menu |

### Menjalankan Proyek

1. Pastikan Laragon aktif (Apache + PHP 8.5). **Tidak perlu MariaDB.**
2. Buka `http://localhost/kopi-nusantara/`.
```

## BUG-FIXING : H16-B1

1. perbaikan eror click pada keranjang sudah terpatch di index.html

## BUG H17

1. terjadi eror tidak dapat terhubung dengan server

## BUG FIXING

1. perbaikan karena belum memanggil stok pada produk.json

## BUG H18

1. terjadi eror pada struk php. tidak muncul lihat struk php
2. terjadi eror ketika latihan dan muncul eror pada percobaan fpdf, ini erornya

```
Warning: Undefined property: FPDF::$Ln in C:\laragon\www\latihan-Junior-Web-Developer-Semuanya-Zaiem404\kopi-nusantara-full-version\api\struk.php on line 54
```

3. hasil pengamatan terjadi kesalahan jam pada struk tidak sesuai jamnya

### Pengamatan

1. link untuk lihat struk : http://localhost/latihan-Junior-Web-Developer-Semuanya-Zaiem404/kopi-nusantara-full-version/api/struk.php?kode=KN-D4A1D91E
2. link untuk unduh struk : http://kopi-nusantara.test/api/struk.php?kode=KN-9D456A60&unduh=1
3. hasil struk :
   Kode Transaksi : KN-D4A1D91E
   Struk Kopi Nusantara
   Nama Pemesan : weweewewe
   Kontak : 2323232323
   Tanggal : 2026-10-24 05:41:12 //? jam tidak sesusai
   Produk
   Kopi Toraja
   Kopi Gayo
   Jumlah Harga
   Subtotal
   2
   Kopi Kintamani
   1
   Rp 85.000
   Rp 170.000
   Rp 90.000
   Rp 90.000
   Kopi Bajawa
   Mandheling
   1
   Rp 88.000
   Rp 88.000
   1
   Rp 82.000
   Rp 82.000
   Total
   Rp 430.000

4. subtotal tidak tampil di devtool tapi keluar di struk

## BUG FIXED H18

1. struk sudah muncul karena terjadi kesalahan penulisan pada struk.php
2. jam struk sudah di fix lewat mengubah date/time pada php.ini

## H17 - Kegiatan Belajar 5F.2: Endpoint Checkout dan Penguncian Berkas

**1. BUG-01**
FrontEnd: Tidak dapat terhubung ke server.
DevTools-Console Error:

```
keranjang.js:147  POST http://kopi-nusantara.test/api/checkout.php 400 (Bad Request)
(anonymous) @ keranjang.js:147

keranjang.js:164 SyntaxError: Unexpected token '<', "<br />
<b>"... is not valid JSON (at VM339:1:1)
(anonymous) @ keranjang.js:164
```

**BUG-FIXED:**
Di produk.json -> cek apakah sudah diisi:
Array produk -> key & value "stok: 20"

**2. BUG-02**
Jam di `transaksi.json` -> item transaksi `dibuat_pada: tidak sesuai` 😇
_Otomatis akan berpengaruh (legacy-bug) pada H18 - Modul-5F.3: Struk PDF dengan FPDF_

**BUG-FIXED:**

1. Buka file `php.ini` di Laragon
2. Cari `date.timezone`
3. Hilangkan semicolon (komen `;` di depannya)
4. isi dengan `date.timezone = Asia/Makassar`

_Catatan: Sesuaikan Wilayah Waktu Anda_

**Pengembangan**

- `Isi KERANJANG`, tidak otomatis tampil / `no-autoreload` (kecuali F5).
- `Lihat Struk` dan `Unduh Struk`, belum berfungsi.

## H18 - Kegiatan Belajar Modul 5F.3: Composer dan FPDF — Struk PDF

**Fitur**

- Penerapan PATCH-01 `autoreload.js` | `patch/01-patch-autoreload.js`
- [Lihat Struk](http://kopi-nusantara.test/api/struk.php?kode=KN-ECEA926A) Sudah berfungsi
- [Unduh Struk](http://kopi-nusantara.test/api/struk.php?kode=KN-ECEA926A&unduh=1) Sudah berfungsi

**BUG**

- H18-001: Aktifitas `Tambah Keranjang` setelah `Checkout` menyembunyikan `Form Checkout` dari `Keranjang Belanja`.

**BUG-FIXED**

- H18-001: Next H19...

**Pengembangan**

- Ubah ukuran kertas Struk agar sesuai dengan POS-Printer (Pelajari Manual FPDF).

**BUG**

- pada saat keranjang belanja sudah ada isi dan nama pemesan dan nomor whatsapp sudah dicatat, dan produk di masukkan ke keranjang, nama dan nomor hilang

**BUG FIXED H18**

- sudah diperbaiki karanjang belanja dengan formCheckout.reset(); pada 03 finalpatch-autoreload.js ditaruh di akhir

**BUG FIXEC H20**
- terjadi eror pada pemrosesan cdn table 
# Pulsa dan Paket Data Kiosbank

Menu **Mau Beli Apa?** di bawah pilihan operator memisahkan Pulsa dan Paket Data.
Operator by.U dan WiFi ID juga tersedia; WiFi ID ditampilkan pada Paket Data.
Pilihan manual dipertahankan saat mengetik digit akhir nomor HP. Deteksi otomatis
berjalan kembali jika empat digit awal nomor berubah.

`resources/data/kiosbank-pulsa.json` berisi 511 kode/nama dari daftar resmi,
termasuk `5DC481`. Nominal yang tertulis dalam nama produk bukan harga modal.
Harga baru tidak ditebak dari nama produk. Produk masa aktif dan paket khusus
telepon disimpan sebagai kategori tersendiri di admin dan tidak dicampur ke
pilihan paket internet.

## Kapan produk bisa dibeli

- Produk diaktifkan admin dan harga modal/harga jual lebih dari nol.
- Harga sudah disinkronkan dari Kiosbank untuk mode yang sedang dipakai.
  Harga development tidak digunakan untuk mengizinkan pembelian production.
- Kiosbank mengembalikan status aktif, bukan maintenance, untuk kode tersebut.
  Kode yang tidak disebut aktif atau respons yang tidak dapat dikonfirmasi
  ditampilkan sebagai belum tersedia.
- Status daftar dicache selama 60 detik per mode/kredensial. Checkout memeriksa
  ulang ke provider sebelum membuat invoice atau memotong saldo.

Tombol produk yang tidak tersedia benar-benar dinonaktifkan. Server tetap
memeriksa status meskipun permintaan checkout dikirim langsung tanpa tombol.
Ketersediaan masih dapat berubah sesudah pengecekan; hasil transaksi akhir
tetap mengikuti balasan provider.

## Upload ke aaPanel

Perubahan ini membutuhkan PHP, data katalog, dan migrasi, bukan hanya build JS.
Upload file berikut dengan struktur folder yang sama:

- `app/Services/KiosbankService.php`
- `app/Services/KiosbankPulsaCatalog.php`
- `app/Models/PpobProduct.php`
- `app/Http/Controllers/PpobController.php`
- `app/Http/Controllers/AdminController.php`
- `resources/views/admin/ppob/products.blade.php`
- `config/kiosbank.php`
- `resources/data/kiosbank-pulsa.json`
- `database/migrations/2026_10_22_120000_import_kiosbank_mobile_catalog.php`
- Seluruh `public/build/`, termasuk `manifest.json`.

Jika membangun frontend di VPS, upload juga perubahan `resources/js/`.
Setelah upload, jalankan dari root aplikasi menggunakan PHP CLI yang sama
dengan PHP website:

```sh
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
```

Lalu buka **Admin > Produk PPOB > Sinkron API** (tombol sinkronisasi katalog).
Pilih operator pada pilihan di samping tombol sinkron untuk mengambil harga
operator tersebut saja. Jika gagal, pesan menampilkan nama kelompok yang gagal.
Pastikan mode, kredensial, serta whitelist IP sesuai akun Kiosbank. Produk baru
tetap belum bisa dibeli sampai harga dan status tersedia. Harga/margin/status
nonaktif produk lama dipertahankan oleh migrasi, tetapi harga perlu disinkronkan
sekali lagi untuk menandai mode sumbernya.

## Harga belum tersedia di localhost

Pemeriksaan 23 Oktober 2026: parameter akun lokal sudah cocok dengan informasi
Kiosbank. Hambatan sertifikat development sudah dilewati dengan pengaturan
kompatibilitas berikut di `.env` lokal, sesuai contoh PHP provider:

```dotenv
KIOSBANK_DEVELOPMENT_VERIFY_TLS=false
```

Pengaturan ini menonaktifkan verifikasi identitas sertifikat untuk koneksi
development saja. Default tetap `true`; production selalu memverifikasi TLS.
Pengaturan berlaku untuk RSA maupun Digest. Sign-On development berhasil
melalui fallback Digest dan mendapatkan sesi asli.

Namun API mengembalikan:

- `get-Active-Product`: rc `19`, `data deposit tidak ditemukan`.
- `getHargaByProductID`: rc `19`, `data tidak ditemukan`.
- Endpoint pulsa lama `getPulsa-Prabayar`: rc `19`, `data pulsa tidak ditemukan`.

Harga belum bisa diisi sampai data akun/deposit/katalog tersedia di provider.
Mintalah helpdesk memeriksa aktivasi akun development dan akses API harga.
Pesan ini bukan instruksi untuk transfer deposit; tindak lanjut untuk akun
development perlu dikonfirmasi Kiosbank.

Jika Kiosbank menyediakan CA privat resmi, simpan file PEM di luar direktori
public dan atur path absolutnya. Contoh (file harus benar-benar tersedia):

```dotenv
KIOSBANK_DEVELOPMENT_VERIFY_TLS=true
KIOSBANK_DEVELOPMENT_CA_BUNDLE=C:/xampp/certs/kiosbank-development-ca.pem
# VPS, hanya bila production memerlukan CA privat resmi:
# KIOSBANK_PRODUCTION_CA_BUNDLE=/www/server/certs/kiosbank-production-ca.pem
```

Jalankan `php artisan config:clear`, lalu gunakan **Tes Sign-On** dan sinkron
ulang operator. Tanpa pengaturan CA khusus, aplikasi memakai CA sistem PHP.

Sinkron admin dan Tes Sign-On menggunakan kembali sesi asli sampai akhir hari
WIB. Hanya sesi simulasi yang dicoba ulang; sesi simulasi disimpan 60 detik.
Aplikasi membatasi percobaan Sign-On lokal menjadi 99 per akun/mode/hari WIB.
Hitungan menggunakan cache aplikasi; jangan menghapus cache untuk mengakali
batas, sebab Kiosbank menghitung permintaan sendiri, termasuk dari instalasi
lain. Produk dengan harga belum terverifikasi tetap tidak dapat dibeli.

Jangan menjalankan `KiosbankDevSeeder` di production: seeder itu mengubah mode
dan pengaturan akun menjadi development.

## Pengujian development

Nomor contoh pengguna disimpan sebagai fixture pada `PulsaDataCatalogTest`,
termasuk nol di depan dan akhiran `true`. Tes tersebut memakai HTTP fake dan
tidak melakukan transaksi sungguhan. Nomor berakhiran `true` adalah fixture API;
form pelanggan tetap menerima nomor numerik.

Mode development memakai endpoint `development.kiosbank.com:4432`, sementara
production memakai `transaksi.kiosbank.com:17109`. Kegagalan pembelian pulsa/data
di development tidak lagi diubah menjadi keberhasilan simulasi. Pengujian live
harus memakai akun development serta kode dan nomor uji yang cocok.

```sh
php artisan test --filter='PulsaDataCatalogTest|PpobProviderAmountTest|KiosbankConfigurationTest'
```

Referensi:
[kode produk](https://ppob.kiosbank.com:4040/api/webservicesV2/Main/Produk/PULSA),
[prefix harga](https://ppob.kiosbank.com:4040/api/webservicesV2/Main/DaftarHarga),
[nomor development](https://ppob.kiosbank.com:4040/api/webservicesV2/Main/Nopel/PULSA).

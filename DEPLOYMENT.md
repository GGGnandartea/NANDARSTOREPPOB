# Deploy NandarStore ke VPS aaPanel

## Perbaikan error daftar pada v2.premiumkita.com

Pemeriksaan respons situs menemukan cookie `XSRF-TOKEN` dan cookie sesi dikirim
dengan `Domain=v2.nandarstore.my.id`, padahal situs diakses melalui
`v2.premiumkita.com`. Browser menolak cookie lintas domain tersebut. Akibatnya,
token dari `/auth/session` tidak memiliki sesi yang sama saat POST `/register`
dan Laravel mengembalikan 419. Ini bukan masalah kata sandi atau izin cookie browser.

Di aaPanel, edit `.env` pada **root aplikasi VPS**. Gabungkan pengaturan pada
`deploy/aapanel.env.example` ke file yang sudah ada; jangan mengganti seluruh
file atau menyalin `.env` lokal. Pastikan setiap nama pengaturan hanya muncul sekali.
Perubahan utama adalah `APP_URL=https://v2.premiumkita.com` dan
`SESSION_DOMAIN=null` (tanpa domain lama). Pertahankan APP_KEY dan kredensial database.

Setelah menyimpan, jalankan di root aplikasi melalui terminal aaPanel:

```sh
php artisan config:clear
php artisan config:cache
```

Gunakan PHP CLI versi yang sama dengan website (PHP 8.2 atau lebih baru), misalnya
`/www/server/php/83/bin/php` bila website memakai PHP 8.3. Jika OPcache tidak
memeriksa perubahan file, restart PHP dari aaPanel setelah upload kode.
Hapus cache HTML Cloudflare jika sebelumnya memakai aturan Cache Everything,
lalu coba mendaftar dari jendela privat.

Cookie baru harus **tidak memiliki atribut Domain**. Cookie host-only tersebut
otomatis berlaku untuk `v2.premiumkita.com`. Berpindah nama cookie akan meminta
pengguna yang sebelumnya login untuk masuk kembali.

## File aplikasi

Build JavaScript tidak menggantikan kode PHP Laravel. Upload perubahan `app/`,
`bootstrap/app.php`, `config/`, `routes/`, dan seluruh `public/build/` hasil build secara bersamaan. Jika build
dilakukan di VPS, upload juga `resources/`, `package.json`, dan `package-lock.json`.
Document root web server harus menunjuk ke direktori `public/`.

## Konfigurasi sesi di VPS

Sesuaikan `.env` **di VPS**, bukan menyalin `.env` lokal:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://v2.premiumkita.com
SESSION_DRIVER=file
SESSION_COOKIE=nandarstore_v2_session
SESSION_DOMAIN=null
SESSION_PATH=/
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

Nama cookie khusus v2 menghindari benturan dengan aplikasi di domain utama.
`SESSION_DOMAIN=null` membatasi cookie ke host yang sedang diakses.
`SESSION_SECURE_COOKIE=true` digunakan untuk situs HTTPS; lokal HTTP gunakan false.
Pertahankan `APP_KEY` VPS yang sudah ada agar data terenkripsi tetap dapat dibaca.
Untuk satu VPS, driver `file` menyimpan sesi di `storage/framework/sessions`.
Folder ini harus dapat ditulis oleh user PHP-FPM. Bila memilih driver `database`,
database harus memiliki tabel `sessions` dan user database harus dapat menulisnya.

## Pengaturan aaPanel dan Cloudflare

- Website > Site directory: running directory `/public` di dalam root aplikasi.
- Untuk Nginx, gunakan URL rewrite Laravel:

  ```nginx
  location / {
      try_files $uri $uri/ /index.php?$query_string;
  }
  ```

- Pastikan `storage/` dan `bootstrap/cache/` dimiliki/dapat ditulis user PHP-FPM
  website. User default aaPanel sering `www`, tetapi periksa konfigurasi pool
  terlebih dahulu. Jangan memakai izin `777`.
- Gunakan HTTPS pada origin dan Cloudflare SSL/TLS **Full (strict)** setelah
  sertifikat origin valid. Hindari pergantian host antara halaman dan API.
- Bypass cache HTML dan endpoint Laravel; cache aset `/build/assets/` tetap boleh.
  Header `no-store` dari aplikasi tidak menggantikan aturan Cloudflare yang
  secara eksplisit memaksa cache. Respons error autentikasi juga tidak boleh di-cache.
- Jika TLS berakhir di reverse proxy dan Laravel tidak mengenali HTTPS, isi
  `TRUSTED_PROXIES` dengan IP/CIDR proxy yang benar. Jangan memasukkan `*`.
  Untuk Cloudflare, gunakan daftar IP resmi, lalu jalankan ulang `config:cache`.

## Perintah setelah upload

Jalankan dari direktori aplikasi:

```sh
composer install --no-dev --optimize-autoloader
# Hanya jika public/build belum dibangun di komputer lokal:
npm ci
npm run build
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Jangan upload `public/hot` dari komputer lokal. Jika file itu sudah ada di VPS,
hapus file tersebut agar Laravel memakai manifest build. User PHP/web server
harus dapat menulis `storage/` dan `bootstrap/cache/`.

Pastikan CDN/reverse proxy tidak menyimpan halaman dinamis, `/auth/session`,
atau respons login/pendaftaran. Hapus cache HTML lama jika sebelumnya disimpan.
Cache file statis `public/build/assets/` tetap boleh digunakan.

## Verifikasi

1. Buka situs HTTPS di jendela privat; login harus berpindah ke beranda dengan
   akun aktif (admin ke dashboard admin).
2. Daftarkan pelanggan baru; harus langsung masuk ke beranda. Reseller gratis
   langsung aktif, sedangkan reseller berbayar tetap menampilkan QRIS.
3. Di Network browser, `GET /auth/session` harus menghasilkan JSON 200 dengan
   `Cache-Control: private, no-store, max-age=0`; cookie `nandarstore_v2_session`
   harus ikut pada POST berikutnya. Jangan membagikan nilai cookie atau token.
4. Jika masih 419, cek konfigurasi sesi yang sudah di-cache, penyimpanan tabel
   `sessions` (driver database) atau izin `storage/framework/sessions` (driver file),
   serta cookie browser. Log `Authentication CSRF mismatch` mencatat apakah cookie
   sesi berhasil dibaca tanpa mencatat token, password, atau nilai cookie.
   Jika 500, periksa `storage/logs/laravel.log`.
   Jika 403, periksa aturan WAF/proxy yang memblokir endpoint autentikasi.

Referensi: [CSRF Laravel](https://laravel.com/docs/12.x/csrf),
[deployment Laravel](https://laravel.com/docs/12.x/deployment),
[Cloudflare Full (strict)](https://developers.cloudflare.com/ssl/origin-configuration/ssl-modes/full-strict/),
[IP Cloudflare](https://www.cloudflare.com/ips/).

# Panduan Hosting Project Rental Mobil di Railway

Panduan ini disesuaikan dengan isi proyek pada 17 Oktober 2026: PHP native, database MySQL/MariaDB, Composer, upload bukti pembayaran, dan integrasi Midtrans. Metode deployment: GitHub → Railway Railpack → MySQL Railway.

Dokumen ini berisi langkah yang perlu Anda kerjakan. Pembuatan panduan ini belum mengubah kode aplikasi atau melakukan deployment. Nama menu Railway bisa berubah; ikuti menu dengan fungsi yang sama.

## 1. Siapkan akun dan alat

1. Siapkan akun [GitHub](https://github.com) dan [Railway](https://railway.com).
2. Siapkan Git, PHP, dan Composer di komputer jika ingin menggunakan terminal.
3. Siapkan aplikasi database, misalnya MySQL Workbench, untuk mengimpor SQL ke Railway.
4. Cadangkan database dan folder `uploads` dari hosting lama jika ingin memindahkan data terbaru. SQL dalam proyek adalah snapshot, bukan otomatis data terkini.
5. Periksa paket, kredit, dan biaya pemakaian di dashboard Railway. Aplikasi, database, dan penyimpanan memakai resource tersendiri; jangan menganggap hosting selalu gratis. Lihat [pricing Railway](https://railway.com/pricing).

## 2. Ubah koneksi database supaya membaca Railway Variables

Saat ini `koneksi.php` memakai konfigurasi hosting lama secara langsung. Variables Railway tidak akan dipakai sebelum kode ini disesuaikan.

1. Buka `koneksi.php`.
2. Ganti bagian awal, mulai dari `<?php` sampai tepat sebelum komentar `SET CHARACTER`, dengan kode berikut:

```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('Asia/Jakarta');

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'db_rentalmobil');
define('DB_PORT', (int) (getenv('DB_PORT') ?: 3306));

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
} catch (mysqli_sql_exception $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Koneksi database gagal. Silakan hubungi administrator.');
}
```

3. Pertahankan bagian `mysqli_set_charset($conn, 'utf8mb4');` dan seluruh kode pembuatan tabel di bawahnya.
4. Untuk menjalankan lokal sesudah perubahan ini, atur environment variable yang sesuai dengan database lokal Anda. PHP native ini belum memiliki pembaca file `.env`; membuat `.env` saja tidak otomatis mengisi `getenv()`.

## 3. Siapkan konfigurasi Midtrans tanpa menyimpan key di GitHub

`midtrans_config.php` saat ini tercantum dalam `.gitignore`. Jika tidak diperbaiki, file tersebut tidak ikut deployment sehingga halaman pembayaran gagal.

1. Ganti isi `midtrans_config.php` dengan contoh berikut, menggunakan environment variable:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

define('MIDTRANS_IS_PRODUCTION', filter_var(
    getenv('MIDTRANS_IS_PRODUCTION') ?: 'false',
    FILTER_VALIDATE_BOOLEAN
));
define('MIDTRANS_SERVER_KEY', getenv('MIDTRANS_SERVER_KEY') ?: '');
define('MIDTRANS_CLIENT_KEY', getenv('MIDTRANS_CLIENT_KEY') ?: '');
define('MIDTRANS_NOTIFICATION_URL', getenv('MIDTRANS_NOTIFICATION_URL') ?: '');

\Midtrans\Config::$serverKey = MIDTRANS_SERVER_KEY;
\Midtrans\Config::$isProduction = MIDTRANS_IS_PRODUCTION;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;
```

2. Hapus baris `midtrans_config.php` dari `.gitignore` setelah file tersebut hanya berisi kode pembaca environment variable.
3. Simpan nilai key hanya di Railway Variables, bukan di dalam kode atau dokumen ini.
4. Konfigurasi database dan Midtrans sebelumnya berisi kredensial langsung. Jika pernah dibagikan atau dimasukkan ke repository, ganti kredensial tersebut melalui penyedia terkait.

**Keterbatasan pembayaran yang ditemukan pada proyek:**

- `midtrans_payment.php` masih memuat script Snap sandbox. Mulai pengujian dengan sandbox; sebelum production, sesuaikan alamat script menjadi `https://app.midtrans.com/snap/snap.js` dan gunakan pasangan key production.
- Endpoint `midtrans_notification.php` yang disebut konfigurasi lama belum tersedia.
- `payment_callback.php` dan `payment_return.php` masih memanggil `tripay_helper.php`, sedangkan file itu tidak tersedia. Keduanya belum bisa digunakan sebagai endpoint Midtrans.
- `midtrans_payment.php` menulis kolom `midtrans_order_id`, tetapi kolom itu belum ada di kedua file SQL.

Website dapat di-host lebih dahulu, tetapi pembayaran online belum lengkap. Jangan mengaktifkan transaksi uang sungguhan sebelum callback Midtrans dibuat, signature diverifikasi, order dicocokkan dengan database, dan perubahan status pembayaran diuji. Transfer manual dan upload bukti dapat diuji tersendiri.

## 4. Atur web server untuk proyek PHP native

Railpack mendukung PHP melalui FrankenPHP serta dapat membaca `Caddyfile` dari root proyek. Lihat [dokumentasi PHP Railpack](https://railpack.com/languages/php/).

Karena file PHP proyek berada langsung di root bersama SQL dan dokumen, buat file bernama `Caddyfile` di folder yang sama dengan `index.php`:

```caddyfile
{
    frankenphp
    auto_https off
    admin off
}

:{$PORT:8080} {
    root * /app
    encode gzip

    @private path /.git /.git/* /.env /.env.* /vendor /vendor/* /logs /logs/* /composer.json /composer.lock /Caddyfile /php.ini /koneksi.php /midtrans_config.php /_layout.php /_layout_footer.php
    respond @private 404

    @documents path_regexp documents (?i)\.(sql|md|txt|bak|log)$
    respond @documents 404

    @uploadScripts path_regexp uploadScripts (?i)^/uploads/.*\.(php[0-9]*|phtml|phar)(/.*)?$
    respond @uploadScripts 404

    php_server
}
```

Konfigurasi ini melayani file PHP pada port Railway dan membatasi akses HTTP ke file internal. `require`/`include` PHP tetap dapat membaca file tersebut dari filesystem. Railway menangani HTTPS publik. Rujukan sintaks server: [konfigurasi FrankenPHP](https://frankenphp.dev/docs/config/).

Buat juga `php.ini` di root:

```ini
date.timezone = Asia/Jakarta
display_errors = Off
log_errors = On
error_log = /dev/stderr
upload_max_filesize = 10M
post_max_size = 12M
```

Batas ukuran yang diperiksa kode upload aplikasi tetap berlaku walaupun batas PHP lebih besar.

## 5. Upload proyek ke GitHub

1. Buat repository GitHub, sebaiknya **Private** untuk proyek ini.
2. Sebelum upload, tambahkan pola berikut ke `.gitignore` tanpa menghapus aturan lain yang masih diperlukan:

```gitignore
vendor/
.env
.env.*
!.env.example
logs/
uploads/bukti/*
!uploads/bukti/README.txt
daftar akun.txt
*.sql
```

3. Pertahankan file SQL dan bukti pembayaran di komputer untuk proses migrasi terpisah. File tersebut tidak perlu masuk repository deployment. Folder `images` untuk aset publik tetap diikutkan.
4. Jika file sensitif sudah pernah terlacak Git, menambah `.gitignore` tidak menghapusnya dari tracking atau riwayat. Periksa sebelum push.
5. Buka PowerShell di folder proyek:

```powershell
Set-Location C:\php\ProjectRentalMobil
git init
git add .
git status
```

6. Pastikan `composer.json`, `composer.lock`, `index.php`, `koneksi.php`, konfigurasi Midtrans berbasis environment, `Caddyfile`, dan `php.ini` ikut; pastikan kredensial serta data pribadi tidak ikut.
7. Ganti URL contoh berikut dengan URL repository Anda, lalu jalankan:

```powershell
git commit -m "Siapkan deployment Railway"
git branch -M main
git remote add origin https://github.com/USERNAME/ProjectRentalMobil.git
git push -u origin main
```

Jika repository lokal sudah memiliki remote `origin`, periksa dengan `git remote -v` dan gunakan remote yang benar, tanpa menjalankan `git remote add` lagi.

## 6. Buat project Railway dan database MySQL

1. Login ke Railway, pilih **New Project → Empty Project**.
2. Di dalam project, pilih **New → Database → MySQL** atau cari MySQL melalui menu tambah service.
3. Tunggu database berjalan.
4. Periksa bahwa database mempunyai volume untuk menyimpan data MySQL, umumnya pada `/var/lib/mysql`.
5. Buka tab **Variables** service MySQL. Railway menyediakan `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, dan `MYSQLDATABASE`.
6. Catat nama service database. Contoh langkah berikut memakai nama persis **MySQL**.

Rujukan: [MySQL di Railway](https://docs.railway.com/databases/mysql).

## 7. Tambahkan service aplikasi dari GitHub

1. Pada project yang sama, pilih **New → GitHub Repo**.
2. Hubungkan akun GitHub dan izinkan Railway mengakses repository proyek.
3. Pilih repository dan branch `main`.
4. Pastikan **Root Directory** menunjuk ke root repository, tempat `composer.json` dan `index.php` berada.
5. Gunakan builder **Railpack**. Biarkan Build Command dan Start Command mengikuti bawaan Railpack; tidak perlu `php artisan`, `npm start`, atau server PHP development.
6. Deployment awal mungkin belum berjalan sebelum Variables dan database diatur. Lanjutkan langkah berikut.

## 8. Isi Variables pada service aplikasi

Buka **service aplikasi → Variables**. Tambahkan nilai berikut melalui antarmuka Railway:

| Nama | Nilai |
| --- | --- |
| `DB_HOST` | `${{MySQL.MYSQLHOST}}` |
| `DB_PORT` | `${{MySQL.MYSQLPORT}}` |
| `DB_USER` | `${{MySQL.MYSQLUSER}}` |
| `DB_PASS` | `${{MySQL.MYSQLPASSWORD}}` |
| `DB_NAME` | `${{MySQL.MYSQLDATABASE}}` |
| `RAILPACK_PHP_ROOT_DIR` | `/app` |
| `RAILPACK_PHP_EXTENSIONS` | `mysqli,curl,mbstring` |
| `MIDTRANS_IS_PRODUCTION` | `false` |
| `MIDTRANS_SERVER_KEY` | Server key sandbox dari dashboard Midtrans Anda |
| `MIDTRANS_CLIENT_KEY` | Client key sandbox dari dashboard Midtrans Anda |

Gunakan fitur **Reference Variable** untuk variabel database. Ganti `MySQL` pada referensi jika nama service berbeda. Variables ini dipasang pada service aplikasi, bukan hanya pada service database. Nilai `DB_NAME` mengikuti database Railway; tidak harus bernama `db_rentalmobil`.

Simpan/aplikasikan perubahan. Rujukan: [Railway Variables](https://docs.railway.com/variables).

## 9. Siapkan SQL yang kompatibel dengan MySQL

Dump bawaan berasal dari MariaDB. Jangan langsung menjalankan seluruh SQL tanpa penyesuaian, karena `ADD COLUMN IF NOT EXISTS` pada dump tidak kompatibel dengan sintaks MySQL biasa.

1. Buat salinan lokal `db_rentalmobil.sql`, misalnya `db_rentalmobil_railway.sql`. Kerjakan salinannya.
2. Di bagian akhir salinan tersebut, ganti blok berikut:

```sql
ALTER TABLE `user`
  ADD COLUMN IF NOT EXISTS `no_hp` VARCHAR(20) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `alamat` TEXT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `foto_profil` VARCHAR(255) DEFAULT NULL;
```

dengan:

```sql
ALTER TABLE `user`
  ADD COLUMN `no_hp` VARCHAR(20) DEFAULT NULL,
  ADD COLUMN `alamat` TEXT DEFAULT NULL,
  ADD COLUMN `foto_profil` VARCHAR(255) DEFAULT NULL;
```

3. Penyesuaian ini untuk **impor pertama ke database kosong**. Jika menggunakan ekspor terbaru dari hosting lama, periksa kolom yang sudah tersedia sebelum menjalankan `ALTER TABLE`.
4. Tidak perlu mengimpor `db_update_fitur_baru.sql` setelah dump bawaan ini: struktur tabel fitur baru, kolom agreement, dan kolom promo sudah ada dalam `db_rentalmobil.sql`. Skrip tambahan juga memakai `USE db_rentalmobil`, sintaks MariaDB, dan seed voucher yang dapat bentrok.

## 10. Impor database melalui MySQL Workbench

1. Buka service **MySQL → Settings → Networking** di Railway.
2. Aktifkan **Public Access / TCP Proxy** untuk koneksi database dari komputer. Catat host publik dan port publik yang ditampilkan; jangan memakai domain privat `*.railway.internal` dari komputer lokal.
3. Buka MySQL Workbench → tambah koneksi **Standard TCP/IP**.
4. Isi host dan port publik Railway, username `MYSQLUSER`, password `MYSQLPASSWORD`, dan schema/database sesuai `MYSQLDATABASE`.
5. Pilih **Test Connection**, lalu hubungkan.
6. Pilih **Server → Data Import → Import from Self-Contained File** dan pilih `db_rentalmobil_railway.sql`.
7. Pilih database Railway sebagai **Default Target Schema**, kemudian **Start Import**. Pastikan semua statement berhasil; jangan mengabaikan pesan error.
8. Refresh daftar tabel dan pastikan tabel `user`, `mobil`, `motor`, `log_penyewaan`, dan `log_sewa_motor` tersedia.
9. Jika ingin menguji halaman Snap Midtrans, tambahkan kolom yang dibutuhkan aplikasi pada database hasil impor pertama ini:

```sql
ALTER TABLE `log_penyewaan`
  ADD COLUMN `midtrans_order_id` VARCHAR(100) DEFAULT NULL;

ALTER TABLE `log_sewa_motor`
  ADD COLUMN `midtrans_order_id` VARCHAR(100) DEFAULT NULL;
```

Periksa keberadaan kolom sebelum menjalankannya pada database lain. Penambahan kolom ini belum menyelesaikan implementasi callback pembayaran.

10. Jangan mengimpor ulang dump penuh ke database yang sudah dipakai karena tabel/data dapat bentrok. Cadangkan database sebelum perubahan berikutnya.
11. Public Access database dapat dimatikan setelah impor jika tidak dibutuhkan. Aplikasi memakai referensi koneksi privat; koneksi Workbench memerlukan Public Access lagi jika diaktifkan kembali. Penggunaan TCP Proxy dapat menambah biaya egress, sesuai [dokumentasi MySQL Railway](https://docs.railway.com/databases/mysql).

## 11. Pasang volume untuk upload

Filesystem deployment biasa tidak menyimpan upload secara permanen. Gunakan volume sesuai [panduan volume Railway](https://docs.railway.com/volumes).

1. Tambahkan volume dan hubungkan ke **service aplikasi**.
2. Isi **Mount Path** dengan `/app/uploads`.
3. Simpan dan deploy ulang.
4. Volume baru mulai kosong dan menutupi folder `uploads` dari image. Jika memindahkan hosting lama, salin file upload lama ke volume melalui fasilitas pengelolaan volume Railway/CLI; mengimpor database saja tidak ikut memindahkan foto atau bukti pembayaran.
5. Uji upload bukti. Kode `upload_bukti.php` akan membuat subfolder `uploads/bukti` jika belum ada.
6. Jika muncul permission denied, periksa pemilik dan izin volume. Untuk image yang berjalan dengan user non-root, dokumentasi Railway menyebut opsi `RAILWAY_RUN_UID=0`; gunakan bila sesuai dengan error yang ditemukan.
7. Setelah upload berhasil, deploy ulang sekali dan pastikan file tetap dapat dibuka.

Volume aplikasi berbeda dari volume database. Keduanya perlu dijaga dan dicadangkan. Sesi login PHP pada penyimpanan sementara juga dapat berakhir ketika aplikasi di-deploy ulang.

## 12. Deploy dan buat domain publik

1. Pastikan perubahan Variables dan volume sudah diaplikasikan.
2. Buka **Deployments**, lalu deploy/redeploy versi terbaru.
3. Periksa Build Logs: Composer harus selesai memasang dependency dan ekstensi PHP harus tersedia.
4. Periksa Deploy Logs: web server harus berjalan tanpa error konfigurasi atau koneksi database.
5. Buka **Settings → Networking → Public Networking → Generate Domain** pada service aplikasi.
6. Jika diminta target port, gunakan port yang didengarkan server sesuai nilai `PORT` Railway. Caddyfile di atas membaca `PORT` dengan fallback `8080`.
7. Buka URL HTTPS yang diberikan, misalnya `https://rental-anda.up.railway.app`.

Rujukan: [Public Networking Railway](https://docs.railway.com/networking/public-networking).

## 13. Lengkapi Midtrans jika pembayaran online akan digunakan

1. Selesaikan endpoint notifikasi Midtrans terlebih dahulu. Jangan memasang `payment_callback.php` sebagai Notification URL karena kode yang ada masih untuk Tripay.
2. Setelah endpoint yang benar tersedia dan diuji, pasang URL HTTPS lengkapnya pada pengaturan Payment Notification URL dashboard Midtrans di environment yang sesuai.
3. Isi `MIDTRANS_NOTIFICATION_URL` di Railway jika kode aplikasi Anda memang membacanya. Menambahkan konstanta/variable tersebut saja tidak otomatis mendaftarkan webhook ke Midtrans.
4. Atur halaman pengembalian yang benar melalui pengaturan Snap/redirect. `payment_return.php` saat ini memerlukan perbaikan dependency Tripay sebelum dipakai.
5. Uji transaksi sandbox sampai status pada database ikut berubah; popup pembayaran sukses saja belum membuktikan callback berhasil.
6. Aktifkan production setelah alur tersebut berfungsi dan pasangan key serta URL Snap sudah sesuai production.

Rujukan: [webhook Midtrans](https://docs.midtrans.com/docs/https-notification-webhooks) dan [pengaturan pembayaran Midtrans](https://docs.midtrans.com/docs/payment-settings).

## 14. Periksa hasil hosting

- [ ] Beranda, halaman kendaraan, dan gambar terbuka melalui HTTPS.
- [ ] Login admin dan customer berhasil.
- [ ] Booking mobil serta motor tersimpan dan tampil di riwayat.
- [ ] Upload bukti, verifikasi admin, dan perubahan status berhasil.
- [ ] Upload tetap ada setelah redeploy.
- [ ] Data database tetap ada setelah restart service.
- [ ] Halaman profil dan chat tidak menghasilkan error SQL.
- [ ] Akses HTTP ke file `.sql`, `.env`, `composer.json`, dan dokumen akun menghasilkan 404.
- [ ] Akun contoh dari dump sudah diamankan sebelum website dibuka untuk pengguna umum.
- [ ] Backup database dan upload tersedia.
- [ ] Pembayaran online sudah diuji penuh sebelum menerima uang sungguhan.

## 15. Cara memperbarui website

Setelah mengubah kode, periksa daftar file sebelum commit:

```powershell
git status
git add nama-file-yang-diubah.php
git commit -m "Perbarui website rental"
git push origin main
```

Railway dapat melakukan deployment otomatis dari branch yang terhubung. Periksa pengaturan autodeploy dan hasil Deployment. Jangan menjalankan impor dump ulang pada setiap deployment; perubahan schema berikutnya dilakukan sebagai migrasi terpisah.

## Troubleshooting

| Masalah | Pemeriksaan dan tindakan |
| --- | --- |
| Database masih mengarah ke hosting lama | Pastikan `koneksi.php` sudah membaca `getenv()` dan versi kode terbaru sudah di-deploy. |
| Access denied / unknown database | Cocokkan referensi `DB_USER`, `DB_PASS`, `DB_NAME`, serta nama service MySQL. |
| Host privat tidak bisa diakses dari laptop | Gunakan host dan port TCP Proxy untuk Workbench; gunakan host privat untuk aplikasi di project Railway yang sama. |
| SQL error dekat `IF NOT EXISTS` | Gunakan salinan SQL yang sudah disesuaikan pada langkah 9. Jika impor gagal sebagian, periksa tabel yang sudah terbentuk sebelum mencoba lagi. |
| Table already exists / duplicate entry | Dump dijalankan pada database berisi data atau skrip tambahan mengulang seed. Jangan menghapus database aktif; gunakan backup dan periksa kebutuhan migrasi. |
| Unknown column `midtrans_order_id` | Tambahkan kolom pada kedua tabel sewa seperti langkah 10, setelah memeriksa kolom belum tersedia. |
| `mysqli_connect` tidak tersedia | Periksa `RAILPACK_PHP_EXTENSIONS=mysqli,curl,mbstring`, kemudian lakukan rebuild. |
| `midtrans_config.php` tidak ditemukan | Pastikan konfigurasi berbasis environment sudah diikutkan Git dan tidak lagi diabaikan `.gitignore`. |
| `vendor/autoload.php` tidak ditemukan | Periksa Build Logs, `composer.json`, dan `composer.lock`; dependency harus terpasang oleh Composer saat build. |
| `tripay_helper.php` tidak ditemukan | Ada rute pembayaran lama yang belum disesuaikan. Perbaiki integrasi sebelum menggunakan callback/return tersebut. |
| 502 / aplikasi tidak merespons | Periksa Deploy Logs, validitas Caddyfile, listener `PORT`, dan target port domain Railway. |
| 404 pada beranda atau halaman PHP | Pastikan root repository benar, `index.php` tersedia, dan document root `/app`. |
| Upload hilang setelah redeploy | Periksa mount `/app/uploads` dan pastikan file tersimpan di volume. |
| Gambar ada di lokal tetapi hilang di Railway | Periksa huruf besar/kecil nama file; filesystem Linux membedakannya. Periksa juga migrasi file upload lama. |


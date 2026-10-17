# Supabase PostgreSQL + Vercel

## 1. Impor database

1. Buat project Supabase dan simpan password database.
2. Buka **SQL Editor**, jalankan seluruh isi `db_rentalmobil_supabase.sql` sekali pada schema `public` yang belum memiliki tabel aplikasi.
3. Jalankan isi `supabase_storage.sql` untuk membuat bucket private `bukti-bayar`.

SQL PostgreSQL dikonversi dari `db_rentalmobil_infinityfree.sql`; file MySQL asli tetap menjadi cadangan. Data, ID, relasi, dan hash password dipertahankan. Sequence ID disesuaikan untuk data baru. SQL baru mencakup tabel chat, notifikasi customer, session, kolom profil, status denda, dan kolom pembayaran. Jangan menjalankan dump MySQL atau `db_update_fitur_baru.sql` di Supabase.

Tabel `user` dinamai `app_users`. Akun login tetap memakai tabel aplikasi dan hash password lama; tidak perlu membuat akun Supabase Auth. RLS diaktifkan dan akses tabel untuk `anon`/`authenticated` ditutup; PHP menggunakan koneksi database backend.

Untuk menghasilkan ulang file SQL dari dump asli: `node scripts/convert-database.cjs`. Perintah ini tidak mengimpor database.

## 2. Isi environment

Di Supabase, buka **Connect → Transaction pooler**, lalu salin URI PostgreSQL dengan port **6543**. Gunakan host dan username persis dari dashboard project:

```dotenv
DATABASE_URL=postgresql://postgres.PROJECT_REF:PASSWORD_URL_ENCODED@aws-0-REGION.pooler.supabase.com:6543/postgres?sslmode=require
SUPABASE_URL=https://PROJECT_REF.supabase.co
SUPABASE_SERVICE_ROLE_KEY=SERVICE_ROLE_KEY_DARI_SUPABASE
SUPABASE_STORAGE_BUCKET=bukti-bayar
APP_URL=https://NAMA-PROJECT.vercel.app
```

URL-encode password pada URI jika mengandung `@`, `#`, `/`, `%`, atau `:`. `DATABASE_URL` adalah koneksi PostgreSQL, berbeda dengan URL project HTTPS dan API key Supabase. PDO memakai emulated prepares dan koneksi nonpersistent agar sesuai transaction pooler.

Alternatif URI: isi `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, dan `DB_SSLMODE` sesuai `.env.example`, lalu hapus `DATABASE_URL`. Password di `DB_PASS` tidak perlu di-URL-encode.

Untuk lokal, salin `.env.example` menjadi `.env` dan isi nilainya. Untuk Vercel, isi **Project → Settings → Environment Variables** pada Production/Preview yang digunakan. Simpan service role key hanya di backend. Environment yang berubah membutuhkan redeploy.

## 3. Deploy Vercel

1. Upload source project ke repository Git, lalu import di Vercel.
2. Pilih framework **Other**. Biarkan Build Command, Install Command, dan Output Directory pada default tanpa override. Jangan mengisi output `public` atau `dist`.
3. Sertakan `vercel.json`, folder `api`, `config`, halaman PHP, `composer.json`, dan `composer.lock` di repository. `midtrans_config.php` kini aman dikomit karena membaca environment.
4. Isi environment, lalu deploy. Runtime komunitas `vercel-php@0.9.0` menjalankan PHP dan memasang dependency Composer.
5. Sesuaikan `APP_URL` ke domain deployment final dan redeploy.

Router `api/index.php` mempertahankan URL seperti `/login.php` dan `/pesan_mobil.php`. Hanya halaman yang diizinkan dan gambar SVG yang dilayani. Dump SQL, environment, konfigurasi, dan dokumen akun tidak disajikan ke browser.

Session login disimpan di PostgreSQL agar tersedia antar-invocation Vercel. Bukti pembayaran disimpan di Supabase Storage karena filesystem Vercel tidak menjadi penyimpanan permanen.

## 4. Pindahkan bukti pembayaran lama

Gambar tidak ikut dipindahkan saat impor SQL. Setelah `.env` lokal terisi dan bucket dibuat, jalankan sekali:

```powershell
php scripts/upload-existing-receipts.php
```

Script menyalin file di `uploads/bukti/` tanpa menghapus file lokal. Jika sebagian berhasil sebelum error, unggah sisanya dari dashboard Storage dengan nama file yang sama. Bukti baru langsung masuk ke bucket. Akses bukti diperiksa terhadap pemilik akun atau admin, kemudian memakai signed URL selama 60 detik. Batas unggahan **4 MB** per file agar sesuai batas body request Vercel.

## 5. Pembayaran

Transfer bank dan verifikasi bukti tetap tersedia. Untuk Midtrans, isi:

```dotenv
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_SERVER_KEY=SERVER_KEY_SANDBOX_ANDA
MIDTRANS_CLIENT_KEY=CLIENT_KEY_SANDBOX_ANDA
```

Daftarkan `https://DOMAIN-ANDA/midtrans_notification.php` sebagai Payment Notification URL di dashboard Midtrans. Untuk production, gunakan key production dan `MIDTRANS_IS_PRODUCTION=true`. Status lunas diverifikasi ke server Midtrans.

Endpoint lama `payment_callback.php` dan `payment_return.php` memakai Tripay, tetapi source awal tidak menyertakan `tripay_helper.php`. Endpoint itu mengembalikan 503 sampai integrasi Tripay dilengkapi. Gunakan transfer bank atau Midtrans yang tersedia.

## 6. Periksa deployment

- Login admin/customer dari data dump, lalu pindah halaman untuk memeriksa session.
- Buka daftar kendaraan, laporan harian/bulanan, dan riwayat sewa.
- Buat booking, unggah bukti maksimal 4 MB, dan verifikasi dari admin.
- Jika memakai Midtrans, lakukan transaksi sandbox dan periksa notification.
- Pastikan `/.env`, `/config/database.php`, dan `/db_rentalmobil_supabase.sql` mengembalikan 404.

Koneksi gagal (503): periksa URI/password, host/username pooler, SSL, status project Supabase, dan environment deployment. Tabel tidak ditemukan: SQL belum diimpor atau database tujuan berbeda. Upload gagal: periksa bucket dan environment Storage. Detail error tersedia di Vercel Logs.

## Lokal

Gunakan PHP 8.2+ dengan `pdo_pgsql`, `curl`, dan `fileinfo`, lalu:

```powershell
composer install
php -S localhost:8000 api/index.php
```

Default koneksi memakai SSL. Gunakan `DB_SSLMODE=disable` hanya untuk PostgreSQL lokal tanpa TLS. `vendor` dipasang Composer dan tidak perlu dikomit.

Referensi: [Koneksi Supabase](https://supabase.com/docs/guides/database/connecting-to-postgres), [Runtime PHP Vercel](https://github.com/vercel-community/php), [Supabase Storage](https://supabase.com/docs/guides/storage/uploads/standard-uploads).

# Hosting Rental Mobil di InfinityFree

Konfigurasi database di `koneksi.php` sudah mengarah ke InfinityFree. Nilai ini belum diverifikasi dengan akun hosting Anda. Error database disembunyikan dari pengunjung dan zona waktu memakai Asia/Jakarta.

## Langkah cepat

1. Login https://dash.infinityfree.com, buat hosting account dan pilih subdomain/domain. Jika akun sudah ada, buka akun tersebut.
2. Buka **Control Panel > MySQL Databases**. Buat database jika belum ada. Catat **MySQL Hostname, Username, Database Name** serta password hosting account dari detail akun. Gunakan nama database lengkap dengan awalan akun.
3. Cocokkan empat nilai `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` di bagian atas `koneksi.php` dengan panel akun Anda. Host database mengikuti panel, bukan `localhost`. Password hosting berbeda dari password login dashboard bila Anda mengaturnya berbeda.
4. Buka **phpMyAdmin** dari panel, pilih database **kosong** tadi, klik **Import**, pilih `db_rentalmobil_infinityfree.sql`, lalu **Go/Import**. File ini sudah menghindari `ADD COLUMN IF NOT EXISTS` untuk impor pertama. Jangan impor ulang ke database berisi data. Jika database hosting sudah berisi data terbaru, cadangkan dan gunakan data tersebut, jangan timpa dengan snapshot proyek.
5. Buka **File Manager** atau FTP. Masuk ke folder **htdocs milik domain tujuan**. Upload seluruh file `.php` di root proyek serta folder `images`, `uploads`, `vendor`, dan file `.htaccess`. Pastikan `uploads/.htaccess` juga ikut. Letakkan `index.php` langsung di `htdocs`, bukan di `htdocs/ProjectRentalMobil`. Jika halaman bawaan tampil, hapus hanya file halaman bawaan tersebut setelah memastikan bukan file aplikasi.
6. Tidak perlu upload file `.sql`, `daftar akun.txt`, panduan `.md`, `.git`, `composer.json`, atau `composer.lock`. SQL hanya diimpor melalui phpMyAdmin. `vendor` sudah tersedia di proyek dan perlu ikut upload.
7. Aktifkan/pasang sertifikat dari menu **Free SSL Certificates** di dashboard sesuai panduan penyedia, lalu buka `https://DOMAIN-ANDA/login.php`.
8. Login dengan akun yang ada di database, uji booking mobil/motor, upload bukti, lalu verifikasi melalui admin. Periksa nama dan nomor rekening tujuan di `payment_page.php` sebelum menerima pembayaran. Ganti password akun contoh melalui profil sebelum dipakai publik.

## Pembayaran

Gunakan transfer manual dan upload bukti pada `payment_page.php`, kemudian verifikasi admin. Free hosting InfinityFree memblokir webhook eksternal melalui pemeriksaan browser; notifikasi otomatis Midtrans tidak dapat diandalkan di paket ini. Proyek juga belum memiliki endpoint `midtrans_notification.php`, sementara `payment_callback.php` dan `payment_return.php` masih bergantung pada `tripay_helper.php` yang tidak tersedia. Hosting ini belum siap untuk alur pembayaran otomatis tersebut.

## Jika gagal

| Gejala | Tindakan |
| --- | --- |
| Koneksi database gagal | Cocokkan empat nilai koneksi dengan panel dan password hosting; database InfinityFree digunakan dari server hosting, bukan laptop. |
| Impor duplicate table/column | Hentikan impor ulang; gunakan database baru yang kosong atau periksa hasil impor parsial sebelum melanjutkan. |
| Halaman bawaan atau 404 | Periksa `index.php` berada langsung di htdocs domain tujuan. |
| Gambar hilang | Upload folder images/uploads dan cocokkan huruf besar-kecil nama file. |
| Upload gagal | Pastikan folder uploads tersedia dan bisa ditulis; bila perlu gunakan izin folder 755 melalui FTP. |
| Error 500 setelah upload | Pastikan `.htaccess` utuh dan file PHP tidak rusak saat upload; periksa log error jika disediakan panel. |

## Rujukan

- Upload dan konfigurasi database: https://forum.infinityfree.com/t/how-to-migrate-your-website-to-infinityfree/49315/1
- SSL: https://forum.infinityfree.com/t/how-to-get-free-ssl-https-on-infinityfree/49323
- Batasan webhook: https://forum.infinityfree.com/t/browser-security-system-features-and-limitations/49353

Belum dilakukan: login panel hosting, upload, impor database, dan pengujian di domain Anda.

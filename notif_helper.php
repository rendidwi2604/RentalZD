<?php
/**
 * notif_helper.php
 * Helper untuk mengirim notifikasi ke customer dan admin.
 * Include file ini di halaman yang perlu kirim notif.
 */

if (!function_exists('notif_customer')) {
    /**
     * Kirim notifikasi ke customer.
     *
     * @param AppDatabase $conn
     * @param int    $id_user
     * @param string $tipe        pesanan_dikonfirmasi|pesanan_ditolak|lunas|bukti_diterima|bukti_ditolak|denda|tagihan_denda|info
     * @param string $pesan
     * @param int    $id_sewa
     * @param string $jenis       mobil|motor
     * @param string $url_target  URL redirect saat notif diklik
     */
    function notif_customer($conn, $id_user, $tipe, $pesan, $id_sewa = null, $jenis = 'mobil', $url_target = 'riwayat_sewa.php') {
        // Buat tabel jika belum ada
        

        $id_user    = (int)$id_user;
        $id_sewa    = $id_sewa ? (int)$id_sewa : 'NULL';
        $tipe       = db_real_escape_string($conn, $tipe);
        $pesan      = db_real_escape_string($conn, $pesan);
        $jenis      = db_real_escape_string($conn, $jenis);
        $url_target = db_real_escape_string($conn, $url_target);
        $id_sewa_sql = $id_sewa === 'NULL' ? 'NULL' : "'$id_sewa'";

        db_query($conn, "INSERT INTO notifikasi_customer
            (id_user, tipe, id_sewa, jenis, pesan, url_target)
            VALUES ('$id_user', '$tipe', $id_sewa_sql, '$jenis', '$pesan', '$url_target')");
    }
}

if (!function_exists('notif_admin')) {
    /**
     * Kirim notifikasi ke admin (atau update jika sudah ada untuk sewa yang sama).
     */
    function notif_admin($conn, $tipe, $pesan, $id_sewa = null, $jenis = 'mobil') {
        // Buat tabel jika belum ada
        

        $id_sewa    = $id_sewa ? (int)$id_sewa : null;
        $tipe       = db_real_escape_string($conn, $tipe);
        $pesan      = db_real_escape_string($conn, $pesan);
        $jenis      = db_real_escape_string($conn, $jenis);

        if ($id_sewa) {
            // Cek apakah sudah ada notif tipe ini untuk sewa ini
            $existing = db_fetch_assoc(db_query($conn,
                "SELECT id_notif FROM notifikasi_admin WHERE id_sewa='$id_sewa' AND jenis='$jenis' AND tipe='$tipe'"));
            if ($existing) {
                db_query($conn, "UPDATE notifikasi_admin SET
                    sudah_baca=0, pesan='$pesan', created_at=timezone('Asia/Jakarta', CURRENT_TIMESTAMP)
                    WHERE id_notif='{$existing['id_notif']}'");
                return;
            }
        }

        $id_sewa_sql = $id_sewa ? "'$id_sewa'" : 'NULL';
        db_query($conn, "INSERT INTO notifikasi_admin (tipe, id_sewa, jenis, pesan)
            VALUES ('$tipe', $id_sewa_sql, '$jenis', '$pesan')");
    }
}

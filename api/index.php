<?php
// Only known public pages may be executed. Source, SQL dumps and config stay private.
$root = dirname(__DIR__);
chdir($root);
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
if (preg_match('#^/uploads/bukti/([a-zA-Z0-9_.-]+)$#', $path, $match)) {
    $_GET['file'] = $match[1];
    require $root . '/bukti_file.php';
    return;
}
if (preg_match('#^/images/([a-zA-Z0-9_-]+\.svg)$#', $path, $match) && is_file($root . $path)) {
    header('Content-Type: image/svg+xml');
    header('Cache-Control: public, max-age=86400');
    readfile($root . $path);
    return;
}
$page = $path === '/' ? 'index.php' : ltrim($path, '/');
$allowed = [
    'index.php', 'login.php', 'logout.php', 'about.php', 'profil.php',
    'rentalmobil.php', 'rental_motor.php', 'pesan_mobil.php', 'pesan_motor.php',
    'riwayat_sewa.php', 'log_penyewaan.php', 'kalender_ketersediaan.php',
    'cek_ketersediaan.php', 'cek_promo.php', 'konfirmasi_pesanan.php',
    'invoice.php', 'laporan_mobil.php', 'laporan_keuangan.php', 'export_laporan.php',
    'promo_voucher.php', 'rating_review.php', 'checklist_kendaraan.php',
    'chat.php', 'notifikasi.php', 'denda_biaya.php', 'user_management.php',
    'payment_page.php', 'payment_callback.php', 'payment_return.php',
    'midtrans_payment.php', 'midtrans_notification.php', 'upload_bukti.php', 'verifikasi_bukti.php', 'bukti_file.php',
];
if (!in_array($page, $allowed, true) || !is_file($root . '/' . $page)) {
    http_response_code(404); exit('Halaman tidak ditemukan.');
}
$_SERVER['SCRIPT_NAME'] = '/' . $page;
$_SERVER['PHP_SELF'] = '/' . $page;
header('X-Content-Type-Options: nosniff');
require $root . '/' . $page;

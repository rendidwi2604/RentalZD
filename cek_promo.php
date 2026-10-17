<?php
// cek_promo.php — AJAX endpoint validasi kode promo
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }
require_once 'koneksi.php';
if (file_exists('notif_helper.php')) require_once 'notif_helper.php';

header('Content-Type: application/json');

$kode       = strtoupper(trim($_POST['kode'] ?? ''));
$total_harga= floatval($_POST['total'] ?? 0);

if (!$kode) { echo json_encode(['valid'=>false,'pesan'=>'Kode kosong.']); exit; }

// Auto-create table check
$p = db_fetch_assoc(db_query($conn, "SELECT * FROM promo_voucher WHERE kode='$kode' LIMIT 1"));

if (!$p) { echo json_encode(['valid'=>false,'pesan'=>'Kode promo tidak ditemukan.']); exit; }
if ($p['status'] !== 'aktif') { echo json_encode(['valid'=>false,'pesan'=>'Kode promo tidak aktif.']); exit; }

$today = date('Y-m-d');
if ($p['berlaku_dari'] && $today < $p['berlaku_dari']) {
    echo json_encode(['valid'=>false,'pesan'=>"Promo belum berlaku. Mulai: {$p['berlaku_dari']}."]); exit;
}
if ($p['berlaku_sampai'] && $today > $p['berlaku_sampai']) {
    echo json_encode(['valid'=>false,'pesan'=>"Promo sudah expired ({$p['berlaku_sampai']})."]); exit;
}
if ($p['sudah_dipakai'] >= $p['max_penggunaan']) {
    echo json_encode(['valid'=>false,'pesan'=>'Kode promo sudah habis limit penggunaannya.']); exit;
}
if ($total_harga > 0 && $p['min_transaksi'] > 0 && $total_harga < $p['min_transaksi']) {
    echo json_encode(['valid'=>false,'pesan'=>'Minimum transaksi Rp '.number_format($p['min_transaksi'],0,',','.').' tidak terpenuhi.']); exit;
}

// Hitung diskon
$diskon = 0;
if ($p['tipe_diskon'] === 'persen') {
    $diskon = round($total_harga * $p['nilai_diskon'] / 100);
} else {
    $diskon = min($p['nilai_diskon'], $total_harga);
}

echo json_encode([
    'valid'       => true,
    'diskon'      => $diskon,
    'tipe'        => $p['tipe_diskon'],
    'nilai'       => $p['nilai_diskon'],
    'deskripsi'   => $p['deskripsi'],
    'pesan'       => "Promo berhasil! Hemat Rp " . number_format($diskon, 0, ',', '.'),
]);

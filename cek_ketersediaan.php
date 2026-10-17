<?php
// cek_ketersediaan.php — API endpoint: cek konflik tanggal
// Dipanggil via AJAX dari pesan_mobil.php / pesan_motor.php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }
require_once 'koneksi.php';
if (file_exists('notif_helper.php')) require_once 'notif_helper.php';

header('Content-Type: application/json');

$jenis           = $_GET['jenis']            ?? 'mobil';
$id_kendaraan    = intval($_GET['id'])        ?? 0;
$tanggal_sewa    = $_GET['tanggal_sewa']      ?? '';
$tanggal_kembali = $_GET['tanggal_kembali']   ?? '';

if (!$id_kendaraan || !$tanggal_sewa || !$tanggal_kembali) {
    echo json_encode(['tersedia' => true, 'pesan' => '']);
    exit;
}

// Validasi tanggal
$ts = DateTime::createFromFormat('Y-m-d', $tanggal_sewa);
$tk = DateTime::createFromFormat('Y-m-d', $tanggal_kembali);
if (!$ts || !$tk || $tk <= $ts) {
    echo json_encode(['tersedia' => false, 'pesan' => 'Tanggal tidak valid.']);
    exit;
}

// Cek konflik: ada booking lain di rentang tanggal ini?
if ($jenis === 'mobil') {
    $q = db_query($conn, "
        SELECT id_sewa, tanggal_sewa, tanggal_kembali
        FROM log_penyewaan
        WHERE id_mobil = '$id_kendaraan'
          AND status = 'aktif'
          AND payment_status != 'pending'
          AND NOT (tanggal_kembali <= '$tanggal_sewa' OR tanggal_sewa >= '$tanggal_kembali')
        LIMIT 1
    ");
} else {
    $q = db_query($conn, "
        SELECT id_sewa, tanggal_sewa, tanggal_kembali
        FROM log_sewa_motor
        WHERE id_motor = '$id_kendaraan'
          AND status = 'aktif'
          AND payment_status != 'pending'
          AND NOT (tanggal_kembali <= '$tanggal_sewa' OR tanggal_sewa >= '$tanggal_kembali')
        LIMIT 1
    ");
}

$konflik = db_fetch_assoc($q);

if ($konflik) {
    echo json_encode([
        'tersedia' => false,
        'pesan'    => "Kendaraan sudah dipesan pada {$konflik['tanggal_sewa']} s/d {$konflik['tanggal_kembali']}. Pilih tanggal lain."
    ]);
} else {
    echo json_encode(['tersedia' => true, 'pesan' => 'Kendaraan tersedia untuk tanggal ini! ✓']);
}

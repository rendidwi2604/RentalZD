<?php
// payment_callback.php
// Dipanggil otomatis oleh server Tripay ketika pembayaran berhasil.
// URL ini didaftarkan di Dashboard Tripay > Merchant > Callback URL
// TIDAK perlu session/login — ini server-to-server

require_once 'koneksi.php';
if (!is_file(__DIR__ . '/tripay_helper.php')) { http_response_code(503); exit('Integrasi Tripay belum tersedia.'); }
require_once __DIR__ . '/tripay_helper.php';
require_once 'notif_helper.php';

// Ambil raw body
$json      = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_CALLBACK_TOKEN'] ?? '';

// Validasi signature
if (!tripay_validate_callback($json, $signature)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid signature']);
    exit;
}

$payload = json_decode($json, true);
if (!$payload) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid payload']);
    exit;
}

$reference    = $payload['reference']    ?? '';
$merchant_ref = $payload['merchant_ref'] ?? '';
$status       = $payload['status']       ?? '';
$amount       = (int)($payload['total_amount'] ?? 0);

// Log semua callback untuk debugging
error_log('Tripay callback: ref=' . $reference . ' status=' . $status);

// Cari transaksi di tabel tripay_transactions


$trx = db_fetch_assoc(db_query($conn,
    "SELECT * FROM tripay_transactions WHERE merchant_ref = '" . db_real_escape_string($conn, $merchant_ref) . "'"
));

if (!$trx) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Transaction not found']);
    exit;
}

$id_sewa = $trx['id_sewa'];
$jenis   = $trx['jenis'];

// Update status di tripay_transactions
$esc_ref    = db_real_escape_string($conn, $reference);
$esc_status = db_real_escape_string($conn, $status);
db_query($conn, "UPDATE tripay_transactions SET
    tripay_ref='$esc_ref', status='$esc_status'
    " . ($status === 'PAID' ? ", paid_at=timezone('Asia/Jakarta', CURRENT_TIMESTAMP)" : "") . "
    WHERE merchant_ref='" . db_real_escape_string($conn, $merchant_ref) . "'");

// Jika PAID → update log_penyewaan / log_sewa_motor
if ($status === 'PAID') {
    if ($jenis === 'mobil') {
        $r = db_fetch_assoc(db_query($conn,
            "SELECT id_mobil FROM log_penyewaan WHERE id_sewa='$id_sewa'"));
        db_query($conn, "UPDATE log_penyewaan SET
            payment_status='lunas', status='selesai', admin_confirmed=1
            WHERE id_sewa='$id_sewa'");
        if ($r) db_query($conn,
            "UPDATE mobil SET status='tersedia' WHERE id_mobil='{$r['id_mobil']}'");
    } else {
        $r = db_fetch_assoc(db_query($conn,
            "SELECT id_motor FROM log_sewa_motor WHERE id_sewa='$id_sewa'"));
        db_query($conn, "UPDATE log_sewa_motor SET
            payment_status='lunas', status='selesai', admin_confirmed=1
            WHERE id_sewa='$id_sewa'");
        if ($r) db_query($conn,
            "UPDATE motor SET status='tersedia' WHERE id_motor='{$r['id_motor']}'");
    }

    // Notifikasi admin
    notif_admin($conn, 'pembayaran',
        "✅ Pembayaran OTOMATIS via Tripay (ref: {$reference}) untuk sewa #{$id_sewa} dikonfirmasi.",
        $id_sewa, $jenis
    );
    // Notifikasi customer
    $id_user_cb = $jenis === 'mobil'
        ? (db_fetch_assoc(db_query($conn, "SELECT id_user FROM log_penyewaan WHERE id_sewa='$id_sewa'"))['id_user'] ?? 0)
        : (db_fetch_assoc(db_query($conn, "SELECT id_user FROM log_sewa_motor WHERE id_sewa='$id_sewa'"))['id_user'] ?? 0);
    if ($id_user_cb) {
        notif_customer($conn, $id_user_cb, 'lunas',
            "✅ Pembayaran pesanan #{$id_sewa} telah dikonfirmasi LUNAS secara otomatis via Tripay. Terima kasih!",
            $id_sewa, $jenis,
            "invoice.php?id={$id_sewa}&jenis={$jenis}"
        );
    }
}

http_response_code(200);
echo json_encode(['success' => true]);

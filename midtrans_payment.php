<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'customer') { header('Location: login.php'); exit; }
require_once 'koneksi.php';
require_once 'midtrans_config.php';
require_once 'notif_helper.php';

$id_sewa = intval($_GET['id'] ?? 0);
$jenis = ($_GET['jenis'] ?? 'mobil') === 'motor' ? 'motor' : 'mobil';
$id_user = (int)$_SESSION['id_user'];

if (!$id_sewa) { header('Location: riwayat_sewa.php'); exit; }

if ($jenis === 'mobil') {
    $table = 'log_penyewaan';
    $sewa = db_fetch_assoc(db_query($conn,
        "SELECT lp.*, m.nama_mobil AS nama_kendaraan, u.nama_lengkap, u.no_hp
         FROM {$table} lp
         LEFT JOIN mobil m ON lp.id_mobil=m.id_mobil
         LEFT JOIN app_users u ON lp.id_user=u.id_user
         WHERE lp.id_sewa='$id_sewa' AND lp.id_user='$id_user'"));
} else {
    $table = 'log_sewa_motor';
    $sewa = db_fetch_assoc(db_query($conn,
        "SELECT ls.*, mt.nama_motor AS nama_kendaraan, u.nama_lengkap, u.no_hp
         FROM {$table} ls
         LEFT JOIN motor mt ON ls.id_motor=mt.id_motor
         LEFT JOIN app_users u ON ls.id_user=u.id_user
         WHERE ls.id_sewa='$id_sewa' AND ls.id_user='$id_user'"));
}
if (!$sewa) { header('Location: riwayat_sewa.php'); exit; }

$order_id = 'ZDR-' . strtoupper($jenis) . '-' . str_pad($id_sewa, 5, '0', STR_PAD_LEFT) . '-' . time() . '-' . bin2hex(random_bytes(4));

$params = [
    'transaction_details' => [
        'order_id' => $order_id,
        'gross_amount' => (int)$sewa['total_bayar'],
    ],
    'customer_details' => [
        'first_name' => $sewa['nama_lengkap'],
        'email' => 'customer@example.com', // Update with actual email
        'phone' => $sewa['no_hp'] ?? '',
    ],
    'item_details' => [[
        'id' => $id_sewa,
        'price' => (int)$sewa['total_bayar'],
        'quantity' => 1,
        'name' => "Sewa {$jenis} - {$sewa['nama_kendaraan']}",
    ]],
];

try {
    $snap_token = \Midtrans\Snap::getSnapToken($params);
} catch (Exception $e) {
    echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); history.back();</script>";
    exit;
}

// Update database with order_id
db_query($conn, "UPDATE {$table} SET
    midtrans_order_id = '$order_id',
    payment_method = 'midtrans'
    WHERE id_sewa = '$id_sewa'");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment - ZD RENT</title>
    <style>
        body { font-family: system-ui; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f3f4f6; }
        .loading { text-align: center; }
        .spinner { border: 3px solid #e5e7eb; border-top: 3px solid #3b82f6; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 16px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div style="max-width:800px;margin:20px auto">
        <h2>Pembayaran Midtrans</h2>
        <div id="midtrans-payment" style="width:100%;height:600px;border:1px solid #ddd;border-radius:8px;"></div>
    </div>
    
    <script src="https://<?= MIDTRANS_IS_PRODUCTION ? 'app' : 'app.sandbox' ?>.midtrans.com/snap/snap.js" data-client-key="<?= htmlspecialchars(MIDTRANS_CLIENT_KEY) ?>"></script>
    <script>
        snap.embed('<?= $snap_token ?>', {
            embedId: 'midtrans-payment',
            onSuccess: function(result){
                window.location.href='payment_page.php?id=<?= $id_sewa ?>&jenis=<?= $jenis ?>&paid=success';
            },
            onPending: function(result){
                window.location.href='payment_page.php?id=<?= $id_sewa ?>&jenis=<?= $jenis ?>&paid=pending';
            },
            onError: function(result){
                alert('Pembayaran gagal: ' + (result.status_message || 'Terjadi kesalahan'));
                window.location.href='payment_page.php?id=<?= $id_sewa ?>&jenis=<?= $jenis ?>&paid=error';
            },
            onClose: function() {
                window.location.href='payment_page.php?id=<?= $id_sewa ?>&jenis=<?= $jenis ?>';
            }
        });
    </script>
</body>
</html>

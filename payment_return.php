<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user'])) { header('Location: login.php'); exit; }
require_once 'koneksi.php';
if (!is_file(__DIR__ . '/tripay_helper.php')) { http_response_code(503); exit('Integrasi Tripay belum tersedia.'); }
require_once __DIR__ . '/tripay_helper.php';

$ref     = $_GET['ref'] ?? '';
$status  = 'unknown';
$trx     = null;
$sewa    = null;

if ($ref) {
    $esc = db_real_escape_string($conn, $ref);
    $trx = db_fetch_assoc(db_query($conn,
        "SELECT * FROM tripay_transactions WHERE merchant_ref='$esc'"));

    if ($trx) {
        // Refresh status dari Tripay API
        $live = tripay_get_transaction($trx['tripay_ref'] ?: $esc);
        if ($live) {
            $new_status = db_real_escape_string($conn, $live['status']);
            db_query($conn, "UPDATE tripay_transactions SET status='$new_status'
                " . ($live['status']==='PAID' ? ", paid_at=timezone('Asia/Jakarta', CURRENT_TIMESTAMP)" : "") . "
                WHERE merchant_ref='$esc'");
            $trx['status'] = $live['status'];

            // Auto-lunas jika PAID
            if ($live['status'] === 'PAID' && $trx['id_sewa']) {
                $id_sewa = $trx['id_sewa'];
                $jenis   = $trx['jenis'];
                if ($jenis === 'mobil') {
                    $r = db_fetch_assoc(db_query($conn,
                        "SELECT id_mobil FROM log_penyewaan WHERE id_sewa='$id_sewa'"));
                    db_query($conn, "UPDATE log_penyewaan SET
                        payment_status='lunas',status='selesai',admin_confirmed=1
                        WHERE id_sewa='$id_sewa' AND payment_status!='lunas'");
                    if ($r) db_query($conn,
                        "UPDATE mobil SET status='tersedia' WHERE id_mobil='{$r['id_mobil']}'");
                } else {
                    $r = db_fetch_assoc(db_query($conn,
                        "SELECT id_motor FROM log_sewa_motor WHERE id_sewa='$id_sewa'"));
                    db_query($conn, "UPDATE log_sewa_motor SET
                        payment_status='lunas',status='selesai',admin_confirmed=1
                        WHERE id_sewa='$id_sewa' AND payment_status!='lunas'");
                    if ($r) db_query($conn,
                        "UPDATE motor SET status='tersedia' WHERE id_motor='{$r['id_motor']}'");
                }
            }
        }
        $status = $trx['status'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Status Pembayaran — ZD RENT</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f4f4f5;min-height:100dvh;display:flex;align-items:center;justify-content:center;padding:1.5rem}
.card{background:#fff;border-radius:1.5rem;padding:2.5rem;max-width:440px;width:100%;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,.1)}
.icon{width:80px;height:80px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;font-size:2.5rem}
.icon.success{background:#f0fdf4}
.icon.pending{background:#fffbeb}
.icon.failed{background:#fef2f2}
h2{font-size:1.5rem;font-weight:800;margin-bottom:.75rem;letter-spacing:-.02em}
p{color:#666;line-height:1.7;font-size:.9rem;margin-bottom:1.5rem}
.ref{font-family:monospace;font-size:.8rem;background:#f4f4f5;padding:.4rem .8rem;border-radius:.5rem;display:inline-block;margin-bottom:1.5rem}
.btn{display:inline-flex;align-items:center;gap:.5rem;padding:.85rem 1.75rem;border-radius:.85rem;font-weight:700;font-size:.9rem;text-decoration:none;transition:all .18s}
.btn-primary{background:#111;color:#fff}
.btn-primary:hover{background:#333;transform:translateY(-1px)}
.btn-ghost{background:#f4f4f5;color:#111;margin-left:.5rem}
.btn-ghost:hover{background:#e5e5e5}
.brand{display:flex;align-items:center;justify-content:center;gap:.625rem;margin-bottom:2rem}
.brand-logo{width:36px;height:36px;background:#E63946;border-radius:.5rem;display:flex;align-items:center;justify-content:center}
.brand-name{font-size:1.1rem;font-weight:800;color:#111}
</style>
</head>
<body>
<div class="card">
  <div class="brand">
    <div class="brand-logo">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/><rect x="9" y="11" width="14" height="10" rx="2"/><circle cx="12" cy="16" r="1"/><circle cx="20" cy="16" r="1"/></svg>
    </div>
    <div class="brand-name">ZD RENT</div>
  </div>

  <?php if ($status === 'PAID'): ?>
  <div class="icon success">✅</div>
  <h2 style="color:#16a34a">Pembayaran Berhasil!</h2>
  <p>Transaksi Anda telah <strong>dikonfirmasi otomatis</strong>. Pesanan aktif dan kendaraan siap disiapkan. Cek riwayat sewa untuk detailnya.</p>
  <?php if ($ref): ?><div class="ref">Ref: <?= htmlspecialchars($ref) ?></div><?php endif; ?>
  <a href="riwayat_sewa.php" class="btn btn-primary">Lihat Riwayat Sewa</a>

  <?php elseif (in_array($status, ['UNPAID','PENDING'])): ?>
  <div class="icon pending">⏳</div>
  <h2 style="color:#d97706">Menunggu Pembayaran</h2>
  <p>Pembayaran Anda belum kami terima. Silakan selesaikan pembayaran sebelum waktu expired.</p>
  <?php if ($ref): ?><div class="ref">Ref: <?= htmlspecialchars($ref) ?></div><?php endif; ?>
  <?php if ($trx && $trx['id_sewa']): ?>
  <a href="payment_page.php?id=<?= $trx['id_sewa'] ?>&jenis=<?= $trx['jenis'] ?>" class="btn btn-primary">Lihat Instruksi Bayar</a>
  <?php endif; ?>
  <a href="riwayat_sewa.php" class="btn btn-ghost">Riwayat Sewa</a>

  <?php elseif (in_array($status, ['EXPIRED','FAILED','REFUND'])): ?>
  <div class="icon failed">❌</div>
  <h2 style="color:#dc2626">Pembayaran Gagal/Expired</h2>
  <p>Transaksi ini sudah <?= $status === 'EXPIRED' ? 'kadaluarsa' : 'gagal/dibatalkan' ?>. Silakan buat pesanan baru jika masih ingin menyewa.</p>
  <a href="pesan_mobil.php" class="btn btn-primary">Pesan Lagi</a>
  <a href="riwayat_sewa.php" class="btn btn-ghost">Riwayat</a>

  <?php else: ?>
  <div class="icon pending">🔍</div>
  <h2>Status Tidak Diketahui</h2>
  <p>Tidak dapat menemukan transaksi ini. Silakan cek riwayat sewa Anda.</p>
  <a href="riwayat_sewa.php" class="btn btn-primary">Riwayat Sewa</a>
  <?php endif; ?>
</div>
</body>
</html>

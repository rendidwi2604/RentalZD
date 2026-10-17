<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') { header('Location: login.php'); exit; }
require_once 'koneksi.php';
require_once 'notif_helper.php';

$msg_success = $msg_error = '';

// Admin: terima atau tolak bukti
if (isset($_POST['action']) && in_array($_POST['action'], ['terima','tolak'])) {
    $id_bukti = intval($_POST['id_bukti']);
    $action   = $_POST['action'];
    $id_sewa  = intval($_POST['id_sewa']);
    $jenis    = $_POST['jenis'] ?? 'mobil';

    $new_status = $action === 'terima' ? 'diterima' : 'ditolak';
    db_query($conn, "UPDATE bukti_bayar SET status='$new_status' WHERE id='$id_bukti'");

    if ($action === 'terima') {
        if ($jenis === 'mobil') {
            $r = db_fetch_assoc(db_query($conn,
                "SELECT lp.id_mobil, lp.id_user, lp.total_bayar, m.nama_mobil
                 FROM log_penyewaan lp LEFT JOIN mobil m ON lp.id_mobil=m.id_mobil
                 WHERE lp.id_sewa='$id_sewa'"));
            db_query($conn, "UPDATE log_penyewaan SET payment_status='lunas', status='selesai', admin_confirmed=1 WHERE id_sewa='$id_sewa'");
            if ($r) db_query($conn, "UPDATE mobil SET status='tersedia' WHERE id_mobil='{$r['id_mobil']}'");
        } else {
            $r = db_fetch_assoc(db_query($conn,
                "SELECT ls.id_motor, ls.id_user, ls.total_bayar, mt.nama_motor AS nama_mobil
                 FROM log_sewa_motor ls LEFT JOIN motor mt ON ls.id_motor=mt.id_motor
                 WHERE ls.id_sewa='$id_sewa'"));
            db_query($conn, "UPDATE log_sewa_motor SET payment_status='lunas', status='selesai', admin_confirmed=1 WHERE id_sewa='$id_sewa'");
            if ($r) db_query($conn, "UPDATE motor SET status='tersedia' WHERE id_motor='{$r['id_motor']}'");
        }

        // Notifikasi customer: lunas
        if ($r) {
            notif_customer(
                $conn, $r['id_user'],
                'bukti_diterima',
                "✅ Bukti transfer pesanan #{$id_sewa} ({$r['nama_mobil']}) telah diterima dan pembayaran dikonfirmasi LUNAS. Terima kasih!",
                $id_sewa, $jenis,
                "invoice.php?id={$id_sewa}&jenis={$jenis}"
            );
        }

        db_query($conn, "UPDATE notifikasi_admin SET sudah_baca=1 WHERE id_sewa='$id_sewa' AND jenis='$jenis' AND tipe='pembayaran'");
        $msg_success = "Bukti diterima. Sewa #$id_sewa dikonfirmasi LUNAS dan customer dinotifikasi.";
    } else {
        // Tolak: kembalikan ke pending
        if ($jenis === 'mobil') {
            $r = db_fetch_assoc(db_query($conn, "SELECT id_user FROM log_penyewaan WHERE id_sewa='$id_sewa'"));
            db_query($conn, "UPDATE log_penyewaan SET payment_status='pending' WHERE id_sewa='$id_sewa'");
        } else {
            $r = db_fetch_assoc(db_query($conn, "SELECT id_user FROM log_sewa_motor WHERE id_sewa='$id_sewa'"));
            db_query($conn, "UPDATE log_sewa_motor SET payment_status='pending' WHERE id_sewa='$id_sewa'");
        }

        // Notifikasi customer: ditolak
        if ($r) {
            notif_customer(
                $conn, $r['id_user'],
                'bukti_ditolak',
                "❌ Bukti transfer pesanan #{$id_sewa} ditolak admin. Silakan upload ulang bukti yang benar agar pembayaran dapat dikonfirmasi.",
                $id_sewa, $jenis,
                "payment_page.php?id={$id_sewa}&jenis={$jenis}"
            );
        }

        $msg_success = "Bukti ditolak. Status sewa #$id_sewa dikembalikan ke pending dan customer dinotifikasi.";
    }
}

// Query semua bukti
$q_bukti = db_query($conn, "
    SELECT bb.*, u.nama_lengkap, u.username,
           COALESCE(m.nama_mobil, mt.nama_motor) AS nama_kendaraan,
           COALESCE(lp.total_bayar, ls.total_bayar) AS total_bayar,
           COALESCE(lp.payment_method, ls.payment_method) AS payment_method
    FROM bukti_bayar bb
    LEFT JOIN app_users u ON bb.id_user=u.id_user
    LEFT JOIN log_penyewaan lp ON bb.jenis='mobil' AND bb.id_sewa=lp.id_sewa
    LEFT JOIN log_sewa_motor ls ON bb.jenis='motor' AND bb.id_sewa=ls.id_sewa
    LEFT JOIN mobil m ON lp.id_mobil=m.id_mobil
    LEFT JOIN motor mt ON ls.id_motor=mt.id_motor
    ORDER BY bb.status ASC, bb.created_at DESC");

$menunggu = (int)(db_fetch_assoc(db_query($conn,
    "SELECT COUNT(*) as c FROM bukti_bayar WHERE status='menunggu'"))['c'] ?? 0);

$page_title  = 'Verifikasi Bukti Bayar';
$active_menu = 'konfirmasi';
include '_layout.php';
?>

<?php if ($msg_success): ?><div class="alert alert-success"><?= htmlspecialchars($msg_success) ?></div><?php endif; ?>
<?php if ($msg_error): ?><div class="alert alert-error"><?= htmlspecialchars($msg_error) ?></div><?php endif; ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-6);flex-wrap:wrap;gap:var(--space-3)">
  <div>
    <h2 style="font-size:var(--text-xl);font-weight:800;letter-spacing:-.02em">Verifikasi Bukti Transfer</h2>
    <p style="color:var(--color-text-muted);font-size:var(--text-sm)"><?= $menunggu ?> bukti menunggu verifikasi · Notifikasi otomatis dikirim ke customer</p>
  </div>
</div>

<?php if ($menunggu > 0): ?>
<div class="alert alert-warning" style="margin-bottom:var(--space-5)">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
  <strong><?= $menunggu ?> bukti transfer</strong> menunggu verifikasi. Terima = customer dinotifikasi lunas. Tolak = customer diminta upload ulang.
</div>
<?php endif; ?>

<div class="card">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>#</th><th>Booking</th><th>Customer</th><th>Total</th><th>Bukti</th><th>Catatan</th><th>Waktu</th><th>Status</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        <?php $no=1; while ($b = db_fetch_assoc($q_bukti)): ?>
        <tr style="<?= $b['status']==='menunggu' ? 'background:rgba(251,191,36,.05)' : '' ?>">
          <td><?= $no++ ?></td>
          <td>
            <div style="font-weight:700">#<?= $b['id_sewa'] ?> · <?= htmlspecialchars($b['nama_kendaraan'] ?? '?') ?></div>
            <div style="font-size:11px;color:var(--color-text-faint)"><?= ucfirst($b['jenis']) ?></div>
          </td>
          <td>
            <div style="font-weight:600"><?= htmlspecialchars($b['nama_lengkap'] ?? '') ?></div>
            <div style="font-size:11px;color:var(--color-text-faint)">@<?= htmlspecialchars($b['username'] ?? '') ?></div>
          </td>
          <td style="font-weight:700">Rp <?= number_format($b['total_bayar'],0,',','.') ?></td>
          <td>
            <?php
            $ext = strtolower(pathinfo($b['file_path'] ?? '', PATHINFO_EXTENSION));
            if ($b['file_path']):
              if (in_array($ext, ['jpg','jpeg','png','webp'])): ?>
            <a href="<?= htmlspecialchars('bukti_file.php?file=' . rawurlencode(basename($b['file_path']))) ?>" target="_blank">
              <img src="<?= htmlspecialchars('bukti_file.php?file=' . rawurlencode(basename($b['file_path']))) ?>" style="width:64px;height:48px;object-fit:cover;border-radius:6px;border:1px solid var(--color-border)">
            </a>
            <?php else: ?>
            <a href="<?= htmlspecialchars('bukti_file.php?file=' . rawurlencode(basename($b['file_path']))) ?>" target="_blank" class="btn btn-ghost btn-sm">📄 PDF</a>
            <?php endif; else: ?>
            <span style="color:var(--color-text-faint);font-size:var(--text-xs)">Tidak ada</span>
            <?php endif; ?>
          </td>
          <td style="font-size:var(--text-xs);max-width:120px"><?= htmlspecialchars($b['catatan'] ?? '—') ?></td>
          <td style="font-size:11px;color:var(--color-text-faint)"><?= date('d M H:i', strtotime($b['created_at'])) ?></td>
          <td>
            <?php if ($b['status'] === 'menunggu'): ?>
            <span class="badge badge-warning">⏳ Menunggu</span>
            <?php elseif ($b['status'] === 'diterima'): ?>
            <span class="badge badge-success">✓ Diterima</span>
            <?php else: ?>
            <span class="badge badge-neutral">✗ Ditolak</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($b['status'] === 'menunggu'): ?>
            <div style="display:flex;gap:var(--space-2)">
              <form method="POST" style="margin:0">
                <input type="hidden" name="id_bukti" value="<?= $b['id'] ?>">
                <input type="hidden" name="id_sewa" value="<?= $b['id_sewa'] ?>">
                <input type="hidden" name="jenis" value="<?= $b['jenis'] ?>">
                <input type="hidden" name="action" value="terima">
                <button type="submit" class="btn btn-primary btn-sm" data-confirm="Terima & konfirmasi LUNAS?">✓ Lunas</button>
              </form>
              <form method="POST" style="margin:0">
                <input type="hidden" name="id_bukti" value="<?= $b['id'] ?>">
                <input type="hidden" name="id_sewa" value="<?= $b['id_sewa'] ?>">
                <input type="hidden" name="jenis" value="<?= $b['jenis'] ?>">
                <input type="hidden" name="action" value="tolak">
                <button type="submit" class="btn btn-danger btn-sm" data-confirm="Tolak bukti ini?">✗ Tolak</button>
              </form>
            </div>
            <?php else: ?>
            <a href="invoice.php?id=<?= $b['id_sewa'] ?>&jenis=<?= $b['jenis'] ?>" class="btn btn-ghost btn-sm" target="_blank">Invoice</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '_layout_footer.php'; ?>

<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') { header('Location: login.php'); exit; }
require_once 'koneksi.php';
require_once 'notif_helper.php';

$msg_success = $msg_error = '';

// ── TAMBAH DENDA ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_denda'])) {
    $id_sewa   = intval($_POST['id_sewa']);
    $jenis     = $_POST['jenis'] ?? 'mobil';
    $tipe      = db_real_escape_string($conn, $_POST['tipe_denda'] ?? 'kerusakan');
    $deskripsi = db_real_escape_string($conn, trim($_POST['deskripsi'] ?? ''));
    $jumlah    = floatval($_POST['jumlah'] ?? 0);
    $by        = $_SESSION['id_user'];

    if ($id_sewa && $jumlah > 0) {
        db_query($conn, "INSERT INTO denda_biaya (id_sewa,jenis,tipe_denda,deskripsi,jumlah,input_by)
            VALUES ('$id_sewa','$jenis','$tipe','$deskripsi','$jumlah','$by')");
        $id_denda_baru = db_insert_id($conn);

        // ── Update total tagihan di log_penyewaan / log_sewa_motor ──
        if ($jenis === 'mobil') {
            $row_sewa = db_fetch_assoc(db_query($conn,
                "SELECT lp.id_user, lp.total_bayar, m.nama_mobil
                 FROM log_penyewaan lp LEFT JOIN mobil m ON lp.id_mobil=m.id_mobil
                 WHERE lp.id_sewa='$id_sewa'"));
            // Tambahkan jumlah denda ke total_bayar
            db_query($conn, "UPDATE log_penyewaan SET total_bayar = total_bayar + '$jumlah', payment_status='pending'
                WHERE id_sewa='$id_sewa'");
        } else {
            $row_sewa = db_fetch_assoc(db_query($conn,
                "SELECT ls.id_user, ls.total_bayar, mt.nama_motor AS nama_mobil
                 FROM log_sewa_motor ls LEFT JOIN motor mt ON ls.id_motor=mt.id_motor
                 WHERE ls.id_sewa='$id_sewa'"));
            db_query($conn, "UPDATE log_sewa_motor SET total_bayar = total_bayar + '$jumlah', payment_status='pending'
                WHERE id_sewa='$id_sewa'");
        }

        // ── Notifikasi customer ──
        if ($row_sewa) {
            $tipe_label = [
                'kerusakan'     => 'Kerusakan',
                'keterlambatan' => 'Keterlambatan',
                'bbm_kurang'    => 'BBM Kurang',
                'kehilangan'    => 'Kehilangan Aksesoris',
                'lainnya'       => 'Biaya Lain',
            ];
            $label_tipe  = $tipe_label[$tipe] ?? ucfirst($tipe);
            $jumlah_fmt  = number_format($jumlah, 0, ',', '.');
            $total_baru  = db_fetch_assoc(db_query($conn,
                $jenis === 'mobil'
                    ? "SELECT total_bayar FROM log_penyewaan WHERE id_sewa='$id_sewa'"
                    : "SELECT total_bayar FROM log_sewa_motor WHERE id_sewa='$id_sewa'"
            ))['total_bayar'] ?? 0;

            $pesan_cust  = "⚠️ Denda {$label_tipe} sebesar Rp {$jumlah_fmt} telah ditambahkan ke tagihan pesanan #{$id_sewa} ({$row_sewa['nama_mobil']}). " .
                           ($deskripsi ? "Alasan: {$deskripsi}. " : '') .
                           "Total tagihan baru: Rp " . number_format($total_baru, 0, ',', '.') . ". Silakan selesaikan pembayaran.";

            notif_customer(
                $conn,
                $row_sewa['id_user'],
                'tagihan_denda',
                $pesan_cust,
                $id_sewa,
                $jenis,
                "payment_page.php?id={$id_sewa}&jenis={$jenis}"
            );
        }

        $msg_success = "Denda berhasil ditambahkan dan customer telah dinotifikasi. Total tagihan pesanan #$id_sewa diperbarui.";
    } else {
        $msg_error = "Harap isi semua field dengan benar.";
    }
}

// ── HAPUS DENDA ──
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    // Kurangi total_bayar juga
    $denda_row = db_fetch_assoc(db_query($conn,
        "SELECT * FROM denda_biaya WHERE id_denda='$id'"));
    if ($denda_row) {
        $tbl = $denda_row['jenis'] === 'mobil' ? 'log_penyewaan' : 'log_sewa_motor';
        db_query($conn, "UPDATE $tbl SET total_bayar = GREATEST(0, total_bayar - '{$denda_row['jumlah']}')
            WHERE id_sewa='{$denda_row['id_sewa']}'");
    }
    db_query($conn, "DELETE FROM denda_biaya WHERE id_denda='$id'");
    $msg_success = "Denda berhasil dihapus dan tagihan diperbarui.";
}

// ── QUERY SEWA AKTIF & SELESAI ──
$q_sewa = db_query($conn, "
    SELECT lp.id_sewa, u.nama_lengkap, m.nama_mobil AS kendaraan, m.merk, 'mobil' AS jenis, lp.tanggal_kembali, lp.total_bayar, lp.payment_status
    FROM log_penyewaan lp LEFT JOIN app_users u ON lp.id_user=u.id_user LEFT JOIN mobil m ON lp.id_mobil=m.id_mobil
    WHERE lp.status='selesai' OR lp.status='aktif'
    UNION ALL
    SELECT ls.id_sewa, u.nama_lengkap, mt.nama_motor AS kendaraan, mt.merk, 'motor' AS jenis, ls.tanggal_kembali, ls.total_bayar, ls.payment_status
    FROM log_sewa_motor ls LEFT JOIN app_users u ON ls.id_user=u.id_user LEFT JOIN motor mt ON ls.id_motor=mt.id_motor
    WHERE ls.status='selesai' OR ls.status='aktif'
    ORDER BY tanggal_kembali DESC");

// ── QUERY DENDA ──
$q_denda = db_query($conn, "
    SELECT d.*, u.nama_lengkap AS admin_nama,
           u2.nama_lengkap AS customer_nama,
           COALESCE(m.nama_mobil, mt.nama_motor) AS kendaraan
    FROM denda_biaya d
    LEFT JOIN app_users u ON d.input_by=u.id_user
    LEFT JOIN log_penyewaan lp ON d.jenis='mobil' AND d.id_sewa=lp.id_sewa
    LEFT JOIN log_sewa_motor ls ON d.jenis='motor' AND d.id_sewa=ls.id_sewa
    LEFT JOIN app_users u2 ON COALESCE(lp.id_user, ls.id_user)=u2.id_user
    LEFT JOIN mobil m ON lp.id_mobil=m.id_mobil
    LEFT JOIN motor mt ON ls.id_motor=mt.id_motor
    ORDER BY d.created_at DESC");

$total_denda  = db_fetch_assoc(db_query($conn, "SELECT SUM(jumlah) as t FROM denda_biaya"))['t'] ?? 0;
$jumlah_kasus = db_fetch_assoc(db_query($conn, "SELECT COUNT(*) as c FROM denda_biaya"))['c'] ?? 0;
$belum_lunas  = db_fetch_assoc(db_query($conn, "SELECT COUNT(*) as c FROM denda_biaya WHERE status_bayar='belum'"))['c'] ?? 0;

$page_title  = 'Denda & Biaya Tambahan';
$active_menu = 'log';
include '_layout.php';
?>

<?php if ($msg_success): ?><div class="alert alert-success"><?= htmlspecialchars($msg_success) ?></div><?php endif; ?>
<?php if ($msg_error): ?><div class="alert alert-error"><?= htmlspecialchars($msg_error) ?></div><?php endif; ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-6);flex-wrap:wrap;gap:var(--space-3)">
  <div>
    <h2 style="font-size:var(--text-xl);font-weight:800;letter-spacing:-.02em">⚠️ Denda & Biaya Tambahan</h2>
    <p style="color:var(--color-text-muted);font-size:var(--text-sm)">Denda otomatis masuk ke tagihan customer & notifikasi dikirim</p>
  </div>
</div>

<div class="stats-grid" style="margin-bottom:var(--space-6)">
  <div class="stat-card"><div class="stat-label">Total Nilai Denda</div><div class="stat-value" style="font-size:var(--text-lg)">Rp <?= number_format($total_denda,0,',','.') ?></div><div class="stat-sub">Semua periode</div></div>
  <div class="stat-card"><div class="stat-label">Jumlah Kasus</div><div class="stat-value" style="color:var(--color-warning)"><?= $jumlah_kasus ?></div><div class="stat-sub">Item denda</div></div>
  <div class="stat-card"><div class="stat-label">Belum Lunas</div><div class="stat-value" style="color:var(--color-error)"><?= $belum_lunas ?></div><div class="stat-sub">Perlu dibayar</div></div>
</div>

<!-- INFO BOX -->
<div class="alert alert-warning" style="margin-bottom:var(--space-5)">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
  <div>Denda yang ditambahkan akan <strong>otomatis masuk ke total tagihan</strong> pesanan dan <strong>notifikasi dikirim ke customer</strong> agar mereka tahu dan segera membayar.
  Status pembayaran pesanan akan dikembalikan ke <em>pending</em> jika sebelumnya sudah selesai.</div>
</div>

<div style="display:grid;grid-template-columns:1fr 1.5fr;gap:var(--space-6)">
  <!-- Form Tambah Denda -->
  <div class="card" style="align-self:start">
    <div class="card-header"><div class="card-title">Tambah Denda / Biaya</div></div>
    <form method="POST">
      <input type="hidden" name="tambah_denda" value="1">
      <div class="form-group">
        <label class="form-label">Pilih Booking</label>
        <select name="id_sewa" class="form-select" id="sel-sewa" required onchange="autoJenis()">
          <option value="">— Pilih booking —</option>
          <?php while ($s = db_fetch_assoc($q_sewa)): ?>
          <option value="<?= $s['id_sewa'] ?>" data-jenis="<?= $s['jenis'] ?>">
            #<?= $s['id_sewa'] ?> · <?= htmlspecialchars($s['kendaraan']) ?> · <?= htmlspecialchars($s['nama_lengkap']) ?>
            (Rp <?= number_format($s['total_bayar'],0,',','.') ?> · <?= $s['payment_status'] ?>)
          </option>
          <?php endwhile; ?>
        </select>
      </div>
      <input type="hidden" name="jenis" id="jenis-denda" value="mobil">

      <div class="form-group">
        <label class="form-label">Tipe Denda</label>
        <select name="tipe_denda" class="form-select">
          <option value="kerusakan">🔧 Kerusakan</option>
          <option value="keterlambatan">⏰ Keterlambatan</option>
          <option value="bbm_kurang">⛽ BBM Kurang</option>
          <option value="kehilangan">🔑 Kehilangan Aksesoris</option>
          <option value="lainnya">📋 Lainnya</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Deskripsi</label>
        <textarea name="deskripsi" class="form-input" rows="2" placeholder="Contoh: Penyok bumper depan, terlambat 2 jam..." style="resize:vertical"></textarea>
      </div>

      <div class="form-group">
        <label class="form-label">Jumlah (Rp)</label>
        <input type="number" name="jumlah" class="form-input" placeholder="Contoh: 250000" min="1" required>
      </div>

      <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:var(--radius-md);padding:var(--space-3);margin-bottom:var(--space-4);font-size:var(--text-xs);color:#856404">
        ⚠️ Denda akan otomatis ditambahkan ke total tagihan dan customer akan dinotifikasi.
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Denda & Notifikasi Customer
      </button>
    </form>
  </div>

  <!-- Tabel Denda -->
  <div class="card">
    <div class="card-header"><div class="card-title">Riwayat Denda</div></div>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr><th>#</th><th>Booking</th><th>Tipe</th><th>Deskripsi</th><th>Jumlah</th><th>Admin</th><th>Aksi</th></tr>
        </thead>
        <tbody>
          <?php $no = 1; while ($d = db_fetch_assoc($q_denda)): ?>
          <tr>
            <td><?= $no++ ?></td>
            <td>
              <div style="font-weight:700">#<?= $d['id_sewa'] ?> · <?= htmlspecialchars($d['kendaraan'] ?? '?') ?></div>
              <div style="font-size:11px;color:var(--color-text-faint)"><?= htmlspecialchars($d['customer_nama'] ?? '') ?> · <?= ucfirst($d['jenis']) ?></div>
            </td>
            <td>
              <?php
              $icons = ['kerusakan'=>'🔧','keterlambatan'=>'⏰','bbm_kurang'=>'⛽','kehilangan'=>'🔑','lainnya'=>'📋'];
              echo ($icons[$d['tipe_denda']] ?? '📋') . ' ' . ucfirst(str_replace('_', ' ', $d['tipe_denda']));
              ?>
            </td>
            <td style="max-width:140px;font-size:var(--text-xs)"><?= htmlspecialchars($d['deskripsi']) ?></td>
            <td style="font-weight:700;color:var(--color-error)">Rp <?= number_format($d['jumlah'],0,',','.') ?></td>
            <td style="font-size:11px;color:var(--color-text-faint)"><?= htmlspecialchars($d['admin_nama'] ?? '') ?></td>
            <td>
              <a href="?hapus=<?= $d['id_denda'] ?>" class="btn btn-danger btn-sm btn-icon"
                data-confirm="Hapus denda ini? Jumlah akan dikurangi dari tagihan customer.">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
              </a>
            </td>
          </tr>
          <?php endwhile; ?>
          <?php if ($jumlah_kasus === 0): ?>
          <tr><td colspan="7"><div class="empty-state" style="padding:var(--space-6)"><p>Belum ada denda.</p></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function autoJenis() {
  const sel = document.getElementById('sel-sewa');
  const opt = sel.options[sel.selectedIndex];
  document.getElementById('jenis-denda').value = opt.dataset.jenis || 'mobil';
}
</script>
<?php include '_layout_footer.php'; ?>

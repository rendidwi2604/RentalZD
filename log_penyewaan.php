<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') { header('Location: login.php'); exit; }
require_once 'koneksi.php';
require_once 'notif_helper.php';

$msg_success = '';

// Reset all logs
if (isset($_POST['action']) && $_POST['action'] === 'reset') {
    db_query($conn, "UPDATE mobil SET status='tersedia'");
    db_query($conn, "DELETE FROM log_penyewaan");
    $msg_success = "Semua log penyewaan berhasil direset.";
}

// Filter
$filter_user  = isset($_GET['id_user'])  ? intval($_GET['id_user'])  : 0;
$filter_mobil = isset($_GET['id_mobil']) ? intval($_GET['id_mobil']) : 0;

$where = [];
if ($filter_user)  $where[] = "lp.id_user='$filter_user'";
if ($filter_mobil) $where[] = "lp.id_mobil='$filter_mobil'";
$where_sql = $where ? "WHERE ".implode(" AND ", $where) : "";

$q = db_query($conn, "SELECT lp.*, u.username, u.nama_lengkap, m.nama_mobil, m.merk FROM log_penyewaan lp LEFT JOIN app_users u ON lp.id_user=u.id_user LEFT JOIN mobil m ON lp.id_mobil=m.id_mobil $where_sql ORDER BY lp.id_sewa DESC");
$total = db_num_rows($q);

$aktif    = db_num_rows(db_query($conn, "SELECT id_sewa FROM log_penyewaan WHERE status='aktif'"));
$selesai  = db_num_rows(db_query($conn, "SELECT id_sewa FROM log_penyewaan WHERE status='selesai'"));
$rev_row  = db_fetch_assoc(db_query($conn, "SELECT SUM(total_bayar) as rev FROM log_penyewaan WHERE status='selesai'"));
$revenue  = $rev_row['rev'] ?? 0;

$users_list = db_query($conn, "SELECT id_user, username, nama_lengkap FROM app_users WHERE role='customer' ORDER BY nama_lengkap");
$mobil_list = db_query($conn, "SELECT id_mobil, nama_mobil, merk FROM mobil ORDER BY nama_mobil");

$page_title = 'Log Penyewaan';
$active_menu = 'log';
include '_layout.php';
?>

<?php if ($msg_success): ?><div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg><?= htmlspecialchars($msg_success) ?></div><?php endif; ?>

<!-- Shortcut ke Konfirmasi Pesanan jika ada yang pending -->
<?php
$pending_baru = db_fetch_assoc(db_query($conn, "SELECT COUNT(*) as c FROM log_penyewaan WHERE admin_confirmed=0"))['c'] +
                db_fetch_assoc(db_query($conn, "SELECT COUNT(*) as c FROM log_sewa_motor WHERE admin_confirmed=0"))['c'];
$pending_bayar= db_fetch_assoc(db_query($conn, "SELECT COUNT(*) as c FROM log_penyewaan WHERE payment_status='menunggu_konfirmasi'"))['c'] +
                db_fetch_assoc(db_query($conn, "SELECT COUNT(*) as c FROM log_sewa_motor WHERE payment_status='menunggu_konfirmasi'"))['c'];
if ($pending_baru > 0 || $pending_bayar > 0):
?>
<div class="alert alert-warning" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--space-3)">
  <div style="display:flex;align-items:center;gap:var(--space-3)">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
    <span>
      <?php if ($pending_baru > 0): ?><strong><?= $pending_baru ?> pesanan baru</strong> menunggu konfirmasi.<?php endif; ?>
      <?php if ($pending_bayar > 0): ?> <strong><?= $pending_bayar ?> klaim pembayaran</strong> menunggu verifikasi.<?php endif; ?>
    </span>
  </div>
  <a href="konfirmasi_pesanan.php" class="btn btn-primary btn-sm">Proses Sekarang →</a>
</div>
<?php endif; ?>

<div class="stats-grid">
  <div class="stat-card"><div class="stat-label">Total Transaksi</div><div class="stat-value"><?= $total ?></div><div class="stat-sub">Semua waktu</div></div>
  <div class="stat-card"><div class="stat-label">Sedang Aktif</div><div class="stat-value" style="color:var(--color-warning)"><?= $aktif ?></div><div class="stat-sub">Belum dikembalikan</div></div>
  <div class="stat-card"><div class="stat-label">Selesai</div><div class="stat-value" style="color:var(--color-success)"><?= $selesai ?></div><div class="stat-sub">Sudah selesai</div></div>
  <div class="stat-card"><div class="stat-label">Total Pendapatan</div><div class="stat-value" style="font-size:var(--text-lg)">Rp <?= number_format($revenue,0,',','.') ?></div><div class="stat-sub">Dari sewa selesai</div></div>
</div>

<!-- FILTER -->
<div class="card" style="margin-bottom:var(--space-6)">
  <div class="card-header"><div class="card-title">Filter Riwayat</div></div>
  <form method="GET" action="">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Filter by Customer</label>
        <select name="id_user" class="form-select">
          <option value="">— Semua Customer —</option>
          <?php while ($u = db_fetch_assoc($users_list)): ?>
          <option value="<?= $u['id_user'] ?>" <?= $filter_user == $u['id_user'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nama_lengkap']) ?> (<?= $u['username'] ?>)</option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Filter by Mobil</label>
        <select name="id_mobil" class="form-select">
          <option value="">— Semua Mobil —</option>
          <?php while ($m = db_fetch_assoc($mobil_list)): ?>
          <option value="<?= $m['id_mobil'] ?>" <?= $filter_mobil == $m['id_mobil'] ? 'selected' : '' ?>><?= htmlspecialchars($m['nama_mobil']) ?> - <?= $m['merk'] ?></option>
          <?php endwhile; ?>
        </select>
      </div>
    </div>
    <div style="margin-top:var(--space-4);display:flex;gap:var(--space-3)">
      <button type="submit" class="btn btn-primary"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Filter</button>
      <a href="log_penyewaan.php" class="btn btn-ghost">Reset</a>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title">Data Log Penyewaan</div>
    <form method="POST" id="form-reset-log">
      <input type="hidden" name="action" value="reset">
      <button type="submit" class="btn btn-danger btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.75"/></svg>
        Reset Semua
      </button>
    </form>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>#</th><th>Customer</th><th>Kendaraan</th><th>Tgl Sewa</th><th>Tgl Kembali</th><th>Total Bayar</th><th>Metode</th><th>Status Bayar</th><th>Status</th><th>Invoice</th></tr>
      </thead>
      <tbody>
        <?php $no=1; while ($row = db_fetch_assoc($q)): ?>
        <tr>
          <td style="color:var(--color-text-faint)"><?= $no++ ?></td>
          <td>
            <div style="font-weight:700"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
            <div style="color:var(--color-text-faint);font-size:var(--text-xs)">@<?= htmlspecialchars($row['username']) ?></div>
          </td>
          <td>
            <div style="font-weight:700"><?= htmlspecialchars($row['nama_mobil']) ?></div>
            <div style="color:var(--color-text-faint);font-size:var(--text-xs)"><?= htmlspecialchars($row['merk']) ?></div>
          </td>
          <td><?= $row['tanggal_sewa'] ?></td>
          <td><?= $row['tanggal_kembali'] ?></td>
          <td style="font-weight:700;font-variant-numeric:tabular-nums">Rp <?= number_format($row['total_bayar'],0,',','.') ?></td>
          <td style="font-size:var(--text-xs)"><?= htmlspecialchars($row['payment_method'] ?? 'Transfer Bank') ?></td>
          <td>
            <?php
              $ps = $row['payment_status'] ?? 'pending';
              if ($ps === 'lunas') echo '<span class="badge badge-success">✓ Lunas</span>';
              elseif ($ps === 'menunggu_konfirmasi') echo '<span class="badge badge-warning">🕐 Klaim Bayar</span>';
              else echo '<span class="badge badge-neutral">⏳ Pending</span>';
            ?>
          </td>
          <td><span class="badge <?= $row['status'] === 'aktif' ? 'badge-warning' : 'badge-success' ?>"><?= $row['status'] === 'aktif' ? '● Aktif' : '✓ Selesai' ?></span></td>
          <?php if ($row['payment_status'] === 'lunas'): ?>
          <td><a href="invoice.php?id=<?= $row['id_sewa'] ?>&jenis=mobil" target="_blank" class="btn btn-ghost btn-sm"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Invoice</a></td>
          <?php else: ?><td>—</td><?php endif; ?>
        </tr>
        <?php endwhile; ?>
        <?php if ($total === 0): ?>
        <tr><td colspan="7"><div class="empty-state"><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg><h3>Tidak ada data</h3><p>Belum ada transaksi yang cocok dengan filter.</p></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.getElementById('form-reset-log') && document.getElementById('form-reset-log').addEventListener('submit', function(e){
  e.preventDefault();
  var form = this;
  showConfirm('Reset SEMUA log penyewaan? Tindakan ini tidak dapat dibatalkan!', function(){ form._confirmed=true; form.submit(); });
});
</script>
<?php include '_layout_footer.php'; ?>

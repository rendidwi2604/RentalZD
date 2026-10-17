<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'customer') { header('Location: login.php'); exit; }
require_once 'koneksi.php';
require_once 'notif_helper.php';
$id_user = (int)$_SESSION['id_user'];

function rupiah_r($n){ return 'Rp ' . number_format((float)$n, 0, ',', '.'); }

$msg_success = '';


// ── QUERY DATA (HANYA YANG SUDAH LUNAS) ──
$q_mobil = db_query($conn,
    "SELECT lp.*, m.nama_mobil, m.merk, m.tahun, m.harga_sewa, COALESCE(m.foto_mobil,'') AS foto
     FROM log_penyewaan lp LEFT JOIN mobil m ON lp.id_mobil=m.id_mobil
     WHERE lp.id_user='$id_user' AND lp.payment_status='lunas' ORDER BY lp.created_at DESC, lp.id_sewa DESC");

$q_motor = db_query($conn,
    "SELECT ls.*, mt.nama_motor, mt.merk, mt.tahun, mt.harga_sewa, COALESCE(mt.foto_motor,'') AS foto
     FROM log_sewa_motor ls LEFT JOIN motor mt ON ls.id_motor=mt.id_motor
     WHERE ls.id_user='$id_user' AND ls.payment_status='lunas' ORDER BY ls.created_at DESC, ls.id_sewa DESC");

$total_mobil = db_num_rows($q_mobil);
$total_motor = db_num_rows($q_motor);
$total_semua = $total_mobil + $total_motor;

$rev_m  = db_fetch_assoc(db_query($conn, "SELECT SUM(total_bayar) as t FROM log_penyewaan WHERE id_user='$id_user' AND payment_status='lunas'"))['t'] ?? 0;
$rev_mt = db_fetch_assoc(db_query($conn, "SELECT SUM(total_bayar) as t FROM log_sewa_motor WHERE id_user='$id_user' AND payment_status='lunas'"))['t'] ?? 0;
$total_bayar_lunas = (float)$rev_m + (float)$rev_mt;

// Notifikasi unread customer
$notif_unread = (int)(db_fetch_assoc(db_query($conn,
    "SELECT COUNT(*) as c FROM notifikasi_customer WHERE id_user='$id_user' AND sudah_baca=0"))['c'] ?? 0);

$page_title  = 'Riwayat Sewa Saya';
$active_menu = 'riwayat';
include '_layout.php';

// Helper untuk ambil denda per sewa
function get_denda($conn, $id_sewa, $jenis) {
    $q = db_query($conn, "SELECT * FROM denda_biaya WHERE id_sewa='$id_sewa' AND jenis='$jenis' ORDER BY created_at DESC");
    $rows = [];
    while ($r = db_fetch_assoc($q)) $rows[] = $r;
    return $rows;
}
?>

<?php if ($msg_success): ?>
<div class="alert alert-success">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
  <?= htmlspecialchars($msg_success) ?>
</div>
<?php endif; ?>

<!-- NOTIFIKASI BANNER -->
<?php if ($notif_unread > 0): ?>
<div class="alert alert-warning" style="margin-bottom:var(--space-5);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--space-3)">
  <div style="display:flex;align-items:center;gap:var(--space-3)">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
    <span>Anda memiliki <strong><?= $notif_unread ?> notifikasi baru</strong> — ada update pada pesanan atau tagihan Anda.</span>
  </div>
  <a href="notifikasi.php" class="btn btn-primary btn-sm">🔔 Lihat Notifikasi</a>
</div>
<?php endif; ?>

<!-- STATS -->
<div class="stats-grid" style="margin-bottom:var(--space-6)">
  <div class="stat-card"><div class="stat-label">Total Transaksi</div><div class="stat-value"><?= $total_semua ?></div><div class="stat-sub">Mobil & motor</div></div>
  <div class="stat-card"><div class="stat-label">Sewa Mobil</div><div class="stat-value"><?= $total_mobil ?></div><div class="stat-sub">Kali booking</div></div>
  <div class="stat-card"><div class="stat-label">Sewa Motor</div><div class="stat-value"><?= $total_motor ?></div><div class="stat-sub">Kali booking</div></div>
  <div class="stat-card"><div class="stat-label">Total Sudah Bayar</div><div class="stat-value" style="font-size:var(--text-lg)"><?= rupiah_r($total_bayar_lunas) ?></div><div class="stat-sub">Pembayaran lunas</div></div>
</div>


<!-- TAB -->
<div style="display:flex;gap:var(--space-2);margin-bottom:var(--space-5);border-bottom:2px solid var(--color-border)">
  <button class="rw-tab-btn active" onclick="switchTab('tab-mobil',this)">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/><rect x="9" y="11" width="14" height="10" rx="2"/></svg>
    Sewa Mobil (<?= $total_mobil ?>)
  </button>
  <button class="rw-tab-btn" onclick="switchTab('tab-motor',this)">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><path d="M15 6H4L2 12h14l1-6z"/></svg>
    Sewa Motor (<?= $total_motor ?>)
  </button>
</div>

<?php
// ── RENDER SEWA CARD ──
function render_rw_card($conn, $row, $jenis) {
    $ps        = $row['payment_status'];
    $confirmed = $row['admin_confirmed'] ?? 0;
    $id_sewa   = $row['id_sewa'];
    $days      = max(1, (int)((strtotime($row['tanggal_kembali']) - strtotime($row['tanggal_sewa'])) / 86400));
    $nama_k    = $jenis === 'mobil' ? $row['nama_mobil'] : $row['nama_motor'];
    $dendas    = get_denda($conn, $id_sewa, $jenis);
    $total_denda = array_sum(array_column($dendas, 'jumlah'));
    ?>
    <div class="rw-card <?= ($ps !== 'lunas' && !empty($dendas)) ? 'rw-card--denda' : '' ?>">
      <div class="rw-card__head">
        <div style="display:flex;align-items:center;gap:var(--space-3)">
          <?php if (!empty($row['foto'])): ?>
          <img src="<?= htmlspecialchars($row['foto']) ?>" width="56" height="38" style="object-fit:cover;border-radius:8px;border:1px solid var(--color-border);flex-shrink:0" alt="">
          <?php else: ?>
          <div style="width:56px;height:38px;background:var(--color-surface-2);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg width="24" height="16" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2">
              <?= $jenis === 'mobil' ? '<path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/><rect x="9" y="11" width="14" height="10" rx="2"/>' : '<circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><path d="M15 6H4L2 12h14l1-6z"/>' ?>
            </svg>
          </div>
          <?php endif; ?>
          <div>
            <div style="font-weight:800;font-size:var(--text-base)"><?= htmlspecialchars($nama_k) ?></div>
            <div style="font-size:var(--text-xs);color:var(--color-text-muted)"><?= htmlspecialchars($row['merk']) ?> · <?= (int)$row['tahun'] ?> · <?= $jenis === 'mobil' ? '🚗' : '🏍️' ?></div>
          </div>
        </div>
        <div style="display:flex;gap:var(--space-2);flex-wrap:wrap;align-items:center">
          <?php if (!$confirmed): ?>
          <span class="badge badge-warning">⏳ Menunggu Konfirmasi Admin</span>
          <?php else: ?>
          <span class="badge badge-success">✓ Dikonfirmasi</span>
          <?php endif; ?>
          <?php if ($ps === 'lunas'): ?>
          <span class="badge badge-success">✓ Lunas</span>
          <?php elseif ($ps === 'menunggu_konfirmasi'): ?>
          <span class="badge badge-warning">🕐 Verifikasi Bayar</span>
          <?php else: ?>
          <span class="badge" style="background:#fee2e2;color:#991b1b">💳 Belum Bayar</span>
          <?php endif; ?>
          <?php if (!empty($dendas) && $ps !== 'lunas'): ?>
          <span class="badge" style="background:#ffedd5;color:#c2410c">⚠️ Ada Denda</span>
          <?php endif; ?>
        </div>
      </div>

      <div class="rw-card__body">
        <div class="rw-detail-grid">
          <div class="rw-detail-item"><span>Tanggal Sewa</span><strong><?= date('d M Y', strtotime($row['tanggal_sewa'])) ?></strong></div>
          <div class="rw-detail-item"><span>Tanggal Kembali</span><strong><?= date('d M Y', strtotime($row['tanggal_kembali'])) ?></strong></div>
          <div class="rw-detail-item"><span>Durasi</span><strong><?= $days ?> hari</strong></div>
          <div class="rw-detail-item"><span>Metode Bayar</span><strong><?= htmlspecialchars($row['payment_method'] ?: 'Transfer Bank') ?></strong></div>
          <?php if ($row['addon_detail']): ?>
          <div class="rw-detail-item"><span>Add-on</span><strong><?= htmlspecialchars($row['addon_detail']) ?></strong></div>
          <?php endif; ?>
          <div class="rw-detail-item" style="grid-column:1/-1">
            <span>Total Tagihan</span>
            <strong style="font-size:var(--text-lg);color:var(--color-accent)"><?= rupiah_r($row['total_bayar']) ?></strong>
            <?php if ($total_denda > 0): ?>
            <small style="color:#c2410c">Termasuk denda <?= rupiah_r($total_denda) ?></small>
            <?php endif; ?>
          </div>
          <?php if (!empty($row['catatan_admin'])): ?>
          <div class="rw-detail-item" style="grid-column:1/-1">
            <span>📝 Catatan Admin</span>
            <div style="margin-top:4px;padding:var(--space-3);background:#fffbeb;border:1px solid #fde68a;border-radius:var(--radius-md);font-size:var(--text-sm)"><?= htmlspecialchars($row['catatan_admin']) ?></div>
          </div>
          <?php endif; ?>
        </div>

        <!-- DENDA SECTION -->
        <?php if (!empty($dendas)): ?>
        <div style="margin-bottom:var(--space-4);padding:var(--space-4);background:#fff7ed;border:1.5px solid #fed7aa;border-radius:var(--radius-lg)">
          <div style="font-weight:700;font-size:var(--text-sm);color:#c2410c;margin-bottom:var(--space-3);display:flex;align-items:center;gap:var(--space-2)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Rincian Denda / Biaya Tambahan
          </div>
          <?php foreach ($dendas as $d):
            $icons = ['kerusakan'=>'🔧','keterlambatan'=>'⏰','bbm_kurang'=>'⛽','kehilangan'=>'🔑','lainnya'=>'📋'];
          ?>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:var(--space-2) 0;border-bottom:1px solid #fed7aa;font-size:var(--text-sm);flex-wrap:wrap;gap:var(--space-2)">
            <div>
              <span><?= ($icons[$d['tipe_denda']] ?? '📋') ?> <strong><?= ucfirst(str_replace('_',' ',$d['tipe_denda'])) ?></strong></span>
              <?php if ($d['deskripsi']): ?>
              <div style="font-size:11px;color:#92400e;margin-top:2px"><?= htmlspecialchars($d['deskripsi']) ?></div>
              <?php endif; ?>
            </div>
            <strong style="color:#c2410c;white-space:nowrap">+ <?= rupiah_r($d['jumlah']) ?></strong>
          </div>
          <?php endforeach; ?>
          <div style="display:flex;justify-content:space-between;margin-top:var(--space-3);font-weight:800;font-size:var(--text-sm);color:#9a3412">
            <span>Total Denda</span>
            <span><?= rupiah_r($total_denda) ?></span>
          </div>
        </div>
        <?php endif; ?>

        <!-- AKSI BAYAR -->
        <?php if ($ps === 'pending'): ?>
        <div class="rw-action-bar">
          <div class="rw-pay-info">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Harap selesaikan pembayaran <strong><?= rupiah_r($row['total_bayar']) ?></strong><?= $total_denda > 0 ? ' (termasuk denda)' : '' ?>.
          </div>
          <div style="display:flex;gap:var(--space-3);flex-wrap:wrap">
            <a href="invoice.php?id=<?= $id_sewa ?>&jenis=<?= $jenis ?>" target="_blank" class="btn btn-primary">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
              📄 Lihat Invoice
            </a>
          </div>
        </div>

        <?php elseif ($ps === 'menunggu_konfirmasi'): ?>
        <div class="rw-action-bar" style="background:#fffbeb;border:1px solid #fde68a">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#92400e" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          <span style="color:#92400e;font-size:var(--text-sm)">Pembayaran sedang diverifikasi admin. Harap tunggu.</span>
        </div>

        <?php elseif ($ps === 'lunas'): ?>
        <div class="rw-action-bar" style="background:#f0faf4;border:1px solid rgba(45,106,79,.2);justify-content:space-between">
          <div style="display:flex;align-items:center;gap:var(--space-2)">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2d6a4f" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <span style="color:#2d6a4f;font-size:var(--text-sm);font-weight:600">Pembayaran lunas. Terima kasih!</span>
          </div>
          <a href="invoice.php?id=<?= $id_sewa ?>&jenis=<?= $jenis ?>" target="_blank" class="btn btn-ghost btn-sm" style="flex-shrink:0">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Invoice PDF
          </a>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php
}
?>

<!-- TAB MOBIL -->
<div id="tab-mobil" class="rw-tab-content">
  <?php if ($total_mobil === 0): ?>
  <div class="card"><div class="empty-state">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/><rect x="9" y="11" width="14" height="10" rx="2"/></svg>
    <h3>Belum ada sewa mobil</h3>
    <p><a href="pesan_mobil.php" style="color:var(--color-accent);font-weight:700">Booking mobil sekarang →</a></p>
  </div></div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:var(--space-4)">
    <?php while ($row = db_fetch_assoc($q_mobil)): render_rw_card($conn, $row, 'mobil'); endwhile; ?>
  </div>
  <?php endif; ?>
</div>

<!-- TAB MOTOR -->
<div id="tab-motor" class="rw-tab-content" style="display:none">
  <?php if ($total_motor === 0): ?>
  <div class="card"><div class="empty-state">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><path d="M15 6H4L2 12h14l1-6z"/></svg>
    <h3>Belum ada sewa motor</h3>
    <p><a href="pesan_motor.php" style="color:var(--color-accent);font-weight:700">Booking motor sekarang →</a></p>
  </div></div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:var(--space-4)">
    <?php while ($row = db_fetch_assoc($q_motor)): render_rw_card($conn, $row, 'motor'); endwhile; ?>
  </div>
  <?php endif; ?>
</div>

<style>
.rw-tab-btn{display:flex;align-items:center;gap:var(--space-2);padding:var(--space-3) var(--space-5);font-size:var(--text-sm);font-weight:600;color:var(--color-text-muted);border-bottom:2px solid transparent;margin-bottom:-2px;background:none;border-radius:0;cursor:pointer;transition:color var(--transition),border-color var(--transition)}
.rw-tab-btn:hover{color:var(--color-text)}
.rw-tab-btn.active{color:var(--color-primary);border-bottom-color:var(--color-primary)}
.rw-card{background:#fff;border:1px solid var(--color-border);border-radius:var(--radius-xl);box-shadow:var(--shadow-sm);overflow:hidden}
.rw-card--denda{border-color:#fed7aa}
.rw-card__head{display:flex;align-items:center;justify-content:space-between;gap:var(--space-4);padding:var(--space-4) var(--space-5);background:var(--color-surface);border-bottom:1px solid var(--color-border);flex-wrap:wrap}
.rw-card__body{padding:var(--space-5)}
.rw-detail-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:var(--space-4);background:var(--color-surface);border-radius:var(--radius-lg);padding:var(--space-4);margin-bottom:var(--space-4)}
.rw-detail-item{display:flex;flex-direction:column;gap:3px}
.rw-detail-item span:first-child{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-faint)}
.rw-detail-item strong{font-weight:700;font-size:var(--text-sm)}
.rw-action-bar{display:flex;align-items:center;justify-content:space-between;gap:var(--space-4);padding:var(--space-4);background:var(--color-surface);border-radius:var(--radius-lg);border:1px solid var(--color-border);flex-wrap:wrap}
.rw-pay-info{display:flex;align-items:flex-start;gap:var(--space-2);font-size:var(--text-sm);color:var(--color-text-muted);flex:1}
</style>

<script>
function switchTab(id,btn){
  document.querySelectorAll('.rw-tab-content').forEach(t=>t.style.display='none');
  document.querySelectorAll('.rw-tab-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById(id).style.display='block';
  btn.classList.add('active');
}
</script>

<?php include '_layout_footer.php'; ?>

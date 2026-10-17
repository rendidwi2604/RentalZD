<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') { header('Location: login.php'); exit; }
require_once 'koneksi.php';
require_once 'notif_helper.php';

$msg_success = $msg_error = '';

// ── KONFIRMASI PESANAN (admin approve booking) ──
if (isset($_POST['action']) && $_POST['action'] === 'konfirmasi_pesanan') {
    $id_sewa = intval($_POST['id_sewa']);
    $jenis   = $_POST['jenis'] ?? 'mobil';
    $catatan = db_real_escape_string($conn, trim($_POST['catatan_admin'] ?? ''));

    if ($jenis === 'mobil') {
        $row_s = db_fetch_assoc(db_query($conn,
            "SELECT lp.*, m.nama_mobil, u.id_user AS cust_id
             FROM log_penyewaan lp
             LEFT JOIN mobil m ON lp.id_mobil=m.id_mobil
             LEFT JOIN app_users u ON lp.id_user=u.id_user
             WHERE lp.id_sewa='$id_sewa'"));
        db_query($conn, "UPDATE log_penyewaan SET admin_confirmed=1, catatan_admin='$catatan' WHERE id_sewa='$id_sewa'");
    } else {
        $row_s = db_fetch_assoc(db_query($conn,
            "SELECT ls.*, mt.nama_motor AS nama_mobil, u.id_user AS cust_id
             FROM log_sewa_motor ls
             LEFT JOIN motor mt ON ls.id_motor=mt.id_motor
             LEFT JOIN app_users u ON ls.id_user=u.id_user
             WHERE ls.id_sewa='$id_sewa'"));
        db_query($conn, "UPDATE log_sewa_motor SET admin_confirmed=1, catatan_admin='$catatan' WHERE id_sewa='$id_sewa'");
    }

    db_query($conn, "UPDATE notifikasi_admin SET sudah_baca=1 WHERE id_sewa='$id_sewa' AND jenis='$jenis' AND tipe='pesanan_baru'");

    // Notifikasi ke customer
    if ($row_s) {
        $nama_k  = $row_s['nama_mobil'];
        $cat_txt = $catatan ? " Catatan admin: $catatan" : '';
        notif_customer(
            $conn, $row_s['cust_id'],
            'pesanan_dikonfirmasi',
            "✅ Pesanan #{$id_sewa} untuk {$nama_k} telah dikonfirmasi admin! Silakan lakukan pembayaran.{$cat_txt}",
            $id_sewa, $jenis,
            "payment_page.php?id={$id_sewa}&jenis={$jenis}"
        );
    }

    $msg_success = "Pesanan #$id_sewa berhasil dikonfirmasi.";
}

// ── KONFIRMASI PEMBAYARAN (admin tandai lunas manual) ──
if (isset($_POST['action']) && $_POST['action'] === 'konfirmasi_bayar') {
    $id_sewa = intval($_POST['id_sewa']);
    $jenis   = $_POST['jenis'] ?? 'mobil';

    if ($jenis === 'mobil') {
        $r = db_fetch_assoc(db_query($conn,
            "SELECT lp.id_mobil, lp.id_user, m.nama_mobil, u.nama_lengkap
             FROM log_penyewaan lp
             LEFT JOIN mobil m ON lp.id_mobil=m.id_mobil
             LEFT JOIN app_users u ON lp.id_user=u.id_user
             WHERE lp.id_sewa='$id_sewa'"));
        db_query($conn, "UPDATE log_penyewaan SET payment_status='lunas', status='selesai', admin_confirmed=1 WHERE id_sewa='$id_sewa'");
        if ($r) db_query($conn, "UPDATE mobil SET status='tersedia' WHERE id_mobil='{$r['id_mobil']}'");
    } else {
        $r = db_fetch_assoc(db_query($conn,
            "SELECT ls.id_motor, ls.id_user, mt.nama_motor AS nama_mobil, u.nama_lengkap
             FROM log_sewa_motor ls
             LEFT JOIN motor mt ON ls.id_motor=mt.id_motor
             LEFT JOIN app_users u ON ls.id_user=u.id_user
             WHERE ls.id_sewa='$id_sewa'"));
        db_query($conn, "UPDATE log_sewa_motor SET payment_status='lunas', status='selesai', admin_confirmed=1 WHERE id_sewa='$id_sewa'");
        if ($r) db_query($conn, "UPDATE motor SET status='tersedia' WHERE id_motor='{$r['id_motor']}'");
    }

    db_query($conn, "UPDATE notifikasi_admin SET sudah_baca=1 WHERE id_sewa='$id_sewa' AND jenis='$jenis' AND tipe='pembayaran'");

    // Notifikasi ke customer
    if ($r) {
        notif_customer(
            $conn, $r['id_user'],
            'lunas',
            "✅ Pembayaran untuk pesanan #{$id_sewa} ({$r['nama_mobil']}) telah dikonfirmasi LUNAS oleh admin. Terima kasih!",
            $id_sewa, $jenis,
            "invoice.php?id={$id_sewa}&jenis={$jenis}"
        );
    }

    $msg_success = "Pembayaran pesanan #$id_sewa berhasil dikonfirmasi sebagai LUNAS.";
}

// ── TOLAK / BATALKAN PESANAN ──
if (isset($_POST['action']) && $_POST['action'] === 'tolak_pesanan') {
    $id_sewa = intval($_POST['id_sewa']);
    $jenis   = $_POST['jenis'] ?? 'mobil';

    if ($jenis === 'mobil') {
        $r = db_fetch_assoc(db_query($conn,
            "SELECT lp.id_mobil, lp.id_user, m.nama_mobil
             FROM log_penyewaan lp LEFT JOIN mobil m ON lp.id_mobil=m.id_mobil
             WHERE lp.id_sewa='$id_sewa'"));
        db_query($conn, "DELETE FROM log_penyewaan WHERE id_sewa='$id_sewa'");
        if ($r) db_query($conn, "UPDATE mobil SET status='tersedia' WHERE id_mobil='{$r['id_mobil']}'");
    } else {
        $r = db_fetch_assoc(db_query($conn,
            "SELECT ls.id_motor, ls.id_user, mt.nama_motor AS nama_mobil
             FROM log_sewa_motor ls LEFT JOIN motor mt ON ls.id_motor=mt.id_motor
             WHERE ls.id_sewa='$id_sewa'"));
        db_query($conn, "DELETE FROM log_sewa_motor WHERE id_sewa='$id_sewa'");
        if ($r) db_query($conn, "UPDATE motor SET status='tersedia' WHERE id_motor='{$r['id_motor']}'");
    }

    db_query($conn, "DELETE FROM notifikasi_admin WHERE id_sewa='$id_sewa' AND jenis='$jenis'");

    // Notifikasi ke customer
    if ($r) {
        notif_customer(
            $conn, $r['id_user'],
            'pesanan_ditolak',
            "❌ Maaf, pesanan #{$id_sewa} untuk {$r['nama_mobil']} telah dibatalkan/ditolak oleh admin. Hubungi kami jika ada pertanyaan.",
            null, $jenis,
            'riwayat_sewa.php'
        );
    }

    $msg_success = "Pesanan #$id_sewa telah dibatalkan.";
}

// ── QUERY DATA ──
$q_mobil_baru = db_query($conn,
    "SELECT lp.*, u.nama_lengkap, u.username, m.nama_mobil, m.merk, m.tahun, m.harga_sewa
     FROM log_penyewaan lp
     LEFT JOIN app_users u ON lp.id_user=u.id_user
     LEFT JOIN mobil m ON lp.id_mobil=m.id_mobil
     WHERE lp.admin_confirmed=0
     ORDER BY lp.created_at DESC");

$q_motor_baru = db_query($conn,
    "SELECT ls.*, u.nama_lengkap, u.username, mt.nama_motor, mt.merk, mt.tahun, mt.harga_sewa
     FROM log_sewa_motor ls
     LEFT JOIN app_users u ON ls.id_user=u.id_user
     LEFT JOIN motor mt ON ls.id_motor=mt.id_motor
     WHERE ls.admin_confirmed=0
     ORDER BY ls.created_at DESC");


// Pending (belum ada aksi bayar)
$q_pending_bayar_mobil = db_query($conn,
    "SELECT lp.*, u.nama_lengkap, u.username, m.nama_mobil, m.merk
     FROM log_penyewaan lp
     LEFT JOIN app_users u ON lp.id_user=u.id_user
     LEFT JOIN mobil m ON lp.id_mobil=m.id_mobil
     WHERE lp.payment_status='pending'
     ORDER BY lp.created_at DESC");

$q_pending_bayar_motor = db_query($conn,
    "SELECT ls.*, u.nama_lengkap, u.username, mt.nama_motor, mt.merk
     FROM log_sewa_motor ls
     LEFT JOIN app_users u ON ls.id_user=u.id_user
     LEFT JOIN motor mt ON ls.id_motor=mt.id_motor
     WHERE ls.payment_status='pending'
     ORDER BY ls.created_at DESC");

$total_baru  = db_num_rows($q_mobil_baru) + db_num_rows($q_motor_baru);
$total_pend  = db_num_rows($q_pending_bayar_mobil) + db_num_rows($q_pending_bayar_motor);

$page_title  = 'Konfirmasi Pesanan';
$active_menu = 'konfirmasi';
include '_layout.php';

// Helper render card
function render_pesanan_card($row, $jenis_label, $icon_class) {
    $jenis = $row['_jenis'];
    $id_sewa = $row['id_sewa'];
    $ps = $row['payment_status'] ?? 'pending';
    ?>
    <div class="pesanan-card">
      <div class="pesanan-card__head">
        <div style="display:flex;align-items:center;gap:var(--space-3)">
          <div class="pesanan-icon <?= $icon_class ?>">
            <?php if ($jenis === 'mobil'): ?>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/><rect x="9" y="11" width="14" height="10" rx="2"/></svg>
            <?php else: ?>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><path d="M15 6H4L2 12h14l1-6z"/></svg>
            <?php endif; ?>
          </div>
          <div>
            <div style="font-weight:800;font-size:var(--text-base)"><?= htmlspecialchars($row['_nama']) ?></div>
            <div style="font-size:var(--text-xs);color:var(--color-text-faint)"><?= htmlspecialchars($row['merk']) ?> · <?= ucfirst($jenis) ?></div>
          </div>
        </div>
        <span class="badge badge-warning">⏳ <?= $jenis_label ?></span>
      </div>
      <div class="pesanan-card__body">
        <div class="pesanan-detail-grid">
          <div class="pesanan-detail-item"><span>Customer</span><strong><?= htmlspecialchars($row['nama_lengkap']) ?></strong><small>@<?= htmlspecialchars($row['username']) ?></small></div>
          <div class="pesanan-detail-item"><span>Periode Sewa</span><strong><?= date('d M Y', strtotime($row['tanggal_sewa'])) ?></strong><small>s/d <?= date('d M Y', strtotime($row['tanggal_kembali'])) ?></small></div>
          <div class="pesanan-detail-item"><span>Total Bayar</span><strong style="color:var(--color-accent)">Rp <?= number_format($row['total_bayar'],0,',','.') ?></strong><small>via <?= htmlspecialchars($row['payment_method'] ?? 'Transfer') ?></small></div>
          <div class="pesanan-detail-item"><span>Status Bayar</span>
            <?php if ($ps === 'lunas'): ?><span class="badge badge-success">✓ Lunas</span>
            <?php elseif ($ps === 'menunggu_konfirmasi'): ?><span class="badge badge-warning">🕐 Klaim Bayar</span>
            <?php else: ?><span class="badge badge-neutral">⏳ Pending</span><?php endif; ?>
          </div>
        </div>
        <div style="font-size:var(--text-xs);color:var(--color-text-faint);margin-top:var(--space-2)">
          Dipesan: <?= date('d M Y, H:i', strtotime($row['created_at'])) ?>
        </div>
        <div class="pesanan-card__actions">
          <form method="POST" style="display:contents">
            <input type="hidden" name="action" value="konfirmasi_pesanan">
            <input type="hidden" name="id_sewa" value="<?= $id_sewa ?>">
            <input type="hidden" name="jenis" value="<?= $jenis ?>">
            <div style="flex:1;display:flex;gap:var(--space-3);align-items:center;flex-wrap:wrap">
              <input type="text" name="catatan_admin" class="form-input" placeholder="Catatan ke customer (opsional)" style="flex:1;min-width:180px;padding:var(--space-2) var(--space-3)">
              <button type="submit" class="btn btn-primary" data-confirm="Konfirmasi pesanan ini?">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                Konfirmasi
              </button>
            </div>
          </form>
          <form method="POST">
            <input type="hidden" name="action" value="tolak_pesanan">
            <input type="hidden" name="id_sewa" value="<?= $id_sewa ?>">
            <input type="hidden" name="jenis" value="<?= $jenis ?>">
            <button type="submit" class="btn btn-danger" data-confirm="Batalkan pesanan ini?">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              Tolak
            </button>
          </form>
        </div>
      </div>
    </div>
    <?php
}
?>

<?php if ($msg_success): ?>
<div class="alert alert-success">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
  <?= htmlspecialchars($msg_success) ?>
</div>
<?php endif; ?>

<div class="stats-grid" style="margin-bottom:var(--space-6)">
  <div class="stat-card">
    <div class="stat-label">Pesanan Baru</div>
    <div class="stat-value" style="color:var(--color-warning)"><?= $total_baru ?></div>
    <div class="stat-sub">Menunggu konfirmasi</div>
  </div>
    <div class="stat-card">
    <div class="stat-label">Menunggu Bayar</div>
    <div class="stat-value"><?= $total_pend ?></div>
    <div class="stat-sub">Dikonfirmasi, belum bayar</div>
  </div>
</div>

<!-- TAB NAVIGATION -->
<div style="display:flex;gap:var(--space-2);margin-bottom:var(--space-5);border-bottom:2px solid var(--color-border);flex-wrap:wrap">
  <button class="tab-btn active" onclick="switchTab('tab-pesanan',this)">
    Pesanan Baru <?php if ($total_baru > 0): ?><span class="notif-badge"><?= $total_baru ?></span><?php endif; ?>
  </button>
    <button class="tab-btn" onclick="switchTab('tab-bayar',this)">
    Menunggu Bayar <?php if ($total_pend > 0): ?><span class="notif-badge"><?= $total_pend ?></span><?php endif; ?>
  </button>
</div>

<!-- TAB: PESANAN BARU -->
<div id="tab-pesanan" class="tab-content">
  <?php
  $semua_baru = [];
  db_data_seek($q_mobil_baru, 0);
  while ($r = db_fetch_assoc($q_mobil_baru)) { $r['_jenis'] = 'mobil'; $r['_nama'] = $r['nama_mobil']; $semua_baru[] = $r; }
  db_data_seek($q_motor_baru, 0);
  while ($r = db_fetch_assoc($q_motor_baru)) { $r['_jenis'] = 'motor'; $r['_nama'] = $r['nama_motor']; $semua_baru[] = $r; }
  usort($semua_baru, fn($a,$b) => strtotime($b['created_at']) - strtotime($a['created_at']));
  ?>
  <?php if (empty($semua_baru)): ?>
  <div class="card"><div class="empty-state">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    <h3>Tidak ada pesanan baru</h3><p>Semua pesanan sudah dikonfirmasi.</p>
  </div></div>
  <?php else: ?>
  <div style="display:grid;gap:var(--space-4)">
    <?php foreach ($semua_baru as $row): render_pesanan_card($row, 'Menunggu Konfirmasi', $row['_jenis']==='mobil'?'icon-mobil':'icon-motor'); endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- TAB: MENUNGGU BAYAR (sudah dikonfirmasi, belum ada aksi bayar) -->
<div id="tab-bayar" class="tab-content" style="display:none">
  <?php
  $semua_bayar = [];
  db_data_seek($q_pending_bayar_mobil, 0);
  while ($r = db_fetch_assoc($q_pending_bayar_mobil)) { $r['_jenis'] = 'mobil'; $r['_nama'] = $r['nama_mobil']; $semua_bayar[] = $r; }
  db_data_seek($q_pending_bayar_motor, 0);
  while ($r = db_fetch_assoc($q_pending_bayar_motor)) { $r['_jenis'] = 'motor'; $r['_nama'] = $r['nama_motor']; $semua_bayar[] = $r; }
  usort($semua_bayar, fn($a,$b) => strtotime($b['created_at']) - strtotime($a['created_at']));
  ?>
  <?php if (empty($semua_bayar)): ?>
  <div class="card"><div class="empty-state">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
    <h3>Tidak ada yang pending bayar</h3><p>Semua pembayaran sudah lunas.</p>
  </div></div>
  <?php else: ?>
  <div style="display:grid;gap:var(--space-4)">
    <?php foreach ($semua_bayar as $row): ?>
    <div class="pesanan-card">
      <div class="pesanan-card__head">
        <div style="display:flex;align-items:center;gap:var(--space-3)">
          <div class="pesanan-icon" style="background:#f0f9ff;color:#075985">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <div>
            <div style="font-weight:800;font-size:var(--text-base)"><?= htmlspecialchars($row['_nama']) ?></div>
            <div style="font-size:var(--text-xs);color:var(--color-text-faint)"><?= ucfirst($row['_jenis']) ?> · <?= htmlspecialchars($row['merk']) ?></div>
          </div>
        </div>
        <span class="badge" style="background:#dbeafe;color:#1d4ed8">💳 Belum Ada Aksi Bayar</span>
      </div>
      <div class="pesanan-card__body">
        <div class="pesanan-detail-grid">
          <div class="pesanan-detail-item"><span>Customer</span><strong><?= htmlspecialchars($row['nama_lengkap']) ?></strong><small>@<?= htmlspecialchars($row['username']) ?></small></div>
          <div class="pesanan-detail-item"><span>Periode</span><strong><?= date('d M Y', strtotime($row['tanggal_sewa'])) ?></strong><small>s/d <?= date('d M Y', strtotime($row['tanggal_kembali'])) ?></small></div>
          <div class="pesanan-detail-item"><span>Total Tagihan</span><strong style="color:var(--color-accent)">Rp <?= number_format($row['total_bayar'],0,',','.') ?></strong><small>via <?= htmlspecialchars($row['payment_method'] ?? 'Transfer') ?></small></div>
        </div>
        <div style="font-size:var(--text-xs);color:var(--color-text-faint);margin-top:var(--space-2)">
          Dipesan: <?= date('d M Y, H:i', strtotime($row['created_at'])) ?>
        </div>
        <div class="pesanan-card__actions">
          <a href="invoice.php?id=<?= $row['id_sewa'] ?>&jenis=<?= $row['_jenis'] ?>" target="_blank" class="btn btn-ghost btn-sm">📄 Invoice</a>
          <form method="POST">
            <input type="hidden" name="action" value="tolak_pesanan">
            <input type="hidden" name="id_sewa" value="<?= $row['id_sewa'] ?>">
            <input type="hidden" name="jenis" value="<?= $row['_jenis'] ?>">
            <button type="submit" class="btn btn-danger btn-sm" data-confirm="Batalkan pesanan?">Batalkan</button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php
?>

<style>
.tab-btn{padding:var(--space-3) var(--space-5);font-size:var(--text-sm);font-weight:600;color:var(--color-text-muted);border-bottom:2px solid transparent;margin-bottom:-2px;background:none;border-radius:0;cursor:pointer;transition:color var(--transition),border-color var(--transition);display:flex;align-items:center;gap:var(--space-2)}
.tab-btn:hover{color:var(--color-text)}
.tab-btn.active{color:var(--color-primary);border-bottom-color:var(--color-primary)}
.notif-badge{display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:0 6px;background:var(--color-accent);color:#fff;border-radius:999px;font-size:11px;font-weight:700}
.pesanan-card{background:#fff;border:1px solid var(--color-border);border-radius:var(--radius-xl);box-shadow:var(--shadow-sm);overflow:hidden}
.pesanan-card__head{display:flex;align-items:center;justify-content:space-between;gap:var(--space-4);padding:var(--space-4) var(--space-5);background:var(--color-surface);border-bottom:1px solid var(--color-border);flex-wrap:wrap}
.pesanan-icon{width:40px;height:40px;border-radius:var(--radius-lg);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.icon-mobil{background:#fff3e0;color:#e65100}
.icon-motor{background:#e8f5e9;color:#2e7d32}
.icon-bayar{background:#e3f2fd;color:#1565c0}
.pesanan-card__body{padding:var(--space-5)}
.pesanan-detail-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:var(--space-4);margin-bottom:var(--space-4)}
.pesanan-detail-item{display:flex;flex-direction:column;gap:2px}
.pesanan-detail-item span:first-child{font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--color-text-faint);font-weight:600}
.pesanan-detail-item strong{font-size:var(--text-sm);font-weight:700}
.pesanan-detail-item small{font-size:var(--text-xs);color:var(--color-text-muted)}
.pesanan-card__actions{display:flex;gap:var(--space-3);align-items:center;padding-top:var(--space-4);border-top:1px solid var(--color-divider);flex-wrap:wrap}
</style>

<script>
function switchTab(id,btn){
  document.querySelectorAll('.tab-content').forEach(t=>t.style.display='none');
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById(id).style.display='block';
  btn.classList.add('active');
}
</script>

<?php include '_layout_footer.php'; ?>

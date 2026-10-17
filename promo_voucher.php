<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') { header('Location: login.php'); exit; }
require_once 'koneksi.php';

$msg_success = $msg_error = '';

// Tambah promo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_promo'])) {
    $kode      = strtoupper(trim($_POST['kode'] ?? ''));
    $desk      = db_real_escape_string($conn, trim($_POST['deskripsi'] ?? ''));
    $tipe      = $_POST['tipe_diskon'] ?? 'persen';
    $nilai     = floatval($_POST['nilai_diskon'] ?? 0);
    $min_trx   = floatval($_POST['min_transaksi'] ?? 0);
    $max_use   = intval($_POST['max_penggunaan'] ?? 100);
    $dari      = $_POST['berlaku_dari'] ?? '';
    $sampai    = $_POST['berlaku_sampai'] ?? '';
    $status    = $_POST['status'] ?? 'aktif';
    if ($kode && $nilai > 0) {
        $res = db_query($conn, "INSERT INTO promo_voucher (kode,deskripsi,tipe_diskon,nilai_diskon,min_transaksi,max_penggunaan,berlaku_dari,berlaku_sampai,status)
            VALUES ('$kode','$desk','$tipe','$nilai','$min_trx','$max_use','$dari','$sampai','$status')");
        $res ? $msg_success = "Promo $kode berhasil dibuat." : $msg_error = "Gagal. Kode sudah digunakan.";
    } else { $msg_error = "Harap isi kode dan nilai diskon."; }
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $curr = db_fetch_assoc(db_query($conn, "SELECT status FROM promo_voucher WHERE id_promo='$id'"))['status'] ?? 'aktif';
    $new = $curr === 'aktif' ? 'nonaktif' : 'aktif';
    db_query($conn, "UPDATE promo_voucher SET status='$new' WHERE id_promo='$id'");
    header('Location: promo_voucher.php'); exit;
}

// Hapus promo
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    db_query($conn, "DELETE FROM promo_voucher WHERE id_promo='$id'");
    $msg_success = "Promo berhasil dihapus.";
}

$promos  = db_query($conn, "SELECT * FROM promo_voucher ORDER BY created_at DESC");
$total_promo  = db_num_rows($promos);
$aktif_count  = db_fetch_assoc(db_query($conn, "SELECT COUNT(*) as c FROM promo_voucher WHERE status='aktif'"))['c'];
$total_pakai  = db_fetch_assoc(db_query($conn, "SELECT SUM(sudah_dipakai) as t FROM promo_voucher"))['t'] ?? 0;

$page_title  = 'Promo & Voucher';
$active_menu = 'promo';
include '_layout.php';
?>

<?php if ($msg_success): ?><div class="alert alert-success"><?= htmlspecialchars($msg_success) ?></div><?php endif; ?>
<?php if ($msg_error): ?><div class="alert alert-error"><?= htmlspecialchars($msg_error) ?></div><?php endif; ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-6);flex-wrap:wrap;gap:var(--space-3)">
  <div>
    <h2 style="font-size:var(--text-xl);font-weight:800;letter-spacing:-.02em">Promo & Voucher</h2>
    <p style="color:var(--color-text-muted);font-size:var(--text-sm)">Kelola kode promo dengan batas penggunaan, min. transaksi & expired date</p>
  </div>
</div>

<div class="stats-grid" style="margin-bottom:var(--space-6)">
  <div class="stat-card"><div class="stat-label">Total Promo</div><div class="stat-value"><?= $total_promo ?></div><div class="stat-sub">Dibuat</div></div>
  <div class="stat-card"><div class="stat-label">Aktif</div><div class="stat-value" style="color:var(--color-success)"><?= $aktif_count ?></div><div class="stat-sub">Bisa digunakan</div></div>
  <div class="stat-card"><div class="stat-label">Total Pemakaian</div><div class="stat-value" style="color:var(--color-accent)"><?= $total_pakai ?></div><div class="stat-sub">Kali dipakai</div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1.8fr;gap:var(--space-6)">
  <!-- Form -->
  <div class="card" style="align-self:start">
    <div class="card-header"><div class="card-title">Buat Promo Baru</div></div>
    <form method="POST">
      <input type="hidden" name="tambah_promo" value="1">
      <div class="form-group">
        <label class="form-label">Kode Voucher *</label>
        <input type="text" name="kode" class="form-input" placeholder="Contoh: HEMAT20" required style="text-transform:uppercase" oninput="this.value=this.value.toUpperCase()">
      </div>
      <div class="form-group">
        <label class="form-label">Deskripsi</label>
        <input type="text" name="deskripsi" class="form-input" placeholder="Diskon 20% hari kemerdekaan">
      </div>
      <div class="form-group">
        <label class="form-label">Tipe Diskon</label>
        <select name="tipe_diskon" class="form-select">
          <option value="persen">% Persen</option>
          <option value="nominal">Rp Nominal</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Nilai Diskon *</label>
        <input type="number" name="nilai_diskon" class="form-input" placeholder="Persen: 10 / Nominal: 50000" min="1" required>
      </div>
      <div class="form-group">
        <label class="form-label">Min. Transaksi (Rp)</label>
        <input type="number" name="min_transaksi" class="form-input" placeholder="0 = tidak ada minimum" min="0">
      </div>
      <div class="form-group">
        <label class="form-label">Maks. Penggunaan</label>
        <input type="number" name="max_penggunaan" class="form-input" value="100" min="1">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Berlaku Dari</label>
          <input type="date" name="berlaku_dari" class="form-input">
        </div>
        <div class="form-group">
          <label class="form-label">Berlaku Sampai</label>
          <input type="date" name="berlaku_sampai" class="form-input">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="aktif">Aktif</option>
          <option value="nonaktif">Nonaktif</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%">Buat Promo</button>
    </form>
  </div>

  <!-- List promo -->
  <div class="card">
    <div class="card-header"><div class="card-title">Daftar Promo</div></div>
    <div style="display:flex;flex-direction:column;gap:var(--space-3)">
      <?php db_data_seek($promos, 0); while ($p = db_fetch_assoc($promos)): 
        $today     = date('Y-m-d');
        $is_expired= $p['berlaku_sampai'] && $p['berlaku_sampai'] < $today;
        $is_full   = $p['sudah_dipakai'] >= $p['max_penggunaan'];
        $effective = $p['status'] === 'aktif' && !$is_expired && !$is_full;
      ?>
      <div style="padding:var(--space-4);background:var(--color-surface);border-radius:var(--radius-lg);border:2px solid <?= $effective ? '#22c55e33' : 'var(--color-border)' ?>;opacity:<?= $effective ? '1' : '0.7' ?>">
        <div style="display:flex;align-items:start;justify-content:space-between;gap:var(--space-3);flex-wrap:wrap">
          <div>
            <div style="display:flex;align-items:center;gap:var(--space-2);margin-bottom:4px">
              <span style="font-family:monospace;font-size:var(--text-base);font-weight:800;background:#111;color:#fff;padding:4px 12px;border-radius:var(--radius-full)"><?= htmlspecialchars($p['kode']) ?></span>
              <?php if ($is_expired): ?><span class="badge badge-neutral">Expired</span>
              <?php elseif ($is_full): ?><span class="badge badge-neutral">Limit Habis</span>
              <?php elseif ($p['status'] === 'nonaktif'): ?><span class="badge badge-neutral">Nonaktif</span>
              <?php else: ?><span class="badge badge-success">● Aktif</span><?php endif; ?>
            </div>
            <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:6px"><?= htmlspecialchars($p['deskripsi'] ?? '') ?></p>
            <div style="display:flex;flex-wrap:wrap;gap:var(--space-3);font-size:var(--text-xs)">
              <span>💰 <?= $p['tipe_diskon'] === 'persen' ? $p['nilai_diskon'].'%' : 'Rp '.number_format($p['nilai_diskon'],0,',','.') ?></span>
              <?php if ($p['min_transaksi'] > 0): ?><span>Min. Rp <?= number_format($p['min_transaksi'],0,',','.') ?></span><?php endif; ?>
              <span>📊 <?= $p['sudah_dipakai'] ?>/<?= $p['max_penggunaan'] ?> pakai</span>
              <?php if ($p['berlaku_dari'] && $p['berlaku_sampai']): ?>
              <span>📅 <?= $p['berlaku_dari'] ?> — <?= $p['berlaku_sampai'] ?></span>
              <?php endif; ?>
            </div>
          </div>
          <div style="display:flex;gap:var(--space-2)">
            <a href="?toggle=<?= $p['id_promo'] ?>" class="btn btn-ghost btn-sm"><?= $p['status']==='aktif'?'Nonaktifkan':'Aktifkan' ?></a>
            <a href="?hapus=<?= $p['id_promo'] ?>" class="btn btn-danger btn-sm btn-icon" data-confirm="Hapus promo ini?">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
            </a>
          </div>
        </div>
      </div>
      <?php endwhile; ?>
      <?php if ($total_promo === 0): ?>
      <div class="empty-state" style="padding:var(--space-8)">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 12V22H4V12"/><path d="M22 7H2v5h20V7z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
        <p>Belum ada promo.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include '_layout_footer.php'; ?>

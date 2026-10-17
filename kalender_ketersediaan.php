<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user'])) { header('Location: login.php'); exit; }
require_once 'koneksi.php';

$jenis         = $_GET['jenis']  ?? 'mobil';
$id_kendaraan  = intval($_GET['id'] ?? 0);
$bulan         = intval($_GET['bulan'] ?? date('n'));
$tahun         = intval($_GET['tahun'] ?? date('Y'));

// Kendaraan data
if ($jenis === 'mobil') {
    $kendaraan = db_fetch_assoc(db_query($conn, "SELECT id_mobil AS id, nama_mobil AS nama, merk, tahun AS thn, harga_sewa, foto_mobil AS foto FROM mobil WHERE id_mobil='$id_kendaraan'"));
    $tabel     = 'log_penyewaan';
    $id_col    = 'id_mobil';
} else {
    $kendaraan = db_fetch_assoc(db_query($conn, "SELECT id_motor AS id, nama_motor AS nama, merk, tahun AS thn, harga_sewa, foto_motor AS foto FROM motor WHERE id_motor='$id_kendaraan'"));
    $tabel     = 'log_sewa_motor';
    $id_col    = 'id_motor';
}

// Semua kendaraan untuk dropdown
$list_mobil  = db_query($conn, "SELECT id_mobil AS id, nama_mobil AS nama, 'mobil' AS jenis FROM mobil ORDER BY nama_mobil");
$list_motor  = db_query($conn, "SELECT id_motor AS id, nama_motor AS nama, 'motor' AS jenis FROM motor ORDER BY nama_motor");

// Booked dates for this month
$first_day = "$tahun-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-01";
$last_day  = date('Y-m-t', strtotime($first_day));

$bookings = [];
if ($id_kendaraan) {
    $q = db_query($conn, "
        SELECT tanggal_sewa, tanggal_kembali, payment_status, status
        FROM $tabel
        WHERE $id_col = '$id_kendaraan'
          AND NOT (tanggal_kembali < '$first_day' OR tanggal_sewa > '$last_day')
    ");
    while ($r = db_fetch_assoc($q)) $bookings[] = $r;
}

// Build booked days map
$booked_map = [];
foreach ($bookings as $b) {
    $start = new DateTime($b['tanggal_sewa']);
    $end   = new DateTime($b['tanggal_kembali']);
    $status_cls = ($b['payment_status'] === 'lunas') ? 'booked-confirmed' : 'booked-pending';
    for ($d = clone $start; $d <= $end; $d->modify('+1 day')) {
        $key = $d->format('Y-m-d');
        $booked_map[$key] = $status_cls;
    }
}

// Navigate
$prev_bulan = $bulan - 1; $prev_tahun = $tahun;
if ($prev_bulan < 1) { $prev_bulan = 12; $prev_tahun--; }
$next_bulan = $bulan + 1; $next_tahun = $tahun;
if ($next_bulan > 12) { $next_bulan = 1; $next_tahun++; }

$nama_bulan = ['', 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

$page_title  = 'Kalender Ketersediaan';
$active_menu = $_SESSION['role'] === 'admin' ? 'laporan' : 'pesan';
include '_layout.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-6);flex-wrap:wrap;gap:var(--space-4)">
  <div>
    <h2 style="font-size:var(--text-xl);font-weight:800;letter-spacing:-.02em">Kalender Ketersediaan</h2>
    <p style="color:var(--color-text-muted);font-size:var(--text-sm)">Visual tanggal terbooked & tersedia per kendaraan</p>
  </div>
  <?php if ($_SESSION['role'] === 'customer'): ?>
  <a href="pesan_mobil.php" class="btn btn-ghost btn-sm">← Kembali ke Sewa Mobil</a>
  <?php endif; ?>
</div>

<!-- Filter Kendaraan -->
<div class="card" style="margin-bottom:var(--space-6)">
  <form method="GET" action="" style="display:flex;gap:var(--space-4);flex-wrap:wrap;align-items:flex-end">
    <div class="form-group" style="flex:1;min-width:200px">
      <label class="form-label">Pilih Kendaraan</label>
      <select name="id" class="form-select" onchange="this.form.submit()">
        <option value="">— Pilih kendaraan —</option>
        <optgroup label="🚗 Mobil">
          <?php while ($m = db_fetch_assoc($list_mobil)): ?>
          <option value="<?= $m['id'] ?>" data-jenis="mobil" <?= ($jenis==='mobil' && $id_kendaraan==$m['id']) ? 'selected' : '' ?>><?= htmlspecialchars($m['nama']) ?></option>
          <?php endwhile; ?>
        </optgroup>
        <optgroup label="🏍️ Motor">
          <?php while ($m = db_fetch_assoc($list_motor)): ?>
          <option value="<?= $m['id'] ?>" data-jenis="motor" <?= ($jenis==='motor' && $id_kendaraan==$m['id']) ? 'selected' : '' ?>><?= htmlspecialchars($m['nama']) ?></option>
          <?php endwhile; ?>
        </optgroup>
      </select>
    </div>
    <input type="hidden" name="jenis" id="jenis-input" value="<?= htmlspecialchars($jenis) ?>">
    <input type="hidden" name="bulan" value="<?= $bulan ?>">
    <input type="hidden" name="tahun" value="<?= $tahun ?>">
  </form>
</div>

<?php if ($id_kendaraan && $kendaraan): ?>
<!-- Kendaraan Info -->
<div class="card" style="margin-bottom:var(--space-6);display:flex;align-items:center;gap:var(--space-5);flex-wrap:wrap">
  <?php if (!empty($kendaraan['foto'])): ?>
  <img src="<?= htmlspecialchars($kendaraan['foto']) ?>" alt="<?= htmlspecialchars($kendaraan['nama']) ?>" style="width:120px;height:80px;object-fit:cover;border-radius:var(--radius-lg)">
  <?php endif; ?>
  <div>
    <h3 style="font-size:var(--text-lg);font-weight:800"><?= htmlspecialchars($kendaraan['nama']) ?></h3>
    <p style="color:var(--color-text-muted);font-size:var(--text-sm)"><?= htmlspecialchars($kendaraan['merk']) ?> · Tahun <?= $kendaraan['thn'] ?> · Rp <?= number_format($kendaraan['harga_sewa'],0,',','.') ?>/hari</p>
  </div>
  <div style="margin-left:auto;display:flex;gap:var(--space-3);flex-wrap:wrap;font-size:var(--text-sm)">
    <span style="display:flex;align-items:center;gap:6px"><span style="width:14px;height:14px;border-radius:3px;background:#22c55e;display:inline-block"></span>Tersedia</span>
    <span style="display:flex;align-items:center;gap:6px"><span style="width:14px;height:14px;border-radius:3px;background:#ef4444;display:inline-block"></span>Booked (Lunas)</span>
    <span style="display:flex;align-items:center;gap:6px"><span style="width:14px;height:14px;border-radius:3px;background:#f59e0b;display:inline-block"></span>Pending</span>
  </div>
</div>

<!-- Calendar Navigation -->
<div class="card">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-5);flex-wrap:wrap;gap:var(--space-3)">
    <a href="?id=<?= $id_kendaraan ?>&jenis=<?= $jenis ?>&bulan=<?= $prev_bulan ?>&tahun=<?= $prev_tahun ?>" class="btn btn-ghost btn-sm">← Prev</a>
    <h3 style="font-size:var(--text-lg);font-weight:800"><?= $nama_bulan[$bulan] ?> <?= $tahun ?></h3>
    <a href="?id=<?= $id_kendaraan ?>&jenis=<?= $jenis ?>&bulan=<?= $next_bulan ?>&tahun=<?= $next_tahun ?>" class="btn btn-ghost btn-sm">Next →</a>
  </div>

  <div class="cal-grid">
    <?php foreach (['Min','Sen','Sel','Rab','Kam','Jum','Sab'] as $h): ?>
    <div class="cal-header"><?= $h ?></div>
    <?php endforeach; ?>

    <?php
    $first = new DateTime("$tahun-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-01");
    $dow   = (int)$first->format('w'); // 0=sun
    $days_in_month = (int)date('t', strtotime($first_day));
    $today = date('Y-m-d');

    for ($i = 0; $i < $dow; $i++) echo '<div class="cal-cell empty"></div>';

    for ($d = 1; $d <= $days_in_month; $d++) {
        $date_str  = "$tahun-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-" . str_pad($d, 2, '0', STR_PAD_LEFT);
        $cls_extra = '';
        $tooltip   = '';
        if (isset($booked_map[$date_str])) {
            $cls_extra = $booked_map[$date_str] === 'booked-confirmed' ? 'is-booked' : 'is-pending';
        } elseif ($date_str < $today) {
            $cls_extra = 'is-past';
        } else {
            $cls_extra = 'is-free';
        }
        if ($date_str === $today) $cls_extra .= ' is-today';
        echo "<div class=\"cal-cell $cls_extra\"><span>$d</span></div>";
    }
    ?>
  </div>

  <!-- Upcoming Bookings List -->
  <?php if (!empty($bookings)): ?>
  <div style="margin-top:var(--space-6)">
    <h4 style="font-weight:700;margin-bottom:var(--space-4)">Daftar Booking Bulan Ini</h4>
    <div style="display:flex;flex-direction:column;gap:var(--space-3)">
      <?php foreach ($bookings as $b): ?>
      <div style="display:flex;align-items:center;justify-content:space-between;padding:var(--space-3) var(--space-4);background:var(--color-surface);border-radius:var(--radius-lg);font-size:var(--text-sm);flex-wrap:wrap;gap:var(--space-3)">
        <span><?= $b['tanggal_sewa'] ?> → <?= $b['tanggal_kembali'] ?></span>
        <span class="badge <?= $b['payment_status'] === 'lunas' ? 'badge-success' : 'badge-warning' ?>"><?= ucfirst($b['payment_status']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php else: ?>
<div class="empty-state">
  <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="16" y1="2" x2="16" y2="6"/></svg>
  <h3>Pilih Kendaraan</h3>
  <p>Pilih kendaraan dari dropdown di atas untuk melihat kalender ketersediaannya.</p>
</div>
<?php endif; ?>

<style>
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:4px}
.cal-header{text-align:center;font-size:11px;font-weight:700;color:var(--color-text-faint);text-transform:uppercase;padding:8px 0}
.cal-cell{border-radius:8px;aspect-ratio:1;display:flex;align-items:center;justify-content:center;font-size:var(--text-sm);font-weight:500;cursor:default;position:relative;transition:transform .15s}
.cal-cell span{position:relative;z-index:1}
.cal-cell.empty{background:transparent}
.cal-cell.is-free{background:#f0fdf4;color:#16a34a}
.cal-cell.is-booked{background:#fef2f2;color:#dc2626;font-weight:700}
.cal-cell.is-pending{background:#fffbeb;color:#d97706;font-weight:700}
.cal-cell.is-past{background:#f9fafb;color:#d1d5db}
.cal-cell.is-today::after{content:'';position:absolute;bottom:4px;left:50%;transform:translateX(-50%);width:5px;height:5px;background:var(--color-accent);border-radius:50%}
.cal-cell.is-free:hover{transform:scale(1.1)}
@media(max-width:480px){.cal-cell{font-size:11px;border-radius:4px}}
</style>

<script>
// Detect jenis from option data attribute
document.querySelector('select[name=id]').addEventListener('change', function(){
  const opt = this.options[this.selectedIndex];
  const j   = opt.getAttribute('data-jenis') || 'mobil';
  document.getElementById('jenis-input').value = j;
});
</script>

<?php include '_layout_footer.php'; ?>

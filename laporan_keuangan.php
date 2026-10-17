<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') { header('Location: login.php'); exit; }
require_once 'koneksi.php';
if (file_exists('notif_helper.php')) require_once 'notif_helper.php';

// ── Filter Periode ──
$filter_tipe  = $_GET['tipe'] ?? 'bulanan';   // harian / bulanan / tahunan
$filter_tahun = intval($_GET['tahun'] ?? date('Y'));
$filter_bulan = intval($_GET['bulan'] ?? date('n'));

// Build WHERE clause
function buildWhere($tabel, $col_tgl, $tipe, $tahun, $bulan) {
    if ($tipe === 'harian') return "EXTRACT(YEAR FROM $col_tgl)='$tahun' AND EXTRACT(MONTH FROM $col_tgl)='$bulan'";
    if ($tipe === 'bulanan') return "EXTRACT(YEAR FROM $col_tgl)='$tahun'";
    return "1=1"; // tahunan = semua
}
$where_mobil = buildWhere('log_penyewaan','tanggal_sewa',$filter_tipe,$filter_tahun,$filter_bulan);
$where_motor = buildWhere('log_sewa_motor','tanggal_sewa',$filter_tipe,$filter_tahun,$filter_bulan);

// ── KPI STATS ──
$rev_mobil  = db_fetch_assoc(db_query($conn, "SELECT COALESCE(SUM(total_bayar),0) as r FROM log_penyewaan WHERE payment_status='lunas' AND $where_mobil"))['r'];
$rev_motor  = db_fetch_assoc(db_query($conn, "SELECT COALESCE(SUM(total_bayar),0) as r FROM log_sewa_motor WHERE payment_status='lunas' AND $where_motor"))['r'];
$total_rev  = (float)$rev_mobil + (float)$rev_motor;

$trx_mobil  = db_fetch_assoc(db_query($conn, "SELECT COUNT(*) as c FROM log_penyewaan WHERE $where_mobil"))['c'];
$trx_motor  = db_fetch_assoc(db_query($conn, "SELECT COUNT(*) as c FROM log_sewa_motor WHERE $where_motor"))['c'];
$total_trx  = $trx_mobil + $trx_motor;

$avg_trx    = $total_trx > 0 ? $total_rev / $total_trx : 0;

$total_denda = db_fetch_assoc(db_query($conn, "SELECT COALESCE(SUM(jumlah),0) as t FROM denda_biaya"))['t'] ?? 0;

// ── TREND DATA ──
if ($filter_tipe === 'harian') {
    // Harian: per hari dalam bulan
    $trend_q_m = db_query($conn, "SELECT EXTRACT(DAY FROM tanggal_sewa) AS label, COUNT(*) AS jml, COALESCE(SUM(total_bayar),0) AS pend
        FROM log_penyewaan WHERE $where_mobil GROUP BY EXTRACT(DAY FROM tanggal_sewa) ORDER BY EXTRACT(DAY FROM tanggal_sewa)");
    $trend_q_mt= db_query($conn, "SELECT EXTRACT(DAY FROM tanggal_sewa) AS label, COUNT(*) AS jml, COALESCE(SUM(total_bayar),0) AS pend
        FROM log_sewa_motor WHERE $where_motor GROUP BY EXTRACT(DAY FROM tanggal_sewa) ORDER BY EXTRACT(DAY FROM tanggal_sewa)");
    $x_label = 'Hari';
} elseif ($filter_tipe === 'bulanan') {
    // Bulanan: per bulan dalam tahun
    $nama_bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
    $trend_q_m  = db_query($conn, "SELECT EXTRACT(MONTH FROM tanggal_sewa) AS label, COUNT(*) AS jml, COALESCE(SUM(total_bayar),0) AS pend
        FROM log_penyewaan WHERE $where_mobil GROUP BY EXTRACT(MONTH FROM tanggal_sewa) ORDER BY EXTRACT(MONTH FROM tanggal_sewa)");
    $trend_q_mt = db_query($conn, "SELECT EXTRACT(MONTH FROM tanggal_sewa) AS label, COUNT(*) AS jml, COALESCE(SUM(total_bayar),0) AS pend
        FROM log_sewa_motor WHERE $where_motor GROUP BY EXTRACT(MONTH FROM tanggal_sewa) ORDER BY EXTRACT(MONTH FROM tanggal_sewa)");
    $x_label = 'Bulan';
} else {
    // Tahunan: per tahun
    $trend_q_m  = db_query($conn, "SELECT EXTRACT(YEAR FROM tanggal_sewa) AS label, COUNT(*) AS jml, COALESCE(SUM(total_bayar),0) AS pend
        FROM log_penyewaan GROUP BY EXTRACT(YEAR FROM tanggal_sewa) ORDER BY EXTRACT(YEAR FROM tanggal_sewa)");
    $trend_q_mt = db_query($conn, "SELECT EXTRACT(YEAR FROM tanggal_sewa) AS label, COUNT(*) AS jml, COALESCE(SUM(total_bayar),0) AS pend
        FROM log_sewa_motor GROUP BY EXTRACT(YEAR FROM tanggal_sewa) ORDER BY EXTRACT(YEAR FROM tanggal_sewa)");
    $x_label = 'Tahun';
}

// Merge trend data
$trend_map = [];
while ($r = db_fetch_assoc($trend_q_m)) {
    $trend_map[$r['label']] = ['label'=>$r['label'],'jml'=>intval($r['jml']),'pend'=>floatval($r['pend'])];
}
while ($r = db_fetch_assoc($trend_q_mt)) {
    if (!isset($trend_map[$r['label']])) $trend_map[$r['label']] = ['label'=>$r['label'],'jml'=>0,'pend'=>0];
    $trend_map[$r['label']]['jml']  += intval($r['jml']);
    $trend_map[$r['label']]['pend'] += floatval($r['pend']);
}
ksort($trend_map);
$trend_labels = array_map(function($k) use ($filter_tipe, $nama_bulan) {
    if ($filter_tipe === 'bulanan') return $nama_bulan[intval($k)-1] ?? $k;
    return $k;
}, array_keys($trend_map));
$trend_pend = array_column($trend_map, 'pend');
$trend_trx  = array_column($trend_map, 'jml');

// ── KENDARAAN PALING SERING DISEWA ──
$top_mobil = db_query($conn, "SELECT m.nama_mobil AS nama, m.merk, m.foto_mobil AS foto, COUNT(lp.id_sewa) AS jml_sewa, COALESCE(SUM(lp.total_bayar),0) AS total_pend
    FROM mobil m LEFT JOIN log_penyewaan lp ON m.id_mobil=lp.id_mobil AND lp.payment_status='lunas'
    GROUP BY m.id_mobil ORDER BY jml_sewa DESC LIMIT 5");
$top_motor = db_query($conn, "SELECT mt.nama_motor AS nama, mt.merk, mt.foto_motor AS foto, COUNT(ls.id_sewa) AS jml_sewa, COALESCE(SUM(ls.total_bayar),0) AS total_pend
    FROM motor mt LEFT JOIN log_sewa_motor ls ON mt.id_motor=ls.id_motor AND ls.payment_status='lunas'
    GROUP BY mt.id_motor ORDER BY jml_sewa DESC LIMIT 5");

// ── CUSTOMER PALING AKTIF ──
$top_customer = db_query($conn, "
    SELECT u.nama_lengkap, u.username,
           COUNT(*) AS jml_sewa,
           COALESCE(SUM(total_bayar),0) AS total_bayar
    FROM (
        SELECT id_user, total_bayar FROM log_penyewaan WHERE payment_status='lunas'
        UNION ALL
        SELECT id_user, total_bayar FROM log_sewa_motor WHERE payment_status='lunas'
    ) AS all_sewa JOIN app_users u ON all_sewa.id_user=u.id_user
    GROUP BY u.id_user ORDER BY total_bayar DESC LIMIT 5");

// ── EXPORT CSV ──
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan_znrent_'.$filter_tipe.'_'.date('Ymd').'.csv');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
    fputcsv($out, ['ZD RENT — Laporan Keuangan', '', '', '']);
    fputcsv($out, ['Periode', ucfirst($filter_tipe), '', '']);
    fputcsv($out, ['Digenerate', date('d/m/Y H:i'), '', '']);
    fputcsv($out, []);
    fputcsv($out, ['Tipe', 'Label', 'Jumlah Transaksi', 'Total Pendapatan (Rp)']);
    foreach ($trend_map as $k => $v) {
        $lbl = ($filter_tipe === 'bulanan') ? ($nama_bulan[intval($k)-1] ?? $k) : $k;
        fputcsv($out, ['Gabungan', $lbl, $v['jml'], number_format($v['pend'],0,',','.')]);
    }
    fputcsv($out, []);
    fputcsv($out, ['TOTAL PENDAPATAN', '', '', number_format($total_rev,0,',','.')]);
    fputcsv($out, ['TOTAL TRANSAKSI', '', '', $total_trx]);
    fclose($out);
    exit;
}

$page_title  = 'Laporan Keuangan';
$active_menu = 'laporan';
include '_layout.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-6);flex-wrap:wrap;gap:var(--space-4)">
  <div>
    <h2 style="font-size:var(--text-xl);font-weight:800;letter-spacing:-.02em">Laporan Keuangan</h2>
    <p style="color:var(--color-text-muted);font-size:var(--text-sm)">Analisis pendapatan, tren, & performa kendaraan</p>
  </div>
  <div style="display:flex;gap:var(--space-2);flex-wrap:wrap">
    <a href="?<?= http_build_query(array_merge($_GET,['export'=>1])) ?>" class="btn btn-ghost btn-sm">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Export CSV
    </a>
    <a href="export_laporan.php?format=excel&type=keuangan&dari=<?= urlencode($filter_bulan.'-01') ?>&sampai=<?= urlencode($filter_bulan.'-'.date('t',strtotime($filter_bulan.'-01'))) ?>" class="btn btn-ghost btn-sm">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
      Excel
    </a>
    <a href="export_laporan.php?format=pdf&type=keuangan&dari=<?= urlencode($filter_bulan.'-01') ?>&sampai=<?= urlencode($filter_bulan.'-'.date('t',strtotime($filter_bulan.'-01'))) ?>" target="_blank" class="btn btn-ghost btn-sm">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      PDF
    </a>
  </div>
</div>

<!-- FILTER -->
<div class="card" style="margin-bottom:var(--space-6)">
  <form method="GET" style="display:flex;gap:var(--space-4);flex-wrap:wrap;align-items:flex-end">
    <div class="form-group" style="margin:0">
      <label class="form-label">Periode</label>
      <select name="tipe" class="form-select" onchange="this.form.submit()">
        <option value="harian" <?= $filter_tipe==='harian'?'selected':'' ?>>📅 Harian (per bulan)</option>
        <option value="bulanan" <?= $filter_tipe==='bulanan'?'selected':'' ?>>📆 Bulanan (per tahun)</option>
        <option value="tahunan" <?= $filter_tipe==='tahunan'?'selected':'' ?>>📊 Tahunan (semua data)</option>
      </select>
    </div>
    <div class="form-group" style="margin:0">
      <label class="form-label">Tahun</label>
      <select name="tahun" class="form-select" onchange="this.form.submit()">
        <?php for ($y=2024;$y<=2030;$y++): ?>
        <option value="<?= $y ?>" <?= $filter_tahun===$y?'selected':'' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <?php if ($filter_tipe === 'harian'): ?>
    <div class="form-group" style="margin:0">
      <label class="form-label">Bulan</label>
      <select name="bulan" class="form-select" onchange="this.form.submit()">
        <?php $bln_arr=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        for ($b=1;$b<=12;$b++): ?>
        <option value="<?= $b ?>" <?= $filter_bulan===$b?'selected':'' ?>><?= $bln_arr[$b-1] ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <?php endif; ?>
    <a href="laporan_keuangan.php" class="btn btn-ghost btn-sm">Reset</a>
  </form>
</div>

<!-- KPI -->
<div class="stats-grid" style="margin-bottom:var(--space-6)">
  <div class="stat-card"><div class="stat-label">Total Pendapatan</div><div class="stat-value" style="font-size:var(--text-lg)">Rp <?= number_format($total_rev,0,',','.') ?></div><div class="stat-sub">Periode ini</div></div>
  <div class="stat-card"><div class="stat-label">Dari Mobil</div><div class="stat-value" style="font-size:var(--text-lg)">Rp <?= number_format($rev_mobil,0,',','.') ?></div><div class="stat-sub"><?= $trx_mobil ?> transaksi</div></div>
  <div class="stat-card"><div class="stat-label">Dari Motor</div><div class="stat-value" style="font-size:var(--text-lg)">Rp <?= number_format($rev_motor,0,',','.') ?></div><div class="stat-sub"><?= $trx_motor ?> transaksi</div></div>
  <div class="stat-card"><div class="stat-label">Rata-rata / Transaksi</div><div class="stat-value" style="font-size:var(--text-lg)">Rp <?= number_format($avg_trx,0,',','.') ?></div><div class="stat-sub"><?= $total_trx ?> total transaksi</div></div>
  <div class="stat-card"><div class="stat-label">Total Denda</div><div class="stat-value" style="color:var(--color-error);font-size:var(--text-lg)">Rp <?= number_format($total_denda,0,',','.') ?></div><div class="stat-sub">Semua periode</div></div>
</div>

<!-- CHARTS ROW -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:var(--space-6);margin-bottom:var(--space-6)">
  <div class="card">
    <div class="card-header"><div class="card-title">Grafik Pendapatan (<?= ucfirst($filter_tipe) ?>)</div></div>
    <canvas id="chartPendapatan" height="100"></canvas>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">Jumlah Transaksi</div></div>
    <canvas id="chartTrx" height="180"></canvas>
  </div>
</div>

<!-- TOP KENDARAAN & CUSTOMER -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:var(--space-6);margin-bottom:var(--space-6)">
  <!-- Top Mobil -->
  <div class="card">
    <div class="card-header"><div class="card-title">🚗 Top Mobil</div></div>
    <div style="display:flex;flex-direction:column;gap:var(--space-3)">
      <?php $rank=1; while ($r = db_fetch_assoc($top_mobil)): ?>
      <div style="display:flex;align-items:center;gap:var(--space-3)">
        <span style="width:24px;text-align:center;font-weight:800;font-size:var(--text-sm);color:<?= $rank<=3?'var(--color-accent)':'var(--color-text-faint)' ?>"><?= $rank++ ?></span>
        <?php if ($r['foto']): ?><img src="<?= htmlspecialchars($r['foto']) ?>" style="width:44px;height:32px;object-fit:cover;border-radius:6px;flex-shrink:0"><?php endif; ?>
        <div style="flex:1;min-width:0">
          <div style="font-weight:700;font-size:var(--text-sm)"><?= htmlspecialchars($r['nama']) ?></div>
          <div style="font-size:11px;color:var(--color-text-faint)"><?= $r['jml_sewa'] ?>x · Rp <?= number_format($r['total_pend'],0,',','.') ?></div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
  <!-- Top Motor -->
  <div class="card">
    <div class="card-header"><div class="card-title">🏍️ Top Motor</div></div>
    <div style="display:flex;flex-direction:column;gap:var(--space-3)">
      <?php $rank=1; while ($r = db_fetch_assoc($top_motor)): ?>
      <div style="display:flex;align-items:center;gap:var(--space-3)">
        <span style="width:24px;text-align:center;font-weight:800;font-size:var(--text-sm);color:<?= $rank<=3?'var(--color-accent)':'var(--color-text-faint)' ?>"><?= $rank++ ?></span>
        <?php if ($r['foto']): ?><img src="<?= htmlspecialchars($r['foto']) ?>" style="width:44px;height:32px;object-fit:cover;border-radius:6px;flex-shrink:0"><?php endif; ?>
        <div style="flex:1;min-width:0">
          <div style="font-weight:700;font-size:var(--text-sm)"><?= htmlspecialchars($r['nama']) ?></div>
          <div style="font-size:11px;color:var(--color-text-faint)"><?= $r['jml_sewa'] ?>x · Rp <?= number_format($r['total_pend'],0,',','.') ?></div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
  <!-- Top Customer -->
  <div class="card">
    <div class="card-header"><div class="card-title">👤 Customer Paling Aktif</div></div>
    <div style="display:flex;flex-direction:column;gap:var(--space-3)">
      <?php $rank=1; while ($r = db_fetch_assoc($top_customer)): ?>
      <div style="display:flex;align-items:center;gap:var(--space-3)">
        <div style="width:32px;height:32px;border-radius:50%;background:#111;color:#fff;display:flex;align-items:center;justify-content:center;font-size:var(--text-xs);font-weight:800;flex-shrink:0"><?= $rank++ ?></div>
        <div style="flex:1;min-width:0">
          <div style="font-weight:700;font-size:var(--text-sm)"><?= htmlspecialchars($r['nama_lengkap']) ?></div>
          <div style="font-size:11px;color:var(--color-text-faint)"><?= $r['jml_sewa'] ?>x sewa · Rp <?= number_format($r['total_bayar'],0,',','.') ?></div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
const labels = <?= json_encode(array_values($trend_labels)) ?>;
const pend   = <?= json_encode(array_values($trend_pend)) ?>;
const trx    = <?= json_encode(array_values($trend_trx)) ?>;

const ctxP = document.getElementById('chartPendapatan').getContext('2d');
new Chart(ctxP, {
  type: 'bar',
  data: {
    labels,
    datasets: [{
      label: 'Pendapatan (Rp)',
      data: pend,
      backgroundColor: 'rgba(17,17,17,0.85)',
      borderRadius: 6,
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: ctx => 'Rp ' + ctx.raw.toLocaleString('id-ID')
        }
      }
    },
    scales: {
      y: {
        ticks: {
          callback: v => 'Rp ' + (v/1000000).toFixed(1) + 'jt'
        }
      }
    }
  }
});

const ctxT = document.getElementById('chartTrx').getContext('2d');
new Chart(ctxT, {
  type: 'doughnut',
  data: {
    labels: ['Mobil', 'Motor'],
    datasets: [{
      data: [<?= $trx_mobil ?>, <?= $trx_motor ?>],
      backgroundColor: ['#111111', '#E63946'],
      borderWidth: 0,
    }]
  },
  options: {
    plugins: {
      legend: { position: 'bottom' }
    }
  }
});
</script>
<?php include '_layout_footer.php'; ?>

<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') { header('Location: login.php'); exit; }
require_once 'koneksi.php';
if (file_exists('notif_helper.php')) require_once 'notif_helper.php';

// ── STATS ──
$rev_row   = db_fetch_assoc(db_query($conn, "SELECT SUM(total_bayar) as rev FROM log_penyewaan"));
$total_rev = $rev_row['rev'] ?? 0;

$cars_row   = db_fetch_assoc(db_query($conn, "SELECT COUNT(*) as t, SUM(CASE WHEN status='tersedia' THEN 1 ELSE 0 END) as av FROM mobil"));
$total_cars = $cars_row['t']  ?? 0;
$available  = $cars_row['av'] ?? 0;

$trx_row  = db_fetch_assoc(db_query($conn, "SELECT COUNT(*) as t FROM log_penyewaan WHERE EXTRACT(MONTH FROM tanggal_sewa)=EXTRACT(MONTH FROM CURRENT_DATE) AND EXTRACT(YEAR FROM tanggal_sewa)=EXTRACT(YEAR FROM CURRENT_DATE)"));
$trx_month = $trx_row['t'] ?? 0;

$active_row = db_fetch_assoc(db_query($conn, "SELECT COUNT(*) as t FROM log_penyewaan WHERE status='aktif'"));
$active_sewa = $active_row['t'] ?? 0;

// ── PER-MOBIL STATS ──
$stat_q = db_query($conn, "
  SELECT m.nama_mobil, m.merk, m.tahun, m.harga_sewa, m.status,
         COUNT(lp.id_sewa) AS total_sewa,
         COALESCE(SUM(lp.total_bayar),0) AS total_pend,
         COALESCE(AVG((lp.tanggal_kembali - lp.tanggal_sewa)),0) AS avg_hari,
         COALESCE(AVG(lp.total_bayar),0) AS avg_pend
  FROM mobil m
  LEFT JOIN log_penyewaan lp ON m.id_mobil = lp.id_mobil
  GROUP BY m.id_mobil
  ORDER BY total_pend DESC
");

// ── CHART DATA: pendapatan per mobil ──
$chart_labels = [];
$chart_pend   = [];
$chart_sewa   = [];
$color_palette = ['#111111','#444444','#666666','#888888','#aaaaaa','#cccccc','#E63946','#c1121f'];

// reset pointer
$stat_rows = [];
while ($r = db_fetch_assoc($stat_q)) $stat_rows[] = $r;

foreach ($stat_rows as $r) {
    $chart_labels[] = $r['nama_mobil'];
    $chart_pend[]   = (float)$r['total_pend'];
    $chart_sewa[]   = (int)$r['total_sewa'];
}

// ── MONTHLY TREND (last 6 months) ──
$months_q = db_query($conn, "
  SELECT TO_CHAR(tanggal_sewa,'Mon YYYY') AS bulan,
         EXTRACT(YEAR FROM tanggal_sewa)*100+EXTRACT(MONTH FROM tanggal_sewa) AS sort_key,
         COUNT(*) AS jml, COALESCE(SUM(total_bayar),0) AS pend
  FROM log_penyewaan
  GROUP BY sort_key, bulan
  ORDER BY sort_key DESC
  LIMIT 6
");
$month_labels = [];
$month_pend   = [];
$month_trx    = [];
while ($m = db_fetch_assoc($months_q)) {
    array_unshift($month_labels, $m['bulan']);
    array_unshift($month_pend,   (float)$m['pend']);
    array_unshift($month_trx,    (int)$m['jml']);
}

$page_title  = 'Laporan';
$active_menu = 'laporan';
include '_layout.php';
?>

<!-- KPI STATS -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr))">
  <div class="stat-card">
    <div class="stat-label">Total Pendapatan</div>
    <div class="stat-value" style="font-size:var(--text-lg)">Rp <?= number_format($total_rev,0,',','.') ?></div>
    <div class="stat-sub">Dari semua transaksi</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Armada Tersedia</div>
    <div class="stat-value" style="color:var(--color-success)"><?= $available ?> / <?= $total_cars ?></div>
    <div class="stat-sub"><?= $total_cars > 0 ? round(($available/$total_cars)*100) : 0 ?>% siap disewa</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Transaksi Bulan Ini</div>
    <div class="stat-value" style="color:var(--color-accent)"><?= $trx_month ?></div>
    <div class="stat-sub">Bulan <?= date('F Y') ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Aktif Disewa</div>
    <div class="stat-value" style="color:var(--color-warning)"><?= $active_sewa ?></div>
    <div class="stat-sub">Belum dikembalikan</div>
  </div>
</div>

<!-- CHARTS ROW -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);margin-bottom:var(--space-6)">
  <!-- Chart: Pendapatan per Mobil -->
  <div class="card" style="grid-column: span 1">
    <div class="card-header">
      <div>
        <div class="card-title">Pendapatan per Kendaraan</div>
        <div style="font-size:var(--text-xs);color:var(--color-text-faint);margin-top:2px">Total pendapatan kumulatif</div>
      </div>
    </div>
    <div style="position:relative;height:240px">
      <canvas id="chartPendapatan"></canvas>
    </div>
  </div>

  <!-- Chart: Jumlah Sewa per Mobil -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Frekuensi Penyewaan</div>
        <div style="font-size:var(--text-xs);color:var(--color-text-faint);margin-top:2px">Berapa kali tiap kendaraan disewa</div>
      </div>
    </div>
    <div style="position:relative;height:240px">
      <canvas id="chartFrekuensi"></canvas>
    </div>
  </div>
</div>

<!-- Chart: Tren Bulanan FULL WIDTH -->
<div class="card" style="margin-bottom:var(--space-6)">
  <div class="card-header">
    <div>
      <div class="card-title">Tren Pendapatan Bulanan</div>
      <div style="font-size:var(--text-xs);color:var(--color-text-faint);margin-top:2px">6 bulan terakhir</div>
    </div>
    <div style="display:flex;align-items:center;gap:var(--space-4)">
      <div style="display:flex;align-items:center;gap:6px;font-size:var(--text-xs);color:var(--color-text-muted)">
        <div style="width:12px;height:3px;background:#111;border-radius:2px"></div> Pendapatan
      </div>
      <div style="display:flex;align-items:center;gap:6px;font-size:var(--text-xs);color:var(--color-text-muted)">
        <div style="width:12px;height:3px;background:#E63946;border-radius:2px;border-style:dashed"></div> Transaksi
      </div>
    </div>
  </div>
  <div style="position:relative;height:260px">
    <canvas id="chartTrend"></canvas>
  </div>
</div>

<!-- TABEL STATISTIK PER MOBIL -->
<div class="card">
  <div class="card-header">
    <div class="card-title">Statistik Detail per Kendaraan</div>
    <button class="btn btn-ghost btn-sm" onclick="window.print()">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      Cetak
    </button>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Kendaraan</th>
          <th>Merk / Tahun</th>
          <th>Harga/Hari</th>
          <th>Status</th>
          <th>Total Sewa</th>
          <th>Total Pendapatan</th>
          <th>Rata-rata Hari</th>
          <th>Pend. Rata-rata</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($stat_rows as $row): ?>
        <tr>
          <td style="font-weight:700"><?= htmlspecialchars($row['nama_mobil']) ?></td>
          <td style="color:var(--color-text-muted)"><?= htmlspecialchars($row['merk']) ?> · <?= $row['tahun'] ?></td>
          <td style="font-variant-numeric:tabular-nums">Rp <?= number_format($row['harga_sewa'],0,',','.') ?></td>
          <td>
            <?php $s = strtolower($row['status']); ?>
            <span class="badge <?= $s==='tersedia'?'badge-success':'badge-error' ?>"><?= $s==='tersedia'?'● Tersedia':'● Disewa' ?></span>
          </td>
          <td style="font-weight:700;font-variant-numeric:tabular-nums;text-align:center"><?= $row['total_sewa'] ?>x</td>
          <td style="font-weight:700;font-variant-numeric:tabular-nums;color:<?= $row['total_pend']>0?'var(--color-success)':'var(--color-text-faint)' ?>">
            Rp <?= number_format($row['total_pend'],0,',','.') ?>
          </td>
          <td style="font-variant-numeric:tabular-nums;text-align:center"><?= $row['total_sewa']>0 ? round($row['avg_hari'],1).' hari' : '—' ?></td>
          <td style="font-variant-numeric:tabular-nums">
            <?= $row['total_sewa']>0 ? 'Rp '.number_format($row['avg_pend'],0,',','.') : '—' ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($stat_rows)): ?>
        <tr><td colspan="8"><div class="empty-state"><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg><h3>Belum ada data</h3><p>Data laporan akan muncul setelah ada transaksi.</p></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- INSIGHT DASHBOARD -->
<?php if (!empty($stat_rows)):
  $top_car       = $stat_rows[0];
  $top_rev_total = array_sum($chart_pend);
  $total_trx_all = array_sum($chart_sewa);
  $avg_per_trx   = $total_trx_all > 0 ? $top_rev_total / $total_trx_all : 0;
  $util_rate     = $total_cars > 0 ? round((($total_cars - $available) / $total_cars) * 100) : 0;
  $top_share     = $top_rev_total > 0 ? round(($top_car['total_pend'] / $top_rev_total) * 100) : 0;
?>
<div class="insight-dashboard">
  <div class="insight-header">
    <div class="insight-title-group">
      <div class="insight-eyebrow">Ringkasan Performa</div>
      <h2 class="insight-title">Insight Bisnis</h2>
      <p class="insight-desc">Gambaran cepat performa armada dan keuangan ZD RENT berdasarkan seluruh transaksi yang ada.</p>
    </div>
    <div class="insight-badge-row">
      <span class="insight-chip">Data Real-time</span>
      <span class="insight-chip accent">Auto Update</span>
    </div>
  </div>

  <div class="insight-grid">
    <!-- Card 1: Kendaraan Terpopuler -->
    <div class="insight-card popular">
      <div class="insight-card-icon">🏆</div>
      <div class="insight-card-label">Kendaraan Terpopuler</div>
      <div class="insight-card-value"><?= htmlspecialchars($top_car['nama_mobil']) ?></div>
      <div class="insight-card-sub"><?= htmlspecialchars($top_car['merk']) ?> · <?= $top_car['tahun'] ?></div>
      <div class="insight-card-meta">
        <div class="insight-meta-item">
          <span class="meta-num"><?= $top_car['total_sewa'] ?>x</span>
          <span class="meta-label">disewa</span>
        </div>
        <div class="insight-meta-item">
          <span class="meta-num">Rp <?= number_format($top_car['total_pend'],0,',','.') ?></span>
          <span class="meta-label">pendapatan</span>
        </div>
      </div>
      <div class="insight-progress-wrap">
        <div class="insight-progress-label">Kontribusi pendapatan</div>
        <div class="insight-progress-bar"><div class="insight-progress-fill" style="width:<?= $top_share ?>%"></div></div>
        <div class="insight-progress-pct"><?= $top_share ?>%</div>
      </div>
    </div>

    <!-- Card 2: Total Pendapatan -->
    <div class="insight-card revenue">
      <div class="insight-card-icon">💰</div>
      <div class="insight-card-label">Total Pendapatan</div>
      <div class="insight-card-value large">Rp <?= number_format($top_rev_total,0,',','.') ?></div>
      <div class="insight-card-sub">Akumulasi seluruh transaksi</div>
      <div class="insight-card-meta">
        <div class="insight-meta-item">
          <span class="meta-num"><?= $total_trx_all ?></span>
          <span class="meta-label">transaksi</span>
        </div>
        <div class="insight-meta-item">
          <span class="meta-num"><?= $total_cars ?></span>
          <span class="meta-label">unit armada</span>
        </div>
      </div>
      <div class="insight-revenue-bar">
        <?php foreach($stat_rows as $i => $r):
          $pct = $top_rev_total > 0 ? ($r['total_pend']/$top_rev_total)*100 : 0;
          $colors = ['#111','#444','#666','#888','#aaa','#E63946'];
          $c = $colors[$i % count($colors)];
        ?>
        <div class="rev-segment" style="width:<?= $pct ?>%;background:<?= $c ?>" title="<?= htmlspecialchars($r['nama_mobil']) ?>: <?= round($pct) ?>%"></div>
        <?php endforeach; ?>
      </div>
      <div class="rev-legend">
        <?php foreach(array_slice($stat_rows,0,4) as $i => $r):
          $colors = ['#111','#444','#666','#888','#aaa','#E63946'];
          $c = $colors[$i % count($colors)];
        ?>
        <div class="rev-legend-item"><span style="background:<?= $c ?>"></span><?= htmlspecialchars($r['nama_mobil']) ?></div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Card 3: Rata-rata per Transaksi -->
    <div class="insight-card avg">
      <div class="insight-card-icon">📊</div>
      <div class="insight-card-label">Rata-rata per Transaksi</div>
      <div class="insight-card-value large">Rp <?= number_format($avg_per_trx,0,',','.') ?></div>
      <div class="insight-card-sub">Nilai rata-rata setiap booking</div>
      <div class="insight-card-meta">
        <div class="insight-meta-item">
          <span class="meta-num"><?= $util_rate ?>%</span>
          <span class="meta-label">utilisasi armada</span>
        </div>
        <div class="insight-meta-item">
          <span class="meta-num"><?= $available ?>/<?= $total_cars ?></span>
          <span class="meta-label">tersedia</span>
        </div>
      </div>
      <div class="insight-util-wrap">
        <div class="insight-progress-label">Tingkat utilisasi armada</div>
        <div class="insight-progress-bar green"><div class="insight-progress-fill green" style="width:<?= $util_rate ?>%"></div></div>
        <div class="insight-progress-pct"><?= $util_rate ?>% terpakai</div>
      </div>
    </div>
  </div>
</div>

<style>
.insight-dashboard{margin-top:var(--space-8);background:linear-gradient(135deg,#111 0%,#1d1d1d 100%);border-radius:1.5rem;padding:clamp(1.5rem,3vw,2.5rem);color:#fff}
.insight-header{display:flex;justify-content:space-between;align-items:flex-start;gap:var(--space-4);margin-bottom:var(--space-6);flex-wrap:wrap}
.insight-eyebrow{font-size:var(--text-xs);text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.4);font-weight:700;margin-bottom:var(--space-2)}
.insight-title{font-size:clamp(1.5rem,2vw + .5rem,2.25rem);font-weight:800;letter-spacing:-.03em;margin-bottom:var(--space-2)}
.insight-desc{color:rgba(255,255,255,.55);font-size:var(--text-sm);max-width:45ch}
.insight-badge-row{display:flex;gap:var(--space-2);flex-shrink:0;align-items:center;flex-wrap:wrap}
.insight-chip{display:inline-flex;padding:.4rem .85rem;border-radius:999px;font-size:var(--text-xs);font-weight:700;background:rgba(255,255,255,.1);color:rgba(255,255,255,.75)}
.insight-chip.accent{background:rgba(230,57,70,.25);color:#ff8b93}
.insight-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:var(--space-4)}
.insight-card{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:1.25rem;padding:var(--space-5);display:flex;flex-direction:column;gap:var(--space-3)}
.insight-card-icon{font-size:1.75rem;line-height:1}
.insight-card-label{font-size:var(--text-xs);text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,.45);font-weight:700}
.insight-card-value{font-size:var(--text-lg);font-weight:800;line-height:1.1;color:#fff}
.insight-card-value.large{font-size:clamp(1.1rem,1.5vw + .5rem,1.5rem)}
.insight-card-sub{font-size:var(--text-xs);color:rgba(255,255,255,.45)}
.insight-card-meta{display:flex;gap:var(--space-4);margin-top:var(--space-2);padding-top:var(--space-3);border-top:1px solid rgba(255,255,255,.08)}
.insight-meta-item{display:flex;flex-direction:column;gap:2px}
.meta-num{font-size:var(--text-base);font-weight:800;color:#fff;font-variant-numeric:tabular-nums}
.meta-label{font-size:10px;text-transform:uppercase;color:rgba(255,255,255,.4);letter-spacing:.06em}
.insight-progress-wrap{margin-top:auto}
.insight-progress-label{font-size:10px;color:rgba(255,255,255,.4);margin-bottom:var(--space-2);text-transform:uppercase;letter-spacing:.05em}
.insight-progress-bar{height:6px;background:rgba(255,255,255,.12);border-radius:999px;overflow:hidden;margin-bottom:var(--space-2)}
.insight-progress-fill{height:100%;border-radius:999px;background:rgba(255,255,255,.7);transition:width .6s ease}
.insight-progress-fill.green{background:#4ade80}
.insight-progress-pct{font-size:var(--text-xs);color:rgba(255,255,255,.55);font-weight:600}
.insight-revenue-bar{display:flex;height:10px;border-radius:999px;overflow:hidden;gap:2px;margin-top:var(--space-2)}
.rev-segment{height:100%;border-radius:2px;min-width:2px;transition:opacity .2s}
.rev-segment:hover{opacity:.8}
.rev-legend{display:flex;flex-wrap:wrap;gap:var(--space-2);margin-top:var(--space-2)}
.rev-legend-item{display:flex;align-items:center;gap:5px;font-size:10px;color:rgba(255,255,255,.55)}
.rev-legend-item span{display:inline-block;width:10px;height:10px;border-radius:2px;flex-shrink:0}
.insight-util-wrap{margin-top:auto}
@media(max-width:900px){.insight-grid{grid-template-columns:1fr 1fr}}
@media(max-width:580px){.insight-grid{grid-template-columns:1fr}.insight-header{flex-direction:column}}
</style>
<?php endif; ?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
Chart.defaults.color = '#999';

const labels   = <?= json_encode($chart_labels) ?>;
const pendData = <?= json_encode($chart_pend) ?>;
const sewaData = <?= json_encode($chart_sewa) ?>;
const mLabels  = <?= json_encode($month_labels) ?>;
const mPend    = <?= json_encode($month_pend) ?>;
const mTrx     = <?= json_encode($month_trx) ?>;

const palette = ['#111111','#333333','#555555','#777777','#999999','#bbbbbb','#E63946','#c1121f'];

// Chart 1 — Pendapatan per Mobil (Horizontal Bar)
new Chart(document.getElementById('chartPendapatan'), {
  type: 'bar',
  data: {
    labels,
    datasets: [{
      label: 'Pendapatan (Rp)',
      data: pendData,
      backgroundColor: labels.map((_, i) => palette[i % palette.length]),
      borderRadius: 6,
      borderSkipped: false,
    }]
  },
  options: {
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: ctx => ' Rp ' + ctx.parsed.x.toLocaleString('id-ID')
        }
      }
    },
    scales: {
      x: {
        grid: { color: 'rgba(0,0,0,0.04)' },
        ticks: {
          callback: v => 'Rp ' + (v/1000).toFixed(0) + 'k',
          font: { size: 11 }
        }
      },
      y: { grid: { display: false }, ticks: { font: { size: 11 } } }
    }
  }
});

// Chart 2 — Frekuensi Sewa (Doughnut)
new Chart(document.getElementById('chartFrekuensi'), {
  type: 'doughnut',
  data: {
    labels,
    datasets: [{
      data: sewaData,
      backgroundColor: palette,
      borderWidth: 2,
      borderColor: '#fff',
      hoverOffset: 8,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '65%',
    plugins: {
      legend: {
        position: 'right',
        labels: { padding: 12, font: { size: 11 }, usePointStyle: true, pointStyleWidth: 8 }
      },
      tooltip: {
        callbacks: { label: ctx => ' ' + ctx.label + ': ' + ctx.parsed + 'x' }
      }
    }
  }
});

// Chart 3 — Tren Bulanan (Line)
new Chart(document.getElementById('chartTrend'), {
  type: 'line',
  data: {
    labels: mLabels.length ? mLabels : ['—'],
    datasets: [
      {
        label: 'Pendapatan',
        data: mPend.length ? mPend : [0],
        borderColor: '#111111',
        backgroundColor: 'rgba(17,17,17,0.06)',
        fill: true,
        tension: 0.4,
        pointBackgroundColor: '#111',
        pointRadius: 5,
        pointHoverRadius: 7,
        borderWidth: 2.5,
        yAxisID: 'y1',
      },
      {
        label: 'Transaksi',
        data: mTrx.length ? mTrx : [0],
        borderColor: '#E63946',
        backgroundColor: 'transparent',
        tension: 0.4,
        pointBackgroundColor: '#E63946',
        pointRadius: 4,
        pointHoverRadius: 6,
        borderWidth: 2,
        borderDash: [6,3],
        yAxisID: 'y2',
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: ctx => {
            if (ctx.datasetIndex === 0) return ' Rp ' + ctx.parsed.y.toLocaleString('id-ID');
            return ' ' + ctx.parsed.y + ' transaksi';
          }
        }
      }
    },
    scales: {
      x: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 11 } } },
      y1: {
        position: 'left',
        grid: { color: 'rgba(0,0,0,0.04)' },
        ticks: { callback: v => 'Rp '+(v/1000).toFixed(0)+'k', font: { size: 11 } }
      },
      y2: {
        position: 'right',
        grid: { display: false },
        ticks: { font: { size: 11 }, callback: v => v + 'x' }
      }
    }
  }
});
</script>

<style>
@media print {
  .sidebar, .header, .btn { display: none !important; }
  .main-content { margin: 0 !important; padding: 1rem !important; }
}
@media (max-width: 768px) {
  .stats-grid > div:nth-child(n+3) { display: block; }
  [style*="grid-template-columns:1fr 1fr"] { grid-template-columns: 1fr !important; }
}
</style>

<?php include '_layout_footer.php'; ?>

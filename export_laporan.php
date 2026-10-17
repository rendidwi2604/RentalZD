<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') { header('Location: login.php'); exit; }
require_once 'koneksi.php';

$format  = $_GET['format']  ?? 'excel'; // excel | pdf
$type    = $_GET['type']    ?? 'keuangan'; // keuangan | armada | penyewaan
$dari    = $_GET['dari']    ?? date('Y-m-01');
$sampai  = $_GET['sampai']  ?? date('Y-m-d');

// Query data
if ($type === 'keuangan') {
    $q = db_query($conn, "
        SELECT 'Mobil' AS jenis, lp.id_sewa, u.nama_lengkap, m.nama_mobil AS kendaraan,
               lp.tanggal_sewa, lp.tanggal_kembali, lp.total_bayar, lp.payment_status, lp.status
        FROM log_penyewaan lp
        LEFT JOIN app_users u ON lp.id_user=u.id_user
        LEFT JOIN mobil m ON lp.id_mobil=m.id_mobil
        WHERE DATE(lp.created_at) BETWEEN '$dari' AND '$sampai'
        UNION ALL
        SELECT 'Motor' AS jenis, ls.id_sewa, u.nama_lengkap, mt.nama_motor AS kendaraan,
               ls.tanggal_sewa, ls.tanggal_kembali, ls.total_bayar, ls.payment_status, ls.status
        FROM log_sewa_motor ls
        LEFT JOIN app_users u ON ls.id_user=u.id_user
        LEFT JOIN motor mt ON ls.id_motor=mt.id_motor
        WHERE DATE(ls.created_at) BETWEEN '$dari' AND '$sampai'
        ORDER BY tanggal_sewa DESC");
    $title = "Laporan Keuangan $dari s/d $sampai";
    $cols  = ['Jenis','ID Sewa','Customer','Kendaraan','Tgl Sewa','Tgl Kembali','Total Bayar','Status Bayar','Status'];
} elseif ($type === 'armada') {
    $q = db_query($conn, "
        SELECT 'Mobil' AS jenis, id_mobil AS id, nama_mobil AS nama, merk, tahun, harga_sewa, status, kapasitas AS info
        FROM mobil
        UNION ALL
        SELECT 'Motor', id_motor, nama_motor, merk, tahun, harga_sewa, status, kapasitas
        FROM motor
        ORDER BY jenis, nama");
    $title = "Laporan Armada Kendaraan";
    $cols  = ['Jenis','ID','Nama','Merk','Tahun','Harga Sewa','Status','Kapasitas'];
} else {
    $q = db_query($conn, "
        SELECT lp.id_sewa, u.nama_lengkap, u.username, m.nama_mobil AS kendaraan, 'Mobil' AS jenis,
               lp.tanggal_sewa, lp.tanggal_kembali, lp.total_bayar, lp.payment_method,
               lp.payment_status, lp.status, lp.addon_detail
        FROM log_penyewaan lp
        LEFT JOIN app_users u ON lp.id_user=u.id_user
        LEFT JOIN mobil m ON lp.id_mobil=m.id_mobil
        WHERE DATE(lp.created_at) BETWEEN '$dari' AND '$sampai'
        UNION ALL
        SELECT ls.id_sewa, u.nama_lengkap, u.username, mt.nama_motor, 'Motor',
               ls.tanggal_sewa, ls.tanggal_kembali, ls.total_bayar, ls.payment_method,
               ls.payment_status, ls.status, ls.addon_detail
        FROM log_sewa_motor ls
        LEFT JOIN app_users u ON ls.id_user=u.id_user
        LEFT JOIN motor mt ON ls.id_motor=mt.id_motor
        WHERE DATE(ls.created_at) BETWEEN '$dari' AND '$sampai'
        ORDER BY tanggal_sewa DESC");
    $title = "Log Penyewaan $dari s/d $sampai";
    $cols  = ['ID','Customer','Username','Kendaraan','Jenis','Tgl Sewa','Tgl Kembali','Total','Metode','Status Bayar','Status','Add-on'];
}

$rows = [];
while ($r = db_fetch_assoc($q)) $rows[] = $r;

// ─── EXCEL (CSV) ──────────────────────────────────────────────────────────────
if ($format === 'excel') {
    $filename = strtolower(str_replace(' ','_', $title)) . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel
    fputcsv($out, $cols, ';');
    foreach ($rows as $r) {
        $vals = array_values($r);
        // Format rupiah untuk total_bayar / harga_sewa
        foreach ($vals as &$v) {
            if (is_numeric($v) && strlen((string)$v) > 3) {
                // skip id columns — heuristic: if column name has 'bayar' or 'sewa'
            }
        }
        fputcsv($out, $vals, ';');
    }
    fclose($out);
    exit;
}

// ─── PDF (HTML Print) ─────────────────────────────────────────────────────────
if ($format === 'pdf') {
    $total_rev = 0;
    foreach ($rows as $r) {
        if (isset($r['total_bayar'])) $total_rev += (float)$r['total_bayar'];
        if (isset($r['harga_sewa']))  $total_rev += (float)$r['harga_sewa'];
    }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($title) ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Arial,sans-serif;font-size:11px;color:#111;padding:20px}
.header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;padding-bottom:16px;border-bottom:2px solid #111}
.brand{font-size:22px;font-weight:900;letter-spacing:-1px}
.brand span{color:#E63946}
.meta{text-align:right;color:#555;font-size:10px;line-height:1.6}
h2{font-size:15px;font-weight:800;margin-bottom:12px}
.summary{display:flex;gap:20px;margin-bottom:16px}
.sum-box{background:#f8f8f8;border:1px solid #eee;border-radius:6px;padding:10px 16px;flex:1;text-align:center}
.sum-box .val{font-size:18px;font-weight:800;margin-bottom:2px}
.sum-box .lbl{font-size:9px;color:#888;text-transform:uppercase;letter-spacing:.08em}
table{width:100%;border-collapse:collapse;margin-bottom:20px}
th{background:#111;color:#fff;padding:7px 10px;text-align:left;font-size:10px;font-weight:700}
td{padding:6px 10px;border-bottom:1px solid #f0f0f0;font-size:10px}
tr:nth-child(even) td{background:#fafafa}
.badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:9px;font-weight:700}
.badge-success{background:#d1fae5;color:#065f46}
.badge-warning{background:#fef3c7;color:#92400e}
.badge-neutral{background:#f3f4f6;color:#374151}
.footer{text-align:center;font-size:9px;color:#aaa;border-top:1px solid #eee;padding-top:12px;margin-top:20px}
@media print{
  body{padding:0}
  .no-print{display:none}
  @page{margin:15mm}
}
</style>
</head>
<body>

<div class="no-print" style="margin-bottom:16px;display:flex;gap:8px">
  <button onclick="window.print()" style="padding:8px 20px;background:#111;color:#fff;border:none;border-radius:6px;font-weight:700;cursor:pointer;font-size:12px">🖨️ Print / Save PDF</button>
  <button onclick="window.close()" style="padding:8px 16px;background:#f4f4f5;border:none;border-radius:6px;cursor:pointer;font-size:12px">Tutup</button>
</div>

<div class="header">
  <div>
    <div class="brand">ZD<span>RENT</span></div>
    <div style="font-size:10px;color:#888;margin-top:4px">Premium Car & Motor Rental</div>
  </div>
  <div class="meta">
    <div style="font-weight:700;font-size:12px"><?= htmlspecialchars($title) ?></div>
    <div>Dicetak: <?= date('d M Y H:i') ?></div>
    <div>Admin: <?= htmlspecialchars($_SESSION['nama_lengkap']) ?></div>
  </div>
</div>

<h2><?= htmlspecialchars($title) ?></h2>

<div class="summary">
  <div class="sum-box"><div class="val"><?= count($rows) ?></div><div class="lbl">Total Data</div></div>
  <?php if ($type !== 'armada'): ?>
  <div class="sum-box"><div class="val">Rp <?= number_format($total_rev,0,',','.') ?></div><div class="lbl">Total Nilai</div></div>
  <?php endif; ?>
  <div class="sum-box"><div class="val"><?= $dari ?></div><div class="lbl">Dari Tanggal</div></div>
  <div class="sum-box"><div class="val"><?= $sampai ?></div><div class="lbl">Sampai</div></div>
</div>

<table>
  <thead><tr><?php foreach ($cols as $c): ?><th><?= $c ?></th><?php endforeach; ?></tr></thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
    <tr>
      <?php foreach (array_values($r) as $i => $v): ?>
      <td>
        <?php
        $cn = $cols[$i] ?? '';
        if ($cn === 'Status Bayar') {
          if ($v==='lunas') echo '<span class="badge badge-success">Lunas</span>';
          elseif ($v==='menunggu_konfirmasi') echo '<span class="badge badge-warning">Menunggu</span>';
          else echo '<span class="badge badge-neutral">'.htmlspecialchars($v).'</span>';
        } elseif ($cn === 'Status') {
          $cls = $v==='aktif'?'badge-warning':($v==='tersedia'?'badge-success':'badge-neutral');
          echo '<span class="badge '.$cls.'">'.ucfirst(htmlspecialchars($v)).'</span>';
        } elseif (in_array($cn, ['Total Bayar','Harga Sewa','Total'])) {
          echo 'Rp ' . number_format((float)$v,0,',','.');
        } else {
          echo htmlspecialchars($v ?? '—');
        }
        ?>
      </td>
      <?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?>
    <tr><td colspan="<?= count($cols) ?>" style="text-align:center;color:#aaa;padding:20px">Tidak ada data</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<div class="footer">ZD RENT — Laporan digenerate otomatis pada <?= date('d M Y H:i:s') ?> WIB</div>
</body>
</html>
<?php
    exit;
}

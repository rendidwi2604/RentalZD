<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user'])) { header('Location: login.php'); exit; }
require_once 'koneksi.php';

$id_sewa = intval($_GET['id'] ?? 0);
$jenis   = ($_GET['jenis'] ?? 'mobil') === 'motor' ? 'motor' : 'mobil';
$role    = $_SESSION['role'] ?? 'customer';
$id_user = (int)$_SESSION['id_user'];

if (!$id_sewa) { echo '<p>Invoice tidak ditemukan.</p>'; exit; }

// Fetch data
if ($jenis === 'mobil') {
    $q = db_query($conn,
        "SELECT lp.*, u.username, u.nama_lengkap,
                m.nama_mobil AS nama_kendaraan, m.merk, m.tahun, m.harga_sewa,
                COALESCE(m.foto_mobil,'') AS foto
         FROM log_penyewaan lp
         LEFT JOIN app_users  u ON lp.id_user  = u.id_user
         LEFT JOIN mobil m ON lp.id_mobil = m.id_mobil
         WHERE lp.id_sewa = '$id_sewa'"
    );
} else {
    $q = db_query($conn,
        "SELECT ls.*, u.username, u.nama_lengkap,
                mt.nama_motor AS nama_kendaraan, mt.merk, mt.tahun, mt.harga_sewa,
                COALESCE(mt.foto_motor,'') AS foto
         FROM log_sewa_motor ls
         LEFT JOIN app_users  u  ON ls.id_user  = u.id_user
         LEFT JOIN motor mt ON ls.id_motor = mt.id_motor
         WHERE ls.id_sewa = '$id_sewa'"
    );
}

$data = db_fetch_assoc($q);
if (!$data) { echo '<p>Invoice tidak ditemukan.</p>'; exit; }

// Customers can only see their own invoices
if ($role === 'customer' && $data['id_user'] != $id_user) {
    echo '<p>Akses ditolak.</p>'; exit;
}

// Calculate
$tanggal_sewa    = $data['tanggal_sewa'];
$tanggal_kembali = $data['tanggal_kembali'];
$days            = max(1, (int)((strtotime($tanggal_kembali) - strtotime($tanggal_sewa)) / 86400));
$harga_per_hari  = (float)$data['harga_sewa'];
$subtotal        = $days * $harga_per_hari;
$addon_total     = (float)($data['addon_total'] ?? 0);
$total_bayar     = (float)$data['total_bayar'];
$addon_detail    = $data['addon_detail'] ?? '';
$payment_method  = $data['payment_method'] ?? 'Transfer Bank';
$payment_status  = $data['payment_status'] ?? 'pending';
$catatan_admin   = $data['catatan_admin'] ?? '';

// Ambil denda untuk invoice ini
$q_denda_inv = db_query($conn, "SELECT * FROM denda_biaya WHERE id_sewa='$id_sewa' AND jenis='$jenis' ORDER BY created_at ASC");
$denda_list  = [];
$total_denda = 0;
if ($q_denda_inv) {
    while ($d = db_fetch_assoc($q_denda_inv)) {
        $denda_list[] = $d;
        $total_denda += (float)$d['jumlah'];
    }
}

$invoice_no = 'ZDR-' . strtoupper($jenis[0]) . '-' . str_pad($id_sewa, 5, '0', STR_PAD_LEFT);
$created_at = $data['created_at'] ?? date('Y-m-d H:i:s');

function rp($n){ return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice <?= $invoice_no ?> — ZD RENT</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ─── BASE ─────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { -webkit-font-smoothing: antialiased; }
body {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 14px;
  color: #111;
  background: #f4f4f5;
  min-height: 100vh;
  padding: 2rem 1rem;
}

/* ─── SCREEN TOOLBAR ──────────────────────────────── */
.print-toolbar {
  max-width: 820px;
  margin: 0 auto 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}
.print-toolbar a, .print-toolbar button {
  display: inline-flex; align-items: center; gap: .5rem;
  padding: .6rem 1.2rem;
  border-radius: .625rem;
  font-family: inherit;
  font-size: .875rem;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  border: none;
  transition: all .15s;
}
.btn-back  { background: #fff; color: #555; border: 1.5px solid #e0e0e0; }
.btn-back:hover  { background: #f8f8f8; }
.btn-print { background: #111; color: #fff; }
.btn-print:hover { background: #333; box-shadow: 0 4px 12px rgba(0,0,0,.2); }

/* ─── INVOICE PAPER ───────────────────────────────── */
.invoice-paper {
  max-width: 820px;
  margin: 0 auto;
  background: #fff;
  border-radius: 1.25rem;
  box-shadow: 0 8px 40px rgba(0,0,0,.1);
  overflow: hidden;
}

/* ─── HEADER BAND ─────────────────────────────────── */
.inv-header {
  background: #111;
  padding: 2.5rem 2.75rem;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 2rem;
}
.inv-brand {
  display: flex; align-items: center; gap: .875rem;
}
.inv-brand-logo {
  width: 48px; height: 48px;
  background: #E63946;
  border-radius: .625rem;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.inv-brand-name  { font-size: 1.25rem; font-weight: 800; color: #fff; letter-spacing: -.02em; }
.inv-brand-sub   { font-size: 11px; color: rgba(255,255,255,.35); text-transform: uppercase; letter-spacing: .1em; margin-top: 2px; }
.inv-brand-addr  { font-size: 12px; color: rgba(255,255,255,.45); margin-top: .5rem; line-height: 1.6; }

.inv-meta-right  { text-align: right; }
.inv-label-invoice { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .12em; color: rgba(255,255,255,.35); }
.inv-number {
  font-size: 1.5rem; font-weight: 800; color: #fff;
  letter-spacing: -.03em; margin: .25rem 0 .75rem;
}
.inv-status-badge {
  display: inline-flex; align-items: center; gap: .4rem;
  padding: .35rem .875rem;
  border-radius: 999px;
  font-size: .75rem; font-weight: 700;
}
.status-lunas   { background: #dcfce7; color: #166534; }
.status-pending { background: #fef3c7; color: #92400e; }
.status-menunggu{ background: #e0f2fe; color: #075985; }

/* ─── BODY ────────────────────────────────────────── */
.inv-body {
  padding: 2.5rem 2.75rem;
}

/* ─── BILL TO / DETAILS ROW ───────────────────────── */
.inv-info-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
  padding-bottom: 2rem;
  border-bottom: 1.5px solid #f0f0f0;
}
.inv-info-block {}
.inv-info-label {
  font-size: 10px; font-weight: 700; text-transform: uppercase;
  letter-spacing: .1em; color: #999; margin-bottom: .625rem;
}
.inv-info-name  { font-size: 1rem; font-weight: 800; color: #111; }
.inv-info-sub   { font-size: .8125rem; color: #666; margin-top: .2rem; }

.inv-info-row2 {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
}
.inv-info-item   {}
.inv-item-label  { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #999; margin-bottom: .35rem; }
.inv-item-value  { font-size: .875rem; font-weight: 700; color: #111; }

/* ─── VEHICLE CARD ────────────────────────────────── */
.inv-vehicle-card {
  background: #f8f8f8;
  border: 1.5px solid #e8e8e8;
  border-radius: .875rem;
  padding: 1.25rem 1.5rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  margin-bottom: 2rem;
}
.inv-vehicle-img {
  width: 100px; height: 66px;
  border-radius: .5rem;
  object-fit: cover;
  border: 1px solid #e0e0e0;
  flex-shrink: 0;
  background: #e8e8e8;
}
.inv-vehicle-placeholder {
  width: 100px; height: 66px;
  border-radius: .5rem;
  background: #e8e8e8;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.inv-vehicle-name  { font-size: 1.0625rem; font-weight: 800; color: #111; }
.inv-vehicle-meta  { font-size: .8125rem; color: #666; margin-top: .2rem; }
.inv-vehicle-badge {
  margin-left: auto;
  display: inline-flex; padding: .35rem .75rem;
  background: <?= $jenis === 'mobil' ? '#fff3e0' : '#e8f5e9' ?>;
  color: <?= $jenis === 'mobil' ? '#e65100' : '#2e7d32' ?>;
  border-radius: 999px; font-size: .75rem; font-weight: 700;
}

/* ─── LINE ITEMS TABLE ────────────────────────────── */
.inv-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 1.5rem;
}
.inv-table th {
  padding: .75rem 1rem;
  text-align: left;
  font-size: 10px; font-weight: 700; text-transform: uppercase;
  letter-spacing: .08em; color: #999;
  background: #f8f8f8;
  border-bottom: 1.5px solid #e8e8e8;
}
.inv-table th:last-child, .inv-table td:last-child { text-align: right; }
.inv-table td {
  padding: .875rem 1rem;
  font-size: .875rem;
  color: #333;
  border-bottom: 1px solid #f0f0f0;
}
.inv-table tbody tr:last-child td { border-bottom: none; }
.inv-table td strong { color: #111; font-weight: 700; }

/* ─── TOTAL BOX ───────────────────────────────────── */
.inv-total-box {
  background: #111;
  border-radius: .875rem;
  padding: 1.5rem;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 2rem;
}
.inv-total-label { font-size: .875rem; color: rgba(255,255,255,.6); }
.inv-total-value {
  font-size: 1.75rem; font-weight: 800;
  letter-spacing: -.03em;
  color: #fff;
}

/* ─── PAYMENT INFO ────────────────────────────────── */
.inv-pay-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding: 1.25rem 1.5rem;
  background: #f8f8f8;
  border-radius: .875rem;
  border: 1.5px solid #e8e8e8;
}
.inv-pay-item {}
.inv-pay-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #999; margin-bottom: .35rem; }
.inv-pay-value { font-size: .9375rem; font-weight: 700; color: #111; }

/* ─── CATATAN ADMIN ───────────────────────────────── */
.inv-note {
  background: #fffbeb;
  border: 1.5px solid #fde68a;
  border-radius: .75rem;
  padding: 1rem 1.25rem;
  margin-bottom: 1.5rem;
  font-size: .875rem;
  color: #92400e;
}
.inv-note strong { display: block; margin-bottom: .25rem; }

/* ─── FOOTER ──────────────────────────────────────── */
.inv-footer {
  margin-top: 2.5rem;
  padding-top: 1.5rem;
  border-top: 1.5px solid #f0f0f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}
.inv-footer-brand {
  display: flex; align-items: center; gap: .5rem;
}
.inv-footer-logo {
  width: 28px; height: 28px;
  background: #111;
  border-radius: .375rem;
  display: flex; align-items: center; justify-content: center;
}
.inv-footer-name { font-size: .875rem; font-weight: 800; color: #111; }
.inv-footer-text { font-size: .75rem; color: #999; line-height: 1.5; text-align: right; }
.inv-footer-wa   { font-size: .8125rem; color: #25D366; font-weight: 700; }

/* ─── PRINT ───────────────────────────────────────── */
@media print {
  body {
    background: #fff;
    padding: 0;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  .print-toolbar { display: none !important; }
  .invoice-paper {
    border-radius: 0;
    box-shadow: none;
    max-width: 100%;
  }
  .inv-header    { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .inv-total-box { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .inv-status-badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .inv-vehicle-badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}

@media (max-width: 600px) {
  .inv-header    { flex-direction: column; padding: 1.75rem 1.5rem; }
  .inv-meta-right{ text-align: left; }
  .inv-body      { padding: 1.5rem; }
  .inv-info-row  { grid-template-columns: 1fr; }
  .inv-info-row2 { grid-template-columns: 1fr 1fr; }
  .inv-pay-row   { grid-template-columns: 1fr; }
  .inv-total-box { flex-direction: column; text-align: center; }
}
</style>
</head>
<body>

<!-- TOOLBAR (screen only) -->
<div class="print-toolbar">
  <a href="riwayat_sewa.php" class="btn-back">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
    Kembali
  </a>
  <button class="btn-print" onclick="window.print()">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
    Cetak / Simpan PDF
  </button>
</div>

<!-- INVOICE PAPER -->
<div class="invoice-paper">

  <!-- HEADER -->
  <div class="inv-header">
    <div class="inv-brand">
      <div class="inv-brand-logo">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/>
          <rect x="9" y="11" width="14" height="10" rx="2"/>
          <circle cx="12" cy="16" r="1"/><circle cx="20" cy="16" r="1"/>
        </svg>
      </div>
      <div>
        <div class="inv-brand-name">ZD RENT</div>
        <div class="inv-brand-sub">Premium Car &amp; Motorcycle Rental</div>
        <div class="inv-brand-addr">
          Tangerang, Banten, Indonesia<br>
          WhatsApp: +62 896-6866-0248<br>
          support@znrent.com
        </div>
      </div>
    </div>
    <div class="inv-meta-right">
      <div class="inv-label-invoice">Invoice</div>
      <div class="inv-number"><?= $invoice_no ?></div>
      <?php
        if ($payment_status === 'lunas')       echo '<span class="inv-status-badge status-lunas">✓ LUNAS</span>';
        elseif ($payment_status === 'menunggu_konfirmasi') echo '<span class="inv-status-badge status-menunggu">⏳ VERIFIKASI</span>';
        else echo '<span class="inv-status-badge status-pending">💳 PENDING</span>';
      ?>
    </div>
  </div>

  <!-- BODY -->
  <div class="inv-body">

    <!-- Bill To + Tanggal -->
    <div class="inv-info-row">
      <div class="inv-info-block">
        <div class="inv-info-label">Ditagihkan Kepada</div>
        <div class="inv-info-name"><?= htmlspecialchars($data['nama_lengkap']) ?></div>
        <div class="inv-info-sub">@<?= htmlspecialchars($data['username']) ?></div>
      </div>
      <div class="inv-info-block" style="text-align:right">
        <div class="inv-info-label">Detail Invoice</div>
        <div class="inv-info-sub"><strong>Tanggal Terbit:</strong> <?= date('d M Y', strtotime($created_at)) ?></div>
        <div class="inv-info-sub"><strong>Tgl Sewa:</strong> <?= date('d M Y', strtotime($tanggal_sewa)) ?></div>
        <div class="inv-info-sub"><strong>Tgl Kembali:</strong> <?= date('d M Y', strtotime($tanggal_kembali)) ?></div>
        <div class="inv-info-sub"><strong>Durasi:</strong> <?= $days ?> hari</div>
      </div>
    </div>

    <!-- Vehicle Card -->
    <div class="inv-vehicle-card">
      <?php if (!empty($data['foto'])): ?>
      <img src="<?= htmlspecialchars($data['foto']) ?>" class="inv-vehicle-img" alt="<?= htmlspecialchars($data['nama_kendaraan']) ?>">
      <?php else: ?>
      <div class="inv-vehicle-placeholder">
        <?php if ($jenis === 'mobil'): ?>
        <svg width="36" height="24" viewBox="0 0 24 24" fill="none" stroke="#bbb" stroke-width="1.5"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/><rect x="9" y="11" width="14" height="10" rx="2"/></svg>
        <?php else: ?>
        <svg width="32" height="22" viewBox="0 0 24 24" fill="none" stroke="#bbb" stroke-width="1.5"><circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><path d="M15 6H4L2 12h14l1-6z"/></svg>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <div>
        <div class="inv-vehicle-name"><?= htmlspecialchars($data['nama_kendaraan']) ?></div>
        <div class="inv-vehicle-meta"><?= htmlspecialchars($data['merk']) ?> &middot; Tahun <?= (int)$data['tahun'] ?> &middot; <?= $jenis === 'mobil' ? '🚗 Mobil' : '🏍️ Motor' ?></div>
      </div>
      <div class="inv-vehicle-badge"><?= $jenis === 'mobil' ? 'Mobil' : 'Motor' ?></div>
    </div>

    <!-- Line Items -->
    <table class="inv-table">
      <thead>
        <tr>
          <th>Deskripsi</th>
          <th>Harga/Hari</th>
          <th>Hari</th>
          <th>Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><strong>Sewa <?= ucfirst($jenis) ?> — <?= htmlspecialchars($data['nama_kendaraan']) ?></strong><br>
            <span style="font-size:.8rem;color:#888"><?= date('d M Y', strtotime($tanggal_sewa)) ?> s/d <?= date('d M Y', strtotime($tanggal_kembali)) ?></span>
          </td>
          <td><?= rp($harga_per_hari) ?></td>
          <td><?= $days ?></td>
          <td><strong><?= rp($subtotal) ?></strong></td>
        </tr>
        <?php if ($addon_total > 0 && $addon_detail): ?>
        <tr>
          <td colspan="3"><strong>Add-on:</strong> <?= htmlspecialchars($addon_detail) ?></td>
          <td><strong><?= rp($addon_total) ?></strong></td>
        </tr>
        <?php endif; ?>
      <?php if (!empty($denda_list)): ?>
        <?php foreach ($denda_list as $d):
          $denda_icons = ['kerusakan'=>'🔧','keterlambatan'=>'⏰','bbm_kurang'=>'⛽','kehilangan'=>'🔑','lainnya'=>'📋'];
          $dicon = $denda_icons[$d['tipe_denda']] ?? '📋';
        ?>
        <tr style="background:#fff7ed">
          <td colspan="3">
            <strong><?= $dicon ?> Denda <?= ucfirst(str_replace('_',' ',$d['tipe_denda'])) ?></strong>
            <?php if ($d['deskripsi']): ?><br><span style="font-size:.8rem;color:#92400e"><?= htmlspecialchars($d['deskripsi']) ?></span><?php endif; ?>
          </td>
          <td style="color:#c2410c;font-weight:700"><strong><?= rp($d['jumlah']) ?></strong></td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
      <tfoot>
        <tr style="background:#f8f8f8">
          <td colspan="3" style="font-weight:700;color:#555;font-size:.8125rem;text-transform:uppercase;letter-spacing:.06em">Subtotal Sewa</td>
          <td><strong><?= rp($subtotal) ?></strong></td>
        </tr>
        <?php if ($addon_total > 0): ?>
        <tr>
          <td colspan="3" style="color:#555;font-size:.8125rem">Add-on Total</td>
          <td><?= rp($addon_total) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($total_denda > 0): ?>
        <tr style="background:#fff7ed">
          <td colspan="3" style="color:#c2410c;font-size:.8125rem;font-weight:700">⚠️ Total Denda</td>
          <td style="color:#c2410c;font-weight:700"><?= rp($total_denda) ?></td>
        </tr>
        <?php endif; ?>
      </tfoot>
    </table>

    <!-- TOTAL BOX -->
    <div class="inv-total-box">
      <div>
        <div class="inv-total-label">Total Pembayaran</div>
        <div style="font-size:.8125rem;color:rgba(255,255,255,.4);margin-top:.25rem">Termasuk semua biaya &amp; add-on</div>
      </div>
      <div class="inv-total-value"><?= rp($total_bayar) ?></div>
    </div>

    <!-- Payment Info -->
    <div class="inv-pay-row">
      <div class="inv-pay-item">
        <div class="inv-pay-label">Metode Pembayaran</div>
        <div class="inv-pay-value"><?= htmlspecialchars($payment_method) ?></div>
      </div>
      <div class="inv-pay-item">
        <div class="inv-pay-label">Status Pembayaran</div>
        <div class="inv-pay-value">
          <?php
            if ($payment_status === 'lunas') echo '<span style="color:#166534;font-weight:800">✓ LUNAS</span>';
            elseif ($payment_status === 'menunggu_konfirmasi') echo '<span style="color:#075985">⏳ Menunggu Verifikasi</span>';
            else echo '<span style="color:#92400e">💳 Belum Dibayar</span>';
          ?>
        </div>
      </div>
      <div class="inv-pay-item">
        <div class="inv-pay-label">Nomor Invoice</div>
        <div class="inv-pay-value" style="font-family:monospace"><?= $invoice_no ?></div>
      </div>
      <div class="inv-pay-item">
        <div class="inv-pay-label">Tanggal Transaksi</div>
        <div class="inv-pay-value"><?= date('d M Y, H:i', strtotime($created_at)) ?></div>
      </div>
    </div>

    <?php if (!empty($catatan_admin)): ?>
    <div class="inv-note">
      <strong>📝 Catatan dari Admin:</strong>
      <?= htmlspecialchars($catatan_admin) ?>
    </div>
    <?php endif; ?>

    <!-- PAYMENT INSTRUCTIONS (jika belum lunas) -->
    <?php if ($payment_status !== 'lunas'): ?>
    <div style="background:#f0f9ff;border:1.5px solid #bae6fd;border-radius:.875rem;padding:1.25rem 1.5rem;margin-bottom:1.5rem;">
      <div style="font-weight:700;color:#0369a1;margin-bottom:.5rem;font-size:.9375rem">📋 Panduan Pembayaran</div>
      <div style="font-size:.875rem;color:#0c4a6e;line-height:1.7">
        Harap lakukan pembayaran sebesar <strong><?= rp($total_bayar) ?></strong> via <strong><?= htmlspecialchars($payment_method) ?></strong>.<br>
        Setelah membayar, konfirmasikan melalui halaman <em>Riwayat Sewa</em> dan klik <strong>"Saya Sudah Bayar"</strong>.<br>
        Hubungi kami via WhatsApp: <span style="color:#25D366;font-weight:700">+62 896-6866-0248</span> jika ada pertanyaan.
      </div>
    </div>
    <?php endif; ?>

    <!-- FOOTER -->
    <div class="inv-footer">
      <div class="inv-footer-brand">
        <div class="inv-footer-logo">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/><rect x="9" y="11" width="14" height="10" rx="2"/><circle cx="12" cy="16" r="1"/><circle cx="20" cy="16" r="1"/></svg>
        </div>
        <div>
          <div class="inv-footer-name">ZD RENT</div>
          <div style="font-size:.7rem;color:#999">Premium Car &amp; Motorcycle Rental</div>
        </div>
      </div>
      <div class="inv-footer-text">
        Tangerang, Banten, Indonesia<br>
        <span class="inv-footer-wa">WhatsApp: +62 896-6866-0248</span><br>
        Dicetak: <?= date('d M Y, H:i') ?> WIB
      </div>
    </div>

  </div><!-- /.inv-body -->
</div><!-- /.invoice-paper -->

</body>
</html>

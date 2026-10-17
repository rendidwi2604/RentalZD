<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'customer') { header('Location: login.php'); exit; }
require_once 'koneksi.php';
require_once 'notif_helper.php';

$id_sewa = intval($_GET['id']    ?? 0);
$jenis   = ($_GET['jenis'] ?? 'mobil') === 'motor' ? 'motor' : 'mobil';
$id_user = (int)$_SESSION['id_user'];

if (!$id_sewa) { header('Location: riwayat_sewa.php'); exit; }

// Rekening tujuan manual
$bank_nama   = 'SeaBank';
$bank_norek  = '901941949043';
$bank_atasnm = 'Ahmad Noval Ramadhan';
$bank_logo   = 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/13/SeaBank_Indonesia_logo.png/200px-SeaBank_Indonesia_logo.png';

// Ambil data sewa
if ($jenis === 'mobil') {
    $sewa = db_fetch_assoc(db_query($conn,
        "SELECT lp.*, m.nama_mobil AS nama_kendaraan, m.merk, m.foto_mobil AS foto,
                u.nama_lengkap, u.username
         FROM log_penyewaan lp
         LEFT JOIN mobil m ON lp.id_mobil=m.id_mobil
         LEFT JOIN app_users u ON lp.id_user=u.id_user
         WHERE lp.id_sewa='$id_sewa' AND lp.id_user='$id_user'"));
} else {
    $sewa = db_fetch_assoc(db_query($conn,
        "SELECT ls.*, mt.nama_motor AS nama_kendaraan, mt.merk, mt.foto_motor AS foto,
                u.nama_lengkap, u.username
         FROM log_sewa_motor ls
         LEFT JOIN motor mt ON ls.id_motor=mt.id_motor
         LEFT JOIN app_users u ON ls.id_user=u.id_user
         WHERE ls.id_sewa='$id_sewa' AND ls.id_user='$id_user'"));
}

if (!$sewa) { header('Location: riwayat_sewa.php'); exit; }

// Ambil denda
$q_denda_pp = db_query($conn, "SELECT * FROM denda_biaya WHERE id_sewa='$id_sewa' AND jenis='$jenis' ORDER BY created_at ASC");
$denda_pp   = [];
$total_denda_pp = 0;
while ($d = db_fetch_assoc($q_denda_pp)) { $denda_pp[] = $d; $total_denda_pp += (float)$d['jumlah']; }

// Handle Midtrans payment result
if (isset($_GET['paid'])) {
    if ($_GET['paid'] === 'success') {
        if (!empty($sewa['midtrans_order_id'])) {
            require_once __DIR__ . '/midtrans_config.php';
            try {
                $live = \Midtrans\Transaction::status($sewa['midtrans_order_id']);
                $paid = $live->transaction_status === 'settlement' ||
                    ($live->transaction_status === 'capture' && ($live->fraud_status ?? '') === 'accept');
                if ($paid && $live->order_id === $sewa['midtrans_order_id'] && (float)$live->gross_amount === (float)$sewa['total_bayar']) {
                    $table_name = $jenis === 'mobil' ? 'log_penyewaan' : 'log_sewa_motor';
                    db_query($conn, "UPDATE {$table_name} SET payment_status='lunas' WHERE id_sewa=? AND id_user=?", [$id_sewa, $id_user]);
                    header("Location: invoice.php?id=$id_sewa&jenis=$jenis"); exit;
                }
            } catch (Throwable $e) {
                error_log('Midtrans return verification failed (' . $e->getCode() . ').');
            }
        }
    } elseif ($_GET['paid'] === 'error') {
        $uploaded_msg = 'Pembayaran gagal. Silakan coba lagi.';
    }
}

// Handle uploaded flash message
$uploaded_msg = isset($_GET['uploaded']) ? 'Bukti transfer berhasil diupload! Admin akan memverifikasi segera.' : '';

// Flash message setelah upload
if ($uploaded_msg): ?>
<div class="alert alert-success" style="max-width:640px;margin:0 auto var(--space-4)">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
  <?= htmlspecialchars($uploaded_msg) ?>
</div>
<?php endif;

// Jika sudah lunas, redirect ke invoice
if ($sewa['payment_status'] === 'lunas') {
    header("Location: invoice.php?id=$id_sewa&jenis=$jenis");
    exit;
}

// Cek apakah sudah ada bukti yang diupload


$bukti_ada = db_fetch_assoc(db_query($conn,
    "SELECT id, status, created_at FROM bukti_bayar
     WHERE id_sewa='$id_sewa' AND jenis='$jenis' AND id_user='$id_user'
     ORDER BY created_at DESC LIMIT 1"));

$invoice_no  = 'ZDR-' . strtoupper($jenis[0]) . '-' . str_pad($id_sewa, 5, '0', STR_PAD_LEFT);
$page_title  = 'Halaman Pembayaran';
$active_menu = 'riwayat';
include '_layout.php';
?>

<div style="max-width:640px;margin:0 auto">

  <!-- Header -->
  <div style="margin-bottom:var(--space-6)">
    <h2 style="font-size:var(--text-xl);font-weight:800;letter-spacing:-.02em">💳 Halaman Pembayaran</h2>
    <p style="color:var(--color-text-muted);font-size:var(--text-sm)"><?= $invoice_no ?> · <?= htmlspecialchars($sewa['nama_kendaraan']) ?></p>
  </div>

  <!-- Ringkasan Pesanan -->
  <div class="card" style="margin-bottom:var(--space-5)">
    <div class="card-header"><div class="card-title">Ringkasan Pesanan</div></div>
    <div style="display:flex;gap:var(--space-4);align-items:center;flex-wrap:wrap">
      <?php if (!empty($sewa['foto'])): ?>
      <img src="<?= htmlspecialchars($sewa['foto']) ?>"
        style="width:100px;height:70px;object-fit:cover;border-radius:var(--radius-lg);flex-shrink:0;border:1px solid var(--color-border)">
      <?php endif; ?>
      <div style="flex:1;min-width:0">
        <p style="font-weight:800;font-size:var(--text-base)"><?= htmlspecialchars($sewa['nama_kendaraan']) ?>
          <span style="color:var(--color-text-faint);font-weight:400"> – <?= htmlspecialchars($sewa['merk']) ?></span>
        </p>
        <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-top:4px">
          📅 <?= $sewa['tanggal_sewa'] ?> → <?= $sewa['tanggal_kembali'] ?>
        </p>
        <?php if (!empty($sewa['addon_detail'])): ?>
        <p style="font-size:var(--text-xs);color:var(--color-text-faint);margin-top:2px">
          Add-on: <?= htmlspecialchars($sewa['addon_detail']) ?>
        </p>
        <?php endif; ?>
      </div>
      <div style="text-align:right;flex-shrink:0">
        <p style="font-size:var(--text-xs);color:var(--color-text-faint)">Total Tagihan</p>
        <p style="font-size:1.5rem;font-weight:800;color:var(--color-accent)">
          Rp <?= number_format($sewa['total_bayar'],0,',','.') ?>
        </p>
        <?php if ($total_denda_pp > 0): ?>
        <p style="font-size:var(--text-xs);color:#c2410c">termasuk denda Rp <?= number_format($total_denda_pp,0,',','.') ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Status bukti jika sudah upload -->
  <?php if ($bukti_ada): ?>
  <div class="alert <?= $bukti_ada['status'] === 'ditolak' ? 'alert-error' : 'alert-warning' ?>" style="margin-bottom:var(--space-5)">
    <?php if ($bukti_ada['status'] === 'menunggu'): ?>
    ⏳ Bukti transfer Anda sudah diupload dan <strong>sedang diverifikasi admin</strong>. Harap tunggu.
    <?php elseif ($bukti_ada['status'] === 'ditolak'): ?>
    ❌ Bukti transfer sebelumnya <strong>ditolak admin</strong>. Silakan upload ulang bukti yang benar.
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- DENDA SECTION -->
  <?php if (!empty($denda_pp)): ?>
  <div class="card" style="margin-bottom:var(--space-5);border:1.5px solid #fed7aa">
    <div class="card-header" style="background:#fff7ed">
      <div class="card-title" style="color:#c2410c">⚠️ Rincian Denda / Biaya Tambahan</div>
    </div>
    <?php foreach ($denda_pp as $d):
      $dicons = ['kerusakan'=>'🔧','keterlambatan'=>'⏰','bbm_kurang'=>'⛽','kehilangan'=>'🔑','lainnya'=>'📋'];
    ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:var(--space-3) var(--space-5);border-bottom:1px solid #fed7aa;font-size:var(--text-sm);flex-wrap:wrap;gap:var(--space-2)">
      <div>
        <span><?= ($dicons[$d['tipe_denda']] ?? '📋') ?> <strong><?= ucfirst(str_replace('_',' ',$d['tipe_denda'])) ?></strong></span>
        <?php if ($d['deskripsi']): ?><div style="font-size:11px;color:#92400e"><?= htmlspecialchars($d['deskripsi']) ?></div><?php endif; ?>
      </div>
      <strong style="color:#c2410c">Rp <?= number_format($d['jumlah'],0,',','.') ?></strong>
    </div>
    <?php endforeach; ?>
    <div style="display:flex;justify-content:space-between;padding:var(--space-3) var(--space-5);background:#fff7ed;font-weight:800;color:#9a3412">
      <span>Total Denda</span><span>Rp <?= number_format($total_denda_pp,0,',','.') ?></span>
    </div>
  </div>
  <?php endif; ?>

  <!-- Pilihan Metode Pembayaran -->
  <div class="card" style="margin-bottom:var(--space-5)">
    <div class="card-header">
      <div class="card-title">💳 Pilih Metode Pembayaran</div>
    </div>
    
    <div style="display:flex;flex-direction:column;gap:var(--space-3)">
      <!-- Opsi Midtrans -->
      <a href="midtrans_payment.php?id=<?= $id_sewa ?>&jenis=<?= $jenis ?>" 
         class="btn btn-primary" style="width:100%;display:flex;align-items:center;justify-content:center;gap:var(--space-2)">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
          <line x1="1" y1="10" x2="23" y2="10"></line>
        </svg>
        🚀 Bayar dengan Midtrans (Instant)
      </a>
      
      <div style="text-align:center;color:var(--color-text-muted);font-size:var(--text-xs)">atau</div>
    </div>
  </div>

  <!-- Info Pembayaran -->
  <div class="card" style="margin-bottom:var(--space-5)">
    <div class="card-header">
      <div class="card-title">💳 Pembayaran Online</div>
    </div>
    
    <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:var(--radius-lg);padding:var(--space-4);margin-bottom:var(--space-5)">
      <p style="font-size:var(--text-sm);color:#166534;margin-bottom:6px;font-weight:700">✅ Pembayaran Instan:</p>
      <ul style="font-size:var(--text-sm);color:#166534;line-height:1.8;padding-left:1.2rem">
        <li>Pembayaran diproses <strong>secara otomatis</strong> dan langsung terverifikasi</li>
        <li>Tidak perlu upload bukti transfer manual</li>
        <li>Invoice otomatis dibuat setelah pembayaran berhasil</li>
      </ul>
    </div>
  </div>

  <div style="display:flex;gap:var(--space-3);flex-wrap:wrap">
    <a href="riwayat_sewa.php" class="btn btn-ghost">← Riwayat Sewa</a>
    <a href="invoice.php?id=<?= $id_sewa ?>&jenis=<?= $jenis ?>" target="_blank" class="btn btn-ghost btn-sm">
      📄 Lihat Invoice
    </a>
  </div>
</div>

<script>
const input   = document.getElementById('bukti-input');
const zone    = document.getElementById('drop-zone');

function processFile(file) {
  if (!file) return;
  if (file.size > 5 * 1024 * 1024) { alert('File terlalu besar. Maks 5MB.'); return; }

  document.getElementById('drop-text').innerHTML = '<strong>' + file.name + '</strong>';
  const fi = document.getElementById('file-info');
  fi.textContent = 'Ukuran: ' + (file.size/1024).toFixed(1) + ' KB';
  fi.style.display = 'block';
  zone.style.borderColor = '#22c55e';
  zone.style.background  = '#f0fdf4';

  if (file.type.startsWith('image/')) {
    const r = new FileReader();
    r.onload = e => {
      document.getElementById('preview-img').src = e.target.result;
      document.getElementById('drop-preview').style.display = 'block';
      document.getElementById('icon-wrap').style.display = 'none';
    };
    r.readAsDataURL(file);
  }
}

if (input) {
  input.addEventListener('change', () => { if (input.files[0]) processFile(input.files[0]); });
}

function handleDragOver(e) {
  e.preventDefault();
  zone.style.borderColor = '#111';
}
function handleDragLeave() {
  zone.style.borderColor = '';
}
function handleDrop(e) {
  e.preventDefault();
  zone.style.borderColor = '';
  if (e.dataTransfer.files[0]) {
    const dt = new DataTransfer();
    dt.items.add(e.dataTransfer.files[0]);
    input.files = dt.files;
    processFile(e.dataTransfer.files[0]);
  }
}

function copyNorek(val) {
  navigator.clipboard.writeText(val).then(() => {
    const btn = document.getElementById('copy-btn');
    btn.textContent = '✅ Tersalin!';
    setTimeout(() => btn.textContent = '📋 Salin Nomor Rekening', 2000);
  });
}

const form = document.querySelector('form');
if (form) {
  form.addEventListener('submit', function(e) {
    if (!input.files || !input.files[0]) {
      e.preventDefault();
      alert('Pilih file bukti transfer dulu!');
      return;
    }
    const btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.textContent = '⏳ Mengupload...';
  });
}
</script>

<?php include '_layout_footer.php'; ?>

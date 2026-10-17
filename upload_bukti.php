<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user'])) { header('Location: login.php'); exit; }
require_once 'koneksi.php';
require_once 'notif_helper.php';
require_once __DIR__ . '/config/storage.php';

$id_sewa = intval($_POST['id_sewa'] ?? $_GET['id'] ?? 0);
$jenis   = ($_POST['jenis'] ?? $_GET['jenis'] ?? 'mobil') === 'motor' ? 'motor' : 'mobil';
$id_user = (int)$_SESSION['id_user'];
$msg_success = $msg_error = '';

// Rekening tujuan
$bank_nama   = 'SeaBank';
$bank_norek  = '901941949043';
$bank_atasnm = 'Ahmad Noval Ramadhan';
$bank_logo   = 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/13/SeaBank_Indonesia_logo.png/200px-SeaBank_Indonesia_logo.png';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bukti'])) {
    $file   = $_FILES['bukti'];
    $ext    = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed= ['jpg','jpeg','png','pdf','webp'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errs = [
            UPLOAD_ERR_INI_SIZE   => 'File terlalu besar (melebihi batas server).',
            UPLOAD_ERR_FORM_SIZE  => 'File terlalu besar.',
            UPLOAD_ERR_PARTIAL    => 'File hanya terupload sebagian. Coba lagi.',
            UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang dipilih.',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara tidak ditemukan di server.',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
        ];
        $msg_error = $errs[$file['error']] ?? 'Error upload (kode: '.$file['error'].')';
    } elseif (!in_array($ext, $allowed)) {
        $msg_error = 'Format tidak didukung. Gunakan JPG, PNG, WEBP, atau PDF.';
    } elseif ($file['size'] > 4 * 1024 * 1024) {
        $msg_error = 'File terlalu besar. Maksimal 4MB.';
    } elseif ($file['size'] === 0) {
        $msg_error = 'File kosong.';
    } else {
        $table = $jenis === 'mobil' ? 'log_penyewaan' : 'log_sewa_motor';
        $owned = db_fetch_assoc(db_query($conn, "SELECT id_sewa FROM {$table} WHERE id_sewa=? AND id_user=?", [$id_sewa, $id_user]));
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $types = ['jpg'=>'image/jpeg', 'jpeg'=>'image/jpeg', 'png'=>'image/png', 'webp'=>'image/webp', 'pdf'=>'application/pdf'];
        if (!$owned) {
            $msg_error = 'Pesanan tidak ditemukan.';
        } elseif (($types[$ext] ?? '') !== $mime) {
            $msg_error = 'Isi file tidak sesuai format yang diizinkan.';
        } else {
            $filename = 'bukti_'.$jenis.'_'.$id_sewa.'_'.time().'_'.bin2hex(random_bytes(8)).'.'.$ext;
            try {
                storage_upload($file['tmp_name'], $filename, $mime);
                db_begin_transaction($conn);
                db_query($conn, "INSERT INTO bukti_bayar (id_sewa,jenis,id_user,file_path,catatan) VALUES (?,?,?,?,?)",
                    [$id_sewa, $jenis, $id_user, 'uploads/bukti/'.$filename, trim($_POST['catatan'] ?? '')]);
                db_query($conn, "UPDATE {$table} SET payment_status='menunggu_konfirmasi' WHERE id_sewa=? AND id_user=?", [$id_sewa, $id_user]);
                notif_admin($conn, 'pembayaran', $_SESSION['nama_lengkap']." mengupload bukti transfer untuk sewa #{$id_sewa} ({$jenis}). Harap verifikasi.", $id_sewa, $jenis);
                db_commit($conn);
                header('Location: payment_page.php?id='.$id_sewa.'&jenis='.$jenis.'&uploaded=1'); exit;
            } catch (Throwable $e) {
                db_rollback($conn);
                error_log('Receipt upload failed (' . $e->getCode() . ').');
                $msg_error = 'Bukti pembayaran gagal disimpan. Periksa konfigurasi Supabase Storage atau coba lagi.';
            }
        }
    }
}

$page_title  = 'Upload Bukti Transfer';
$active_menu = 'riwayat';
include '_layout.php';
?>

<?php if ($msg_success): ?>
<div class="alert alert-success">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
  <?= htmlspecialchars($msg_success) ?>
</div>
<div style="text-align:center;margin-top:var(--space-4)">
  <a href="riwayat_sewa.php" class="btn btn-primary">← Lihat Riwayat Sewa</a>
</div>
<?php else: ?>

<?php if ($msg_error): ?>
<div class="alert alert-error">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
  <?= htmlspecialchars($msg_error) ?>
</div>
<?php endif; ?>

<div style="max-width:540px;margin:0 auto">
  <h2 style="font-size:var(--text-xl);font-weight:800;margin-bottom:var(--space-6)">📎 Upload Bukti Pembayaran</h2>

  <div class="card">
    <div class="card-header"><div class="card-title">Booking #<?= $id_sewa ?> · <?= ucfirst($jenis) ?></div></div>

    <!-- Info rekening tujuan -->
    <div style="background:var(--color-surface);border-radius:var(--radius-lg);padding:var(--space-4);margin-bottom:var(--space-5);border:1px solid var(--color-border)">
      <p style="font-weight:700;font-size:var(--text-sm);margin-bottom:var(--space-3)">💳 Transfer ke Rekening Berikut:</p>
      <div style="display:flex;align-items:center;gap:var(--space-3);flex-wrap:wrap">
        <img src="<?= $bank_logo ?>" alt="<?= $bank_nama ?>" style="height:28px;object-fit:contain;flex-shrink:0">
        <div style="flex:1">
          <p style="font-weight:800;font-size:var(--text-base);letter-spacing:.04em"><?= $bank_norek ?></p>
          <p style="font-size:var(--text-xs);color:var(--color-text-muted)">a.n. <?= $bank_atasnm ?> (<?= $bank_nama ?>)</p>
        </div>
        <button onclick="copyNorek('<?= $bank_norek ?>')" class="btn btn-ghost btn-sm" id="copy-btn">📋 Salin</button>
      </div>
    </div>

    <form method="POST" action="upload_bukti.php" enctype="multipart/form-data">
      <input type="hidden" name="id_sewa" value="<?= $id_sewa ?>">
      <input type="hidden" name="jenis"   value="<?= $jenis ?>">

      <div class="form-group">
        <label class="form-label">Foto / Screenshot Bukti Transfer <span style="color:var(--color-error)">*</span></label>

        <div id="drop-zone"
          style="border:2px dashed var(--color-border);border-radius:var(--radius-lg);padding:2rem;text-align:center;cursor:pointer;transition:all .2s;background:var(--color-surface)"
          onclick="document.getElementById('bukti-input').click()"
          ondragover="handleDragOver(event)"
          ondragleave="handleDragLeave(event)"
          ondrop="handleDrop(event)">

          <div id="drop-preview" style="display:none;margin-bottom:var(--space-3)">
            <img id="preview-img" style="max-height:180px;max-width:100%;border-radius:8px;margin:0 auto;display:block;box-shadow:0 2px 8px rgba(0,0,0,.1)">
          </div>

          <div id="drop-icon-wrap" style="margin-bottom:var(--space-2)">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="1.5" style="margin:0 auto;display:block">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
              <polyline points="17 8 12 3 7 8"/>
              <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
          </div>
          <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:4px" id="drop-text">
            <strong>Klik untuk memilih</strong> atau seret file ke sini
          </p>
          <p style="font-size:var(--text-xs);color:var(--color-text-faint)">JPG, PNG, WEBP, PDF · Maks 4MB</p>
        </div>

        <input type="file" name="bukti" id="bukti-input" accept="image/*,.pdf" required style="display:none">
        <p id="file-info" style="font-size:var(--text-xs);color:var(--color-text-muted);margin-top:6px;display:none"></p>
      </div>

      <div class="form-group">
        <label class="form-label">Catatan <span style="color:var(--color-text-faint);font-weight:400">(opsional)</span></label>
        <textarea name="catatan" class="form-input" rows="2"
          placeholder="Contoh: Transfer dari BCA atas nama Budi, jam 14.30"></textarea>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%" id="submit-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
          <polyline points="17 8 12 3 7 8"/>
          <line x1="12" y1="3" x2="12" y2="15"/>
        </svg>
        Upload Bukti & Beritahu Admin
      </button>
    </form>
  </div>

  <div style="text-align:center;margin-top:var(--space-4)">
    <a href="riwayat_sewa.php" class="btn btn-ghost btn-sm">← Kembali ke Riwayat</a>
  </div>
</div>

<?php endif; ?>

<script>
const input     = document.getElementById('bukti-input');
const zone      = document.getElementById('drop-zone');
const preview   = document.getElementById('drop-preview');
const prevImg   = document.getElementById('preview-img');
const iconWrap  = document.getElementById('drop-icon-wrap');
const dropText  = document.getElementById('drop-text');
const fileInfo  = document.getElementById('file-info');

function processFile(file) {
  if (!file) return;
  const maxSize = 4 * 1024 * 1024;
  if (file.size > maxSize) { alert('File terlalu besar. Maks 4MB.'); return; }
  if (file.size === 0) { alert('File kosong.'); return; }

  dropText.innerHTML = '<strong>' + file.name + '</strong>';
  fileInfo.textContent = 'Ukuran: ' + (file.size / 1024).toFixed(1) + ' KB · Klik untuk ganti';
  fileInfo.style.display = 'block';
  zone.style.borderColor = '#22c55e';
  zone.style.background  = '#f0fdf4';

  if (file.type.startsWith('image/')) {
    const reader = new FileReader();
    reader.onload = e => {
      prevImg.src = e.target.result;
      preview.style.display = 'block';
      iconWrap.style.display = 'none';
    };
    reader.readAsDataURL(file);
  } else {
    preview.style.display = 'none';
    iconWrap.innerHTML = '<div style="font-size:2.5rem;margin-bottom:4px">📄</div>';
  }
}

input.addEventListener('change', () => { if (input.files[0]) processFile(input.files[0]); });

function handleDragOver(e) {
  e.preventDefault();
  zone.style.borderColor = '#111';
  zone.style.background  = '#f8f8f8';
}
function handleDragLeave() {
  zone.style.borderColor = '';
  zone.style.background  = '';
}
function handleDrop(e) {
  e.preventDefault();
  zone.style.borderColor = '';
  zone.style.background  = '';
  if (e.dataTransfer.files && e.dataTransfer.files[0]) {
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
    setTimeout(() => btn.textContent = '📋 Salin', 2000);
  });
}

document.querySelector('form').addEventListener('submit', function(e) {
  if (!input.files || !input.files[0]) {
    e.preventDefault();
    alert('Pilih file bukti transfer terlebih dahulu.');
    return;
  }
  const btn = document.getElementById('submit-btn');
  btn.disabled = true;
  btn.innerHTML = '⏳ Mengupload...';
});
</script>

<?php include '_layout_footer.php'; ?>

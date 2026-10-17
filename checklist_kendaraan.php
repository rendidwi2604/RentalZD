<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user'])) { header('Location: login.php'); exit; }
require_once 'koneksi.php';
if (file_exists('notif_helper.php')) require_once 'notif_helper.php';

$msg_success = $msg_error = '';

// Save checklist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_checklist'])) {
    $id_sewa    = intval($_POST['id_sewa']);
    $jenis      = $_POST['jenis'] ?? 'mobil';
    $tipe       = $_POST['tipe'] ?? 'keluar';
    $eksterior  = $_POST['kondisi_eksterior'] ?? 'baik';
    $interior   = $_POST['kondisi_interior']  ?? 'baik';
    $mesin      = $_POST['kondisi_mesin']     ?? 'baik';
    $ban        = $_POST['kondisi_ban']       ?? 'baik';
    $bbm        = $_POST['bbm_level']         ?? 'penuh';
    $km         = intval($_POST['km_odometer'] ?? 0);
    $catatan    = db_real_escape_string($conn, trim($_POST['catatan'] ?? ''));
    $foto_urls  = db_real_escape_string($conn, trim($_POST['foto_urls'] ?? ''));
    $input_by   = $_SESSION['id_user'];

    // Cek apakah sudah ada checklist jenis ini untuk id_sewa ini
    $existing = db_fetch_assoc(db_query($conn,
        "SELECT id_checklist FROM checklist_kendaraan WHERE id_sewa='$id_sewa' AND jenis='$jenis' AND tipe='$tipe'"));

    if ($existing) {
        $cid = $existing['id_checklist'];
        db_query($conn, "UPDATE checklist_kendaraan SET
            kondisi_eksterior='$eksterior', kondisi_interior='$interior',
            kondisi_mesin='$mesin', kondisi_ban='$ban', bbm_level='$bbm',
            km_odometer='$km', catatan='$catatan', foto_urls='$foto_urls', input_by='$input_by'
            WHERE id_checklist='$cid'");
        $msg_success = "Checklist berhasil diupdate.";
    } else {
        db_query($conn, "INSERT INTO checklist_kendaraan
            (id_sewa,jenis,tipe,kondisi_eksterior,kondisi_interior,kondisi_mesin,kondisi_ban,bbm_level,km_odometer,catatan,foto_urls,input_by)
            VALUES ('$id_sewa','$jenis','$tipe','$eksterior','$interior','$mesin','$ban','$bbm','$km','$catatan','$foto_urls','$input_by')");
        $msg_success = "Checklist berhasil disimpan.";
    }
}

// Get active rentals for selection
if ($_SESSION['role'] === 'admin') {
    $q_aktif_mobil = db_query($conn, "SELECT lp.id_sewa, u.nama_lengkap, m.nama_mobil, m.merk, lp.tanggal_sewa, lp.tanggal_kembali, 'mobil' AS jenis
        FROM log_penyewaan lp LEFT JOIN app_users u ON lp.id_user=u.id_user LEFT JOIN mobil m ON lp.id_mobil=m.id_mobil
        WHERE lp.status='aktif' AND lp.admin_confirmed=1 ORDER BY lp.tanggal_sewa DESC");
    $q_aktif_motor = db_query($conn, "SELECT ls.id_sewa, u.nama_lengkap, mt.nama_motor AS nama_mobil, mt.merk, ls.tanggal_sewa, ls.tanggal_kembali, 'motor' AS jenis
        FROM log_sewa_motor ls LEFT JOIN app_users u ON ls.id_user=u.id_user LEFT JOIN motor mt ON ls.id_motor=mt.id_motor
        WHERE ls.status='aktif' AND ls.admin_confirmed=1 ORDER BY ls.tanggal_sewa DESC");
} else {
    $uid = $_SESSION['id_user'];
    $q_aktif_mobil = db_query($conn, "SELECT lp.id_sewa, u.nama_lengkap, m.nama_mobil, m.merk, lp.tanggal_sewa, lp.tanggal_kembali, 'mobil' AS jenis
        FROM log_penyewaan lp LEFT JOIN app_users u ON lp.id_user=u.id_user LEFT JOIN mobil m ON lp.id_mobil=m.id_mobil
        WHERE lp.id_user='$uid' AND lp.status='aktif' ORDER BY lp.tanggal_sewa DESC");
    $q_aktif_motor = db_query($conn, "SELECT ls.id_sewa, u.nama_lengkap, mt.nama_motor AS nama_mobil, mt.merk, ls.tanggal_sewa, ls.tanggal_kembali, 'motor' AS jenis
        FROM log_sewa_motor ls LEFT JOIN app_users u ON ls.id_user=u.id_user LEFT JOIN motor mt ON ls.id_motor=mt.id_motor
        WHERE ls.id_user='$uid' AND ls.status='aktif' ORDER BY ls.tanggal_sewa DESC");
}

$aktif_rows = [];
while ($r = db_fetch_assoc($q_aktif_mobil)) $aktif_rows[] = $r;
while ($r = db_fetch_assoc($q_aktif_motor)) $aktif_rows[] = $r;

// Load existing checklists for display
$q_checks = db_query($conn, "
    SELECT ck.*, u.nama_lengkap AS input_nama
    FROM checklist_kendaraan ck
    LEFT JOIN app_users u ON ck.input_by = u.id_user
    ORDER BY ck.created_at DESC LIMIT 50");

$page_title  = 'Checklist Kendaraan';
$active_menu = $_SESSION['role'] === 'admin' ? 'konfirmasi' : 'riwayat';
include '_layout.php';
?>

<?php if ($msg_success): ?>
<div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg><?= htmlspecialchars($msg_success) ?></div>
<?php endif; ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-6);flex-wrap:wrap;gap:var(--space-3)">
  <div>
    <h2 style="font-size:var(--text-xl);font-weight:800;letter-spacing:-.02em">Checklist Kondisi Kendaraan</h2>
    <p style="color:var(--color-text-muted);font-size:var(--text-sm)">Dokumentasi kondisi kendaraan saat keluar & kembali</p>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);margin-bottom:var(--space-6)">
  <!-- FORM -->
  <div class="card">
    <div class="card-header"><div class="card-title">Input Checklist</div></div>
    <form method="POST">
      <input type="hidden" name="save_checklist" value="1">
      
      <div class="form-group">
        <label class="form-label">Pilih Sewa Aktif</label>
        <select name="id_sewa" class="form-select" id="sewa-select" required onchange="updateJenis()">
          <option value="">— Pilih booking aktif —</option>
          <?php foreach ($aktif_rows as $a): ?>
          <option value="<?= $a['id_sewa'] ?>" data-jenis="<?= $a['jenis'] ?>">
            #<?= $a['id_sewa'] ?> · <?= htmlspecialchars($a['nama_mobil']) ?> (<?= htmlspecialchars($a['merk']) ?>) · <?= htmlspecialchars($a['nama_lengkap']) ?> · <?= $a['tanggal_sewa'] ?>→<?= $a['tanggal_kembali'] ?>
          </option>
          <?php endforeach; ?>
        </select>
        <?php if (empty($aktif_rows)): ?>
        <p style="color:var(--color-text-faint);font-size:var(--text-xs);margin-top:4px">Tidak ada sewa aktif saat ini.</p>
        <?php endif; ?>
      </div>
      <input type="hidden" name="jenis" id="jenis-field" value="mobil">

      <div class="form-group">
        <label class="form-label">Tipe Checklist</label>
        <div style="display:flex;gap:var(--space-3)">
          <label style="display:flex;align-items:center;gap:var(--space-2);cursor:pointer;flex:1;padding:var(--space-3);border:2px solid var(--color-border);border-radius:var(--radius-lg);transition:border-color .15s" id="lbl-keluar">
            <input type="radio" name="tipe" value="keluar" checked onchange="highlightTipe()">
            <div>
              <div style="font-weight:700;font-size:var(--text-sm)">🚗 Saat Keluar</div>
              <div style="font-size:11px;color:var(--color-text-faint)">Pre-rental</div>
            </div>
          </label>
          <label style="display:flex;align-items:center;gap:var(--space-2);cursor:pointer;flex:1;padding:var(--space-3);border:2px solid var(--color-border);border-radius:var(--radius-lg);transition:border-color .15s" id="lbl-kembali">
            <input type="radio" name="tipe" value="kembali" onchange="highlightTipe()">
            <div>
              <div style="font-weight:700;font-size:var(--text-sm)">🏁 Saat Kembali</div>
              <div style="font-size:11px;color:var(--color-text-faint)">Post-rental</div>
            </div>
          </label>
        </div>
      </div>

      <?php
      $items = [
        ['kondisi_eksterior', 'Eksterior', ['baik'=>'✓ Baik','lecet_ringan'=>'Lecet Ringan','lecet_parah'=>'Lecet Parah','penyok'=>'Penyok']],
        ['kondisi_interior',  'Interior',  ['baik'=>'✓ Baik','kotor'=>'Kotor','rusak_ringan'=>'Rusak Ringan','rusak_parah'=>'Rusak Parah']],
        ['kondisi_mesin',     'Mesin',     ['baik'=>'✓ Baik','suara_aneh'=>'Suara Aneh','perlu_servis'=>'Perlu Servis','rusak'=>'Rusak']],
        ['kondisi_ban',       'Ban',       ['baik'=>'✓ Baik','aus_ringan'=>'Aus Ringan','aus_parah'=>'Aus Parah','bocor'=>'Bocor']],
        ['bbm_level',         'BBM',       ['penuh'=>'Penuh','3/4'=>'3/4','1/2'=>'½','1/4'=>'¼','hampir_kosong'=>'Hampir Kosong']],
      ];
      foreach ($items as [$name, $label, $opts]):
      ?>
      <div class="form-group">
        <label class="form-label"><?= $label ?></label>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <?php foreach ($opts as $val => $lbl): ?>
          <label style="cursor:pointer">
            <input type="radio" name="<?= $name ?>" value="<?= $val ?>" <?= $val==='baik'||$val==='penuh' ? 'checked' : '' ?> style="display:none" class="kondisi-radio">
            <span class="kondisi-chip" data-name="<?= $name ?>"><?= $lbl ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>

      <div class="form-group">
        <label class="form-label">Km Odometer</label>
        <input type="number" name="km_odometer" class="form-input" placeholder="Contoh: 45320" min="0">
      </div>

      <div class="form-group">
        <label class="form-label">Catatan</label>
        <textarea name="catatan" class="form-input" rows="3" placeholder="Deskripsi kondisi tambahan, keluhan, dll..." style="resize:vertical"></textarea>
      </div>

      <div class="form-group">
        <label class="form-label">URL Foto Dokumentasi</label>
        <textarea name="foto_urls" class="form-input" rows="2" placeholder="Isi link foto (pisahkan dengan koma jika lebih dari satu)" style="resize:vertical"></textarea>
        <p style="color:var(--color-text-faint);font-size:var(--text-xs);margin-top:4px">Upload foto ke Google Drive / ImgBB lalu paste link-nya di sini.</p>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
        Simpan Checklist
      </button>
    </form>
  </div>

  <!-- HISTORY -->
  <div class="card">
    <div class="card-header"><div class="card-title">Riwayat Checklist</div></div>
    <div style="display:flex;flex-direction:column;gap:var(--space-3);max-height:600px;overflow-y:auto">
      <?php if (db_num_rows($q_checks) === 0): ?>
      <div class="empty-state" style="padding:var(--space-8)">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
        <p>Belum ada checklist.</p>
      </div>
      <?php else: db_data_seek($q_checks, 0); while ($ck = db_fetch_assoc($q_checks)): ?>
      <div style="padding:var(--space-4);background:var(--color-surface);border-radius:var(--radius-lg);border:1px solid var(--color-border)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-3)">
          <div>
            <span style="font-weight:700;font-size:var(--text-sm)">Booking #<?= $ck['id_sewa'] ?></span>
            <span class="badge <?= $ck['tipe']==='keluar' ? 'badge-warning' : 'badge-success' ?>" style="margin-left:8px"><?= $ck['tipe']==='keluar' ? '🚗 Keluar' : '🏁 Kembali' ?></span>
          </div>
          <span style="font-size:var(--text-xs);color:var(--color-text-faint)"><?= date('d M Y H:i', strtotime($ck['created_at'])) ?></span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:var(--space-2);font-size:11px;margin-bottom:var(--space-3)">
          <?php
          $vals = [
            'Eksterior' => $ck['kondisi_eksterior'],
            'Interior'  => $ck['kondisi_interior'],
            'Mesin'     => $ck['kondisi_mesin'],
            'Ban'       => $ck['kondisi_ban'],
            'BBM'       => $ck['bbm_level'],
            'Km'        => number_format($ck['km_odometer'],0,',','.') . ' km',
          ];
          foreach ($vals as $k => $v):
            $color = (in_array($v, ['baik','penuh'])) ? '#2d6a4f' : (in_array($v, ['lecet_parah','rusak_parah','aus_parah','bocor','rusak']) ? '#c1121f' : '#b45309');
          ?>
          <div style="background:#fff;border-radius:6px;padding:6px 8px;border:1px solid var(--color-divider)">
            <div style="color:var(--color-text-faint);margin-bottom:2px"><?= $k ?></div>
            <div style="font-weight:700;color:<?= $color ?>"><?= htmlspecialchars(str_replace('_',' ',$v)) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php if ($ck['catatan']): ?>
        <p style="font-size:var(--text-xs);color:var(--color-text-muted);margin-bottom:var(--space-2)">📝 <?= htmlspecialchars($ck['catatan']) ?></p>
        <?php endif; ?>
        <?php if ($ck['foto_urls']): ?>
        <div style="display:flex;flex-wrap:wrap;gap:6px">
          <?php foreach (explode(',', $ck['foto_urls']) as $url): $url = trim($url); if (!$url) continue; ?>
          <a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener" style="font-size:11px;color:var(--color-accent);text-decoration:none">📷 Foto</a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <p style="font-size:11px;color:var(--color-text-faint);margin-top:6px">Oleh: <?= htmlspecialchars($ck['input_nama'] ?? 'N/A') ?></p>
      </div>
      <?php endwhile; endif; ?>
    </div>
  </div>
</div>

<style>
.kondisi-chip{display:inline-block;padding:6px 14px;border-radius:999px;border:2px solid var(--color-border);font-size:var(--text-xs);font-weight:600;cursor:pointer;transition:all .15s;background:#fff}
.kondisi-radio:checked+.kondisi-chip{border-color:var(--color-primary);background:var(--color-primary);color:#fff}
</style>
<script>
function updateJenis(){
  const sel = document.getElementById('sewa-select');
  const opt = sel.options[sel.selectedIndex];
  document.getElementById('jenis-field').value = opt.getAttribute('data-jenis') || 'mobil';
}
function highlightTipe(){
  const radios = document.querySelectorAll('input[name=tipe]');
  document.getElementById('lbl-keluar').style.borderColor = radios[0].checked ? '#111' : 'var(--color-border)';
  document.getElementById('lbl-kembali').style.borderColor = radios[1].checked ? '#111' : 'var(--color-border)';
}
highlightTipe();
</script>
<?php include '_layout_footer.php'; ?>

<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user'])) { header('Location: login.php'); exit; }
require_once 'koneksi.php';
require_once 'notif_helper.php';

$msg_success = $msg_error = '';
$id_user = $_SESSION['id_user'];

// Submit review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $id_sewa      = intval($_POST['id_sewa']);
    $jenis        = $_POST['jenis'] ?? 'mobil';
    $id_kendaraan = intval($_POST['id_kendaraan']);
    $rating       = max(1, min(5, intval($_POST['rating'])));
    $komentar     = db_real_escape_string($conn, trim($_POST['komentar'] ?? ''));

    // Cek ownership
    if ($jenis === 'mobil') {
        $own = db_fetch_assoc(db_query($conn,
            "SELECT id_sewa FROM log_penyewaan WHERE id_sewa='$id_sewa' AND id_user='$id_user' AND status='selesai'"));
    } else {
        $own = db_fetch_assoc(db_query($conn,
            "SELECT id_sewa FROM log_sewa_motor WHERE id_sewa='$id_sewa' AND id_user='$id_user' AND status='selesai'"));
    }

    if (!$own) {
        $msg_error = 'Tidak dapat memberikan review untuk sewa ini.';
    } else {
        $res = db_query($conn, "INSERT INTO review_kendaraan (id_sewa,id_user,id_kendaraan,jenis,rating,komentar)
            VALUES ('$id_sewa','$id_user','$id_kendaraan','$jenis','$rating','$komentar')
            ON CONFLICT (id_sewa, jenis) DO UPDATE SET rating=EXCLUDED.rating, komentar=EXCLUDED.komentar, created_at=timezone('Asia/Jakarta', CURRENT_TIMESTAMP)");
        if ($res) $msg_success = 'Review berhasil disimpan. Terima kasih!';
        else $msg_error = 'Gagal menyimpan review.';
    }
}

// Completed rentals that can be reviewed (customer)
if ($_SESSION['role'] === 'customer') {
    $q_selesai = db_query($conn, "
        SELECT lp.id_sewa, lp.id_mobil AS id_kendaraan, m.nama_mobil AS nama, m.merk, lp.tanggal_sewa, lp.tanggal_kembali, 'mobil' AS jenis,
               rv.rating AS existing_rating, rv.komentar AS existing_komentar
        FROM log_penyewaan lp
        LEFT JOIN mobil m ON lp.id_mobil=m.id_mobil
        LEFT JOIN review_kendaraan rv ON rv.id_sewa=lp.id_sewa AND rv.jenis='mobil'
        WHERE lp.id_user='$id_user' AND lp.status='selesai'
        UNION ALL
        SELECT ls.id_sewa, ls.id_motor AS id_kendaraan, mt.nama_motor AS nama, mt.merk, ls.tanggal_sewa, ls.tanggal_kembali, 'motor' AS jenis,
               rv2.rating AS existing_rating, rv2.komentar AS existing_komentar
        FROM log_sewa_motor ls
        LEFT JOIN motor mt ON ls.id_motor=mt.id_motor
        LEFT JOIN review_kendaraan rv2 ON rv2.id_sewa=ls.id_sewa AND rv2.jenis='motor'
        WHERE ls.id_user='$id_user' AND ls.status='selesai'
        ORDER BY tanggal_kembali DESC");
}

// All reviews (public, for display)
$q_all_reviews = db_query($conn, "
    SELECT rv.*, u.nama_lengkap,
           COALESCE(m.nama_mobil, mt.nama_motor) AS nama_kendaraan,
           COALESCE(m.merk, mt.merk) AS merk,
           COALESCE(m.foto_mobil, mt.foto_motor) AS foto
    FROM review_kendaraan rv
    LEFT JOIN app_users u ON rv.id_user=u.id_user
    LEFT JOIN mobil m ON rv.jenis='mobil' AND rv.id_kendaraan=m.id_mobil
    LEFT JOIN motor mt ON rv.jenis='motor' AND rv.id_kendaraan=mt.id_motor
    ORDER BY rv.created_at DESC LIMIT 50");

// Rating per kendaraan (summary)
$q_summary_mobil = db_query($conn, "
    SELECT m.id_mobil, m.nama_mobil, m.merk, m.foto_mobil,
           COUNT(rv.id_review) AS jml_review, ROUND(AVG(rv.rating),1) AS avg_rating
    FROM mobil m LEFT JOIN review_kendaraan rv ON rv.id_kendaraan=m.id_mobil AND rv.jenis='mobil'
    GROUP BY m.id_mobil ORDER BY avg_rating DESC, jml_review DESC");

$q_summary_motor = db_query($conn, "
    SELECT mt.id_motor, mt.nama_motor, mt.merk, mt.foto_motor,
           COUNT(rv.id_review) AS jml_review, ROUND(AVG(rv.rating),1) AS avg_rating
    FROM motor mt LEFT JOIN review_kendaraan rv ON rv.id_kendaraan=mt.id_motor AND rv.jenis='motor'
    GROUP BY mt.id_motor ORDER BY avg_rating DESC, jml_review DESC");

$page_title  = 'Rating & Review';
$active_menu = 'riwayat';
include '_layout.php';
?>

<?php if ($msg_success): ?>
<div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg><?= htmlspecialchars($msg_success) ?></div>
<?php endif; ?>
<?php if ($msg_error): ?>
<div class="alert alert-error"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg><?= htmlspecialchars($msg_error) ?></div>
<?php endif; ?>

<h2 style="font-size:var(--text-xl);font-weight:800;letter-spacing:-.02em;margin-bottom:var(--space-2)">Rating & Review Kendaraan</h2>
<p style="color:var(--color-text-muted);font-size:var(--text-sm);margin-bottom:var(--space-6)">Pendapat pelanggan nyata untuk keputusan booking yang lebih baik</p>

<!-- TABS -->
<div style="display:flex;gap:var(--space-2);margin-bottom:var(--space-6);flex-wrap:wrap">
  <button class="btn btn-primary" onclick="showTab('tab-beri')" id="btn-beri">
    <?= $_SESSION['role']==='customer' ? '⭐ Beri Review' : '⭐ Semua Review' ?>
  </button>
  <button class="btn btn-ghost" onclick="showTab('tab-ranking')" id="btn-ranking">🏆 Ranking Kendaraan</button>
</div>

<!-- TAB: Beri Review / Semua Review -->
<div id="tab-beri">
  <?php if ($_SESSION['role'] === 'customer'): ?>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);margin-bottom:var(--space-6)">
    <!-- Form -->
    <div class="card">
      <div class="card-header"><div class="card-title">Tulis Review</div></div>
      <form method="POST" id="review-form">
        <input type="hidden" name="submit_review" value="1">
        <input type="hidden" name="id_sewa" id="r-id-sewa">
        <input type="hidden" name="jenis" id="r-jenis">
        <input type="hidden" name="id_kendaraan" id="r-id-kendaraan">

        <div class="form-group">
          <label class="form-label">Pilih Sewa Selesai</label>
          <select class="form-select" onchange="fillReviewForm(this)" required>
            <option value="">— Pilih sewa —</option>
            <?php while ($s = db_fetch_assoc($q_selesai)): ?>
            <option value="<?= $s['id_sewa'] ?>" 
              data-jenis="<?= $s['jenis'] ?>"
              data-idkendaraan="<?= $s['id_kendaraan'] ?>"
              data-existing-rating="<?= $s['existing_rating'] ?? '' ?>"
              data-existing-komentar="<?= htmlspecialchars($s['existing_komentar'] ?? '') ?>">
              <?= htmlspecialchars($s['nama']) ?> (<?= $s['merk'] ?>) · <?= $s['tanggal_sewa'] ?>→<?= $s['tanggal_kembali'] ?>
              <?= $s['existing_rating'] ? ' ✓ Sudah direview' : '' ?>
            </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Rating</label>
          <div id="star-container" style="display:flex;gap:8px;margin-bottom:var(--space-2)">
            <?php for ($i=1;$i<=5;$i++): ?>
            <label style="cursor:pointer;font-size:28px;line-height:1" title="<?= $i ?> bintang">
              <input type="radio" name="rating" value="<?= $i ?>" style="display:none" <?= $i===5?'checked':'' ?> onchange="updateStars()">
              <span class="star" data-val="<?= $i ?>">★</span>
            </label>
            <?php endfor; ?>
          </div>
          <span id="star-label" style="font-size:var(--text-sm);color:var(--color-text-muted)">5 — Luar Biasa</span>
        </div>

        <div class="form-group">
          <label class="form-label">Komentar</label>
          <textarea name="komentar" id="r-komentar" class="form-input" rows="4" placeholder="Ceritakan pengalaman sewa Anda..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%">Kirim Review</button>
      </form>
    </div>
    <!-- Recent reviews -->
    <div class="card">
      <div class="card-header"><div class="card-title">Review Terbaru</div></div>
      <div style="display:flex;flex-direction:column;gap:var(--space-4);max-height:500px;overflow-y:auto">
        <?php db_data_seek($q_all_reviews, 0); $cnt=0; while ($rv = db_fetch_assoc($q_all_reviews) and $cnt<10): $cnt++; ?>
        <div style="padding:var(--space-4);background:var(--color-surface);border-radius:var(--radius-lg);border:1px solid var(--color-border)">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
            <div style="font-weight:700;font-size:var(--text-sm)"><?= htmlspecialchars($rv['nama_kendaraan']) ?> <span style="color:var(--color-text-faint);font-weight:400"><?= $rv['merk'] ?></span></div>
            <span style="color:#f59e0b;font-size:16px"><?= str_repeat('★', $rv['rating']) ?><?= str_repeat('☆', 5-$rv['rating']) ?></span>
          </div>
          <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:6px"><?= htmlspecialchars($rv['komentar']) ?></p>
          <p style="font-size:11px;color:var(--color-text-faint)">— <?= htmlspecialchars($rv['nama_lengkap']) ?> · <?= date('d M Y', strtotime($rv['created_at'])) ?></p>
        </div>
        <?php endwhile; ?>
      </div>
    </div>
  </div>
  <?php else: ?>
  <!-- Admin: semua review -->
  <div class="card">
    <div class="card-header"><div class="card-title">Semua Review</div></div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>#</th><th>Kendaraan</th><th>Customer</th><th>Rating</th><th>Komentar</th><th>Tanggal</th></tr></thead>
        <tbody>
          <?php db_data_seek($q_all_reviews, 0); $no=1; while ($rv = db_fetch_assoc($q_all_reviews)): ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><strong><?= htmlspecialchars($rv['nama_kendaraan']) ?></strong><br><small style="color:var(--color-text-faint)"><?= $rv['merk'] ?> · <?= $rv['jenis'] ?></small></td>
            <td><?= htmlspecialchars($rv['nama_lengkap']) ?></td>
            <td><span style="color:#f59e0b;font-size:18px"><?= str_repeat('★', $rv['rating']) ?></span> <small><?= $rv['rating'] ?>/5</small></td>
            <td style="max-width:200px"><?= htmlspecialchars($rv['komentar']) ?></td>
            <td style="font-size:var(--text-xs);color:var(--color-text-faint)"><?= date('d M Y', strtotime($rv['created_at'])) ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- TAB: Ranking -->
<div id="tab-ranking" style="display:none">
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6)">
    <div class="card">
      <div class="card-header"><div class="card-title">🚗 Ranking Mobil</div></div>
      <div style="display:flex;flex-direction:column;gap:var(--space-3)">
        <?php $rank=1; while ($r = db_fetch_assoc($q_summary_mobil)): ?>
        <div style="display:flex;align-items:center;gap:var(--space-3);padding:var(--space-3);background:var(--color-surface);border-radius:var(--radius-lg)">
          <div style="width:32px;height:32px;border-radius:50%;background:<?= $rank<=3?'#111':'var(--color-surface-2)' ?>;color:<?= $rank<=3?'#fff':'var(--color-text)' ?>;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:var(--text-sm);flex-shrink:0"><?= $rank++ ?></div>
          <?php if ($r['foto_mobil']): ?>
          <img src="<?= htmlspecialchars($r['foto_mobil']) ?>" style="width:48px;height:36px;object-fit:cover;border-radius:6px;flex-shrink:0">
          <?php endif; ?>
          <div style="flex:1;min-width:0">
            <div style="font-weight:700;font-size:var(--text-sm)"><?= htmlspecialchars($r['nama_mobil']) ?></div>
            <div style="font-size:11px;color:var(--color-text-faint)"><?= $r['merk'] ?></div>
          </div>
          <div style="text-align:right;flex-shrink:0">
            <div style="color:#f59e0b;font-size:16px"><?= $r['avg_rating'] ? str_repeat('★',round($r['avg_rating'])).str_repeat('☆',5-round($r['avg_rating'])) : '☆☆☆☆☆' ?></div>
            <div style="font-size:11px;color:var(--color-text-faint)"><?= $r['avg_rating'] ?: 'N/A' ?> (<?= $r['jml_review'] ?> review)</div>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><div class="card-title">🏍️ Ranking Motor</div></div>
      <div style="display:flex;flex-direction:column;gap:var(--space-3)">
        <?php $rank=1; while ($r = db_fetch_assoc($q_summary_motor)): ?>
        <div style="display:flex;align-items:center;gap:var(--space-3);padding:var(--space-3);background:var(--color-surface);border-radius:var(--radius-lg)">
          <div style="width:32px;height:32px;border-radius:50%;background:<?= $rank<=3?'#111':'var(--color-surface-2)' ?>;color:<?= $rank<=3?'#fff':'var(--color-text)' ?>;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:var(--text-sm);flex-shrink:0"><?= $rank++ ?></div>
          <?php if ($r['foto_motor']): ?>
          <img src="<?= htmlspecialchars($r['foto_motor']) ?>" style="width:48px;height:36px;object-fit:cover;border-radius:6px;flex-shrink:0">
          <?php endif; ?>
          <div style="flex:1;min-width:0">
            <div style="font-weight:700;font-size:var(--text-sm)"><?= htmlspecialchars($r['nama_motor']) ?></div>
            <div style="font-size:11px;color:var(--color-text-faint)"><?= $r['merk'] ?></div>
          </div>
          <div style="text-align:right;flex-shrink:0">
            <div style="color:#f59e0b;font-size:16px"><?= $r['avg_rating'] ? str_repeat('★',round($r['avg_rating'])).str_repeat('☆',5-round($r['avg_rating'])) : '☆☆☆☆☆' ?></div>
            <div style="font-size:11px;color:var(--color-text-faint)"><?= $r['avg_rating'] ?: 'N/A' ?> (<?= $r['jml_review'] ?> review)</div>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
    </div>
  </div>
</div>

<style>
.star{color:#d1d5db;transition:color .1s}
.star.active{color:#f59e0b}
</style>
<script>
const starLabels = {1:'1 — Buruk',2:'2 — Kurang',3:'3 — Cukup',4:'4 — Bagus',5:'5 — Luar Biasa'};
function updateStars(){
  const checked = document.querySelector('input[name=rating]:checked');
  const val = checked ? parseInt(checked.value) : 0;
  document.querySelectorAll('.star').forEach(s => {
    s.classList.toggle('active', parseInt(s.dataset.val) <= val);
  });
  document.getElementById('star-label').textContent = starLabels[val] || '';
}
function fillReviewForm(sel){
  const opt = sel.options[sel.selectedIndex];
  document.getElementById('r-id-sewa').value = opt.value;
  document.getElementById('r-jenis').value = opt.dataset.jenis || 'mobil';
  document.getElementById('r-id-kendaraan').value = opt.dataset.idkendaraan || '';
  const existingRating = opt.dataset.existingRating;
  const existingKomentar = opt.dataset.existingKomentar;
  if (existingRating) {
    const radio = document.querySelector('input[name=rating][value="'+existingRating+'"]');
    if (radio) { radio.checked = true; updateStars(); }
  }
  if (existingKomentar) document.getElementById('r-komentar').value = existingKomentar;
}
function showTab(id){
  ['tab-beri','tab-ranking'].forEach(t => document.getElementById(t).style.display = 'none');
  ['btn-beri','btn-ranking'].forEach(b => { document.getElementById(b).className = 'btn btn-ghost'; });
  document.getElementById(id).style.display = '';
  document.getElementById('btn-'+id.replace('tab-','')).className = 'btn btn-primary';
}
updateStars();
</script>
<?php include '_layout_footer.php'; ?>

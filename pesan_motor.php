<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'customer') {
    header('Location: login.php'); exit;
}
require_once 'koneksi.php';
require_once 'notif_helper.php';
$id_user = (int)$_SESSION['id_user'];

function rupiah_m($n){ return 'Rp ' . number_format((float)$n, 0, ',', '.'); }

$pesan_sukses = '';
$pesan_error  = '';

// ── BOOKING ──────────────────────────────────────────────
if (isset($_POST['booking_motor'])) {
    $id_motor        = (int)$_POST['id_motor'];
    $tanggal_sewa    = $_POST['tanggal_sewa']    ?? '';
    $tanggal_kembali = $_POST['tanggal_kembali'] ?? '';
    $payment_method  = 'Midtrans'; // Fixed ke Midtrans payment
    $addons          = $_POST['addons']          ?? [];
    $kode_promo      = strtoupper(trim($_POST['kode_promo'] ?? ''));
    $agreement       = isset($_POST['agreement']) ? 1 : 0;

    if (!$agreement) {
        $pesan_error = 'Anda harus menyetujui syarat & ketentuan sewa terlebih dahulu.';
    } else {
        $addonMap    = ['helm' => 25000, 'raincoat' => 30000, 'gps' => 50000];
        $addonLabels = ['helm' => 'Helm Extra', 'raincoat' => 'Jas Hujan', 'gps' => 'GPS'];

        $row_harga = db_fetch_assoc(db_query($conn,
            "SELECT harga_sewa, status FROM motor WHERE id_motor = '$id_motor'"));

        if (!$row_harga) {
            $pesan_error = 'Motor tidak ditemukan.';
        } else {
            // ── Real-time availability check ──
            $konflik = db_fetch_assoc(db_query($conn,
                "SELECT id_sewa FROM log_sewa_motor
                 WHERE id_motor='$id_motor' AND status='aktif' AND payment_status!='pending'
                 AND NOT (tanggal_kembali <= '$tanggal_sewa' OR tanggal_sewa >= '$tanggal_kembali')
                 LIMIT 1"));
            if ($konflik) {
                $pesan_error = 'Maaf, motor tidak tersedia untuk tanggal yang dipilih. Silakan pilih tanggal lain.';
            } else {
                $harga = (float)$row_harga['harga_sewa'];
                $start = new DateTime($tanggal_sewa);
                $end   = new DateTime($tanggal_kembali);
                $days  = max(1, $start->diff($end)->days);
                $base_total  = $days * $harga;
                $addon_total = 0;
                $picked      = [];
                foreach ($addons as $ad) {
                    if (isset($addonMap[$ad])) { $addon_total += $addonMap[$ad]; $picked[] = $addonLabels[$ad]; }
                }
                $sub_total    = $base_total + $addon_total;
                $addon_detail = implode(', ', $picked);

                // ── Promo ──
                $diskon_promo = 0;
                $kode_promo_final = '';
                if ($kode_promo) {
                    $today = date('Y-m-d');
                    $p = db_fetch_assoc(db_query($conn,
                        "SELECT * FROM promo_voucher WHERE kode='$kode_promo' AND status='aktif'
                         AND (berlaku_dari IS NULL OR berlaku_dari <= '$today')
                         AND (berlaku_sampai IS NULL OR berlaku_sampai >= '$today')
                         AND sudah_dipakai < max_penggunaan LIMIT 1"));
                    if ($p && $sub_total >= (float)$p['min_transaksi']) {
                        $diskon_promo = $p['tipe_diskon'] === 'persen'
                            ? round($sub_total * $p['nilai_diskon'] / 100)
                            : min($p['nilai_diskon'], $sub_total);
                        $kode_promo_final = $kode_promo;
                        db_query($conn, "UPDATE promo_voucher SET sudah_dipakai=sudah_dipakai+1 WHERE kode='$kode_promo'");
                    }
                }
                $total_bayar = max(0, $sub_total - $diskon_promo);
                $pm  = db_real_escape_string($conn, $payment_method);
                $ad  = db_real_escape_string($conn, $addon_detail);
                $kp  = db_real_escape_string($conn, $kode_promo_final);


                db_begin_transaction($conn);
                try {
                    db_query($conn, "INSERT INTO log_sewa_motor
                        (id_user, id_motor, tanggal_sewa, tanggal_kembali, total_bayar,
                         status, payment_status, payment_method, addon_total, addon_detail,
                         kode_promo, diskon_promo, agreement_accepted, agreement_at)
                        VALUES ('$id_user','$id_motor','$tanggal_sewa','$tanggal_kembali','$total_bayar',
                                'aktif','pending','$pm','$addon_total','$ad',
                                " . ($kp ? "'$kp'" : "NULL") . ",'$diskon_promo','1',timezone('Asia/Jakarta', CURRENT_TIMESTAMP))");
                    $new_id = db_insert_id($conn);
                    db_query($conn, "UPDATE motor SET status='disewa' WHERE id_motor='$id_motor'");
                    $nama_motor_r = db_fetch_assoc(db_query($conn, "SELECT nama_motor FROM motor WHERE id_motor='$id_motor'"));
                    $nama_cust    = db_real_escape_string($conn, $_SESSION['nama_lengkap']);
                    $nama_mot_esc = db_real_escape_string($conn, $nama_motor_r['nama_motor'] ?? '');
                    $pesan_notif  = db_real_escape_string($conn, "Pesanan baru dari $nama_cust: $nama_mot_esc ($tanggal_sewa s/d $tanggal_kembali) — Rp " . number_format($total_bayar,0,',','.'));
                    db_query($conn, "INSERT INTO notifikasi_admin (tipe, id_sewa, jenis, pesan) VALUES ('pesanan_baru','$new_id','motor','$pesan_notif')");
                    notif_customer($conn, $id_user, 'info',
                        "🏍️ Booking motor {$nama_mot_esc} ({$tanggal_sewa} s/d {$tanggal_kembali}) berhasil dibuat! Menunggu konfirmasi admin.",
                        $new_id, 'motor', "payment_page.php?id={$new_id}&jenis=motor"
                    );
                    db_commit($conn);
                    header("Location: midtrans_payment.php?id={$new_id}&jenis=motor"); exit;
                } catch (Exception $e) {
                    db_rollback($conn);
                    $pesan_error = 'Terjadi kesalahan, silakan coba lagi.';
                }
            }
        }
    }
}

// ── KONFIRMASI BAYAR — arahkan ke payment_page ───────────
if (isset($_POST['confirm_payment_id'])) {
    $id_sewa = (int)$_POST['confirm_payment_id'];
    header("Location: payment_page.php?id={$id_sewa}&jenis=motor"); exit;
}


// Search & filter motor
$search_q_mt  = db_real_escape_string($conn, trim($_GET['cari'] ?? ''));
$filter_st_mt = in_array($_GET['status_filter'] ?? '', ['tersedia','disewa']) ? $_GET['status_filter'] : '';
$where_motor  = [];
if ($search_q_mt)  $where_motor[] = "(nama_motor LIKE '%$search_q_mt%' OR merk LIKE '%$search_q_mt%')";
if ($filter_st_mt) $where_motor[] = "status='$filter_st_mt'";
$where_motor_sql = $where_motor ? "WHERE ".implode(" AND ",$where_motor) : "";
$data  = db_query($conn, "SELECT * FROM motor $where_motor_sql ORDER BY CASE status WHEN 'tersedia' THEN 1 WHEN 'disewa' THEN 2 ELSE 0 END, nama_motor ASC");
$q_log = db_query($conn,
    "SELECT ls.*, m.nama_motor, m.merk, m.tahun, m.harga_sewa, COALESCE(m.foto_motor,'') AS foto_motor
     FROM log_sewa_motor ls LEFT JOIN motor m ON ls.id_motor = m.id_motor
     WHERE ls.id_user = '$id_user' ORDER BY ls.id_sewa DESC");

$page_title  = 'Sewa Motor';
$active_menu = 'pesan_motor';
include '_layout.php';
?>

<?php if ($pesan_sukses): ?>
<div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg><?= htmlspecialchars($pesan_sukses) ?></div>
<?php endif; ?>
<?php if ($pesan_error): ?>
<div class="alert alert-error"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg><?= htmlspecialchars($pesan_error) ?></div>
<?php endif; ?>

<div class="pm-hero" style="background:linear-gradient(135deg,#1a0a2e 0%,#2a1a3e 100%)">
  <div class="pm-hero__text">
    <div class="pm-chip">🏍️ Katalog Motor ZD RENT</div>
    <h1>Sewa Motor <span style="color:#E63946">Kece</span>,<br>Ringan &amp; Terpercaya</h1>
    <p>Motor pilihan untuk perjalanan harian, wisata, atau bisnis. Booking kilat, harga jelas.</p>
  </div>
  <div class="pm-hero__cards">
    <div class="pm-stat-card">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><path d="M15 6H4L2 12h14l1-6z"/></svg>
      <strong><?= db_num_rows(db_query($conn, "SELECT id_motor FROM motor WHERE LOWER(status)='tersedia'")) ?></strong>
      <span>Motor Tersedia</span>
    </div>
    <div class="pm-stat-card">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      <strong>Aman</strong>
      <span>Armada Terawat</span>
    </div>
    <div class="pm-stat-card">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      <strong>24/7</strong>
      <span>Customer Support</span>
    </div>
  </div>
</div>

<div class="section-head-pm">
  <h2>Katalog Motor</h2>
  <a href="pesan_mobil.php" class="btn btn-secondary" style="font-size:var(--text-xs)">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/><rect x="9" y="11" width="14" height="10" rx="2"/></svg>
    Lihat Mobil
  </a>
</div>

<!-- SEARCH & FILTER BAR -->
<form method="GET" action="pesan_motor.php" style="display:flex;gap:var(--space-3);margin-bottom:var(--space-5);flex-wrap:wrap;align-items:center">
  <div style="position:relative;flex:1;min-width:180px">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#aaa"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" name="cari" class="form-input" placeholder="Cari nama / merk motor..." value="<?= htmlspecialchars($_GET['cari'] ?? '') ?>" style="padding-left:36px">
  </div>
  <select name="status_filter" class="form-select" style="width:auto;min-width:130px">
    <option value="">Semua Status</option>
    <option value="tersedia" <?= ($_GET['status_filter']??'')==='tersedia'?'selected':'' ?>>✅ Tersedia</option>
    <option value="disewa"   <?= ($_GET['status_filter']??'')==='disewa'  ?'selected':'' ?>>🔴 Sedang Disewa</option>
  </select>
  <button type="submit" class="btn btn-primary btn-sm">Cari</button>
  <?php if (!empty($_GET['cari']) || !empty($_GET['status_filter'])): ?>
  <a href="pesan_motor.php" class="btn btn-ghost btn-sm">Reset</a>
  <?php endif; ?>
</form>
<?php if (!empty($_GET['cari'])): ?>
<p style="font-size:var(--text-xs);color:var(--color-text-faint);margin-bottom:var(--space-4)">
  Hasil pencarian untuk "<strong><?= htmlspecialchars($_GET['cari']) ?></strong>" — <?= db_num_rows($data) ?> motor ditemukan
</p>
<?php endif; ?>

<div class="pm-grid">
  <?php while ($motor = db_fetch_assoc($data)):
    $s = strtolower(trim($motor['status']));
    $available = $s === 'tersedia';
  ?>
  <div class="pm-car-card <?= $available ? '' : 'is-rented' ?>">
    <div class="pm-car-media">
      <?php if (!empty($motor['foto_motor'])): ?>
        <img src="<?= htmlspecialchars($motor['foto_motor']) ?>" alt="<?= htmlspecialchars($motor['nama_motor']) ?>" loading="lazy">
      <?php else: ?>
        <div class="pm-car-placeholder"><svg width="70" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><path d="M15 6H4L2 12h14l1-6z"/></svg></div>
      <?php endif; ?>
      <div class="pm-car-badge <?= $available ? 'ok' : 'busy' ?>"><?= $available ? '● Tersedia' : '● Sedang Disewa' ?></div>
    </div>
    <div class="pm-car-body">
      <div class="pm-car-name"><?= htmlspecialchars($motor['nama_motor']) ?></div>
      <div class="pm-car-sub"><?= htmlspecialchars($motor['merk']) ?> · <?= (int)$motor['tahun'] ?></div>
      <div class="pm-car-price"><?= rupiah_m($motor['harga_sewa']) ?><span>/hari</span></div>
      <button class="btn btn-primary pm-book-btn" onclick='openModal(<?= json_encode($motor, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' <?= !$available ? 'disabled' : '' ?>>
        <?= $available ? 'Booking Sekarang' : 'Tidak Tersedia' ?>
      </button>
    </div>
  </div>
  <?php endwhile; ?>
</div>

<!-- RIWAYAT -->
<div class="card" style="margin-top:var(--space-4)">
  <div class="card-header">
    <div class="card-title">Riwayat Booking Motor Saya</div>
    <span style="font-size:var(--text-xs);color:var(--color-text-faint)"><?= db_num_rows($q_log) ?> transaksi</span>
  </div>
  <?php if (db_num_rows($q_log) > 0): ?>
  <div style="display:flex;flex-direction:column;gap:0">
    <?php while ($row = db_fetch_assoc($q_log)):
      $ps = $row['payment_status'];
      $as = $row['status'];
      $confirmed = $row['admin_confirmed'] ?? 0;
    ?>
    <div style="padding:var(--space-5);border-bottom:1px solid var(--color-divider)">
      <div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-4);flex-wrap:wrap;justify-content:space-between">
        <div style="display:flex;align-items:center;gap:var(--space-3)">
          <?php if (!empty($row['foto_motor'])): ?>
          <img src="<?= htmlspecialchars($row['foto_motor']) ?>" width="52" height="36" style="object-fit:cover;border-radius:8px;border:1px solid var(--color-border)" alt="">
          <?php else: ?>
          <div style="width:52px;height:36px;background:var(--color-surface-2);border-radius:8px;display:flex;align-items:center;justify-content:center">
            <svg width="22" height="14" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5"><circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><path d="M15 6H4L2 12h14l1-6z"/></svg>
          </div>
          <?php endif; ?>
          <div>
            <div style="font-weight:800;font-size:var(--text-base)"><?= htmlspecialchars($row['nama_motor']) ?></div>
            <div style="font-size:var(--text-xs);color:var(--color-text-muted)"><?= htmlspecialchars($row['merk']) ?> · <?= (int)$row['tahun'] ?></div>
          </div>
        </div>
        <div style="display:flex;gap:var(--space-2);flex-wrap:wrap">
          <?php if (!$confirmed): ?>
          <span class="badge badge-warning">⏳ Menunggu Konfirmasi Admin</span>
          <?php else: ?>
          <span class="badge badge-success">✓ Dikonfirmasi Admin</span>
          <?php endif; ?>
          <span class="badge <?= $as === 'aktif' ? 'badge-warning' : 'badge-neutral' ?>"><?= $as === 'aktif' ? '● Aktif' : '✓ Selesai' ?></span>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:var(--space-3) var(--space-5);background:var(--color-surface);border-radius:var(--radius-lg);padding:var(--space-4);margin-bottom:var(--space-4)">
        <div>
          <div style="font-size:10px;text-transform:uppercase;color:var(--color-text-faint);font-weight:700;margin-bottom:2px">Tanggal Sewa</div>
          <div style="font-weight:600;font-size:var(--text-sm)"><?= date('d M Y', strtotime($row['tanggal_sewa'])) ?></div>
        </div>
        <div>
          <div style="font-size:10px;text-transform:uppercase;color:var(--color-text-faint);font-weight:700;margin-bottom:2px">Tanggal Kembali</div>
          <div style="font-weight:600;font-size:var(--text-sm)"><?= date('d M Y', strtotime($row['tanggal_kembali'])) ?></div>
        </div>
        <div>
          <div style="font-size:10px;text-transform:uppercase;color:var(--color-text-faint);font-weight:700;margin-bottom:2px">Total Bayar</div>
          <div style="font-weight:800;font-size:var(--text-base);color:var(--color-accent)"><?= rupiah_m($row['total_bayar']) ?></div>
        </div>
        <div>
          <div style="font-size:10px;text-transform:uppercase;color:var(--color-text-faint);font-weight:700;margin-bottom:2px">Metode</div>
          <div style="font-weight:600;font-size:var(--text-sm)"><?= htmlspecialchars($row['payment_method'] ?: 'Transfer Bank') ?></div>
        </div>
        <?php if ($row['addon_detail']): ?>
        <div>
          <div style="font-size:10px;text-transform:uppercase;color:var(--color-text-faint);font-weight:700;margin-bottom:2px">Add-on</div>
          <div style="font-size:var(--text-xs);color:var(--color-text-muted)"><?= htmlspecialchars($row['addon_detail']) ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($row['catatan_admin'])): ?>
        <div style="grid-column:1/-1">
          <div style="font-size:10px;text-transform:uppercase;color:var(--color-text-faint);font-weight:700;margin-bottom:2px">Catatan Admin</div>
          <div style="font-size:var(--text-sm);padding:var(--space-2) var(--space-3);background:#fffbeb;border:1px solid #fde68a;border-radius:var(--radius-md)"><?= htmlspecialchars($row['catatan_admin']) ?></div>
        </div>
        <?php endif; ?>
      </div>
      <div style="display:flex;align-items:center;justify-content:space-between;gap:var(--space-3);flex-wrap:wrap">
        <div>
          <?php if ($ps === 'lunas'): ?>
          <span class="badge badge-success" style="padding:.4rem .85rem">✓ Pembayaran Lunas</span>
          <?php elseif ($ps === 'menunggu_konfirmasi'): ?>
          <span class="badge badge-warning" style="padding:.4rem .85rem">🕐 Pembayaran Sedang Diverifikasi Admin</span>
          <?php else: ?>
          <span class="badge badge-neutral" style="padding:.4rem .85rem">💳 Belum Bayar</span>
          <?php endif; ?>
        </div>
        <?php if ($ps === 'pending'): ?>
        <a href="payment_page.php?id=<?= (int)$row['id_sewa'] ?>&jenis=motor" class="btn btn-primary btn-sm" style="white-space:nowrap">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            💳 Bayar Sekarang
          </a>
        <?php elseif ($ps === 'menunggu_konfirmasi'): ?>
        <span style="font-size:var(--text-xs);color:var(--color-text-muted)">Menunggu admin memverifikasi...</span>
        <?php endif; ?>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
  <?php else: ?>
  <div class="empty-state"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><path d="M15 6H4L2 12h14l1-6z"/></svg><h3>Belum ada booking motor</h3><p>Pilih motor di katalog atas!</p></div>
  <?php endif; ?>
</div>

<!-- MODAL -->
<div id="bookingModal" class="modal-shell" style="display:none">
  <div class="modal-backdrop" onclick="closeModal()"></div>
  <div class="modal-card">
    <div class="card-header">
      <div><div class="card-title" id="modalMotorName">Booking Motor</div><div style="font-size:var(--text-sm);color:var(--color-text-muted)" id="modalMotorMeta"></div></div>
      <button class="btn btn-ghost btn-sm btn-icon" onclick="closeModal()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
    </div>
    <div id="modalMotorImg" style="height:160px;background:#f8f8f8;border-radius:var(--radius-lg);overflow:hidden;margin-bottom:var(--space-5);display:flex;align-items:center;justify-content:center"></div>
    <form method="post">
      <input type="hidden" name="booking_motor" value="1">
      <input type="hidden" name="id_motor" id="modalMotorId">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Tanggal Sewa <span>*</span></label><input type="date" name="tanggal_sewa" id="tanggalSewa" class="form-input" required min="<?= date('Y-m-d') ?>" onchange="calcTotal(); checkAvailability();"></div>
        <div class="form-group"><label class="form-label">Tanggal Kembali <span>*</span></label><input type="date" name="tanggal_kembali" id="tanggalKembali" class="form-input" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>" onchange="calcTotal(); checkAvailability();"></div>
      </div>
      <div id="avail-status" style="display:none;margin-bottom:var(--space-4);padding:var(--space-3) var(--space-4);border-radius:var(--radius-lg);font-size:var(--text-sm);font-weight:600"></div>
            <div class="form-group">
        <label class="form-label">Add-on Layanan Motor</label>
        <div class="addon-list">
          <label class="addon-item"><div style="display:flex;align-items:center;gap:var(--space-3)"><input type="checkbox" name="addons[]" value="helm" onchange="calcTotal()"><div><div style="font-weight:600">Helm Extra</div><div style="font-size:var(--text-xs);color:var(--color-text-muted)">Helm SNI full-face</div></div></div><span style="font-weight:700;color:var(--color-accent)">+Rp 25.000</span></label>
          <label class="addon-item"><div style="display:flex;align-items:center;gap:var(--space-3)"><input type="checkbox" name="addons[]" value="raincoat" onchange="calcTotal()"><div><div style="font-weight:600">Jas Hujan</div><div style="font-size:var(--text-xs);color:var(--color-text-muted)">Jas hujan double layer</div></div></div><span style="font-weight:700;color:var(--color-accent)">+Rp 30.000</span></label>
          <label class="addon-item"><div style="display:flex;align-items:center;gap:var(--space-3)"><input type="checkbox" name="addons[]" value="gps" onchange="calcTotal()"><div><div style="font-weight:600">GPS Tracker</div><div style="font-size:var(--text-xs);color:var(--color-text-muted)">Navigasi real-time</div></div></div><span style="font-weight:700;color:var(--color-accent)">+Rp 50.000</span></label>
        </div>
      </div>
      <!-- Promo -->
      <div class="form-group">
        <label class="form-label">Kode Promo <span style="color:var(--color-text-faint);font-weight:400">(opsional)</span></label>
        <div style="display:flex;gap:var(--space-2)">
          <input type="text" name="kode_promo" id="kodePromo" class="form-input" placeholder="Contoh: ZDRENT10" style="text-transform:uppercase;flex:1" oninput="this.value=this.value.toUpperCase()">
          <button type="button" class="btn btn-ghost" onclick="terapkanPromo()">Terapkan</button>
        </div>
        <div id="promo-status" style="display:none;margin-top:6px;font-size:var(--text-xs);font-weight:600"></div>
      </div>
      <div class="invoice-preview">
        <div class="invoice-row"><span>Harga motor</span><span id="invBase">—</span></div>
        <div class="invoice-row"><span>Add-on</span><span id="invAddon">—</span></div>
        <div class="invoice-row" id="invPromoRow" style="display:none"><span>🎁 Diskon Promo</span><span id="invPromo" style="color:#4ade80">—</span></div>
        <div class="invoice-row total"><span>Total Estimasi</span><strong id="invTotal">Rp 0</strong></div>
      </div>
      <!-- E-Agreement -->
      <div style="margin-top:var(--space-5);padding:var(--space-4);background:var(--color-surface);border-radius:var(--radius-lg);border:1.5px solid var(--color-border)">
        <details>
          <summary style="font-weight:700;font-size:var(--text-sm);cursor:pointer;margin-bottom:var(--space-3)">📋 Syarat & Ketentuan Sewa (wajib dibaca)</summary>
          <div style="font-size:var(--text-xs);color:var(--color-text-muted);line-height:1.8;max-height:140px;overflow-y:auto">
            <p><strong>1. Identitas</strong> — Penyewa wajib berusia min. 17 tahun dan memiliki SIM C aktif.</p>
            <p><strong>2. Kondisi</strong> — Motor diserahkan dalam kondisi baik. Kerusakan selama sewa menjadi tanggung jawab penyewa.</p>
            <p><strong>3. Keterlambatan</strong> — Denda Rp 50.000/jam untuk keterlambatan pengembalian.</p>
            <p><strong>4. Larangan</strong> — Dilarang balapan, membonceng penumpang berlebih, dan memodifikasi kendaraan.</p>
            <p><strong>5. BBM</strong> — Wajib dikembalikan dengan kondisi BBM penuh seperti saat pengambilan.</p>
          </div>
        </details>
        <label style="display:flex;align-items:center;gap:var(--space-3);margin-top:var(--space-3);cursor:pointer">
          <input type="checkbox" name="agreement" id="agreementCheck" required style="width:18px;height:18px;accent-color:#111;flex-shrink:0">
          <span style="font-size:var(--text-sm);font-weight:600">Saya menyetujui syarat & ketentuan sewa</span>
        </label>
      </div>
      <div style="margin-top:var(--space-5);display:flex;gap:var(--space-3)">
        <button type="submit" class="btn btn-primary" id="btnKonfirmasi" style="flex:1" disabled>Konfirmasi Booking</button>
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Batal</button>
      </div>
    </form>
  </div>
</div>

<style>
.pm-hero{display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);color:#fff;border-radius:1.5rem;padding:clamp(1.5rem,3vw,2.5rem);margin-bottom:var(--space-6)}
.pm-chip{display:inline-flex;padding:.4rem .85rem;background:rgba(255,255,255,.1);border-radius:999px;font-size:.75rem;font-weight:700;margin-bottom:var(--space-4)}
.pm-hero h1{font-size:clamp(1.8rem,2vw+.8rem,2.8rem);line-height:1.08;letter-spacing:-.04em;margin-bottom:var(--space-4)}
.pm-hero p{color:rgba(255,255,255,.6);font-size:var(--text-sm)}
.pm-hero__cards{display:flex;flex-direction:column;gap:var(--space-3);justify-content:center}
.pm-stat-card{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.1);border-radius:1rem;padding:var(--space-4);display:flex;align-items:center;gap:var(--space-3)}
.pm-stat-card strong{font-size:1.25rem;font-weight:800}
.pm-stat-card span{font-size:var(--text-xs);color:rgba(255,255,255,.5)}
.pm-stat-card svg{flex-shrink:0;color:rgba(255,255,255,.5)}
.section-head-pm{display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-5);gap:var(--space-4)}
.section-head-pm h2{font-size:var(--text-xl);font-weight:800;letter-spacing:-.02em}
.pm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(280px,100%),1fr));gap:var(--space-5)}
.pm-car-card{background:#fff;border:1px solid var(--color-border);border-radius:1.4rem;overflow:hidden;box-shadow:var(--shadow-sm);transition:transform var(--transition),box-shadow var(--transition)}
.pm-car-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg)}
.pm-car-card.is-rented{opacity:.7}
.pm-car-media{position:relative;height:190px;background:linear-gradient(180deg,#f5f5f5,#eee);display:flex;align-items:center;justify-content:center;overflow:hidden}
.pm-car-media img{width:100%;height:100%;object-fit:cover}
.pm-car-placeholder{color:#bbb}
.pm-car-badge{position:absolute;top:12px;left:12px;padding:.4rem .75rem;border-radius:999px;font-size:11px;font-weight:700}
.pm-car-badge.ok{background:#edf8f1;color:#2d6a4f}
.pm-car-badge.busy{background:#fff1f0;color:#c1121f}
.pm-car-body{padding:var(--space-5)}
.pm-car-name{font-size:var(--text-base);font-weight:800;margin-bottom:4px}
.pm-car-sub{font-size:var(--text-xs);color:var(--color-text-muted);margin-bottom:var(--space-3)}
.pm-car-price{font-size:var(--text-lg);font-weight:800;margin-bottom:var(--space-4)}
.pm-car-price span{font-size:var(--text-xs);color:var(--color-text-muted);font-weight:500}
.pm-book-btn{width:100%}
.pm-book-btn:disabled{opacity:.5;cursor:not-allowed}
.addon-list{display:grid;gap:var(--space-2)}
.addon-item{display:flex;justify-content:space-between;align-items:center;gap:var(--space-3);padding:var(--space-3) var(--space-4);border:1.5px solid var(--color-border);border-radius:var(--radius-lg);cursor:pointer;transition:border-color var(--transition),background var(--transition)}
.addon-item:hover{border-color:var(--color-primary);background:var(--color-surface)}
.invoice-preview{background:#111;border-radius:var(--radius-lg);padding:var(--space-5);margin-top:var(--space-4)}
.invoice-row{display:flex;justify-content:space-between;align-items:center;padding:var(--space-2) 0;font-size:var(--text-sm);color:rgba(255,255,255,.65);border-bottom:1px solid rgba(255,255,255,.08)}
.invoice-row.total{color:#fff;padding-top:var(--space-3)}
.invoice-row.total strong{font-size:var(--text-lg);color:#fff}
.modal-shell{position:fixed;inset:0;z-index:220;display:flex;align-items:center;justify-content:center;padding:var(--space-4)}
.modal-backdrop{position:absolute;inset:0;background:rgba(0,0,0,.6)}
.modal-card{position:relative;z-index:1;width:100%;max-width:540px;max-height:92dvh;overflow-y:auto;background:#fff;border-radius:1.4rem;padding:var(--space-6);box-shadow:0 24px 48px rgba(0,0,0,.25)}
@media(max-width:900px){.pm-hero{grid-template-columns:1fr}}
@media(max-width:560px){.pm-hero{padding:1.25rem}.form-row{grid-template-columns:1fr}.modal-card{padding:var(--space-4)}}
</style>
<script>
let selPrice=0,selMotorId=0,promoDiskon=0,availOk=false;
function openModal(m){
  document.getElementById('bookingModal').style.display='flex';
  document.getElementById('modalMotorId').value=m.id_motor;
  document.getElementById('modalMotorName').textContent=m.nama_motor;
  document.getElementById('modalMotorMeta').textContent=m.merk+' · '+m.tahun;
  selPrice=Number(m.harga_sewa)||0; selMotorId=m.id_motor;
  const imgBox=document.getElementById('modalMotorImg');
  if(m.foto_motor){imgBox.innerHTML=`<img src="${m.foto_motor}" style="width:100%;height:100%;object-fit:cover" alt="">`;}
  else{imgBox.innerHTML=`<svg width="70" height="50" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.2"><circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><path d="M15 6H4L2 12h14l1-6z"/></svg>`;}
  document.querySelectorAll('input[name="addons[]"]').forEach(el=>el.checked=false);
  document.getElementById('kodePromo').value='';
  document.getElementById('promo-status').style.display='none';
  document.getElementById('avail-status').style.display='none';
  document.getElementById('invPromoRow').style.display='none';
  document.getElementById('agreementCheck').checked=false;
  promoDiskon=0;availOk=false;updateSubmitBtn();calcTotal();
}
function closeModal(){document.getElementById('bookingModal').style.display='none';}
let availTimer=null;
function checkAvailability(){
  const s=document.getElementById('tanggalSewa').value;
  const e=document.getElementById('tanggalKembali').value;
  const box=document.getElementById('avail-status');
  if(!s||!e||!selMotorId){box.style.display='none';availOk=false;updateSubmitBtn();return;}
  box.style.display='block';box.style.background='#f3f4f6';box.style.color='#6b7280';
  box.textContent='⏳ Memeriksa ketersediaan...';
  clearTimeout(availTimer);
  availTimer=setTimeout(()=>{
    fetch(`cek_ketersediaan.php?jenis=motor&id=${selMotorId}&tanggal_sewa=${s}&tanggal_kembali=${e}`)
      .then(r=>r.json()).then(d=>{
        box.style.display='block';
        if(d.tersedia){box.style.background='#f0fdf4';box.style.color='#16a34a';box.textContent='✓ '+(d.pesan||'Motor tersedia!');availOk=true;}
        else{box.style.background='#fef2f2';box.style.color='#dc2626';box.textContent='✗ '+d.pesan;availOk=false;}
        updateSubmitBtn();
      }).catch(()=>{availOk=true;updateSubmitBtn();});
  },600);
}
function terapkanPromo(){
  const kode=document.getElementById('kodePromo').value.trim().toUpperCase();
  const total=calcRawTotal();const box=document.getElementById('promo-status');
  if(!kode){box.style.display='none';return;}
  box.style.display='block';box.style.color='#6b7280';box.textContent='⏳ Mengecek promo...';
  const fd=new FormData();fd.append('kode',kode);fd.append('total',total);
  fetch('cek_promo.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
    if(d.valid){box.style.color='#16a34a';box.textContent='✓ '+d.pesan+' — '+d.deskripsi;promoDiskon=d.diskon;}
    else{box.style.color='#dc2626';box.textContent='✗ '+d.pesan;promoDiskon=0;}
    calcTotal();
  });
}
function calcRawTotal(){
  const s=document.getElementById('tanggalSewa').value;const e=document.getElementById('tanggalKembali').value;
  let days=1;if(s&&e){const diff=Math.ceil((new Date(e)-new Date(s))/86400000);days=diff>0?diff:1;}
  let addon=0;document.querySelectorAll('input[name="addons[]"]:checked').forEach(el=>{if(el.value==='helm')addon+=25000;if(el.value==='raincoat')addon+=30000;if(el.value==='gps')addon+=50000;});
  return days*selPrice+addon;
}
function calcTotal(){
  const s=document.getElementById('tanggalSewa').value;const e=document.getElementById('tanggalKembali').value;
  let days=1;if(s&&e){const diff=Math.ceil((new Date(e)-new Date(s))/86400000);days=diff>0?diff:1;}
  let addon=0;document.querySelectorAll('input[name="addons[]"]:checked').forEach(el=>{if(el.value==='helm')addon+=25000;if(el.value==='raincoat')addon+=30000;if(el.value==='gps')addon+=50000;});
  const base=days*selPrice;const sub=base+addon;const total=Math.max(0,sub-promoDiskon);
  const fmt=v=>'Rp '+v.toLocaleString('id-ID');
  document.getElementById('invBase').textContent=fmt(base);
  document.getElementById('invAddon').textContent=addon>0?fmt(addon):'—';
  if(promoDiskon>0){document.getElementById('invPromoRow').style.display='';document.getElementById('invPromo').textContent='−'+fmt(promoDiskon);}
  else{document.getElementById('invPromoRow').style.display='none';}
  document.getElementById('invTotal').textContent=fmt(total);
}
function updateSubmitBtn(){
  const agreed=document.getElementById('agreementCheck').checked;
  const s=document.getElementById('tanggalSewa').value;const e=document.getElementById('tanggalKembali').value;
  const btn=document.getElementById('btnKonfirmasi');const ok=agreed&&s&&e&&availOk;
  btn.disabled=!ok;btn.style.opacity=ok?'1':'0.5';
}
document.getElementById('agreementCheck').addEventListener('change',updateSubmitBtn);
document.getElementById('tanggalSewa').addEventListener('change',calcTotal);
document.getElementById('tanggalKembali').addEventListener('change',calcTotal);
document.getElementById('bookingModal').addEventListener('click',e=>{if(e.target.id==='bookingModal')closeModal();});
</script>
<?php include '_layout_footer.php'; ?>

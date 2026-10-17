<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'customer') {
    header('Location: login.php'); exit;
}
require_once 'koneksi.php';
require_once 'notif_helper.php';
$id_user = (int)$_SESSION['id_user'];

function rupiah($n){ return 'Rp ' . number_format((float)$n, 0, ',', '.'); }

$pesan_sukses = '';
$pesan_error  = '';

// ── BOOKING ──────────────────────────────────────────────
if (isset($_POST['booking_mobil'])) {
    $id_mobil        = (int)$_POST['id_mobil'];
    $tanggal_sewa    = $_POST['tanggal_sewa']    ?? '';
    $tanggal_kembali = $_POST['tanggal_kembali'] ?? '';
    $payment_method  = 'Midtrans'; // Fixed ke Midtrans payment
    $addons          = $_POST['addons']          ?? [];
    $kode_promo      = strtoupper(trim($_POST['kode_promo'] ?? ''));
    $agreement       = isset($_POST['agreement']) ? 1 : 0;

    if (!$agreement) {
        $pesan_error = 'Anda harus menyetujui syarat & ketentuan sewa terlebih dahulu.';
    } else {
        $addonMap    = ['driver' => 150000, 'child_seat' => 50000, 'airport_pickup' => 100000];
        $addonLabels = ['driver' => 'Driver', 'child_seat' => 'Child Seat', 'airport_pickup' => 'Airport Pickup'];

        $row_harga = db_fetch_assoc(db_query($conn,
            "SELECT harga_sewa, status FROM mobil WHERE id_mobil = '$id_mobil'"));

        if (!$row_harga) {
            $pesan_error = 'Mobil tidak ditemukan.';
        } else {
            // ── Real-time availability check ──
            $konflik = db_fetch_assoc(db_query($conn,
                "SELECT id_sewa FROM log_penyewaan
                 WHERE id_mobil='$id_mobil' AND status='aktif' AND payment_status!='pending'
                 AND NOT (tanggal_kembali <= '$tanggal_sewa' OR tanggal_sewa >= '$tanggal_kembali')
                 LIMIT 1"));
            if ($konflik) {
                $pesan_error = 'Maaf, kendaraan tidak tersedia untuk tanggal yang dipilih. Silakan pilih tanggal lain atau cek kalender ketersediaan.';
            } else {
                $harga = (float)$row_harga['harga_sewa'];
                $start = new DateTime($tanggal_sewa);
                $end   = new DateTime($tanggal_kembali);
                $days  = max(1, $start->diff($end)->days);
                $base_total  = $days * $harga;
                $addon_total = 0;
                $picked      = [];
                foreach ($addons as $ad) {
                    if (isset($addonMap[$ad])) {
                        $addon_total += $addonMap[$ad];
                        $picked[]    = $addonLabels[$ad];
                    }
                }
                $sub_total    = $base_total + $addon_total;
                $addon_detail = implode(', ', $picked);

                // ── Promo / Voucher ──
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

                // Kolom baru mungkin belum ada — ALTER safe

                db_begin_transaction($conn);
                try {
                    db_query($conn, "INSERT INTO log_penyewaan
                        (id_user, id_mobil, tanggal_sewa, tanggal_kembali, total_bayar,
                         status, payment_status, payment_method, addon_total, addon_detail,
                         kode_promo, diskon_promo, agreement_accepted, agreement_at)
                        VALUES
                        ('$id_user','$id_mobil','$tanggal_sewa','$tanggal_kembali','$total_bayar',
                         'aktif','pending','$pm','$addon_total','$ad',
                         " . ($kp ? "'$kp'" : "NULL") . ",'$diskon_promo','1',timezone('Asia/Jakarta', CURRENT_TIMESTAMP))");
                    $new_id = db_insert_id($conn);
                    db_query($conn, "UPDATE mobil SET status='disewa' WHERE id_mobil='$id_mobil'");
                    $nama_mobil_r = db_fetch_assoc(db_query($conn, "SELECT nama_mobil FROM mobil WHERE id_mobil='$id_mobil'"));
                    $nama_cust    = db_real_escape_string($conn, $_SESSION['nama_lengkap']);
                    $nama_mob_esc = db_real_escape_string($conn, $nama_mobil_r['nama_mobil'] ?? '');
                    $pesan_notif  = db_real_escape_string($conn, "Pesanan baru dari $nama_cust: $nama_mob_esc ($tanggal_sewa s/d $tanggal_kembali) — Rp " . number_format($total_bayar,0,',','.'));
                    db_query($conn, "INSERT INTO notifikasi_admin (tipe, id_sewa, jenis, pesan) VALUES ('pesanan_baru','$new_id','mobil','$pesan_notif')");
                    // Notifikasi ke customer
                    notif_customer($conn, $id_user, 'info',
                        "🚗 Booking mobil {$nama_mob_esc} ({$tanggal_sewa} s/d {$tanggal_kembali}) berhasil dibuat! Menunggu konfirmasi admin.",
                        $new_id, 'mobil', "payment_page.php?id={$new_id}&jenis=mobil"
                    );
                    db_commit($conn);
                    header("Location: midtrans_payment.php?id={$new_id}&jenis=mobil"); exit;
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
    header("Location: payment_page.php?id={$id_sewa}&jenis=mobil"); exit;
}

// ── DATA ─────────────────────────────────────────────────

// Search & filter
$search_q  = db_real_escape_string($conn, trim($_GET['cari'] ?? ''));
$filter_st = in_array($_GET['status_filter'] ?? '', ['tersedia','disewa']) ? $_GET['status_filter'] : '';
$where_mobil = [];
if ($search_q)  $where_mobil[] = "(nama_mobil LIKE '%$search_q%' OR merk LIKE '%$search_q%')";
if ($filter_st) $where_mobil[] = "status='$filter_st'";
$where_mobil_sql = $where_mobil ? "WHERE ".implode(" AND ",$where_mobil) : "";
$data  = db_query($conn, "SELECT * FROM mobil $where_mobil_sql ORDER BY CASE status WHEN 'tersedia' THEN 1 WHEN 'disewa' THEN 2 ELSE 0 END, nama_mobil ASC");
$q_log = db_query($conn,
    "SELECT lp.*, m.nama_mobil, m.merk, m.tahun, m.harga_sewa,
            COALESCE(m.foto_mobil,'') AS foto_mobil
     FROM log_penyewaan lp
     LEFT JOIN mobil m ON lp.id_mobil = m.id_mobil
     WHERE lp.id_user = '$id_user'
     ORDER BY lp.id_sewa DESC");

$page_title  = 'Sewa Mobil';
$active_menu = 'pesan';
include '_layout.php';
?>

<?php if ($pesan_sukses): ?>
<div class="alert alert-success">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
  <?= htmlspecialchars($pesan_sukses) ?>
</div>
<?php endif; ?>
<?php if ($pesan_error): ?>
<div class="alert alert-error">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
  <?= htmlspecialchars($pesan_error) ?>
</div>
<?php endif; ?>

<!-- HERO -->
<div class="pm-hero">
  <div class="pm-hero__text">
    <div class="pm-chip">🚗 Katalog Mobil ZD RENT</div>
    <h1>Sewa Mobil <span>Premium</span>,<br>Booking Cepat &amp; Mudah</h1>
    <p>Armada terpilih siap mengantarmu. Pilih mobil favorit, tambah layanan ekstra, konfirmasi — selesai.</p>
  </div>
  <div class="pm-hero__cards">
    <div class="pm-stat-card">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/><rect x="9" y="11" width="14" height="10" rx="2"/></svg>
      <strong><?= db_num_rows(db_query($conn, "SELECT id_mobil FROM mobil WHERE LOWER(status)='tersedia'")) ?></strong>
      <span>Mobil Tersedia</span>
    </div>
    <div class="pm-stat-card">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      <strong>24/7</strong>
      <span>Customer Support</span>
    </div>
    <div class="pm-stat-card">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      <strong>100%</strong>
      <span>Terpercaya</span>
    </div>
  </div>
</div>

<!-- KATALOG MOBIL -->
<div class="section-head-pm">
  <h2>Katalog Armada</h2>
  <a href="pesan_motor.php" class="btn btn-secondary" style="font-size:var(--text-xs)">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><path d="M15 6H4L2 12h14l1-6z"/></svg>
    Lihat Motor
  </a>
</div>

<!-- SEARCH & FILTER BAR -->
<form method="GET" action="pesan_mobil.php" style="display:flex;gap:var(--space-3);margin-bottom:var(--space-5);flex-wrap:wrap;align-items:center">
  <div style="position:relative;flex:1;min-width:180px">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#aaa"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" name="cari" class="form-input" placeholder="Cari nama / merk mobil..." value="<?= htmlspecialchars($_GET['cari'] ?? '') ?>" style="padding-left:36px">
  </div>
  <select name="status_filter" class="form-select" style="width:auto;min-width:130px">
    <option value="">Semua Status</option>
    <option value="tersedia" <?= ($_GET['status_filter']??'')==='tersedia'?'selected':'' ?>>✅ Tersedia</option>
    <option value="disewa"   <?= ($_GET['status_filter']??'')==='disewa'  ?'selected':'' ?>>🔴 Sedang Disewa</option>
  </select>
  <button type="submit" class="btn btn-primary btn-sm">Cari</button>
  <?php if (!empty($_GET['cari']) || !empty($_GET['status_filter'])): ?>
  <a href="pesan_mobil.php" class="btn btn-ghost btn-sm">Reset</a>
  <?php endif; ?>
</form>
<?php if (!empty($_GET['cari'])): ?>
<p style="font-size:var(--text-xs);color:var(--color-text-faint);margin-bottom:var(--space-4)">
  Hasil pencarian untuk "<strong><?= htmlspecialchars($_GET['cari']) ?></strong>" — <?= db_num_rows($data) ?> kendaraan ditemukan
</p>
<?php endif; ?>

<div class="pm-grid">
  <?php while ($car = db_fetch_assoc($data)):
    $s = strtolower(trim($car['status']));
    $available = $s === 'tersedia';
  ?>
  <div class="pm-car-card <?= $available ? '' : 'is-rented' ?>">
    <div class="pm-car-media">
      <?php if (!empty($car['foto_mobil'])): ?>
        <img src="<?= htmlspecialchars($car['foto_mobil']) ?>" alt="<?= htmlspecialchars($car['nama_mobil']) ?>" loading="lazy">
      <?php else: ?>
        <div class="pm-car-placeholder">
          <svg width="80" height="50" viewBox="0 0 200 100" fill="none">
            <path d="M20 60L30 35Q35 25 45 25H155Q165 25 170 35L180 60H185Q192 60 192 67V75Q192 82 185 82H15Q8 82 8 75V67Q8 60 15 60Z" fill="currentColor" opacity="0.2"/>
          </svg>
        </div>
      <?php endif; ?>
      <div class="pm-car-badge <?= $available ? 'ok' : 'busy' ?>">
        <?= $available ? '● Tersedia' : '● Sedang Disewa' ?>
      </div>
    </div>
    <div class="pm-car-body">
      <div class="pm-car-name"><?= htmlspecialchars($car['nama_mobil']) ?></div>
      <div class="pm-car-sub"><?= htmlspecialchars($car['merk']) ?> · <?= (int)$car['tahun'] ?></div>
      <div class="pm-car-price"><?= rupiah($car['harga_sewa']) ?><span>/hari</span></div>
      <button class="btn btn-primary pm-book-btn"
        onclick='openModal(<?= json_encode($car, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'
        <?= !$available ? 'disabled' : '' ?>>
        <?= $available ? 'Booking Sekarang' : 'Tidak Tersedia' ?>
      </button>
    </div>
  </div>
  <?php endwhile; ?>
</div>

<!-- RIWAYAT BOOKING -->
<div class="card" style="margin-top:var(--space-8)">
  <div class="card-header">
    <div class="card-title">Riwayat Booking Mobil Saya</div>
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
      <!-- Header baris -->
      <div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-4);flex-wrap:wrap;justify-content:space-between">
        <div style="display:flex;align-items:center;gap:var(--space-3)">
          <?php if (!empty($row['foto_mobil'])): ?>
          <img src="<?= htmlspecialchars($row['foto_mobil']) ?>" width="52" height="36" style="object-fit:cover;border-radius:8px;border:1px solid var(--color-border)" alt="">
          <?php else: ?>
          <div style="width:52px;height:36px;background:var(--color-surface-2);border-radius:8px;display:flex;align-items:center;justify-content:center">
            <svg width="22" height="14" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/><rect x="9" y="11" width="14" height="10" rx="2"/></svg>
          </div>
          <?php endif; ?>
          <div>
            <div style="font-weight:800;font-size:var(--text-base)"><?= htmlspecialchars($row['nama_mobil']) ?></div>
            <div style="font-size:var(--text-xs);color:var(--color-text-muted)"><?= htmlspecialchars($row['merk']) ?> · <?= (int)$row['tahun'] ?></div>
          </div>
        </div>
        <div style="display:flex;gap:var(--space-2);flex-wrap:wrap">
          <!-- Status konfirmasi admin -->
          <?php if (!$confirmed): ?>
          <span class="badge badge-warning">⏳ Menunggu Konfirmasi Admin</span>
          <?php else: ?>
          <span class="badge badge-success">✓ Dikonfirmasi Admin</span>
          <?php endif; ?>
          <!-- Status sewa -->
          <span class="badge <?= $as === 'aktif' ? 'badge-warning' : 'badge-neutral' ?>"><?= $as === 'aktif' ? '● Aktif' : '✓ Selesai' ?></span>
        </div>
      </div>

      <!-- Detail grid -->
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
          <div style="font-weight:800;font-size:var(--text-base);color:var(--color-accent)"><?= rupiah($row['total_bayar']) ?></div>
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

      <!-- Status pembayaran & aksi -->
      <div style="display:flex;align-items:center;justify-content:space-between;gap:var(--space-3);flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:var(--space-3)">
          <?php if ($ps === 'lunas'): ?>
          <span class="badge badge-success" style="padding:.4rem .85rem">✓ Pembayaran Lunas</span>
          <?php elseif ($ps === 'menunggu_konfirmasi'): ?>
          <span class="badge badge-warning" style="padding:.4rem .85rem">🕐 Pembayaran Sedang Diverifikasi Admin</span>
          <?php else: ?>
          <span class="badge badge-neutral" style="padding:.4rem .85rem">💳 Belum Bayar</span>
          <?php endif; ?>
        </div>
        <?php if ($ps === 'pending'): ?>
        <a href="payment_page.php?id=<?= (int)$row['id_sewa'] ?>&jenis=mobil" class="btn btn-primary btn-sm" style="white-space:nowrap">
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
  <div class="empty-state">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/><rect x="9" y="11" width="14" height="10" rx="2"/></svg>
    <h3>Belum ada booking</h3>
    <p>Pilih mobil di katalog atas dan mulai booking pertamamu!</p>
  </div>
  <?php endif; ?>
</div>

<!-- BOOKING MODAL -->
<div id="bookingModal" class="modal-shell" style="display:none">
  <div class="modal-backdrop" onclick="closeModal()"></div>
  <div class="modal-card">
    <div class="card-header">
      <div>
        <div class="card-title" id="modalCarName">Booking Mobil</div>
        <div style="font-size:var(--text-sm);color:var(--color-text-muted)" id="modalCarMeta"></div>
      </div>
      <button class="btn btn-ghost btn-sm btn-icon" onclick="closeModal()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div id="modalCarImg" style="height:180px;background:#f8f8f8;border-radius:var(--radius-lg);overflow:hidden;margin-bottom:var(--space-5);display:flex;align-items:center;justify-content:center"></div>
    <form method="post">
      <input type="hidden" name="booking_mobil" value="1">
      <input type="hidden" name="id_mobil" id="modalCarId">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Tanggal Sewa <span>*</span></label>
          <input type="date" name="tanggal_sewa" id="tanggalSewa" class="form-input" required
            min="<?= date('Y-m-d') ?>" onchange="calcTotal(); checkAvailability();">
        </div>
        <div class="form-group">
          <label class="form-label">Tanggal Kembali <span>*</span></label>
          <input type="date" name="tanggal_kembali" id="tanggalKembali" class="form-input" required
            min="<?= date('Y-m-d', strtotime('+1 day')) ?>" onchange="calcTotal(); checkAvailability();">
        </div>
      </div>

      <!-- Availability indicator -->
      <div id="avail-status" style="display:none;margin-bottom:var(--space-4);padding:var(--space-3) var(--space-4);border-radius:var(--radius-lg);font-size:var(--text-sm);font-weight:600"></div>

            <div class="form-group">
        <label class="form-label">Add-on Layanan</label>
        <div class="addon-list">
          <label class="addon-item">
            <div style="display:flex;align-items:center;gap:var(--space-3)">
              <input type="checkbox" name="addons[]" value="driver" onchange="calcTotal()">
              <div>
                <div style="font-weight:600">Driver</div>
                <div style="font-size:var(--text-xs);color:var(--color-text-muted)">Pengemudi profesional berpengalaman</div>
              </div>
            </div>
            <span style="font-weight:700;color:var(--color-accent)">+Rp 150.000</span>
          </label>
          <label class="addon-item">
            <div style="display:flex;align-items:center;gap:var(--space-3)">
              <input type="checkbox" name="addons[]" value="child_seat" onchange="calcTotal()">
              <div>
                <div style="font-weight:600">Child Seat</div>
                <div style="font-size:var(--text-xs);color:var(--color-text-muted)">Kursi bayi/anak yang aman</div>
              </div>
            </div>
            <span style="font-weight:700;color:var(--color-accent)">+Rp 50.000</span>
          </label>
          <label class="addon-item">
            <div style="display:flex;align-items:center;gap:var(--space-3)">
              <input type="checkbox" name="addons[]" value="airport_pickup" onchange="calcTotal()">
              <div>
                <div style="font-weight:600">Airport Pickup</div>
                <div style="font-size:var(--text-xs);color:var(--color-text-muted)">Jemput/antar bandara</div>
              </div>
            </div>
            <span style="font-weight:700;color:var(--color-accent)">+Rp 100.000</span>
          </label>
        </div>
      </div>

      <!-- Promo / Voucher -->
      <div class="form-group">
        <label class="form-label">Kode Promo <span style="color:var(--color-text-faint);font-weight:400">(opsional)</span></label>
        <div style="display:flex;gap:var(--space-2)">
          <input type="text" name="kode_promo" id="kodePromo" class="form-input" placeholder="Contoh: ZDRENT10" style="text-transform:uppercase;flex:1" oninput="this.value=this.value.toUpperCase()">
          <button type="button" class="btn btn-ghost" onclick="terapkanPromo()">Terapkan</button>
        </div>
        <div id="promo-status" style="display:none;margin-top:6px;font-size:var(--text-xs);font-weight:600"></div>
      </div>

      <div class="invoice-preview">
        <div class="invoice-row"><span>Harga kendaraan</span><span id="invBase">—</span></div>
        <div class="invoice-row"><span>Add-on</span><span id="invAddon">—</span></div>
        <div class="invoice-row" id="invPromoRow" style="display:none"><span>🎁 Diskon Promo</span><span id="invPromo" style="color:#4ade80">—</span></div>
        <div class="invoice-row total"><span>Total Estimasi</span><strong id="invTotal">Rp 0</strong></div>
        <div style="font-size:var(--text-xs);color:rgba(255,255,255,.5);margin-top:var(--space-3)">*Total final dihitung berdasarkan lama sewa dan add-on yang dipilih</div>
      </div>

      <!-- E-Agreement -->
      <div style="margin-top:var(--space-5);padding:var(--space-4);background:var(--color-surface);border-radius:var(--radius-lg);border:1.5px solid var(--color-border)">
        <details>
          <summary style="font-weight:700;font-size:var(--text-sm);cursor:pointer;margin-bottom:var(--space-3)">📋 Syarat & Ketentuan Sewa (wajib dibaca)</summary>
          <div style="font-size:var(--text-xs);color:var(--color-text-muted);line-height:1.8;max-height:160px;overflow-y:auto;padding-right:8px">
            <p><strong>1. Identitas & Usia</strong> — Penyewa wajib berusia min. 17 tahun dan memiliki SIM aktif. KTP asli wajib diserahkan saat pengambilan kendaraan.</p>
            <p><strong>2. Kondisi Kendaraan</strong> — Kendaraan diserahkan dalam kondisi baik. Penyewa bertanggung jawab atas kerusakan yang terjadi selama masa sewa.</p>
            <p><strong>3. Keterlambatan Pengembalian</strong> — Keterlambatan dikenakan denda Rp 50.000/jam untuk motor dan Rp 100.000/jam untuk mobil.</p>
            <p><strong>4. Larangan</strong> — Dilarang menggunakan kendaraan untuk balapan, membawa muatan berlebih, atau meninggalkan kota tanpa izin tertulis.</p>
            <p><strong>5. BBM</strong> — Kendaraan diserahkan dengan kondisi tangki penuh dan wajib dikembalikan dalam kondisi yang sama.</p>
            <p><strong>6. Pembatalan</strong> — Pembatalan kurang dari 24 jam sebelum tanggal sewa dikenakan biaya pembatalan 50% dari total sewa.</p>
            <p><strong>7. Force Majeure</strong> — ZD RENT tidak bertanggung jawab atas kejadian di luar kendali (bencana alam, dll).</p>
          </div>
        </details>
        <label style="display:flex;align-items:center;gap:var(--space-3);margin-top:var(--space-3);cursor:pointer">
          <input type="checkbox" name="agreement" id="agreementCheck" required style="width:18px;height:18px;accent-color:#111;flex-shrink:0">
          <span style="font-size:var(--text-sm);font-weight:600">Saya telah membaca dan menyetujui syarat & ketentuan sewa di atas</span>
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
.pm-hero{display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);background:linear-gradient(135deg,#111 0%,#1d1d1d 100%);color:#fff;border-radius:1.5rem;padding:clamp(1.5rem,3vw,2.5rem);margin-bottom:var(--space-6)}
.pm-chip{display:inline-flex;padding:.4rem .85rem;background:rgba(255,255,255,.1);border-radius:999px;font-size:.75rem;font-weight:700;margin-bottom:var(--space-4)}
.pm-hero h1{font-size:clamp(1.8rem,2vw+.8rem,2.8rem);line-height:1.08;letter-spacing:-.04em;margin-bottom:var(--space-4)}
.pm-hero h1 span{color:#E63946}
.pm-hero p{color:rgba(255,255,255,.6);font-size:var(--text-sm)}
.pm-hero__cards{display:flex;flex-direction:column;gap:var(--space-3);justify-content:center}
.pm-stat-card{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.1);border-radius:1rem;padding:var(--space-4);display:flex;align-items:center;gap:var(--space-3)}
.pm-stat-card strong{font-size:1.25rem;font-weight:800}
.pm-stat-card span{font-size:var(--text-xs);color:rgba(255,255,255,.5)}
.pm-stat-card svg{flex-shrink:0;color:rgba(255,255,255,.5)}
.section-head-pm{display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-5);gap:var(--space-4)}
.section-head-pm h2{font-size:var(--text-xl);font-weight:800;letter-spacing:-.02em}
.pm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(280px,100%),1fr));gap:var(--space-5);margin-bottom:var(--space-8)}
.pm-car-card{background:#fff;border:1px solid var(--color-border);border-radius:1.4rem;overflow:hidden;box-shadow:var(--shadow-sm);transition:transform var(--transition),box-shadow var(--transition)}
.pm-car-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg)}
.pm-car-card.is-rented{opacity:.7}
.pm-car-media{position:relative;height:200px;background:linear-gradient(180deg,#f5f5f5,#eee);display:flex;align-items:center;justify-content:center;overflow:hidden}
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
.invoice-row:last-of-type{border-bottom:none}
.invoice-row.total{color:#fff;padding-top:var(--space-3)}
.invoice-row.total strong{font-size:var(--text-lg);color:#fff}
.modal-shell{position:fixed;inset:0;z-index:220;display:flex;align-items:center;justify-content:center;padding:var(--space-4)}
.modal-backdrop{position:absolute;inset:0;background:rgba(0,0,0,.6)}
.modal-card{position:relative;z-index:1;width:100%;max-width:560px;max-height:92dvh;overflow-y:auto;background:#fff;border-radius:1.4rem;padding:var(--space-6);box-shadow:0 24px 48px rgba(0,0,0,.25)}
@media(max-width:900px){.pm-hero{grid-template-columns:1fr}}
@media(max-width:560px){.pm-hero{padding:1.25rem}.form-row{grid-template-columns:1fr}.modal-card{padding:var(--space-4)}}
</style>

<script>
let selPrice  = 0;
let selCarId  = 0;
let promoDiskon = 0;
let availOk   = false;

function openModal(car) {
  document.getElementById('bookingModal').style.display = 'flex';
  document.getElementById('modalCarId').value  = car.id_mobil;
  document.getElementById('modalCarName').textContent = car.nama_mobil;
  document.getElementById('modalCarMeta').textContent = car.merk + ' · ' + car.tahun;
  selPrice = Number(car.harga_sewa) || 0;
  selCarId = car.id_mobil;

  const imgBox = document.getElementById('modalCarImg');
  if (car.foto_mobil) {
    imgBox.innerHTML = `<img src="${car.foto_mobil}" style="width:100%;height:100%;object-fit:cover" alt="">`;
  } else {
    imgBox.innerHTML = `<svg width="80" height="50" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/><rect x="9" y="11" width="14" height="10" rx="2"/></svg>`;
  }

  // Reset
  document.querySelectorAll('input[name="addons[]"]').forEach(el => el.checked = false);
  document.getElementById('kodePromo').value = '';
  document.getElementById('promo-status').style.display = 'none';
  document.getElementById('avail-status').style.display = 'none';
  document.getElementById('invPromoRow').style.display = 'none';
  document.getElementById('agreementCheck').checked = false;
  promoDiskon = 0;
  availOk = false;
  updateSubmitBtn();
  calcTotal();
}

function closeModal() {
  document.getElementById('bookingModal').style.display = 'none';
}

let availTimer = null;
function checkAvailability() {
  const s = document.getElementById('tanggalSewa').value;
  const e = document.getElementById('tanggalKembali').value;
  const box = document.getElementById('avail-status');
  if (!s || !e || !selCarId) { box.style.display='none'; availOk=false; updateSubmitBtn(); return; }
  box.style.display = 'block';
  box.style.background = '#f3f4f6';
  box.style.color = '#6b7280';
  box.textContent = '⏳ Memeriksa ketersediaan...';
  clearTimeout(availTimer);
  availTimer = setTimeout(() => {
    fetch(`cek_ketersediaan.php?jenis=mobil&id=${selCarId}&tanggal_sewa=${s}&tanggal_kembali=${e}`)
      .then(r => r.json())
      .then(d => {
        box.style.display = 'block';
        if (d.tersedia) {
          box.style.background = '#f0fdf4';
          box.style.color = '#16a34a';
          box.textContent = '✓ ' + (d.pesan || 'Kendaraan tersedia!');
          availOk = true;
        } else {
          box.style.background = '#fef2f2';
          box.style.color = '#dc2626';
          box.textContent = '✗ ' + d.pesan;
          availOk = false;
        }
        updateSubmitBtn();
      }).catch(() => { availOk = true; updateSubmitBtn(); });
  }, 600);
}

function terapkanPromo() {
  const kode  = document.getElementById('kodePromo').value.trim().toUpperCase();
  const total = calcRawTotal();
  const box   = document.getElementById('promo-status');
  if (!kode) { box.style.display='none'; return; }
  box.style.display = 'block';
  box.style.color = '#6b7280';
  box.textContent = '⏳ Mengecek promo...';
  const fd = new FormData();
  fd.append('kode', kode);
  fd.append('total', total);
  fetch('cek_promo.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(d => {
      if (d.valid) {
        box.style.color = '#16a34a';
        box.textContent = '✓ ' + d.pesan + ' — ' + d.deskripsi;
        promoDiskon = d.diskon;
      } else {
        box.style.color = '#dc2626';
        box.textContent = '✗ ' + d.pesan;
        promoDiskon = 0;
      }
      calcTotal();
    });
}

function calcRawTotal() {
  const s = document.getElementById('tanggalSewa').value;
  const e = document.getElementById('tanggalKembali').value;
  let days = 1;
  if (s && e) { const diff = Math.ceil((new Date(e)-new Date(s))/86400000); days = diff > 0 ? diff : 1; }
  let addon = 0;
  document.querySelectorAll('input[name="addons[]"]:checked').forEach(el => {
    if (el.value==='driver')         addon += 150000;
    if (el.value==='child_seat')     addon += 50000;
    if (el.value==='airport_pickup') addon += 100000;
  });
  return days * selPrice + addon;
}

function calcTotal() {
  const s = document.getElementById('tanggalSewa').value;
  const e = document.getElementById('tanggalKembali').value;
  let days = 1;
  if (s && e) { const diff = Math.ceil((new Date(e)-new Date(s))/86400000); days = diff > 0 ? diff : 1; }
  let addon = 0;
  document.querySelectorAll('input[name="addons[]"]:checked').forEach(el => {
    if (el.value==='driver')         addon += 150000;
    if (el.value==='child_seat')     addon += 50000;
    if (el.value==='airport_pickup') addon += 100000;
  });
  const base  = days * selPrice;
  const sub   = base + addon;
  const total = Math.max(0, sub - promoDiskon);
  const fmt   = v => 'Rp ' + v.toLocaleString('id-ID');
  document.getElementById('invBase').textContent  = fmt(base);
  document.getElementById('invAddon').textContent = addon > 0 ? fmt(addon) : '—';
  if (promoDiskon > 0) {
    document.getElementById('invPromoRow').style.display = '';
    document.getElementById('invPromo').textContent = '−' + fmt(promoDiskon);
  } else {
    document.getElementById('invPromoRow').style.display = 'none';
  }
  document.getElementById('invTotal').textContent = fmt(total);
}

function updateSubmitBtn() {
  const agreed   = document.getElementById('agreementCheck').checked;
  const btn      = document.getElementById('btnKonfirmasi');
  const s        = document.getElementById('tanggalSewa').value;
  const e        = document.getElementById('tanggalKembali').value;
  const canBook  = agreed && s && e && availOk;
  btn.disabled   = !canBook;
  btn.style.opacity = canBook ? '1' : '0.5';
}

document.getElementById('agreementCheck').addEventListener('change', updateSubmitBtn);
document.getElementById('tanggalSewa').addEventListener('change', calcTotal);
document.getElementById('tanggalKembali').addEventListener('change', calcTotal);
document.getElementById('bookingModal').addEventListener('click', e => {
  if (e.target.id === 'bookingModal') closeModal();
});
</script>

<?php include '_layout_footer.php'; ?>

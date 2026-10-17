<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user'])) { header('Location: login.php'); exit; }
require_once 'koneksi.php';

$id_user = (int)$_SESSION['id_user'];
$role    = $_SESSION['role'];

// ── Auto-create tabel notifikasi admin ──


// ── Auto-create tabel notifikasi customer ──


// ══════════════════════════════════════════
// AJAX: ambil jumlah unread (polling)
// ══════════════════════════════════════════
if (isset($_GET['count_unread'])) {
    header('Content-Type: application/json');
    if ($role === 'admin') {
        $c = (int)(db_fetch_assoc(db_query($conn,
            "SELECT COUNT(*) as c FROM notifikasi_admin WHERE sudah_baca=0"))['c'] ?? 0);
    } else {
        $c = (int)(db_fetch_assoc(db_query($conn,
            "SELECT COUNT(*) as c FROM notifikasi_customer WHERE id_user='$id_user' AND sudah_baca=0"))['c'] ?? 0);
    }
    echo json_encode(['count' => $c]);
    exit;
}

// ══════════════════════════════════════════
// AKSI: Tandai semua dibaca
// ══════════════════════════════════════════
if (isset($_GET['baca_semua'])) {
    if ($role === 'admin') {
        db_query($conn, "UPDATE notifikasi_admin SET sudah_baca=1");
    } else {
        db_query($conn, "UPDATE notifikasi_customer SET sudah_baca=1 WHERE id_user='$id_user'");
    }
    header('Location: notifikasi.php');
    exit;
}

// ══════════════════════════════════════════
// AKSI: Tandai satu notif dibaca + redirect
// ══════════════════════════════════════════
if (isset($_GET['baca'])) {
    $nid = intval($_GET['baca']);
    if ($role === 'admin') {
        $notif = db_fetch_assoc(db_query($conn,
            "SELECT * FROM notifikasi_admin WHERE id_notif='$nid'"));
        if ($notif) {
            db_query($conn, "UPDATE notifikasi_admin SET sudah_baca=1 WHERE id_notif='$nid'");
            // Redirect sesuai tipe
            if ($notif['tipe'] === 'pembayaran') {
                header('Location: verifikasi_bukti.php');
            } else {
                header('Location: konfirmasi_pesanan.php');
            }
            exit;
        }
    } else {
        $notif = db_fetch_assoc(db_query($conn,
            "SELECT * FROM notifikasi_customer WHERE id_notif='$nid' AND id_user='$id_user'"));
        if ($notif) {
            db_query($conn, "UPDATE notifikasi_customer SET sudah_baca=1 WHERE id_notif='$nid'");
            $target = $notif['url_target'] ?: 'riwayat_sewa.php';
            header("Location: $target");
            exit;
        }
    }
    header('Location: notifikasi.php');
    exit;
}

// ══════════════════════════════════════════
// AMBIL DATA NOTIFIKASI
// ══════════════════════════════════════════
if ($role === 'admin') {
    $q_notifs = db_query($conn,
        "SELECT *, 'admin' AS sumber FROM notifikasi_admin ORDER BY created_at DESC LIMIT 100");
    $total    = db_num_rows($q_notifs);
    $unread   = (int)(db_fetch_assoc(db_query($conn,
        "SELECT COUNT(*) as c FROM notifikasi_admin WHERE sudah_baca=0"))['c'] ?? 0);
} else {
    $q_notifs = db_query($conn,
        "SELECT * FROM notifikasi_customer WHERE id_user='$id_user' ORDER BY created_at DESC LIMIT 100");
    $total    = db_num_rows($q_notifs);
    $unread   = (int)(db_fetch_assoc(db_query($conn,
        "SELECT COUNT(*) as c FROM notifikasi_customer WHERE id_user='$id_user' AND sudah_baca=0"))['c'] ?? 0);
}

$page_title  = 'Notifikasi';
$active_menu = 'notif';
include '_layout.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-6);flex-wrap:wrap;gap:var(--space-3)">
  <div>
    <h2 style="font-size:var(--text-xl);font-weight:800;letter-spacing:-.02em">🔔 Notifikasi</h2>
    <p style="color:var(--color-text-muted);font-size:var(--text-sm)">
      <?= $unread ?> belum dibaca · <?= $total ?> total
    </p>
  </div>
  <?php if ($unread > 0): ?>
  <a href="?baca_semua=1" class="btn btn-ghost btn-sm">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    Tandai Semua Dibaca
  </a>
  <?php endif; ?>
</div>

<div class="card" style="overflow:hidden">
  <?php if ($total === 0): ?>
  <div class="empty-state" style="padding:var(--space-12)">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
      <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
      <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
    </svg>
    <h3>Belum ada notifikasi</h3>
    <p><?= $role === 'admin' ? 'Notifikasi pesanan baru & pembayaran akan muncul di sini.' : 'Notifikasi status pesanan, denda, dan pembayaran akan muncul di sini.' ?></p>
  </div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column">
    <?php while ($n = db_fetch_assoc($q_notifs)):
      $is_unread  = !$n['sudah_baca'];
      $tipe       = $n['tipe'];
      $id_notif   = $n['id_notif'];

      // Warna & ikon per tipe
      $icon_map = [
        'pesanan_baru'  => ['color'=>'#b45309','bg'=>'#fef3c7','icon'=>'car'],
        'pembayaran'    => ['color'=>'#166534','bg'=>'#dcfce7','icon'=>'card'],
        'pesanan_dikonfirmasi' => ['color'=>'#1d4ed8','bg'=>'#dbeafe','icon'=>'check'],
        'pesanan_ditolak'     => ['color'=>'#991b1b','bg'=>'#fee2e2','icon'=>'x'],
        'denda'         => ['color'=>'#c2410c','bg'=>'#ffedd5','icon'=>'alert'],
        'tagihan_denda' => ['color'=>'#c2410c','bg'=>'#ffedd5','icon'=>'alert'],
        'lunas'         => ['color'=>'#166534','bg'=>'#dcfce7','icon'=>'check'],
        'bukti_diterima'=> ['color'=>'#166534','bg'=>'#dcfce7','icon'=>'check'],
        'bukti_ditolak' => ['color'=>'#991b1b','bg'=>'#fee2e2','icon'=>'x'],
        'info'          => ['color'=>'#374151','bg'=>'#f3f4f6','icon'=>'info'],
      ];
      $cfg = $icon_map[$tipe] ?? $icon_map['info'];
    ?>
    <div style="
      display:flex;align-items:flex-start;gap:var(--space-4);
      padding:var(--space-4) var(--space-5);
      border-bottom:1px solid var(--color-divider);
      background:<?= $is_unread ? 'rgba(230,57,70,.03)' : 'transparent' ?>;
      transition:background .15s;
    ">
      <!-- Icon -->
      <div style="
        width:42px;height:42px;border-radius:50%;flex-shrink:0;margin-top:2px;
        background:<?= $cfg['bg'] ?>;
        display:flex;align-items:center;justify-content:center;
      ">
        <?php if ($cfg['icon'] === 'car'): ?>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="<?= $cfg['color'] ?>" stroke-width="2"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/><rect x="9" y="11" width="14" height="10" rx="2"/></svg>
        <?php elseif ($cfg['icon'] === 'card'): ?>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="<?= $cfg['color'] ?>" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        <?php elseif ($cfg['icon'] === 'check'): ?>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="<?= $cfg['color'] ?>" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <?php elseif ($cfg['icon'] === 'x'): ?>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="<?= $cfg['color'] ?>" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        <?php elseif ($cfg['icon'] === 'alert'): ?>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="<?= $cfg['color'] ?>" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <?php else: ?>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="<?= $cfg['color'] ?>" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?php endif; ?>
      </div>

      <!-- Konten -->
      <div style="flex:1;min-width:0">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:var(--space-3)">
          <div style="flex:1">
            <div style="font-weight:<?= $is_unread ? '700' : '500' ?>;font-size:var(--text-sm);line-height:1.55;color:var(--color-text)">
              <?= htmlspecialchars($n['pesan']) ?>
              <?php if ($is_unread): ?>
              <span style="display:inline-block;width:7px;height:7px;background:#E63946;border-radius:50%;margin-left:6px;vertical-align:middle;flex-shrink:0"></span>
              <?php endif; ?>
            </div>
            <div style="font-size:var(--text-xs);color:var(--color-text-faint);margin-top:5px;display:flex;align-items:center;gap:var(--space-3)">
              <span><?= date('d M Y, H:i', strtotime($n['created_at'])) ?></span>
              <?php if (!empty($n['jenis'])): ?>
              <span style="display:flex;align-items:center;gap:3px">
                <?= $n['jenis'] === 'mobil' ? '🚗' : '🏍️' ?>
                <?= ucfirst($n['jenis']) ?>
                <?php if (!empty($n['id_sewa'])): ?> · #<?= $n['id_sewa'] ?><?php endif; ?>
              </span>
              <?php endif; ?>
            </div>
          </div>
          <!-- Tombol -->
          <div style="display:flex;gap:var(--space-2);flex-shrink:0;align-items:flex-start">
            <?php if ($is_unread): ?>
            <a href="?baca=<?= $id_notif ?>" class="btn btn-primary btn-sm" style="white-space:nowrap">
              Lihat →
            </a>
            <?php else: ?>
            <?php
              // Link detail berdasarkan tipe
              if ($role === 'admin') {
                $href = in_array($tipe, ['pembayaran']) ? 'verifikasi_bukti.php' : 'konfirmasi_pesanan.php';
              } else {
                $href = !empty($n['url_target']) ? $n['url_target'] : 'riwayat_sewa.php';
              }
            ?>
            <a href="<?= $href ?>" class="btn btn-ghost btn-sm">Detail</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
  <?php endif; ?>
</div>

<?php include '_layout_footer.php'; ?>

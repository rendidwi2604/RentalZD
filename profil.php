<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user'])) { header('Location: login.php'); exit; }
require_once 'koneksi.php';

$id_user     = (int)$_SESSION['id_user'];
$msg_success = $msg_error = '';

// Update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $no_hp= trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if ($nama) {
                $esc_nama   = db_real_escape_string($conn, $nama);
        $esc_hp     = db_real_escape_string($conn, $no_hp);
        $esc_alamat = db_real_escape_string($conn, $alamat);

        db_query($conn, "UPDATE app_users SET nama_lengkap='$esc_nama', no_hp='$esc_hp', alamat='$esc_alamat' WHERE id_user='$id_user'");
        $_SESSION['nama_lengkap'] = $nama;
        $msg_success = 'Profil berhasil diperbarui.';
    } else {
        $msg_error = 'Nama lengkap tidak boleh kosong.';
    }
}

// Ganti password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_password'])) {
    $old_pass = $_POST['old_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$old_pass || !$new_pass || !$confirm) {
        $msg_error = 'Harap isi semua field password.';
    } elseif ($new_pass !== $confirm) {
        $msg_error = 'Password baru dan konfirmasi tidak cocok.';
    } elseif (strlen($new_pass) < 6) {
        $msg_error = 'Password baru minimal 6 karakter.';
    } else {
        $user = db_fetch_assoc(db_query($conn, "SELECT password FROM app_users WHERE id_user='$id_user'"));
        if (!password_verify($old_pass, $user['password'])) {
            $msg_error = 'Password lama salah.';
        } else {
            $hash = password_hash($new_pass, PASSWORD_BCRYPT);
            db_query($conn, "UPDATE app_users SET password='$hash' WHERE id_user='$id_user'");
            $msg_success = 'Password berhasil diubah.';
        }
    }
}

// Ambil data user terbaru
$user = db_fetch_assoc(db_query($conn, "SELECT * FROM app_users WHERE id_user='$id_user'"));

// Statistik customer
$total_sewa = 0; $total_bayar = 0;
if ($_SESSION['role'] === 'customer') {
    $sm = db_fetch_assoc(db_query($conn, "SELECT COUNT(*) as c, SUM(total_bayar) as t FROM log_penyewaan WHERE id_user='$id_user'"));
    $smt= db_fetch_assoc(db_query($conn, "SELECT COUNT(*) as c, SUM(total_bayar) as t FROM log_sewa_motor WHERE id_user='$id_user'"));
    $total_sewa  = ($sm['c']??0) + ($smt['c']??0);
    $total_bayar = ($sm['t']??0) + ($smt['t']??0);
}

$page_title  = 'Profil Saya';
$active_menu = 'profil';
include '_layout.php';
?>

<?php if ($msg_success): ?><div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg><?= htmlspecialchars($msg_success) ?></div><?php endif; ?>
<?php if ($msg_error): ?><div class="alert alert-error"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg><?= htmlspecialchars($msg_error) ?></div><?php endif; ?>

<div style="max-width:700px;margin:0 auto">

  <!-- Profile Header -->
  <div class="card" style="margin-bottom:var(--space-6);text-align:center;padding:var(--space-8)">
    <div style="width:80px;height:80px;border-radius:50%;background:#111;color:#fff;font-size:2rem;font-weight:800;display:flex;align-items:center;justify-content:center;margin:0 auto var(--space-4)">
      <?= strtoupper(substr($user['nama_lengkap'],0,1)) ?>
    </div>
    <h2 style="font-size:var(--text-xl);font-weight:800;letter-spacing:-.02em"><?= htmlspecialchars($user['nama_lengkap']) ?></h2>
    <p style="color:var(--color-text-muted);font-size:var(--text-sm)">@<?= htmlspecialchars($user['username']) ?> · <span class="badge <?= $user['role']==='admin'?'badge-warning':'badge-neutral' ?>"><?= ucfirst($user['role']) ?></span></p>

    <?php if ($_SESSION['role'] === 'customer'): ?>
    <div style="display:flex;justify-content:center;gap:var(--space-8);margin-top:var(--space-6)">
      <div><div style="font-size:1.5rem;font-weight:800"><?= $total_sewa ?></div><div style="font-size:var(--text-xs);color:var(--color-text-faint)">Total Sewa</div></div>
      <div style="width:1px;background:var(--color-divider)"></div>
      <div><div style="font-size:1.5rem;font-weight:800">Rp <?= number_format($total_bayar,0,',','.') ?></div><div style="font-size:var(--text-xs);color:var(--color-text-faint)">Total Bayar</div></div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Edit Profil -->
  <div class="card" style="margin-bottom:var(--space-5)">
    <div class="card-header"><div class="card-title">Edit Informasi Profil</div></div>
    <form method="POST">
      <input type="hidden" name="update_profil" value="1">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Nama Lengkap <span style="color:var(--color-error)">*</span></label>
          <input type="text" name="nama_lengkap" class="form-input" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Username</label>
          <input type="text" class="form-input" value="<?= htmlspecialchars($user['username']) ?>" disabled style="opacity:.6;cursor:not-allowed">
          <p style="font-size:10px;color:var(--color-text-faint);margin-top:4px">Username tidak dapat diubah</p>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">No. HP / WhatsApp</label>
          <input type="text" name="no_hp" class="form-input" value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>" placeholder="08xx-xxxx-xxxx">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Alamat</label>
        <textarea name="alamat" class="form-input" rows="2" placeholder="Alamat lengkap Anda"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
        Simpan Perubahan
      </button>
    </form>
  </div>

  <!-- Ganti Password -->
  <div class="card">
    <div class="card-header"><div class="card-title">🔒 Ganti Password</div></div>
    <form method="POST">
      <input type="hidden" name="ganti_password" value="1">
      <div class="form-group">
        <label class="form-label">Password Lama <span style="color:var(--color-error)">*</span></label>
        <input type="password" name="old_password" class="form-input" placeholder="Masukkan password saat ini" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Password Baru <span style="color:var(--color-error)">*</span></label>
          <input type="password" name="new_password" class="form-input" placeholder="Min. 6 karakter" required>
        </div>
        <div class="form-group">
          <label class="form-label">Konfirmasi Password Baru <span style="color:var(--color-error)">*</span></label>
          <input type="password" name="confirm_password" class="form-input" placeholder="Ulangi password baru" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Ganti Password
      </button>
    </form>
  </div>
</div>

<?php include '_layout_footer.php'; ?>

<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') { header('Location: login.php'); exit; }
require_once 'koneksi.php';

$msg_success = $msg_error = '';

if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $role = $_POST['role'] ?? 'customer';
    if ($username && $password && $nama) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO app_users (username,password,nama_lengkap,role) VALUES (?,?,?,?)");
        $stmt->execute([$username, $hash, $nama, $role]) ? $msg_success = "User berhasil ditambahkan." : $msg_error = "Gagal. Username mungkin sudah digunakan.";
    } else { $msg_error = "Harap isi semua field."; }
}

if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['id_user']);
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $role = $_POST['role'] ?? 'customer';
    $new_pass = $_POST['new_password'] ?? '';
    if ($new_pass) {
        $hash = password_hash($new_pass, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE app_users SET nama_lengkap=?,role=?,password=? WHERE id_user=?");
        $params = [$nama, $role, $hash, $id];
    } else {
        $stmt = $conn->prepare("UPDATE app_users SET nama_lengkap=?,role=? WHERE id_user=?");
        $params = [$nama, $role, $id];
    }
    $stmt->execute($params) ? $msg_success = "User berhasil diupdate." : $msg_error = "Gagal update.";
}

if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    if ($id == $_SESSION['id_user']) { $msg_error = "Tidak dapat menghapus akun sendiri."; }
    else { db_query($conn, "DELETE FROM app_users WHERE id_user='$id'") ? $msg_success = "User dihapus." : $msg_error = "Gagal menghapus."; }
}

$users = db_query($conn, "SELECT * FROM app_users ORDER BY role ASC, nama_lengkap ASC");
$total = db_num_rows($users);
$admins  = db_num_rows(db_query($conn, "SELECT id_user FROM app_users WHERE role='admin'"));
$customers = db_num_rows(db_query($conn, "SELECT id_user FROM app_users WHERE role='customer'"));

$page_title = 'Manajemen User';
$active_menu = 'users';
include '_layout.php';
?>

<?php if ($msg_success): ?><div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg><?= htmlspecialchars($msg_success) ?></div><?php endif; ?>
<?php if ($msg_error): ?><div class="alert alert-error"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg><?= htmlspecialchars($msg_error) ?></div><?php endif; ?>

<div class="stats-grid">
  <div class="stat-card"><div class="stat-label">Total User</div><div class="stat-value"><?= $total ?></div><div class="stat-sub">Terdaftar</div></div>
  <div class="stat-card"><div class="stat-label">Admin</div><div class="stat-value"><?= $admins ?></div><div class="stat-sub">Pengelola sistem</div></div>
  <div class="stat-card"><div class="stat-label">Customer</div><div class="stat-value"><?= $customers ?></div><div class="stat-sub">Pelanggan aktif</div></div>
</div>

<div class="card" style="margin-bottom:var(--space-6)">
  <div class="card-header">
    <div class="card-title">Tambah User Baru</div>
    <button class="btn btn-ghost btn-sm" onclick="toggleForm()">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Tambah
    </button>
  </div>
  <div id="form-tambah" style="display:none">
    <form method="POST">
      <input type="hidden" name="action" value="tambah">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Username <span>*</span></label><input type="text" name="username" class="form-input" placeholder="username unik" required></div>
        <div class="form-group"><label class="form-label">Password <span>*</span></label><input type="password" name="password" class="form-input" placeholder="min 8 karakter" required></div>
        <div class="form-group"><label class="form-label">Nama Lengkap <span>*</span></label><input type="text" name="nama_lengkap" class="form-input" placeholder="Nama lengkap" required></div>
        <div class="form-group"><label class="form-label">Role</label>
          <select name="role" class="form-select">
            <option value="customer">Customer</option>
            <option value="admin">Admin</option>
          </select>
        </div>
      </div>
      <div style="margin-top:var(--space-5);display:flex;gap:var(--space-3)">
        <button type="submit" class="btn btn-primary"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>Simpan</button>
        <button type="button" class="btn btn-ghost" onclick="toggleForm()">Batal</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">Daftar Pengguna</div></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>#</th><th>Username</th><th>Nama Lengkap</th><th>Role</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php db_data_seek($users, 0); $no=1; while ($u = db_fetch_assoc($users)): ?>
        <tr>
          <td style="color:var(--color-text-faint)"><?= $no++ ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:var(--space-3)">
              <div style="width:32px;height:32px;background:#111;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:var(--text-xs);font-weight:700;flex-shrink:0"><?= strtoupper(substr($u['nama_lengkap'],0,1)) ?></div>
              <span style="font-weight:600"><?= htmlspecialchars($u['username']) ?></span>
              <?php if ($u['id_user'] == $_SESSION['id_user']): ?><span class="badge badge-neutral" style="font-size:10px">Saya</span><?php endif; ?>
            </div>
          </td>
          <td><?= htmlspecialchars($u['nama_lengkap']) ?></td>
          <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-warning' : 'badge-neutral' ?>"><?= ucfirst($u['role']) ?></span></td>
          <td>
            <div style="display:flex;gap:var(--space-2)">
              <button class="btn btn-ghost btn-sm btn-icon" onclick="openEdit(<?= htmlspecialchars(json_encode($u)) ?>)" title="Edit">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </button>
              <?php if ($u['id_user'] != $_SESSION['id_user']): ?>
              <a href="?hapus=<?= $u['id_user'] ?>" class="btn btn-danger btn-sm btn-icon" data-confirm="Hapus user ini?">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
              </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- EDIT MODAL -->
<div id="edit-modal" style="display:none;position:fixed;inset:0;z-index:200;align-items:center;justify-content:center;padding:var(--space-4)">
  <div style="position:absolute;inset:0;background:rgba(0,0,0,0.5)" onclick="closeEdit()"></div>
  <div class="card" style="position:relative;z-index:1;width:100%;max-width:500px">
    <div class="card-header"><div class="card-title">Edit User</div><button class="btn btn-ghost btn-sm btn-icon" onclick="closeEdit()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id_user" id="edit-id">
      <div class="form-grid">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Nama Lengkap</label><input type="text" name="nama_lengkap" id="edit-nama" class="form-input" required></div>
          <div class="form-group"><label class="form-label">Role</label><select name="role" id="edit-role" class="form-select"><option value="customer">Customer</option><option value="admin">Admin</option></select></div>
        </div>
        <div class="form-group"><label class="form-label">Password Baru <span style="color:var(--color-text-faint);font-weight:400">(kosongkan jika tidak diubah)</span></label><input type="password" name="new_password" class="form-input" placeholder="Isi untuk ganti password"></div>
      </div>
      <div style="margin-top:var(--space-5);display:flex;gap:var(--space-3)"><button type="submit" class="btn btn-primary">Simpan</button><button type="button" class="btn btn-ghost" onclick="closeEdit()">Batal</button></div>
    </form>
  </div>
</div>

<script>
function toggleForm(){ const f = document.getElementById('form-tambah'); f.style.display = f.style.display === 'none' ? 'block' : 'none'; }
function openEdit(u){ document.getElementById('edit-id').value=u.id_user; document.getElementById('edit-nama').value=u.nama_lengkap; document.getElementById('edit-role').value=u.role; document.getElementById('edit-modal').style.display='flex'; }
function closeEdit(){ document.getElementById('edit-modal').style.display='none'; }
</script>

<?php include '_layout_footer.php'; ?>

<?php
require_once __DIR__ . '/config/bootstrap.php';
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit;
}
require_once 'koneksi.php';

$msg_success = '';
$msg_error   = '';

if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nama   = trim($_POST['nama_motor'] ?? '');
    $merk   = trim($_POST['merk'] ?? '');
    $tahun  = intval($_POST['tahun'] ?? 0);
    $harga  = floatval($_POST['harga_sewa'] ?? 0);
    $foto   = trim($_POST['foto_motor'] ?? '');
    $status = $_POST['status'] ?? 'tersedia';
    if ($nama && $merk && $tahun && $harga) {
        $stmt = $conn->prepare("INSERT INTO motor (nama_motor,merk,tahun,harga_sewa,foto_motor,status) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$nama, $merk, $tahun, $harga, $foto, $status]) ? $msg_success = "Motor berhasil ditambahkan." : $msg_error = "Gagal menambahkan motor.";
    } else { $msg_error = "Harap isi semua field yang wajib."; }
}

if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id    = intval($_POST['id_motor']);
    $nama  = trim($_POST['nama_motor'] ?? '');
    $merk  = trim($_POST['merk'] ?? '');
    $tahun = intval($_POST['tahun'] ?? 0);
    $harga = floatval($_POST['harga_sewa'] ?? 0);
    $foto  = trim($_POST['foto_motor'] ?? '');
    $status= $_POST['status'] ?? 'tersedia';
    $stmt  = $conn->prepare("UPDATE motor SET nama_motor=?,merk=?,tahun=?,harga_sewa=?,foto_motor=?,status=? WHERE id_motor=?");
        $stmt->execute([$nama, $merk, $tahun, $harga, $foto, $status, $id]) ? $msg_success = "Data motor berhasil diupdate." : $msg_error = "Gagal update.";
}

if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    db_query($conn, "DELETE FROM motor WHERE id_motor='$id'") ? $msg_success = "Motor berhasil dihapus." : $msg_error = "Gagal menghapus.";
}

$cars     = [];
$result   = db_query($conn, "SELECT * FROM motor ORDER BY status ASC, nama_motor ASC");
while ($r = db_fetch_assoc($result)) $cars[] = $r;

$total     = count($cars);
$available = count(array_filter($cars, fn($c) => strtolower($c['status']) === 'tersedia'));
$rented    = $total - $available;
$avg_price = $total > 0 ? array_sum(array_column($cars, 'harga_sewa')) / $total : 0;

$page_title = 'Data Motor'; $active_menu = 'motor'; include '_layout.php';
?>
<?php if ($msg_success): ?><div class="alert alert-success"><?= htmlspecialchars($msg_success) ?></div><?php endif; ?>
<?php if ($msg_error): ?><div class="alert alert-error"><?= htmlspecialchars($msg_error) ?></div><?php endif; ?>

<div class="fleet-hero">
  <div class="fleet-hero__content">
    <div class="fleet-chip">ZD RENT · Armada Motor</div>
    <h1>Kelola Motor <span>Lebih Cepat</span>, Rapi, dan Profesional</h1>
    <p>Data motor kini mendukung foto kendaraan, tampilan card detail, dan UI responsif agar admin cepat bekerja dan client lebih percaya.</p>
    <div class="fleet-hero__actions">
      <button class="btn btn-primary" onclick="toggleForm()">Tambah Motor</button>
      <a href="#motor-grid" class="btn btn-secondary">Lihat Katalog</a>
    </div>
  </div>
  <div class="fleet-hero__visual">
    <div class="hero-visual-stack">
      <div class="fleet-visual-card main">
        <div class="visual-label">Armada Motor Aktif</div>
        <div class="visual-value"><?= $total ?></div>
        <div class="visual-meta">Unit motor terdaftar</div>
      </div>
      <div class="hero-mini-row">
        <div class="fleet-visual-card floating clean"><span class="stat-pill ok">● <?= $available ?> Tersedia</span></div>
        <div class="fleet-visual-card floating clean"><span class="stat-pill bad">● <?= $rented ?> Disewa</span></div>
      </div>
    </div>
  </div>
</div>

<div class="stats-grid fleet-stats">
  <div class="stat-card stat-card--dark"><div class="stat-label">Total Armada</div><div class="stat-value"><?= $total ?></div><div class="stat-sub">Semua motor aktif</div></div>
  <div class="stat-card stat-card--light"><div class="stat-label">Tersedia</div><div class="stat-value" style="color:var(--color-success)"><?= $available ?></div><div class="stat-sub">Siap ditawarkan</div></div>
  <div class="stat-card stat-card--light"><div class="stat-label">Rata-rata Harga</div><div class="stat-value" style="font-size:var(--text-lg)">Rp <?= number_format($avg_price,0,',','.') ?></div><div class="stat-sub">Per hari</div></div>
  <div class="stat-card stat-card--accent"><div class="stat-label">Disewa</div><div class="stat-value"><?= $rented ?></div><div class="stat-sub">Perlu monitoring</div></div>
</div>

<div class="card form-shell" style="margin-bottom:var(--space-6)">
  <div class="card-header">
    <div><div class="card-title">Input Motor Baru</div><div class="section-subtitle">Tambahkan foto motor agar katalog customer lebih menarik.</div></div>
    <button class="btn btn-ghost btn-sm" onclick="toggleForm()">Tambah Baru</button>
  </div>
  <div id="form-tambah" style="display:none">
    <form method="POST" action="">
      <input type="hidden" name="action" value="tambah">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Nama Motor</label><input type="text" name="nama_motor" class="form-input" required></div>
        <div class="form-group"><label class="form-label">Merek</label><input type="text" name="merk" class="form-input" required></div>
        <div class="form-group"><label class="form-label">Tahun</label><input type="number" name="tahun" class="form-input" required></div>
        <div class="form-group"><label class="form-label">Harga / Hari</label><input type="number" name="harga_sewa" class="form-input" required></div>
        <div class="form-group"><label class="form-label">Link Foto Motor</label><input type="url" name="foto_motor" class="form-input" placeholder="https://...jpg"></div>
        <div class="form-group"><label class="form-label">Status</label><select name="status" class="form-select"><option value="tersedia">Tersedia</option><option value="disewa">Disewa</option></select></div>
      </div>
      <div style="margin-top:var(--space-5);display:flex;gap:var(--space-3);flex-wrap:wrap">
        <button type="submit" class="btn btn-primary">Simpan Motor</button>
        <button type="button" class="btn btn-ghost" onclick="toggleForm()">Batal</button>
      </div>
    </form>
  </div>
</div>

<div class="section-head">
  <div><h2>Katalog Motor</h2><p>Visual kendaraan dibuat lebih meyakinkan dengan foto, status, harga, dan aksi cepat.</p></div>
  <div class="section-head__meta"><?= $total ?> unit motor</div>
</div>

<div id="motor-grid" class="fleet-grid">
  <?php foreach ($cars as $row):
    $s = strtolower($row['status']);
    $isAvailable = $s === 'tersedia';
  ?>
  <div class="fleet-card <?= $isAvailable ? 'is-available' : 'is-rented' ?>">
    <div class="fleet-card__visual <?= !empty($row['foto_motor']) ? 'has-photo':'' ?>">
      <?php if(!empty($row['foto_motor'])): ?>
        <img src="<?= htmlspecialchars($row['foto_motor']) ?>" alt="<?= htmlspecialchars($row['nama_motor']) ?>" loading="lazy">
      <?php else: ?>
        <div class="car-illustration">
          <svg width="120" height="78" viewBox="0 0 120 78" fill="none">
            <ellipse cx="60" cy="55" rx="50" ry="8" fill="currentColor" opacity="0.06"/>
            <rect x="30" y="28" width="60" height="26" rx="6" fill="currentColor" opacity="0.12"/>
            <rect x="42" y="18" width="36" height="18" rx="5" fill="currentColor" opacity="0.18"/>
            <circle cx="38" cy="56" r="10" fill="currentColor" opacity="0.22"/>
            <circle cx="82" cy="56" r="10" fill="currentColor" opacity="0.22"/>
            <circle cx="38" cy="56" r="5" fill="currentColor" opacity="0.45"/>
            <circle cx="82" cy="56" r="5" fill="currentColor" opacity="0.45"/>
            <rect x="20" y="34" width="14" height="8" rx="3" fill="currentColor" opacity="0.15"/>
          </svg>
        </div>
      <?php endif; ?>
      <div class="fleet-card__badge <?= $isAvailable ? 'ok':'busy' ?>"><?= $isAvailable ? 'Tersedia':'Sedang Disewa' ?></div>
    </div>
    <div class="fleet-card__body">
      <div class="fleet-card__title-row">
        <div>
          <h3><?= htmlspecialchars($row['nama_motor']) ?></h3>
          <p><?= htmlspecialchars($row['merk']) ?> · Tahun <?= $row['tahun'] ?></p>
        </div>
        <div class="fleet-card__price"><span>Rp <?= number_format($row['harga_sewa'],0,',','.') ?></span><small>/hari</small></div>
      </div>
      <div class="fleet-card__detail-grid">
        <div><span>Status</span><strong><?= ucfirst($s) ?></strong></div>
        <div><span>Merek</span><strong><?= htmlspecialchars($row['merk']) ?></strong></div>
        <div><span>Tahun</span><strong><?= $row['tahun'] ?></strong></div>
        <div><span>Foto</span><strong><?= !empty($row['foto_motor']) ? 'Tersedia':'Belum ada' ?></strong></div>
      </div>
      <div class="fleet-card__actions">
        <button class="btn btn-secondary" onclick='openEdit(<?= json_encode($row, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Edit</button>
        <a href="?hapus=<?= $row['id_motor'] ?>" class="btn btn-danger" data-confirm="Hapus motor ini?">Hapus</a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if(empty($cars)): ?>
  <div style="grid-column:1/-1">
    <div class="empty-state">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <h3>Belum ada motor</h3>
      <p>Tambahkan motor pertama Anda menggunakan tombol "Tambah Motor" di atas.</p>
    </div>
  </div>
  <?php endif; ?>
</div>

<div class="service-panel">
  <div class="service-panel__content">
    <div class="fleet-chip dark">Customer Service</div>
    <h2>Support yang bikin client lebih yakin</h2>
    <p>Hubungi admin ZD RENT via WhatsApp untuk bantuan cepat dan terpercaya.</p>
  </div>
  <div class="service-panel__contact">
    <div class="contact-card primary">
      <div class="contact-title">Hubungi Customer Service</div>
      <a href="https://wa.me/6289668660248" target="_blank" rel="noopener noreferrer" class="contact-action">WhatsApp Admin</a>
      <small>Aktif setiap hari • 08.00 - 22.00 WIB</small>
    </div>
  </div>
</div>

<div id="edit-modal" class="modal-shell" style="display:none">
  <div class="modal-backdrop" onclick="closeEdit()"></div>
  <div class="modal-card">
    <div class="card-header">
      <div><div class="card-title">Edit Data Motor</div><div class="section-subtitle">Ubah info motor termasuk link foto.</div></div>
      <button class="btn btn-ghost btn-sm btn-icon" onclick="closeEdit()">✕</button>
    </div>
    <form method="POST" action="">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id_motor" id="edit-id">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Nama Motor</label><input type="text" name="nama_motor" id="edit-nama" class="form-input" required></div>
        <div class="form-group"><label class="form-label">Merek</label><input type="text" name="merk" id="edit-merk" class="form-input" required></div>
        <div class="form-group"><label class="form-label">Tahun</label><input type="number" name="tahun" id="edit-tahun" class="form-input" required></div>
        <div class="form-group"><label class="form-label">Harga / Hari</label><input type="number" name="harga_sewa" id="edit-harga" class="form-input" required></div>
        <div class="form-group"><label class="form-label">Link Foto Motor</label><input type="url" name="foto_motor" id="edit-foto" class="form-input"></div>
        <div class="form-group"><label class="form-label">Status</label><select name="status" id="edit-status" class="form-select"><option value="tersedia">Tersedia</option><option value="disewa">Disewa</option></select></div>
      </div>
      <div style="margin-top:var(--space-5);display:flex;gap:var(--space-3);flex-wrap:wrap">
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        <button type="button" class="btn btn-ghost" onclick="closeEdit()">Batal</button>
      </div>
    </form>
  </div>
</div>

<style>
.fleet-hero{
    display:grid;
    grid-template-columns:1.2fr .8fr;
    gap:var(--space-6);
    background:linear-gradient(135deg,#0f3fae 0%,#1d4ed8 60%,#2563eb 100%);
    color:#fff;
    border-radius:1.5rem;
    padding:clamp(1.5rem,3vw,2.5rem);
    margin-bottom:var(--space-6);
    overflow:hidden;
    position:relative;
}

.fleet-hero::before{
    content:'';
    position:absolute;
    right:-80px;
    top:-80px;
    width:240px;
    height:240px;
    border-radius:50%;
    background:rgba(255,255,255,.12);
}

.fleet-hero::after{
    content:'';
    position:absolute;
    left:-100px;
    bottom:-120px;
    width:260px;
    height:260px;
    border-radius:50%;
    background:rgba(255,255,255,.06);
}

.fleet-hero__content{
    position:relative;
    z-index:1;
}

.fleet-hero__visual{
    display:flex;
    align-items:center;
    justify-content:center;
    min-height:280px;
    position:relative;
    z-index:1;
}

.hero-visual-stack{
    display:grid;
    gap:var(--space-3);
    width:min(100%,320px);
}

.hero-mini-row{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:var(--space-3);
}

.fleet-chip{
    display:inline-flex;
    padding:.45rem .85rem;
    border-radius:999px;
    font-size:.75rem;
    font-weight:700;
    letter-spacing:.06em;
    text-transform:uppercase;
    background:rgba(255,255,255,.14);
    border:1px solid rgba(255,255,255,.18);
    margin-bottom:var(--space-4);
}

.fleet-chip.dark{
    background:#1d4ed8;
    color:#fff;
}

.fleet-hero h1{
    font-size:clamp(2rem,2vw + 1rem,3.4rem);
    line-height:1.05;
    letter-spacing:-.04em;
    margin-bottom:var(--space-4);
    max-width:13ch;
}

.fleet-hero h1 span{
    color:#bfdbfe;
}

.fleet-hero p{
    color:rgba(255,255,255,.78);
}

.fleet-hero__actions{
    display:flex;
    gap:var(--space-3);
    flex-wrap:wrap;
    margin-top:var(--space-6);
}

.fleet-hero__actions .btn-primary{
    background:#fff;
    color:#1d4ed8;
    border-color:#fff;
}

.fleet-hero__actions .btn-primary:hover{
    background:#eff6ff;
    color:#1e40af;
}

.fleet-hero__actions .btn-secondary{
    background:rgba(255,255,255,.12);
    color:#fff;
    border-color:rgba(255,255,255,.3);
}

.fleet-hero__actions .btn-secondary:hover{
    background:rgba(255,255,255,.2);
}

.fleet-visual-card.main{
    background:rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.2);
    border-radius:1.5rem;
    padding:2rem;
    min-width:240px;
    backdrop-filter:blur(8px);
}

.visual-label{
    font-size:var(--text-xs);
    text-transform:uppercase;
    color:rgba(255,255,255,.65);
}

.visual-value{
    font-size:clamp(3rem,5vw,4.5rem);
    font-weight:800;
}

.visual-meta{
    color:rgba(255,255,255,.68);
    font-size:var(--text-sm);
}

.fleet-visual-card.floating{
    background:#fff;
    color:#172033;
    border-radius:1rem;
    padding:.85rem 1rem;
    box-shadow:0 16px 30px rgba(15,63,174,.2);
}

.fleet-visual-card.floating.clean{
    position:static;
}

.stat-pill{
    display:inline-flex;
    align-items:center;
    gap:.45rem;
    padding:.55rem .9rem;
    border-radius:999px;
    font-size:.82rem;
    font-weight:800;
    white-space:nowrap;
}

.stat-pill.ok{
    background:#edf8f1;
    color:#2d6a4f;
}

.stat-pill.bad{
    background:#fff1f0;
    color:#c1121f;
}

.section-subtitle{
    font-size:var(--text-sm);
    color:var(--color-text-muted);
    margin-top:4px;
}

.section-head{
    display:flex;
    align-items:end;
    justify-content:space-between;
    gap:var(--space-4);
    margin-bottom:var(--space-5);
    flex-wrap:wrap;
}

.section-head__meta{
    padding:.55rem .95rem;
    border-radius:999px;
    border:1px solid rgba(37,99,235,.15);
    background:#eff6ff;
    font-size:var(--text-xs);
    color:#1d4ed8;
    font-weight:700;
}

.fleet-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(min(320px,100%),1fr));
    gap:var(--space-5);
}

.fleet-card{
    background:#fff;
    border:1px solid rgba(37,99,235,.12);
    border-radius:1.4rem;
    overflow:hidden;
    box-shadow:var(--shadow-sm);
    display:flex;
    flex-direction:column;
    transition:transform var(--transition),box-shadow var(--transition);
}

.fleet-card:hover{
    transform:translateY(-4px);
    box-shadow:0 16px 35px rgba(29,78,216,.13);
}

.fleet-card__visual{
    position:relative;
    min-height:220px;
    padding:var(--space-5);
    display:flex;
    align-items:center;
    justify-content:center;
    background:linear-gradient(180deg,#f8fbff 0%,#eaf2ff 100%);
    color:#2563eb;
}

.fleet-card__visual.has-photo{
    padding:0;
}

.fleet-card__visual img{
    width:100%;
    height:220px;
    object-fit:cover;
}

.fleet-card__badge{
    position:absolute;
    top:16px;
    left:16px;
    font-size:11px;
    font-weight:700;
    padding:.45rem .75rem;
    border-radius:999px;
    z-index:2;
}

.fleet-card__badge.ok{
    background:#edf8f1;
    color:#2d6a4f;
    border:1px solid rgba(45,106,79,.1);
}

.fleet-card__badge.busy{
    background:#fff1f0;
    color:#c1121f;
    border:1px solid rgba(193,18,31,.1);
}

.fleet-card__body{
    padding:var(--space-5);
    display:flex;
    flex-direction:column;
    gap:var(--space-4);
}

.fleet-card__title-row{
    display:flex;
    align-items:start;
    justify-content:space-between;
    gap:var(--space-3);
}

.fleet-card__title-row h3{
    font-size:1.2rem;
    color:#172033;
}

.fleet-card__title-row p{
    color:var(--color-text-muted);
    font-size:var(--text-sm);
}

.fleet-card__price{
    text-align:right;
    color:#1d4ed8;
}

.fleet-card__price span{
    font-weight:800;
}

.fleet-card__price small{
    color:var(--color-text-muted);
}

.fleet-card__detail-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:var(--space-3);
    background:#f8fbff;
    border:1px solid rgba(37,99,235,.08);
    border-radius:1rem;
    padding:var(--space-4);
}

.fleet-card__detail-grid span{
    display:block;
    font-size:11px;
    text-transform:uppercase;
    color:var(--color-text-faint);
}

.fleet-card__detail-grid strong{
    color:#172033;
}

.fleet-card__actions{
    display:flex;
    gap:var(--space-2);
    flex-wrap:wrap;
}

.fleet-card__actions .btn{
    flex:1 1 120px;
    justify-content:center;
}

.fleet-card__actions .btn-secondary{
    background:#eff6ff;
    color:#1d4ed8;
    border-color:#bfdbfe;
}

.fleet-card__actions .btn-secondary:hover{
    background:#dbeafe;
    color:#1e40af;
}

.fleet-card__actions .btn-danger{
    background:#fff1f0;
    color:#c1121f;
    border-color:#fecaca;
}

.fleet-card__actions .btn-danger:hover{
    background:#fee2e2;
}

.service-panel{
    margin-top:var(--space-8);
    background:linear-gradient(135deg,#0f3fae 0%,#1d4ed8 100%);
    color:#fff;
    border-radius:1.5rem;
    padding:clamp(1.5rem,3vw,2.25rem);
    display:grid;
    grid-template-columns:1.1fr .9fr;
    gap:var(--space-6);
    position:relative;
    overflow:hidden;
}

.service-panel::after{
    content:'';
    position:absolute;
    width:220px;
    height:220px;
    right:-90px;
    bottom:-100px;
    border-radius:50%;
    background:rgba(255,255,255,.08);
}

.service-panel__content,
.service-panel__contact{
    position:relative;
    z-index:1;
}

.service-panel .fleet-chip.dark{
    background:rgba(255,255,255,.14);
    border:1px solid rgba(255,255,255,.18);
}

.service-panel h2{
    color:#fff;
}

.service-panel p{
    color:rgba(255,255,255,.75);
}

.contact-card{
    background:#fff;
    color:#172033;
    border-radius:1rem;
    padding:var(--space-5);
    box-shadow:0 15px 35px rgba(15,63,174,.2);
}

.contact-card.primary{
    border:1px solid rgba(37,99,235,.1);
}

.contact-title{
    font-weight:800;
    color:#172033;
    margin-bottom:var(--space-3);
}

.contact-action{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:100%;
    padding:.9rem 1rem;
    background:#1d4ed8;
    color:#fff;
    text-decoration:none;
    border-radius:.9rem;
    font-weight:700;
    margin-bottom:var(--space-3);
    transition:background .2s ease,transform .2s ease;
}

.contact-action:hover{
    background:#1e40af;
    color:#fff;
    transform:translateY(-1px);
}

.contact-card small{
    color:#64748b;
}

.car-illustration{
    color:#2563eb;
}

.modal-shell{
    position:fixed;
    inset:0;
    z-index:220;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:var(--space-4);
}

.modal-backdrop{
    position:absolute;
    inset:0;
    background:rgba(15,23,42,.62);
}

.modal-card{
    position:relative;
    z-index:1;
    width:100%;
    max-width:720px;
    max-height:90dvh;
    overflow-y:auto;
    background:#fff;
    border:1px solid rgba(37,99,235,.12);
    border-radius:1.4rem;
    padding:var(--space-6);
    box-shadow:0 24px 48px rgba(15,63,174,.22);
}

.modal-card .card-header{
    border-bottom:1px solid rgba(37,99,235,.08);
}

.modal-card .card-title{
    color:#172033;
}

.modal-card .btn-primary{
    background:#1d4ed8;
    border-color:#1d4ed8;
    color:#fff;
}

.modal-card .btn-primary:hover{
    background:#1e40af;
    border-color:#1e40af;
}

.form-shell{
    border:1px solid rgba(37,99,235,.1);
    box-shadow:var(--shadow-sm);
}

.form-shell .card-header{
    border-bottom:1px solid rgba(37,99,235,.08);
}

.form-shell .card-title{
    color:#172033;
}

.form-input:focus,
.form-select:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 3px rgba(37,99,235,.12);
    outline:none;
}

.form-shell .btn-primary{
    background:#1d4ed8;
    border-color:#1d4ed8;
    color:#fff;
}

.form-shell .btn-primary:hover{
    background:#1e40af;
    border-color:#1e40af;
}

.stats-grid .stat-card{
    border:1px solid rgba(37,99,235,.1);
}

.stats-grid .stat-card--dark{
    background:linear-gradient(135deg,#0f3fae,#1d4ed8);
    color:#fff;
}

.stats-grid .stat-card--accent{
    background:#eff6ff;
    color:#1d4ed8;
    border-color:#bfdbfe;
}

.stats-grid .stat-card--light{
    background:#fff;
}

.empty-state{
    background:#f8fbff;
    border:1px dashed #bfdbfe;
    border-radius:1.25rem;
    padding:3rem 1.5rem;
    text-align:center;
    color:#64748b;
}

.empty-state svg{
    color:#2563eb;
    margin-bottom:var(--space-3);
}

.empty-state h3{
    color:#172033;
    margin-bottom:.5rem;
}

@media (max-width:980px){
    .fleet-hero,
    .service-panel{
        grid-template-columns:1fr;
    }

    .fleet-hero__visual{
        min-height:220px;
    }
}

@media (max-width:768px){
    .fleet-card__title-row{
        flex-direction:column;
    }

    .fleet-card__price{
        text-align:left;
    }
}

@media (max-width:560px){
    .fleet-hero{
        padding:1.25rem;
    }

    .fleet-hero h1{
        max-width:none;
    }

    .fleet-card__detail-grid{
        grid-template-columns:1fr;
    }

    .hero-mini-row{
        grid-template-columns:1fr;
    }

    .fleet-hero__visual{
        flex-direction:column;
        align-items:stretch;
    }

    .fleet-card__actions .btn{
        flex:1 1 100%;
    }

    .modal-card{
        padding:var(--space-4);
    }
}
</style>
<script>
function toggleForm(){const f=document.getElementById('form-tambah');f.style.display=f.style.display==='none'?'block':'none';}
function openEdit(row){document.getElementById('edit-id').value=row.id_motor;document.getElementById('edit-nama').value=row.nama_motor;document.getElementById('edit-merk').value=row.merk;document.getElementById('edit-tahun').value=row.tahun;document.getElementById('edit-harga').value=row.harga_sewa;document.getElementById('edit-foto').value=row.foto_motor||'';document.getElementById('edit-status').value=String(row.status).toLowerCase();document.getElementById('edit-modal').style.display='flex';}
function closeEdit(){document.getElementById('edit-modal').style.display='none';}
</script>
<?php include '_layout_footer.php'; ?>

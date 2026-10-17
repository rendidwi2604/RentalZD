<?php
require_once __DIR__ . '/config/bootstrap.php';
$nama = $_SESSION['nama_lengkap'] ?? 'User';
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Logout — ZD RENT</title>
<meta http-equiv="refresh" content="3;url=login.php">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html{-webkit-font-smoothing:antialiased;}
body{
  min-height:100dvh;
  font-family:'Plus Jakarta Sans',sans-serif;
  background:#111;
  display:flex;align-items:center;justify-content:center;
  padding:1.5rem;
}

.logout-card {
  background:#fff;
  border-radius:1.5rem;
  padding:3rem 2.5rem;
  text-align:center;
  max-width:400px;
  width:100%;
  box-shadow:0 32px 64px rgba(0,0,0,0.4);
  animation: fadeUp .5s cubic-bezier(0.16,1,0.3,1) both;
}

@keyframes fadeUp {
  from { opacity:0; transform:translateY(24px); }
  to   { opacity:1; transform:translateY(0); }
}

.icon-wrap {
  width:72px;height:72px;
  background:#f8f8f8;
  border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  margin:0 auto 1.5rem;
}

h2 {
  font-size:1.375rem;
  font-weight:800;
  letter-spacing:-.02em;
  margin-bottom:.5rem;
  color:#111;
}

p {
  color:#666;
  font-size:.9rem;
  line-height:1.6;
  margin-bottom:1.5rem;
}

.progress-bar {
  height:3px;
  background:#f0f0f0;
  border-radius:99px;
  overflow:hidden;
  margin-bottom:1.5rem;
}

.progress-fill {
  height:100%;
  background:#111;
  border-radius:99px;
  animation:fill 3s linear forwards;
}

@keyframes fill {
  from { width:0 }
  to   { width:100% }
}

.brand {
  display:flex;align-items:center;justify-content:center;gap:.625rem;
  margin-bottom:2rem;
}

.brand-logo {
  width:36px;height:36px;
  background:#E63946;
  border-radius:.5rem;
  display:flex;align-items:center;justify-content:center;
}

.brand-name {
  font-size:1.125rem;
  font-weight:800;
  letter-spacing:-.01em;
  color:#111;
}

.btn-login {
  display:inline-flex;align-items:center;gap:.5rem;
  padding:.75rem 1.5rem;
  background:#111;
  color:#fff;
  border-radius:.75rem;
  font-size:.875rem;
  font-weight:700;
  text-decoration:none;
  transition:background .18s,transform .18s,box-shadow .18s;
}
.btn-login:hover{background:#333;transform:translateY(-1px);box-shadow:0 8px 20px rgba(0,0,0,0.15);}
.btn-login:active{transform:translateY(0);}

.redirect-note {
  font-size:.75rem;
  color:#bbb;
  margin-top:1rem;
}
</style>
</head>
<body>
<div class="logout-card">
  <div class="brand">
    <div class="brand-logo">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3"/>
        <rect x="9" y="11" width="14" height="10" rx="2"/>
        <circle cx="12" cy="16" r="1"/><circle cx="20" cy="16" r="1"/>
      </svg>
    </div>
    <div class="brand-name">ZD RENT</div>
  </div>

  <div class="icon-wrap">
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#111" stroke-width="2">
      <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
      <polyline points="16 17 21 12 16 7"/>
      <line x1="21" y1="12" x2="9" y2="12"/>
    </svg>
  </div>

  <h2>Sampai Jumpa, <?= htmlspecialchars($nama) ?>!</h2>
  <p>Anda telah berhasil keluar dari sistem.<br>Terima kasih telah menggunakan ZD RENT.</p>

  <div class="progress-bar">
    <div class="progress-fill"></div>
  </div>

  <a href="login.php" class="btn-login">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
      <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
      <polyline points="10 17 15 12 10 7"/>
      <line x1="15" y1="12" x2="3" y2="12"/>
    </svg>
    Masuk Kembali
  </a>

  <div class="redirect-note">Redirect otomatis dalam 3 detik...</div>
</div>
</body>
</html>

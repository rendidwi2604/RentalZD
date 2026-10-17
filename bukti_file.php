<?php
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/config/storage.php';
if (!isset($_SESSION['id_user'])) { http_response_code(401); exit('Silakan login.'); }
$name = $_GET['file'] ?? '';
if (!preg_match('/^[a-zA-Z0-9_.-]+\.(jpg|jpeg|png|webp|pdf)$/', $name)) {
    http_response_code(404); exit('File tidak ditemukan.');
}
$params = ['uploads/bukti/' . $name];
$sql = 'SELECT id FROM bukti_bayar WHERE file_path = ?';
if ($_SESSION['role'] !== 'admin') {
    $sql .= ' AND id_user = ?';
    $params[] = (int)$_SESSION['id_user'];
}
if (!db_fetch_assoc(db_query($conn, $sql, $params))) {
    http_response_code(404); exit('File tidak ditemukan.');
}
session_write_close();
header('Cache-Control: private, no-store');
try {
    header('Location: ' . storage_signed_url($name), true, 302);
} catch (RuntimeException $e) {
    http_response_code(503); echo 'Bukti pembayaran belum tersedia di Storage.';
}

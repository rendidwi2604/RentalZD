<?php
require_once __DIR__ . '/config/bootstrap.php';
if (isset($_SESSION['id_user'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: rentalmobil.php');
    } else {
        header('Location: pesan_mobil.php');
    }
    exit;
}
header('Location: login.php');
exit;

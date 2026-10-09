<?php
// petugas/login/cek-login.php
// Dipanggil di baris paling atas index.php dan semua file function/.
// Kalau belum login (atau bukan petugas gudang), langsung dilempar ke halaman login.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'gudang') {
    header('Location: /e-inventory/petugas/login/login.php?pesan=belum_login');
    exit;
}
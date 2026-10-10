<?php
// manajer/login/cek-login.php
// Dipanggil di baris paling atas index.php dan semua file function/.
// Kalau belum login (atau bukan manajer), langsung dilempar ke halaman login.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'manajer') {
    header('Location: /e-inventory/manajer/login/login.php?pesan=belum_login');
    exit;
}
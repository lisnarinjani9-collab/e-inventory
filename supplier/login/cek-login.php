<?php
// supplier/login/cek-login.php
// Dipanggil paling awal di partials/head.php (halaman utama) dan di file function/ yang butuh login.
// Kalau belum login (atau bukan supplier), langsung diarahkan ke halaman register.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'supplier') {
    $tujuan = '/supplier/login/register.php?pesan=belum_login';

    if (!headers_sent()) {
        header('Location: ' . $tujuan);
    } else {
        // Halaman sudah mulai tercetak (index.php mencetak <html> sebelum include head), jadi pakai pengalihan di browser
        echo '<meta http-equiv="refresh" content="0;url=' . $tujuan . '">';
        echo '<script>window.location.replace("' . $tujuan . '");</script>';
    }
    exit;
}
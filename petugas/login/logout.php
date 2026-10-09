<?php
// petugas/login/logout.php

session_start();

// Kosongkan semua data session
$_SESSION = [];

// Hapus cookie session di browser
if (ini_get('session.use_cookies')) {
    $pengaturan_cookie = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $pengaturan_cookie['path'],
        $pengaturan_cookie['domain'],
        $pengaturan_cookie['secure'],
        $pengaturan_cookie['httponly']
    );
}

// Hancurkan session lalu kembali ke halaman login
session_destroy();

header('Location: /e-inventory/petugas/login/login.php?pesan=logout');
exit;
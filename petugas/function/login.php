<?php
// petugas/function/login.php

include_once __DIR__ . '/../database/connection.php';

session_start();

$koneksi = (new Database())->conn;

$aksi          = $_GET['aksi'] ?? '';
$base          = '/e-inventory/petugas/';
$halaman_login = $base . 'login/login.php';

// ---------- MASUK ----------
if ($aksi === 'masuk' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 1. Cek form tidak kosong
    if ($email === '' || $password === '') {
        header("Location: $halaman_login?pesan=kosong");
        exit;
    }

    // 2. Cari pengguna berdasarkan email
    $cari_pengguna = $koneksi->prepare(
        "SELECT id, nama, email, password, role FROM users WHERE email = ?"
    );
    $cari_pengguna->bind_param('s', $email);
    $cari_pengguna->execute();
    $pengguna = $cari_pengguna->get_result()->fetch_assoc();

    // 3. Cek email dan password (pesan sengaja dibuat sama supaya tidak membocorkan email mana yang terdaftar)
    if (!$pengguna || !password_verify($password, $pengguna['password'])) {
        header("Location: $halaman_login?pesan=salah");
        exit;
    }

    // 4. Hanya role 'gudang' yang boleh masuk ke tampilan petugas
    if ($pengguna['role'] !== 'gudang') {
        header("Location: $halaman_login?pesan=bukan_petugas");
        exit;
    }

    // 5. Simpan data petugas ke session
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $pengguna['id'];
    $_SESSION['nama']    = $pengguna['nama'];
    $_SESSION['email']   = $pengguna['email'];
    $_SESSION['role']    = $pengguna['role'];

    header("Location: {$base}index.php?page=dashboard");
    exit;
}

// Aksi tidak dikenal
header("Location: $halaman_login");
exit;
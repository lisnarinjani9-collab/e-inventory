<?php
// manajer/function/petugas-gudang.php
// Manajer hanya bisa:
//   tambah : membuatkan akun petugas gudang (role 'gudang')
//   hapus  : menghapus akun petugas gudang

include_once __DIR__ . '/../login/cek-login.php';
include_once __DIR__ . '/../database/connection.php';

$koneksi = (new Database())->conn;

$aksi = $_GET['aksi'] ?? '';
$base        = '/e-inventory/manajer/index.php?page=petugas-gudang';
$base_tambah = '/e-inventory/manajer/index.php?page=tambah-petugas-gudang';

// Semua aksi hanya boleh lewat form (POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $base");
    exit;
}

// ---------- TAMBAH ----------
if ($aksi === 'tambah') {
    $nama        = trim($_POST['nama'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';
    $konfirmasi  = $_POST['konfirmasi_password'] ?? '';

    // Simpan isian sementara supaya form tidak kosong lagi kalau ada yang salah
    $_SESSION['isian_petugas'] = ['nama' => $nama, 'email' => $email];

    // 1. Semua kolom wajib diisi
    if ($nama === '' || $email === '' || $password === '' || $konfirmasi === '') {
        header("Location: $base_tambah&pesan=kosong");
        exit;
    }

    // 2. Format email harus benar
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: $base_tambah&pesan=email_tidak_valid");
        exit;
    }

    // 3. Password minimal 6 karakter dan harus sama dengan konfirmasi
    if (strlen($password) < 6) {
        header("Location: $base_tambah&pesan=password_pendek");
        exit;
    }
    if ($password !== $konfirmasi) {
        header("Location: $base_tambah&pesan=password_beda");
        exit;
    }

    // 4. Email tidak boleh sudah dipakai akun lain
    $cek_email = $koneksi->prepare("SELECT id FROM users WHERE email = ?");
    $cek_email->bind_param('s', $email);
    $cek_email->execute();
    if ($cek_email->get_result()->num_rows > 0) {
        header("Location: $base_tambah&pesan=email_dipakai");
        exit;
    }

    // 5. Simpan akun baru dengan role 'gudang' (password disimpan dalam bentuk hash)
    $role       = 'gudang';
    $password_h = password_hash($password, PASSWORD_DEFAULT);
    $simpan     = $koneksi->prepare(
        "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)"
    );
    $simpan->bind_param('ssss', $nama, $email, $password_h, $role);
    $simpan->execute();

    unset($_SESSION['isian_petugas']);
    header("Location: $base&pesan=tambah_ok");
    exit;
}

// ---------- HAPUS ----------
if ($aksi === 'hapus') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        header("Location: $base&pesan=tidak_valid");
        exit;
    }

    // Hanya akun dengan role 'gudang' yang boleh dihapus (akun manajer dan supplier aman)
    try {
        $hapus = $koneksi->prepare("DELETE FROM users WHERE id = ? AND role = 'gudang'");
        $hapus->bind_param('i', $id);
        $hapus->execute();
        $pesan = $hapus->affected_rows > 0 ? 'hapus_ok' : 'tidak_ditemukan';
    } catch (mysqli_sql_exception $e) {
        // Akun masih dipakai di data lain (relasi tabel)
        $pesan = 'tidak_bisa_hapus';
    }

    header("Location: $base&pesan=$pesan");
    exit;
}

// Aksi tidak dikenal
header("Location: $base");
exit;
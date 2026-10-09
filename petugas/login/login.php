<?php
// petugas/login/login.php

session_start();

// Kalau sudah login sebagai petugas, langsung ke dashboard
if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'gudang') {
    header('Location: /e-inventory/petugas/index.php?page=dashboard');
    exit;
}

$daftar_pesan = [
    'belum_login'   => ['warning', 'Silakan login terlebih dahulu.'],
    'logout'        => ['success', 'Anda berhasil logout.'],
    'kosong'        => ['danger',  'Email dan password wajib diisi.'],
    'salah'         => ['danger',  'Email atau password salah.'],
    'bukan_petugas' => ['danger',  'Akun ini bukan akun Petugas Gudang.'],
];
$pesan = $daftar_pesan[$_GET['pesan'] ?? ''] ?? null;
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <title>Login Petugas Gudang || E-INVENTORY</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" sizes="32x32" href="../template/src/assets/images/favicon_io/favicon-32x32.png">
  <link rel="stylesheet" crossorigin href="../template/src/assets/css/main.css">
  <!-- Font ikon Tabler (harus SETELAH main.css) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.35.0/dist/tabler-icons.min.css">
</head>

<body>

<div class="container d-flex align-items-center justify-content-center min-vh-100">
  <div class="card" style="max-width:420px; width:100%;">
    <div class="card-body p-5">
      <div class="text-center mb-3">
        <a href="login.php" class="mb-4 d-inline-block"><img src="../template/src/assets/images/logo-icon.svg" alt="" width="36">
          <span class="ms-2"> <img src="../template/src/assets/images/logo.svg" alt=""></span>
        </a>
        <h1 class="card-title mb-1 h5">Login Petugas Gudang</h1>
        <p class="text-secondary small mb-4">E-Inventory - Manajemen Logistik &amp; Stok Gudang</p>
      </div>

      <?php if ($pesan): ?>
        <div class="alert alert-<?= $pesan[0] ?> small"><?= $pesan[1] ?></div>
      <?php endif; ?>

      <form method="post" action="../function/login.php?aksi=masuk" class="mt-3">
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input id="email" name="email" type="email" class="form-control" placeholder="nama@email.com" required autofocus>
        </div>

        <div class="mb-4">
          <label for="password" class="form-label">Password</label>
          <div class="input-group">
            <input id="password" name="password" type="password" class="form-control" placeholder="Password" required>
            <button type="button" class="btn btn-outline-secondary" id="tombolLihat" aria-label="Tampilkan password">
              <i class="ti ti-eye"></i>
            </button>
          </div>
        </div>

        <button class="btn btn-primary w-100" type="submit">Login</button>
      </form>
    </div>
  </div>
</div>

<script>
  // Tombol mata: tampilkan / sembunyikan password
  document.getElementById('tombolLihat').addEventListener('click', function () {
    var kolom_password = document.getElementById('password');
    var ikon = this.querySelector('i');
    var sedang_tersembunyi = kolom_password.type === 'password';
    kolom_password.type = sedang_tersembunyi ? 'text' : 'password';
    ikon.className = sedang_tersembunyi ? 'ti ti-eye-off' : 'ti ti-eye';
  });
</script>

</body>

</html>
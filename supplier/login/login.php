<?php
// supplier/login/login.php

session_start();

// Kalau sudah login sebagai supplier, langsung ke halaman utama
if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'supplier') {
    header('Location: /');
    exit;
}

$daftar_pesan = [
    'belum_login'   => ['warning', 'Silakan login terlebih dahulu.'],
    'logout'        => ['success', 'Anda berhasil logout.'],
    'daftar_ok'     => ['success', 'Pendaftaran berhasil. Silakan login dengan akun Anda.'],
    'kosong'        => ['danger',  'Email dan password wajib diisi.'],
    'salah'         => ['danger',  'Email atau password salah.'],
    'bukan_supplier' => ['danger', 'Akun ini bukan akun Supplier.'],
];
$pesan = $daftar_pesan[$_GET['pesan'] ?? ''] ?? null;

$judul_halaman = 'Login Supplier | E-Inventory';
?>
<!DOCTYPE html>
<html lang="id">

<?php include __DIR__ . '/../partials/head-meta.php'; ?>

<body class="auth-page">
  <div class="auth-wrap">

    <!-- Panel kiri -->
    <div class="auth-side">
      <a href="/supplier/login/login.php" class="auth-brand">
        <img src="/supplier/template/assets/img/logo-icon.svg" alt=""> E-Inventory
      </a>

      <div>
        <h2>Selamat datang kembali, Supplier.</h2>
        <p class="auth-lead">Masuk untuk menambah bahan baku, memperbarui harga, dan memantau Purchase Order dari gudang.</p>
        <ul class="auth-points list-unstyled">
          <li><span class="pt-icon"><i class="bi bi-cash-coin"></i></span>
            <div><strong>Penawaran harga</strong><br>Tambah bahan baku &amp; ubah harga kapan saja.</div></li>
          <li><span class="pt-icon"><i class="bi bi-clipboard-check"></i></span>
            <div><strong>Status Purchase Order</strong><br>Diajukan, disetujui, hingga selesai.</div></li>
          <li><span class="pt-icon"><i class="bi bi-clock-history"></i></span>
            <div><strong>Riwayat PO</strong><br>Semua pesanan tercatat rapi.</div></li>
        </ul>
      </div>

      <small class="auth-foot">Manajemen Logistik &amp; Stok Gudang</small>
    </div>

    <!-- Form -->
    <div class="auth-main">
      <div class="auth-card">
        <a href="/supplier/login/login.php" class="auth-brand-mobile d-lg-none">
          <img src="/supplier/template/assets/img/logo-icon.svg" alt=""> E-Inventory
        </a>
        <h1>Login Supplier</h1>
        <p class="text-secondary mb-4">Masukkan email dan password akun supplier Anda.</p>

        <?php if ($pesan): ?>
          <div class="alert alert-<?= $pesan[0] ?> small"><?= $pesan[1] ?></div>
        <?php endif; ?>

        <form method="post" action="/supplier/function/proses-login.php?aksi=masuk">
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="email" class="form-control" placeholder="nama@perusahaan.com" required autofocus>
          </div>

          <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
              <input id="password" name="password" type="password" class="form-control" placeholder="Password" required>
              <button type="button" class="btn btn-outline-secondary toggle-pass" data-target="password" aria-label="Tampilkan password">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

          <button class="btn-auth" type="submit">Login</button>
        </form>

        <p class="text-center small mt-4 mb-0">Belum punya akun? <a href="/supplier/login/register.php" class="fw-semibold">Daftar sebagai supplier</a></p>
      </div>
    </div>

  </div>

  <?php include __DIR__ . '/../partials/auth-script.php'; ?>
</body>

</html>
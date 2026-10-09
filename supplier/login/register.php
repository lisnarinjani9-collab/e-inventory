<?php
// supplier/login/register.php
// Halaman tujuan pengunjung yang belum login.

session_start();

// Kalau sudah login sebagai supplier, langsung ke halaman utama
if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'supplier') {
    header('Location: /');
    exit;
}

$daftar_pesan = [
    'belum_login'         => ['warning', 'Silakan daftar atau login terlebih dahulu untuk mengakses portal supplier.'],
    'kosong'              => ['danger',  'Semua kolom wajib diisi.'],
    'email_tidak_valid'   => ['danger',  'Format email tidak valid.'],
    'telepon_tidak_valid' => ['danger',  'Nomor telepon tidak valid (8-20 digit, boleh memakai + dan -).'],
    'password_pendek'     => ['danger',  'Password minimal 6 karakter.'],
    'password_beda'       => ['danger',  'Konfirmasi password tidak sama.'],
    'terlalu_panjang'     => ['danger',  'Isian terlalu panjang (perusahaan maks 150, nama maks 100, email maks 100 karakter).'],
    'email_dipakai'       => ['danger',  'Email sudah terdaftar. Silakan login.'],
    'gagal'               => ['danger',  'Pendaftaran gagal diproses. Coba lagi beberapa saat.'],
];
$pesan = $daftar_pesan[$_GET['pesan'] ?? ''] ?? null;

// Isian sebelumnya (kalau ada kesalahan) lalu dihapus supaya tidak muncul terus
$lama = $_SESSION['isian_lama'] ?? [];
unset($_SESSION['isian_lama']);
function lama($lama, $kunci)
{
    return htmlspecialchars($lama[$kunci] ?? '', ENT_QUOTES, 'UTF-8');
}

$judul_halaman = 'Daftar Supplier | E-Inventory';
?>
<!DOCTYPE html>
<html lang="id">

<?php include __DIR__ . '/../partials/head-meta.php'; ?>

<body class="auth-page">
  <div class="auth-wrap">

    <!-- Panel kiri -->
    <div class="auth-side">
      <a href="/supplier/login/register.php" class="auth-brand">
        <img src="/supplier/template/assets/img/logo-icon.svg" alt=""> E-Inventory
      </a>

      <div>
        <h2>Jadi supplier bahan baku E-Inventory.</h2>
        <p class="auth-lead">Daftarkan perusahaan Anda untuk menawarkan harga bahan baku dan memantau Purchase Order langsung dari gudang.</p>
        <ul class="auth-points list-unstyled">
          <li><span class="pt-icon"><i class="bi bi-building-add"></i></span>
            <div><strong>1. Daftarkan perusahaan</strong><br>Isi data perusahaan dan buat akun.</div></li>
          <li><span class="pt-icon"><i class="bi bi-cash-coin"></i></span>
            <div><strong>2. Input penawaran harga</strong><br>Perbarui harga satuan bahan baku.</div></li>
          <li><span class="pt-icon"><i class="bi bi-truck"></i></span>
            <div><strong>3. Penuhi Purchase Order</strong><br>Pantau status PO sampai barang diterima gudang.</div></li>
        </ul>
      </div>

      <small class="auth-foot">Manajemen Logistik &amp; Stok Gudang</small>
    </div>

    <!-- Form -->
    <div class="auth-main">
      <div class="auth-card">
        <a href="/supplier/login/register.php" class="auth-brand-mobile d-lg-none">
          <img src="/supplier/template/assets/img/logo-icon.svg" alt=""> E-Inventory
        </a>
        <h1>Daftar Supplier</h1>
        <p class="text-secondary mb-4">Buat akun supplier untuk mulai menggunakan portal.</p>

        <?php if ($pesan): ?>
          <div class="alert alert-<?= $pesan[0] ?> small"><?= $pesan[1] ?></div>
        <?php endif; ?>

        <form method="post" action="/supplier/function/proses-login.php?aksi=daftar">
          <div class="mb-3">
            <label for="nama_perusahaan" class="form-label">Nama Perusahaan</label>
            <input id="nama_perusahaan" name="nama_perusahaan" type="text" maxlength="150" class="form-control" placeholder="PT Sumber Makmur" value="<?= lama($lama, 'nama_perusahaan') ?>" required autofocus>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="nama" class="form-label">Nama Penanggung Jawab</label>
              <input id="nama" name="nama" type="text" maxlength="100" class="form-control" placeholder="Nama lengkap" value="<?= lama($lama, 'nama') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="telepon" class="form-label">No. Telepon</label>
              <input id="telepon" name="telepon" type="tel" maxlength="20" class="form-control" placeholder="08xxxxxxxxxx" value="<?= lama($lama, 'telepon') ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="email" maxlength="100" class="form-control" placeholder="nama@perusahaan.com" value="<?= lama($lama, 'email') ?>" required>
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
              <input id="password" name="password" type="password" class="form-control" placeholder="Minimal 6 karakter" minlength="6" required>
              <button type="button" class="btn btn-outline-secondary toggle-pass" data-target="password" aria-label="Tampilkan password">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

          <div class="mb-4">
            <label for="konfirmasi" class="form-label">Konfirmasi Password</label>
            <div class="input-group">
              <input id="konfirmasi" name="konfirmasi" type="password" class="form-control" placeholder="Ulangi password" required>
              <button type="button" class="btn btn-outline-secondary toggle-pass" data-target="konfirmasi" aria-label="Tampilkan password">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

          <button class="btn-auth" type="submit">Daftar</button>
        </form>

        <p class="text-center small mt-4 mb-0">Sudah punya akun? <a href="/supplier/login/login.php" class="fw-semibold">Login</a></p>
      </div>
    </div>

  </div>

  <?php include __DIR__ . '/../partials/auth-script.php'; ?>
</body>

</html>
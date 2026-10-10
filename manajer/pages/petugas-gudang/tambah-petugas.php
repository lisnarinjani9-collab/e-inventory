<?php
// Halaman form tambah akun petugas gudang
$daftar_pesan = [
    'kosong'            => 'Semua kolom wajib diisi.',
    'email_tidak_valid' => 'Format email tidak valid.',
    'password_pendek'   => 'Password minimal 6 karakter.',
    'password_beda'     => 'Konfirmasi password tidak sama dengan password.',
    'email_dipakai'     => 'Email sudah dipakai akun lain.',
];
$pesan_error = $daftar_pesan[$_GET['pesan'] ?? ''] ?? null;

// Isian sebelumnya (kalau ada yang salah, form tidak kosong lagi)
$isian_lama = $pesan_error ? ($_SESSION['isian_petugas'] ?? ['nama' => '', 'email' => '']) : ['nama' => '', 'email' => ''];
if (!$pesan_error) {
    unset($_SESSION['isian_petugas']);
}
?>
  <!-- Sembunyikan scrollbar bawaan browser di halaman ini (halaman tetap bisa di-scroll) -->
  <style>
    html { scrollbar-width: none; -ms-overflow-style: none; }
    html::-webkit-scrollbar { display: none; width: 0; height: 0; }
  </style>
  <main id="content" class="content py-10">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
            <div>
              <h1 class="fs-3 mb-1">Tambah Akun Petugas</h1>
              <p class="mb-0">Buatkan akun baru untuk petugas gudang</p>
            </div>
            <a href="index.php?page=petugas-gudang" class="btn btn-outline-secondary"><i class="ti ti-arrow-left"></i> Kembali</a>
          </div>
        </div>
      </div>

      <?php if ($pesan_error): ?>
        <div class="alert alert-danger"><?= $pesan_error ?></div>
      <?php endif; ?>

      <div class="row">
        <div class="col-12 col-lg-8 col-xl-6">
          <div class="card">
            <div class="card-body p-4">
              <form method="post" action="function/petugas-gudang.php?aksi=tambah" autocomplete="off">
                <div class="mb-3">
                  <label for="nama" class="form-label">Nama Petugas</label>
                  <input type="text" id="nama" name="nama" class="form-control" placeholder="Nama lengkap petugas" value="<?= htmlspecialchars($isian_lama['nama']) ?>" required>
                </div>
                <div class="mb-3">
                  <label for="email" class="form-label">Email</label>
                  <input type="email" id="email" name="email" class="form-control" placeholder="nama@email.com" value="<?= htmlspecialchars($isian_lama['email']) ?>" required>
                </div>
                <div class="mb-3">
                  <label for="password" class="form-label">Password</label>
                  <input type="password" id="password" name="password" class="form-control" placeholder="Minimal 6 karakter" minlength="6" required>
                </div>
                <div class="mb-4">
                  <label for="konfirmasi_password" class="form-label">Konfirmasi Password</label>
                  <input type="password" id="konfirmasi_password" name="konfirmasi_password" class="form-control" placeholder="Ulangi password" minlength="6" required>
                </div>
                <div class="d-flex gap-2">
                  <button type="submit" class="btn btn-primary">Simpan Akun</button>
                  <a href="index.php?page=petugas-gudang" class="btn btn-outline-secondary">Batal</a>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-12">
          <footer class="text-center py-2 mt-6 text-secondary ">
            <p class="mb-0">Copyright © 2026 E-Inventory Manajemen Logistik &amp; Stok Gudang. Developed by <a href="https://codescandy.com/" target="_blank" class="text-primary">CodesCandy</a> • Distributed by <a href="https://themewagon.com/" target="_blank" class="text-primary">ThemeWagon</a> </p>
          </footer>
        </div>
      </div>

    </div>
  </main>
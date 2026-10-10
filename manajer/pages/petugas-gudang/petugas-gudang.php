<?php
include_once __DIR__ . '/../../database/connection.php';
$koneksi = (new Database())->conn;

// ---------- PESAN ----------
$daftar_pesan = [
    'tambah_ok'         => ['success', 'Akun petugas gudang berhasil dibuat.'],
    'hapus_ok'          => ['success', 'Akun petugas gudang berhasil dihapus.'],
    'kosong'            => ['danger',  'Semua kolom wajib diisi.'],
    'email_tidak_valid' => ['danger',  'Format email tidak valid.'],
    'password_pendek'   => ['danger',  'Password minimal 6 karakter.'],
    'password_beda'     => ['danger',  'Konfirmasi password tidak sama dengan password.'],
    'email_dipakai'     => ['danger',  'Email sudah dipakai akun lain.'],
    'tidak_ditemukan'   => ['warning', 'Akun petugas tidak ditemukan atau sudah dihapus.'],
    'tidak_bisa_hapus'  => ['warning', 'Akun ini tidak bisa dihapus karena masih terhubung dengan data lain.'],
    'tidak_valid'       => ['danger',  'Data tidak valid.'],
];
$kode_pesan = $_GET['pesan'] ?? '';
$pesan      = $daftar_pesan[$kode_pesan] ?? null;

// Kalau pembuatan akun gagal, form dibuka lagi dengan isian sebelumnya
$pesan_gagal_tambah = ['kosong', 'email_tidak_valid', 'password_pendek', 'password_beda', 'email_dipakai'];
$buka_form          = in_array($kode_pesan, $pesan_gagal_tambah, true);
$isian_lama         = $_SESSION['isian_petugas'] ?? ['nama' => '', 'email' => ''];
if (!$buka_form) {
    unset($_SESSION['isian_petugas']);
    $isian_lama = ['nama' => '', 'email' => ''];
}

// ---------- PENCARIAN DAN HALAMAN ----------
$kata_cari        = trim($_GET['cari'] ?? '');
$pola_cari        = '%' . $kata_cari . '%';
$data_per_halaman = 10;
$halaman_aktif    = max(1, (int) ($_GET['halaman'] ?? 1));

// ---------- HITUNG TOTAL DATA ----------
$statement_total = $koneksi->prepare(
    "SELECT COUNT(*) AS total_data FROM users
     WHERE role = 'gudang' AND (nama LIKE ? OR email LIKE ?)"
);
$statement_total->bind_param('ss', $pola_cari, $pola_cari);
$statement_total->execute();
$total_data    = (int) $statement_total->get_result()->fetch_assoc()['total_data'];
$total_halaman = max(1, (int) ceil($total_data / $data_per_halaman));
$halaman_aktif = min($halaman_aktif, $total_halaman);
$mulai_dari    = ($halaman_aktif - 1) * $data_per_halaman;

// ---------- AMBIL DATA PETUGAS ----------
$statement_data = $koneksi->prepare(
    "SELECT id, nama, email FROM users
     WHERE role = 'gudang' AND (nama LIKE ? OR email LIKE ?)
     ORDER BY nama ASC, id ASC
     LIMIT ? OFFSET ?"
);
$statement_data->bind_param('ssii', $pola_cari, $pola_cari, $data_per_halaman, $mulai_dari);
$statement_data->execute();
$hasil_data = $statement_data->get_result();

$nomor_urut = $mulai_dari + 1;

function linkHalamanPetugas($nomor_halaman, $kata_cari)
{
    return 'index.php?page=petugas-akun&cari=' . urlencode($kata_cari) . '&halaman=' . $nomor_halaman;
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
              <h1 class="fs-3 mb-1">Akun Petugas</h1>
              <p class="mb-0">Buatkan akun untuk petugas gudang atau hapus akun yang sudah tidak dipakai</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahPetugas">Tambah Akun Petugas</button>
          </div>
        </div>
      </div>

      <?php if ($pesan): ?>
        <div class="alert alert-<?= $pesan[0] ?>"><?= $pesan[1] ?></div>
      <?php endif; ?>

      <div class="row">
        <div class="col-12">
          <form method="get" action="index.php" class="d-flex gap-2 mb-3 flex-wrap">
            <input type="hidden" name="page" value="petugas-akun">
            <input type="text" name="cari" class="form-control" placeholder="Cari nama atau email petugas..." value="<?= htmlspecialchars($kata_cari) ?>" style="max-width: 300px;">
            <button type="submit" class="btn btn-outline-secondary"><i class="ti ti-search"></i> Cari</button>
          </form>

          <div class="card table-responsive">
            <table class="table mb-0 text-nowrap table-hover">
              <thead class="table-light border-light">
                <tr>
                  <th>No</th>
                  <th>Nama Petugas</th>
                  <th>Email</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($hasil_data->num_rows === 0): ?>
                  <tr>
                    <td colspan="4" class="text-center text-secondary py-4">Belum ada akun petugas gudang.</td>
                  </tr>
                <?php endif; ?>

                <?php while ($petugas = $hasil_data->fetch_assoc()): ?>
                  <tr class="align-middle">
                    <td><?= $nomor_urut++ ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($petugas['nama']) ?></td>
                    <td><?= htmlspecialchars($petugas['email']) ?></td>
                    <td>
                      <form method="post" action="function/petugas-akun.php?aksi=hapus" class="d-inline"
                        onsubmit="return confirm('Hapus akun petugas <?= htmlspecialchars(addslashes($petugas['nama']), ENT_QUOTES) ?>? Akun yang dihapus tidak bisa login lagi.')">
                        <input type="hidden" name="id" value="<?= $petugas['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                      </form>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
              <tfoot class="">
                <tr>
                  <td colspan="2" class="border-bottom-0">Menampilkan <?= $hasil_data->num_rows ?> dari <?= $total_data ?> data</td>
                  <td colspan="2" class="border-bottom-0">
                    <nav aria-label="Page navigation" class="d-flex justify-content-end">
                      <ul class="pagination mb-0">
                        <li class="page-item <?= $halaman_aktif <= 1 ? 'disabled' : '' ?>">
                          <a class="page-link" href="<?= linkHalamanPetugas($halaman_aktif - 1, $kata_cari) ?>">Sebelumnya</a>
                        </li>
                        <?php for ($nomor_halaman = 1; $nomor_halaman <= $total_halaman; $nomor_halaman++): ?>
                          <li class="page-item <?= $nomor_halaman === $halaman_aktif ? 'active' : '' ?>">
                            <a class="page-link" href="<?= linkHalamanPetugas($nomor_halaman, $kata_cari) ?>"><?= $nomor_halaman ?></a>
                          </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $halaman_aktif >= $total_halaman ? 'disabled' : '' ?>">
                          <a class="page-link" href="<?= linkHalamanPetugas($halaman_aktif + 1, $kata_cari) ?>">Berikutnya</a>
                        </li>
                      </ul>
                    </nav>
                  </td>
                </tr>
              </tfoot>
            </table>
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

  <!-- Form tambah akun petugas -->
  <div class="modal fade" id="modalTambahPetugas" tabindex="-1" aria-labelledby="judulModalTambahPetugas" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="post" action="function/petugas-akun.php?aksi=tambah" autocomplete="off">
          <div class="modal-header">
            <h5 class="modal-title" id="judulModalTambahPetugas">Tambah Akun Petugas</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
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
            <div class="mb-0">
              <label for="konfirmasi_password" class="form-label">Konfirmasi Password</label>
              <input type="password" id="konfirmasi_password" name="konfirmasi_password" class="form-control" placeholder="Ulangi password" minlength="6" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Buat Akun</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php if ($buka_form): ?>
    <script>
      // Buka lagi form tambah akun kalau ada isian yang salah
      window.addEventListener('load', function () {
        var modal = document.getElementById('modalTambahPetugas');
        if (modal && window.bootstrap) {
          new bootstrap.Modal(modal).show();
        } else if (modal) {
          modal.classList.add('show');
          modal.style.display = 'block';
        }
      });
    </script>
  <?php endif; ?>
<?php
// Variabel di bawah disiapkan di function/data-dashboard.php (lewat partials/head.php).
// Baris ??= hanya memberi nilai awal supaya editor tidak menandai "undefined variable";
// kalau variabelnya sudah ada, nilainya tidak berubah.
include_once __DIR__ . '/../function/helper.php';
$pesan               ??= null;
$daftar_barang       ??= null;

/**
 * @var array|null $pesan
 * @var mysqli_result|null $daftar_barang
 */
?>
<section id="harga" class="services section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Penawaran Harga</h2>
        <p>Tambahkan bahan baku yang Anda tawarkan dan perbarui harga satuannya untuk gudang</p>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <?php if ($pesan): ?>
          <div class="alert alert-<?= $pesan[0] ?> small"><?= $pesan[1] ?></div>
        <?php endif; ?>

        <!-- Form tambah bahan baku baru -->
        <div class="panel p-4 mb-4">
          <h5 class="mb-1">Tambah Bahan Baku</h5>
          <p class="text-secondary small mb-3">Bahan baku yang Anda tambahkan akan langsung muncul di tampilan petugas gudang.</p>
          <form method="post" action="/supplier/function/barang.php" class="row g-3 align-items-end">
            <div class="col-md-5">
              <label for="nama_barang" class="form-label">Nama Bahan Baku</label>
              <input type="text" id="nama_barang" name="nama_barang" class="form-control" maxlength="150" placeholder="Contoh: Tepung Terigu 25 kg" required>
            </div>
            <div class="col-md-4">
              <label for="harga_baru" class="form-label">Harga Satuan</label>
              <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="number" id="harga_baru" name="harga_satuan" class="form-control" min="1" max="9999999999" step="any" placeholder="0" required>
              </div>
            </div>
            <div class="col-md-3">
              <button type="submit" class="btn btn-simpan w-100"><i class="bi bi-plus-lg me-1"></i>Tambah</button>
            </div>
          </form>
        </div>

        <div class="panel">
          <div class="table-responsive">
            <table class="table mb-0">
              <thead>
                <tr>
                  <th style="width:70px">No</th>
                  <th>Nama Barang</th>
                  <th>Harga Satuan Saat Ini</th>
                  <th style="width:340px">Ubah Harga</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($daftar_barang && $daftar_barang->num_rows > 0): $no = 1; ?>
                  <?php while ($barang = $daftar_barang->fetch_assoc()): ?>
                    <tr>
                      <td><?= $no++ ?></td>
                      <td><?= e($barang['nama_barang']) ?></td>
                      <td><?= rupiah($barang['harga_satuan']) ?></td>
                      <td>
                        <form method="post" action="/supplier/function/harga.php" class="d-flex gap-2">
                          <input type="hidden" name="product_id" value="<?= (int) $barang['id'] ?>">
                          <div class="input-group input-group-sm">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="harga_satuan" class="form-control" min="1" max="9999999999" step="any" placeholder="Harga baru" required>
                          </div>
                          <button type="submit" class="btn btn-sm btn-simpan">Simpan</button>
                        </form>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="4" class="text-center text-secondary py-4">Belum ada bahan baku. Tambahkan bahan baku pertama Anda lewat formulir di atas.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>

    </section>
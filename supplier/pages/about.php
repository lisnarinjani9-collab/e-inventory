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
        <p>Perbarui harga satuan bahan baku yang Anda tawarkan kepada gudang</p>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <?php if ($pesan): ?>
          <div class="alert alert-<?= $pesan[0] ?> small"><?= $pesan[1] ?></div>
        <?php endif; ?>

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
                  <tr><td colspan="4" class="text-center text-secondary py-4">Belum ada data barang.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>

    </section>
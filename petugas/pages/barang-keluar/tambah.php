<?php
include_once __DIR__ . '/../../database/connection.php';
$koneksi = (new Database())->conn;

// Daftar barang beserta stok saat ini (stok = masuk - keluar + opname)
$daftar_barang = $koneksi->query(
    "SELECT products.id, products.nama_barang,
            COALESCE(SUM(CASE stock_transactions.jenis_transaksi
                WHEN 'masuk'  THEN stock_transactions.jumlah
                WHEN 'keluar' THEN -stock_transactions.jumlah
                WHEN 'opname' THEN stock_transactions.jumlah
                ELSE 0 END), 0) AS stok_saat_ini
     FROM products
     LEFT JOIN stock_transactions ON stock_transactions.product_id = products.id
     GROUP BY products.id, products.nama_barang
     ORDER BY products.nama_barang ASC"
);

$daftar_pesan = [
    'tidak_valid'      => 'Isian tidak valid, periksa kembali.',
    'barang_tidak_ada' => 'Barang yang dipilih tidak ditemukan.',
    'stok_kurang'      => 'Stok barang tidak cukup untuk jumlah yang diminta.',
];
$pesan = $daftar_pesan[$_GET['pesan'] ?? ''] ?? '';
?>
  <main id="content" class="content py-10">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div class="">
              <h1 class="fs-3 mb-1">Tambah Barang Keluar</h1>
              <p class="mb-0">Catat barang yang keluar dari gudang</p>
            </div>
            <div>
              <a href="index.php?page=barang-keluar" class="btn btn-primary">Kembali ke Daftar Barang Keluar</a>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-12">
          <?php if ($pesan): ?>
            <div class="alert alert-danger"><?= $pesan ?></div>
          <?php endif; ?>
          <div class="card">
            <div class="card-body p-4">
              <form id="addProductForm" method="post" action="function/barang-keluar.php?aksi=simpan">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="product_id" class="form-label">Nama Barang</label>
                    <select class="form-select" name="product_id" id="product_id" required>
                      <option value="">Pilih barang</option>
                      <?php while ($barang = $daftar_barang->fetch_assoc()): ?>
                        <option value="<?= $barang['id'] ?>">
                          <?= htmlspecialchars($barang['nama_barang']) ?> (stok: <?= (int) $barang['stok_saat_ini'] ?>)
                        </option>
                      <?php endwhile; ?>
                    </select>
                  </div>
                  <div class="col-md-3 mb-3">
                    <label for="jumlah" class="form-label">Jumlah</label>
                    <input type="number" class="form-control" name="jumlah" id="jumlah" placeholder="0" min="1" required>
                  </div>
                  <div class="col-md-3 mb-3">
                    <label for="tanggal" class="form-label">Tanggal Keluar</label>
                    <input type="date" class="form-control" name="tanggal" id="tanggal" value="<?= date('Y-m-d') ?>" required>
                  </div>
                </div>

                <div class="d-flex gap-2">
                  <button type="submit" class="btn btn-primary">Simpan</button>
                  <button type="reset" class="btn btn-secondary">Reset</button>
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
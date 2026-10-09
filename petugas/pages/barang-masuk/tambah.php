<?php
include_once __DIR__ . '/../../database/connection.php';
$koneksi = (new Database())->conn;

// Daftar barang untuk pilihan "Nama Barang"
$daftar_barang = $koneksi->query(
    "SELECT id, nama_barang FROM products ORDER BY nama_barang ASC"
);

// Daftar PO untuk pilihan "Referensi PO" (hanya PO yang sudah disetujui / selesai)
$daftar_purchase_order = $koneksi->query(
    "SELECT purchase_orders.id, suppliers.nama_perusahaan
     FROM purchase_orders
     INNER JOIN suppliers ON suppliers.id = purchase_orders.supplier_id
     WHERE purchase_orders.status_po IN ('disetujui', 'selesai')
     ORDER BY purchase_orders.id DESC"
);

$daftar_pesan = [
    'tidak_valid'      => 'Isian tidak valid, periksa kembali.',
    'barang_tidak_ada' => 'Barang yang dipilih tidak ditemukan.',
    'po_tidak_ada'     => 'PO yang dipilih tidak ditemukan.',
];
$pesan = $daftar_pesan[$_GET['pesan'] ?? ''] ?? '';
?>
  <main id="content" class="content py-10">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div class="">
              <h1 class="fs-3 mb-1">Tambah Barang Masuk</h1>
              <p class="mb-0">Catat barang yang masuk ke gudang</p>
            </div>
            <div>
              <a href="index.php?page=barang-masuk" class="btn btn-primary">Kembali ke Daftar Barang Masuk</a>
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
              <form id="addProductForm" method="post" action="function/barang-masuk.php?aksi=simpan">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="product_id" class="form-label">Nama Barang</label>
                    <select class="form-select" name="product_id" id="product_id" required>
                      <option value="">Pilih barang</option>
                      <?php while ($barang = $daftar_barang->fetch_assoc()): ?>
                        <option value="<?= $barang['id'] ?>"><?= htmlspecialchars($barang['nama_barang']) ?></option>
                      <?php endwhile; ?>
                    </select>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="po_id" class="form-label">Referensi PO (opsional)</label>
                    <select class="form-select" name="po_id" id="po_id">
                      <option value="">Tanpa PO</option>
                      <?php while ($purchase_order = $daftar_purchase_order->fetch_assoc()): ?>
                        <option value="<?= $purchase_order['id'] ?>">
                          PO-<?= str_pad($purchase_order['id'], 4, '0', STR_PAD_LEFT) ?> - <?= htmlspecialchars($purchase_order['nama_perusahaan']) ?>
                        </option>
                      <?php endwhile; ?>
                    </select>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="jumlah" class="form-label">Jumlah</label>
                    <input type="number" class="form-control" name="jumlah" id="jumlah" placeholder="0" min="1" required>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="tanggal" class="form-label">Tanggal Masuk</label>
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
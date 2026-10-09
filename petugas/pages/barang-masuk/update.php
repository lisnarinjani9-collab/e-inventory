<?php
include_once __DIR__ . '/../../database/connection.php';
$koneksi = (new Database())->conn;

$id = (int) ($_GET['id'] ?? 0);

// Ambil data barang masuk yang mau diubah
$ambil_data = $koneksi->prepare(
    "SELECT id, product_id, po_id, jumlah, tanggal
     FROM stock_transactions
     WHERE id = ? AND jenis_transaksi = 'masuk'"
);
$ambil_data->bind_param('i', $id);
$ambil_data->execute();
$data = $ambil_data->get_result()->fetch_assoc();

// Kalau datanya tidak ada, kembali ke daftar
if (!$data) {
    echo "<script>window.location.href='index.php?page=barang-masuk'</script>";
    return;
}

// Daftar barang untuk pilihan "Nama Barang"
$daftar_barang = $koneksi->query(
    "SELECT id, nama_barang FROM products ORDER BY nama_barang ASC"
);

// Daftar PO untuk pilihan "Referensi PO" (PO yang sedang dipakai data ini tetap ditampilkan)
$po_saat_ini = (int) ($data['po_id'] ?? 0);
$ambil_purchase_order = $koneksi->prepare(
    "SELECT purchase_orders.id, suppliers.nama_perusahaan
     FROM purchase_orders
     INNER JOIN suppliers ON suppliers.id = purchase_orders.supplier_id
     WHERE purchase_orders.status_po IN ('disetujui', 'selesai')
        OR purchase_orders.id = ?
     ORDER BY purchase_orders.id DESC"
);
$ambil_purchase_order->bind_param('i', $po_saat_ini);
$ambil_purchase_order->execute();
$daftar_purchase_order = $ambil_purchase_order->get_result();

$daftar_pesan = [
    'tidak_valid'  => 'Isian tidak valid, periksa kembali.',
    'po_tidak_ada' => 'PO yang dipilih tidak ditemukan.',
];
$pesan = $daftar_pesan[$_GET['pesan'] ?? ''] ?? '';
?>
  <main id="content" class="content py-10">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div class="">
              <h1 class="fs-3 mb-1">Edit Barang Masuk</h1>
              <p class="mb-0">Perbarui data barang yang masuk ke gudang</p>
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
              <form method="post" action="function/barang-masuk.php?aksi=ubah">
                <input type="hidden" name="id" value="<?= $data['id'] ?>">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="product_id" class="form-label">Nama Barang</label>
                    <select class="form-select" name="product_id" id="product_id" required>
                      <?php while ($barang = $daftar_barang->fetch_assoc()): ?>
                        <option value="<?= $barang['id'] ?>" <?= $barang['id'] == $data['product_id'] ? 'selected' : '' ?>>
                          <?= htmlspecialchars($barang['nama_barang']) ?>
                        </option>
                      <?php endwhile; ?>
                    </select>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="po_id" class="form-label">Referensi PO (opsional)</label>
                    <select class="form-select" name="po_id" id="po_id">
                      <option value="">Tanpa PO</option>
                      <?php while ($purchase_order = $daftar_purchase_order->fetch_assoc()): ?>
                        <option value="<?= $purchase_order['id'] ?>" <?= $purchase_order['id'] == $data['po_id'] ? 'selected' : '' ?>>
                          PO-<?= str_pad($purchase_order['id'], 4, '0', STR_PAD_LEFT) ?> - <?= htmlspecialchars($purchase_order['nama_perusahaan']) ?>
                        </option>
                      <?php endwhile; ?>
                    </select>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="jumlah" class="form-label">Jumlah</label>
                    <input type="number" class="form-control" name="jumlah" id="jumlah" min="1" value="<?= (int) $data['jumlah'] ?>" required>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="tanggal" class="form-label">Tanggal Masuk</label>
                    <input type="date" class="form-control" name="tanggal" id="tanggal" value="<?= date('Y-m-d', strtotime($data['tanggal'])) ?>" required>
                  </div>
                </div>

                <div class="d-flex gap-2">
                  <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                  <a href="index.php?page=barang-masuk" class="btn btn-secondary">Batal</a>
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
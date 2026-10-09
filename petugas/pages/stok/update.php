<?php
include_once __DIR__ . '/../../database/connection.php';
$koneksi = (new Database())->conn;

$id = (int) ($_GET['id'] ?? 0);

// Ambil data opname yang mau diubah
$ambil_data = $koneksi->prepare(
    "SELECT stock_transactions.id, stock_transactions.product_id, stock_transactions.jumlah,
            stock_transactions.tanggal, products.nama_barang
     FROM stock_transactions
     INNER JOIN products ON products.id = stock_transactions.product_id
     WHERE stock_transactions.id = ? AND stock_transactions.jenis_transaksi = 'opname'"
);
$ambil_data->bind_param('i', $id);
$ambil_data->execute();
$data = $ambil_data->get_result()->fetch_assoc();

// Kalau datanya tidak ada, kembali ke daftar
if (!$data) {
    echo "<script>window.location.href='index.php?page=stok'</script>";
    return;
}

// Stok sistem tanpa opname ini, lalu stok fisik saat opname dicatat = stok sistem + selisih
$ambil_stok = $koneksi->prepare(
    "SELECT COALESCE(SUM(CASE stock_transactions.jenis_transaksi
                WHEN 'masuk'  THEN stock_transactions.jumlah
                WHEN 'keluar' THEN -stock_transactions.jumlah
                WHEN 'opname' THEN stock_transactions.jumlah
                ELSE 0 END), 0) AS stok_saat_ini
     FROM stock_transactions
     WHERE stock_transactions.product_id = ? AND stock_transactions.id <> ?"
);
$ambil_stok->bind_param('ii', $data['product_id'], $id);
$ambil_stok->execute();
$stok_sistem = (int) $ambil_stok->get_result()->fetch_assoc()['stok_saat_ini'];
$stok_fisik  = $stok_sistem + (int) $data['jumlah'];

$daftar_pesan = [
    'tidak_valid' => 'Isian tidak valid, periksa kembali.',
];
$pesan = $daftar_pesan[$_GET['pesan'] ?? ''] ?? '';
?>
  <main id="content" class="content py-10">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div class="">
              <h1 class="fs-3 mb-1">Edit Opname Stok</h1>
              <p class="mb-0">Perbarui hasil hitung fisik barang</p>
            </div>
            <div>
              <a href="index.php?page=stok" class="btn btn-primary">Kembali ke Stok &amp; Opname</a>
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
              <form method="post" action="function/stok.php?aksi=ubah">
                <input type="hidden" name="id" value="<?= $data['id'] ?>">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="nama_barang" class="form-label">Nama Barang</label>
                    <input type="text" class="form-control" id="nama_barang" value="<?= htmlspecialchars($data['nama_barang']) ?>" disabled>
                  </div>
                  <div class="col-md-3 mb-3">
                    <label for="stok_fisik" class="form-label">Stok Fisik</label>
                    <input type="number" class="form-control" name="stok_fisik" id="stok_fisik" min="0" value="<?= $stok_fisik ?>" required>
                    <div class="form-text">Stok sistem (tanpa opname ini): <?= $stok_sistem ?></div>
                  </div>
                  <div class="col-md-3 mb-3">
                    <label for="tanggal" class="form-label">Tanggal Opname</label>
                    <input type="date" class="form-control" name="tanggal" id="tanggal" value="<?= date('Y-m-d', strtotime($data['tanggal'])) ?>" required>
                  </div>
                </div>

                <div class="d-flex gap-2">
                  <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                  <a href="index.php?page=stok" class="btn btn-secondary">Batal</a>
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
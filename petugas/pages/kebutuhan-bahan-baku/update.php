<?php
include_once __DIR__ . '/../../database/connection.php';
$koneksi = (new Database())->conn;

$id = (int) ($_GET['id'] ?? 0);

// Ambil data pengajuan yang mau diubah (hanya yang masih 'diajukan')
$ambil_data = $koneksi->prepare(
    "SELECT id, supplier_id, tanggal_po
     FROM purchase_orders
     WHERE id = ? AND status_po = 'diajukan'"
);
$ambil_data->bind_param('i', $id);
$ambil_data->execute();
$data = $ambil_data->get_result()->fetch_assoc();

// Kalau datanya tidak ada atau sudah diproses, kembali ke daftar
if (!$data) {
    echo "<script>window.location.href='index.php?page=kebutuhan-bahan-baku&pesan=tidak_bisa_diubah'</script>";
    return;
}

// Daftar supplier untuk pilihan "Supplier"
$daftar_supplier = $koneksi->query(
    "SELECT id, nama_perusahaan FROM suppliers ORDER BY nama_perusahaan ASC"
);

$daftar_pesan = [
    'tidak_valid'        => 'Isian tidak valid, periksa kembali.',
    'supplier_tidak_ada' => 'Supplier yang dipilih tidak ditemukan.',
];
$pesan = $daftar_pesan[$_GET['pesan'] ?? ''] ?? '';
?>
  <main id="content" class="content py-10">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div class="">
              <h1 class="fs-3 mb-1">Edit Kebutuhan Bahan Baku</h1>
              <p class="mb-0">Perbarui pengajuan PO-<?= str_pad($data['id'], 4, '0', STR_PAD_LEFT) ?></p>
            </div>
            <div>
              <a href="index.php?page=kebutuhan-bahan-baku" class="btn btn-primary">Kembali ke Daftar Pengajuan</a>
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
              <form method="post" action="function/kebutuhan-bahan-baku.php?aksi=ubah">
                <input type="hidden" name="id" value="<?= $data['id'] ?>">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="supplier_id" class="form-label">Supplier</label>
                    <select class="form-select" name="supplier_id" id="supplier_id" required>
                      <?php while ($supplier = $daftar_supplier->fetch_assoc()): ?>
                        <option value="<?= $supplier['id'] ?>" <?= $supplier['id'] == $data['supplier_id'] ? 'selected' : '' ?>>
                          <?= htmlspecialchars($supplier['nama_perusahaan']) ?>
                        </option>
                      <?php endwhile; ?>
                    </select>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="tanggal_po" class="form-label">Tanggal Pengajuan</label>
                    <input type="date" class="form-control" name="tanggal_po" id="tanggal_po" value="<?= $data['tanggal_po'] ?>" required>
                  </div>
                </div>

                <div class="d-flex gap-2">
                  <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                  <a href="index.php?page=kebutuhan-bahan-baku" class="btn btn-secondary">Batal</a>
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
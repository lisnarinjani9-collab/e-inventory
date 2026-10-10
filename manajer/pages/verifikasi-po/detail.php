<?php
include_once __DIR__ . '/../../database/connection.php';
$koneksi = (new Database())->conn;

$id = (int) ($_GET['id'] ?? 0);

// ---------- DATA PO + SUPPLIER ----------
$ambil_po = $koneksi->prepare(
    "SELECT purchase_orders.id, purchase_orders.tanggal_po, purchase_orders.status_po,
            suppliers.nama_perusahaan, suppliers.telepon, users.nama AS nama_penanggung_jawab, users.email
     FROM purchase_orders
     INNER JOIN suppliers ON suppliers.id = purchase_orders.supplier_id
     INNER JOIN users ON users.id = suppliers.user_id
     WHERE purchase_orders.id = ?"
);
$ambil_po->bind_param('i', $id);
$ambil_po->execute();
$purchase_order = $ambil_po->get_result()->fetch_assoc();

// Kalau PO tidak ada, kembali ke daftar
if (!$purchase_order) {
    echo "<script>window.location.href='index.php?page=verifikasi-po'</script>";
    return;
}

$kode_po = 'PO-' . str_pad($purchase_order['id'], 4, '0', STR_PAD_LEFT);

// ---------- BARANG YANG SUDAH DITERIMA GUDANG DARI PO INI ----------
$ambil_barang = $koneksi->prepare(
    "SELECT stock_transactions.tanggal, stock_transactions.jumlah,
            products.nama_barang, products.harga_satuan
     FROM stock_transactions
     INNER JOIN products ON products.id = stock_transactions.product_id
     WHERE stock_transactions.po_id = ? AND stock_transactions.jenis_transaksi = 'masuk'
     ORDER BY stock_transactions.tanggal ASC, stock_transactions.id ASC"
);
$ambil_barang->bind_param('i', $id);
$ambil_barang->execute();
$hasil_barang = $ambil_barang->get_result();
$daftar_barang_diterima = [];
$total_nilai_diterima   = 0;
while ($barang = $hasil_barang->fetch_assoc()) {
    $daftar_barang_diterima[] = $barang;
    $total_nilai_diterima    += (int) $barang['jumlah'] * (float) $barang['harga_satuan'];
}

// ---------- STOK MENIPIS (bahan pertimbangan saat memverifikasi) ----------
$hasil_stok = $koneksi->query(
    "SELECT products.nama_barang, products.stok_minimal,
            COALESCE(SUM(CASE stock_transactions.jenis_transaksi
                WHEN 'masuk'  THEN stock_transactions.jumlah
                WHEN 'keluar' THEN -stock_transactions.jumlah
                WHEN 'opname' THEN stock_transactions.jumlah
                ELSE 0 END), 0) AS stok_saat_ini
     FROM products
     LEFT JOIN stock_transactions ON stock_transactions.product_id = products.id
     GROUP BY products.id, products.nama_barang, products.stok_minimal
     ORDER BY products.nama_barang ASC"
);
$stok_menipis = [];
while ($barang = $hasil_stok->fetch_assoc()) {
    if ((int) $barang['stok_saat_ini'] <= (int) $barang['stok_minimal']) {
        $stok_menipis[] = $barang;
    }
}

// ---------- PESAN ----------
$daftar_pesan = [
    'setuju_ok'              => ['success', 'PO berhasil disetujui. Petugas gudang dan supplier bisa melihat statusnya.'],
    'tolak_ok'               => ['success', 'PO berhasil ditolak.'],
    'selesai_ok'             => ['success', 'PO berhasil ditandai selesai.'],
    'tidak_bisa_diproses'    => ['warning', 'PO ini sudah diproses sebelumnya.'],
    'belum_ada_barang_masuk' => ['warning', 'PO belum bisa diselesaikan karena gudang belum mencatat barang masuk untuk PO ini.'],
    'tidak_valid'            => ['danger',  'Data tidak valid.'],
];
$pesan = $daftar_pesan[$_GET['pesan'] ?? ''] ?? null;

$warna_status_po = [
    'diajukan'  => 'warning',
    'disetujui' => 'primary',
    'selesai'   => 'success',
    'ditolak'   => 'danger',
];
$warna = $warna_status_po[$purchase_order['status_po']] ?? 'secondary';
?>
  <main id="content" class="content py-10">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div class="">
              <h1 class="fs-3 mb-1">Detail <?= $kode_po ?></h1>
              <p class="mb-0">Periksa pengajuan ini sebelum diputuskan</p>
            </div>
            <div>
              <a href="index.php?page=verifikasi-po" class="btn btn-primary">Kembali ke Verifikasi PO</a>
            </div>
          </div>
        </div>
      </div>

      <?php if ($pesan): ?>
        <div class="alert alert-<?= $pesan[0] ?>"><?= $pesan[1] ?></div>
      <?php endif; ?>

      <div class="row g-3 mb-3">
        <!-- Informasi PO -->
        <div class="col-lg-6 col-12">
          <div class="card h-100">
            <div class="card-header bg-white px-4 py-3 d-flex justify-content-between align-items-center">
              <h3 class="h5 mb-0">Informasi PO</h3>
              <span class="badge bg-<?= $warna ?>-subtle text-<?= $warna ?>"><?= ucfirst($purchase_order['status_po']) ?></span>
            </div>
            <div class="card-body p-4">
              <table class="table table-borderless mb-0">
                <tr><td class="text-secondary" style="width: 40%;">Kode PO</td><td class="fw-semibold"><?= $kode_po ?></td></tr>
                <tr><td class="text-secondary">Tanggal Pengajuan</td><td><?= date('d M Y', strtotime($purchase_order['tanggal_po'])) ?></td></tr>
                <tr><td class="text-secondary">Supplier</td><td class="fw-semibold"><?= htmlspecialchars($purchase_order['nama_perusahaan']) ?></td></tr>
                <tr><td class="text-secondary">Penanggung Jawab</td><td><?= htmlspecialchars($purchase_order['nama_penanggung_jawab']) ?></td></tr>
                <tr><td class="text-secondary">Telepon</td><td><?= htmlspecialchars($purchase_order['telepon']) ?></td></tr>
                <tr><td class="text-secondary">Email</td><td><?= htmlspecialchars($purchase_order['email']) ?></td></tr>
              </table>
            </div>
          </div>
        </div>

        <!-- Keputusan -->
        <div class="col-lg-6 col-12">
          <div class="card h-100">
            <div class="card-header bg-white px-4 py-3">
              <h3 class="h5 mb-0">Keputusan Manajer</h3>
            </div>
            <div class="card-body p-4">
              <?php if ($purchase_order['status_po'] === 'diajukan'): ?>
                <p class="text-secondary">Pengajuan ini menunggu verifikasi Anda. Setelah disetujui, supplier dapat mengirim barang ke gudang.</p>
                <div class="d-flex gap-2">
                  <form method="post" action="function/po.php?aksi=setujui" onsubmit="return confirm('Setujui <?= $kode_po ?>?')">
                    <input type="hidden" name="id" value="<?= $purchase_order['id'] ?>">
                    <input type="hidden" name="dari" value="detail">
                    <button type="submit" class="btn btn-success"><i class="ti ti-check me-1"></i>Setujui PO</button>
                  </form>
                  <form method="post" action="function/po.php?aksi=tolak" onsubmit="return confirm('Tolak <?= $kode_po ?>?')">
                    <input type="hidden" name="id" value="<?= $purchase_order['id'] ?>">
                    <input type="hidden" name="dari" value="detail">
                    <button type="submit" class="btn btn-outline-danger"><i class="ti ti-x me-1"></i>Tolak PO</button>
                  </form>
                </div>

              <?php elseif ($purchase_order['status_po'] === 'disetujui'): ?>
                <p class="text-secondary">PO sudah disetujui dan menunggu barang dari supplier. Setelah gudang mencatat barang masuk, PO ini bisa ditandai selesai.</p>
                <form method="post" action="function/po.php?aksi=selesai" onsubmit="return confirm('Tandai <?= $kode_po ?> selesai?')">
                  <input type="hidden" name="id" value="<?= $purchase_order['id'] ?>">
                  <input type="hidden" name="dari" value="detail">
                  <button type="submit" class="btn btn-primary" <?= count($daftar_barang_diterima) === 0 ? 'disabled' : '' ?>>
                    <i class="ti ti-package me-1"></i>Tandai Selesai
                  </button>
                  <?php if (count($daftar_barang_diterima) === 0): ?>
                    <div class="form-text">Belum bisa: gudang belum mencatat barang masuk untuk PO ini.</div>
                  <?php endif; ?>
                </form>

              <?php elseif ($purchase_order['status_po'] === 'selesai'): ?>
                <p class="mb-0 text-success"><i class="ti ti-circle-check me-1"></i>PO ini sudah selesai. Barang sudah diterima gudang.</p>

              <?php else: ?>
                <p class="mb-0 text-danger"><i class="ti ti-circle-x me-1"></i>PO ini sudah ditolak.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <!-- Barang diterima -->
        <div class="col-lg-7 col-12">
          <div class="card h-100">
            <div class="card-header bg-white px-4 py-3">
              <h3 class="h5 mb-0">Barang Diterima dari PO Ini</h3>
            </div>
            <div class="table-responsive">
              <table class="table mb-0 text-nowrap align-middle">
                <thead class="table-light border-light">
                  <tr>
                    <th class="ps-4">Tanggal</th>
                    <th>Nama Barang</th>
                    <th class="text-end">Jumlah</th>
                    <th class="text-end pe-4">Nilai</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (count($daftar_barang_diterima) === 0): ?>
                    <tr>
                      <td colspan="4" class="text-center text-secondary py-4">Belum ada barang masuk untuk PO ini.</td>
                    </tr>
                  <?php endif; ?>
                  <?php foreach ($daftar_barang_diterima as $barang): ?>
                    <tr>
                      <td class="ps-4 text-secondary"><?= date('d M Y', strtotime($barang['tanggal'])) ?></td>
                      <td><?= htmlspecialchars($barang['nama_barang']) ?></td>
                      <td class="text-end"><?= number_format($barang['jumlah'], 0, ',', '.') ?></td>
                      <td class="text-end pe-4">Rp <?= number_format($barang['jumlah'] * $barang['harga_satuan'], 0, ',', '.') ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
                <?php if (count($daftar_barang_diterima) > 0): ?>
                  <tfoot>
                    <tr>
                      <td colspan="3" class="ps-4 fw-semibold border-bottom-0">Total nilai</td>
                      <td class="text-end pe-4 fw-bold border-bottom-0">Rp <?= number_format($total_nilai_diterima, 0, ',', '.') ?></td>
                    </tr>
                  </tfoot>
                <?php endif; ?>
              </table>
            </div>
          </div>
        </div>

        <!-- Stok menipis -->
        <div class="col-lg-5 col-12">
          <div class="card h-100">
            <div class="card-header bg-white px-4 py-3">
              <h3 class="h5 mb-0">Barang Stok Menipis</h3>
            </div>
            <ul class="list-group list-group-flush">
              <?php if (count($stok_menipis) === 0): ?>
                <li class="list-group-item text-secondary py-4 text-center">Semua stok barang masih aman.</li>
              <?php endif; ?>
              <?php foreach ($stok_menipis as $barang): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center px-4">
                  <span><?= htmlspecialchars($barang['nama_barang']) ?></span>
                  <span class="badge bg-danger-subtle text-danger"><?= (int) $barang['stok_saat_ini'] ?> / <?= (int) $barang['stok_minimal'] ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
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
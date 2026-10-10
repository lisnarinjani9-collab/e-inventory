<?php
include_once __DIR__ . '/../../database/connection.php';
$koneksi = (new Database())->conn;

// ---------- STOK SETIAP BARANG (stok = masuk - keluar + opname) ----------
$hasil_stok = $koneksi->query(
    "SELECT products.id, products.nama_barang, products.stok_minimal, products.harga_satuan,
            COALESCE(SUM(CASE stock_transactions.jenis_transaksi
                WHEN 'masuk'  THEN stock_transactions.jumlah
                WHEN 'keluar' THEN -stock_transactions.jumlah
                WHEN 'opname' THEN stock_transactions.jumlah
                ELSE 0 END), 0) AS stok_saat_ini
     FROM products
     LEFT JOIN stock_transactions ON stock_transactions.product_id = products.id
     GROUP BY products.id, products.nama_barang, products.stok_minimal, products.harga_satuan
     ORDER BY products.nama_barang ASC"
);

// Hitung status dan nilai tiap barang, sekaligus total ringkasan
$semua_barang     = [];
$total_nilai      = 0;
$total_menipis    = 0;
$total_habis      = 0;
while ($barang = $hasil_stok->fetch_assoc()) {
    $stok = (int) $barang['stok_saat_ini'];
    $barang['nilai'] = max(0, $stok) * (float) $barang['harga_satuan'];
    if ($stok <= 0) {
        $barang['label_status'] = 'Habis';
        $barang['warna_status'] = 'danger';
        $total_habis++;
    } elseif ($stok <= (int) $barang['stok_minimal']) {
        $barang['label_status'] = 'Menipis';
        $barang['warna_status'] = 'warning';
        $total_menipis++;
    } else {
        $barang['label_status'] = 'Aman';
        $barang['warna_status'] = 'success';
    }
    $total_nilai += $barang['nilai'];
    $semua_barang[] = $barang;
}
$total_barang = count($semua_barang);

// ---------- PENCARIAN & HALAMAN (dilakukan di PHP karena datanya sudah terkumpul) ----------
$kata_cari = trim($_GET['cari'] ?? '');
if ($kata_cari !== '') {
    $semua_barang = array_values(array_filter($semua_barang, function ($barang) use ($kata_cari) {
        return stripos($barang['nama_barang'], $kata_cari) !== false;
    }));
}
$data_per_halaman = 10;
$total_data       = count($semua_barang);
$total_halaman    = max(1, (int) ceil($total_data / $data_per_halaman));
$halaman_aktif    = min(max(1, (int) ($_GET['halaman'] ?? 1)), $total_halaman);
$mulai_dari       = ($halaman_aktif - 1) * $data_per_halaman;
$barang_tampil    = array_slice($semua_barang, $mulai_dari, $data_per_halaman);
$nomor_urut       = $mulai_dari + 1;

// Fungsi kecil untuk membuat link halaman (tetap membawa kata pencarian)
function linkHalaman($nomor_halaman, $kata_cari)
{
    return 'index.php?page=laporan-stok&cari=' . urlencode($kata_cari) . '&halaman=' . $nomor_halaman;
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
          <div class="mb-4">
            <h1 class="fs-3 mb-1">Laporan Stok</h1>
            <p class="mb-0">Kondisi stok dan nilai persediaan seluruh barang di gudang</p>
          </div>
        </div>
      </div>

      <!-- Ringkasan -->
      <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6 col-12">
          <div class="card p-4 bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded-2">
            <h2 class="mb-2 fs-6">Jenis Barang</h2>
            <h3 class="fw-bold mb-0"><?= $total_barang ?></h3>
          </div>
        </div>
        <div class="col-xl-3 col-md-6 col-12">
          <div class="card p-4 bg-success bg-opacity-10 border border-success border-opacity-25 rounded-2">
            <h2 class="mb-2 fs-6">Nilai Persediaan</h2>
            <h3 class="fw-bold mb-0">Rp <?= number_format($total_nilai, 0, ',', '.') ?></h3>
          </div>
        </div>
        <div class="col-xl-3 col-md-6 col-12">
          <div class="card p-4 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-2">
            <h2 class="mb-2 fs-6">Stok Menipis</h2>
            <h3 class="fw-bold mb-0"><?= $total_menipis ?></h3>
          </div>
        </div>
        <div class="col-xl-3 col-md-6 col-12">
          <div class="card p-4 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded-2">
            <h2 class="mb-2 fs-6">Stok Habis</h2>
            <h3 class="fw-bold mb-0"><?= $total_habis ?></h3>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-12">
          <form method="get" action="index.php" class="d-flex gap-2 mb-3 flex-wrap">
            <input type="hidden" name="page" value="laporan-stok">
            <input type="text" name="cari" class="form-control" placeholder="Cari nama barang..." value="<?= htmlspecialchars($kata_cari) ?>" style="max-width: 250px;">
            <button type="submit" class="btn btn-outline-secondary"><i class="ti ti-search"></i> Cari</button>
          </form>

          <div class="card table-responsive ">
            <table class="table mb-0 text-nowrap table-hover">
              <thead class="table-light border-light">
                <tr>
                  <th>No</th>
                  <th>Nama Barang</th>
                  <th class="text-end">Stok Saat Ini</th>
                  <th class="text-end">Stok Minimal</th>
                  <th class="text-end">Harga Satuan</th>
                  <th class="text-end">Nilai Persediaan</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($barang_tampil) === 0): ?>
                  <tr>
                    <td colspan="7" class="text-center text-secondary py-4">Tidak ada data barang.</td>
                  </tr>
                <?php endif; ?>

                <?php foreach ($barang_tampil as $barang): ?>
                  <tr class="align-middle">
                    <td><?= $nomor_urut++ ?></td>
                    <td><?= htmlspecialchars($barang['nama_barang']) ?></td>
                    <td class="text-end fw-semibold"><?= number_format((int) $barang['stok_saat_ini'], 0, ',', '.') ?></td>
                    <td class="text-end"><?= number_format($barang['stok_minimal'], 0, ',', '.') ?></td>
                    <td class="text-end">Rp <?= number_format($barang['harga_satuan'], 0, ',', '.') ?></td>
                    <td class="text-end">Rp <?= number_format($barang['nilai'], 0, ',', '.') ?></td>
                    <td><span class="badge bg-<?= $barang['warna_status'] ?>-subtle text-<?= $barang['warna_status'] ?>"><?= $barang['label_status'] ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot class="">
                <tr>
                  <td colspan="3" class="border-bottom-0">Menampilkan <?= count($barang_tampil) ?> dari <?= $total_data ?> barang</td>
                  <td colspan="4" class="border-bottom-0">
                    <nav aria-label="Page navigation" class="d-flex justify-content-end">
                      <ul class="pagination mb-0">
                        <li class="page-item <?= $halaman_aktif <= 1 ? 'disabled' : '' ?>">
                          <a class="page-link" href="<?= linkHalaman($halaman_aktif - 1, $kata_cari) ?>">Sebelumnya</a>
                        </li>
                        <?php for ($nomor_halaman = 1; $nomor_halaman <= $total_halaman; $nomor_halaman++): ?>
                          <li class="page-item <?= $nomor_halaman === $halaman_aktif ? 'active' : '' ?>">
                            <a class="page-link" href="<?= linkHalaman($nomor_halaman, $kata_cari) ?>"><?= $nomor_halaman ?></a>
                          </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $halaman_aktif >= $total_halaman ? 'disabled' : '' ?>">
                          <a class="page-link" href="<?= linkHalaman($halaman_aktif + 1, $kata_cari) ?>">Berikutnya</a>
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
<?php
include_once __DIR__ . '/../../database/connection.php';
$koneksi = (new Database())->conn;

// ---------- FILTER JENIS, PENCARIAN, HALAMAN ----------
$daftar_jenis  = ['semua', 'masuk', 'keluar', 'opname'];
$jenis_dipilih = $_GET['jenis'] ?? 'semua';
if (!in_array($jenis_dipilih, $daftar_jenis, true)) {
    $jenis_dipilih = 'semua';
}

$kata_cari        = trim($_GET['cari'] ?? '');
$pola_cari        = '%' . $kata_cari . '%';
$data_per_halaman = 15;
$halaman_aktif    = max(1, (int) ($_GET['halaman'] ?? 1));

// Susun syarat pencarian secara bertahap
$daftar_syarat = [];
$tipe_isian    = '';
$isian         = [];
if ($jenis_dipilih !== 'semua') {
    $daftar_syarat[] = "stock_transactions.jenis_transaksi = ?";
    $tipe_isian     .= 's';
    $isian[]         = $jenis_dipilih;
}
if ($kata_cari !== '') {
    $daftar_syarat[] = "products.nama_barang LIKE ?";
    $tipe_isian     .= 's';
    $isian[]         = $pola_cari;
}
$syarat = count($daftar_syarat) > 0 ? ' WHERE ' . implode(' AND ', $daftar_syarat) : '';

// ---------- HITUNG TOTAL DATA ----------
$statement_total = $koneksi->prepare(
    "SELECT COUNT(*) AS total_data
     FROM stock_transactions
     INNER JOIN products ON products.id = stock_transactions.product_id" . $syarat
);
if (count($isian) > 0) {
    $statement_total->bind_param($tipe_isian, ...$isian);
}
$statement_total->execute();
$total_data    = (int) $statement_total->get_result()->fetch_assoc()['total_data'];
$total_halaman = max(1, (int) ceil($total_data / $data_per_halaman));
$halaman_aktif = min($halaman_aktif, $total_halaman);
$mulai_dari    = ($halaman_aktif - 1) * $data_per_halaman;

// ---------- AMBIL DATA TRANSAKSI ----------
$statement_data = $koneksi->prepare(
    "SELECT stock_transactions.tanggal, stock_transactions.jenis_transaksi, stock_transactions.jumlah,
            stock_transactions.po_id, products.nama_barang
     FROM stock_transactions
     INNER JOIN products ON products.id = stock_transactions.product_id" . $syarat . "
     ORDER BY stock_transactions.tanggal DESC, stock_transactions.id DESC
     LIMIT ? OFFSET ?"
);
$isian_data = array_merge($isian, [$data_per_halaman, $mulai_dari]);
$statement_data->bind_param($tipe_isian . 'ii', ...$isian_data);
$statement_data->execute();
$hasil_data = $statement_data->get_result();

$nomor_urut = $mulai_dari + 1;

// Fungsi kecil untuk membuat link (tetap membawa jenis dan kata pencarian)
function linkHalaman($nomor_halaman, $jenis_dipilih, $kata_cari)
{
    return 'index.php?page=riwayat-transaksi&jenis=' . $jenis_dipilih
        . '&cari=' . urlencode($kata_cari) . '&halaman=' . $nomor_halaman;
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
            <h1 class="fs-3 mb-1">Riwayat Transaksi</h1>
            <p class="mb-0">Seluruh pergerakan barang di gudang (barang masuk, keluar, dan opname)</p>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-12">
          <!-- Tombol filter jenis -->
          <div class="d-flex flex-wrap gap-2 mb-3">
            <?php foreach ($daftar_jenis as $jenis): ?>
              <a href="index.php?page=riwayat-transaksi&jenis=<?= $jenis ?>&cari=<?= urlencode($kata_cari) ?>"
                class="btn btn-sm <?= $jenis === $jenis_dipilih ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <?= ucfirst($jenis) ?>
              </a>
            <?php endforeach; ?>
          </div>

          <form method="get" action="index.php" class="d-flex gap-2 mb-3 flex-wrap">
            <input type="hidden" name="page" value="riwayat-transaksi">
            <input type="hidden" name="jenis" value="<?= $jenis_dipilih ?>">
            <input type="text" name="cari" class="form-control" placeholder="Cari nama barang..." value="<?= htmlspecialchars($kata_cari) ?>" style="max-width: 250px;">
            <button type="submit" class="btn btn-outline-secondary"><i class="ti ti-search"></i> Cari</button>
          </form>

          <div class="card table-responsive ">
            <table class="table mb-0 text-nowrap table-hover">
              <thead class="table-light border-light">
                <tr>
                  <th>No</th>
                  <th>Tanggal</th>
                  <th>Nama Barang</th>
                  <th>Jenis</th>
                  <th class="text-end">Jumlah</th>
                  <th>Ref. PO</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($hasil_data->num_rows === 0): ?>
                  <tr>
                    <td colspan="6" class="text-center text-secondary py-4">Tidak ada transaksi pada filter ini.</td>
                  </tr>
                <?php endif; ?>

                <?php while ($transaksi = $hasil_data->fetch_assoc()):
                    $selisih = (int) $transaksi['jumlah'];
                ?>
                  <tr class="align-middle">
                    <td><?= $nomor_urut++ ?></td>
                    <td><?= date('d M Y', strtotime($transaksi['tanggal'])) ?></td>
                    <td><?= htmlspecialchars($transaksi['nama_barang']) ?></td>
                    <td>
                      <?php if ($transaksi['jenis_transaksi'] === 'masuk'): ?>
                        <span class="badge bg-success-subtle text-success"><i class="ti ti-arrow-down-left me-1"></i>Masuk</span>
                      <?php elseif ($transaksi['jenis_transaksi'] === 'keluar'): ?>
                        <span class="badge bg-danger-subtle text-danger"><i class="ti ti-arrow-up-right me-1"></i>Keluar</span>
                      <?php else: ?>
                        <span class="badge bg-info-subtle text-info"><i class="ti ti-clipboard-check me-1"></i>Opname</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end fw-semibold">
                      <?php if ($transaksi['jenis_transaksi'] === 'opname' && $selisih > 0): ?>+<?php endif; ?><?= number_format($selisih, 0, ',', '.') ?>
                    </td>
                    <td><?= $transaksi['po_id'] ? 'PO-' . str_pad($transaksi['po_id'], 4, '0', STR_PAD_LEFT) : '-' ?></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
              <tfoot class="">
                <tr>
                  <td colspan="3" class="border-bottom-0">Menampilkan <?= $hasil_data->num_rows ?> dari <?= $total_data ?> transaksi</td>
                  <td colspan="3" class="border-bottom-0">
                    <nav aria-label="Page navigation" class="d-flex justify-content-end">
                      <ul class="pagination mb-0">
                        <li class="page-item <?= $halaman_aktif <= 1 ? 'disabled' : '' ?>">
                          <a class="page-link" href="<?= linkHalaman($halaman_aktif - 1, $jenis_dipilih, $kata_cari) ?>">Sebelumnya</a>
                        </li>
                        <?php for ($nomor_halaman = 1; $nomor_halaman <= $total_halaman; $nomor_halaman++): ?>
                          <li class="page-item <?= $nomor_halaman === $halaman_aktif ? 'active' : '' ?>">
                            <a class="page-link" href="<?= linkHalaman($nomor_halaman, $jenis_dipilih, $kata_cari) ?>"><?= $nomor_halaman ?></a>
                          </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $halaman_aktif >= $total_halaman ? 'disabled' : '' ?>">
                          <a class="page-link" href="<?= linkHalaman($halaman_aktif + 1, $jenis_dipilih, $kata_cari) ?>">Berikutnya</a>
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
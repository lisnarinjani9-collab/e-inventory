<?php
include_once __DIR__ . '/../../database/connection.php';
$koneksi = (new Database())->conn;

// ---------- PESAN (setelah catat / ubah / hapus opname) ----------
$daftar_pesan = [
    'tambah_ok'   => ['success', 'Data opname stok berhasil dicatat.'],
    'update_ok'   => ['success', 'Data opname stok berhasil diperbarui.'],
    'hapus_ok'    => ['success', 'Data opname stok berhasil dihapus.'],
    'gagal_hapus' => ['danger',  'Data opname stok gagal dihapus.'],
];
$pesan = $daftar_pesan[$_GET['pesan'] ?? ''] ?? null;

// ---------- PENCARIAN & HALAMAN ----------
$kata_cari        = trim($_GET['cari'] ?? '');
$pola_cari        = '%' . $kata_cari . '%';
$kondisi_cari     = $kata_cari !== '' ? " WHERE products.nama_barang LIKE ?" : '';
$data_per_halaman = 10;
$halaman_aktif    = max(1, (int) ($_GET['halaman'] ?? 1));

// ---------- HITUNG TOTAL BARANG ----------
$query_total = "SELECT COUNT(*) AS total_data FROM products" . $kondisi_cari;
$statement_total = $koneksi->prepare($query_total);
if ($kata_cari !== '') {
    $statement_total->bind_param('s', $pola_cari);
}
$statement_total->execute();
$total_data    = (int) $statement_total->get_result()->fetch_assoc()['total_data'];
$total_halaman = max(1, (int) ceil($total_data / $data_per_halaman));
$halaman_aktif = min($halaman_aktif, $total_halaman);
$mulai_dari    = ($halaman_aktif - 1) * $data_per_halaman;

// ---------- AMBIL STOK SETIAP BARANG (stok = masuk - keluar + opname) ----------
$query_data = "SELECT products.id, products.nama_barang, products.stok_minimal, products.harga_satuan,
                      COALESCE(SUM(CASE stock_transactions.jenis_transaksi
                          WHEN 'masuk'  THEN stock_transactions.jumlah
                          WHEN 'keluar' THEN -stock_transactions.jumlah
                          WHEN 'opname' THEN stock_transactions.jumlah
                          ELSE 0 END), 0) AS stok_saat_ini
               FROM products
               LEFT JOIN stock_transactions ON stock_transactions.product_id = products.id"
               . $kondisi_cari . "
               GROUP BY products.id, products.nama_barang, products.stok_minimal, products.harga_satuan
               ORDER BY products.nama_barang ASC
               LIMIT ? OFFSET ?";
$statement_data = $koneksi->prepare($query_data);
if ($kata_cari !== '') {
    $statement_data->bind_param('sii', $pola_cari, $data_per_halaman, $mulai_dari);
} else {
    $statement_data->bind_param('ii', $data_per_halaman, $mulai_dari);
}
$statement_data->execute();
$hasil_data = $statement_data->get_result();

// ---------- RIWAYAT OPNAME (10 terakhir) ----------
$hasil_opname = $koneksi->query(
    "SELECT stock_transactions.id, stock_transactions.tanggal, stock_transactions.jumlah,
            products.nama_barang
     FROM stock_transactions
     INNER JOIN products ON products.id = stock_transactions.product_id
     WHERE stock_transactions.jenis_transaksi = 'opname'
     ORDER BY stock_transactions.tanggal DESC, stock_transactions.id DESC
     LIMIT 10"
);

$nomor_urut = $mulai_dari + 1;

// Fungsi kecil untuk membuat link halaman (tetap membawa kata pencarian)
function linkHalaman($nomor_halaman, $kata_cari)
{
    return 'index.php?page=stok&cari=' . urlencode($kata_cari) . '&halaman=' . $nomor_halaman;
}
?>
  <!-- Sembunyikan scrollbar bawaan browser khusus halaman Stok & Opname (halaman tetap bisa di-scroll) -->
  <style>
    html { scrollbar-width: none; -ms-overflow-style: none; }
    html::-webkit-scrollbar { display: none; width: 0; height: 0; }
  </style>
  <main id="content" class="content py-10">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="">
              <h1 class="fs-3 mb-1">Stok &amp; Opname</h1>
              <p class="mb-0">Pantau stok barang dan catat hasil opname stok</p>
            </div>
            <div>
              <a href="index.php?page=tambah-opname" class="btn btn-primary">Catat Opname</a>
            </div>
          </div>
        </div>
      </div>

      <?php if ($pesan): ?>
        <div class="alert alert-<?= $pesan[0] ?>"><?= $pesan[1] ?></div>
      <?php endif; ?>

      <div class="row">
        <div class="col-12">
          <div>
            <form method="get" action="index.php" class="d-flex gap-2 mb-3 flex-wrap justify-content-between">
              <input type="hidden" name="page" value="stok">
              <div class="d-flex gap-2">
                <input type="text" name="cari" class="form-control" placeholder="Cari nama barang..." value="<?= htmlspecialchars($kata_cari) ?>" style="max-width: 250px;">
                <button type="submit" class="btn btn-outline-secondary"><i class="ti ti-search"></i> Cari</button>
              </div>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary">
                  <i class="ti ti-file-excel"></i> Excel
                </button>
                <button type="button" class="btn btn-outline-secondary">
                  <i class="ti ti-file-type-pdf"></i> PDF
                </button>
              </div>
            </form>
          </div>
          <div class="card table-responsive mb-4">
            <table class="table mb-0 text-nowrap table-hover">
              <thead class="table-light border-light">
                <tr>
                  <th>No</th>
                  <th>Nama Barang</th>
                  <th>Stok Saat Ini</th>
                  <th>Stok Minimal</th>
                  <th>Harga Satuan</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($hasil_data->num_rows === 0): ?>
                  <tr>
                    <td colspan="7" class="text-center text-secondary py-4">Belum ada data barang.</td>
                  </tr>
                <?php endif; ?>

                <?php while ($barang = $hasil_data->fetch_assoc()):
                    $stok = (int) $barang['stok_saat_ini'];
                    if ($stok <= 0) {
                        $label_status = 'Habis';
                        $warna_status = 'danger';
                    } elseif ($stok <= (int) $barang['stok_minimal']) {
                        $label_status = 'Menipis';
                        $warna_status = 'warning';
                    } else {
                        $label_status = 'Aman';
                        $warna_status = 'success';
                    }
                ?>
                  <tr class="align-middle">
                    <td><?= $nomor_urut++ ?></td>
                    <td><?= htmlspecialchars($barang['nama_barang']) ?></td>
                    <td><?= number_format($stok, 0, ',', '.') ?></td>
                    <td><?= number_format($barang['stok_minimal'], 0, ',', '.') ?></td>
                    <td>Rp <?= number_format($barang['harga_satuan'], 0, ',', '.') ?></td>
                    <td><span class="badge bg-<?= $warna_status ?>-subtle text-<?= $warna_status ?>"><?= $label_status ?></span></td>
                    <td class="">
                      <a href="index.php?page=tambah-opname&product_id=<?= $barang['id'] ?>" class="btn btn-sm btn-outline-primary">Opname</a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
              <tfoot class="">
                <tr>
                  <td colspan="3" class="border-bottom-0">Menampilkan <?= $hasil_data->num_rows ?> dari <?= $total_data ?> barang</td>
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

          <h2 class="h5 mb-3">Riwayat Opname Terakhir</h2>
          <div class="card table-responsive">
            <table class="table mb-0 text-nowrap table-hover">
              <thead class="table-light border-light">
                <tr>
                  <th>Tanggal Opname</th>
                  <th>Nama Barang</th>
                  <th>Selisih</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($hasil_opname->num_rows === 0): ?>
                  <tr>
                    <td colspan="4" class="text-center text-secondary py-4">Belum ada riwayat opname.</td>
                  </tr>
                <?php endif; ?>

                <?php while ($opname = $hasil_opname->fetch_assoc()):
                    $selisih = (int) $opname['jumlah'];
                    if ($selisih > 0) {
                        $warna_selisih = 'success';
                        $teks_selisih  = '+' . $selisih;
                    } elseif ($selisih < 0) {
                        $warna_selisih = 'danger';
                        $teks_selisih  = (string) $selisih;
                    } else {
                        $warna_selisih = 'secondary';
                        $teks_selisih  = '0';
                    }
                ?>
                  <tr class="align-middle">
                    <td><?= date('d M Y', strtotime($opname['tanggal'])) ?></td>
                    <td><?= htmlspecialchars($opname['nama_barang']) ?></td>
                    <td><span class="badge bg-<?= $warna_selisih ?>-subtle text-<?= $warna_selisih ?>"><?= $teks_selisih ?></span></td>
                    <td class="">
                      <a href="index.php?page=edit-opname&id=<?= $opname['id'] ?>" class=""><i class="ti ti-edit "></i></a>
                      <a href="function/stok.php?aksi=hapus&id=<?= $opname['id'] ?>" class="link-danger" onclick="return confirm('Yakin hapus data opname ini?')"><i class="ti ti-trash ms-2"></i></a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
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
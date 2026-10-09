<?php
include_once __DIR__ . '/../../database/connection.php';
$koneksi = (new Database())->conn;

// ---------- PESAN (setelah tambah / ubah / hapus) ----------
$daftar_pesan = [
    'tambah_ok'        => ['success', 'Pengajuan kebutuhan bahan baku berhasil ditambahkan.'],
    'update_ok'        => ['success', 'Pengajuan kebutuhan bahan baku berhasil diperbarui.'],
    'hapus_ok'         => ['success', 'Pengajuan kebutuhan bahan baku berhasil dihapus.'],
    'gagal_hapus'      => ['danger',  'Pengajuan kebutuhan bahan baku gagal dihapus.'],
    'tidak_bisa_diubah' => ['warning', 'Pengajuan yang sudah diproses tidak bisa diubah.'],
    'tidak_bisa_hapus' => ['warning', 'Pengajuan yang sudah diproses tidak bisa dihapus.'],
];
$pesan = $daftar_pesan[$_GET['pesan'] ?? ''] ?? null;

// Warna badge untuk setiap status PO
$warna_status_po = [
    'diajukan'  => 'warning',
    'disetujui' => 'primary',
    'selesai'   => 'success',
    'ditolak'   => 'danger',
];

// ---------- PENCARIAN & HALAMAN ----------
$kata_cari        = trim($_GET['cari'] ?? '');
$pola_cari        = '%' . $kata_cari . '%';
$kondisi_cari     = $kata_cari !== '' ? " WHERE suppliers.nama_perusahaan LIKE ?" : '';
$data_per_halaman = 10;
$halaman_aktif    = max(1, (int) ($_GET['halaman'] ?? 1));

// ---------- HITUNG TOTAL DATA ----------
$query_total = "SELECT COUNT(*) AS total_data
                FROM purchase_orders
                INNER JOIN suppliers ON suppliers.id = purchase_orders.supplier_id" . $kondisi_cari;
$statement_total = $koneksi->prepare($query_total);
if ($kata_cari !== '') {
    $statement_total->bind_param('s', $pola_cari);
}
$statement_total->execute();
$total_data    = (int) $statement_total->get_result()->fetch_assoc()['total_data'];
$total_halaman = max(1, (int) ceil($total_data / $data_per_halaman));
$halaman_aktif = min($halaman_aktif, $total_halaman);
$mulai_dari    = ($halaman_aktif - 1) * $data_per_halaman;

// ---------- AMBIL DATA PENGAJUAN (PURCHASE ORDER) ----------
$query_data = "SELECT purchase_orders.id, purchase_orders.tanggal_po, purchase_orders.status_po,
                      suppliers.nama_perusahaan, suppliers.telepon
               FROM purchase_orders
               INNER JOIN suppliers ON suppliers.id = purchase_orders.supplier_id"
               . $kondisi_cari . "
               ORDER BY purchase_orders.tanggal_po DESC, purchase_orders.id DESC
               LIMIT ? OFFSET ?";
$statement_data = $koneksi->prepare($query_data);
if ($kata_cari !== '') {
    $statement_data->bind_param('sii', $pola_cari, $data_per_halaman, $mulai_dari);
} else {
    $statement_data->bind_param('ii', $data_per_halaman, $mulai_dari);
}
$statement_data->execute();
$hasil_data = $statement_data->get_result();

$nomor_urut = $mulai_dari + 1;

// Fungsi kecil untuk membuat link halaman (tetap membawa kata pencarian)
function linkHalaman($nomor_halaman, $kata_cari)
{
    return 'index.php?page=kebutuhan-bahan-baku&cari=' . urlencode($kata_cari) . '&halaman=' . $nomor_halaman;
}
?>
  <main id="content" class="content py-10">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="">
              <h1 class="fs-3 mb-1">Kebutuhan Bahan Baku</h1>
              <p class="mb-0">Ajukan kebutuhan bahan baku ke supplier dan pantau statusnya</p>
            </div>
            <div>
              <a href="index.php?page=tambah-kebutuhan-bahan-baku" class="btn btn-primary">Ajukan Kebutuhan</a>
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
              <input type="hidden" name="page" value="kebutuhan-bahan-baku">
              <div class="d-flex gap-2">
                <input type="text" name="cari" class="form-control" placeholder="Cari nama supplier..." value="<?= htmlspecialchars($kata_cari) ?>" style="max-width: 250px;">
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
          <div class="card table-responsive ">
            <table class="table mb-0 text-nowrap table-hover">
              <thead class="table-light border-light">
                <tr>
                  <th>No</th>
                  <th>Kode PO</th>
                  <th>Supplier</th>
                  <th>Telepon</th>
                  <th>Tanggal PO</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($hasil_data->num_rows === 0): ?>
                  <tr>
                    <td colspan="7" class="text-center text-secondary py-4">Belum ada pengajuan kebutuhan bahan baku.</td>
                  </tr>
                <?php endif; ?>

                <?php while ($purchase_order = $hasil_data->fetch_assoc()):
                    $warna = $warna_status_po[$purchase_order['status_po']] ?? 'secondary';
                ?>
                  <tr class="align-middle">
                    <td><?= $nomor_urut++ ?></td>
                    <td>PO-<?= str_pad($purchase_order['id'], 4, '0', STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars($purchase_order['nama_perusahaan']) ?></td>
                    <td><?= htmlspecialchars($purchase_order['telepon']) ?></td>
                    <td><?= date('d M Y', strtotime($purchase_order['tanggal_po'])) ?></td>
                    <td><span class="badge bg-<?= $warna ?>-subtle text-<?= $warna ?>"><?= ucfirst($purchase_order['status_po']) ?></span></td>
                    <td class="">
                      <?php if ($purchase_order['status_po'] === 'diajukan'): ?>
                        <a href="index.php?page=edit-kebutuhan-bahan-baku&id=<?= $purchase_order['id'] ?>" class=""><i class="ti ti-edit "></i></a>
                        <a href="function/kebutuhan-bahan-baku.php?aksi=hapus&id=<?= $purchase_order['id'] ?>" class="link-danger" onclick="return confirm('Yakin hapus pengajuan ini?')"><i class="ti ti-trash ms-2"></i></a>
                      <?php else: ?>
                        <span class="text-secondary small">-</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
              <tfoot class="">
                <tr>
                  <td colspan="3" class="border-bottom-0">Menampilkan <?= $hasil_data->num_rows ?> dari <?= $total_data ?> data</td>
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
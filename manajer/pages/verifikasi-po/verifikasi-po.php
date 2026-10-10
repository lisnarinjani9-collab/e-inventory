<?php
include_once __DIR__ . '/../../database/connection.php';
$koneksi = (new Database())->conn;

// ---------- PESAN (setelah setujui / tolak / selesai) ----------
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

// ---------- FILTER STATUS, PENCARIAN, HALAMAN ----------
// Tanpa pilihan status, yang ditampilkan adalah PO yang perlu diverifikasi.
$daftar_status  = ['semua', 'diajukan', 'disetujui', 'selesai', 'ditolak'];
$status_dipilih = $_GET['status'] ?? 'diajukan';
if (!in_array($status_dipilih, $daftar_status, true)) {
    $status_dipilih = 'diajukan';
}

$kata_cari        = trim($_GET['cari'] ?? '');
$pola_cari        = '%' . $kata_cari . '%';
$data_per_halaman = 10;
$halaman_aktif    = max(1, (int) ($_GET['halaman'] ?? 1));

// Susun syarat pencarian secara bertahap
$daftar_syarat = [];
$tipe_isian    = '';
$isian         = [];
if ($status_dipilih !== 'semua') {
    $daftar_syarat[] = "purchase_orders.status_po = ?";
    $tipe_isian     .= 's';
    $isian[]         = $status_dipilih;
}
if ($kata_cari !== '') {
    $daftar_syarat[] = "suppliers.nama_perusahaan LIKE ?";
    $tipe_isian     .= 's';
    $isian[]         = $pola_cari;
}
$syarat = count($daftar_syarat) > 0 ? ' WHERE ' . implode(' AND ', $daftar_syarat) : '';

// ---------- JUMLAH PO PER STATUS (untuk tombol filter) ----------
$jumlah_po = ['diajukan' => 0, 'disetujui' => 0, 'selesai' => 0, 'ditolak' => 0];
$hasil_jumlah = $koneksi->query("SELECT status_po, COUNT(*) AS total FROM purchase_orders GROUP BY status_po");
while ($baris = $hasil_jumlah->fetch_assoc()) {
    $jumlah_po[$baris['status_po']] = (int) $baris['total'];
}
$jumlah_po['semua'] = array_sum($jumlah_po);

// ---------- HITUNG TOTAL DATA ----------
$statement_total = $koneksi->prepare(
    "SELECT COUNT(*) AS total_data
     FROM purchase_orders
     INNER JOIN suppliers ON suppliers.id = purchase_orders.supplier_id" . $syarat
);
if (count($isian) > 0) {
    $statement_total->bind_param($tipe_isian, ...$isian);
}
$statement_total->execute();
$total_data    = (int) $statement_total->get_result()->fetch_assoc()['total_data'];
$total_halaman = max(1, (int) ceil($total_data / $data_per_halaman));
$halaman_aktif = min($halaman_aktif, $total_halaman);
$mulai_dari    = ($halaman_aktif - 1) * $data_per_halaman;

// ---------- AMBIL DATA PO ----------
$statement_data = $koneksi->prepare(
    "SELECT purchase_orders.id, purchase_orders.tanggal_po, purchase_orders.status_po,
            suppliers.nama_perusahaan, suppliers.telepon
     FROM purchase_orders
     INNER JOIN suppliers ON suppliers.id = purchase_orders.supplier_id" . $syarat . "
     ORDER BY purchase_orders.tanggal_po DESC, purchase_orders.id DESC
     LIMIT ? OFFSET ?"
);
$isian_data   = array_merge($isian, [$data_per_halaman, $mulai_dari]);
$statement_data->bind_param($tipe_isian . 'ii', ...$isian_data);
$statement_data->execute();
$hasil_data = $statement_data->get_result();

$nomor_urut = $mulai_dari + 1;

// Fungsi kecil untuk membuat link (tetap membawa status dan kata pencarian)
function linkHalaman($nomor_halaman, $status_dipilih, $kata_cari)
{
    return 'index.php?page=verifikasi-po&status=' . $status_dipilih
        . '&cari=' . urlencode($kata_cari) . '&halaman=' . $nomor_halaman;
}
?>
  <main id="content" class="content py-10">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="mb-4">
            <h1 class="fs-3 mb-1">Verifikasi PO</h1>
            <p class="mb-0">Periksa pengajuan kebutuhan bahan baku dari gudang, lalu setujui atau tolak</p>
          </div>
        </div>
      </div>

      <?php if ($pesan): ?>
        <div class="alert alert-<?= $pesan[0] ?>"><?= $pesan[1] ?></div>
      <?php endif; ?>

      <div class="row">
        <div class="col-12">
          <!-- Tombol filter status -->
          <div class="d-flex flex-wrap gap-2 mb-3">
            <?php foreach ($daftar_status as $status): ?>
              <a href="index.php?page=verifikasi-po&status=<?= $status ?>&cari=<?= urlencode($kata_cari) ?>"
                class="btn btn-sm <?= $status === $status_dipilih ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <?= ucfirst($status) ?> <span class="badge bg-light text-dark ms-1"><?= $jumlah_po[$status] ?></span>
              </a>
            <?php endforeach; ?>
          </div>

          <form method="get" action="index.php" class="d-flex gap-2 mb-3 flex-wrap">
            <input type="hidden" name="page" value="verifikasi-po">
            <input type="hidden" name="status" value="<?= $status_dipilih ?>">
            <input type="text" name="cari" class="form-control" placeholder="Cari nama supplier..." value="<?= htmlspecialchars($kata_cari) ?>" style="max-width: 250px;">
            <button type="submit" class="btn btn-outline-secondary"><i class="ti ti-search"></i> Cari</button>
          </form>

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
                    <td colspan="7" class="text-center text-secondary py-4">Tidak ada PO pada filter ini.</td>
                  </tr>
                <?php endif; ?>

                <?php while ($purchase_order = $hasil_data->fetch_assoc()):
                    $warna   = $warna_status_po[$purchase_order['status_po']] ?? 'secondary';
                    $kode_po = 'PO-' . str_pad($purchase_order['id'], 4, '0', STR_PAD_LEFT);
                ?>
                  <tr class="align-middle">
                    <td><?= $nomor_urut++ ?></td>
                    <td class="fw-semibold"><?= $kode_po ?></td>
                    <td><?= htmlspecialchars($purchase_order['nama_perusahaan']) ?></td>
                    <td><?= htmlspecialchars($purchase_order['telepon']) ?></td>
                    <td><?= date('d M Y', strtotime($purchase_order['tanggal_po'])) ?></td>
                    <td><span class="badge bg-<?= $warna ?>-subtle text-<?= $warna ?>"><?= ucfirst($purchase_order['status_po']) ?></span></td>
                    <td>
                      <a href="index.php?page=detail-po&id=<?= $purchase_order['id'] ?>" class="btn btn-sm btn-outline-secondary">Detail</a>
                      <?php if ($purchase_order['status_po'] === 'diajukan'): ?>
                        <form method="post" action="function/po.php?aksi=setujui" class="d-inline" onsubmit="return confirm('Setujui <?= $kode_po ?>?')">
                          <input type="hidden" name="id" value="<?= $purchase_order['id'] ?>">
                          <input type="hidden" name="dari" value="daftar">
                          <button type="submit" class="btn btn-sm btn-success">Setujui</button>
                        </form>
                        <form method="post" action="function/po.php?aksi=tolak" class="d-inline" onsubmit="return confirm('Tolak <?= $kode_po ?>?')">
                          <input type="hidden" name="id" value="<?= $purchase_order['id'] ?>">
                          <input type="hidden" name="dari" value="daftar">
                          <button type="submit" class="btn btn-sm btn-outline-danger">Tolak</button>
                        </form>
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
                          <a class="page-link" href="<?= linkHalaman($halaman_aktif - 1, $status_dipilih, $kata_cari) ?>">Sebelumnya</a>
                        </li>
                        <?php for ($nomor_halaman = 1; $nomor_halaman <= $total_halaman; $nomor_halaman++): ?>
                          <li class="page-item <?= $nomor_halaman === $halaman_aktif ? 'active' : '' ?>">
                            <a class="page-link" href="<?= linkHalaman($nomor_halaman, $status_dipilih, $kata_cari) ?>"><?= $nomor_halaman ?></a>
                          </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $halaman_aktif >= $total_halaman ? 'disabled' : '' ?>">
                          <a class="page-link" href="<?= linkHalaman($halaman_aktif + 1, $status_dipilih, $kata_cari) ?>">Berikutnya</a>
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
<?php
// Variabel di bawah disiapkan di function/data-dashboard.php (lewat partials/head.php).
// Baris ??= hanya memberi nilai awal supaya editor tidak menandai "undefined variable";
// kalau variabelnya sudah ada, nilainya tidak berubah.
include_once __DIR__ . '/../function/helper.php';
$jumlah_po         ??= ['diajukan' => 0, 'disetujui' => 0, 'ditolak' => 0, 'selesai' => 0];
$filter_status       ??= '';
$daftar_po           ??= null;
$warna_status        ??= [];

/**
 * @var array<string,int> $jumlah_po
 * @var string $filter_status
 * @var mysqli_result|null $daftar_po
 * @var array<string,string> $warna_status
 */
?>
<section id="riwayat-po" class="section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Riwayat PO</h2>
        <p>10 Purchase Order terbaru milik perusahaan Anda</p>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <!-- Filter status -->
        <div class="status-chips mb-3">
          <a href="/#riwayat-po" class="<?= $filter_status === '' ? 'active' : '' ?>">Semua</a>
          <?php foreach (array_keys($jumlah_po) as $status): ?>
            <a href="/?status=<?= $status ?>#riwayat-po" class="<?= $filter_status === $status ? 'active' : '' ?>"><?= ucfirst($status) ?></a>
          <?php endforeach; ?>
        </div>

        <div class="panel">
          <div class="table-responsive">
            <table class="table mb-0">
              <thead>
                <tr>
                  <th style="width:70px">No</th>
                  <th>No. PO</th>
                  <th>Tanggal PO</th>
                  <th>Barang Diterima Gudang</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($daftar_po && $daftar_po->num_rows > 0): $no = 1; ?>
                  <?php while ($po = $daftar_po->fetch_assoc()): $warna = $warna_status[$po['status_po']] ?? 'secondary'; ?>
                    <tr>
                      <td><?= $no++ ?></td>
                      <td>PO-<?= str_pad($po['id'], 4, '0', STR_PAD_LEFT) ?></td>
                      <td><?= tanggalIndo($po['tanggal_po']) ?></td>
                      <td><?= $po['barang_diterima'] ? e($po['barang_diterima']) : '<span class="text-secondary">-</span>' ?></td>
                      <td><span class="badge bg-<?= $warna ?>-subtle text-<?= $warna ?>-emphasis"><?= ucfirst($po['status_po']) ?></span></td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="5" class="text-center text-secondary py-4">Belum ada Purchase Order<?= $filter_status !== '' ? ' dengan status ' . e($filter_status) : '' ?>.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>

    </section>
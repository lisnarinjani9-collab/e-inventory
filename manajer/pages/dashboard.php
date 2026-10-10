<?php
include_once __DIR__ . '/../database/connection.php';
$koneksi = (new Database())->conn;

// ---------- PESAN (setelah menyetujui / menolak PO dari dashboard) ----------
$daftar_pesan = [
    'setuju_ok'           => ['success', 'PO berhasil disetujui. Petugas gudang dan supplier bisa melihat statusnya.'],
    'tolak_ok'            => ['success', 'PO berhasil ditolak.'],
    'tidak_bisa_diproses' => ['warning', 'PO ini sudah diproses sebelumnya.'],
    'tidak_valid'         => ['danger',  'Data tidak valid.'],
];
$pesan = $daftar_pesan[$_GET['pesan'] ?? ''] ?? null;

// ---------- JUMLAH PO PER STATUS ----------
$jumlah_po = ['diajukan' => 0, 'disetujui' => 0, 'selesai' => 0, 'ditolak' => 0];
$hasil_jumlah = $koneksi->query("SELECT status_po, COUNT(*) AS total FROM purchase_orders GROUP BY status_po");
while ($baris = $hasil_jumlah->fetch_assoc()) {
    $jumlah_po[$baris['status_po']] = (int) $baris['total'];
}

// ---------- JUMLAH SUPPLIER ----------
$total_supplier = (int) $koneksi->query("SELECT COUNT(*) AS total FROM suppliers")->fetch_assoc()['total'];

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

$nilai_persediaan = 0;
$stok_menipis     = [];
while ($barang = $hasil_stok->fetch_assoc()) {
    $stok    = (int) $barang['stok_saat_ini'];
    $minimal = (int) $barang['stok_minimal'];
    $nilai_persediaan += max(0, $stok) * (float) $barang['harga_satuan'];
    if ($stok <= $minimal) {
        $barang['persen'] = $minimal > 0 ? max(0, min(100, (int) round($stok / $minimal * 100))) : 0;
        $stok_menipis[] = $barang;
    }
}
$total_stok_menipis = count($stok_menipis);
usort($stok_menipis, function ($barang_pertama, $barang_kedua) {
    return $barang_pertama['persen'] <=> $barang_kedua['persen'];
});
$stok_menipis_tampil = array_slice($stok_menipis, 0, 5);

// ---------- PO YANG MENUNGGU VERIFIKASI (yang paling lama menunggu di atas) ----------
$hasil_menunggu = $koneksi->query(
    "SELECT purchase_orders.id, purchase_orders.tanggal_po, suppliers.nama_perusahaan, suppliers.telepon
     FROM purchase_orders
     INNER JOIN suppliers ON suppliers.id = purchase_orders.supplier_id
     WHERE purchase_orders.status_po = 'diajukan'
     ORDER BY purchase_orders.tanggal_po ASC, purchase_orders.id ASC
     LIMIT 5"
);

// ---------- AKTIVITAS GUDANG TERBARU ----------
$hasil_aktivitas = $koneksi->query(
    "SELECT stock_transactions.tanggal, stock_transactions.jenis_transaksi, stock_transactions.jumlah,
            stock_transactions.po_id, products.nama_barang
     FROM stock_transactions
     INNER JOIN products ON products.id = stock_transactions.product_id
     ORDER BY stock_transactions.tanggal DESC, stock_transactions.id DESC
     LIMIT 6"
);

$kartu_status = [
    ['judul' => 'Menunggu Verifikasi', 'angka' => $jumlah_po['diajukan'],  'ket' => 'perlu keputusan Anda', 'icon' => 'ti-hourglass',     'warna' => 'warning', 'status' => 'diajukan'],
    ['judul' => 'Disetujui',           'angka' => $jumlah_po['disetujui'], 'ket' => 'menunggu barang',      'icon' => 'ti-circle-check',  'warna' => 'primary', 'status' => 'disetujui'],
    ['judul' => 'Selesai',             'angka' => $jumlah_po['selesai'],   'ket' => 'barang sudah diterima', 'icon' => 'ti-package',      'warna' => 'success', 'status' => 'selesai'],
    ['judul' => 'Ditolak',             'angka' => $jumlah_po['ditolak'],   'ket' => 'tidak disetujui',      'icon' => 'ti-circle-x',      'warna' => 'danger',  'status' => 'ditolak'],
];

$nama_bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$tanggal_hari_ini = date('d') . ' ' . $nama_bulan[(int) date('n') - 1] . ' ' . date('Y');
?>
<!-- Sembunyikan scrollbar bawaan browser di halaman ini (halaman tetap bisa di-scroll) -->
<style>
  html { scrollbar-width: none; -ms-overflow-style: none; }
  html::-webkit-scrollbar { display: none; width: 0; height: 0; }
</style>
<main id="content" class="content py-10">
  <div class="container-fluid">

    <!-- ===== BANNER ===== -->
    <div class="row mb-3">
      <div class="col-12">
        <div class="card border-0 bg-primary bg-opacity-10">
          <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
              <div class="icon-shape icon-lg bg-primary text-white rounded-circle">
                <i class="ti ti-briefcase fs-3"></i>
              </div>
              <div>
                <h1 class="fs-4 mb-1">Halo, <?= htmlspecialchars($_SESSION['nama'] ?? 'Manajer') ?> 👋</h1>
                <p class="mb-0 text-secondary">
                  <?= $tanggal_hari_ini ?> &bull;
                  <?php if ($jumlah_po['diajukan'] > 0): ?>
                    Ada <strong><?= $jumlah_po['diajukan'] ?> pengajuan PO</strong> yang menunggu verifikasi Anda.
                  <?php else: ?>
                    Tidak ada pengajuan PO yang menunggu. Semua sudah diproses.
                  <?php endif; ?>
                </p>
              </div>
            </div>
            <a href="index.php?page=verifikasi-po" class="btn btn-primary">
              <i class="ti ti-file-check me-1"></i>Verifikasi PO
            </a>
          </div>
        </div>
      </div>
    </div>

    <?php if ($pesan): ?>
      <div class="alert alert-<?= $pesan[0] ?>"><?= $pesan[1] ?></div>
    <?php endif; ?>

    <!-- ===== STATUS PO ===== -->
    <div class="row g-3 mb-3">
      <?php foreach ($kartu_status as $kartu): ?>
        <div class="col-xl-3 col-md-6 col-12">
          <a href="index.php?page=verifikasi-po&status=<?= $kartu['status'] ?>" class="card h-100 text-decoration-none text-reset">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between align-items-start mb-4">
                <div class="icon-shape icon-md bg-<?= $kartu['warna'] ?>-subtle text-<?= $kartu['warna'] ?> rounded-2">
                  <i class="ti <?= $kartu['icon'] ?> fs-4"></i>
                </div>
                <i class="ti ti-arrow-up-right text-secondary"></i>
              </div>
              <h2 class="fs-6 mb-1"><?= $kartu['judul'] ?></h2>
              <div class="d-flex align-items-baseline gap-2">
                <span class="h3 fw-bold mb-0"><?= $kartu['angka'] ?></span>
                <small class="text-secondary"><?= $kartu['ket'] ?></small>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- ===== PO MENUNGGU + KONDISI GUDANG ===== -->
    <div class="row g-3 mb-3">
      <div class="col-lg-8 col-12">
        <div class="card h-100">
          <div class="card-header bg-white d-flex justify-content-between align-items-center px-4 py-3">
            <h3 class="h5 mb-0">PO Menunggu Verifikasi</h3>
            <a href="index.php?page=verifikasi-po" class="small text-primary text-decoration-underline">Lihat Semua</a>
          </div>
          <div class="table-responsive">
            <table class="table table-hover mb-0 text-nowrap align-middle">
              <thead class="table-light border-light">
                <tr>
                  <th class="ps-4">Kode PO</th>
                  <th>Supplier</th>
                  <th>Tanggal</th>
                  <th class="pe-4">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($hasil_menunggu->num_rows === 0): ?>
                  <tr>
                    <td colspan="4" class="text-center text-secondary py-4">Tidak ada PO yang menunggu verifikasi.</td>
                  </tr>
                <?php endif; ?>

                <?php while ($purchase_order = $hasil_menunggu->fetch_assoc()):
                    $kode_po = 'PO-' . str_pad($purchase_order['id'], 4, '0', STR_PAD_LEFT);
                ?>
                  <tr>
                    <td class="ps-4 fw-semibold"><?= $kode_po ?></td>
                    <td><?= htmlspecialchars($purchase_order['nama_perusahaan']) ?></td>
                    <td class="text-secondary"><?= date('d M Y', strtotime($purchase_order['tanggal_po'])) ?></td>
                    <td class="pe-4">
                      <a href="index.php?page=detail-po&id=<?= $purchase_order['id'] ?>" class="btn btn-sm btn-outline-secondary">Detail</a>
                      <form method="post" action="function/po.php?aksi=setujui" class="d-inline" onsubmit="return confirm('Setujui <?= $kode_po ?>?')">
                        <input type="hidden" name="id" value="<?= $purchase_order['id'] ?>">
                        <input type="hidden" name="dari" value="dashboard">
                        <button type="submit" class="btn btn-sm btn-success">Setujui</button>
                      </form>
                      <form method="post" action="function/po.php?aksi=tolak" class="d-inline" onsubmit="return confirm('Tolak <?= $kode_po ?>?')">
                        <input type="hidden" name="id" value="<?= $purchase_order['id'] ?>">
                        <input type="hidden" name="dari" value="dashboard">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Tolak</button>
                      </form>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-lg-4 col-12">
        <div class="card h-100">
          <div class="card-header bg-white d-flex justify-content-between align-items-center px-4 py-3">
            <h3 class="h5 mb-0">Kondisi Gudang</h3>
            <a href="index.php?page=laporan-stok" class="small text-primary text-decoration-underline">Laporan</a>
          </div>
          <div class="card-body p-4">
            <div class="d-flex justify-content-between mb-1">
              <span class="text-secondary">Nilai persediaan</span>
              <span class="fw-bold">Rp <?= number_format($nilai_persediaan, 0, ',', '.') ?></span>
            </div>
            <div class="d-flex justify-content-between mb-1">
              <span class="text-secondary">Supplier terdaftar</span>
              <span class="fw-bold"><?= $total_supplier ?></span>
            </div>
            <div class="d-flex justify-content-between mb-4">
              <span class="text-secondary">Barang stok menipis</span>
              <span class="fw-bold text-<?= $total_stok_menipis > 0 ? 'danger' : 'success' ?>"><?= $total_stok_menipis ?></span>
            </div>

            <h4 class="h6 mb-3">Paling kritis</h4>
            <?php if ($total_stok_menipis === 0): ?>
              <p class="text-secondary mb-0">Semua stok barang masih aman.</p>
            <?php endif; ?>
            <div class="d-flex flex-column gap-3">
              <?php foreach ($stok_menipis_tampil as $barang):
                  $warna = $barang['persen'] <= 25 ? 'danger' : 'warning';
              ?>
                <div>
                  <div class="d-flex justify-content-between small mb-1">
                    <span class="fw-semibold"><?= htmlspecialchars($barang['nama_barang']) ?></span>
                    <span class="text-<?= $warna ?>"><?= (int) $barang['stok_saat_ini'] ?> / <?= (int) $barang['stok_minimal'] ?></span>
                  </div>
                  <div class="progress" style="height: 6px;">
                    <div class="progress-bar bg-<?= $warna ?>" role="progressbar" style="width: <?= $barang['persen'] ?>%"></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== AKTIVITAS GUDANG ===== -->
    <div class="row g-3">
      <div class="col-12">
        <div class="card">
          <div class="card-header bg-white d-flex justify-content-between align-items-center px-4 py-3">
            <h3 class="h5 mb-0">Aktivitas Gudang Terbaru</h3>
            <a href="index.php?page=riwayat-transaksi" class="small text-primary text-decoration-underline">Lihat Semua</a>
          </div>
          <div class="table-responsive">
            <table class="table table-hover mb-0 text-nowrap align-middle">
              <thead class="table-light border-light">
                <tr>
                  <th class="ps-4">Tanggal</th>
                  <th>Nama Barang</th>
                  <th>Jenis</th>
                  <th class="text-end">Jumlah</th>
                  <th class="pe-4">Ref. PO</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($hasil_aktivitas->num_rows === 0): ?>
                  <tr>
                    <td colspan="5" class="text-center text-secondary py-4">Belum ada aktivitas gudang.</td>
                  </tr>
                <?php endif; ?>

                <?php while ($aktivitas = $hasil_aktivitas->fetch_assoc()): ?>
                  <tr>
                    <td class="ps-4 text-secondary"><?= date('d M Y', strtotime($aktivitas['tanggal'])) ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($aktivitas['nama_barang']) ?></td>
                    <td>
                      <?php if ($aktivitas['jenis_transaksi'] === 'masuk'): ?>
                        <span class="badge bg-success-subtle text-success"><i class="ti ti-arrow-down-left me-1"></i>Masuk</span>
                      <?php elseif ($aktivitas['jenis_transaksi'] === 'keluar'): ?>
                        <span class="badge bg-danger-subtle text-danger"><i class="ti ti-arrow-up-right me-1"></i>Keluar</span>
                      <?php else: ?>
                        <span class="badge bg-info-subtle text-info"><i class="ti ti-clipboard-check me-1"></i>Opname</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end fw-semibold"><?= number_format($aktivitas['jumlah'], 0, ',', '.') ?></td>
                    <td class="pe-4 text-secondary"><?= $aktivitas['po_id'] ? 'PO-' . str_pad($aktivitas['po_id'], 4, '0', STR_PAD_LEFT) : '-' ?></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</main>
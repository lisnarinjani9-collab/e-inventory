<?php
include_once __DIR__ . '/../database/connection.php';
$koneksi = (new Database())->conn;

// Fungsi kecil: jalankan query COUNT(*) lalu ambil angkanya
function ambilTotal($koneksi, $query)
{
    return (int) $koneksi->query($query)->fetch_assoc()['total'];
}

// ---------- RINGKASAN ----------
$total_masuk_hari_ini = ambilTotal(
    $koneksi,
    "SELECT COUNT(*) AS total FROM stock_transactions
     WHERE jenis_transaksi = 'masuk' AND DATE(tanggal) = CURDATE()"
);
$total_keluar_hari_ini = ambilTotal(
    $koneksi,
    "SELECT COUNT(*) AS total FROM stock_transactions
     WHERE jenis_transaksi = 'keluar' AND DATE(tanggal) = CURDATE()"
);
$total_pengajuan_menunggu = ambilTotal(
    $koneksi,
    "SELECT COUNT(*) AS total FROM purchase_orders WHERE status_po = 'diajukan'"
);

// ---------- STOK SETIAP BARANG (stok = masuk - keluar + opname) ----------
$hasil_stok = $koneksi->query(
    "SELECT products.id, products.nama_barang, products.stok_minimal,
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

// Pilih barang yang stoknya sudah sama dengan / di bawah stok minimal
$stok_menipis = [];
while ($barang = $hasil_stok->fetch_assoc()) {
    $stok = (int) $barang['stok_saat_ini'];
    $minimal = (int) $barang['stok_minimal'];
    if ($stok <= $minimal) {
        $barang['persen'] = $minimal > 0 ? max(0, min(100, (int) round($stok / $minimal * 100))) : 0;
        $stok_menipis[] = $barang;
    }
}
$total_stok_menipis = count($stok_menipis);

// Urutkan dari yang paling kritis, tampilkan 5 teratas
usort($stok_menipis, function ($barang_pertama, $barang_kedua) {
    return $barang_pertama['persen'] <=> $barang_kedua['persen'];
});
$stok_menipis_tampil = array_slice($stok_menipis, 0, 5);

// ---------- AKTIVITAS STOK TERBARU ----------
$hasil_aktivitas = $koneksi->query(
    "SELECT stock_transactions.tanggal, stock_transactions.jenis_transaksi, stock_transactions.jumlah,
            stock_transactions.po_id, products.nama_barang
     FROM stock_transactions
     INNER JOIN products ON products.id = stock_transactions.product_id
     ORDER BY stock_transactions.tanggal DESC, stock_transactions.id DESC
     LIMIT 6"
);

// ---------- PENGAJUAN KEBUTUHAN BAHAN BAKU TERBARU ----------
$hasil_pengajuan = $koneksi->query(
    "SELECT purchase_orders.id, purchase_orders.tanggal_po, purchase_orders.status_po,
            suppliers.nama_perusahaan
     FROM purchase_orders
     INNER JOIN suppliers ON suppliers.id = purchase_orders.supplier_id
     ORDER BY purchase_orders.tanggal_po DESC, purchase_orders.id DESC
     LIMIT 5"
);

$warna_status_po = [
    'diajukan'  => 'warning',
    'disetujui' => 'primary',
    'selesai'   => 'success',
    'ditolak'   => 'danger',
];

// Kartu menu cepat (sesuai folder pages)
$ringkasan = [
    ['judul' => 'Barang Masuk',         'angka' => $total_masuk_hari_ini,       'ket' => 'transaksi hari ini',       'icon' => 'ti-package-import',     'warna' => 'success', 'link' => 'index.php?page=barang-masuk'],
    ['judul' => 'Barang Keluar',        'angka' => $total_keluar_hari_ini,      'ket' => 'transaksi hari ini',       'icon' => 'ti-package-export',     'warna' => 'danger',  'link' => 'index.php?page=barang-keluar'],
    ['judul' => 'Stok & Opname',        'angka' => $total_stok_menipis,         'ket' => 'barang stok menipis',      'icon' => 'ti-clipboard-check',    'warna' => 'info',    'link' => 'index.php?page=stok'],
    ['judul' => 'Kebutuhan Bahan Baku', 'angka' => $total_pengajuan_menunggu,   'ket' => 'pengajuan menunggu',       'icon' => 'ti-shopping-cart-plus', 'warna' => 'warning', 'link' => 'index.php?page=kebutuhan-bahan-baku'],
];

$nama_bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$tanggal_hari_ini = date('d') . ' ' . $nama_bulan[(int) date('n') - 1] . ' ' . date('Y');
?>
<main id="content" class="content py-10">
  <div class="container-fluid">

    <!-- ===== BANNER SAMBUTAN ===== -->
    <div class="row mb-3">
      <div class="col-12">
        <div class="card border-0 bg-primary bg-opacity-10">
          <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
              <div class="icon-shape icon-lg bg-primary text-white rounded-circle">
                <i class="ti ti-building-warehouse fs-3"></i>
              </div>
              <div>
                <h1 class="fs-4 mb-1">Halo, <?= htmlspecialchars($_SESSION['nama'] ?? 'Petugas') ?> 👋</h1>
                <p class="mb-0 text-secondary"><?= $tanggal_hari_ini ?> &bull; Cek stok dan transaksi terbaru di bawah.</p>
              </div>
            </div>
            <a href="index.php?page=tambah-barang-masuk" class="btn btn-primary">
              <i class="ti ti-plus me-1"></i>Input Barang Masuk
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== MENU CEPAT ===== -->
    <div class="row g-3 mb-3">
      <?php foreach ($ringkasan as $kartu): ?>
        <div class="col-xl-3 col-md-6 col-12">
          <a href="<?= $kartu['link'] ?>" class="card h-100 text-decoration-none text-reset">
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

    <!-- ===== AKTIVITAS STOK + STOK MENIPIS ===== -->
    <div class="row g-3 mb-3">
      <div class="col-lg-8 col-12">
        <div class="card h-100">
          <div class="card-header bg-white d-flex justify-content-between align-items-center px-4 py-3">
            <h3 class="h5 mb-0">Aktivitas Stok Terbaru</h3>
            <a href="index.php?page=stok" class="small text-primary text-decoration-underline">Lihat Semua</a>
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
                    <td colspan="5" class="text-center text-secondary py-4">Belum ada aktivitas stok.</td>
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

      <div class="col-lg-4 col-12">
        <div class="card h-100">
          <div class="card-header bg-white d-flex justify-content-between align-items-center px-4 py-3">
            <h3 class="h5 mb-0">Stok Hampir Habis</h3>
            <a href="index.php?page=tambah-kebutuhan-bahan-baku" class="small text-primary text-decoration-underline">Ajukan</a>
          </div>
          <div class="card-body p-4 d-flex flex-column gap-4">
            <?php if ($total_stok_menipis === 0): ?>
              <p class="text-secondary mb-0">Semua stok barang masih aman.</p>
            <?php endif; ?>

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

    <!-- ===== PENGAJUAN KEBUTUHAN BAHAN BAKU ===== -->
    <div class="row g-3">
      <div class="col-12">
        <div class="card">
          <div class="card-header bg-white d-flex justify-content-between align-items-center px-4 py-3">
            <h3 class="h5 mb-0">Pengajuan Kebutuhan Bahan Baku</h3>
            <a href="index.php?page=tambah-kebutuhan-bahan-baku" class="btn btn-sm btn-outline-primary">
              <i class="ti ti-plus"></i> Buat Pengajuan
            </a>
          </div>
          <div class="table-responsive">
            <table class="table table-hover mb-0 text-nowrap align-middle">
              <thead class="table-light border-light">
                <tr>
                  <th class="ps-4">Kode PO</th>
                  <th>Supplier</th>
                  <th>Tanggal</th>
                  <th class="pe-4">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($hasil_pengajuan->num_rows === 0): ?>
                  <tr>
                    <td colspan="4" class="text-center text-secondary py-4">Belum ada pengajuan.</td>
                  </tr>
                <?php endif; ?>

                <?php while ($purchase_order = $hasil_pengajuan->fetch_assoc()):
                    $warna = $warna_status_po[$purchase_order['status_po']] ?? 'secondary';
                ?>
                  <tr>
                    <td class="ps-4 fw-semibold">PO-<?= str_pad($purchase_order['id'], 4, '0', STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars($purchase_order['nama_perusahaan']) ?></td>
                    <td class="text-secondary"><?= date('d M Y', strtotime($purchase_order['tanggal_po'])) ?></td>
                    <td class="pe-4"><span class="badge bg-<?= $warna ?>-subtle text-<?= $warna ?>"><?= ucfirst($purchase_order['status_po']) ?></span></td>
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
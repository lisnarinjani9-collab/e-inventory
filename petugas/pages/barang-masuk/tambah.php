<?php
include_once __DIR__ . '/../../database/connection.php';
include_once __DIR__ . '/../../function/po-barang-masuk.php';
$koneksi = (new Database())->conn;

// Daftar PO untuk pilihan "PO dan Supplier".
// Hanya PO yang sudah disetujui / selesai DAN masih punya barang supplier-nya yang belum dicatat masuk.
// PO yang semua barangnya sudah masuk tidak ditampilkan lagi.
$data_po = [];
$hasil_po = $koneksi->query(
    "SELECT purchase_orders.id, purchase_orders.supplier_id, suppliers.nama_perusahaan
     FROM purchase_orders
     INNER JOIN suppliers ON suppliers.id = purchase_orders.supplier_id
     WHERE purchase_orders.status_po IN ('disetujui', 'selesai')
     ORDER BY purchase_orders.id DESC"
);
while ($purchase_order = $hasil_po->fetch_assoc()) {
    $daftar_barang = daftarBarangBelumMasuk($koneksi, (int) $purchase_order['id'], (int) $purchase_order['supplier_id']);
    if (count($daftar_barang) === 0) {
        continue;
    }
    $data_po[] = [
        'id'     => (int) $purchase_order['id'],
        'teks'   => 'PO-' . str_pad($purchase_order['id'], 4, '0', STR_PAD_LEFT) . ' - ' . $purchase_order['nama_perusahaan'],
        'barang' => $daftar_barang,
    ];
}

$daftar_pesan = [
    'po_wajib'              => 'PO dan supplier wajib dipilih.',
    'tidak_valid'           => 'Isian tidak valid, periksa kembali.',
    'barang_tidak_ada'      => 'Barang yang dipilih tidak ditemukan.',
    'po_tidak_ada'          => 'PO yang dipilih tidak ditemukan atau belum disetujui.',
    'barang_bukan_supplier' => 'Barang yang dipilih bukan milik supplier dari PO tersebut.',
    'barang_sudah_masuk'    => 'Barang ini sudah dicatat masuk untuk PO tersebut.',
    'supplier_ditolak'      => 'Barang ini milik supplier yang ditolak manajer, jadi tidak bisa dicatat.',
];
$pesan = $daftar_pesan[$_GET['pesan'] ?? ''] ?? '';
?>
  <main id="content" class="content py-10">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div class="">
              <h1 class="fs-3 mb-1">Tambah Barang Masuk</h1>
              <p class="mb-0">Catat barang yang masuk ke gudang</p>
            </div>
            <div>
              <a href="index.php?page=barang-masuk" class="btn btn-primary">Kembali ke Daftar Barang Masuk</a>
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
              <form id="addProductForm" method="post" action="function/barang-masuk.php?aksi=simpan">
                <?php if (count($data_po) === 0): ?>
                  <div class="alert alert-warning">
                    Belum ada PO yang bisa dicatat barang masuknya. PO harus sudah disetujui manajer, dan barang dari supplier-nya belum seluruhnya dicatat masuk.
                  </div>
                <?php endif; ?>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="po_id" class="form-label">PO dan Supplier</label>
                    <select class="form-select" name="po_id" id="po_id" required <?= count($data_po) === 0 ? 'disabled' : '' ?>>
                      <option value="">Pilih PO dan supplier</option>
                      <?php foreach ($data_po as $po): ?>
                        <option value="<?= $po['id'] ?>"><?= htmlspecialchars($po['teks']) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <div class="form-text">Hanya PO yang sudah disetujui. Pilihan barang mengikuti supplier PO ini.</div>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="product_id" class="form-label">Nama Barang</label>
                    <select class="form-select" name="product_id" id="product_id" required disabled>
                      <option value="">Pilih PO terlebih dahulu</option>
                    </select>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="jumlah" class="form-label">Jumlah</label>
                    <input type="number" class="form-control" name="jumlah" id="jumlah" placeholder="0" min="1" required>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="tanggal" class="form-label">Tanggal Masuk</label>
                    <input type="date" class="form-control" name="tanggal" id="tanggal" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
                  </div>
                </div>

                <div class="d-flex gap-2">
                  <button type="submit" class="btn btn-primary" <?= count($data_po) === 0 ? 'disabled' : '' ?>>Simpan</button>
                  <button type="reset" class="btn btn-secondary">Reset</button>
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

  <script>
    // Pilihan barang mengikuti PO yang dipilih:
    // hanya barang milik supplier PO itu yang belum dicatat masuk untuk PO tersebut.
    (function () {
      var data_po        = <?= json_encode($data_po, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
      var pilihan_po     = document.getElementById('po_id');
      var pilihan_barang = document.getElementById('product_id');
      var formulir       = document.getElementById('addProductForm');

      function isiPilihanBarang() {
        var po_terpilih = null;
        for (var i = 0; i < data_po.length; i++) {
          if (String(data_po[i].id) === pilihan_po.value) {
            po_terpilih = data_po[i];
          }
        }

        pilihan_barang.innerHTML = '';
        var pilihan_awal = document.createElement('option');
        pilihan_awal.value = '';

        if (!po_terpilih) {
          pilihan_awal.textContent = 'Pilih PO terlebih dahulu';
          pilihan_barang.appendChild(pilihan_awal);
          pilihan_barang.disabled = true;
          return;
        }

        pilihan_awal.textContent = 'Pilih barang';
        pilihan_barang.appendChild(pilihan_awal);
        po_terpilih.barang.forEach(function (barang) {
          var pilihan = document.createElement('option');
          pilihan.value = barang.id;
          pilihan.textContent = barang.nama;
          pilihan_barang.appendChild(pilihan);
        });
        pilihan_barang.disabled = false;
      }

      pilihan_po.addEventListener('change', isiPilihanBarang);
      // Tombol Reset mengosongkan form, jadi pilihan barang ikut dikosongkan
      formulir.addEventListener('reset', function () { setTimeout(isiPilihanBarang, 0); });
      isiPilihanBarang();
    })();
  </script>
<?php
// supplier/function/data-dashboard.php
// Menyiapkan data untuk halaman utama supplier (dipanggil dari partials/head.php).

include_once __DIR__ . '/../database/connection.php';
include_once __DIR__ . '/helper.php';

$koneksi         = (new Database())->conn;
$supplier_id     = $_SESSION['supplier_id'] ?? null;
$nama_perusahaan = $_SESSION['nama_perusahaan'] ?? $_SESSION['nama'];

// ---------- JUMLAH PO PER STATUS ----------
$jumlah_po = ['diajukan' => 0, 'disetujui' => 0, 'ditolak' => 0, 'selesai' => 0];
if ($supplier_id) {
    $hitung = $koneksi->prepare(
        "SELECT status_po, COUNT(*) AS total FROM purchase_orders WHERE supplier_id = ? GROUP BY status_po"
    );
    $hitung->bind_param('i', $supplier_id);
    $hitung->execute();
    $hasil_hitung = $hitung->get_result();
    while ($baris = $hasil_hitung->fetch_assoc()) {
        $jumlah_po[$baris['status_po']] = (int) $baris['total'];
    }
}
$total_po = array_sum($jumlah_po);

// ---------- DAFTAR PO (bisa difilter dengan ?status=) ----------
$filter_status = $_GET['status'] ?? '';
if (!array_key_exists($filter_status, $jumlah_po)) {
    $filter_status = '';
}

$daftar_po = null;
if ($supplier_id) {
    if ($filter_status !== '') {
        $ambil_po = $koneksi->prepare(
            "SELECT purchase_orders.id, purchase_orders.tanggal_po, purchase_orders.status_po,
                    (SELECT GROUP_CONCAT(CONCAT(products.nama_barang, ' (', stock_transactions.jumlah, ')') SEPARATOR ', ')
              FROM stock_transactions
              INNER JOIN products ON products.id = stock_transactions.product_id
              WHERE stock_transactions.po_id = purchase_orders.id AND stock_transactions.jenis_transaksi = 'masuk') AS barang_diterima
             FROM purchase_orders
             WHERE purchase_orders.supplier_id = ? AND purchase_orders.status_po = ?
             ORDER BY purchase_orders.tanggal_po DESC, purchase_orders.id DESC LIMIT 10"
        );
        $ambil_po->bind_param('is', $supplier_id, $filter_status);
    } else {
        $ambil_po = $koneksi->prepare(
            "SELECT purchase_orders.id, purchase_orders.tanggal_po, purchase_orders.status_po,
                    (SELECT GROUP_CONCAT(CONCAT(products.nama_barang, ' (', stock_transactions.jumlah, ')') SEPARATOR ', ')
              FROM stock_transactions
              INNER JOIN products ON products.id = stock_transactions.product_id
              WHERE stock_transactions.po_id = purchase_orders.id AND stock_transactions.jenis_transaksi = 'masuk') AS barang_diterima
             FROM purchase_orders
             WHERE purchase_orders.supplier_id = ?
             ORDER BY purchase_orders.tanggal_po DESC, purchase_orders.id DESC LIMIT 10"
        );
        $ambil_po->bind_param('i', $supplier_id);
    }
    $ambil_po->execute();
    $daftar_po = $ambil_po->get_result();
}

// ---------- DAFTAR BARANG & HARGA ----------
$daftar_barang = $koneksi->query(
    "SELECT id, nama_barang, harga_satuan FROM products ORDER BY nama_barang ASC"
);

// ---------- PESAN SETELAH UBAH HARGA ----------
$daftar_pesan = [
    'harga_ok'          => ['success', 'Harga berhasil diperbarui.'],
    'harga_tidak_valid' => ['danger',  'Harga tidak valid. Isi dengan angka lebih dari 0.'],
    'harga_gagal'       => ['danger',  'Harga gagal disimpan. Coba lagi.'],
    'barang_ok'          => ['success', 'Bahan baku baru berhasil ditambahkan dan sudah tampil di gudang.'],
    'barang_tidak_valid' => ['danger',  'Isian tidak valid. Nama bahan baku wajib diisi (maks 150 karakter) dan harga harus lebih dari 0.'],
    'barang_sudah_ada'   => ['warning', 'Bahan baku dengan nama itu sudah ada. Ubah harganya lewat tabel di bawah.'],
    'barang_gagal'       => ['danger',  'Bahan baku gagal disimpan. Coba lagi.'],
];
$pesan = $daftar_pesan[$_GET['pesan'] ?? ''] ?? null;

$warna_status = [
    'diajukan'  => 'warning',
    'disetujui' => 'primary',
    'ditolak'   => 'danger',
    'selesai'   => 'success',
];
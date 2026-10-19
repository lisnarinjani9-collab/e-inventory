<?php
// petugas/function/barang-masuk.php

include_once __DIR__ . '/../database/connection.php';
include_once __DIR__ . '/supplier-ditolak.php';
include_once __DIR__ . '/po-barang-masuk.php';

$koneksi = (new Database())->conn;

$aksi    = $_GET['aksi'] ?? '';
$base    = '/e-inventory/petugas/index.php?page=';
$kembali = $base . 'barang-masuk';

// ---------- TAMBAH ----------
if ($aksi === 'simpan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int) ($_POST['product_id'] ?? 0);
    $po_id      = (int) ($_POST['po_id'] ?? 0);
    $jumlah     = (int) ($_POST['jumlah'] ?? 0);
    $tanggal    = htmlspecialchars($_POST['tanggal'] ?? '');

    // PO wajib dipilih, supaya jelas barang ini masuk dari PT (supplier) mana
    if ($po_id <= 0) {
        header("Location: {$base}tambah-barang-masuk&pesan=po_wajib");
        exit;
    }

    // Cek isiannya masuk akal
    $tanggal_valid = DateTime::createFromFormat('Y-m-d', $tanggal);
    if ($product_id <= 0 || $jumlah < 1 || !$tanggal_valid || $tanggal_valid->format('Y-m-d') !== $tanggal) {
        header("Location: {$base}tambah-barang-masuk&pesan=tidak_valid");
        exit;
    }

    // Cek PO ada dan sudah disetujui manajer
    $data_po = ambilPoUntukBarangMasuk($koneksi, $po_id);
    if (!$data_po) {
        header("Location: {$base}tambah-barang-masuk&pesan=po_tidak_ada");
        exit;
    }

    // Cek barang ada di tabel products
    $cek_barang = $koneksi->prepare("SELECT products.supplier_id FROM products WHERE products.id = ?");
    $cek_barang->bind_param('i', $product_id);
    $cek_barang->execute();
    $data_barang = $cek_barang->get_result()->fetch_assoc();
    if (!$data_barang) {
        header("Location: {$base}tambah-barang-masuk&pesan=barang_tidak_ada");
        exit;
    }

    // Barang harus milik supplier dari PO yang dipilih
    if ((int) $data_barang['supplier_id'] !== (int) $data_po['supplier_id']) {
        header("Location: {$base}tambah-barang-masuk&pesan=barang_bukan_supplier");
        exit;
    }

    // Barang yang sudah dicatat masuk untuk PO ini tidak boleh dicatat lagi
    if (barangSudahMasukDiPo($koneksi, $po_id, $product_id)) {
        header("Location: {$base}tambah-barang-masuk&pesan=barang_sudah_masuk");
        exit;
    }

    // Barang milik supplier yang ditolak manajer tidak boleh dicatat masuk
    if (barangDariSupplierDitolak($koneksi, $product_id)) {
        header("Location: {$base}tambah-barang-masuk&pesan=supplier_ditolak");
        exit;
    }

    // Simpan (jenis_transaksi otomatis 'masuk')
    try {
        $simpan = $koneksi->prepare(
            "INSERT INTO stock_transactions (product_id, po_id, jenis_transaksi, jumlah, tanggal)
             VALUES (?, ?, 'masuk', ?, ?)"
        );
        $simpan->bind_param('iiis', $product_id, $po_id, $jumlah, $tanggal);
        $simpan->execute();
    } catch (mysqli_sql_exception $kesalahan) {
        // Biasanya karena po_id tidak ditemukan di tabel purchase_orders
        header("Location: {$base}tambah-barang-masuk&pesan=po_tidak_ada");
        exit;
    }

    header("Location: $kembali&pesan=tambah_ok");
    exit;
}

// ---------- UBAH ----------
// Yang boleh diubah HANYA jumlah. Barang, referensi PO, dan tanggal tetap seperti saat data dibuat
// (nilainya diambil dari database, bukan dari form).
if ($aksi === 'ubah' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Ambil data dari form
    $id     = (int) ($_POST['id'] ?? 0);
    $jumlah = (int) ($_POST['jumlah'] ?? 0);

    // 2. Cek isiannya masuk akal
    if ($id <= 0 || $jumlah < 1) {
        header("Location: {$base}edit-barang-masuk&id=$id&pesan=tidak_valid");
        exit;
    }

    // 3. Ambil barang dari data aslinya
    $ambil_data = $koneksi->prepare(
        "SELECT product_id FROM stock_transactions WHERE id = ? AND jenis_transaksi = 'masuk'"
    );
    $ambil_data->bind_param('i', $id);
    $ambil_data->execute();
    $data_lama = $ambil_data->get_result()->fetch_assoc();
    if (!$data_lama) {
        header("Location: $kembali");
        exit;
    }

    // 4. Stok tidak boleh jadi minus (sebagian barang ini mungkin sudah dikeluarkan)
    $hitung_stok = $koneksi->prepare(
        "SELECT COALESCE(SUM(CASE stock_transactions.jenis_transaksi
                    WHEN 'masuk'  THEN stock_transactions.jumlah
                    WHEN 'keluar' THEN -stock_transactions.jumlah
                    WHEN 'opname' THEN stock_transactions.jumlah
                    ELSE 0 END), 0) AS stok_lain
         FROM stock_transactions
         WHERE stock_transactions.product_id = ?
           AND stock_transactions.id <> ?"
    );
    $hitung_stok->bind_param('ii', $data_lama['product_id'], $id);
    $hitung_stok->execute();
    $stok_lain = (int) $hitung_stok->get_result()->fetch_assoc()['stok_lain'];
    if ($stok_lain + $jumlah < 0) {
        header("Location: {$base}edit-barang-masuk&id=$id&pesan=stok_minus");
        exit;
    }

    // 5. Simpan perubahan (hanya kolom jumlah)
    $ubah = $koneksi->prepare(
        "UPDATE stock_transactions SET jumlah = ? WHERE id = ? AND jenis_transaksi = 'masuk'"
    );
    $ubah->bind_param('ii', $jumlah, $id);
    $ubah->execute();

    header("Location: $kembali&pesan=update_ok");
    exit;
}

// ---------- HAPUS ----------
if ($aksi === 'hapus') {
    $id = (int) ($_GET['id'] ?? 0);

    try {
        $hapus = $koneksi->prepare(
            "DELETE FROM stock_transactions WHERE id = ? AND jenis_transaksi = 'masuk'"
        );
        $hapus->bind_param('i', $id);
        $hapus->execute();
    } catch (mysqli_sql_exception $kesalahan) {
        header("Location: $kembali&pesan=gagal_hapus");
        exit;
    }

    header("Location: $kembali&pesan=hapus_ok");
    exit;
}

// Aksi tidak dikenal
header("Location: $kembali");
exit;
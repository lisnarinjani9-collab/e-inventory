<?php
// petugas/function/barang-masuk.php

include_once __DIR__ . '/../database/connection.php';

$koneksi = (new Database())->conn;

$aksi    = $_GET['aksi'] ?? '';
$base    = '/e-inventory/petugas/index.php?page=';
$kembali = $base . 'barang-masuk';

// ---------- TAMBAH ----------
if ($aksi === 'simpan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int) ($_POST['product_id'] ?? 0);
    $po_id      = ($_POST['po_id'] ?? '') !== '' ? (int) $_POST['po_id'] : null;
    $jumlah     = (int) ($_POST['jumlah'] ?? 0);
    $tanggal    = $_POST['tanggal'] ?? '';

    // Cek isiannya masuk akal
    $tanggal_valid = DateTime::createFromFormat('Y-m-d', $tanggal);
    if ($product_id <= 0 || $jumlah < 1 || !$tanggal_valid || $tanggal_valid->format('Y-m-d') !== $tanggal) {
        header("Location: {$base}tambah-barang-masuk&pesan=tidak_valid");
        exit;
    }

    // Cek barang ada di tabel products
    $cek_barang = $koneksi->prepare("SELECT id FROM products WHERE id = ?");
    $cek_barang->bind_param('i', $product_id);
    $cek_barang->execute();
    if ($cek_barang->get_result()->num_rows === 0) {
        header("Location: {$base}tambah-barang-masuk&pesan=barang_tidak_ada");
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
if ($aksi === 'ubah' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Ambil data dari form
    $id         = (int) ($_POST['id'] ?? 0);
    $product_id = (int) ($_POST['product_id'] ?? 0);
    $po_id      = ($_POST['po_id'] ?? '') !== '' ? (int) $_POST['po_id'] : null;
    $jumlah     = (int) ($_POST['jumlah'] ?? 0);
    $tanggal    = $_POST['tanggal'] ?? '';

    // 2. Cek isiannya masuk akal
    $tanggal_valid = DateTime::createFromFormat('Y-m-d', $tanggal);
    if ($id <= 0 || $product_id <= 0 || $jumlah < 1 || !$tanggal_valid || $tanggal_valid->format('Y-m-d') !== $tanggal) {
        header("Location: {$base}edit-barang-masuk&id=$id&pesan=tidak_valid");
        exit;
    }

    // 3. Simpan perubahan (hanya untuk transaksi jenis 'masuk')
    try {
        $ubah = $koneksi->prepare(
            "UPDATE stock_transactions
             SET product_id = ?, po_id = ?, jumlah = ?, tanggal = ?
             WHERE id = ? AND jenis_transaksi = 'masuk'"
        );
        $ubah->bind_param('iiisi', $product_id, $po_id, $jumlah, $tanggal, $id);
        $ubah->execute();
    } catch (mysqli_sql_exception $kesalahan) {
        header("Location: {$base}edit-barang-masuk&id=$id&pesan=po_tidak_ada");
        exit;
    }

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
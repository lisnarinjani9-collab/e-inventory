<?php
// petugas/function/barang-keluar.php

include_once __DIR__ . '/../database/connection.php';

$koneksi = (new Database())->conn;

$aksi    = $_GET['aksi'] ?? '';
$base    = '/e-inventory/petugas/index.php?page=';
$kembali = $base . 'barang-keluar';

// Hitung stok sebuah barang = masuk - keluar + penyesuaian opname.
// $kecuali_transaksi_id dipakai saat ubah data, supaya transaksi yang sedang diubah tidak ikut dihitung.
function hitungStok($koneksi, $product_id, $kecuali_transaksi_id = 0)
{
    $hitung = $koneksi->prepare(
        "SELECT COALESCE(SUM(CASE stock_transactions.jenis_transaksi
                    WHEN 'masuk'   THEN stock_transactions.jumlah
                    WHEN 'keluar'  THEN -stock_transactions.jumlah
                    WHEN 'opname'  THEN stock_transactions.jumlah
                    ELSE 0 END), 0) AS stok_saat_ini
         FROM stock_transactions
         WHERE stock_transactions.product_id = ?
           AND stock_transactions.id <> ?"
    );
    $hitung->bind_param('ii', $product_id, $kecuali_transaksi_id);
    $hitung->execute();
    return (int) $hitung->get_result()->fetch_assoc()['stok_saat_ini'];
}

// ---------- TAMBAH ----------
if ($aksi === 'simpan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int) ($_POST['product_id'] ?? 0);
    $jumlah     = (int) ($_POST['jumlah'] ?? 0);
    $tanggal    = $_POST['tanggal'] ?? '';

    // Cek isiannya masuk akal
    $tanggal_valid = DateTime::createFromFormat('Y-m-d', $tanggal);
    if ($product_id <= 0 || $jumlah < 1 || !$tanggal_valid || $tanggal_valid->format('Y-m-d') !== $tanggal) {
        header("Location: {$base}tambah-barang-keluar&pesan=tidak_valid");
        exit;
    }

    // Cek barang ada di tabel products
    $cek_barang = $koneksi->prepare("SELECT id FROM products WHERE id = ?");
    $cek_barang->bind_param('i', $product_id);
    $cek_barang->execute();
    if ($cek_barang->get_result()->num_rows === 0) {
        header("Location: {$base}tambah-barang-keluar&pesan=barang_tidak_ada");
        exit;
    }

    // Cek stok cukup
    if ($jumlah > hitungStok($koneksi, $product_id)) {
        header("Location: {$base}tambah-barang-keluar&pesan=stok_kurang");
        exit;
    }

    // Simpan (jenis_transaksi otomatis 'keluar', barang keluar tidak punya PO)
    $simpan = $koneksi->prepare(
        "INSERT INTO stock_transactions (product_id, po_id, jenis_transaksi, jumlah, tanggal)
         VALUES (?, NULL, 'keluar', ?, ?)"
    );
    $simpan->bind_param('iis', $product_id, $jumlah, $tanggal);
    $simpan->execute();

    header("Location: $kembali&pesan=tambah_ok");
    exit;
}

// ---------- UBAH ----------
if ($aksi === 'ubah' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Ambil data dari form
    $id         = (int) ($_POST['id'] ?? 0);
    $product_id = (int) ($_POST['product_id'] ?? 0);
    $jumlah     = (int) ($_POST['jumlah'] ?? 0);
    $tanggal    = $_POST['tanggal'] ?? '';

    // 2. Cek isiannya masuk akal
    $tanggal_valid = DateTime::createFromFormat('Y-m-d', $tanggal);
    if ($id <= 0 || $product_id <= 0 || $jumlah < 1 || !$tanggal_valid || $tanggal_valid->format('Y-m-d') !== $tanggal) {
        header("Location: {$base}edit-barang-keluar&id=$id&pesan=tidak_valid");
        exit;
    }

    // 3. Cek stok cukup (transaksi ini sendiri tidak dihitung)
    if ($jumlah > hitungStok($koneksi, $product_id, $id)) {
        header("Location: {$base}edit-barang-keluar&id=$id&pesan=stok_kurang");
        exit;
    }

    // 4. Simpan perubahan (hanya untuk transaksi jenis 'keluar')
    $ubah = $koneksi->prepare(
        "UPDATE stock_transactions
         SET product_id = ?, jumlah = ?, tanggal = ?
         WHERE id = ? AND jenis_transaksi = 'keluar'"
    );
    $ubah->bind_param('iisi', $product_id, $jumlah, $tanggal, $id);
    $ubah->execute();

    header("Location: $kembali&pesan=update_ok");
    exit;
}

// ---------- HAPUS ----------
if ($aksi === 'hapus') {
    $id = (int) ($_GET['id'] ?? 0);

    try {
        $hapus = $koneksi->prepare(
            "DELETE FROM stock_transactions WHERE id = ? AND jenis_transaksi = 'keluar'"
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
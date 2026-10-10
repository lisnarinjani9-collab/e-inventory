<?php
// supplier/function/barang.php
// Supplier menambahkan bahan baku baru beserta harga satuannya (penawaran harga).
// Data disimpan di tabel products, sehingga langsung muncul di tampilan petugas gudang
// (dropdown Barang Masuk / Barang Keluar, daftar Stok & Opname).

include_once __DIR__ . '/../login/cek-login.php';
include_once __DIR__ . '/../database/connection.php';

$koneksi = (new Database())->conn;
$kembali = '/';

// Stok minimal awal untuk bahan baku baru. Petugas gudang bisa mengubahnya nanti di halaman Stok & Opname.
$stok_minimal_awal = 10;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $kembali#harga");
    exit;
}

$nama_barang  = trim($_POST['nama_barang'] ?? '');
$harga_satuan = $_POST['harga_satuan'] ?? '';

// 1. Cek isian masuk akal (batas sesuai kolom di database)
if ($nama_barang === '' || mb_strlen($nama_barang) > 150
    || !is_numeric($harga_satuan) || (float) $harga_satuan <= 0 || (float) $harga_satuan > 9999999999) {
    header("Location: $kembali?pesan=barang_tidak_valid#harga");
    exit;
}
$harga_satuan = (float) $harga_satuan;

// 2. Nama barang tidak boleh sudah ada (huruf besar/kecil dianggap sama)
$cek_barang = $koneksi->prepare("SELECT id FROM products WHERE LOWER(nama_barang) = LOWER(?)");
$cek_barang->bind_param('s', $nama_barang);
$cek_barang->execute();
if ($cek_barang->get_result()->num_rows > 0) {
    header("Location: $kembali?pesan=barang_sudah_ada#harga");
    exit;
}

// 3. Simpan
try {
    $simpan = $koneksi->prepare(
        "INSERT INTO products (nama_barang, stok_minimal, harga_satuan) VALUES (?, ?, ?)"
    );
    $simpan->bind_param('sid', $nama_barang, $stok_minimal_awal, $harga_satuan);
    $simpan->execute();
} catch (Throwable $kesalahan) {
    header("Location: $kembali?pesan=barang_gagal#harga");
    exit;
}

header("Location: $kembali?pesan=barang_ok#harga");
exit;
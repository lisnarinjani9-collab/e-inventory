<?php
// supplier/function/harga.php
// Supplier memperbarui harga satuan bahan baku (penawaran harga).

include_once __DIR__ . '/../login/cek-login.php';
include_once __DIR__ . '/../database/connection.php';

$koneksi = (new Database())->conn;
$kembali = '/';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $kembali#harga");
    exit;
}

$product_id   = (int) ($_POST['product_id'] ?? 0);
$harga_satuan = $_POST['harga_satuan'] ?? '';

// Harga harus angka dan lebih dari 0
if ($product_id <= 0 || !is_numeric($harga_satuan) || (float) $harga_satuan <= 0 || (float) $harga_satuan > 9999999999) {
    header("Location: $kembali?pesan=harga_tidak_valid#harga");
    exit;
}
$harga_satuan = (float) $harga_satuan;

try {
    $ubah = $koneksi->prepare("UPDATE products SET harga_satuan = ? WHERE id = ?");
    $ubah->bind_param('di', $harga_satuan, $product_id);
    $ubah->execute();
} catch (Throwable $kesalahan) {
    header("Location: $kembali?pesan=harga_gagal#harga");
    exit;
}

header("Location: $kembali?pesan=harga_ok#harga");
exit;
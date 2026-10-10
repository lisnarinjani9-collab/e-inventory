<?php
// petugas/function/stok.php
// Mengelola OPNAME STOK. Opname disimpan di stock_transactions dengan jenis_transaksi = 'opname',
// kolom jumlah berisi SELISIH (stok fisik - stok sistem). Selisih bisa positif atau negatif.

include_once __DIR__ . '/../login/cek-login.php';
include_once __DIR__ . '/../database/connection.php';

$koneksi = (new Database())->conn;

$aksi    = $_GET['aksi'] ?? '';
$base    = '/e-inventory/petugas/index.php?page=';
$kembali = $base . 'stok';

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

// ---------- ATUR STOK MINIMAL ----------
// Dipakai petugas untuk mengubah batas stok minimal sebuah barang (tabel products).
if ($aksi === 'minimal' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id   = (int) ($_POST['product_id'] ?? 0);
    $stok_minimal = $_POST['stok_minimal'] ?? '';

    if ($product_id <= 0 || !ctype_digit((string) $stok_minimal) || (int) $stok_minimal > 1000000) {
        header("Location: $kembali&pesan=minimal_tidak_valid");
        exit;
    }
    $stok_minimal = (int) $stok_minimal;

    $ubah_minimal = $koneksi->prepare("UPDATE products SET stok_minimal = ? WHERE id = ?");
    $ubah_minimal->bind_param('ii', $stok_minimal, $product_id);
    $ubah_minimal->execute();

    header("Location: $kembali&pesan=minimal_ok");
    exit;
}

// ---------- TAMBAH (CATAT OPNAME) ----------
if ($aksi === 'simpan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int) ($_POST['product_id'] ?? 0);
    $stok_fisik = (int) ($_POST['stok_fisik'] ?? -1);
    $tanggal    = $_POST['tanggal'] ?? '';

    // Cek isiannya masuk akal
    $tanggal_valid = DateTime::createFromFormat('Y-m-d', $tanggal);
    if ($product_id <= 0 || $stok_fisik < 0 || !$tanggal_valid || $tanggal_valid->format('Y-m-d') !== $tanggal) {
        header("Location: {$base}tambah-opname&pesan=tidak_valid");
        exit;
    }

    // Cek barang ada di tabel products
    $cek_barang = $koneksi->prepare("SELECT id FROM products WHERE id = ?");
    $cek_barang->bind_param('i', $product_id);
    $cek_barang->execute();
    if ($cek_barang->get_result()->num_rows === 0) {
        header("Location: {$base}tambah-opname&pesan=barang_tidak_ada");
        exit;
    }

    // Selisih = stok fisik hasil hitung - stok menurut sistem
    $selisih = $stok_fisik - hitungStok($koneksi, $product_id);

    // Simpan (jenis_transaksi otomatis 'opname', tidak punya PO)
    $simpan = $koneksi->prepare(
        "INSERT INTO stock_transactions (product_id, po_id, jenis_transaksi, jumlah, tanggal)
         VALUES (?, NULL, 'opname', ?, ?)"
    );
    $simpan->bind_param('iis', $product_id, $selisih, $tanggal);
    $simpan->execute();

    header("Location: $kembali&pesan=tambah_ok");
    exit;
}

// ---------- UBAH ----------
if ($aksi === 'ubah' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Ambil data dari form
    $id         = (int) ($_POST['id'] ?? 0);
    $stok_fisik = (int) ($_POST['stok_fisik'] ?? -1);
    $tanggal    = $_POST['tanggal'] ?? '';

    // 2. Cek isiannya masuk akal
    $tanggal_valid = DateTime::createFromFormat('Y-m-d', $tanggal);
    if ($id <= 0 || $stok_fisik < 0 || !$tanggal_valid || $tanggal_valid->format('Y-m-d') !== $tanggal) {
        header("Location: {$base}edit-opname&id=$id&pesan=tidak_valid");
        exit;
    }

    // 3. Ambil barang dari data opname yang diubah
    $ambil_opname = $koneksi->prepare(
        "SELECT product_id FROM stock_transactions WHERE id = ? AND jenis_transaksi = 'opname'"
    );
    $ambil_opname->bind_param('i', $id);
    $ambil_opname->execute();
    $opname = $ambil_opname->get_result()->fetch_assoc();
    if (!$opname) {
        header("Location: $kembali");
        exit;
    }

    // 4. Hitung ulang selisih (opname ini sendiri tidak dihitung sebagai stok sistem)
    $selisih = $stok_fisik - hitungStok($koneksi, (int) $opname['product_id'], $id);

    // 5. Simpan perubahan
    $ubah = $koneksi->prepare(
        "UPDATE stock_transactions
         SET jumlah = ?, tanggal = ?
         WHERE id = ? AND jenis_transaksi = 'opname'"
    );
    $ubah->bind_param('isi', $selisih, $tanggal, $id);
    $ubah->execute();

    header("Location: $kembali&pesan=update_ok");
    exit;
}

// ---------- HAPUS ----------
if ($aksi === 'hapus') {
    $id = (int) ($_GET['id'] ?? 0);

    try {
        $hapus = $koneksi->prepare(
            "DELETE FROM stock_transactions WHERE id = ? AND jenis_transaksi = 'opname'"
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
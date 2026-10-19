<?php
// petugas/function/po-barang-masuk.php
// Aturan Barang Masuk:
// 1. PO wajib dipilih, supaya jelas barang masuk dari PT (supplier) mana.
// 2. Barang yang bisa dipilih hanya barang milik supplier dari PO tersebut.
// 3. Barang yang sudah dicatat masuk untuk PO itu tidak bisa dipilih lagi.
// 4. PO yang semua barang supplier-nya sudah dicatat masuk tidak muncul lagi di pilihan PO.
// (Barang yang sama tetap bisa masuk lagi lewat PO lain, misalnya saat stok perlu ditambah.)

include_once __DIR__ . '/supplier-ditolak.php';

// Ambil data PO yang boleh dipakai untuk barang masuk (status disetujui / selesai).
// Hasilnya null kalau PO tidak ada atau belum disetujui manajer.
function ambilPoUntukBarangMasuk(mysqli $koneksi, int $po_id): ?array
{
    $ambil = $koneksi->prepare(
        "SELECT purchase_orders.id, purchase_orders.supplier_id
         FROM purchase_orders
         WHERE purchase_orders.id = ?
           AND purchase_orders.status_po IN ('disetujui', 'selesai')"
    );
    $ambil->bind_param('i', $po_id);
    $ambil->execute();
    $data_po = $ambil->get_result()->fetch_assoc();

    return $data_po ?: null;
}

// Cek apakah barang ini sudah pernah dicatat masuk untuk PO tersebut.
function barangSudahMasukDiPo(mysqli $koneksi, int $po_id, int $product_id): bool
{
    $cek = $koneksi->prepare(
        "SELECT stock_transactions.id
         FROM stock_transactions
         WHERE stock_transactions.po_id = ?
           AND stock_transactions.product_id = ?
           AND stock_transactions.jenis_transaksi = 'masuk'
         LIMIT 1"
    );
    $cek->bind_param('ii', $po_id, $product_id);
    $cek->execute();

    return $cek->get_result()->num_rows > 0;
}

// Daftar barang milik supplier PO yang BELUM dicatat masuk untuk PO tersebut.
// Isinya array berisi id dan nama_barang.
function daftarBarangBelumMasuk(mysqli $koneksi, int $po_id, int $supplier_id): array
{
    $ambil = $koneksi->prepare(
        "SELECT products.id, products.nama_barang
         FROM products
         WHERE products.supplier_id = ?
           AND NOT (" . syaratSupplierDitolak() . ")
           AND NOT EXISTS (
                SELECT 1 FROM stock_transactions
                WHERE stock_transactions.product_id = products.id
                  AND stock_transactions.po_id = ?
                  AND stock_transactions.jenis_transaksi = 'masuk')
         ORDER BY products.nama_barang ASC"
    );
    $ambil->bind_param('ii', $supplier_id, $po_id);
    $ambil->execute();

    $daftar_barang = [];
    $hasil = $ambil->get_result();
    while ($barang = $hasil->fetch_assoc()) {
        $daftar_barang[] = ['id' => (int) $barang['id'], 'nama' => $barang['nama_barang']];
    }

    return $daftar_barang;
}
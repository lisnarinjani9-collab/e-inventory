<?php
// petugas/function/kebutuhan-bahan-baku.php
// Pengajuan kebutuhan bahan baku = Purchase Order (PO) berstatus 'diajukan'.
// Petugas hanya boleh mengubah / menghapus PO yang masih 'diajukan'.
// Persetujuan (disetujui / ditolak) dilakukan oleh Manajer Operasional.

include_once __DIR__ . '/../database/connection.php';

$koneksi = (new Database())->conn;

$aksi    = $_GET['aksi'] ?? '';
$base    = '/e-inventory/petugas/index.php?page=';
$kembali = $base . 'kebutuhan-bahan-baku';

// ---------- TAMBAH ----------
if ($aksi === 'simpan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = (int) ($_POST['supplier_id'] ?? 0);
    $tanggal_po  = $_POST['tanggal_po'] ?? '';

    // Cek isiannya masuk akal
    $tanggal_valid = DateTime::createFromFormat('Y-m-d', $tanggal_po);
    if ($supplier_id <= 0 || !$tanggal_valid || $tanggal_valid->format('Y-m-d') !== $tanggal_po) {
        header("Location: {$base}tambah-kebutuhan-bahan-baku&pesan=tidak_valid");
        exit;
    }

    // Cek supplier ada di tabel suppliers
    $cek_supplier = $koneksi->prepare("SELECT id FROM suppliers WHERE id = ?");
    $cek_supplier->bind_param('i', $supplier_id);
    $cek_supplier->execute();
    if ($cek_supplier->get_result()->num_rows === 0) {
        header("Location: {$base}tambah-kebutuhan-bahan-baku&pesan=supplier_tidak_ada");
        exit;
    }

    // Simpan (status otomatis 'diajukan')
    $simpan = $koneksi->prepare(
        "INSERT INTO purchase_orders (supplier_id, tanggal_po, status_po) VALUES (?, ?, 'diajukan')"
    );
    $simpan->bind_param('is', $supplier_id, $tanggal_po);
    $simpan->execute();

    header("Location: $kembali&pesan=tambah_ok");
    exit;
}

// ---------- UBAH ----------
if ($aksi === 'ubah' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Ambil data dari form
    $id          = (int) ($_POST['id'] ?? 0);
    $supplier_id = (int) ($_POST['supplier_id'] ?? 0);
    $tanggal_po  = $_POST['tanggal_po'] ?? '';

    // 2. Cek isiannya masuk akal
    $tanggal_valid = DateTime::createFromFormat('Y-m-d', $tanggal_po);
    if ($id <= 0 || $supplier_id <= 0 || !$tanggal_valid || $tanggal_valid->format('Y-m-d') !== $tanggal_po) {
        header("Location: {$base}edit-kebutuhan-bahan-baku&id=$id&pesan=tidak_valid");
        exit;
    }

    // 3. Cek PO masih berstatus 'diajukan'
    $cek_status = $koneksi->prepare("SELECT status_po FROM purchase_orders WHERE id = ?");
    $cek_status->bind_param('i', $id);
    $cek_status->execute();
    $purchase_order = $cek_status->get_result()->fetch_assoc();
    if (!$purchase_order || $purchase_order['status_po'] !== 'diajukan') {
        header("Location: $kembali&pesan=tidak_bisa_diubah");
        exit;
    }

    // 4. Simpan perubahan
    try {
        $ubah = $koneksi->prepare(
            "UPDATE purchase_orders
             SET supplier_id = ?, tanggal_po = ?
             WHERE id = ? AND status_po = 'diajukan'"
        );
        $ubah->bind_param('isi', $supplier_id, $tanggal_po, $id);
        $ubah->execute();
    } catch (mysqli_sql_exception $kesalahan) {
        header("Location: {$base}edit-kebutuhan-bahan-baku&id=$id&pesan=supplier_tidak_ada");
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
            "DELETE FROM purchase_orders WHERE id = ? AND status_po = 'diajukan'"
        );
        $hapus->bind_param('i', $id);
        $hapus->execute();
        $jumlah_terhapus = $hapus->affected_rows;
    } catch (mysqli_sql_exception $kesalahan) {
        header("Location: $kembali&pesan=gagal_hapus");
        exit;
    }

    if ($jumlah_terhapus === 0) {
        header("Location: $kembali&pesan=tidak_bisa_hapus");
        exit;
    }

    header("Location: $kembali&pesan=hapus_ok");
    exit;
}

// Aksi tidak dikenal
header("Location: $kembali");
exit;
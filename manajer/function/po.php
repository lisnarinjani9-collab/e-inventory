<?php
// manajer/function/po.php
// Manajer memproses Purchase Order (PO):
//   setujui : diajukan  -> disetujui
//   tolak   : diajukan  -> ditolak
//   selesai : disetujui -> selesai (hanya kalau gudang sudah mencatat barang masuk untuk PO ini)

include_once __DIR__ . '/../login/cek-login.php';
include_once __DIR__ . '/../database/connection.php';

$koneksi = (new Database())->conn;

$aksi = $_GET['aksi'] ?? '';
$base = '/e-inventory/manajer/index.php?page=';

// Perubahan status hanya boleh lewat form (POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: {$base}verifikasi-po");
    exit;
}

$id   = (int) ($_POST['id'] ?? 0);
$dari = $_POST['dari'] ?? 'daftar';

// Tentukan halaman tujuan setelah proses selesai (kembali ke halaman asal tombol)
function tujuan($base, $dari, $id, $pesan)
{
    if ($dari === 'detail') {
        return "{$base}detail-po&id=$id&pesan=$pesan";
    }
    if ($dari === 'dashboard') {
        return "{$base}dashboard&pesan=$pesan";
    }
    return "{$base}verifikasi-po&pesan=$pesan";
}

if ($id <= 0) {
    header('Location: ' . tujuan($base, $dari, $id, 'tidak_valid'));
    exit;
}

// ---------- SETUJUI ----------
if ($aksi === 'setujui') {
    $setujui = $koneksi->prepare(
        "UPDATE purchase_orders SET status_po = 'disetujui' WHERE id = ? AND status_po = 'diajukan'"
    );
    $setujui->bind_param('i', $id);
    $setujui->execute();

    $pesan = $setujui->affected_rows > 0 ? 'setuju_ok' : 'tidak_bisa_diproses';
    header('Location: ' . tujuan($base, $dari, $id, $pesan));
    exit;
}

// ---------- TOLAK ----------
if ($aksi === 'tolak') {
    $tolak = $koneksi->prepare(
        "UPDATE purchase_orders SET status_po = 'ditolak' WHERE id = ? AND status_po = 'diajukan'"
    );
    $tolak->bind_param('i', $id);
    $tolak->execute();

    $pesan = $tolak->affected_rows > 0 ? 'tolak_ok' : 'tidak_bisa_diproses';
    header('Location: ' . tujuan($base, $dari, $id, $pesan));
    exit;
}

// ---------- SELESAI ----------
if ($aksi === 'selesai') {
    // PO baru boleh diselesaikan kalau gudang sudah mencatat barang masuk untuk PO ini
    $cek_barang = $koneksi->prepare(
        "SELECT COUNT(*) AS total FROM stock_transactions WHERE po_id = ? AND jenis_transaksi = 'masuk'"
    );
    $cek_barang->bind_param('i', $id);
    $cek_barang->execute();
    if ((int) $cek_barang->get_result()->fetch_assoc()['total'] === 0) {
        header('Location: ' . tujuan($base, $dari, $id, 'belum_ada_barang_masuk'));
        exit;
    }

    $selesai = $koneksi->prepare(
        "UPDATE purchase_orders SET status_po = 'selesai' WHERE id = ? AND status_po = 'disetujui'"
    );
    $selesai->bind_param('i', $id);
    $selesai->execute();

    $pesan = $selesai->affected_rows > 0 ? 'selesai_ok' : 'tidak_bisa_diproses';
    header('Location: ' . tujuan($base, $dari, $id, $pesan));
    exit;
}

// Aksi tidak dikenal
header("Location: {$base}verifikasi-po");
exit;
<?php include 'login/cek-login.php'; ?>
<!DOCTYPE html>
<html lang="en">

    <!-- Head -->
    <?php include 'partials/head.php' ?>
    <!-- /Head -->

<body>
  <!-- TOPBAR -->
    <?php include 'components/topbar.php' ?>
    <!-- /TOPBAR -->

    <!-- SIDEBAR -->
    <?php include 'components/sidebar.php' ?>
    <!-- /SIDEBAR -->

  <!-- MAIN CONTENT -->
    <?php
    $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
    switch ($page) {
        // Dashboard
        case 'dashboard':
            include 'pages/dashboard.php';
            break;

        // Verifikasi PO
        case 'verifikasi-po':
            include 'pages/verifikasi-po/verifikasi-po.php';
            break;
        case 'detail-po':
            include 'pages/verifikasi-po/detail.php';
            break;

        // Petugas Gudang
        case 'petugas-gudang':
            include 'pages/petugas-gudang/petugas-gudang.php';
            break;
        case 'tambah-petugas-gudang':
            include 'pages/petugas-gudang/tambah-petugas.php';
            break;

        // Laporan
        case 'laporan-stok':
            include 'pages/laporan-stok/laporan-stok.php';
            break;
        case 'riwayat-transaksi':
            include 'pages/riwayat-transaksi/riwayat-transaksi.php';
            break;

        default:
            include 'pages/dashboard.php';
            break;
    }
    ?>

    <!-- script -->
    <?php include 'partials/script.php' ?>
    <!-- /script -->

</body>

</html>
<?php include 'login/cek-login.php'; ?>
<!DOCTYPE html>
<html lang="en">

    <!-- Head -->
    <?php include 'partials/head.php' ?>
    <!-- /Head -->

<body>
  <!-- <div id="overlay" class="overlay"></div> -->
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

        // Barang Masuk
        case 'barang-masuk':
            include 'pages/barang-masuk/barang-masuk.php';
            break;
        case 'tambah-barang-masuk':
            include 'pages/barang-masuk/tambah.php';
            break;
        case 'edit-barang-masuk':
        case 'update':
            include 'pages/barang-masuk/update.php';
            break;

        // Barang Keluar
        case 'barang-keluar':
            include 'pages/barang-keluar/barang-keluar.php';
            break;
        case 'tambah-barang-keluar':
            include 'pages/barang-keluar/tambah.php';
            break;
        case 'edit-barang-keluar':
            include 'pages/barang-keluar/update.php';
            break;

        // Stok & Opname
        case 'stok':
            include 'pages/stok/stok.php';
            break;
        case 'tambah-opname':
            include 'pages/stok/tambah.php';
            break;
        case 'edit-opname':
            include 'pages/stok/update.php';
            break;

        // Kebutuhan Bahan Baku
        case 'kebutuhan-bahan-baku':
            include 'pages/kebutuhan-bahan-baku/kebutuhan-bahan-baku.php';
            break;
        case 'tambah-kebutuhan-bahan-baku':
            include 'pages/kebutuhan-bahan-baku/tambah.php';
            break;
        case 'edit-kebutuhan-bahan-baku':
            include 'pages/kebutuhan-bahan-baku/update.php';
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
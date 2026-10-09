<?php
// Variabel di bawah disiapkan di function/data-dashboard.php (lewat partials/head.php).
// Baris ??= hanya memberi nilai awal supaya editor tidak menandai "undefined variable";
// kalau variabelnya sudah ada, nilainya tidak berubah.
include_once __DIR__ . '/../function/helper.php';
$nama_perusahaan     ??= '';

/**
 * @var string $nama_perusahaan
 */
?>
<header id="header" class="header d-flex align-items-center fixed-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center">

      <a href="#hero" class="logo d-flex align-items-center me-auto">
        <img src="/supplier/template/assets/img/logo-icon.svg" alt="E-Inventory">
        <h1 class="sitename">E-Inventory</h1>
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="#hero" class="active">Beranda</a></li>
          <li><a href="#ringkasan">Ringkasan</a></li>
          <li><a href="#harga">Penawaran Harga</a></li>
          <li><a href="#status-po">Status PO</a></li>
          <li><a href="#riwayat-po">Riwayat PO</a></li>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

      <span class="header-user d-none d-xl-inline-flex align-items-center">
        <i class="bi bi-building me-2"></i><?= e($nama_perusahaan) ?>
      </span>
      <a class="btn-getstarted" href="/supplier/login/logout.php" onclick="return confirm('Yakin ingin logout?')">Logout</a>

    </div>
  </header>
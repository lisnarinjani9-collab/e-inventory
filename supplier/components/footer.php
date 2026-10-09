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
<footer id="footer" class="footer position-relative light-background">

    <div class="container footer-top">
      <div class="row gy-4">
        <div class="col-lg-6 col-md-12 footer-about">
          <a href="#hero" class="logo d-flex align-items-center">
            <img src="/supplier/template/assets/img/logo-icon.svg" alt="" style="height:32px" class="me-2">
            <span class="sitename">E-Inventory</span>
          </a>
          <div class="footer-contact pt-3">
            <p>Portal supplier untuk sistem Manajemen Logistik &amp; Stok Gudang. Perbarui harga bahan baku dan pantau Purchase Order dengan mudah.</p>
          </div>
        </div>

        <div class="col-lg-3 col-md-6 footer-links">
          <h4>Menu</h4>
          <ul>
            <li><a href="#hero">Beranda</a></li>
            <li><a href="#harga">Penawaran Harga</a></li>
            <li><a href="#status-po">Status PO</a></li>
            <li><a href="#riwayat-po">Riwayat PO</a></li>
          </ul>
        </div>

        <div class="col-lg-3 col-md-6 footer-links">
          <h4>Akun</h4>
          <ul>
            <li><?= e($nama_perusahaan) ?></li>
            <li><a href="/supplier/login/logout.php" onclick="return confirm('Yakin ingin logout?')">Logout</a></li>
          </ul>
        </div>
      </div>
    </div>

    <div class="container copyright text-center mt-4">
      <p>© <span>Copyright</span> <strong class="px-1 sitename">E-Inventory</strong><span>Manajemen Logistik &amp; Stok Gudang</span></p>
    </div>

  </footer>
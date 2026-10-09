<?php
// Menandai menu yang sedang dibuka
$halaman_sekarang = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

function menuAktif($halaman_sekarang, $daftar_halaman)
{
    return in_array($halaman_sekarang, $daftar_halaman) ? 'active' : '';
}
?>
  <aside id="sidebar" class="sidebar">
    <div class="logo-area">
     <a href="index.php?page=dashboard" class="d-inline-flex"><img src="template/src/assets/images/logo-icon.svg" alt="" width="24">
      </a>
    </div>
    <ul class="nav flex-column">
      <li class="px-4 py-2"><small class="nav-text">Main</small></li>
      <li><a class="nav-link <?= menuAktif($halaman_sekarang, ['dashboard']) ?>" href="index.php?page=dashboard"><i class="ti ti-home"></i><span
            class="nav-text">Dashboard</span></a></li>
      <li><a class="nav-link <?= menuAktif($halaman_sekarang, ['barang-masuk', 'tambah-barang-masuk', 'edit-barang-masuk', 'update']) ?>" href="index.php?page=barang-masuk"><i class="ti ti-box-seam"></i><span
            class="nav-text">Barang Masuk</span></a></li>
      <li><a class="nav-link <?= menuAktif($halaman_sekarang, ['barang-keluar', 'tambah-barang-keluar', 'edit-barang-keluar']) ?>" href="index.php?page=barang-keluar"><i class="ti ti-box"></i><span class="nav-text">
        Barang Keluar</span></a></li>
      <li><a class="nav-link <?= menuAktif($halaman_sekarang, ['stok', 'tambah-opname', 'edit-opname']) ?>" href="index.php?page=stok"><i class="ti ti-clipboard-check"></i><span class="nav-text">Stok &amp; Opname</span></a></li>
      <li><a class="nav-link <?= menuAktif($halaman_sekarang, ['kebutuhan-bahan-baku', 'tambah-kebutuhan-bahan-baku', 'edit-kebutuhan-bahan-baku']) ?>" href="index.php?page=kebutuhan-bahan-baku"><i class="ti ti-shopping-cart-plus"></i><span class="nav-text">Kebutuhan Bahan Baku</span></a></li>

      <li class="px-4 pt-4 pb-2"><small class="nav-text">Account</small></li>
      <li><a class="nav-link" href="login/logout.php" onclick="return confirm('Yakin ingin logout?')"><i class="ti ti-logout"></i><span class="nav-text">Logout</span></a>
      </li>
    </ul>

  </aside>
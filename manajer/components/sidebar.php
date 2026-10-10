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
        <span class="logo-text ms-2"> <img src="template/src/assets/images/logo.svg" alt=""></span>
      </a>
    </div>
    <ul class="nav flex-column">
      <li class="px-4 py-2"><small class="nav-text">Main</small></li>
      <li><a class="nav-link <?= menuAktif($halaman_sekarang, ['dashboard']) ?>" href="index.php?page=dashboard"><i class="ti ti-home"></i><span class="nav-text">Dashboard</span></a></li>
      <li><a class="nav-link <?= menuAktif($halaman_sekarang, ['verifikasi-po', 'detail-po']) ?>" href="index.php?page=verifikasi-po"><i class="ti ti-file-check"></i><span class="nav-text">Verifikasi PO</span></a></li>
      <li><a class="nav-link <?= menuAktif($halaman_sekarang, ['petugas-gudang']) ?>" href="index.php?page=petugas-gudang"><i class="ti ti-users"></i><span class="nav-text">Petugas Gudang</span></a></li>

      <li class="px-4 pt-4 pb-2"><small class="nav-text">Laporan</small></li>
      <li><a class="nav-link <?= menuAktif($halaman_sekarang, ['laporan-stok']) ?>" href="index.php?page=laporan-stok"><i class="ti ti-packages"></i><span class="nav-text">Laporan Stok</span></a></li>
      <li><a class="nav-link <?= menuAktif($halaman_sekarang, ['riwayat-transaksi']) ?>" href="index.php?page=riwayat-transaksi"><i class="ti ti-history"></i><span class="nav-text">Riwayat Transaksi</span></a></li>

      <li class="px-4 pt-4 pb-2"><small class="nav-text">Account</small></li>
      <li><a class="nav-link" href="login/logout.php" onclick="return confirm('Yakin ingin logout?')"><i class="ti ti-logout"></i><span class="nav-text">Logout</span></a></li>
    </ul>

  </aside>
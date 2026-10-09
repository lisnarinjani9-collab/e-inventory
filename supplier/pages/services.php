<?php
// Variabel di bawah disiapkan di function/data-dashboard.php (lewat partials/head.php).
// Baris ??= hanya memberi nilai awal supaya editor tidak menandai "undefined variable";
// kalau variabelnya sudah ada, nilainya tidak berubah.
$jumlah_po         ??= ['diajukan' => 0, 'disetujui' => 0, 'ditolak' => 0, 'selesai' => 0];

/**
 * @var array<string,int> $jumlah_po
 */
?>
<section id="status-po" class="pricing section light-background">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Status PO</h2>
        <p>Tahapan Purchase Order dari pengajuan gudang sampai barang diterima</p>
      </div><!-- End Section Title -->

      <div class="container">

        <div class="row gy-4">

          <div class="col-lg-4" data-aos="zoom-in" data-aos-delay="100">
            <div class="pricing-item">
              <h3>PO Diajukan</h3>
              <p class="description">PO yang baru dibuat gudang dan menunggu verifikasi manajer</p>
              <h4><?= $jumlah_po['diajukan'] ?><span> PO</span></h4>
              <a href="/?status=diajukan#riwayat-po" class="cta-btn">Lihat Detail</a>
              <p class="text-center small">Menunggu verifikasi</p>
              <ul>
                <li><i class="bi bi-check"></i> <span>Gudang mengajukan kebutuhan bahan baku</span></li>
                <li><i class="bi bi-check"></i> <span>PO tercatat di sistem</span></li>
                <li><i class="bi bi-check"></i> <span>Supplier melihat status PO</span></li>
                <li class="na"><i class="bi bi-x"></i> <span>Manajer memverifikasi pengajuan</span></li>
                <li class="na"><i class="bi bi-x"></i> <span>Manajer menyetujui PO</span></li>
                <li class="na"><i class="bi bi-x"></i> <span>Supplier mengirim barang</span></li>
                <li class="na"><i class="bi bi-x"></i> <span>Gudang mencatat barang masuk</span></li>
              </ul>
            </div>
          </div><!-- End Pricing Item -->

          <div class="col-lg-4" data-aos="zoom-in" data-aos-delay="200">
            <div class="pricing-item featured">
              <p class="popular">Perlu Dikirim</p>
              <h3>PO Disetujui</h3>
              <p class="description">PO yang sudah disetujui manajer dan siap dipenuhi supplier</p>
              <h4><?= $jumlah_po['disetujui'] ?><span> PO</span></h4>
              <a href="/?status=disetujui#riwayat-po" class="cta-btn">Lihat Detail</a>
              <p class="text-center small">Siap dikirim</p>
              <ul>
                <li><i class="bi bi-check"></i> <span>Gudang mengajukan kebutuhan bahan baku</span></li>
                <li><i class="bi bi-check"></i> <span>PO tercatat di sistem</span></li>
                <li><i class="bi bi-check"></i> <span>Supplier melihat status PO</span></li>
                <li><i class="bi bi-check"></i> <span>Manajer memverifikasi pengajuan</span></li>
                <li><i class="bi bi-check"></i> <span>Manajer menyetujui PO</span></li>
                <li><i class="bi bi-check"></i> <span>Supplier mengirim barang</span></li>
                <li class="na"><i class="bi bi-x"></i> <span>Gudang mencatat barang masuk</span></li>
              </ul>
            </div>
          </div><!-- End Pricing Item -->

          <div class="col-lg-4" data-aos="zoom-in" data-aos-delay="300">
            <div class="pricing-item">
              <h3>PO Selesai</h3>
              <p class="description">PO yang barangnya sudah diterima dan dicatat oleh gudang</p>
              <h4><?= $jumlah_po['selesai'] ?><span> PO</span></h4>
              <a href="/?status=selesai#riwayat-po" class="cta-btn">Lihat Detail</a>
              <p class="text-center small">Proses selesai</p>
              <ul>
                <li><i class="bi bi-check"></i> <span>Gudang mengajukan kebutuhan bahan baku</span></li>
                <li><i class="bi bi-check"></i> <span>PO tercatat di sistem</span></li>
                <li><i class="bi bi-check"></i> <span>Supplier melihat status PO</span></li>
                <li><i class="bi bi-check"></i> <span>Manajer memverifikasi pengajuan</span></li>
                <li><i class="bi bi-check"></i> <span>Manajer menyetujui PO</span></li>
                <li><i class="bi bi-check"></i> <span>Supplier mengirim barang</span></li>
                <li><i class="bi bi-check"></i> <span>Gudang mencatat barang masuk</span></li>
              </ul>
            </div>
          </div><!-- End Pricing Item -->

        </div>

      </div>

    </section>
<?php
// Variabel di bawah disiapkan di function/data-dashboard.php (lewat partials/head.php).
// Baris ??= hanya memberi nilai awal supaya editor tidak menandai "undefined variable";
// kalau variabelnya sudah ada, nilainya tidak berubah.
$supplier_id         ??= null;
$nama_perusahaan     ??= '';
$jumlah_po         ??= ['diajukan' => 0, 'disetujui' => 0, 'ditolak' => 0, 'selesai' => 0];

/**
 * @var int|null $supplier_id
 * @var string $nama_perusahaan
 * @var array<string,int> $jumlah_po
 */
?>
<section id="hero" class="hero hero-dashboard section">
      <div class="hero-bg"></div>
      <div class="container">

        <?php if (!$supplier_id): ?>
          <div class="alert alert-warning small" role="alert">
            Akun Anda belum terhubung dengan data perusahaan supplier, sehingga PO belum bisa ditampilkan.
            Hubungi petugas gudang atau manajer operasional.
          </div>
        <?php endif; ?>

        <div class="row align-items-center gy-4">
          <div class="col-lg-7" data-aos="fade-up">
            <span class="hero-badge"><i class="bi bi-shop me-1"></i> Portal Supplier</span>
            <h1>Halo, <span><?= e($nama_perusahaan) ?></span></h1>
            <p>Tambahkan bahan baku, perbarui harga yang Anda tawarkan, dan pantau Purchase Order dari gudang dalam satu tempat.</p>
            <div class="d-flex flex-wrap gap-2">
              <a href="#harga" class="btn-get-started">Tambah / Ubah Harga</a>
              <a href="#status-po" class="btn-outline-accent ms-0">Lihat Status PO</a>
            </div>
          </div>

          <div class="col-lg-5" data-aos="fade-up" data-aos-delay="100">
            <div class="hero-stat">
              <div class="d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                <div>
                  <div class="stat-number"><?= $jumlah_po['diajukan'] ?></div>
                  <div class="text-secondary">PO menunggu verifikasi</div>
                </div>
              </div>
              <hr>
              <div class="d-flex justify-content-between small">
                <span><i class="bi bi-check2-circle text-primary me-1"></i> <?= $jumlah_po['disetujui'] ?> disetujui</span>
                <span><i class="bi bi-box-seam text-success me-1"></i> <?= $jumlah_po['selesai'] ?> selesai</span>
                <span><i class="bi bi-x-circle text-danger me-1"></i> <?= $jumlah_po['ditolak'] ?> ditolak</span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>
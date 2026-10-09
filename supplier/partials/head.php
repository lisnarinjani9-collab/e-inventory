<?php
// supplier/partials/head.php
// File ini di-include paling awal oleh index.php (di luar folder supplier),
// jadi pengecekan login dan pengambilan data halaman utama ditaruh di sini.
include_once __DIR__ . '/../login/cek-login.php';
include_once __DIR__ . '/../function/data-dashboard.php';

include __DIR__ . '/head-meta.php';
<?php
// supplier/function/helper.php
// Fungsi bantu yang dipakai bersama oleh halaman dan file function.

// Escape teks sebelum ditampilkan di HTML
function e($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

// Format tanggal Indonesia, contoh: 09 Okt 2026
function tanggalIndo($tanggal): string
{
    $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $waktu = strtotime((string) $tanggal);
    if (!$waktu) {
        return '-';
    }
    return date('d', $waktu) . ' ' . $bulan[(int) date('n', $waktu) - 1] . ' ' . date('Y', $waktu);
}

// Format rupiah, contoh: Rp 15.000
function rupiah($angka): string
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}
<?php
// supplier/function/proses-login.php
// Menangani aksi: masuk (login) dan daftar (register) untuk supplier.

include_once __DIR__ . '/../database/connection.php';

session_start();

$koneksi = (new Database())->conn;

$aksi           = $_GET['aksi'] ?? '';
$halaman_login  = '/supplier/login/login.php';
$halaman_daftar = '/supplier/login/register.php';

// ---------- MASUK ----------
if ($aksi === 'masuk' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 1. Cek form tidak kosong
    if ($email === '' || $password === '') {
        header("Location: $halaman_login?pesan=kosong");
        exit;
    }

    // 2. Cari pengguna berdasarkan email
    $cari_pengguna = $koneksi->prepare(
        "SELECT id, nama, email, password, role FROM users WHERE email = ?"
    );
    $cari_pengguna->bind_param('s', $email);
    $cari_pengguna->execute();
    $pengguna = $cari_pengguna->get_result()->fetch_assoc();

    // 3. Cek email dan password (pesan sengaja dibuat sama supaya tidak membocorkan email mana yang terdaftar)
    if (!$pengguna || !password_verify($password, $pengguna['password'])) {
        header("Location: $halaman_login?pesan=salah");
        exit;
    }

    // 4. Hanya role 'supplier' yang boleh masuk ke tampilan supplier
    if ($pengguna['role'] !== 'supplier') {
        header("Location: $halaman_login?pesan=bukan_supplier");
        exit;
    }

    // 5. Cari data perusahaan supplier (tabel suppliers terhubung lewat user_id)
    $cari_supplier = $koneksi->prepare(
        "SELECT id, nama_perusahaan FROM suppliers WHERE user_id = ?"
    );
    $cari_supplier->bind_param('i', $pengguna['id']);
    $cari_supplier->execute();
    $data_supplier = $cari_supplier->get_result()->fetch_assoc();

    // 6. Akun supplier lama yang belum punya data perusahaan dibuatkan otomatis
    //    (nama perusahaan = nama akun, telepon diisi '-' karena kolom wajib diisi)
    if (!$data_supplier) {
        try {
            $telepon_kosong = '-';
            $buat_supplier  = $koneksi->prepare(
                "INSERT INTO suppliers (user_id, nama_perusahaan, telepon) VALUES (?, ?, ?)"
            );
            $buat_supplier->bind_param('iss', $pengguna['id'], $pengguna['nama'], $telepon_kosong);
            $buat_supplier->execute();
            $data_supplier = [
                'id'              => $koneksi->insert_id,
                'nama_perusahaan' => $pengguna['nama'],
            ];
        } catch (Throwable $kesalahan) {
            $data_supplier = null;
        }
    }

    // 7. Simpan data supplier ke session
    session_regenerate_id(true);
    $_SESSION['user_id']         = (int) $pengguna['id'];
    $_SESSION['nama']            = $pengguna['nama'];
    $_SESSION['email']           = $pengguna['email'];
    $_SESSION['role']            = $pengguna['role'];
    $_SESSION['supplier_id']     = $data_supplier ? (int) $data_supplier['id'] : null;
    $_SESSION['nama_perusahaan'] = $data_supplier ? $data_supplier['nama_perusahaan'] : $pengguna['nama'];

    header('Location: /');
    exit;
}

// ---------- DAFTAR ----------
if ($aksi === 'daftar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_perusahaan = trim($_POST['nama_perusahaan'] ?? '');
    $nama            = trim($_POST['nama'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $telepon         = trim($_POST['telepon'] ?? '');
    $password        = $_POST['password'] ?? '';
    $konfirmasi      = $_POST['konfirmasi'] ?? '';

    // Isian lama disimpan sebentar supaya form tidak kosong lagi kalau ada yang salah
    $_SESSION['isian_lama'] = [
        'nama_perusahaan' => $nama_perusahaan,
        'nama'            => $nama,
        'email'           => $email,
        'telepon'         => $telepon,
    ];

    // 1. Validasi isian
    if ($nama_perusahaan === '' || $nama === '' || $email === '' || $telepon === '' || $password === '') {
        header("Location: $halaman_daftar?pesan=kosong");
        exit;
    }
    // Batas panjang sesuai kolom di database
    if (mb_strlen($nama_perusahaan) > 150 || mb_strlen($nama) > 100 || mb_strlen($email) > 100) {
        header("Location: $halaman_daftar?pesan=terlalu_panjang");
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: $halaman_daftar?pesan=email_tidak_valid");
        exit;
    }
    if (!preg_match('/^[0-9+\-\s]{8,20}$/', $telepon)) {
        header("Location: $halaman_daftar?pesan=telepon_tidak_valid");
        exit;
    }
    if (strlen($password) < 6) {
        header("Location: $halaman_daftar?pesan=password_pendek");
        exit;
    }
    if ($password !== $konfirmasi) {
        header("Location: $halaman_daftar?pesan=password_beda");
        exit;
    }

    // 2. Email tidak boleh sudah terdaftar
    $cek_email = $koneksi->prepare("SELECT id FROM users WHERE email = ?");
    $cek_email->bind_param('s', $email);
    $cek_email->execute();
    if ($cek_email->get_result()->num_rows > 0) {
        header("Location: $halaman_daftar?pesan=email_dipakai");
        exit;
    }

    // 3. Simpan akun (users) dan data perusahaan (suppliers) sekaligus
    try {
        $koneksi->begin_transaction();

        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $role          = 'supplier';
        $simpan_user   = $koneksi->prepare(
            "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)"
        );
        $simpan_user->bind_param('ssss', $nama, $email, $password_hash, $role);
        $simpan_user->execute();
        $user_id = $koneksi->insert_id;

        $simpan_supplier = $koneksi->prepare(
            "INSERT INTO suppliers (user_id, nama_perusahaan, telepon) VALUES (?, ?, ?)"
        );
        $simpan_supplier->bind_param('iss', $user_id, $nama_perusahaan, $telepon);
        $simpan_supplier->execute();

        $koneksi->commit();
    } catch (Throwable $kesalahan) {
        $koneksi->rollback();
        header("Location: $halaman_daftar?pesan=gagal");
        exit;
    }

    unset($_SESSION['isian_lama']);
    header("Location: $halaman_login?pesan=daftar_ok");
    exit;
}

// Aksi tidak dikenal
header("Location: $halaman_login");
exit;
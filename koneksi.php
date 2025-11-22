<?php
define('DB_HOST', 'db.fr-pari1.bengt.wasmernet.com:10272');
define('DB_USER', '15b3dbbe739e800064c13be1300b');
define('DB_PASS', 'PASSWORD_BARU_KAMU');  // masukkan password BARU, bukan yang kamu kirim tadi
define('DB_NAME', 'db6zhJ8SD9drV28SFgbfwPB2');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Set charset ke utf8mb4
mysqli_set_charset($conn, "utf8mb4");

// Timezone setting
date_default_timezone_set('Asia/Jakarta');

/* ============================================================
   HELPER FUNCTIONS
   ============================================================ */

/**
 * Sanitasi input untuk keamanan
 */
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

/**
 * Cek login dan role
 */
function check_login($role = null) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        session_destroy();
        header("Location: ../index.php");
        exit();
    }

    if ($role !== null && $_SESSION['role'] != $role) {
        session_destroy();
        header("Location: ../index.php");
        exit();
    }
}

/**
 * Format Rupiah
 */
function format_rupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

/**
 * Format tanggal Indonesia
 */
function format_tanggal($tanggal) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $pecah = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $pecah[2] . ' ' . $bulan[(int)$pecah[1]] . ' ' . $pecah[0];
}

/**
 * Format tanggal dengan waktu
 */
function format_tanggal_waktu($tanggal) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $timestamp = strtotime($tanggal);
    $pecah = explode('-', date('Y-m-d', $timestamp));
    $waktu = date('H:i', $timestamp);

    return $pecah[2] . ' ' . $bulan[(int)$pecah[1]] . ' ' . $pecah[0] . ' - ' . $waktu;
}

/* ============================================================
   USER FUNCTIONS
   ============================================================ */

/** Ambil data user */
function get_user_data($user_id) {
    global $conn;
    if (!is_numeric($user_id)) return false;

    $user_id = clean_input($user_id);
    $query = "SELECT * FROM users WHERE id = '$user_id'";
    $result = mysqli_query($conn, $query);

    return ($result && mysqli_num_rows($result) > 0)
        ? mysqli_fetch_assoc($result)
        : false;
}

/** Cek username */
function is_username_exists($username) {
    global $conn;

    $username = clean_input($username);
    $query = "SELECT id FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);

    return ($result && mysqli_num_rows($result) > 0);
}

/* ============================================================
   TABUNGAN FUNCTIONS
   ============================================================ */

function get_saldo_warga($warga_id) {
    global $conn;

    if (!is_numeric($warga_id)) return 0;

    $warga_id = clean_input($warga_id);
    $result = mysqli_query($conn, "SELECT saldo FROM tabungan WHERE warga_id='$warga_id'");

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result)['saldo'];
    }

    mysqli_query($conn, "INSERT INTO tabungan (warga_id, saldo) VALUES ('$warga_id', 0)");
    return 0;
}

function update_saldo_warga($warga_id, $jumlah) {
    global $conn;

    if (!is_numeric($warga_id)) return false;

    $warga_id = clean_input($warga_id);
    $jumlah = clean_input($jumlah);

    $check = mysqli_query($conn, "SELECT id FROM tabungan WHERE warga_id='$warga_id'");

    if (mysqli_num_rows($check) > 0) {
        return mysqli_query($conn,
            "UPDATE tabungan SET saldo = saldo + $jumlah WHERE warga_id='$warga_id'"
        );
    }

    return mysqli_query($conn,
        "INSERT INTO tabungan (warga_id, saldo) VALUES ('$warga_id', '$jumlah')"
    );
}

function kurangi_saldo_warga($warga_id, $jumlah) {
    global $conn;

    if (!is_numeric($warga_id)) return false;

    $saldo_sekarang = get_saldo_warga($warga_id);
    if ($saldo_sekarang < $jumlah) return false;

    return mysqli_query($conn,
        "UPDATE tabungan SET saldo = saldo - $jumlah WHERE warga_id='$warga_id'"
    );
}

/* ============================================================
   VALIDATION FUNCTIONS
   ============================================================ */

function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function is_valid_phone($phone) {
    return preg_match('/^08[0-9]{8,11}$/', $phone);
}

function is_valid_password($password) {
    return strlen($password) >= 6;
}

?>

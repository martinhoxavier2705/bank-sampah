<?php
/**
 * Helper Functions
 * File ini berisi semua fungsi-fungsi helper yang digunakan di seluruh sistem
 */

// ============================================
// SECURITY FUNCTIONS
// ============================================

/**
 * Fungsi untuk membersihkan input (mencegah SQL Injection)
 */
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

/**
 * Fungsi untuk cek session login
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

// ============================================
// FORMATTING FUNCTIONS
// ============================================

/**
 * Fungsi untuk format rupiah
 */
function format_rupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

/**
 * Fungsi untuk format tanggal Indonesia
 */
function format_tanggal($tanggal) {
    $bulan = array(
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );
    
    $pecah = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $pecah[2] . ' ' . $bulan[(int)$pecah[1]] . ' ' . $pecah[0];
}

/**
 * Fungsi untuk format tanggal waktu Indonesia
 */
function format_tanggal_waktu($tanggal) {
    $bulan = array(
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );
    
    $timestamp = strtotime($tanggal);
    $pecah = explode('-', date('Y-m-d', $timestamp));
    $waktu = date('H:i', $timestamp);
    
    return $pecah[2] . ' ' . $bulan[(int)$pecah[1]] . ' ' . $pecah[0] . ' - ' . $waktu;
}

// ============================================
// USER FUNCTIONS
// ============================================

/**
 * Fungsi untuk mendapatkan data user berdasarkan ID
 */
function get_user_data($user_id) {
    global $conn;
    
    if (empty($user_id) || !is_numeric($user_id)) {
        return false;
    }
    
    $user_id = clean_input($user_id);
    $query = "SELECT * FROM users WHERE id = '$user_id'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return false;
}

/**
 * Fungsi untuk cek apakah username sudah ada
 */
function is_username_exists($username) {
    global $conn;
    
    $username = clean_input($username);
    $query = "SELECT id FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);
    
    return ($result && mysqli_num_rows($result) > 0);
}

// ============================================
// TABUNGAN FUNCTIONS
// ============================================

/**
 * Fungsi untuk mendapatkan saldo warga
 */
function get_saldo_warga($warga_id) {
    global $conn;
    
    // Validasi warga_id
    if (empty($warga_id) || !is_numeric($warga_id)) {
        return 0;
    }
    
    $warga_id = clean_input($warga_id);
    $query = "SELECT saldo FROM tabungan WHERE warga_id = '$warga_id'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        return $data['saldo'];
    }
    
    // Jika belum ada record tabungan, buat record baru dengan saldo 0
    $insert_query = "INSERT INTO tabungan (warga_id, saldo) VALUES ('$warga_id', 0)";
    if (mysqli_query($conn, $insert_query)) {
        return 0;
    }
    
    return 0;
}

/**
 * Fungsi untuk update saldo warga
 */
function update_saldo_warga($warga_id, $jumlah) {
    global $conn;
    
    if (empty($warga_id) || !is_numeric($warga_id)) {
        return false;
    }
    
    $warga_id = clean_input($warga_id);
    $jumlah = clean_input($jumlah);
    
    // Cek apakah sudah ada record tabungan
    $check = mysqli_query($conn, "SELECT id FROM tabungan WHERE warga_id = '$warga_id'");
    
    if (mysqli_num_rows($check) > 0) {
        // Update saldo yang sudah ada
        $query = "UPDATE tabungan SET saldo = saldo + $jumlah WHERE warga_id = '$warga_id'";
    } else {
        // Insert saldo baru
        $query = "INSERT INTO tabungan (warga_id, saldo) VALUES ('$warga_id', '$jumlah')";
    }
    
    return mysqli_query($conn, $query);
}

/**
 * Fungsi untuk kurangi saldo warga (untuk penarikan)
 */
function kurangi_saldo_warga($warga_id, $jumlah) {
    global $conn;
    
    if (empty($warga_id) || !is_numeric($warga_id)) {
        return false;
    }
    
    $warga_id = clean_input($warga_id);
    $jumlah = clean_input($jumlah);
    
    // Cek saldo cukup atau tidak
    $saldo_saat_ini = get_saldo_warga($warga_id);
    
    if ($saldo_saat_ini < $jumlah) {
        return false; // Saldo tidak cukup
    }
    
    $query = "UPDATE tabungan SET saldo = saldo - $jumlah WHERE warga_id = '$warga_id'";
    return mysqli_query($conn, $query);
}

// ============================================
// STATISTIK FUNCTIONS
// ============================================

/**
 * Fungsi untuk mendapatkan total penjemputan warga
 */
function get_total_penjemputan($warga_id) {
    global $conn;
    
    if (empty($warga_id) || !is_numeric($warga_id)) {
        return 0;
    }
    
    $warga_id = clean_input($warga_id);
    $query = "SELECT COUNT(*) as total FROM penjemputan WHERE warga_id = '$warga_id'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        return $data['total'];
    }
    
    return 0;
}

/**
 * Fungsi untuk mendapatkan total berat sampah warga
 */
function get_total_berat_sampah($warga_id) {
    global $conn;
    
    if (empty($warga_id) || !is_numeric($warga_id)) {
        return 0;
    }
    
    $warga_id = clean_input($warga_id);
    $query = "SELECT COALESCE(SUM(berat_kg), 0) as total FROM transaksi_sampah WHERE warga_id = '$warga_id'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        return $data['total'];
    }
    
    return 0;
}

// ============================================
// NOTIFICATION FUNCTIONS
// ============================================

/**
 * Fungsi untuk membuat notifikasi
 */
function create_notification($user_id, $message, $type = 'info') {
    // Bisa dikembangkan untuk sistem notifikasi
    // Saat ini hanya return true
    return true;
}

/**
 * Fungsi untuk log aktivitas
 */
function log_activity($user_id, $activity, $details = '') {
    global $conn;
    
    if (empty($user_id) || !is_numeric($user_id)) {
        return false;
    }
    
    $user_id = clean_input($user_id);
    $activity = clean_input($activity);
    $details = clean_input($details);
    
    // Bisa ditambahkan tabel log_aktivitas untuk tracking
    // $query = "INSERT INTO log_aktivitas (user_id, activity, details) VALUES ('$user_id', '$activity', '$details')";
    // return mysqli_query($conn, $query);
    
    return true;
}

// ============================================
// VALIDATION FUNCTIONS
// ============================================

/**
 * Fungsi untuk validasi email
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Fungsi untuk validasi nomor HP
 */
function is_valid_phone($phone) {
    // Format: 08xxxxxxxxxx (minimal 10 digit, maksimal 13 digit)
    return preg_match('/^08[0-9]{8,11}$/', $phone);
}

/**
 * Fungsi untuk validasi password
 */
function is_valid_password($password) {
    // Minimal 6 karakter
    return (strlen($password) >= 6);
}
?>
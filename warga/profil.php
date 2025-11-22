<?php
session_start();
require_once '../koneksi.php';

check_login('warga');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['nama'])) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

$warga_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

$success = '';
$error = '';

// Handle Update Profil
if (isset($_POST['update_profil'])) {
    $nama_update = clean_input($_POST['nama']);
    $email = clean_input($_POST['email']);
    $no_hp = clean_input($_POST['no_hp']);
    $alamat = clean_input($_POST['alamat']);
    
    $query = "UPDATE users SET nama='$nama_update', email='$email', no_hp='$no_hp', alamat='$alamat' 
              WHERE id='$warga_id'";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['nama'] = $nama_update;
        $success = "Profil berhasil diupdate!";
    } else {
        $error = "Gagal mengupdate profil!";
    }
}

// Handle Ganti Password
if (isset($_POST['ganti_password'])) {
    $password_lama = clean_input($_POST['password_lama']);
    $password_baru = clean_input($_POST['password_baru']);
    $konfirmasi_password = clean_input($_POST['konfirmasi_password']);
    
    // Cek password lama
    $check = mysqli_query($conn, "SELECT password FROM users WHERE id='$warga_id'");
    $user = mysqli_fetch_assoc($check);
    
    if ($user['password'] != $password_lama) {
        $error = "Password lama tidak sesuai!";
    } elseif ($password_baru != $konfirmasi_password) {
        $error = "Konfirmasi password tidak cocok!";
    } elseif (strlen($password_baru) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        $query = "UPDATE users SET password='$password_baru' WHERE id='$warga_id'";
        if (mysqli_query($conn, $query)) {
            $success = "Password berhasil diubah!";
        } else {
            $error = "Gagal mengubah password!";
        }
    }
}

// Ambil data profil
$query_profil = "SELECT * FROM users WHERE id = '$warga_id'";
$result_profil = mysqli_query($conn, $query_profil);
$profil = mysqli_fetch_assoc($result_profil);

// Ambil saldo
$saldo = get_saldo_warga($warga_id);

// Statistik
$query_stats = "SELECT 
    COUNT(DISTINCT p.id) as total_penjemputan,
    COUNT(DISTINCT ts.id) as total_transaksi,
    COALESCE(SUM(ts.berat_kg), 0) as total_berat,
    COALESCE(SUM(ts.total_uang), 0) as total_pendapatan
FROM users u
LEFT JOIN penjemputan p ON u.id = p.warga_id
LEFT JOIN transaksi_sampah ts ON u.id = ts.warga_id
WHERE u.id = '$warga_id'";
$result_stats = mysqli_query($conn, $query_stats);
$stats = mysqli_fetch_assoc($result_stats);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Warga</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .navbar { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-brand { font-size: 1.5em; font-weight: bold; }
        .navbar-menu { display: flex; gap: 20px; align-items: center; }
        .navbar-menu a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: all 0.3s; }
        .navbar-menu a:hover { background: rgba(255,255,255,0.2); }
        .container { max-width: 1000px; margin: 0 auto; padding: 30px; }
        .profile-header { text-align: center; padding: 40px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border-radius: 20px; margin-bottom: 30px; }
        .profile-avatar { width: 120px; height: 120px; background: white; color: #f093fb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 4em; margin: 0 auto 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .profile-info h2 { margin-bottom: 5px; font-size: 2em; }
        .profile-info p { opacity: 0.9; font-size: 1.1em; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 20px; }
        .stat-mini { background: rgba(255,255,255,0.2); padding: 15px; border-radius: 10px; text-align: center; }
        .stat-mini-value { font-size: 1.8em; font-weight: bold; }
        .stat-mini-label { font-size: 0.9em; opacity: 0.9; margin-top: 5px; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card h2 { color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f093fb; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-danger { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1em; font-family: inherit; }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #f093fb; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; width: 100%; font-size: 1.1em; }
        .btn-primary:hover { opacity: 0.9; transform: translateY(-2px); }
        .info-card { background: #f8f9fa; padding: 20px; border-radius: 10px; }
        .info-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e0e0e0; }
        .info-item:last-child { border-bottom: none; }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">👤 Warga - Profil</div>
        <div class="navbar-menu">
            <a href="dashboard.php">← Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="profile-header">
            <div class="profile-avatar">👤</div>
            <div class="profile-info">
                <h2><?php echo $profil['nama']; ?></h2>
                <p>Warga Bank Sampah</p>
                <p style="font-size: 0.9em; margin-top: 5px;">@<?php echo $profil['username']; ?></p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-mini">
                    <div class="stat-mini-value"><?php echo format_rupiah($saldo); ?></div>
                    <div class="stat-mini-label">Saldo</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-value"><?php echo $stats['total_penjemputan']; ?></div>
                    <div class="stat-mini-label">Penjemputan</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-value"><?php echo number_format($stats['total_berat'], 1); ?> kg</div>
                    <div class="stat-mini-label">Total Berat</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-value"><?php echo $stats['total_transaksi']; ?></div>
                    <div class="stat-mini-label">Transaksi</div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>✏️ Edit Profil</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" value="<?php echo $profil['username']; ?>" readonly style="background: #f0f0f0;">
                    <small style="color: #666;">Username tidak dapat diubah</small>
                </div>
                
                <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input type="text" name="nama" value="<?php echo $profil['nama']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo $profil['email']; ?>" placeholder="contoh@email.com">
                </div>
                
                <div class="form-group">
                    <label>No. HP / WhatsApp</label>
                    <input type="text" name="no_hp" value="<?php echo $profil['no_hp']; ?>" placeholder="08xx-xxxx-xxxx">
                    <small style="color: #666;">Nomor HP akan dilihat oleh mitra saat penjemputan</small>
                </div>
                
                <div class="form-group">
                    <label>Alamat Lengkap</label>
                    <textarea name="alamat" placeholder="Masukkan alamat lengkap termasuk RT/RW, Kelurahan, Kecamatan"><?php echo $profil['alamat']; ?></textarea>
                    <small style="color: #666;">Alamat akan digunakan sebagai lokasi penjemputan sampah</small>
                </div>
                
                <button type="submit" name="update_profil" class="btn btn-primary">
                    💾 Simpan Perubahan
                </button>
            </form>
        </div>
        
        <div class="card">
            <h2>🔒 Ganti Password</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Password Lama *</label>
                    <input type="password" name="password_lama" required>
                </div>
                
                <div class="form-group">
                    <label>Password Baru *</label>
                    <input type="password" name="password_baru" required minlength="6">
                    <small style="color: #666;">Minimal 6 karakter</small>
                </div>
                
                <div class="form-group">
                    <label>Konfirmasi Password Baru *</label>
                    <input type="password" name="konfirmasi_password" required minlength="6">
                </div>
                
                <button type="submit" name="ganti_password" class="btn btn-primary">
                    🔐 Ganti Password
                </button>
            </form>
        </div>
        
        <div class="card">
            <h2>ℹ️ Informasi Akun</h2>
            <div class="info-card">
                <div class="info-item">
                    <span><strong>Bergabung sejak:</strong></span>
                    <span><?php echo format_tanggal($profil['created_at']); ?></span>
                </div>
                <div class="info-item">
                    <span><strong>Role:</strong></span>
                    <span>Warga</span>
                </div>
                <div class="info-item">
                    <span><strong>Status:</strong></span>
                    <span style="color: #28a745; font-weight: 600;">● Aktif</span>
                </div>
                <div class="info-item">
                    <span><strong>Total Pendapatan:</strong></span>
                    <span style="color: #28a745; font-weight: 600;"><?php echo format_rupiah($stats['total_pendapatan']); ?></span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
session_start();
require_once '../koneksi.php';

check_login('mitra');

$mitra_id = $_SESSION['user_id'];
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
              WHERE id='$mitra_id'";
    
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
    $check = mysqli_query($conn, "SELECT password FROM users WHERE id='$mitra_id'");
    $user = mysqli_fetch_assoc($check);
    
    if ($user['password'] != $password_lama) {
        $error = "Password lama tidak sesuai!";
    } elseif ($password_baru != $konfirmasi_password) {
        $error = "Konfirmasi password tidak cocok!";
    } elseif (strlen($password_baru) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        $query = "UPDATE users SET password='$password_baru' WHERE id='$mitra_id'";
        if (mysqli_query($conn, $query)) {
            $success = "Password berhasil diubah!";
        } else {
            $error = "Gagal mengubah password!";
        }
    }
}

// Ambil data profil
$query_profil = "SELECT * FROM users WHERE id = '$mitra_id'";
$result_profil = mysqli_query($conn, $query_profil);
$profil = mysqli_fetch_assoc($result_profil);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Mitra</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .navbar { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-brand { font-size: 1.5em; font-weight: bold; }
        .navbar-menu { display: flex; gap: 20px; align-items: center; }
        .navbar-menu a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: all 0.3s; }
        .navbar-menu a:hover { background: rgba(255,255,255,0.2); }
        .container { max-width: 900px; margin: 0 auto; padding: 30px; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card h2 { color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #11998e; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-danger { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1em; font-family: inherit; }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #11998e; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .btn-primary { background: #11998e; color: white; width: 100%; font-size: 1.1em; }
        .btn-primary:hover { background: #0e7c73; }
        .profile-header { text-align: center; padding: 30px; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; border-radius: 15px; margin-bottom: 30px; }
        .profile-avatar { width: 100px; height: 100px; background: white; color: #11998e; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 3em; margin: 0 auto 15px; }
        .profile-info h2 { margin-bottom: 5px; }
        .profile-info p { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">👤 Mitra - Profil</div>
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
                <p>Mitra Pengumpul Sampah</p>
                <p style="font-size: 0.9em; margin-top: 5px;">@<?php echo $profil['username']; ?></p>
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
                    <label>Nama Lengkap / Nama Usaha *</label>
                    <input type="text" name="nama" value="<?php echo $profil['nama']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo $profil['email']; ?>">
                </div>
                
                <div class="form-group">
                    <label>No. HP / WhatsApp</label>
                    <input type="text" name="no_hp" value="<?php echo $profil['no_hp']; ?>" placeholder="08xx-xxxx-xxxx">
                </div>
                
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="alamat"><?php echo $profil['alamat']; ?></textarea>
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
        
        <div class="card" style="background: #f8f9fa;">
            <h2 style="border-color: #e0e0e0;">ℹ️ Informasi Akun</h2>
            <p style="color: #666; line-height: 1.8;">
                <strong>Bergabung sejak:</strong> <?php echo format_tanggal($profil['created_at']); ?><br>
                <strong>Role:</strong> Mitra Pengumpul Sampah<br>
                <strong>Status:</strong> <span style="color: #28a745; font-weight: 600;">● Aktif</span>
            </p>
        </div>
    </div>
</body>
</html>
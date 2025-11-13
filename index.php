<?php
session_start();

// Redirect jika sudah login berdasarkan role
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: admin/dashboard.php");
            break;
        case 'mitra':
            header("Location: mitra/dashboard.php");
            break;
        case 'warga':
            header("Location: warga/dashboard.php");
            break;
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Sampah - Sistem Manajemen</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 900px;
            width: 100%;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .roles {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .role-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            cursor: pointer;
        }
        
        .role-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-color: #667eea;
        }
        
        .role-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }
        
        .role-card h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.5em;
        }
        
        .role-card p {
            color: #666;
            margin-bottom: 20px;
            font-size: 0.95em;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 0.9em;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2em;
            }
            
            .roles {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🌱 Bank Sampah</h1>
            <p>Sistem Manajemen Tabungan Sampah Digital</p>
        </div>
        
        <div class="content">
            <h2 style="text-align: center; color: #333; margin-bottom: 30px;">Pilih Role Anda</h2>
            
            <div class="roles">
                <div class="role-card">
                    <div class="role-icon">👥</div>
                    <h3>Warga</h3>
                    <p>Kelola tabungan sampah, jadwal penjemputan, dan penarikan saldo Anda</p>
                    <a href="warga/login.php" class="btn">Login Warga</a>
                </div>
                
                <div class="role-card">
                    <div class="role-icon">🚛</div>
                    <h3>Mitra</h3>
                    <p>Kelola penjemputan, penimbangan, dan laporan pengumpulan sampah</p>
                    <a href="mitra/login.php" class="btn">Login Mitra</a>
                </div>
                
                <div class="role-card">
                    <div class="role-icon">⚙️</div>
                    <h3>Admin</h3>
                    <p>Kelola sistem, verifikasi transaksi, dan pantau seluruh aktivitas</p>
                    <a href="admin/login.php" class="btn">Login Admin</a>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>Belum punya akun? <a href="registrasi.php" style="color: #667eea; font-weight: 600; text-decoration: none;">Daftar di sini</a></p>
            <p style="margin-top: 10px;">&copy; 2025 Bank Sampah. Sistem Manajemen Lingkungan Digital.</p>
        </div>
    </div>
</body>
</html>
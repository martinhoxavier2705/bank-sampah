<?php
session_start();
require_once '../koneksi.php';

// Cek login
check_login('admin');

$admin_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

$success = '';
$error = '';

// Handle Approve/Reject (untuk development, semua akun langsung aktif)
// Fitur ini bisa dikembangkan dengan menambah kolom 'status' di tabel users

// Ambil user yang baru mendaftar (7 hari terakhir)
$query = "SELECT * FROM users 
          WHERE role IN ('warga', 'mitra') 
          AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
          ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

// Statistik
$query_stats = "SELECT 
    COUNT(*) as total_baru,
    SUM(CASE WHEN role = 'warga' THEN 1 ELSE 0 END) as warga_baru,
    SUM(CASE WHEN role = 'mitra' THEN 1 ELSE 0 END) as mitra_baru
FROM users 
WHERE role IN ('warga', 'mitra') 
AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$result_stats = mysqli_query($conn, $query_stats);
$stats = mysqli_fetch_assoc($result_stats);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Akun - Admin Bank Sampah</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-size: 1.5em;
            font-weight: bold;
        }
        
        .navbar-menu {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .navbar-menu a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .navbar-menu a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid;
        }
        
        .stat-card.total { border-color: #667eea; }
        .stat-card.warga { border-color: #f093fb; }
        .stat-card.mitra { border-color: #11998e; }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #333;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .card h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .user-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
        
        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .user-info h3 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .badge.warga {
            background: #ffe6f0;
            color: #d63384;
        }
        
        .badge.mitra {
            background: #d1f4e0;
            color: #198754;
        }
        
        .user-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }
        
        .detail-item strong {
            color: #333;
        }
        
        .user-actions {
            display: flex;
            gap: 10px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        
        .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.8;
            transform: translateY(-2px);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state .icon {
            font-size: 5em;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .user-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .user-details {
                grid-template-columns: 1fr;
            }
            
            .user-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">✓ Admin - Verifikasi Akun</div>
        <div class="navbar-menu">
            <a href="dashboard.php">← Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-label">Total Pendaftar Baru (7 Hari)</div>
                <div class="stat-value"><?php echo $stats['total_baru']; ?></div>
            </div>
            
            <div class="stat-card warga">
                <div class="stat-label">Warga Baru</div>
                <div class="stat-value"><?php echo $stats['warga_baru']; ?></div>
            </div>
            
            <div class="stat-card mitra">
                <div class="stat-label">Mitra Baru</div>
                <div class="stat-value"><?php echo $stats['mitra_baru']; ?></div>
            </div>
        </div>
        
        <div class="card">
            <h2>📋 Daftar Akun Baru (7 Hari Terakhir)</h2>
            
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="user-card">
                    <div class="user-header">
                        <div class="user-info">
                            <h3><?php echo $row['nama']; ?></h3>
                            <span class="badge <?php echo $row['role']; ?>">
                                <?php echo ucfirst($row['role']); ?>
                            </span>
                        </div>
                        <div>
                            <small style="color: #999;">
                                Daftar: <?php echo format_tanggal_waktu($row['created_at']); ?>
                            </small>
                        </div>
                    </div>
                    
                    <div class="user-details">
                        <div class="detail-item">
                            <span>👤</span>
                            <strong>Username:</strong> <?php echo $row['username']; ?>
                        </div>
                        <div class="detail-item">
                            <span>📧</span>
                            <strong>Email:</strong> <?php echo $row['email'] ?: '-'; ?>
                        </div>
                        <div class="detail-item">
                            <span>📱</span>
                            <strong>No. HP:</strong> <?php echo $row['no_hp'] ?: '-'; ?>
                        </div>
                        <div class="detail-item">
                            <span>📍</span>
                            <strong>Alamat:</strong> <?php echo $row['alamat'] ? substr($row['alamat'], 0, 30) . '...' : '-'; ?>
                        </div>
                    </div>
                    
                    <div class="user-actions">
                        <span class="btn btn-success">✓ Akun Aktif</span>
                        <a href="users.php" class="btn btn-info">👁️ Lihat Detail</a>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="icon">📭</div>
                    <h3>Tidak Ada Pendaftar Baru</h3>
                    <p>Belum ada user yang mendaftar dalam 7 hari terakhir</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <h2 style="color: white; border-color: rgba(255,255,255,0.3);">💡 Informasi</h2>
            <p style="line-height: 1.8;">
                <strong>Catatan:</strong> Saat ini semua akun yang mendaftar langsung aktif dan dapat menggunakan sistem. 
                Halaman ini menampilkan user yang baru mendaftar dalam 7 hari terakhir untuk monitoring.
            </p>
            <p style="margin-top: 15px; line-height: 1.8;">
                Jika Anda ingin menambahkan fitur approval manual, silakan tambahkan kolom <code style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 3px;">status</code> 
                di tabel users dengan nilai: pending, approved, rejected.
            </p>
        </div>
    </div>
</body>
</html>
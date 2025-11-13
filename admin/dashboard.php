<?php
session_start();
require_once '../koneksi.php';

// Cek login
check_login('admin');

$admin_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

// Ambil statistik sistem
$query_users = "SELECT role, COUNT(*) as total FROM users GROUP BY role";
$result_users = mysqli_query($conn, $query_users);
$users = array();
while ($row = mysqli_fetch_assoc($result_users)) {
    $users[$row['role']] = $row['total'];
}

// Total statistik
$query_stats = "SELECT 
    (SELECT COUNT(*) FROM penjemputan) as total_penjemputan,
    (SELECT COUNT(*) FROM penjemputan WHERE status = 'pending') as pending_penjemputan,
    (SELECT COUNT(*) FROM penarikan_saldo WHERE status = 'pending') as pending_penarikan,
    (SELECT COALESCE(SUM(total_uang), 0) FROM transaksi_sampah) as total_transaksi,
    (SELECT COALESCE(SUM(berat_kg), 0) FROM transaksi_sampah) as total_berat,
    (SELECT COALESCE(SUM(saldo), 0) FROM tabungan) as total_saldo";
$result_stats = mysqli_query($conn, $query_stats);
$stats = mysqli_fetch_assoc($result_stats);

// Penarikan saldo pending
$query_penarikan = "SELECT ps.*, u.nama, u.email, u.no_hp, t.saldo
FROM penarikan_saldo ps
JOIN users u ON ps.warga_id = u.id
LEFT JOIN tabungan t ON ps.warga_id = t.warga_id
WHERE ps.status = 'pending'
ORDER BY ps.tanggal DESC
LIMIT 5";
$result_penarikan = mysqli_query($conn, $query_penarikan);

// Transaksi terbaru
$query_transaksi = "SELECT ts.*, u.nama
FROM transaksi_sampah ts
JOIN users u ON ts.warga_id = u.id
ORDER BY ts.tanggal DESC
LIMIT 5";
$result_transaksi = mysqli_query($conn, $query_transaksi);

// Feedback terbaru
$query_feedback = "SELECT f.*, 
    u1.nama as pengirim_nama,
    u2.nama as penerima_nama
FROM feedback f
JOIN users u1 ON f.pengirim_id = u1.id
LEFT JOIN users u2 ON f.penerima_id = u2.id
ORDER BY f.tanggal DESC
LIMIT 5";
$result_feedback = mysqli_query($conn, $query_feedback);

// Aktivitas bulan ini
$query_bulan_ini = "SELECT 
    (SELECT COUNT(*) FROM transaksi_sampah WHERE MONTH(tanggal) = MONTH(CURDATE())) as transaksi_bulan_ini,
    (SELECT COUNT(*) FROM penjemputan WHERE MONTH(created_at) = MONTH(CURDATE())) as penjemputan_bulan_ini,
    (SELECT COALESCE(SUM(jumlah), 0) FROM penarikan_saldo WHERE MONTH(tanggal) = MONTH(CURDATE()) AND status = 'selesai') as penarikan_bulan_ini";
$result_bulan = mysqli_query($conn, $query_bulan_ini);
$bulan_ini = mysqli_fetch_assoc($result_bulan);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Bank Sampah</title>
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
        
        .navbar-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .welcome {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .welcome h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card.warga { border-color: #f093fb; }
        .stat-card.mitra { border-color: #11998e; }
        .stat-card.admin { border-color: #667eea; }
        .stat-card.penjemputan { border-color: #4facfe; }
        .stat-card.transaksi { border-color: #43e97b; }
        .stat-card.saldo { border-color: #fa709a; }
        .stat-card.berat { border-color: #ffa502; }
        .stat-card.pending { border-color: #ff6b6b; }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #333;
        }
        
        .stat-icon {
            float: right;
            font-size: 2.5em;
            opacity: 0.3;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .card h3 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .menu-item {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .menu-item .icon {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
        }
        
        table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .status.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status.verifikasi {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status.selesai {
            background: #d4edda;
            color: #155724;
        }
        
        .btn {
            padding: 6px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.85em;
            transition: all 0.3s;
            display: inline-block;
            margin-right: 5px;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .alert-warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            color: #856404;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .rating {
            color: #ffa502;
        }
        
        @media (max-width: 968px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .menu-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">⚙️ Bank Sampah - Admin</div>
        <div class="navbar-user">
            <span>👤 <?php echo $nama; ?></span>
            <a href="../logout.php" class="btn-logout">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome">
            <h2>Selamat Datang, <?php echo $nama; ?>! 👋</h2>
            <p>Panel kontrol sistem manajemen Bank Sampah</p>
        </div>
        
        <?php if ($stats['pending_penjemputan'] > 0 || $stats['pending_penarikan'] > 0): ?>
        <div class="alert alert-warning">
            <strong>⚠️ Perhatian!</strong>
            <?php if ($stats['pending_penjemputan'] > 0): ?>
                <p>• <?php echo $stats['pending_penjemputan']; ?> penjemputan menunggu ditangani</p>
            <?php endif; ?>
            <?php if ($stats['pending_penarikan'] > 0): ?>
                <p>• <?php echo $stats['pending_penarikan']; ?> permintaan penarikan saldo menunggu verifikasi</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card warga">
                <div class="stat-icon">👥</div>
                <div class="stat-label">Total Warga</div>
                <div class="stat-value"><?php echo isset($users['warga']) ? $users['warga'] : 0; ?></div>
            </div>
            
            <div class="stat-card mitra">
                <div class="stat-icon">🚛</div>
                <div class="stat-label">Total Mitra</div>
                <div class="stat-value"><?php echo isset($users['mitra']) ? $users['mitra'] : 0; ?></div>
            </div>
            
            <div class="stat-card admin">
                <div class="stat-icon">⚙️</div>
                <div class="stat-label">Total Admin</div>
                <div class="stat-value"><?php echo isset($users['admin']) ? $users['admin'] : 0; ?></div>
            </div>
            
            <div class="stat-card penjemputan">
                <div class="stat-icon">📦</div>
                <div class="stat-label">Total Penjemputan</div>
                <div class="stat-value"><?php echo $stats['total_penjemputan']; ?></div>
            </div>
            
            <div class="stat-card transaksi">
                <div class="stat-icon">💰</div>
                <div class="stat-label">Total Transaksi</div>
                <div class="stat-value"><?php echo format_rupiah($stats['total_transaksi']); ?></div>
            </div>
            
            <div class="stat-card saldo">
                <div class="stat-icon">💳</div>
                <div class="stat-label">Total Saldo Warga</div>
                <div class="stat-value"><?php echo format_rupiah($stats['total_saldo']); ?></div>
            </div>
            
            <div class="stat-card berat">
                <div class="stat-icon">⚖️</div>
                <div class="stat-label">Total Berat Sampah</div>
                <div class="stat-value"><?php echo number_format($stats['total_berat'], 1); ?> kg</div>
            </div>
            
            <div class="stat-card pending">
                <div class="stat-icon">⏳</div>
                <div class="stat-label">Pending Verifikasi</div>
                <div class="stat-value"><?php echo $stats['pending_penarikan']; ?></div>
            </div>
        </div>
        
        <div class="card">
            <h3>📊 Menu Manajemen</h3>
            <div class="menu-grid">
                <a href="users.php" class="menu-item">
                    <div class="icon">👥</div>
                    <div>Kelola User</div>
                </a>
                <a href="verifikasi.php" class="menu-item">
                    <div class="icon">✓</div>
                    <div>Verifikasi Akun</div>
                </a>
                <a href="penjemputan.php" class="menu-item">
                    <div class="icon">🚛</div>
                    <div>Penjemputan</div>
                </a>
                <a href="transaksi.php" class="menu-item">
                    <div class="icon">💰</div>
                    <div>Transaksi</div>
                </a>
                <a href="penarikan.php" class="menu-item">
                    <div class="icon">💳</div>
                    <div>Penarikan Saldo</div>
                </a>
                <a href="harga_sampah.php" class="menu-item">
                    <div class="icon">💲</div>
                    <div>Harga Sampah</div>
                </a>
                <a href="pengumuman.php" class="menu-item">
                    <div class="icon">📢</div>
                    <div>Pengumuman</div>
                </a>
                <a href="laporan.php" class="menu-item">
                    <div class="icon">📊</div>
                    <div>Laporan</div>
                </a>
                <a href="feedback.php" class="menu-item">
                    <div class="icon">⭐</div>
                    <div>Feedback</div>
                </a>
            </div>
        </div>
        
        <div class="content-grid">
            <div class="card">
                <h3>💳 Permintaan Penarikan Saldo</h3>
                <?php if (mysqli_num_rows($result_penarikan) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Warga</th>
                                <th>Jumlah</th>
                                <th>Metode</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result_penarikan)): ?>
                            <tr>
                                <td><?php echo $row['nama']; ?></td>
                                <td><strong><?php echo format_rupiah($row['jumlah']); ?></strong></td>
                                <td><?php echo ucfirst($row['metode']); ?></td>
                                <td>
                                    <a href="verifikasi_penarikan.php?id=<?php echo $row['id']; ?>&action=approve" class="btn btn-success">
                                        ✓ Approve
                                    </a>
                                    <a href="verifikasi_penarikan.php?id=<?php echo $row['id']; ?>&action=reject" class="btn btn-danger">
                                        ✗ Tolak
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 20px;">Tidak ada permintaan penarikan</p>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h3>⭐ Feedback Terbaru</h3>
                <?php if (mysqli_num_rows($result_feedback) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result_feedback)): ?>
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <strong><?php echo $row['pengirim_nama']; ?></strong>
                            <span class="rating">
                                <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $row['rating'] ? '★' : '☆';
                                }
                                ?>
                            </span>
                        </div>
                        <p style="color: #666; font-size: 0.9em;"><?php echo substr($row['komentar'], 0, 80) . '...'; ?></p>
                        <small style="color: #999;"><?php echo format_tanggal($row['tanggal']); ?></small>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 20px;">Belum ada feedback</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <h3>💰 Transaksi Sampah Terbaru</h3>
            <?php if (mysqli_num_rows($result_transaksi) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Warga</th>
                            <th>Berat (kg)</th>
                            <th>Harga/kg</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result_transaksi)): ?>
                        <tr>
                            <td><?php echo format_tanggal_waktu($row['tanggal']); ?></td>
                            <td><?php echo $row['nama']; ?></td>
                            <td><?php echo number_format($row['berat_kg'], 2); ?> kg</td>
                            <td><?php echo format_rupiah($row['harga_per_kg']); ?></td>
                            <td><strong><?php echo format_rupiah($row['total_uang']); ?></strong></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 20px;">Belum ada transaksi</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
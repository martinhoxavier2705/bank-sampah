<?php
session_start();
require_once '../koneksi.php';

// Cek login
check_login('warga');

// Validasi session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['nama'])) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

$warga_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

// Ambil data saldo
$saldo = get_saldo_warga($warga_id);

// Ambil statistik warga
$query_stats = "SELECT 
    COUNT(DISTINCT p.id) as total_penjemputan,
    COALESCE(SUM(t.berat_kg), 0) as total_berat,
    COALESCE(SUM(t.total_uang), 0) as total_pendapatan
FROM users u
LEFT JOIN penjemputan p ON u.id = p.warga_id
LEFT JOIN transaksi_sampah t ON u.id = t.warga_id
WHERE u.id = '$warga_id'";

$result_stats = mysqli_query($conn, $query_stats);
$stats = mysqli_fetch_assoc($result_stats);

// Ambil penjemputan terbaru
$query_penjemputan = "SELECT p.*, u.nama as nama_mitra 
FROM penjemputan p
LEFT JOIN users u ON p.mitra_id = u.id
WHERE p.warga_id = '$warga_id'
ORDER BY p.created_at DESC
LIMIT 5";
$result_penjemputan = mysqli_query($conn, $query_penjemputan);

// Ambil transaksi sampah terbaru
$query_transaksi = "SELECT * FROM transaksi_sampah 
WHERE warga_id = '$warga_id'
ORDER BY tanggal DESC
LIMIT 5";
$result_transaksi = mysqli_query($conn, $query_transaksi);

// Ambil pengumuman terbaru
$query_pengumuman = "SELECT p.*, u.nama as pembuat 
FROM pengumuman p
JOIN users u ON p.dibuat_oleh = u.id
ORDER BY p.tanggal DESC
LIMIT 3";
$result_pengumuman = mysqli_query($conn, $query_pengumuman);

// Data untuk grafik (6 bulan terakhir)
$query_grafik = "SELECT 
    DATE_FORMAT(tanggal, '%Y-%m') as bulan,
    SUM(total_uang) as total
FROM transaksi_sampah
WHERE warga_id = '$warga_id'
AND tanggal >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
ORDER BY bulan ASC";
$result_grafik = mysqli_query($conn, $query_grafik);

$data_grafik = array();
while ($row = mysqli_fetch_assoc($result_grafik)) {
    $data_grafik[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Warga - Bank Sampah</title>
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
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
            max-width: 1200px;
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
        
        .stat-card.saldo {
            border-color: #f093fb;
        }
        
        .stat-card.penjemputan {
            border-color: #4facfe;
        }
        
        .stat-card.berat {
            border-color: #43e97b;
        }
        
        .stat-card.pendapatan {
            border-color: #fa709a;
        }
        
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
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .card h3 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f093fb;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
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
        
        .status.dijemput {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status.selesai {
            background: #d4edda;
            color: #155724;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .menu-item {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(240, 147, 251, 0.4);
        }
        
        .menu-item .icon {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .announcement {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #f093fb;
        }
        
        .announcement h4 {
            color: #333;
            margin-bottom: 8px;
        }
        
        .announcement p {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        
        .announcement small {
            color: #999;
            font-size: 0.85em;
        }
        
        @media (max-width: 968px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .menu-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">🌱 Bank Sampah - Warga</div>
        <div class="navbar-user">
            <span>👤 <?php echo $nama; ?></span>
            <a href="../logout.php" class="btn-logout">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome">
            <h2>Selamat Datang, <?php echo $nama; ?>! 👋</h2>
            <p>Kelola tabungan sampah Anda dan pantau aktivitas penjemputan</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card saldo">
                <div class="stat-icon">💰</div>
                <div class="stat-label">Saldo Tabungan</div>
                <div class="stat-value"><?php echo format_rupiah($saldo); ?></div>
            </div>
            
            <div class="stat-card penjemputan">
                <div class="stat-icon">🚛</div>
                <div class="stat-label">Total Penjemputan</div>
                <div class="stat-value"><?php echo $stats['total_penjemputan']; ?></div>
            </div>
            
            <div class="stat-card berat">
                <div class="stat-icon">⚖️</div>
                <div class="stat-label">Total Berat Sampah</div>
                <div class="stat-value"><?php echo number_format($stats['total_berat'], 1); ?> kg</div>
            </div>
            
            <div class="stat-card pendapatan">
                <div class="stat-icon">📈</div>
                <div class="stat-label">Total Pendapatan</div>
                <div class="stat-value"><?php echo format_rupiah($stats['total_pendapatan']); ?></div>
            </div>
        </div>
        
        <div class="card">
            <h3>📊 Menu Utama</h3>
            <div class="menu-grid">
                <a href="penjemputan.php" class="menu-item">
                    <div class="icon">🚛</div>
                    <div>Penjemputan Sampah</div>
                </a>
                <a href="tabungan.php" class="menu-item">
                    <div class="icon">💰</div>
                    <div>Riwayat Tabungan</div>
                </a>
                <a href="penarikan.php" class="menu-item">
                    <div class="icon">💳</div>
                    <div>Penarikan Saldo</div>
                </a>
                <a href="grafik.php" class="menu-item">
                    <div class="icon">📈</div>
                    <div>Grafik Tabungan</div>
                </a>
                <a href="feedback.php" class="menu-item">
                    <div class="icon">⭐</div>
                    <div>Beri Penilaian</div>
                </a>
                <a href="profil.php" class="menu-item">
                    <div class="icon">👤</div>
                    <div>Profil Saya</div>
                </a>
            </div>
        </div>
        
        <div class="content-grid">
            <div class="card">
                <h3>🚛 Penjemputan Terbaru</h3>
                <?php if (mysqli_num_rows($result_penjemputan) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Mitra</th>
                                <th>Status</th>
                                <th>Lokasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result_penjemputan)): ?>
                            <tr>
                                <td><?php echo $row['jadwal'] ? format_tanggal($row['jadwal']) : '-'; ?></td>
                                <td><?php echo $row['nama_mitra'] ? $row['nama_mitra'] : 'Belum ditentukan'; ?></td>
                                <td>
                                    <span class="status <?php echo $row['status']; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo substr($row['lokasi'], 0, 30) . '...'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 20px;">Belum ada data penjemputan</p>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h3>📢 Pengumuman</h3>
                <?php if (mysqli_num_rows($result_pengumuman) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result_pengumuman)): ?>
                    <div class="announcement">
                        <h4><?php echo $row['judul']; ?></h4>
                        <p><?php echo substr($row['isi'], 0, 100) . '...'; ?></p>
                        <small><?php echo format_tanggal($row['tanggal']); ?></small>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 20px;">Belum ada pengumuman</p>
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
                            <th>Berat (kg)</th>
                            <th>Harga/kg</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result_transaksi)): ?>
                        <tr>
                            <td><?php echo format_tanggal_waktu($row['tanggal']); ?></td>
                            <td><?php echo number_format($row['berat_kg'], 2); ?> kg</td>
                            <td><?php echo format_rupiah($row['harga_per_kg']); ?></td>
                            <td><strong><?php echo format_rupiah($row['total_uang']); ?></strong></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 20px;">Belum ada transaksi sampah</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
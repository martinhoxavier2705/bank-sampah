<?php
session_start();
require_once '../koneksi.php';

// Cek login
check_login('mitra');

// Validasi session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['nama'])) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

$mitra_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

// Ambil statistik mitra
$query_stats = "SELECT 
    COUNT(DISTINCT p.id) as total_penjemputan,
    COUNT(DISTINCT CASE WHEN p.status = 'pending' THEN p.id END) as pending,
    COUNT(DISTINCT CASE WHEN p.status = 'dijemput' THEN p.id END) as dijemput,
    COUNT(DISTINCT CASE WHEN p.status = 'selesai' THEN p.id END) as selesai,
    COALESCE(SUM(pen.berat_kg), 0) as total_berat,
    COALESCE(SUM(pen.hasil_uang), 0) as total_hasil
FROM penjemputan p
LEFT JOIN penimbangan pen ON p.id = pen.penjemputan_id
WHERE p.mitra_id = '$mitra_id'";

$result_stats = mysqli_query($conn, $query_stats);
$stats = mysqli_fetch_assoc($result_stats);

// Ambil jumlah karyawan
$query_karyawan = "SELECT COUNT(*) as total FROM karyawan WHERE mitra_id = '$mitra_id'";
$result_karyawan = mysqli_query($conn, $query_karyawan);
$karyawan = mysqli_fetch_assoc($result_karyawan);

// Ambil permintaan penjemputan pending
$query_pending = "SELECT p.*, u.nama, u.alamat, u.no_hp 
FROM penjemputan p
JOIN users u ON p.warga_id = u.id
WHERE p.mitra_id IS NULL OR (p.mitra_id = '$mitra_id' AND p.status = 'pending')
ORDER BY p.created_at DESC
LIMIT 5";
$result_pending = mysqli_query($conn, $query_pending);

// Ambil penjemputan hari ini
$query_today = "SELECT p.*, u.nama, u.alamat, u.no_hp 
FROM penjemputan p
JOIN users u ON p.warga_id = u.id
WHERE p.mitra_id = '$mitra_id' 
AND p.jadwal = CURDATE()
ORDER BY p.jadwal ASC";
$result_today = mysqli_query($conn, $query_today);

// Ambil hasil penimbangan terbaru
$query_penimbangan = "SELECT pen.*, p.warga_id, u.nama as nama_warga
FROM penimbangan pen
JOIN penjemputan p ON pen.penjemputan_id = p.id
JOIN users u ON p.warga_id = u.id
WHERE p.mitra_id = '$mitra_id'
ORDER BY pen.tanggal DESC
LIMIT 5";
$result_penimbangan = mysqli_query($conn, $query_penimbangan);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mitra - Bank Sampah</title>
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
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        
        .stat-card.pending { border-color: #ffa502; }
        .stat-card.dijemput { border-color: #4facfe; }
        .stat-card.selesai { border-color: #43e97b; }
        .stat-card.total { border-color: #11998e; }
        .stat-card.berat { border-color: #fa709a; }
        .stat-card.karyawan { border-color: #667eea; }
        
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
            border-bottom: 2px solid #11998e;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .menu-item {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(17, 153, 142, 0.4);
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
        
        .status.dijemput {
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
        }
        
        .btn-primary {
            background: #11998e;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0e7c73;
        }
        
        .alert {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert h4 {
            color: #0c5460;
            margin-bottom: 8px;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
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
        <div class="navbar-brand">🚛 Bank Sampah - Mitra</div>
        <div class="navbar-user">
            <span>👤 <?php echo $nama; ?></span>
            <a href="../logout.php" class="btn-logout">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome">
            <h2>Selamat Datang, <?php echo $nama; ?>! 👋</h2>
            <p>Partner pengumpul sampah terpercaya</p>
        </div>
        
        <?php if (mysqli_num_rows($result_today) > 0): ?>
        <div class="alert">
            <h4>📅 Anda memiliki <?php echo mysqli_num_rows($result_today); ?> jadwal penjemputan hari ini!</h4>
            <p>Silakan cek bagian "Penjemputan Hari Ini" di bawah.</p>
        </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card pending">
                <div class="stat-icon">⏳</div>
                <div class="stat-label">Pending</div>
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
            </div>
            
            <div class="stat-card dijemput">
                <div class="stat-icon">🚛</div>
                <div class="stat-label">Sedang Dijemput</div>
                <div class="stat-value"><?php echo $stats['dijemput']; ?></div>
            </div>
            
            <div class="stat-card selesai">
                <div class="stat-icon">✅</div>
                <div class="stat-label">Selesai</div>
                <div class="stat-value"><?php echo $stats['selesai']; ?></div>
            </div>
            
            <div class="stat-card total">
                <div class="stat-icon">📦</div>
                <div class="stat-label">Total Penjemputan</div>
                <div class="stat-value"><?php echo $stats['total_penjemputan']; ?></div>
            </div>
            
            <div class="stat-card berat">
                <div class="stat-icon">⚖️</div>
                <div class="stat-label">Total Berat</div>
                <div class="stat-value"><?php echo number_format($stats['total_berat'], 1); ?> kg</div>
            </div>
            
            <div class="stat-card karyawan">
                <div class="stat-icon">👥</div>
                <div class="stat-label">Jumlah Karyawan</div>
                <div class="stat-value"><?php echo $karyawan['total']; ?></div>
            </div>
        </div>
        
        <div class="card">
            <h3>📊 Menu Utama</h3>
            <div class="menu-grid">
                <a href="penjemputan.php" class="menu-item">
                    <div class="icon">📋</div>
                    <div>Kelola Penjemputan</div>
                </a>
                <a href="jadwal.php" class="menu-item">
                    <div class="icon">📅</div>
                    <div>Input jadwal</div>
                </a>
                <a href="penimbangan.php" class="menu-item">
                    <div class="icon">⚖️</div>
                    <div>Input Penimbangan</div>
                </a>
                <a href="karyawan.php" class="menu-item">
                    <div class="icon">👥</div>
                    <div>Kelola Karyawan</div>
                </a>
                <a href="laporan.php" class="menu-item">
                    <div class="icon">📊</div>
                    <div>Laporan Kinerja</div>
                </a>
                <a href="profil.php" class="menu-item">
                    <div class="icon">👤</div>
                    <div>Profil Saya</div>
                </a>
            </div>
        </div>
        
        <div class="card">
            <h3>🔔 Permintaan Penjemputan Baru</h3>
            <?php if (mysqli_num_rows($result_pending) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Warga</th>
                            <th>Alamat</th>
                            <th>No. HP</th>
                            <th>Tanggal Request</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result_pending)): ?>
                        <tr>
                            <td><?php echo $row['nama']; ?></td>
                            <td><?php echo substr($row['alamat'], 0, 40); ?></td>
                            <td><?php echo $row['no_hp']; ?></td>
                            <td><?php echo format_tanggal($row['created_at']); ?></td>
                            <td>
                                <a href="penjemputan.php?terima=<?php echo $row['id']; ?>" class="btn btn-primary">
                                    Terima
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 20px;">Tidak ada permintaan baru</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3>📅 Penjemputan Hari Ini</h3>
            <?php if (mysqli_num_rows($result_today) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Warga</th>
                            <th>Alamat</th>
                            <th>No. HP</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        mysqli_data_seek($result_today, 0);
                        while ($row = mysqli_fetch_assoc($result_today)): 
                        ?>
                        <tr>
                            <td><?php echo $row['nama']; ?></td>
                            <td><?php echo substr($row['alamat'], 0, 40); ?></td>
                            <td><?php echo $row['no_hp']; ?></td>
                            <td>
                                <span class="status <?php echo $row['status']; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['status'] == 'pending'): ?>
                                    <a href="penjemputan.php?mulai=<?php echo $row['id']; ?>" class="btn btn-primary">
                                        Mulai Jemput
                                    </a>
                                <?php elseif ($row['status'] == 'dijemput'): ?>
                                    <a href="penimbangan.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">
                                        Input Hasil
                                    </a>
                                <?php else: ?>
                                    <span style="color: #43e97b;">✓ Selesai</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 20px;">Tidak ada jadwal hari ini</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3>⚖️ Hasil Penimbangan Terbaru</h3>
            <?php if (mysqli_num_rows($result_penimbangan) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Warga</th>
                            <th>Berat (kg)</th>
                            <th>Hasil Uang</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result_penimbangan)): ?>
                        <tr>
                            <td><?php echo format_tanggal_waktu($row['tanggal']); ?></td>
                            <td><?php echo $row['nama_warga']; ?></td>
                            <td><?php echo number_format($row['berat_kg'], 2); ?> kg</td>
                            <td><strong><?php echo format_rupiah($row['hasil_uang']); ?></strong></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 20px;">Belum ada data penimbangan</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
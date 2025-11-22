<?php
session_start();
require_once '../koneksi.php';

check_login('admin');

$admin_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

// Filter
$filter_warga = isset($_GET['warga']) ? clean_input($_GET['warga']) : '';
$filter_bulan = isset($_GET['bulan']) ? clean_input($_GET['bulan']) : '';
$filter_tahun = isset($_GET['tahun']) ? clean_input($_GET['tahun']) : date('Y');

// Query transaksi
$query = "SELECT ts.*, u.nama as nama_warga, u.alamat
FROM transaksi_sampah ts
JOIN users u ON ts.warga_id = u.id
WHERE 1=1";

if (!empty($filter_warga)) {
    $query .= " AND ts.warga_id = '$filter_warga'";
}
if (!empty($filter_bulan)) {
    $query .= " AND MONTH(ts.tanggal) = '$filter_bulan'";
}
if (!empty($filter_tahun)) {
    $query .= " AND YEAR(ts.tanggal) = '$filter_tahun'";
}

$query .= " ORDER BY ts.tanggal DESC LIMIT 100";
$result = mysqli_query($conn, $query);

// Statistik
$query_stats = "SELECT 
    COUNT(*) as total_transaksi,
    COALESCE(SUM(berat_kg), 0) as total_berat,
    COALESCE(SUM(total_uang), 0) as total_uang
FROM transaksi_sampah WHERE 1=1";

if (!empty($filter_bulan)) {
    $query_stats .= " AND MONTH(tanggal) = '$filter_bulan'";
}
if (!empty($filter_tahun)) {
    $query_stats .= " AND YEAR(tanggal) = '$filter_tahun'";
}

$result_stats = mysqli_query($conn, $query_stats);
$stats = mysqli_fetch_assoc($result_stats);

// Daftar warga
$query_warga = "SELECT id, nama FROM users WHERE role = 'warga' ORDER BY nama ASC";
$result_warga = mysqli_query($conn, $query_warga);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Sampah - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-brand { font-size: 1.5em; font-weight: bold; }
        .navbar-menu { display: flex; gap: 20px; align-items: center; }
        .navbar-menu a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: all 0.3s; }
        .navbar-menu a:hover { background: rgba(255,255,255,0.2); }
        .container { max-width: 1400px; margin: 0 auto; padding: 30px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid; }
        .stat-card.transaksi { border-color: #667eea; }
        .stat-card.berat { border-color: #43e97b; }
        .stat-card.uang { border-color: #fa709a; }
        .stat-label { color: #666; font-size: 0.9em; margin-bottom: 10px; }
        .stat-value { font-size: 2em; font-weight: bold; color: #333; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card h2 { color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #667eea; }
        .filter-section { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-section select { padding: 10px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 0.95em; }
        .filter-section button { padding: 10px 25px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .filter-section button:hover { background: #5568d3; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table th { background: #f8f9fa; padding: 12px; text-align: left; font-weight: 600; color: #333; border-bottom: 2px solid #e0e0e0; }
        table td { padding: 12px; border-bottom: 1px solid #f0f0f0; }
        .empty-state { text-align: center; padding: 60px 20px; color: #999; }
        .empty-state .icon { font-size: 5em; margin-bottom: 20px; opacity: 0.5; }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
            .filter-section { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">💰 Admin - Transaksi Sampah</div>
        <div class="navbar-menu">
            <a href="dashboard.php">← Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card transaksi">
                <div class="stat-label">Total Transaksi</div>
                <div class="stat-value"><?php echo $stats['total_transaksi']; ?></div>
            </div>
            <div class="stat-card berat">
                <div class="stat-label">Total Berat Sampah</div>
                <div class="stat-value"><?php echo number_format($stats['total_berat'], 1); ?> kg</div>
            </div>
            <div class="stat-card uang">
                <div class="stat-label">Total Nilai Transaksi</div>
                <div class="stat-value"><?php echo format_rupiah($stats['total_uang']); ?></div>
            </div>
        </div>
        
        <div class="card">
            <h2>💰 Data Transaksi Sampah</h2>
            
            <form method="GET" class="filter-section">
                <select name="warga">
                    <option value="">Semua Warga</option>
                    <?php while($w = mysqli_fetch_assoc($result_warga)): ?>
                    <option value="<?php echo $w['id']; ?>" <?php echo $filter_warga == $w['id'] ? 'selected' : ''; ?>>
                        <?php echo $w['nama']; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                
                <select name="bulan">
                    <option value="">Semua Bulan</option>
                    <?php 
                    $bulan_names = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    for($i = 1; $i <= 12; $i++): 
                    ?>
                    <option value="<?php echo $i; ?>" <?php echo $filter_bulan == $i ? 'selected' : ''; ?>>
                        <?php echo $bulan_names[$i]; ?>
                    </option>
                    <?php endfor; ?>
                </select>
                
                <select name="tahun">
                    <?php for($y = date('Y'); $y >= 2020; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $filter_tahun == $y ? 'selected' : ''; ?>>
                        <?php echo $y; ?>
                    </option>
                    <?php endfor; ?>
                </select>
                
                <button type="submit">🔍 Filter</button>
                <a href="transaksi.php" style="padding: 10px 25px; background: #6c757d; color: white; border-radius: 8px; text-decoration: none;">Reset</a>
            </form>
            
            <?php if (mysqli_num_rows($result) > 0): ?>
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
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo format_tanggal_waktu($row['tanggal']); ?></td>
                        <td>
                            <strong><?php echo $row['nama_warga']; ?></strong><br>
                            <small style="color: #666;"><?php echo substr($row['alamat'], 0, 40); ?></small>
                        </td>
                        <td><strong><?php echo number_format($row['berat_kg'], 2); ?> kg</strong></td>
                        <td><?php echo format_rupiah($row['harga_per_kg']); ?></td>
                        <td><strong style="color: #28a745;"><?php echo format_rupiah($row['total_uang']); ?></strong></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <h3>Tidak Ada Data</h3>
                <p>Tidak ada transaksi yang sesuai dengan filter</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
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

// Ambil saldo
$saldo = get_saldo_warga($warga_id);

// Filter
$filter_bulan = isset($_GET['bulan']) ? clean_input($_GET['bulan']) : '';
$filter_tahun = isset($_GET['tahun']) ? clean_input($_GET['tahun']) : date('Y');

// Query transaksi
$query_transaksi = "SELECT * FROM transaksi_sampah WHERE warga_id = '$warga_id'";

if (!empty($filter_bulan)) {
    $query_transaksi .= " AND MONTH(tanggal) = '$filter_bulan'";
}
if (!empty($filter_tahun)) {
    $query_transaksi .= " AND YEAR(tanggal) = '$filter_tahun'";
}

$query_transaksi .= " ORDER BY tanggal DESC";
$result_transaksi = mysqli_query($conn, $query_transaksi);

// Statistik periode
$query_stats = "SELECT 
    COUNT(*) as total_transaksi,
    COALESCE(SUM(berat_kg), 0) as total_berat,
    COALESCE(SUM(total_uang), 0) as total_pendapatan
FROM transaksi_sampah WHERE warga_id = '$warga_id'";

if (!empty($filter_bulan)) {
    $query_stats .= " AND MONTH(tanggal) = '$filter_bulan'";
}
if (!empty($filter_tahun)) {
    $query_stats .= " AND YEAR(tanggal) = '$filter_tahun'";
}

$result_stats = mysqli_query($conn, $query_stats);
$stats = mysqli_fetch_assoc($result_stats);

// Statistik keseluruhan
$query_all = "SELECT 
    COUNT(*) as total_transaksi,
    COALESCE(SUM(berat_kg), 0) as total_berat,
    COALESCE(SUM(total_uang), 0) as total_pendapatan
FROM transaksi_sampah WHERE warga_id = '$warga_id'";
$result_all = mysqli_query($conn, $query_all);
$all_stats = mysqli_fetch_assoc($result_all);

$bulan_names = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Tabungan - Warga</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .navbar { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-brand { font-size: 1.5em; font-weight: bold; }
        .navbar-menu { display: flex; gap: 20px; align-items: center; }
        .navbar-menu a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: all 0.3s; }
        .navbar-menu a:hover { background: rgba(255,255,255,0.2); }
        .container { max-width: 1200px; margin: 0 auto; padding: 30px; }
        .saldo-card { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 40px; border-radius: 20px; text-align: center; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(240, 147, 251, 0.3); }
        .saldo-label { font-size: 1.2em; margin-bottom: 10px; opacity: 0.9; }
        .saldo-value { font-size: 3.5em; font-weight: bold; margin-bottom: 10px; }
        .saldo-actions { margin-top: 20px; }
        .btn { padding: 12px 30px; border: none; border-radius: 25px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; transition: all 0.3s; margin: 0 5px; }
        .btn-light { background: white; color: #f093fb; }
        .btn-light:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(255,255,255,0.3); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid; }
        .stat-card.transaksi { border-color: #f093fb; }
        .stat-card.berat { border-color: #43e97b; }
        .stat-card.pendapatan { border-color: #ffc107; }
        .stat-label { color: #666; font-size: 0.9em; margin-bottom: 10px; }
        .stat-value { font-size: 2em; font-weight: bold; color: #333; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card h2 { color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f093fb; }
        .filter-section { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-section select { padding: 10px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 0.95em; }
        .filter-section button { padding: 10px 25px; background: #f093fb; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table th { background: #f8f9fa; padding: 12px; text-align: left; font-weight: 600; color: #333; border-bottom: 2px solid #e0e0e0; }
        table td { padding: 12px; border-bottom: 1px solid #f0f0f0; }
        .empty-state { text-align: center; padding: 60px 20px; color: #999; }
        .empty-state .icon { font-size: 5em; margin-bottom: 20px; opacity: 0.5; }
        .summary-box { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 20px; }
        .summary-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e0e0e0; }
        .summary-item:last-child { border-bottom: none; font-weight: bold; font-size: 1.1em; }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
            .filter-section { flex-direction: column; }
            .saldo-value { font-size: 2.5em; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">💰 Warga - Riwayat Tabungan</div>
        <div class="navbar-menu">
            <a href="dashboard.php">← Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="saldo-card">
            <div class="saldo-label">💰 Saldo Tabungan Anda</div>
            <div class="saldo-value"><?php echo format_rupiah($saldo); ?></div>
            <div class="saldo-actions">
                <a href="penarikan.php" class="btn btn-light">💳 Tarik Saldo</a>
                <a href="grafik.php" class="btn btn-light">📈 Lihat Grafik</a>
            </div>
        </div>
        
        <div class="card">
            <h2>📊 Statistik Keseluruhan</h2>
            <div class="stats-grid">
                <div class="stat-card transaksi">
                    <div class="stat-label">Total Transaksi</div>
                    <div class="stat-value"><?php echo $all_stats['total_transaksi']; ?></div>
                </div>
                <div class="stat-card berat">
                    <div class="stat-label">Total Berat Sampah</div>
                    <div class="stat-value"><?php echo number_format($all_stats['total_berat'], 1); ?> kg</div>
                </div>
                <div class="stat-card pendapatan">
                    <div class="stat-label">Total Pendapatan</div>
                    <div class="stat-value"><?php echo format_rupiah($all_stats['total_pendapatan']); ?></div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>📋 Riwayat Transaksi</h2>
            
            <form method="GET" class="filter-section">
                <label style="font-weight: 600;">Filter Periode:</label>
                <select name="bulan">
                    <option value="">Semua Bulan</option>
                    <?php for($i = 1; $i <= 12; $i++): ?>
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
                <a href="tabungan.php" style="padding: 10px 25px; background: #6c757d; color: white; border-radius: 8px; text-decoration: none;">Reset</a>
            </form>
            
            <?php if (!empty($filter_bulan) || $filter_tahun != date('Y')): ?>
            <div class="summary-box">
                <h3 style="margin-bottom: 15px;">Periode: <?php echo ($filter_bulan ? $bulan_names[(int)$filter_bulan] . ' ' : '') . $filter_tahun; ?></h3>
                <div class="summary-item">
                    <span>Total Transaksi:</span>
                    <strong><?php echo $stats['total_transaksi']; ?> kali</strong>
                </div>
                <div class="summary-item">
                    <span>Total Berat:</span>
                    <strong><?php echo number_format($stats['total_berat'], 2); ?> kg</strong>
                </div>
                <div class="summary-item">
                    <span>Total Pendapatan:</span>
                    <strong style="color: #28a745;"><?php echo format_rupiah($stats['total_pendapatan']); ?></strong>
                </div>
            </div>
            <?php endif; ?>
            
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
                    <?php while($row = mysqli_fetch_assoc($result_transaksi)): ?>
                    <tr>
                        <td><?php echo format_tanggal_waktu($row['tanggal']); ?></td>
                        <td><strong><?php echo number_format($row['berat_kg'], 2); ?> kg</strong></td>
                        <td><?php echo format_rupiah($row['harga_per_kg']); ?></td>
                        <td><strong style="color: #28a745; font-size: 1.1em;"><?php echo format_rupiah($row['total_uang']); ?></strong></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <h3>Belum Ada Transaksi</h3>
                <p>Belum ada transaksi <?php echo ($filter_bulan ? 'di ' . $bulan_names[(int)$filter_bulan] . ' ' : '') . $filter_tahun; ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
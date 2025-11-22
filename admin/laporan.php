<?php
session_start();
require_once '../koneksi.php';

check_login('admin');

$admin_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

// Filter
$filter_bulan = isset($_GET['bulan']) ? clean_input($_GET['bulan']) : date('m');
$filter_tahun = isset($_GET['tahun']) ? clean_input($_GET['tahun']) : date('Y');

// Laporan Bulanan
$query_laporan = "SELECT 
    COUNT(DISTINCT ts.id) as total_transaksi,
    COUNT(DISTINCT ts.warga_id) as total_warga_aktif,
    COALESCE(SUM(ts.berat_kg), 0) as total_berat,
    COALESCE(SUM(ts.total_uang), 0) as total_transaksi_uang,
    (SELECT COALESCE(SUM(jumlah), 0) FROM penarikan_saldo 
     WHERE status = 'selesai' 
     AND MONTH(tanggal) = '$filter_bulan' 
     AND YEAR(tanggal) = '$filter_tahun') as total_penarikan,
    (SELECT COUNT(*) FROM penjemputan 
     WHERE MONTH(created_at) = '$filter_bulan' 
     AND YEAR(created_at) = '$filter_tahun') as total_penjemputan
FROM transaksi_sampah ts
WHERE MONTH(ts.tanggal) = '$filter_bulan' 
AND YEAR(ts.tanggal) = '$filter_tahun'";

$result_laporan = mysqli_query($conn, $query_laporan);
$laporan = mysqli_fetch_assoc($result_laporan);

// Top 5 Warga Teratas Bulan Ini
$query_top_warga = "SELECT u.nama, 
    COUNT(*) as jumlah_transaksi,
    SUM(ts.berat_kg) as total_berat,
    SUM(ts.total_uang) as total_uang
FROM transaksi_sampah ts
JOIN users u ON ts.warga_id = u.id
WHERE MONTH(ts.tanggal) = '$filter_bulan' 
AND YEAR(ts.tanggal) = '$filter_tahun'
GROUP BY ts.warga_id
ORDER BY total_berat DESC
LIMIT 5";
$result_top_warga = mysqli_query($conn, $query_top_warga);

// Transaksi per hari dalam bulan ini
$query_harian = "SELECT 
    DAY(tanggal) as hari,
    COUNT(*) as jumlah,
    SUM(total_uang) as total
FROM transaksi_sampah
WHERE MONTH(tanggal) = '$filter_bulan' 
AND YEAR(tanggal) = '$filter_tahun'
GROUP BY DAY(tanggal)
ORDER BY hari ASC";
$result_harian = mysqli_query($conn, $query_harian);

$data_harian = array();
while ($row = mysqli_fetch_assoc($result_harian)) {
    $data_harian[$row['hari']] = $row;
}

$bulan_names = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-brand { font-size: 1.5em; font-weight: bold; }
        .navbar-menu { display: flex; gap: 20px; align-items: center; }
        .navbar-menu a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: all 0.3s; }
        .navbar-menu a:hover { background: rgba(255,255,255,0.2); }
        .container { max-width: 1400px; margin: 0 auto; padding: 30px; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card h2 { color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #667eea; }
        .filter-section { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
        .filter-section select { padding: 10px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 0.95em; }
        .filter-section button { padding: 10px 25px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .filter-section button:hover { background: #5568d3; }
        .btn-print { padding: 10px 25px; background: #28a745; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-print:hover { background: #218838; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid; }
        .stat-card.transaksi { border-color: #667eea; }
        .stat-card.berat { border-color: #43e97b; }
        .stat-card.uang { border-color: #fa709a; }
        .stat-card.penarikan { border-color: #ffc107; }
        .stat-card.penjemputan { border-color: #17a2b8; }
        .stat-card.warga { border-color: #6f42c1; }
        .stat-label { color: #666; font-size: 0.9em; margin-bottom: 10px; }
        .stat-value { font-size: 1.8em; font-weight: bold; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table th { background: #f8f9fa; padding: 12px; text-align: left; font-weight: 600; color: #333; border-bottom: 2px solid #e0e0e0; }
        table td { padding: 12px; border-bottom: 1px solid #f0f0f0; }
        .summary-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 15px; margin-top: 20px; }
        .summary-box h3 { margin-bottom: 20px; }
        .summary-item { display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid rgba(255,255,255,0.2); }
        .summary-item:last-child { border-bottom: none; font-size: 1.2em; font-weight: bold; }
        @media print {
            .navbar, .filter-section, .btn-print { display: none; }
            body { background: white; }
            .card { box-shadow: none; }
        }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .filter-section { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">📊 Admin - Laporan Keuangan</div>
        <div class="navbar-menu">
            <a href="dashboard.php">← Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0; border: none;">📊 Laporan Keuangan</h2>
                <button onclick="window.print()" class="btn-print">🖨️ Cetak Laporan</button>
            </div>
            
            <form method="GET" class="filter-section">
                <label style="font-weight: 600;">Pilih Periode:</label>
                <select name="bulan">
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
                
                <button type="submit">🔍 Tampilkan</button>
            </form>
            
            <h3 style="text-align: center; color: #667eea; margin: 20px 0;">
                Periode: <?php echo $bulan_names[(int)$filter_bulan] . ' ' . $filter_tahun; ?>
            </h3>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card transaksi">
                <div class="stat-label">Total Transaksi</div>
                <div class="stat-value"><?php echo $laporan['total_transaksi']; ?></div>
            </div>
            <div class="stat-card warga">
                <div class="stat-label">Warga Aktif</div>
                <div class="stat-value"><?php echo $laporan['total_warga_aktif']; ?></div>
            </div>
            <div class="stat-card penjemputan">
                <div class="stat-label">Total Penjemputan</div>
                <div class="stat-value"><?php echo $laporan['total_penjemputan']; ?></div>
            </div>
            <div class="stat-card berat">
                <div class="stat-label">Total Berat Sampah</div>
                <div class="stat-value"><?php echo number_format($laporan['total_berat'], 1); ?> kg</div>
            </div>
            <div class="stat-card uang">
                <div class="stat-label">Total Pendapatan Warga</div>
                <div class="stat-value" style="font-size: 1.4em;"><?php echo format_rupiah($laporan['total_transaksi_uang']); ?></div>
            </div>
            <div class="stat-card penarikan">
                <div class="stat-label">Total Penarikan</div>
                <div class="stat-value" style="font-size: 1.4em;"><?php echo format_rupiah($laporan['total_penarikan']); ?></div>
            </div>
        </div>
        
        <div class="card">
            <h2>🏆 Top 5 Warga Teratas Bulan Ini</h2>
            <?php if (mysqli_num_rows($result_top_warga) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Peringkat</th>
                        <th>Nama Warga</th>
                        <th>Jumlah Transaksi</th>
                        <th>Total Berat</th>
                        <th>Total Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    while($row = mysqli_fetch_assoc($result_top_warga)): 
                    ?>
                    <tr>
                        <td><strong><?php echo $rank; ?></strong></td>
                        <td><strong><?php echo $row['nama']; ?></strong></td>
                        <td><?php echo $row['jumlah_transaksi']; ?> kali</td>
                        <td><?php echo number_format($row['total_berat'], 2); ?> kg</td>
                        <td><strong style="color: #28a745;"><?php echo format_rupiah($row['total_uang']); ?></strong></td>
                    </tr>
                    <?php 
                    $rank++;
                    endwhile; 
                    ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #999; padding: 40px;">Belum ada data transaksi bulan ini</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <div class="summary-box">
                <h3>📋 Ringkasan Keuangan</h3>
                <div class="summary-item">
                    <span>Total Pendapatan Warga:</span>
                    <strong><?php echo format_rupiah($laporan['total_transaksi_uang']); ?></strong>
                </div>
                <div class="summary-item">
                    <span>Total Penarikan Saldo:</span>
                    <strong><?php echo format_rupiah($laporan['total_penarikan']); ?></strong>
                </div>
                <div class="summary-item">
                    <span>Saldo Tersimpan:</span>
                    <strong><?php echo format_rupiah($laporan['total_transaksi_uang'] - $laporan['total_penarikan']); ?></strong>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px; color: #999; font-size: 0.9em;">
            <p>Laporan dibuat pada: <?php echo date('d F Y, H:i'); ?> WIB</p>
            <p>Dicetak oleh: <?php echo $nama; ?> (Admin)</p>
        </div>
    </div>
</body>
</html>
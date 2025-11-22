<?php
session_start();
require_once '../koneksi.php';

check_login('mitra');

$mitra_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

// Filter
$filter_bulan = isset($_GET['bulan']) ? clean_input($_GET['bulan']) : date('m');
$filter_tahun = isset($_GET['tahun']) ? clean_input($_GET['tahun']) : date('Y');

// Laporan Kinerja Mitra
$query_kinerja = "SELECT 
    COUNT(DISTINCT p.id) as total_penjemputan,
    COUNT(DISTINCT CASE WHEN p.status = 'selesai' THEN p.id END) as selesai,
    COUNT(DISTINCT p.warga_id) as warga_dilayani,
    COALESCE(SUM(pen.berat_kg), 0) as total_berat,
    COALESCE(SUM(pen.hasil_uang), 0) as total_nilai
FROM penjemputan p
LEFT JOIN penimbangan pen ON p.id = pen.penjemputan_id
WHERE p.mitra_id = '$mitra_id'
AND MONTH(p.created_at) = '$filter_bulan'
AND YEAR(p.created_at) = '$filter_tahun'";

$result_kinerja = mysqli_query($conn, $query_kinerja);
$kinerja = mysqli_fetch_assoc($result_kinerja);

// Detail Penjemputan per Hari
$query_harian = "SELECT 
    DATE(p.created_at) as tanggal,
    COUNT(p.id) as jumlah_penjemputan,
    COUNT(CASE WHEN p.status = 'selesai' THEN 1 END) as selesai,
    COALESCE(SUM(pen.berat_kg), 0) as total_berat,
    COALESCE(SUM(pen.hasil_uang), 0) as total_nilai
FROM penjemputan p
LEFT JOIN penimbangan pen ON p.id = pen.penjemputan_id
WHERE p.mitra_id = '$mitra_id'
AND MONTH(p.created_at) = '$filter_bulan'
AND YEAR(p.created_at) = '$filter_tahun'
GROUP BY DATE(p.created_at)
ORDER BY tanggal DESC";
$result_harian = mysqli_query($conn, $query_harian);

// Top 5 Warga yang Dilayani
$query_top_warga = "SELECT 
    u.nama,
    COUNT(p.id) as jumlah_penjemputan,
    COALESCE(SUM(pen.berat_kg), 0) as total_berat,
    COALESCE(SUM(pen.hasil_uang), 0) as total_nilai
FROM penjemputan p
JOIN users u ON p.warga_id = u.id
LEFT JOIN penimbangan pen ON p.id = pen.penjemputan_id
WHERE p.mitra_id = '$mitra_id'
AND MONTH(p.created_at) = '$filter_bulan'
AND YEAR(p.created_at) = '$filter_tahun'
AND p.status = 'selesai'
GROUP BY p.warga_id
ORDER BY total_berat DESC
LIMIT 5";
$result_top_warga = mysqli_query($conn, $query_top_warga);

// Kinerja Karyawan
$query_karyawan = "SELECT k.nama, k.jabatan, COUNT(*) as total 
FROM karyawan k 
WHERE k.mitra_id = '$mitra_id'
GROUP BY k.id";
$result_karyawan = mysqli_query($conn, $query_karyawan);

// Persentase selesai
$persentase_selesai = $kinerja['total_penjemputan'] > 0 
    ? round(($kinerja['selesai'] / $kinerja['total_penjemputan']) * 100, 1) 
    : 0;

$bulan_names = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Mitra</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .navbar { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-brand { font-size: 1.5em; font-weight: bold; }
        .navbar-menu { display: flex; gap: 20px; align-items: center; }
        .navbar-menu a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: all 0.3s; }
        .navbar-menu a:hover { background: rgba(255,255,255,0.2); }
        .container { max-width: 1400px; margin: 0 auto; padding: 30px; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card h2 { color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #11998e; }
        .filter-section { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
        .filter-section select { padding: 10px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 0.95em; }
        .filter-section button { padding: 10px 25px; background: #11998e; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .filter-section button:hover { background: #0e7c73; }
        .btn-print { padding: 10px 25px; background: #28a745; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-print:hover { background: #218838; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid; }
        .stat-card.penjemputan { border-color: #11998e; }
        .stat-card.selesai { border-color: #28a745; }
        .stat-card.warga { border-color: #17a2b8; }
        .stat-card.berat { border-color: #ffc107; }
        .stat-card.nilai { border-color: #fa709a; }
        .stat-label { color: #666; font-size: 0.9em; margin-bottom: 10px; }
        .stat-value { font-size: 2em; font-weight: bold; color: #333; }
        .stat-subtext { font-size: 0.85em; color: #999; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table th { background: #f8f9fa; padding: 12px; text-align: left; font-weight: 600; color: #333; border-bottom: 2px solid #e0e0e0; }
        table td { padding: 12px; border-bottom: 1px solid #f0f0f0; }
        .progress-bar { width: 100%; height: 8px; background: #e0e0e0; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: #28a745; border-radius: 10px; transition: width 0.3s; }
        .summary-box { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 30px; border-radius: 15px; margin-top: 20px; }
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
        <div class="navbar-brand">📊 Mitra - Laporan</div>
        <div class="navbar-menu">
            <a href="dashboard.php">← Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0; border: none;">📊 Laporan Kinerja Mitra</h2>
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
            
            <h3 style="text-align: center; color: #11998e; margin: 20px 0;">
                Periode: <?php echo $bulan_names[(int)$filter_bulan] . ' ' . $filter_tahun; ?>
            </h3>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card penjemputan">
                <div class="stat-label">Total Penjemputan</div>
                <div class="stat-value"><?php echo $kinerja['total_penjemputan']; ?></div>
            </div>
            
            <div class="stat-card selesai">
                <div class="stat-label">Penjemputan Selesai</div>
                <div class="stat-value"><?php echo $kinerja['selesai']; ?></div>
                <div class="stat-subtext">
                    <div class="progress-bar" style="margin-top: 10px;">
                        <div class="progress-fill" style="width: <?php echo $persentase_selesai; ?>%;"></div>
                    </div>
                    <?php echo $persentase_selesai; ?>% dari total
                </div>
            </div>
            
            <div class="stat-card warga">
                <div class="stat-label">Warga Dilayani</div>
                <div class="stat-value"><?php echo $kinerja['warga_dilayani']; ?></div>
            </div>
            
            <div class="stat-card berat">
                <div class="stat-label">Total Berat Terkumpul</div>
                <div class="stat-value"><?php echo number_format($kinerja['total_berat'], 1); ?> kg</div>
            </div>
            
            <div class="stat-card nilai">
                <div class="stat-label">Total Nilai Dihasilkan</div>
                <div class="stat-value" style="font-size: 1.5em;"><?php echo format_rupiah($kinerja['total_nilai']); ?></div>
            </div>
        </div>
        
        <div class="card">
            <h2>📋 Detail Penjemputan Harian</h2>
            <?php if (mysqli_num_rows($result_harian) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jumlah Penjemputan</th>
                        <th>Selesai</th>
                        <th>Total Berat</th>
                        <th>Total Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result_harian)): ?>
                    <tr>
                        <td><?php echo format_tanggal($row['tanggal']); ?></td>
                        <td><?php echo $row['jumlah_penjemputan']; ?> penjemputan</td>
                        <td><strong style="color: #28a745;"><?php echo $row['selesai']; ?></strong></td>
                        <td><?php echo number_format($row['total_berat'], 2); ?> kg</td>
                        <td><strong><?php echo format_rupiah($row['total_nilai']); ?></strong></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #999; padding: 40px;">Belum ada data penjemputan bulan ini</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>🏆 Top 5 Warga yang Dilayani</h2>
            <?php if (mysqli_num_rows($result_top_warga) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Peringkat</th>
                        <th>Nama Warga</th>
                        <th>Jumlah Penjemputan</th>
                        <th>Total Berat</th>
                        <th>Total Nilai</th>
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
                        <td><?php echo $row['jumlah_penjemputan']; ?> kali</td>
                        <td><?php echo number_format($row['total_berat'], 2); ?> kg</td>
                        <td><strong style="color: #28a745;"><?php echo format_rupiah($row['total_nilai']); ?></strong></td>
                    </tr>
                    <?php 
                    $rank++;
                    endwhile; 
                    ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #999; padding: 40px;">Belum ada data bulan ini</p>
            <?php endif; ?>
        </div>
        
        <?php if (mysqli_num_rows($result_karyawan) > 0): ?>
        <div class="card">
            <h2>👥 Data Karyawan</h2>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Karyawan</th>
                        <th>Jabatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    while($row = mysqli_fetch_assoc($result_karyawan)): 
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo $row['nama']; ?></td>
                        <td><?php echo $row['jabatan']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="summary-box">
                <h3>📋 Ringkasan Kinerja</h3>
                <div class="summary-item">
                    <span>Total Penjemputan:</span>
                    <strong><?php echo $kinerja['total_penjemputan']; ?> kali</strong>
                </div>
                <div class="summary-item">
                    <span>Tingkat Penyelesaian:</span>
                    <strong><?php echo $persentase_selesai; ?>%</strong>
                </div>
                <div class="summary-item">
                    <span>Total Berat Terkumpul:</span>
                    <strong><?php echo number_format($kinerja['total_berat'], 2); ?> kg</strong>
                </div>
                <div class="summary-item">
                    <span>Total Nilai Dihasilkan:</span>
                    <strong><?php echo format_rupiah($kinerja['total_nilai']); ?></strong>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px; color: #999; font-size: 0.9em;">
            <p>Laporan dibuat pada: <?php echo date('d F Y, H:i'); ?> WIB</p>
            <p>Mitra: <?php echo $nama; ?></p>
        </div>
    </div>
</body>
</html>
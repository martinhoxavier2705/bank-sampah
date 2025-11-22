<?php
session_start();
require_once '../koneksi.php';

check_login('admin');

$admin_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

// Filter
$filter_status = isset($_GET['status']) ? clean_input($_GET['status']) : '';
$filter_mitra = isset($_GET['mitra']) ? clean_input($_GET['mitra']) : '';

// Query penjemputan
$query = "SELECT p.*, 
    u1.nama as nama_warga, u1.alamat as alamat_warga, u1.no_hp as hp_warga,
    u2.nama as nama_mitra
FROM penjemputan p
JOIN users u1 ON p.warga_id = u1.id
LEFT JOIN users u2 ON p.mitra_id = u2.id
WHERE 1=1";

if (!empty($filter_status)) {
    $query .= " AND p.status = '$filter_status'";
}
if (!empty($filter_mitra)) {
    $query .= " AND p.mitra_id = '$filter_mitra'";
}

$query .= " ORDER BY p.created_at DESC LIMIT 100";
$result = mysqli_query($conn, $query);

// Statistik
$query_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'dijemput' THEN 1 ELSE 0 END) as dijemput,
    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai
FROM penjemputan";
$result_stats = mysqli_query($conn, $query_stats);
$stats = mysqli_fetch_assoc($result_stats);

// Daftar mitra
$query_mitra = "SELECT id, nama FROM users WHERE role = 'mitra' ORDER BY nama ASC";
$result_mitra = mysqli_query($conn, $query_mitra);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penjemputan - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-brand { font-size: 1.5em; font-weight: bold; }
        .navbar-menu { display: flex; gap: 20px; align-items: center; }
        .navbar-menu a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: all 0.3s; }
        .navbar-menu a:hover { background: rgba(255,255,255,0.2); }
        .container { max-width: 1400px; margin: 0 auto; padding: 30px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid; }
        .stat-card.total { border-color: #667eea; }
        .stat-card.pending { border-color: #ffc107; }
        .stat-card.dijemput { border-color: #17a2b8; }
        .stat-card.selesai { border-color: #28a745; }
        .stat-label { color: #666; font-size: 0.9em; margin-bottom: 10px; }
        .stat-value { font-size: 2em; font-weight: bold; color: #333; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card h2 { color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #667eea; }
        .filter-section { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-section select { padding: 10px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 0.95em; }
        .filter-section button { padding: 10px 25px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .filter-section button:hover { background: #5568d3; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table th { background: #f8f9fa; padding: 12px; text-align: left; font-weight: 600; color: #333; border-bottom: 2px solid #e0e0e0; font-size: 0.9em; }
        table td { padding: 12px; border-bottom: 1px solid #f0f0f0; font-size: 0.9em; }
        .status { padding: 5px 12px; border-radius: 20px; font-size: 0.85em; font-weight: 600; }
        .status.pending { background: #fff3cd; color: #856404; }
        .status.dijemput { background: #d1ecf1; color: #0c5460; }
        .status.selesai { background: #d4edda; color: #155724; }
        .empty-state { text-align: center; padding: 60px 20px; color: #999; }
        .empty-state .icon { font-size: 5em; margin-bottom: 20px; opacity: 0.5; }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .filter-section { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">🚛 Admin - Penjemputan Sampah</div>
        <div class="navbar-menu">
            <a href="dashboard.php">← Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-label">Total Penjemputan</div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
            </div>
            <div class="stat-card pending">
                <div class="stat-label">Pending</div>
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
            </div>
            <div class="stat-card dijemput">
                <div class="stat-label">Sedang Dijemput</div>
                <div class="stat-value"><?php echo $stats['dijemput']; ?></div>
            </div>
            <div class="stat-card selesai">
                <div class="stat-label">Selesai</div>
                <div class="stat-value"><?php echo $stats['selesai']; ?></div>
            </div>
        </div>
        
        <div class="card">
            <h2>🚛 Data Penjemputan</h2>
            
            <form method="GET" class="filter-section">
                <select name="status">
                    <option value="">Semua Status</option>
                    <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="dijemput" <?php echo $filter_status == 'dijemput' ? 'selected' : ''; ?>>Dijemput</option>
                    <option value="selesai" <?php echo $filter_status == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                </select>
                
                <select name="mitra">
                    <option value="">Semua Mitra</option>
                    <?php while($m = mysqli_fetch_assoc($result_mitra)): ?>
                    <option value="<?php echo $m['id']; ?>" <?php echo $filter_mitra == $m['id'] ? 'selected' : ''; ?>>
                        <?php echo $m['nama']; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                
                <button type="submit">🔍 Filter</button>
                <a href="penjemputan.php" style="padding: 10px 25px; background: #6c757d; color: white; border-radius: 8px; text-decoration: none;">Reset</a>
            </form>
            
            <?php if (mysqli_num_rows($result) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal Request</th>
                        <th>Warga</th>
                        <th>Mitra</th>
                        <th>Jadwal</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo format_tanggal_waktu($row['created_at']); ?></td>
                        <td>
                            <strong><?php echo $row['nama_warga']; ?></strong><br>
                            <small style="color: #666;">📱 <?php echo $row['hp_warga'] ?: '-'; ?></small>
                        </td>
                        <td><?php echo $row['nama_mitra'] ?: '<em style="color: #999;">Belum ditentukan</em>'; ?></td>
                        <td><?php echo $row['jadwal'] ? format_tanggal($row['jadwal']) : '-'; ?></td>
                        <td><?php echo $row['lokasi'] ? substr($row['lokasi'], 0, 40) . '...' : '-'; ?></td>
                        <td>
                            <span class="status <?php echo $row['status']; ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <h3>Tidak Ada Data</h3>
                <p>Tidak ada penjemputan yang sesuai dengan filter</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
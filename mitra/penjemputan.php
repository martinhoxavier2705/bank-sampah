<?php
session_start();
require_once '../koneksi.php';

check_login('mitra');

$mitra_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

$success = '';
$error = '';

// Handle Terima Penjemputan
if (isset($_GET['terima'])) {
    $penjemputan_id = clean_input($_GET['terima']);
    
    $query = "UPDATE penjemputan SET mitra_id='$mitra_id' WHERE id='$penjemputan_id' AND mitra_id IS NULL";
    
    if (mysqli_query($conn, $query)) {
        $success = "Penjemputan berhasil diterima!";
    } else {
        $error = "Gagal menerima penjemputan!";
    }
}

// Handle Mulai Jemput
if (isset($_GET['mulai'])) {
    $penjemputan_id = clean_input($_GET['mulai']);
    
    $query = "UPDATE penjemputan SET status='dijemput' WHERE id='$penjemputan_id' AND mitra_id='$mitra_id'";
    
    if (mysqli_query($conn, $query)) {
        $success = "Status berhasil diupdate ke 'Dijemput'!";
    } else {
        $error = "Gagal mengupdate status!";
    }
}

// Filter
$filter_status = isset($_GET['status']) ? clean_input($_GET['status']) : '';

// Query penjemputan baru (belum ada mitra)
$query_baru = "SELECT p.*, u.nama, u.alamat, u.no_hp 
FROM penjemputan p
JOIN users u ON p.warga_id = u.id
WHERE p.mitra_id IS NULL
ORDER BY p.created_at DESC";
$result_baru = mysqli_query($conn, $query_baru);

// Query penjemputan mitra ini
$query_mitra = "SELECT p.*, u.nama, u.alamat, u.no_hp 
FROM penjemputan p
JOIN users u ON p.warga_id = u.id
WHERE p.mitra_id = '$mitra_id'";

if (!empty($filter_status)) {
    $query_mitra .= " AND p.status = '$filter_status'";
}

$query_mitra .= " ORDER BY p.created_at DESC";
$result_mitra = mysqli_query($conn, $query_mitra);

// Statistik
$query_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'dijemput' THEN 1 ELSE 0 END) as dijemput,
    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai
FROM penjemputan WHERE mitra_id = '$mitra_id'";
$result_stats = mysqli_query($conn, $query_stats);
$stats = mysqli_fetch_assoc($result_stats);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penjemputan - Mitra</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .navbar { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-brand { font-size: 1.5em; font-weight: bold; }
        .navbar-menu { display: flex; gap: 20px; align-items: center; }
        .navbar-menu a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: all 0.3s; }
        .navbar-menu a:hover { background: rgba(255,255,255,0.2); }
        .container { max-width: 1400px; margin: 0 auto; padding: 30px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid; }
        .stat-card.total { border-color: #11998e; }
        .stat-card.pending { border-color: #ffc107; }
        .stat-card.dijemput { border-color: #17a2b8; }
        .stat-card.selesai { border-color: #28a745; }
        .stat-label { color: #666; font-size: 0.9em; margin-bottom: 10px; }
        .stat-value { font-size: 2em; font-weight: bold; color: #333; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card h2 { color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #11998e; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-danger { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .penjemputan-item { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 15px; border-left: 4px solid #11998e; }
        .penjemputan-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; }
        .penjemputan-info h3 { color: #333; margin-bottom: 5px; }
        .penjemputan-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px; }
        .detail-item { display: flex; align-items: center; gap: 8px; color: #666; }
        .status { padding: 5px 12px; border-radius: 20px; font-size: 0.85em; font-weight: 600; }
        .status.pending { background: #fff3cd; color: #856404; }
        .status.dijemput { background: #d1ecf1; color: #0c5460; }
        .status.selesai { background: #d4edda; color: #155724; }
        .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; margin-right: 5px; transition: all 0.3s; }
        .btn-success { background: #28a745; color: white; }
        .btn-primary { background: #11998e; color: white; }
        .btn-warning { background: #ffc107; color: #000; }
        .btn:hover { opacity: 0.8; transform: translateY(-2px); }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #e0e0e0; }
        .tab { padding: 12px 25px; cursor: pointer; border: none; background: none; font-weight: 600; color: #666; border-bottom: 3px solid transparent; transition: all 0.3s; text-decoration: none; }
        .tab.active { color: #11998e; border-bottom-color: #11998e; }
        .empty-state { text-align: center; padding: 60px 20px; color: #999; }
        .empty-state .icon { font-size: 5em; margin-bottom: 20px; opacity: 0.5; }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .penjemputan-header { flex-direction: column; gap: 10px; }
            .penjemputan-details { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">🚛 Mitra - Penjemputan</div>
        <div class="navbar-menu">
            <a href="dashboard.php">← Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
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
            <h2>🔔 Permintaan Penjemputan Baru</h2>
            
            <?php if (mysqli_num_rows($result_baru) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result_baru)): ?>
                <div class="penjemputan-item">
                    <div class="penjemputan-header">
                        <div class="penjemputan-info">
                            <h3><?php echo $row['nama']; ?></h3>
                            <small style="color: #999;">📅 Tanggal Request: <?php echo format_tanggal_waktu($row['created_at']); ?></small>
                        </div>
                    </div>
                    
                    <div class="penjemputan-details">
                        <div class="detail-item">
                            <span>📱</span>
                            <strong>No. HP:</strong> <?php echo $row['no_hp'] ?: '-'; ?>
                        </div>
                        <div class="detail-item">
                            <span>📍</span>
                            <strong>Alamat:</strong> <?php echo substr($row['alamat'], 0, 40) . '...'; ?>
                        </div>
                        <div class="detail-item">
                            <span>📍</span>
                            <strong>Lokasi:</strong> <?php echo $row['lokasi'] ? substr($row['lokasi'], 0, 30) . '...' : 'Sama dengan alamat'; ?>
                        </div>
                    </div>
                    
                    <button class="btn btn-success" onclick="terimaPenjemputan(<?php echo $row['id']; ?>, '<?php echo addslashes($row['nama']); ?>')">
                        ✓ Terima Penjemputan
                    </button>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <h3>Tidak Ada Permintaan Baru</h3>
                <p>Belum ada permintaan penjemputan yang tersedia</p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>📋 Daftar Penjemputan Saya</h2>
            
            <div class="tabs">
                <a href="?status=" class="tab <?php echo empty($filter_status) ? 'active' : ''; ?>">
                    Semua (<?php echo $stats['total']; ?>)
                </a>
                <a href="?status=pending" class="tab <?php echo $filter_status == 'pending' ? 'active' : ''; ?>">
                    Pending (<?php echo $stats['pending']; ?>)
                </a>
                <a href="?status=dijemput" class="tab <?php echo $filter_status == 'dijemput' ? 'active' : ''; ?>">
                    Dijemput (<?php echo $stats['dijemput']; ?>)
                </a>
                <a href="?status=selesai" class="tab <?php echo $filter_status == 'selesai' ? 'active' : ''; ?>">
                    Selesai (<?php echo $stats['selesai']; ?>)
                </a>
            </div>
            
            <?php if (mysqli_num_rows($result_mitra) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result_mitra)): ?>
                <div class="penjemputan-item">
                    <div class="penjemputan-header">
                        <div class="penjemputan-info">
                            <h3><?php echo $row['nama']; ?></h3>
                            <small style="color: #999;">📅 Request: <?php echo format_tanggal_waktu($row['created_at']); ?></small>
                        </div>
                        <span class="status <?php echo $row['status']; ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                    </div>
                    
                    <div class="penjemputan-details">
                        <div class="detail-item">
                            <span>📱</span>
                            <strong>No. HP:</strong> <?php echo $row['no_hp'] ?: '-'; ?>
                        </div>
                        <div class="detail-item">
                            <span>📍</span>
                            <strong>Alamat:</strong> <?php echo $row['alamat']; ?>
                        </div>
                        <div class="detail-item">
                            <span>📅</span>
                            <strong>Jadwal:</strong> <?php echo $row['jadwal'] ? format_tanggal($row['jadwal']) : 'Belum dijadwalkan'; ?>
                        </div>
                        <div class="detail-item">
                            <span>🗺️</span>
                            <strong>Rute:</strong> <?php echo $row['rute'] ?: '-'; ?>
                        </div>
                    </div>
                    
                    <div style="margin-top: 15px;">
                        <?php if ($row['status'] == 'pending'): ?>
                            <a href="jadwal.php?id=<?php echo $row['id']; ?>" class="btn btn-warning">
                                📅 Atur Jadwal
                            </a>
                            <button class="btn btn-primary" onclick="mulaiJemput(<?php echo $row['id']; ?>)">
                                🚛 Mulai Jemput
                            </button>
                        <?php elseif ($row['status'] == 'dijemput'): ?>
                            <a href="penimbangan.php?id=<?php echo $row['id']; ?>" class="btn btn-success">
                                ⚖️ Input Hasil Timbang
                            </a>
                        <?php else: ?>
                            <span style="color: #28a745; font-weight: 600;">✓ Penjemputan Selesai</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <h3>Tidak Ada Data</h3>
                <p>Belum ada penjemputan <?php echo $filter_status ? 'dengan status ' . $filter_status : ''; ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function terimaPenjemputan(id, nama) {
            Swal.fire({
                title: 'Konfirmasi',
                text: 'Terima penjemputan dari: ' + nama + '?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Terima!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'penjemputan.php?terima=' + id;
                }
            });
        }
        
        function mulaiJemput(id) {
            Swal.fire({
                title: 'Mulai Penjemputan',
                text: 'Ubah status menjadi "Dijemput"?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#11998e',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Mulai!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'penjemputan.php?mulai=' + id;
                }
            });
        }
    </script>
</body>
</html>
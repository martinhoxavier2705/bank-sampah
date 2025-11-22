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

$success = '';
$error = '';

// Handle Request Penjemputan Baru
if (isset($_POST['request_penjemputan'])) {
    $lokasi = clean_input($_POST['lokasi']);
    
    $query = "INSERT INTO penjemputan (warga_id, lokasi, status) VALUES ('$warga_id', '$lokasi', 'pending')";
    
    if (mysqli_query($conn, $query)) {
        $success = "Request penjemputan berhasil dikirim! Menunggu mitra menerima.";
    } else {
        $error = "Gagal mengirim request penjemputan!";
    }
}

// Handle Batalkan Request
if (isset($_GET['batalkan'])) {
    $penjemputan_id = clean_input($_GET['batalkan']);
    
    // Hanya bisa batalkan jika status masih pending
    $query = "DELETE FROM penjemputan WHERE id='$penjemputan_id' AND warga_id='$warga_id' AND status='pending'";
    
    if (mysqli_query($conn, $query)) {
        $success = "Request penjemputan berhasil dibatalkan!";
    } else {
        $error = "Gagal membatalkan request!";
    }
}

// Ambil data user
$query_user = "SELECT * FROM users WHERE id = '$warga_id'";
$result_user = mysqli_query($conn, $query_user);
$user = mysqli_fetch_assoc($result_user);

// Ambil riwayat penjemputan
$query_riwayat = "SELECT p.*, u.nama as nama_mitra 
FROM penjemputan p
LEFT JOIN users u ON p.mitra_id = u.id
WHERE p.warga_id = '$warga_id'
ORDER BY p.created_at DESC";
$result_riwayat = mysqli_query($conn, $query_riwayat);

// Statistik
$query_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'dijemput' THEN 1 ELSE 0 END) as dijemput,
    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai
FROM penjemputan WHERE warga_id = '$warga_id'";
$result_stats = mysqli_query($conn, $query_stats);
$stats = mysqli_fetch_assoc($result_stats);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penjemputan - Warga</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .navbar { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-brand { font-size: 1.5em; font-weight: bold; }
        .navbar-menu { display: flex; gap: 20px; align-items: center; }
        .navbar-menu a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: all 0.3s; }
        .navbar-menu a:hover { background: rgba(255,255,255,0.2); }
        .container { max-width: 1200px; margin: 0 auto; padding: 30px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid; }
        .stat-card.total { border-color: #f093fb; }
        .stat-card.pending { border-color: #ffc107; }
        .stat-card.dijemput { border-color: #17a2b8; }
        .stat-card.selesai { border-color: #28a745; }
        .stat-label { color: #666; font-size: 0.9em; margin-bottom: 10px; }
        .stat-value { font-size: 2em; font-weight: bold; color: #333; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card h2 { color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f093fb; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-danger { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1em; font-family: inherit; }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s; text-decoration: none; display: inline-block; }
        .btn-primary { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; width: 100%; font-size: 1.1em; }
        .btn-danger { background: #dc3545; color: white; font-size: 0.9em; padding: 8px 15px; }
        .btn-primary:hover { opacity: 0.9; transform: translateY(-2px); }
        .info-box { background: #e7f3ff; padding: 20px; border-radius: 10px; border-left: 4px solid #0066cc; margin-bottom: 20px; }
        .info-box h3 { color: #0066cc; margin-bottom: 10px; }
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .detail-item { display: flex; flex-direction: column; gap: 5px; }
        .detail-label { color: #666; font-size: 0.9em; }
        .detail-value { font-weight: 600; color: #333; }
        .riwayat-item { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 15px; border-left: 4px solid #f093fb; }
        .riwayat-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; }
        .riwayat-info h3 { color: #333; margin-bottom: 5px; }
        .status { padding: 5px 12px; border-radius: 20px; font-size: 0.85em; font-weight: 600; }
        .status.pending { background: #fff3cd; color: #856404; }
        .status.dijemput { background: #d1ecf1; color: #0c5460; }
        .status.selesai { background: #d4edda; color: #155724; }
        .riwayat-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-bottom: 10px; }
        .riwayat-detail { font-size: 0.9em; color: #666; }
        .empty-state { text-align: center; padding: 60px 20px; color: #999; }
        .empty-state .icon { font-size: 5em; margin-bottom: 20px; opacity: 0.5; }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .detail-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">🚛 Warga - Penjemputan Sampah</div>
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
                <div class="stat-label">Menunggu</div>
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
            <h2>📝 Request Penjemputan Baru</h2>
            
            <div class="info-box">
                <h3>ℹ️ Data Anda</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">👤 Nama</span>
                        <span class="detail-value"><?php echo $user['nama']; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">📱 No. HP</span>
                        <span class="detail-value"><?php echo $user['no_hp'] ?: '-'; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">📍 Alamat</span>
                        <span class="detail-value"><?php echo $user['alamat'] ?: '-'; ?></span>
                    </div>
                </div>
                <small style="color: #666; display: block; margin-top: 10px;">
                    Data di atas akan dikirim ke mitra. Pastikan data sudah benar di <a href="profil.php" style="color: #0066cc;">halaman profil</a>.
                </small>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="lokasi">📍 Lokasi Detail / Patokan (Opsional)</label>
                    <textarea id="lokasi" name="lokasi" placeholder="Contoh: Rumah warna hijau, sebelah warung makan Pak Budi. Gang masuk dari jalan utama sebelah kanan.&#10;&#10;Jika kosong, akan menggunakan alamat di profil Anda."></textarea>
                    <small style="color: #666;">Berikan detail lokasi agar mitra lebih mudah menemukan rumah Anda</small>
                </div>
                
                <button type="submit" name="request_penjemputan" class="btn btn-primary">
                    📤 Kirim Request Penjemputan
                </button>
            </form>
        </div>
        
        <div class="card">
            <h2>📋 Riwayat Penjemputan</h2>
            
            <?php if (mysqli_num_rows($result_riwayat) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result_riwayat)): ?>
                <div class="riwayat-item">
                    <div class="riwayat-header">
                        <div class="riwayat-info">
                            <h3>Penjemputan #<?php echo $row['id']; ?></h3>
                            <small style="color: #999;">📅 Request: <?php echo format_tanggal_waktu($row['created_at']); ?></small>
                        </div>
                        <span class="status <?php echo $row['status']; ?>">
                            <?php 
                            $status_text = [
                                'pending' => 'Menunggu Mitra',
                                'dijemput' => 'Sedang Dijemput',
                                'selesai' => 'Selesai'
                            ];
                            echo $status_text[$row['status']];
                            ?>
                        </span>
                    </div>
                    
                    <div class="riwayat-details">
                        <div class="riwayat-detail">
                            <strong>Mitra:</strong> <?php echo $row['nama_mitra'] ?: '<em>Belum ada mitra</em>'; ?>
                        </div>
                        <div class="riwayat-detail">
                            <strong>Jadwal:</strong> <?php echo $row['jadwal'] ? format_tanggal($row['jadwal']) : '-'; ?>
                        </div>
                        <div class="riwayat-detail">
                            <strong>Lokasi:</strong> <?php echo $row['lokasi'] ?: $user['alamat']; ?>
                        </div>
                        <?php if ($row['rute']): ?>
                        <div class="riwayat-detail">
                            <strong>Rute:</strong> <?php echo $row['rute']; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($row['status'] == 'pending' && !$row['mitra_id']): ?>
                    <button class="btn btn-danger" onclick="batalkanRequest(<?php echo $row['id']; ?>)">
                        ❌ Batalkan Request
                    </button>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <h3>Belum Ada Riwayat</h3>
                <p>Anda belum pernah request penjemputan</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function batalkanRequest(id) {
            Swal.fire({
                title: 'Batalkan Request?',
                text: 'Request penjemputan akan dibatalkan dan dihapus dari sistem.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Batalkan!',
                cancelButtonText: 'Tidak'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'penjemputan.php?batalkan=' + id;
                }
            });
        }
    </script>
</body>
</html>
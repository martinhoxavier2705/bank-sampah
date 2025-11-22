<?php
session_start();
require_once '../koneksi.php';

check_login('mitra');

$mitra_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

$success = '';
$error = '';

// Handle Update Jadwal
if (isset($_POST['update_jadwal'])) {
    $penjemputan_id = clean_input($_POST['penjemputan_id']);
    $jadwal = clean_input($_POST['jadwal']);
    $rute = clean_input($_POST['rute']);
    
    $query = "UPDATE penjemputan SET jadwal='$jadwal', rute='$rute' 
              WHERE id='$penjemputan_id' AND mitra_id='$mitra_id'";
    
    if (mysqli_query($conn, $query)) {
        $success = "Jadwal berhasil diatur!";
    } else {
        $error = "Gagal mengatur jadwal!";
    }
}

// Get penjemputan ID dari URL
$penjemputan_id = isset($_GET['id']) ? clean_input($_GET['id']) : 0;

// Ambil data penjemputan
$query_penjemputan = "SELECT p.*, u.nama, u.alamat, u.no_hp 
FROM penjemputan p
JOIN users u ON p.warga_id = u.id
WHERE p.id = '$penjemputan_id' AND p.mitra_id = '$mitra_id'";
$result_penjemputan = mysqli_query($conn, $query_penjemputan);

if (mysqli_num_rows($result_penjemputan) == 0) {
    header("Location: penjemputan.php");
    exit();
}

$penjemputan = mysqli_fetch_assoc($result_penjemputan);

// Ambil jadwal penjemputan hari ini
$query_today = "SELECT p.*, u.nama, u.alamat 
FROM penjemputan p
JOIN users u ON p.warga_id = u.id
WHERE p.mitra_id = '$mitra_id' 
AND p.jadwal = CURDATE()
ORDER BY p.jadwal ASC";
$result_today = mysqli_query($conn, $query_today);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atur Jadwal - Mitra</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .navbar { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-brand { font-size: 1.5em; font-weight: bold; }
        .navbar-menu { display: flex; gap: 20px; align-items: center; }
        .navbar-menu a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: all 0.3s; }
        .navbar-menu a:hover { background: rgba(255,255,255,0.2); }
        .container { max-width: 1200px; margin: 0 auto; padding: 30px; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card h2 { color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #11998e; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-danger { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .info-box { background: #e7f3ff; padding: 20px; border-radius: 10px; border-left: 4px solid #0066cc; margin-bottom: 20px; }
        .info-box h3 { color: #0066cc; margin-bottom: 10px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1em; font-family: inherit; }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; transition: all 0.3s; }
        .btn-primary { background: #11998e; color: white; width: 100%; font-size: 1.1em; }
        .btn-primary:hover { background: #0e7c73; }
        .detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; padding: 20px; background: #f8f9fa; border-radius: 10px; margin-bottom: 20px; }
        .detail-item { display: flex; flex-direction: column; gap: 5px; }
        .detail-label { color: #666; font-size: 0.9em; }
        .detail-value { font-weight: 600; color: #333; }
        .schedule-item { background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 10px; border-left: 4px solid #11998e; }
        .schedule-item h4 { color: #333; margin-bottom: 5px; }
        .schedule-item p { color: #666; font-size: 0.9em; }
        @media (max-width: 768px) {
            .detail-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">📅 Mitra - Atur Jadwal</div>
        <div class="navbar-menu">
            <a href="penjemputan.php">← Kembali</a>
            <a href="dashboard.php">Dashboard</a>
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
        
        <div class="card">
            <h2>📅 Atur Jadwal Penjemputan</h2>
            
            <div class="info-box">
                <h3>ℹ️ Informasi Warga</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">👤 Nama Warga</span>
                        <span class="detail-value"><?php echo $penjemputan['nama']; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">📱 No. HP</span>
                        <span class="detail-value"><?php echo $penjemputan['no_hp'] ?: '-'; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">📍 Alamat</span>
                        <span class="detail-value"><?php echo $penjemputan['alamat']; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">📅 Tanggal Request</span>
                        <span class="detail-value"><?php echo format_tanggal($penjemputan['created_at']); ?></span>
                    </div>
                </div>
            </div>
            
            <form method="POST">
                <input type="hidden" name="penjemputan_id" value="<?php echo $penjemputan['id']; ?>">
                
                <div class="form-group">
                    <label for="jadwal">📅 Jadwal Penjemputan *</label>
                    <input type="date" id="jadwal" name="jadwal" value="<?php echo $penjemputan['jadwal']; ?>" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="rute">🗺️ Rute / Catatan</label>
                    <textarea id="rute" name="rute" placeholder="Contoh: Rute A - Jalan Utama -> Gang 5 -> Rumah No. 10"><?php echo $penjemputan['rute']; ?></textarea>
                    <small style="color: #666;">Opsional: Tulis rute atau catatan untuk memudahkan penjemputan</small>
                </div>
                
                <button type="submit" name="update_jadwal" class="btn btn-primary">
                    💾 Simpan Jadwal
                </button>
            </form>
        </div>
        
        <?php if (mysqli_num_rows($result_today) > 0): ?>
        <div class="card">
            <h2>📋 Jadwal Penjemputan Hari Ini</h2>
            <?php while($row = mysqli_fetch_assoc($result_today)): ?>
            <div class="schedule-item">
                <h4><?php echo $row['nama']; ?></h4>
                <p>📍 <?php echo $row['alamat']; ?></p>
                <?php if ($row['rute']): ?>
                <p>🗺️ <?php echo $row['rute']; ?></p>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
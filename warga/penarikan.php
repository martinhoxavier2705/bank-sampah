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

// Ambil saldo
$saldo = get_saldo_warga($warga_id);

// Handle Request Penarikan
if (isset($_POST['request_penarikan'])) {
    $jumlah = clean_input($_POST['jumlah']);
    $metode = clean_input($_POST['metode']);
    
    if ($jumlah <= 0) {
        $error = "Jumlah penarikan harus lebih dari 0!";
    } elseif ($jumlah > $saldo) {
        $error = "Saldo tidak mencukupi! Saldo Anda: " . format_rupiah($saldo);
    } else {
        $query = "INSERT INTO penarikan_saldo (warga_id, jumlah, metode, status) 
                  VALUES ('$warga_id', '$jumlah', '$metode', 'pending')";
        
        if (mysqli_query($conn, $query)) {
            $success = "Request penarikan berhasil! Menunggu verifikasi admin.";
        } else {
            $error = "Gagal mengirim request penarikan!";
        }
    }
}

// Ambil riwayat penarikan
$query_riwayat = "SELECT * FROM penarikan_saldo WHERE warga_id = '$warga_id' ORDER BY tanggal DESC";
$result_riwayat = mysqli_query($conn, $query_riwayat);

// Statistik
$query_stats = "SELECT 
    COUNT(*) as total_request,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
    COALESCE(SUM(CASE WHEN status = 'selesai' THEN jumlah ELSE 0 END), 0) as total_ditarik
FROM penarikan_saldo WHERE warga_id = '$warga_id'";
$result_stats = mysqli_query($conn, $query_stats);
$stats = mysqli_fetch_assoc($result_stats);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penarikan Saldo - Warga</title>
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
        .content-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .saldo-card { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 40px; border-radius: 20px; text-align: center; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(240, 147, 251, 0.3); }
        .saldo-label { font-size: 1.2em; margin-bottom: 10px; opacity: 0.9; }
        .saldo-value { font-size: 3.5em; font-weight: bold; margin-bottom: 10px; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card h2 { color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f093fb; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-danger { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .alert-warning { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1em; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #f093fb; }
        .btn-primary { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; width: 100%; font-size: 1.1em; padding: 15px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-primary:hover { opacity: 0.9; transform: translateY(-2px); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center; }
        .stat-value { font-size: 2em; font-weight: bold; color: #f093fb; }
        .stat-label { color: #666; font-size: 0.9em; margin-top: 5px; }
        .riwayat-item { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 15px; border-left: 4px solid; }
        .riwayat-item.pending { border-color: #ffc107; }
        .riwayat-item.verifikasi { border-color: #17a2b8; }
        .riwayat-item.selesai { border-color: #28a745; }
        .riwayat-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px; }
        .status { padding: 5px 12px; border-radius: 20px; font-size: 0.85em; font-weight: 600; }
        .status.pending { background: #fff3cd; color: #856404; }
        .status.verifikasi { background: #d1ecf1; color: #0c5460; }
        .status.selesai { background: #d4edda; color: #155724; }
        .info-box { background: #e7f3ff; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .info-box ul { margin-left: 20px; margin-top: 10px; }
        .info-box li { margin: 5px 0; }
        .empty-state { text-align: center; padding: 40px 20px; color: #999; }
        @media (max-width: 968px) {
            .content-grid { grid-template-columns: 1fr; }
            .saldo-value { font-size: 2.5em; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">💳 Warga - Penarikan Saldo</div>
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
        
        <div class="saldo-card">
            <div class="saldo-label">💰 Saldo Tersedia</div>
            <div class="saldo-value"><?php echo format_rupiah($saldo); ?></div>
            <small style="opacity: 0.9;">Saldo yang bisa Anda tarik saat ini</small>
        </div>
        
        <div class="content-grid">
            <div>
                <div class="card">
                    <h2>💳 Form Penarikan Saldo</h2>
                    
                    <?php if ($saldo < 10000): ?>
                    <div class="alert alert-warning">
                        ⚠️ Saldo Anda kurang dari Rp 10.000. Minimal penarikan adalah Rp 10.000.
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-box">
                        <strong>ℹ️ Informasi Penarikan:</strong>
                        <ul>
                            <li>Minimal penarikan: <strong>Rp 10.000</strong></li>
                            <li>Maksimal penarikan: <strong>Sesuai saldo Anda</strong></li>
                            <li>Proses verifikasi: <strong>1-3 hari kerja</strong></li>
                            <li>Transfer akan dikirim ke rekening/e-wallet Anda</li>
                        </ul>
                    </div>
                    
                    <form method="POST" id="formPenarikan">
                        <div class="form-group">
                            <label for="jumlah">💰 Jumlah Penarikan (Rp) *</label>
                            <input type="number" id="jumlah" name="jumlah" required min="10000" max="<?php echo $saldo; ?>" step="1000" placeholder="Minimal Rp 10.000">
                            <small style="color: #666;">Maksimal: <?php echo format_rupiah($saldo); ?></small>
                        </div>
                        
                        <div class="form-group">
                            <label for="metode">💳 Metode Penarikan *</label>
                            <select id="metode" name="metode" required>
                                <option value="">-- Pilih Metode --</option>
                                <option value="rekening">Transfer Bank / Rekening</option>
                                <option value="ewallet">E-Wallet (OVO, GoPay, DANA, dll)</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="request_penarikan" class="btn-primary" <?php echo $saldo < 10000 ? 'disabled' : ''; ?>>
                            📤 Kirim Request Penarikan
                        </button>
                    </form>
                </div>
            </div>
            
            <div>
                <div class="card">
                    <h2>📊 Statistik Penarikan</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['total_request']; ?></div>
                            <div class="stat-label">Total Request</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['pending']; ?></div>
                            <div class="stat-label">Pending</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['selesai']; ?></div>
                            <div class="stat-label">Selesai</div>
                        </div>
                    </div>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; margin-top: 10px;">
                        <div style="color: #666; font-size: 0.9em;">Total yang Sudah Ditarik</div>
                        <div style="font-size: 1.8em; font-weight: bold; color: #28a745; margin-top: 5px;">
                            <?php echo format_rupiah($stats['total_ditarik']); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>📋 Riwayat Penarikan</h2>
            
            <?php if (mysqli_num_rows($result_riwayat) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result_riwayat)): ?>
                <div class="riwayat-item <?php echo $row['status']; ?>">
                    <div class="riwayat-header">
                        <div>
                            <h3 style="color: #333; margin-bottom: 5px;"><?php echo format_rupiah($row['jumlah']); ?></h3>
                            <small style="color: #666;">
                                📅 <?php echo format_tanggal_waktu($row['tanggal']); ?> • 
                                💳 <?php echo ucfirst($row['metode']); ?>
                            </small>
                        </div>
                        <span class="status <?php echo $row['status']; ?>">
                            <?php 
                            $status_text = [
                                'pending' => '⏳ Menunggu Verifikasi',
                                'verifikasi' => '🔄 Sedang Diproses',
                                'selesai' => '✅ Selesai'
                            ];
                            echo $status_text[$row['status']];
                            ?>
                        </span>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
            <div class="empty-state">
                <p>Belum ada riwayat penarikan</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Validasi form
        document.getElementById('formPenarikan').addEventListener('submit', function(e) {
            const jumlah = parseInt(document.getElementById('jumlah').value);
            const saldo = <?php echo $saldo; ?>;
            
            if (jumlah < 10000) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Jumlah Tidak Valid',
                    text: 'Minimal penarikan adalah Rp 10.000',
                    confirmButtonColor: '#f093fb'
                });
                return false;
            }
            
            if (jumlah > saldo) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Saldo Tidak Cukup',
                    text: 'Saldo Anda tidak mencukupi untuk penarikan ini',
                    confirmButtonColor: '#f093fb'
                });
                return false;
            }
        });
    </script>
</body>
</html>
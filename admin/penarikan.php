<?php
session_start();
require_once '../koneksi.php';

// Cek login
check_login('admin');

$admin_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

$success = '';
$error = '';

// Handle Approve
if (isset($_GET['approve'])) {
    $penarikan_id = clean_input($_GET['approve']);
    
    // Ambil data penarikan
    $query = "SELECT * FROM penarikan_saldo WHERE id = '$penarikan_id'";
    $result = mysqli_query($conn, $query);
    $penarikan = mysqli_fetch_assoc($result);
    
    if ($penarikan) {
        // Cek saldo warga
        $saldo_warga = get_saldo_warga($penarikan['warga_id']);
        
        if ($saldo_warga >= $penarikan['jumlah']) {
            // Kurangi saldo
            if (kurangi_saldo_warga($penarikan['warga_id'], $penarikan['jumlah'])) {
                // Update status penarikan
                $update = "UPDATE penarikan_saldo SET status = 'selesai' WHERE id = '$penarikan_id'";
                if (mysqli_query($conn, $update)) {
                    $success = "Penarikan berhasil diapprove dan saldo telah dikurangi!";
                } else {
                    $error = "Gagal update status penarikan!";
                }
            } else {
                $error = "Gagal mengurangi saldo warga!";
            }
        } else {
            $error = "Saldo warga tidak mencukupi!";
        }
    } else {
        $error = "Data penarikan tidak ditemukan!";
    }
}

// Handle Reject
if (isset($_GET['reject'])) {
    $penarikan_id = clean_input($_GET['reject']);
    
    $query = "DELETE FROM penarikan_saldo WHERE id = '$penarikan_id'";
    if (mysqli_query($conn, $query)) {
        $success = "Permintaan penarikan ditolak!";
    } else {
        $error = "Gagal menolak permintaan!";
    }
}

// Ambil data penarikan berdasarkan status
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$query_penarikan = "SELECT ps.*, u.nama, u.email, u.no_hp, t.saldo
FROM penarikan_saldo ps
JOIN users u ON ps.warga_id = u.id
LEFT JOIN tabungan t ON ps.warga_id = t.warga_id
WHERE ps.status = '$status_filter'
ORDER BY ps.tanggal DESC";
$result_penarikan = mysqli_query($conn, $query_penarikan);

// Statistik
$query_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'verifikasi' THEN 1 ELSE 0 END) as verifikasi,
    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
    COALESCE(SUM(CASE WHEN status = 'selesai' THEN jumlah ELSE 0 END), 0) as total_selesai
FROM penarikan_saldo";
$result_stats = mysqli_query($conn, $query_stats);
$stats = mysqli_fetch_assoc($result_stats);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Penarikan - Admin Bank Sampah</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        
        .navbar-menu {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .navbar-menu a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .navbar-menu a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
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
        
        .stat-card.pending { border-color: #ffc107; }
        .stat-card.verifikasi { border-color: #17a2b8; }
        .stat-card.selesai { border-color: #28a745; }
        .stat-card.total { border-color: #667eea; }
        
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
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .card h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .tab {
            padding: 12px 25px;
            cursor: pointer;
            border: none;
            background: none;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
        }
        
        table td {
            padding: 15px;
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
        
        .status.verifikasi {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status.selesai {
            background: #d4edda;
            color: #155724;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85em;
            margin-right: 5px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.8;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .metode-badge {
            padding: 4px 10px;
            background: #e7f3ff;
            color: #0066cc;
            border-radius: 5px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state .icon {
            font-size: 5em;
            margin-bottom: 20px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">💳 Admin - Penarikan Saldo</div>
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
            <div class="stat-card pending">
                <div class="stat-label">Pending</div>
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
            </div>
            
            <div class="stat-card verifikasi">
                <div class="stat-label">Verifikasi</div>
                <div class="stat-value"><?php echo $stats['verifikasi']; ?></div>
            </div>
            
            <div class="stat-card selesai">
                <div class="stat-label">Selesai</div>
                <div class="stat-value"><?php echo $stats['selesai']; ?></div>
            </div>
            
            <div class="stat-card total">
                <div class="stat-label">Total Pencairan</div>
                <div class="stat-value"><?php echo format_rupiah($stats['total_selesai']); ?></div>
            </div>
        </div>
        
        <div class="card">
            <h2>💳 Verifikasi Penarikan Saldo</h2>
            
            <div class="tabs">
                <a href="?status=pending" class="tab <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">
                    Pending (<?php echo $stats['pending']; ?>)
                </a>
                <a href="?status=verifikasi" class="tab <?php echo $status_filter == 'verifikasi' ? 'active' : ''; ?>">
                    Verifikasi (<?php echo $stats['verifikasi']; ?>)
                </a>
                <a href="?status=selesai" class="tab <?php echo $status_filter == 'selesai' ? 'active' : ''; ?>">
                    Selesai (<?php echo $stats['selesai']; ?>)
                </a>
            </div>
            
            <?php if (mysqli_num_rows($result_penarikan) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Warga</th>
                            <th>Saldo Saat Ini</th>
                            <th>Jumlah Penarikan</th>
                            <th>Metode</th>
                            <th>Status</th>
                            <?php if ($status_filter == 'pending'): ?>
                            <th>Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result_penarikan)): ?>
                        <tr>
                            <td><?php echo format_tanggal_waktu($row['tanggal']); ?></td>
                            <td>
                                <strong><?php echo $row['nama']; ?></strong><br>
                                <small style="color: #666;">
                                    📱 <?php echo $row['no_hp'] ?: '-'; ?><br>
                                    📧 <?php echo $row['email'] ?: '-'; ?>
                                </small>
                            </td>
                            <td>
                                <strong style="color: #28a745;"><?php echo format_rupiah($row['saldo']); ?></strong>
                            </td>
                            <td>
                                <strong style="font-size: 1.1em; color: #dc3545;">
                                    <?php echo format_rupiah($row['jumlah']); ?>
                                </strong>
                            </td>
                            <td>
                                <span class="metode-badge">
                                    <?php echo ucfirst($row['metode']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status <?php echo $row['status']; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <?php if ($status_filter == 'pending'): ?>
                            <td>
                                <?php if ($row['saldo'] >= $row['jumlah']): ?>
                                    <button class="btn btn-success" onclick="approveWithdrawal(<?php echo $row['id']; ?>, '<?php echo $row['nama']; ?>', <?php echo $row['jumlah']; ?>)">
                                        ✓ Approve
                                    </button>
                                    <button class="btn btn-danger" onclick="rejectWithdrawal(<?php echo $row['id']; ?>, '<?php echo $row['nama']; ?>')">
                                        ✗ Tolak
                                    </button>
                                <?php else: ?>
                                    <span style="color: #dc3545; font-weight: 600;">
                                        ⚠️ Saldo Tidak Cukup
                                    </span>
                                    <button class="btn btn-danger" onclick="rejectWithdrawal(<?php echo $row['id']; ?>, '<?php echo $row['nama']; ?>')">
                                        ✗ Tolak
                                    </button>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="icon">📭</div>
                    <h3>Tidak Ada Data</h3>
                    <p>Tidak ada penarikan dengan status <?php echo ucfirst($status_filter); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function approveWithdrawal(id, nama, jumlah) {
            Swal.fire({
                title: 'Konfirmasi Approve',
                html: `
                    Approve penarikan untuk:<br>
                    <strong>${nama}</strong><br>
                    Jumlah: <strong style="color: #dc3545;">${formatRupiah(jumlah)}</strong>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Approve!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'penarikan.php?approve=' + id;
                }
            });
        }
        
        function rejectWithdrawal(id, nama) {
            Swal.fire({
                title: 'Konfirmasi Tolak',
                text: 'Tolak penarikan untuk: ' + nama + '?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Tolak!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'penarikan.php?reject=' + id;
                }
            });
        }
        
        function formatRupiah(angka) {
            return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
    </script>
</body>
</html>
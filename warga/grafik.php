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

// Data untuk grafik (12 bulan terakhir)
$query_grafik = "SELECT 
    DATE_FORMAT(tanggal, '%Y-%m') as bulan,
    COUNT(*) as jumlah_transaksi,
    SUM(berat_kg) as total_berat,
    SUM(total_uang) as total_uang
FROM transaksi_sampah
WHERE warga_id = '$warga_id'
AND tanggal >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
ORDER BY bulan ASC";
$result_grafik = mysqli_query($conn, $query_grafik);

$data_grafik = array();
while ($row = mysqli_fetch_assoc($result_grafik)) {
    $data_grafik[] = $row;
}

// Statistik total
$query_total = "SELECT 
    COUNT(*) as total_transaksi,
    COALESCE(SUM(berat_kg), 0) as total_berat,
    COALESCE(SUM(total_uang), 0) as total_pendapatan
FROM transaksi_sampah WHERE warga_id = '$warga_id'";
$result_total = mysqli_query($conn, $query_total);
$total = mysqli_fetch_assoc($result_total);

// Rata-rata per bulan
$avg_per_bulan = count($data_grafik) > 0 ? $total['total_pendapatan'] / count($data_grafik) : 0;

// Bulan dengan pendapatan tertinggi
$query_max = "SELECT 
    DATE_FORMAT(tanggal, '%M %Y') as bulan,
    SUM(total_uang) as total
FROM transaksi_sampah
WHERE warga_id = '$warga_id'
GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
ORDER BY total DESC
LIMIT 1";
$result_max = mysqli_query($conn, $query_max);
$max_bulan = mysqli_fetch_assoc($result_max);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik Tabungan - Warga</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid; }
        .stat-card.transaksi { border-color: #f093fb; }
        .stat-card.berat { border-color: #43e97b; }
        .stat-card.avg { border-color: #ffc107; }
        .stat-label { color: #666; font-size: 0.9em; margin-bottom: 10px; }
        .stat-value { font-size: 2em; font-weight: bold; color: #333; }
        .stat-subtext { color: #999; font-size: 0.85em; margin-top: 5px; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card h2 { color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f093fb; }
        .chart-container { position: relative; height: 400px; margin-top: 20px; }
        .insight-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 15px; margin-bottom: 20px; }
        .insight-box h3 { margin-bottom: 15px; }
        .insight-item { background: rgba(255,255,255,0.1); padding: 15px; border-radius: 10px; margin-bottom: 10px; }
        .insight-item:last-child { margin-bottom: 0; }
        .motivasi-box { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 30px; border-radius: 15px; text-align: center; }
        .motivasi-box h3 { font-size: 1.5em; margin-bottom: 10px; }
        .motivasi-box p { font-size: 1.1em; opacity: 0.9; }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
            .saldo-value { font-size: 2.5em; }
            .chart-container { height: 300px; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">📈 Warga - Grafik Tabungan</div>
        <div class="navbar-menu">
            <a href="dashboard.php">← Dashboard</a>
            <a href="tabungan.php">Riwayat</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="saldo-card">
            <div class="saldo-label">💰 Saldo Tabungan Saat Ini</div>
            <div class="saldo-value"><?php echo format_rupiah($saldo); ?></div>
            <p style="opacity: 0.9;">Terus kumpulkan sampah untuk menambah tabungan! 🌱</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card transaksi">
                <div class="stat-label">Total Transaksi</div>
                <div class="stat-value"><?php echo $total['total_transaksi']; ?></div>
                <div class="stat-subtext">kali transaksi</div>
            </div>
            <div class="stat-card berat">
                <div class="stat-label">Total Berat Sampah</div>
                <div class="stat-value"><?php echo number_format($total['total_berat'], 1); ?> kg</div>
                <div class="stat-subtext">sampah yang terkumpul</div>
            </div>
            <div class="stat-card avg">
                <div class="stat-label">Rata-rata per Bulan</div>
                <div class="stat-value"><?php echo format_rupiah($avg_per_bulan); ?></div>
                <div class="stat-subtext">pendapatan bulanan</div>
            </div>
        </div>
        
        <?php if (count($data_grafik) > 0): ?>
        <div class="card">
            <h2>📊 Grafik Perkembangan Pendapatan (12 Bulan Terakhir)</h2>
            <div class="chart-container">
                <canvas id="chartPendapatan"></canvas>
            </div>
        </div>
        
        <div class="card">
            <h2>⚖️ Grafik Berat Sampah (12 Bulan Terakhir)</h2>
            <div class="chart-container">
                <canvas id="chartBerat"></canvas>
            </div>
        </div>
        
        <div class="insight-box">
            <h3>💡 Insight & Pencapaian</h3>
            <?php if ($max_bulan): ?>
            <div class="insight-item">
                <strong>🏆 Bulan Terbaik:</strong><br>
                <?php echo $max_bulan['bulan']; ?> dengan pendapatan <?php echo format_rupiah($max_bulan['total']); ?>
            </div>
            <?php endif; ?>
            <div class="insight-item">
                <strong>📈 Total Pendapatan:</strong><br>
                Anda telah menghasilkan <?php echo format_rupiah($total['total_pendapatan']); ?> dari <?php echo $total['total_transaksi']; ?> transaksi
            </div>
            <div class="insight-item">
                <strong>♻️ Kontribusi Lingkungan:</strong><br>
                Anda telah mengumpulkan <?php echo number_format($total['total_berat'], 1); ?> kg sampah untuk didaur ulang
            </div>
        </div>
        
        <div class="motivasi-box">
            <h3>🌟 Semangat!</h3>
            <p>Setiap sampah yang Anda kumpulkan adalah kontribusi nyata untuk lingkungan yang lebih bersih dan hijau. Teruskan kebiasaan baik ini!</p>
        </div>
        
        <?php else: ?>
        <div class="card" style="text-align: center; padding: 60px 20px;">
            <div style="font-size: 5em; margin-bottom: 20px; opacity: 0.5;">📊</div>
            <h3 style="color: #999; margin-bottom: 10px;">Belum Ada Data</h3>
            <p style="color: #999;">Mulai kumpulkan sampah untuk melihat grafik perkembangan tabungan Anda</p>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (count($data_grafik) > 0): ?>
    <script>
        // Data dari PHP
        const dataGrafik = <?php echo json_encode($data_grafik); ?>;
        
        // Prepare data untuk chart
        const labels = dataGrafik.map(item => {
            const date = new Date(item.bulan + '-01');
            return date.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' });
        });
        const dataPendapatan = dataGrafik.map(item => parseFloat(item.total_uang));
        const dataBerat = dataGrafik.map(item => parseFloat(item.total_berat));
        
        // Chart Pendapatan
        const ctxPendapatan = document.getElementById('chartPendapatan').getContext('2d');
        new Chart(ctxPendapatan, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: dataPendapatan,
                    borderColor: 'rgb(240, 147, 251)',
                    backgroundColor: 'rgba(240, 147, 251, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Pendapatan: Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
        
        // Chart Berat
        const ctxBerat = document.getElementById('chartBerat').getContext('2d');
        new Chart(ctxBerat, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Berat Sampah (kg)',
                    data: dataBerat,
                    backgroundColor: 'rgba(67, 233, 123, 0.7)',
                    borderColor: 'rgb(67, 233, 123)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Berat: ' + context.parsed.y.toFixed(2) + ' kg';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + ' kg';
                            }
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
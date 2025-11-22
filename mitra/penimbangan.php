<?php
session_start();
require_once '../koneksi.php';

check_login('mitra');

$mitra_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

$success = '';
$error = '';

// Handle Input Penimbangan
if (isset($_POST['input_penimbangan'])) {
    $penjemputan_id = clean_input($_POST['penjemputan_id']);
    $warga_id = clean_input($_POST['warga_id']);
    $berat_kg = clean_input($_POST['berat_kg']);
    $harga_per_kg = clean_input($_POST['harga_per_kg']);
    
    $hasil_uang = $berat_kg * $harga_per_kg;
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Insert ke tabel penimbangan
        $query1 = "INSERT INTO penimbangan (penjemputan_id, berat_kg, hasil_uang) 
                   VALUES ('$penjemputan_id', '$berat_kg', '$hasil_uang')";
        mysqli_query($conn, $query1);
        
        // Insert ke tabel transaksi_sampah
        $query2 = "INSERT INTO transaksi_sampah (warga_id, berat_kg, harga_per_kg, total_uang) 
                   VALUES ('$warga_id', '$berat_kg', '$harga_per_kg', '$hasil_uang')";
        mysqli_query($conn, $query2);
        
        // Update saldo warga
        update_saldo_warga($warga_id, $hasil_uang);
        
        // Update status penjemputan menjadi selesai
        $query3 = "UPDATE penjemputan SET status='selesai' WHERE id='$penjemputan_id'";
        mysqli_query($conn, $query3);
        
        // Commit transaction
        mysqli_commit($conn);
        
        $success = "Hasil penimbangan berhasil disimpan! Saldo warga telah ditambahkan.";
    } catch (Exception $e) {
        // Rollback jika ada error
        mysqli_rollback($conn);
        $error = "Gagal menyimpan hasil penimbangan!";
    }
}

// Get penjemputan ID dari URL
$penjemputan_id = isset($_GET['id']) ? clean_input($_GET['id']) : 0;

// Ambil data penjemputan
$query_penjemputan = "SELECT p.*, u.nama, u.alamat, u.no_hp 
FROM penjemputan p
JOIN users u ON p.warga_id = u.id
WHERE p.id = '$penjemputan_id' AND p.mitra_id = '$mitra_id' AND p.status = 'dijemput'";
$result_penjemputan = mysqli_query($conn, $query_penjemputan);

if (mysqli_num_rows($result_penjemputan) == 0) {
    header("Location: penjemputan.php");
    exit();
}

$penjemputan = mysqli_fetch_assoc($result_penjemputan);

// Ambil daftar harga sampah
$query_harga = "SELECT * FROM jenis_sampah ORDER BY nama_jenis ASC";
$result_harga = mysqli_query($conn, $query_harga);

// Ambil riwayat penimbangan
$query_riwayat = "SELECT pen.*, p.warga_id, u.nama as nama_warga
FROM penimbangan pen
JOIN penjemputan p ON pen.penjemputan_id = p.id
JOIN users u ON p.warga_id = u.id
WHERE p.mitra_id = '$mitra_id'
ORDER BY pen.tanggal DESC
LIMIT 10";
$result_riwayat = mysqli_query($conn, $query_riwayat);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Penimbangan - Mitra</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .navbar { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-brand { font-size: 1.5em; font-weight: bold; }
        .navbar-menu { display: flex; gap: 20px; align-items: center; }
        .navbar-menu a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: all 0.3s; }
        .navbar-menu a:hover { background: rgba(255,255,255,0.2); }
        .container { max-width: 1200px; margin: 0 auto; padding: 30px; }
        .content-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card h2 { color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #11998e; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-danger { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .info-box { background: #e7f3ff; padding: 20px; border-radius: 10px; border-left: 4px solid #0066cc; margin-bottom: 20px; }
        .info-box h3 { color: #0066cc; margin-bottom: 10px; }
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; padding: 20px; background: #f8f9fa; border-radius: 10px; margin-bottom: 20px; }
        .detail-item { display: flex; flex-direction: column; gap: 5px; }
        .detail-label { color: #666; font-size: 0.9em; }
        .detail-value { font-weight: 600; color: #333; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1em; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #11998e; }
        .result-box { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 30px; border-radius: 15px; text-align: center; margin: 20px 0; }
        .result-box h3 { margin-bottom: 10px; }
        .result-value { font-size: 3em; font-weight: bold; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; transition: all 0.3s; }
        .btn-success { background: #28a745; color: white; width: 100%; font-size: 1.1em; }
        .btn-success:hover { background: #218838; }
        .price-list { background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .price-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e0e0e0; }
        .price-item:last-child { border-bottom: none; }
        .riwayat-item { background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 10px; border-left: 4px solid #11998e; }
        .riwayat-item h4 { color: #333; margin-bottom: 5px; }
        .riwayat-item p { color: #666; font-size: 0.9em; }
        @media (max-width: 968px) {
            .content-grid { grid-template-columns: 1fr; }
            .detail-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">⚖️ Mitra - Input Penimbangan</div>
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
        
        <div class="content-grid">
            <div>
                <div class="card">
                    <h2>⚖️ Input Hasil Penimbangan</h2>
                    
                    <div class="info-box">
                        <h3>ℹ️ Informasi Warga</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">👤 Nama</span>
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
                                <span class="detail-label">📅 Jadwal</span>
                                <span class="detail-value"><?php echo $penjemputan['jadwal'] ? format_tanggal($penjemputan['jadwal']) : '-'; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" id="formPenimbangan">
                        <input type="hidden" name="penjemputan_id" value="<?php echo $penjemputan['id']; ?>">
                        <input type="hidden" name="warga_id" value="<?php echo $penjemputan['warga_id']; ?>">
                        
                        <div class="form-group">
                            <label for="berat_kg">⚖️ Berat Sampah (kg) *</label>
                            <input type="number" id="berat_kg" name="berat_kg" step="0.01" required min="0.1" placeholder="Contoh: 5.5">
                        </div>
                        
                        <div class="form-group">
                            <label for="harga_per_kg">💰 Harga per Kg (Rp) *</label>
                            <select id="harga_per_kg" name="harga_per_kg" required>
                                <option value="">-- Pilih Jenis Sampah --</option>
                                <?php while($harga = mysqli_fetch_assoc($result_harga)): ?>
                                <option value="<?php echo $harga['harga_per_kg']; ?>">
                                    <?php echo $harga['nama_jenis']; ?> - <?php echo format_rupiah($harga['harga_per_kg']); ?>/kg
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="result-box" id="resultBox" style="display: none;">
                            <h3>💰 Total Nilai</h3>
                            <div class="result-value" id="totalNilai">Rp 0</div>
                        </div>
                        
                        <button type="submit" name="input_penimbangan" class="btn btn-success">
                            💾 Simpan & Tambah ke Saldo Warga
                        </button>
                    </form>
                </div>
            </div>
            
            <div>
                <div class="card">
                    <h2>💲 Referensi Harga Sampah</h2>
                    <div class="price-list">
                        <?php 
                        mysqli_data_seek($result_harga, 0);
                        while($harga = mysqli_fetch_assoc($result_harga)): 
                        ?>
                        <div class="price-item">
                            <strong>♻️ <?php echo $harga['nama_jenis']; ?></strong>
                            <span style="color: #28a745; font-weight: bold;">
                                <?php echo format_rupiah($harga['harga_per_kg']); ?>/kg
                            </span>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <div class="card">
                    <h2>📋 Riwayat Penimbangan Terbaru</h2>
                    <?php if (mysqli_num_rows($result_riwayat) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result_riwayat)): ?>
                        <div class="riwayat-item">
                            <h4><?php echo $row['nama_warga']; ?></h4>
                            <p>⚖️ <?php echo number_format($row['berat_kg'], 2); ?> kg</p>
                            <p>💰 <?php echo format_rupiah($row['hasil_uang']); ?></p>
                            <p style="color: #999;">📅 <?php echo format_tanggal_waktu($row['tanggal']); ?></p>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #999; padding: 20px;">Belum ada riwayat</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Hitung total nilai secara real-time
        document.getElementById('berat_kg').addEventListener('input', hitungTotal);
        document.getElementById('harga_per_kg').addEventListener('change', hitungTotal);
        
        function hitungTotal() {
            const berat = parseFloat(document.getElementById('berat_kg').value) || 0;
            const harga = parseFloat(document.getElementById('harga_per_kg').value) || 0;
            const total = berat * harga;
            
            if (total > 0) {
                document.getElementById('resultBox').style.display = 'block';
                document.getElementById('totalNilai').textContent = formatRupiah(total);
            } else {
                document.getElementById('resultBox').style.display = 'none';
            }
        }
        
        
        function formatRupiah(angka) {
            return 'Rp ' + angka.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
    </script>
</body>
</html>
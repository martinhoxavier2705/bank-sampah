<?php
session_start();
require_once '../koneksi.php';

check_login('admin');

$admin_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

$success = '';
$error = '';

// Cek apakah tabel jenis_sampah ada
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'jenis_sampah'");

if (mysqli_num_rows($check_table) == 0) {
    // Buat tabel jika belum ada
    $create_table = "CREATE TABLE jenis_sampah (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        nama_jenis VARCHAR(100) NOT NULL,
        harga_per_kg DECIMAL(10,2) NOT NULL,
        deskripsi TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $create_table);
    
    // Insert data default
    $insert_default = "INSERT INTO jenis_sampah (nama_jenis, harga_per_kg, deskripsi) VALUES
        ('Plastik PET', 3000, 'Botol plastik minuman, botol kemasan'),
        ('Plastik HDPE', 2500, 'Botol shampo, botol detergen'),
        ('Kertas/Kardus', 2000, 'Kardus bekas, kertas HVS, koran'),
        ('Logam/Besi', 5000, 'Kaleng, besi tua, alumunium'),
        ('Kaca/Botol', 500, 'Botol kaca, pecahan kaca')";
    mysqli_query($conn, $insert_default);
}

// Handle Tambah
if (isset($_POST['tambah_jenis'])) {
    $nama_jenis = clean_input($_POST['nama_jenis']);
    $harga_per_kg = clean_input($_POST['harga_per_kg']);
    $deskripsi = clean_input($_POST['deskripsi']);
    
    $query = "INSERT INTO jenis_sampah (nama_jenis, harga_per_kg, deskripsi) 
              VALUES ('$nama_jenis', '$harga_per_kg', '$deskripsi')";
    
    if (mysqli_query($conn, $query)) {
        $success = "Jenis sampah berhasil ditambahkan!";
    } else {
        $error = "Gagal menambahkan jenis sampah!";
    }
}

// Handle Edit
if (isset($_POST['edit_jenis'])) {
    $id = clean_input($_POST['id']);
    $nama_jenis = clean_input($_POST['nama_jenis']);
    $harga_per_kg = clean_input($_POST['harga_per_kg']);
    $deskripsi = clean_input($_POST['deskripsi']);
    
    $query = "UPDATE jenis_sampah SET 
              nama_jenis='$nama_jenis', 
              harga_per_kg='$harga_per_kg', 
              deskripsi='$deskripsi' 
              WHERE id='$id'";
    
    if (mysqli_query($conn, $query)) {
        $success = "Harga sampah berhasil diupdate!";
    } else {
        $error = "Gagal mengupdate harga sampah!";
    }
}

// Handle Hapus
if (isset($_GET['delete'])) {
    $id = clean_input($_GET['delete']);
    
    if (mysqli_query($conn, "DELETE FROM jenis_sampah WHERE id='$id'")) {
        $success = "Jenis sampah berhasil dihapus!";
    } else {
        $error = "Gagal menghapus jenis sampah!";
    }
}

// Ambil semua jenis sampah
$query_jenis = "SELECT * FROM jenis_sampah ORDER BY nama_jenis ASC";
$result_jenis = mysqli_query($conn, $query_jenis);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Harga Sampah - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-brand { font-size: 1.5em; font-weight: bold; }
        .navbar-menu { display: flex; gap: 20px; align-items: center; }
        .navbar-menu a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: all 0.3s; }
        .navbar-menu a:hover { background: rgba(255,255,255,0.2); }
        .container { max-width: 1200px; margin: 0 auto; padding: 30px; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card h2 { color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #667eea; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-danger { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .price-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .price-card { background: #f8f9fa; padding: 20px; border-radius: 10px; border-left: 4px solid #667eea; position: relative; }
        .price-card h3 { color: #333; margin-bottom: 10px; }
        .price-value { font-size: 2em; color: #28a745; font-weight: bold; margin: 15px 0; }
        .price-desc { color: #666; font-size: 0.9em; margin-bottom: 15px; }
        .price-meta { font-size: 0.85em; color: #999; margin-top: 10px; padding-top: 10px; border-top: 1px solid #e0e0e0; }
        .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; margin-right: 5px; }
        .btn-primary { background: #667eea; color: white; }
        .btn-warning { background: #ffc107; color: #000; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn:hover { opacity: 0.8; }
        .btn-add { padding: 12px 25px; font-size: 1em; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 5% auto; padding: 30px; border-radius: 15px; width: 90%; max-width: 600px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #e0e0e0; }
        .close { font-size: 2em; cursor: pointer; color: #999; }
        .close:hover { color: #333; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1em; font-family: inherit; }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .info-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .info-box h3 { margin-bottom: 10px; }
        @media (max-width: 768px) {
            .price-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">💲 Admin - Harga Sampah</div>
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
        
        <div class="info-box">
            <h3>💡 Informasi</h3>
            <p>Kelola harga sampah per kilogram untuk setiap jenis. Harga ini akan digunakan sebagai acuan dalam perhitungan nilai tabungan warga.</p>
        </div>
        
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0; border: none;">💲 Daftar Harga Sampah</h2>
                <button class="btn btn-success btn-add" onclick="showAddModal()">
                    ➕ Tambah Jenis Sampah
                </button>
            </div>
            
            <div class="price-grid">
                <?php while($row = mysqli_fetch_assoc($result_jenis)): ?>
                <div class="price-card">
                    <h3>♻️ <?php echo $row['nama_jenis']; ?></h3>
                    <div class="price-value"><?php echo format_rupiah($row['harga_per_kg']); ?>/kg</div>
                    <div class="price-desc"><?php echo $row['deskripsi'] ?: '-'; ?></div>
                    <div style="margin-top: 15px;">
                        <button class="btn btn-warning" onclick="editJenis(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                            ✏️ Edit
                        </button>
                        <button class="btn btn-danger" onclick="deleteJenis(<?php echo $row['id']; ?>, '<?php echo addslashes($row['nama_jenis']); ?>')">
                            🗑️ Hapus
                        </button>
                    </div>
                    <div class="price-meta">
                        Terakhir update: <?php echo format_tanggal($row['updated_at']); ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal Tambah -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>➕ Tambah Jenis Sampah</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Nama Jenis Sampah *</label>
                    <input type="text" name="nama_jenis" required placeholder="Contoh: Plastik PP">
                </div>
                <div class="form-group">
                    <label>Harga per Kg (Rp) *</label>
                    <input type="number" name="harga_per_kg" required step="0.01" placeholder="Contoh: 3000">
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" placeholder="Contoh: Kantong plastik, gelas plastik"></textarea>
                </div>
                <button type="submit" name="tambah_jenis" class="btn btn-success" style="width: 100%; padding: 12px;">
                    💾 Simpan
                </button>
            </form>
        </div>
    </div>
    
    <!-- Modal Edit -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Edit Harga Sampah</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Nama Jenis Sampah *</label>
                    <input type="text" name="nama_jenis" id="edit_nama_jenis" required>
                </div>
                <div class="form-group">
                    <label>Harga per Kg (Rp) *</label>
                    <input type="number" name="harga_per_kg" id="edit_harga_per_kg" required step="0.01">
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" id="edit_deskripsi"></textarea>
                </div>
                <button type="submit" name="edit_jenis" class="btn btn-success" style="width: 100%; padding: 12px;">
                    💾 Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function showAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function editJenis(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_nama_jenis').value = data.nama_jenis;
            document.getElementById('edit_harga_per_kg').value = data.harga_per_kg;
            document.getElementById('edit_deskripsi').value = data.deskripsi || '';
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function deleteJenis(id, nama) {
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Yakin ingin menghapus jenis sampah: ' + nama + '?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'harga_sampah.php?delete=' + id;
                }
            });
        }
        
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            if (event.target == addModal) addModal.style.display = 'none';
            if (event.target == editModal) editModal.style.display = 'none';
        }
    </script>
</body>
</html>
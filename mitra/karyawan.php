<?php
session_start();
require_once '../koneksi.php';

check_login('mitra');

$mitra_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

$success = '';
$error = '';

// Handle Tambah Karyawan
if (isset($_POST['tambah_karyawan'])) {
    $nama_karyawan = clean_input($_POST['nama']);
    $jabatan = clean_input($_POST['jabatan']);
    
    $query = "INSERT INTO karyawan (nama, jabatan, mitra_id) VALUES ('$nama_karyawan', '$jabatan', '$mitra_id')";
    
    if (mysqli_query($conn, $query)) {
        $success = "Karyawan berhasil ditambahkan!";
    } else {
        $error = "Gagal menambahkan karyawan!";
    }
}

// Handle Edit Karyawan
if (isset($_POST['edit_karyawan'])) {
    $karyawan_id = clean_input($_POST['karyawan_id']);
    $nama_karyawan = clean_input($_POST['nama']);
    $jabatan = clean_input($_POST['jabatan']);
    
    $query = "UPDATE karyawan SET nama='$nama_karyawan', jabatan='$jabatan' 
              WHERE id='$karyawan_id' AND mitra_id='$mitra_id'";
    
    if (mysqli_query($conn, $query)) {
        $success = "Data karyawan berhasil diupdate!";
    } else {
        $error = "Gagal mengupdate data karyawan!";
    }
}

// Handle Hapus Karyawan
if (isset($_GET['delete'])) {
    $karyawan_id = clean_input($_GET['delete']);
    
    if (mysqli_query($conn, "DELETE FROM karyawan WHERE id='$karyawan_id' AND mitra_id='$mitra_id'")) {
        $success = "Karyawan berhasil dihapus!";
    } else {
        $error = "Gagal menghapus karyawan!";
    }
}

// Ambil semua karyawan
$query_karyawan = "SELECT * FROM karyawan WHERE mitra_id = '$mitra_id' ORDER BY created_at DESC";
$result_karyawan = mysqli_query($conn, $query_karyawan);

// Hitung total karyawan
$total_karyawan = mysqli_num_rows($result_karyawan);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Karyawan - Mitra</title>
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
        .container { max-width: 1200px; margin: 0 auto; padding: 30px; }
        .stat-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; text-align: center; border-left: 4px solid #11998e; }
        .stat-value { font-size: 3em; font-weight: bold; color: #11998e; }
        .stat-label { color: #666; font-size: 1.1em; margin-top: 10px; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card h2 { color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #11998e; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-danger { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; margin-right: 5px; transition: all 0.3s; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: #000; }
        .btn-danger { background: #dc3545; color: white; }
        .btn:hover { opacity: 0.8; transform: translateY(-2px); }
        .karyawan-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .karyawan-card { background: #f8f9fa; padding: 20px; border-radius: 10px; border-left: 4px solid #11998e; }
        .karyawan-card h3 { color: #333; margin-bottom: 10px; }
        .karyawan-card p { color: #666; margin-bottom: 15px; }
        .karyawan-meta { font-size: 0.85em; color: #999; margin-top: 10px; padding-top: 10px; border-top: 1px solid #e0e0e0; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 5% auto; padding: 30px; border-radius: 15px; width: 90%; max-width: 500px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #e0e0e0; }
        .close { font-size: 2em; cursor: pointer; color: #999; }
        .close:hover { color: #333; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1em; }
        .empty-state { text-align: center; padding: 60px 20px; color: #999; }
        .empty-state .icon { font-size: 5em; margin-bottom: 20px; opacity: 0.5; }
        @media (max-width: 768px) {
            .karyawan-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">👥 Mitra - Kelola Karyawan</div>
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
        
        <div class="stat-card">
            <div class="stat-value"><?php echo $total_karyawan; ?></div>
            <div class="stat-label">Total Karyawan</div>
        </div>
        
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0; border: none;">👥 Daftar Karyawan</h2>
                <button class="btn btn-success" onclick="showAddModal()">
                    ➕ Tambah Karyawan
                </button>
            </div>
            
            <?php if ($total_karyawan > 0): ?>
                <div class="karyawan-grid">
                    <?php while($row = mysqli_fetch_assoc($result_karyawan)): ?>
                    <div class="karyawan-card">
                        <h3>👤 <?php echo $row['nama']; ?></h3>
                        <p><strong>Jabatan:</strong> <?php echo $row['jabatan']; ?></p>
                        <div>
                            <button class="btn btn-warning" onclick="editKaryawan(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                ✏️ Edit
                            </button>
                            <button class="btn btn-danger" onclick="deleteKaryawan(<?php echo $row['id']; ?>, '<?php echo addslashes($row['nama']); ?>')">
                                🗑️ Hapus
                            </button>
                        </div>
                        <div class="karyawan-meta">
                            Ditambahkan: <?php echo format_tanggal($row['created_at']); ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="icon">👥</div>
                <h3>Belum Ada Karyawan</h3>
                <p>Klik tombol "Tambah Karyawan" untuk menambahkan karyawan baru</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal Tambah -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>➕ Tambah Karyawan</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Nama Karyawan *</label>
                    <input type="text" name="nama" required placeholder="Masukkan nama lengkap">
                </div>
                <div class="form-group">
                    <label>Jabatan *</label>
                    <input type="text" name="jabatan" required placeholder="Contoh: Driver, Helper">
                </div>
                <button type="submit" name="tambah_karyawan" class="btn btn-success" style="width: 100%; padding: 12px;">
                    💾 Simpan
                </button>
            </form>
        </div>
    </div>
    
    <!-- Modal Edit -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Edit Karyawan</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="karyawan_id" id="edit_id">
                <div class="form-group">
                    <label>Nama Karyawan *</label>
                    <input type="text" name="nama" id="edit_nama" required>
                </div>
                <div class="form-group">
                    <label>Jabatan *</label>
                    <input type="text" name="jabatan" id="edit_jabatan" required>
                </div>
                <button type="submit" name="edit_karyawan" class="btn btn-success" style="width: 100%; padding: 12px;">
                    💾 Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function showAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function editKaryawan(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_nama').value = data.nama;
            document.getElementById('edit_jabatan').value = data.jabatan;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function deleteKaryawan(id, nama) {
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Yakin ingin menghapus karyawan: ' + nama + '?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'karyawan.php?delete=' + id;
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
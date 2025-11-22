<?php
session_start();
require_once '../koneksi.php';

// Cek login
check_login('admin');

$admin_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

$success = '';
$error = '';

// Handle Tambah Pengumuman
if (isset($_POST['tambah_pengumuman'])) {
    $judul = clean_input($_POST['judul']);
    $isi = clean_input($_POST['isi']);
    
    $query = "INSERT INTO pengumuman (judul, isi, dibuat_oleh) VALUES ('$judul', '$isi', '$admin_id')";
    
    if (mysqli_query($conn, $query)) {
        $success = "Pengumuman berhasil ditambahkan!";
    } else {
        $error = "Gagal menambahkan pengumuman!";
    }
}

// Handle Edit Pengumuman
if (isset($_POST['edit_pengumuman'])) {
    $pengumuman_id = clean_input($_POST['pengumuman_id']);
    $judul = clean_input($_POST['judul']);
    $isi = clean_input($_POST['isi']);
    
    $query = "UPDATE pengumuman SET judul='$judul', isi='$isi' WHERE id='$pengumuman_id'";
    
    if (mysqli_query($conn, $query)) {
        $success = "Pengumuman berhasil diupdate!";
    } else {
        $error = "Gagal mengupdate pengumuman!";
    }
}

// Handle Hapus Pengumuman
if (isset($_GET['delete'])) {
    $pengumuman_id = clean_input($_GET['delete']);
    
    if (mysqli_query($conn, "DELETE FROM pengumuman WHERE id='$pengumuman_id'")) {
        $success = "Pengumuman berhasil dihapus!";
    } else {
        $error = "Gagal menghapus pengumuman!";
    }
}

// Ambil semua pengumuman
$query_pengumuman = "SELECT p.*, u.nama as pembuat 
FROM pengumuman p
JOIN users u ON p.dibuat_oleh = u.id
ORDER BY p.tanggal DESC";
$result_pengumuman = mysqli_query($conn, $query_pengumuman);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengumuman - Admin Bank Sampah</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .card h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #000;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.8;
            transform: translateY(-2px);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            font-family: inherit;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 150px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .pengumuman-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
        
        .pengumuman-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .pengumuman-header h3 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .pengumuman-meta {
            font-size: 0.9em;
            color: #666;
        }
        
        .pengumuman-content {
            color: #555;
            line-height: 1.8;
            margin-bottom: 15px;
            white-space: pre-line;
        }
        
        .pengumuman-actions {
            display: flex;
            gap: 10px;
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
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 700px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .close {
            font-size: 2em;
            cursor: pointer;
            color: #999;
        }
        
        .close:hover {
            color: #333;
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
        <div class="navbar-brand">📢 Admin - Pengumuman</div>
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
        
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0; border: none;">📢 Kelola Pengumuman</h2>
                <button class="btn btn-primary" onclick="showAddModal()">
                    ➕ Tambah Pengumuman
                </button>
            </div>
            
            <?php if (mysqli_num_rows($result_pengumuman) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result_pengumuman)): ?>
                <div class="pengumuman-item">
                    <div class="pengumuman-header">
                        <div>
                            <h3><?php echo $row['judul']; ?></h3>
                            <div class="pengumuman-meta">
                                👤 <?php echo $row['pembuat']; ?> • 
                                📅 <?php echo format_tanggal_waktu($row['tanggal']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pengumuman-content">
                        <?php echo nl2br($row['isi']); ?>
                    </div>
                    
                    <div class="pengumuman-actions">
                        <button class="btn btn-warning" onclick="editPengumuman(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                            ✏️ Edit
                        </button>
                        <button class="btn btn-danger" onclick="deletePengumuman(<?php echo $row['id']; ?>, '<?php echo addslashes($row['judul']); ?>')">
                            🗑️ Hapus
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="icon">📭</div>
                    <h3>Belum Ada Pengumuman</h3>
                    <p>Klik tombol "Tambah Pengumuman" untuk membuat pengumuman baru</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal Tambah -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>➕ Tambah Pengumuman</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST" id="addForm">
                <div class="form-group">
                    <label>Judul Pengumuman *</label>
                    <input type="text" name="judul" required placeholder="Contoh: Perubahan Jadwal Penjemputan">
                </div>
                
                <div class="form-group">
                    <label>Isi Pengumuman *</label>
                    <textarea name="isi" required placeholder="Tuliskan isi pengumuman disini..."></textarea>
                </div>
                
                <button type="submit" name="tambah_pengumuman" class="btn btn-primary" style="width: 100%; padding: 12px;">
                    📢 Publikasikan Pengumuman
                </button>
            </form>
        </div>
    </div>
    
    <!-- Modal Edit -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Edit Pengumuman</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="pengumuman_id" id="edit_pengumuman_id">
                
                <div class="form-group">
                    <label>Judul Pengumuman *</label>
                    <input type="text" name="judul" id="edit_judul" required>
                </div>
                
                <div class="form-group">
                    <label>Isi Pengumuman *</label>
                    <textarea name="isi" id="edit_isi" required></textarea>
                </div>
                
                <button type="submit" name="edit_pengumuman" class="btn btn-primary" style="width: 100%; padding: 12px;">
                    💾 Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function showAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function editPengumuman(data) {
            document.getElementById('edit_pengumuman_id').value = data.id;
            document.getElementById('edit_judul').value = data.judul;
            document.getElementById('edit_isi').value = data.isi;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function deletePengumuman(id, judul) {
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Yakin ingin menghapus pengumuman: ' + judul + '?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'pengumuman.php?delete=' + id;
                }
            });
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            if (event.target == addModal) {
                addModal.style.display = 'none';
            }
            if (event.target == editModal) {
                editModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
<?php
session_start();
require_once '../koneksi.php';

// Cek login
check_login('admin');

$admin_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

$success = '';
$error = '';

// Handle Edit User
if (isset($_POST['edit_user'])) {
    $user_id = clean_input($_POST['user_id']);
    $nama_user = clean_input($_POST['nama']);
    $email = clean_input($_POST['email']);
    $no_hp = clean_input($_POST['no_hp']);
    $alamat = clean_input($_POST['alamat']);
    
    $query = "UPDATE users SET nama='$nama_user', email='$email', no_hp='$no_hp', alamat='$alamat' WHERE id='$user_id'";
    
    if (mysqli_query($conn, $query)) {
        $success = "Data user berhasil diupdate!";
    } else {
        $error = "Gagal mengupdate data user!";
    }
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $user_id = clean_input($_GET['delete']);
    
    // Cek apakah user adalah admin
    $check = mysqli_query($conn, "SELECT role FROM users WHERE id='$user_id'");
    $user_data = mysqli_fetch_assoc($check);
    
    if ($user_data['role'] == 'admin') {
        $error = "Tidak bisa menghapus akun admin!";
    } else {
        // Hapus data terkait terlebih dahulu
        mysqli_query($conn, "DELETE FROM tabungan WHERE warga_id='$user_id'");
        mysqli_query($conn, "DELETE FROM penjemputan WHERE warga_id='$user_id' OR mitra_id='$user_id'");
        mysqli_query($conn, "DELETE FROM transaksi_sampah WHERE warga_id='$user_id'");
        mysqli_query($conn, "DELETE FROM penarikan_saldo WHERE warga_id='$user_id'");
        mysqli_query($conn, "DELETE FROM feedback WHERE pengirim_id='$user_id' OR penerima_id='$user_id'");
        mysqli_query($conn, "DELETE FROM karyawan WHERE mitra_id='$user_id'");
        
        // Hapus user
        if (mysqli_query($conn, "DELETE FROM users WHERE id='$user_id'")) {
            $success = "User berhasil dihapus!";
        } else {
            $error = "Gagal menghapus user!";
        }
    }
}

// Handle Reset Password
if (isset($_POST['reset_password'])) {
    $user_id = clean_input($_POST['user_id']);
    $new_password = clean_input($_POST['new_password']);
    
    $query = "UPDATE users SET password='$new_password' WHERE id='$user_id'";
    
    if (mysqli_query($conn, $query)) {
        $success = "Password berhasil direset!";
    } else {
        $error = "Gagal reset password!";
    }
}

// Ambil semua user
$filter_role = isset($_GET['role']) ? $_GET['role'] : '';
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

$query = "SELECT * FROM users WHERE 1=1";

if (!empty($filter_role)) {
    $query .= " AND role = '$filter_role'";
}

if (!empty($search)) {
    $query .= " AND (nama LIKE '%$search%' OR username LIKE '%$search%' OR email LIKE '%$search%')";
}

$query .= " ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Admin Bank Sampah</title>
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
        
        .filter-section {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-section select,
        .filter-section input {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95em;
        }
        
        .filter-section button {
            padding: 10px 25px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .filter-section button:hover {
            background: #5568d3;
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
        
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .badge.warga {
            background: #ffe6f0;
            color: #d63384;
        }
        
        .badge.mitra {
            background: #d1f4e0;
            color: #198754;
        }
        
        .badge.admin {
            background: #cfe2ff;
            color: #0d6efd;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85em;
            margin-right: 5px;
            text-decoration: none;
            display: inline-block;
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
            max-width: 600px;
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
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        @media (max-width: 768px) {
            .filter-section {
                flex-direction: column;
            }
            
            table {
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">⚙️ Admin - Kelola User</div>
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
            <h2>👥 Kelola User</h2>
            
            <form method="GET" class="filter-section">
                <select name="role">
                    <option value="">Semua Role</option>
                    <option value="warga" <?php echo $filter_role == 'warga' ? 'selected' : ''; ?>>Warga</option>
                    <option value="mitra" <?php echo $filter_role == 'mitra' ? 'selected' : ''; ?>>Mitra</option>
                    <option value="admin" <?php echo $filter_role == 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
                
                <input type="text" name="search" placeholder="Cari nama, username, atau email..." value="<?php echo $search; ?>" style="flex: 1; min-width: 250px;">
                
                <button type="submit">🔍 Filter</button>
                <a href="users.php" style="padding: 10px 25px; background: #6c757d; color: white; border-radius: 8px; text-decoration: none;">Reset</a>
            </form>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>No. HP</th>
                        <th>Role</th>
                        <th>Tgl Daftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><strong><?php echo $row['username']; ?></strong></td>
                            <td><?php echo $row['nama']; ?></td>
                            <td><?php echo $row['email'] ?: '-'; ?></td>
                            <td><?php echo $row['no_hp'] ?: '-'; ?></td>
                            <td>
                                <span class="badge <?php echo $row['role']; ?>">
                                    <?php echo ucfirst($row['role']); ?>
                                </span>
                            </td>
                            <td><?php echo format_tanggal($row['created_at']); ?></td>
                            <td>
                                <button class="btn btn-primary" onclick="editUser(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                    ✏️ Edit
                                </button>
                                <button class="btn btn-warning" onclick="resetPassword(<?php echo $row['id']; ?>, '<?php echo $row['username']; ?>')">
                                    🔑 Reset
                                </button>
                                <?php if ($row['role'] != 'admin'): ?>
                                <button class="btn btn-danger" onclick="deleteUser(<?php echo $row['id']; ?>, '<?php echo $row['nama']; ?>')">
                                    🗑️ Hapus
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #999;">
                                Tidak ada data user
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Modal Edit User -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Edit User</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="edit_username" readonly style="background: #f0f0f0;">
                </div>
                
                <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input type="text" name="nama" id="edit_nama" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_email">
                </div>
                
                <div class="form-group">
                    <label>No. HP</label>
                    <input type="text" name="no_hp" id="edit_no_hp">
                </div>
                
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="alamat" id="edit_alamat"></textarea>
                </div>
                
                <button type="submit" name="edit_user" class="btn btn-primary" style="width: 100%; padding: 12px;">
                    💾 Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_nama').value = user.nama;
            document.getElementById('edit_email').value = user.email || '';
            document.getElementById('edit_no_hp').value = user.no_hp || '';
            document.getElementById('edit_alamat').value = user.alamat || '';
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function resetPassword(userId, username) {
            Swal.fire({
                title: 'Reset Password',
                text: 'Reset password untuk user: ' + username,
                input: 'password',
                inputLabel: 'Password Baru',
                inputPlaceholder: 'Masukkan password baru',
                showCancelButton: true,
                confirmButtonText: 'Reset',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#667eea',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Password tidak boleh kosong!'
                    }
                    if (value.length < 6) {
                        return 'Password minimal 6 karakter!'
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="user_id" value="${userId}">
                        <input type="hidden" name="new_password" value="${result.value}">
                        <input type="hidden" name="reset_password" value="1">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
        
        function deleteUser(userId, nama) {
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Yakin ingin menghapus user: ' + nama + '?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'users.php?delete=' + userId;
                }
            });
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
<?php
session_start();
require_once 'koneksi.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: admin/dashboard.php");
            break;
        case 'mitra':
            header("Location: mitra/dashboard.php");
            break;
        case 'warga':
            header("Location: warga/dashboard.php");
            break;
    }
    exit();
}

$success = '';
$error = '';
$success_registration = false;

// Proses registrasi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean_input($_POST['username']);
    $password = clean_input($_POST['password']);
    $confirm_password = clean_input($_POST['confirm_password']);
    $role = clean_input($_POST['role']);
    $nama = clean_input($_POST['nama']);
    $email = clean_input($_POST['email']);
    $no_hp = clean_input($_POST['no_hp']);
    $alamat = clean_input($_POST['alamat']);
    
    // Validasi input
    if (empty($username) || empty($password) || empty($role) || empty($nama)) {
        $error = "Username, password, role, dan nama harus diisi!";
    } elseif ($password !== $confirm_password) {
        $error = "Password dan konfirmasi password tidak cocok!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif ($role == 'admin') {
        $error = "Tidak bisa mendaftar sebagai admin!";
    } else {
        // Cek apakah username sudah ada
        $check_query = "SELECT id FROM users WHERE username = '$username'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Username sudah digunakan! Pilih username lain.";
        } else {
            // Insert user baru
            $insert_query = "INSERT INTO users (username, password, role, nama, email, no_hp, alamat) 
                           VALUES ('$username', '$password', '$role', '$nama', '$email', '$no_hp', '$alamat')";
            
            if (mysqli_query($conn, $insert_query)) {
                $user_id = mysqli_insert_id($conn);
                
                // Jika role warga, buat record tabungan dengan saldo 0
                if ($role == 'warga') {
                    $tabungan_query = "INSERT INTO tabungan (warga_id, saldo) VALUES ('$user_id', 0)";
                    mysqli_query($conn, $tabungan_query);
                }
                
                $success_registration = true;
            } else {
                $error = "Terjadi kesalahan saat mendaftar. Coba lagi!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Bank Sampah</title>
    
    <!-- SweetAlert2 CDN -->
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .register-header .icon {
            font-size: 3em;
            margin-bottom: 10px;
        }
        
        .register-header h2 {
            font-size: 1.8em;
            margin-bottom: 5px;
        }
        
        .register-header p {
            opacity: 0.9;
            font-size: 0.95em;
        }
        
        .register-form {
            padding: 30px;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1em;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }
        
        .success-message {
            background: #efe;
            color: #3c3;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #3c3;
        }
        
        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .required {
            color: #c33;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .register-form {
                max-height: 60vh;
            }
        }
    </style>
</head>
<body>
    <?php if ($success_registration): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Pendaftaran Berhasil!',
            html: '<p>Akun Anda telah berhasil didaftarkan.</p><p>Silakan login untuk melanjutkan.</p>',
            confirmButtonText: 'OK',
            confirmButtonColor: '#667eea',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'index.php';
            }
        });
    </script>
    <?php endif; ?>
    
    <div class="register-container">
        <div class="register-header">
            <div class="icon">📝</div>
            <h2>Pendaftaran Akun</h2>
            <p>Bergabung dengan Bank Sampah Digital</p>
        </div>
        
        <div class="register-form">
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registrationForm">
                <div class="form-group">
                    <label for="role">Daftar Sebagai <span class="required">*</span></label>
                    <select id="role" name="role" required>
                        <option value="">-- Pilih Role --</option>
                        <option value="warga">Warga Desa</option>
                        <option value="mitra">Mitra Pengumpul Sampah</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="nama">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" id="nama" name="nama" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username <span class="required">*</span></label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <input type="password" id="password" name="password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Konfirmasi Password <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="no_hp">No. HP / WhatsApp</label>
                    <input type="tel" id="no_hp" name="no_hp" placeholder="08xx-xxxx-xxxx">
                </div>
                
                <div class="form-group">
                    <label for="alamat">Alamat Lengkap</label>
                    <textarea id="alamat" name="alamat" placeholder="Masukkan alamat lengkap Anda"></textarea>
                </div>
                
                <button type="submit" class="btn-register" id="submitBtn">Daftar Sekarang</button>
            </form>
            
            <div class="login-link">
                <p>Sudah punya akun? <a href="index.php">Login di sini</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Validasi konfirmasi password
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Password dan Konfirmasi Password tidak cocok!',
                    confirmButtonColor: '#667eea'
                });
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Password minimal 6 karakter!',
                    confirmButtonColor: '#667eea'
                });
                return false;
            }
        });
    </script>
</body>
</html>
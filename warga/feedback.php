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

// Handle Submit Feedback
if (isset($_POST['submit_feedback'])) {
    $penerima_id = clean_input($_POST['penerima_id']);
    $role_penerima = clean_input($_POST['role_penerima']);
    $rating = clean_input($_POST['rating']);
    $komentar = clean_input($_POST['komentar']);
    
    if ($rating < 1 || $rating > 5) {
        $error = "Rating harus antara 1-5!";
    } else {
        $query = "INSERT INTO feedback (pengirim_id, penerima_id, role_penerima, rating, komentar) 
                  VALUES ('$warga_id', '$penerima_id', '$role_penerima', '$rating', '$komentar')";
        
        if (mysqli_query($conn, $query)) {
            $success = "Feedback berhasil dikirim! Terima kasih atas penilaian Anda.";
        } else {
            $error = "Gagal mengirim feedback!";
        }
    }
}

// Ambil daftar mitra yang pernah melayani
$query_mitra = "SELECT DISTINCT u.id, u.nama 
FROM penjemputan p
JOIN users u ON p.mitra_id = u.id
WHERE p.warga_id = '$warga_id' AND p.status = 'selesai'
ORDER BY u.nama ASC";
$result_mitra = mysqli_query($conn, $query_mitra);

// Ambil riwayat feedback yang sudah dikirim
$query_riwayat = "SELECT f.*, u.nama as nama_penerima, u.role as role_penerima_detail
FROM feedback f
JOIN users u ON f.penerima_id = u.id
WHERE f.pengirim_id = '$warga_id'
ORDER BY f.tanggal DESC";
$result_riwayat = mysqli_query($conn, $query_riwayat);

// Statistik feedback
$query_stats = "SELECT 
    COUNT(*) as total_feedback,
    COALESCE(AVG(rating), 0) as avg_rating
FROM feedback WHERE pengirim_id = '$warga_id'";
$result_stats = mysqli_query($conn, $query_stats);
$stats = mysqli_fetch_assoc($result_stats);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Warga</title>
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
        .container { max-width: 1000px; margin: 0 auto; padding: 30px; }
        .stats-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; text-align: center; }
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .stat-item { padding: 20px; background: #f8f9fa; border-radius: 10px; }
        .stat-value { font-size: 2.5em; font-weight: bold; color: #f093fb; }
        .stat-label { color: #666; margin-top: 5px; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card h2 { color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f093fb; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-danger { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1em; font-family: inherit; }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .rating-container { display: flex; gap: 10px; align-items: center; }
        .star { font-size: 2.5em; cursor: pointer; color: #ddd; transition: all 0.2s; }
        .star:hover, .star.active { color: #ffc107; transform: scale(1.1); }
        .btn-primary { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; width: 100%; font-size: 1.1em; padding: 15px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .btn-primary:hover { opacity: 0.9; transform: translateY(-2px); }
        .feedback-item { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 15px; border-left: 4px solid #f093fb; }
        .feedback-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px; }
        .feedback-header h3 { color: #333; }
        .rating-display { color: #ffc107; font-size: 1.2em; }
        .feedback-content { color: #666; line-height: 1.6; margin-bottom: 10px; }
        .feedback-meta { font-size: 0.85em; color: #999; }
        .info-box { background: #e7f3ff; padding: 20px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #0066cc; }
        .info-box h3 { color: #0066cc; margin-bottom: 10px; }
        .empty-state { text-align: center; padding: 60px 20px; color: #999; }
        .empty-state .icon { font-size: 5em; margin-bottom: 20px; opacity: 0.5; }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">⭐ Warga - Feedback</div>
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
        
        <div class="stats-card">
            <h2 style="border: none; margin-bottom: 20px;">📊 Statistik Feedback Anda</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $stats['total_feedback']; ?></div>
                    <div class="stat-label">Total Feedback Diberikan</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo number_format($stats['avg_rating'], 1); ?> ⭐</div>
                    <div class="stat-label">Rata-rata Rating</div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>✍️ Berikan Penilaian</h2>
            
            <div class="info-box">
                <h3>ℹ️ Tentang Feedback</h3>
                <p>Penilaian Anda sangat membantu kami meningkatkan kualitas pelayanan. Berikan feedback yang jujur dan membangun untuk mitra yang telah melayani Anda.</p>
            </div>
            
            <form method="POST" id="formFeedback">
                <div class="form-group">
                    <label for="penerima_id">🚛 Mitra yang Ingin Dinilai *</label>
                    <select id="penerima_id" name="penerima_id" required>
                        <option value="">-- Pilih Mitra --</option>
                        <?php if (mysqli_num_rows($result_mitra) > 0): ?>
                            <?php while($mitra = mysqli_fetch_assoc($result_mitra)): ?>
                            <option value="<?php echo $mitra['id']; ?>">
                                <?php echo $mitra['nama']; ?>
                            </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="" disabled>Belum ada mitra yang melayani Anda</option>
                        <?php endif; ?>
                    </select>
                    <input type="hidden" name="role_penerima" value="mitra">
                </div>
                
                <div class="form-group">
                    <label>⭐ Rating *</label>
                    <div class="rating-container">
                        <span class="star" data-rating="1">★</span>
                        <span class="star" data-rating="2">★</span>
                        <span class="star" data-rating="3">★</span>
                        <span class="star" data-rating="4">★</span>
                        <span class="star" data-rating="5">★</span>
                        <span id="ratingText" style="margin-left: 10px; color: #666;"></span>
                    </div>
                    <input type="hidden" name="rating" id="ratingInput" required>
                    <small style="color: #666;">Klik bintang untuk memberi rating (1-5)</small>
                </div>
                
                <div class="form-group">
                    <label for="komentar">💬 Komentar / Saran</label>
                    <textarea id="komentar" name="komentar" placeholder="Tuliskan pengalaman Anda dengan mitra ini...&#10;&#10;Contoh: Pelayanan cepat, petugas ramah, jadwal tepat waktu."></textarea>
                </div>
                
                <button type="submit" name="submit_feedback" class="btn-primary" <?php echo mysqli_num_rows($result_mitra) == 0 ? 'disabled' : ''; ?>>
                    📤 Kirim Penilaian
                </button>
            </form>
        </div>
        
        <div class="card">
            <h2>📋 Riwayat Feedback Anda</h2>
            
            <?php if (mysqli_num_rows($result_riwayat) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result_riwayat)): ?>
                <div class="feedback-item">
                    <div class="feedback-header">
                        <h3><?php echo $row['nama_penerima']; ?></h3>
                        <div class="rating-display">
                            <?php 
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $row['rating'] ? '★' : '☆';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="feedback-content">
                        "<?php echo nl2br($row['komentar']); ?>"
                    </div>
                    <div class="feedback-meta">
                        📅 <?php echo format_tanggal_waktu($row['tanggal']); ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
            <div class="empty-state">
                <div class="icon">⭐</div>
                <h3>Belum Ada Feedback</h3>
                <p>Anda belum pernah memberikan penilaian</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Rating stars interaction
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('ratingInput');
        const ratingText = document.getElementById('ratingText');
        
        const ratingLabels = {
            1: 'Sangat Buruk',
            2: 'Buruk',
            3: 'Cukup',
            4: 'Baik',
            5: 'Sangat Baik'
        };
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                ratingInput.value = rating;
                ratingText.textContent = ratingLabels[rating];
                
                // Update visual
                stars.forEach(s => {
                    if (s.getAttribute('data-rating') <= rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
            
            star.addEventListener('mouseenter', function() {
                const rating = this.getAttribute('data-rating');
                stars.forEach(s => {
                    if (s.getAttribute('data-rating') <= rating) {
                        s.style.color = '#ffc107';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        });
        
        document.querySelector('.rating-container').addEventListener('mouseleave', function() {
            const currentRating = ratingInput.value;
            stars.forEach(s => {
                if (currentRating && s.getAttribute('data-rating') <= currentRating) {
                    s.style.color = '#ffc107';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });
        
        // Form validation
        document.getElementById('formFeedback').addEventListener('submit', function(e) {
            if (!ratingInput.value) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Rating Belum Dipilih',
                    text: 'Silakan pilih rating dengan klik bintang',
                    confirmButtonColor: '#f093fb'
                });
                return false;
            }
        });
    </script>
</body>
</html>
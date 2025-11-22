<?php
session_start();
require_once '../koneksi.php';

check_login('admin');

$admin_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

// Filter
$filter_rating = isset($_GET['rating']) ? clean_input($_GET['rating']) : '';
$filter_role = isset($_GET['role']) ? clean_input($_GET['role']) : '';

// Query feedback
$query = "SELECT f.*, 
    u1.nama as pengirim_nama, u1.role as pengirim_role,
    u2.nama as penerima_nama, u2.role as penerima_role
FROM feedback f
JOIN users u1 ON f.pengirim_id = u1.id
LEFT JOIN users u2 ON f.penerima_id = u2.id
WHERE 1=1";

if (!empty($filter_rating)) {
    $query .= " AND f.rating = '$filter_rating'";
}
if (!empty($filter_role)) {
    $query .= " AND f.role_penerima = '$filter_role'";
}

$query .= " ORDER BY f.tanggal DESC";
$result = mysqli_query($conn, $query);

// Statistik
$query_stats = "SELECT 
    COUNT(*) as total,
    COALESCE(AVG(rating), 0) as rata_rating,
    SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as positif,
    SUM(CASE WHEN rating <= 2 THEN 1 ELSE 0 END) as negatif
FROM feedback";
$result_stats = mysqli_query($conn, $query_stats);
$stats = mysqli_fetch_assoc($result_stats);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-brand { font-size: 1.5em; font-weight: bold; }
        .navbar-menu { display: flex; gap: 20px; align-items: center; }
        .navbar-menu a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: all 0.3s; }
        .navbar-menu a:hover { background: rgba(255,255,255,0.2); }
        .container { max-width: 1400px; margin: 0 auto; padding: 30px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid; }
        .stat-card.total { border-color: #667eea; }
        .stat-card.rating { border-color: #ffc107; }
        .stat-card.positif { border-color: #28a745; }
        .stat-card.negatif { border-color: #dc3545; }
        .stat-label { color: #666; font-size: 0.9em; margin-bottom: 10px; }
        .stat-value { font-size: 2em; font-weight: bold; color: #333; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card h2 { color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #667eea; }
        .filter-section { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-section select { padding: 10px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 0.95em; }
        .filter-section button { padding: 10px 25px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .filter-section button:hover { background: #5568d3; }
        .feedback-item { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 15px; border-left: 4px solid #667eea; }
        .feedback-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; }
        .feedback-meta { display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
        .feedback-meta span { font-size: 0.9em; color: #666; }
        .rating { color: #ffc107; font-size: 1.2em; }
        .badge { padding: 4px 10px; border-radius: 5px; font-size: 0.85em; font-weight: 600; }
        .badge.warga { background: #ffe6f0; color: #d63384; }
        .badge.mitra { background: #d1f4e0; color: #198754; }
        .badge.admin { background: #cfe2ff; color: #0d6efd; }
        .feedback-content { color: #555; line-height: 1.8; margin-top: 10px; }
        .empty-state { text-align: center; padding: 60px 20px; color: #999; }
        .empty-state .icon { font-size: 5em; margin-bottom: 20px; opacity: 0.5; }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .filter-section { flex-direction: column; }
            .feedback-header { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">⭐ Admin - Feedback</div>
        <div class="navbar-menu">
            <a href="dashboard.php">← Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-label">Total Feedback</div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
            </div>
            <div class="stat-card rating">
                <div class="stat-label">Rata-rata Rating</div>
                <div class="stat-value"><?php echo number_format($stats['rata_rating'], 1); ?> ⭐</div>
            </div>
            <div class="stat-card positif">
                <div class="stat-label">Feedback Positif (≥4)</div>
                <div class="stat-value"><?php echo $stats['positif']; ?></div>
            </div>
            <div class="stat-card negatif">
                <div class="stat-label">Feedback Negatif (≤2)</div>
                <div class="stat-value"><?php echo $stats['negatif']; ?></div>
            </div>
        </div>
        
        <div class="card">
            <h2>⭐ Daftar Feedback</h2>
            
            <form method="GET" class="filter-section">
                <select name="rating">
                    <option value="">Semua Rating</option>
                    <option value="5" <?php echo $filter_rating == '5' ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐ (5)</option>
                    <option value="4" <?php echo $filter_rating == '4' ? 'selected' : ''; ?>>⭐⭐⭐⭐ (4)</option>
                    <option value="3" <?php echo $filter_rating == '3' ? 'selected' : ''; ?>>⭐⭐⭐ (3)</option>
                    <option value="2" <?php echo $filter_rating == '2' ? 'selected' : ''; ?>>⭐⭐ (2)</option>
                    <option value="1" <?php echo $filter_rating == '1' ? 'selected' : ''; ?>>⭐ (1)</option>
                </select>
                
                <select name="role">
                    <option value="">Semua Penerima</option>
                    <option value="mitra" <?php echo $filter_role == 'mitra' ? 'selected' : ''; ?>>Mitra</option>
                    <option value="admin" <?php echo $filter_role == 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
                
                <button type="submit">🔍 Filter</button>
                <a href="feedback.php" style="padding: 10px 25px; background: #6c757d; color: white; border-radius: 8px; text-decoration: none;">Reset</a>
            </form>
            
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <div class="feedback-item">
                    <div class="feedback-header">
                        <div class="feedback-meta">
                            <strong><?php echo $row['pengirim_nama']; ?></strong>
                            <span class="badge <?php echo $row['pengirim_role']; ?>">
                                <?php echo ucfirst($row['pengirim_role']); ?>
                            </span>
                            <span>→</span>
                            <strong><?php echo $row['penerima_nama'] ?: 'Sistem'; ?></strong>
                            <?php if ($row['penerima_role']): ?>
                            <span class="badge <?php echo $row['penerima_role']; ?>">
                                <?php echo ucfirst($row['penerima_role']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div style="text-align: right;">
                            <div class="rating">
                                <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $row['rating'] ? '★' : '☆';
                                }
                                ?>
                            </div>
                            <small style="color: #999;"><?php echo format_tanggal($row['tanggal']); ?></small>
                        </div>
                    </div>
                    <div class="feedback-content">
                        "<?php echo nl2br($row['komentar']); ?>"
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <h3>Belum Ada Feedback</h3>
                <p>Belum ada feedback yang sesuai dengan filter</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
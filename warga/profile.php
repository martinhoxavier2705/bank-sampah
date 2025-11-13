<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['warga_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_SESSION['warga_id'];

$stmt = $conn->prepare("SELECT * FROM warga WHERE id = :id");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<h2>Profil Warga</h2>
<p>Nama: <?= htmlspecialchars($user['nama']) ?></p>
<p>Email: <?= htmlspecialchars($user['email']) ?></p>
<p>Telepon: <?= htmlspecialchars($user['telepon']) ?></p>
<p>Alamat: <?= htmlspecialchars($user['alamat']) ?></p>
<a href="logout.php">Logout</a>

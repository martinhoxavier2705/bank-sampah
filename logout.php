<?php
session_start();

// Simpan role sebelum destroy untuk redirect yang tepat
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Hapus semua session
session_unset();
session_destroy();

// Redirect ke halaman index
header("Location: index.php");
exit();
?>
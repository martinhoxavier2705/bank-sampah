<?php
session_start();
include '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $telepon = $_POST['telepon'];
    $alamat = $_POST['alamat'];

    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    $query = "INSERT INTO warga (nama, email, password_hash, telepon, alamat)
              VALUES (:nama, :email, :password_hash, :telepon, :alamat)";
    $stmt = $conn->prepare($query);

    try {
        $stmt->execute([
            ':nama' => $nama,
            ':email' => $email,
            ':password_hash' => $password_hash,
            ':telepon' => $telepon,
            ':alamat' => $alamat
        ]);
        echo "Pendaftaran berhasil. <a href='login.php'>Login sekarang</a>";
    } catch (PDOException $e) {
        echo "Gagal daftar: " . $e->getMessage();
    }
}
?>
<form method="POST">
    <input type="text" name="nama" placeholder="Nama" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <input type="text" name="telepon" placeholder="Telepon"><br>
    <textarea name="alamat" placeholder="Alamat"></textarea><br>
    <button type="submit">Daftar</button>
</form>

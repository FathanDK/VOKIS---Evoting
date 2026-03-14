<?php
session_start();
include 'koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Amankan input dari SQL Injection
    $nisn = $conn->real_escape_string($_POST['nisn']);
    $token = $conn->real_escape_string($_POST['token']);

    // Cek di tabel pemilih
    $query = "SELECT * FROM pemilih WHERE nisn = '$nisn' AND token = '$token'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $data_pemilih = $result->fetch_assoc();
        
        // Simpan data ke session
        $_SESSION['pemilih_id'] = $data_pemilih['id'];
        $_SESSION['nama_pemilih'] = $data_pemilih['nama'];
        
        // Arahkan ke halaman pemilihan (misal ke OSIS yang ID-nya 1)
        // Di sistem yang lebih besar, arahkan ke daftar pemilihan dulu
        header("Location: vote.php?id=1"); 
        exit;
    } else {
        // Jika salah, kembalikan ke form dengan pesan error
        $_SESSION['error'] = "NISN atau Token tidak valid!";
        header("Location: login.php");
        exit;
    }
}
?>
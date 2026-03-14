<?php
session_start();
include 'koneksi.php';

// Beritahu browser bahwa ini adalah balasan berupa data JSON
header('Content-Type: application/json');

if (!isset($_SESSION['pemilih_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Anda belum login.']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pemilih_id = $_SESSION['pemilih_id'];
    $kandidat_id = (int)$_POST['kandidat_id'];
    $pemilihan_id = (int)$_POST['pemilihan_id'];

    // Cek apakah sudah memilih
    $cek_suara = $conn->query("SELECT id FROM riwayat_suara WHERE pemilih_id = $pemilih_id AND pemilihan_id = $pemilihan_id");

    if ($cek_suara->num_rows > 0) {
        // Balasan jika sudah milih
        echo json_encode(['status' => 'error', 'message' => 'Anda sudah memberikan suara pada kategori pemilihan ini.']);
    } else {
        $conn->begin_transaction();
        try {
            $conn->query("INSERT INTO riwayat_suara (pemilih_id, pemilihan_id) VALUES ($pemilih_id, $pemilihan_id)");
            $conn->query("UPDATE kandidat SET jumlah_suara = jumlah_suara + 1 WHERE id = $kandidat_id");
            $conn->commit();
            // Balasan jika sukses
            echo json_encode(['status' => 'success', 'message' => 'Suara Anda telah direkam secara Real-time. Terima kasih!']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Sistem gagal memproses suara Anda.']);
        }
    }
    exit;
}
?>
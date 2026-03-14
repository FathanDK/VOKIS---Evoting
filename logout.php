<?php
session_start();

if (isset($_GET['from'])) {
    session_unset();
    session_destroy();
    if ($_GET['from'] === 'admin') {
        header("Location: admin_login.php");
    } else {
        // from=vote atau lainnya → login pemilih
        header("Location: login.php?pesan=logout_sukses");
    }
    exit;
}

// Fallback tanpa parameter — cek session
$is_admin = isset($_SESSION['admin_id']);
session_unset();
session_destroy();
header("Location: " . ($is_admin ? "admin_login.php" : "login.php?pesan=logout_sukses"));
exit;
?>
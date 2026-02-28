<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'koneksi.php';

// Get a valid token
$q = mysqli_query($conn, "SELECT token FROM tte_user WHERE status='aktif' LIMIT 1");
$d = mysqli_fetch_assoc($q);

if ($d) {
    echo "Found token: " . $d['token'] . "<br>";
    echo "<img src='generate_qr.php?token=" . $d['token'] . "'>";
} else {
    echo "No active TTE token found.";
}
?>

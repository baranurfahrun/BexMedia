<?php
include "security.php";
$user_id = $_SESSION['user_id'] ?? 0;
echo "User ID: $user_id\n";
$res = mysqli_query($conn, "SELECT username, nama_lengkap, photo FROM users WHERE id = $user_id");
$row = mysqli_fetch_assoc($res);
echo "Username: " . $row['username'] . "\n";
echo "Nama Lengkap: " . $row['nama_lengkap'] . "\n";
echo "Photo Path: " . $row['photo'] . "\n";
if ($row['photo']) {
    $p = "../" . $row['photo'];
    echo "Checking path: $p\n";
    if (file_exists($p)) {
        echo "File exists!\n";
    } else {
        echo "File DOES NOT exist.\n";
    }
}
?>

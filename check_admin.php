<?php
require_once "conf/config.php";
$res = mysqli_query($conn, "SELECT id, username, nama_lengkap, photo FROM users WHERE username = AES_ENCRYPT('admin', 'bex')");
if ($row = mysqli_fetch_assoc($res)) {
    echo "ID: " . $row['id'] . "\n";
    echo "Username: admin\n";
    echo "Nama Lengkap: " . $row['nama_lengkap'] . "\n";
    echo "Photo: " . $row['photo'] . "\n";
} else {
    echo "User admin not found.\n";
}
?>

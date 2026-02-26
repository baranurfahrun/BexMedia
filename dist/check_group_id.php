<?php
include 'koneksi.php';
$res = mysqli_query($conn, "SELECT setting_value FROM web_settings WHERE setting_key = 'wa_group_it'");
$row = mysqli_fetch_assoc($res);
echo "WA Group IT: " . ($row['setting_value'] ?? 'NOT SET');
?>

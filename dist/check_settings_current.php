<?php
include 'koneksi.php';
header('Content-Type: text/plain');
$keys = ['wa_db_host', 'wa_db_user', 'wa_db_pass', 'wa_db_name', 'wa_number', 'wa_group_it'];
foreach ($keys as $key) {
    $res = mysqli_query($conn, "SELECT setting_value FROM web_settings WHERE setting_key = '$key'");
    $row = mysqli_fetch_assoc($res);
    echo "$key: [" . ($row['setting_value'] ?? 'NULL') . "]\n";
}
?>

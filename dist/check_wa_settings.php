<?php
include 'koneksi.php';
header('Content-Type: text/plain');
$res = mysqli_query($conn, "SELECT * FROM web_settings WHERE setting_key LIKE 'wa_db_%'");
while($row = mysqli_fetch_assoc($res)) {
    echo "{$row['setting_key']}: |{$row['setting_value']}|\n";
}
?>

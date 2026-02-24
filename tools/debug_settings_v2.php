<?php
include 'conf/config.php';
$res = mysqli_query($conn, "SELECT * FROM web_settings");
echo "WEB_SETTINGS Content:\n";
while($row = mysqli_fetch_assoc($res)) {
    echo $row['setting_key'] . " => " . $row['setting_value'] . "\n";
}
echo "\nTarget keys check:\n";
$targets = ['app_name', 'host_khanza', 'name_khanza', 'user_khanza', 'pass_khanza', 'host_bex', 'name_bex', 'user_bex', 'pass_bex'];
foreach($targets as $t) {
    $res = mysqli_query($conn, "SELECT 1 FROM web_settings WHERE setting_key = '$t'");
    echo "$t: " . (mysqli_num_rows($res) > 0 ? "EXISTS" : "MISSING") . "\n";
}

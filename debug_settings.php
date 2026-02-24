<?php
require_once "conf/config.php";
echo "--- WEB_SETTINGS CONTENT ---\n";
$res = mysqli_query($conn, "SELECT * FROM web_settings");
while($row = mysqli_fetch_assoc($res)) {
    echo "[" . $row['setting_key'] . "] => " . $row['setting_value'] . "\n";
}
echo "---------------------------\n";
?>

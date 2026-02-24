<?php
require_once "conf/config.php";
echo "--- DB STRUCT CHECK ---\n";
$res = mysqli_query($conn, "DESCRIBE web_settings");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
echo "--- CURRENT VALUES ---\n";
$res = mysqli_query($conn, "SELECT *, LENGTH(setting_key) as klen, LENGTH(setting_value) as vlen FROM web_settings");
while($row = mysqli_fetch_assoc($res)) {
    echo "Key: [".$row['setting_key']."] (len:".$row['klen'].") | Val: [".$row['setting_value']."] (len:".$row['vlen'].")\n";
}
echo "--- TESTING UPDATE ---\n";
$test_key = "test_update_".time();
$res = mysqli_query($conn, "INSERT INTO web_settings (setting_key, setting_value) VALUES ('$test_key', 'works') ON DUPLICATE KEY UPDATE setting_value = 'works'");
if ($res) {
    echo "Insert/Update query success.\n";
    $check = mysqli_query($conn, "SELECT setting_value FROM web_settings WHERE setting_key = '$test_key'");
    $row = mysqli_fetch_assoc($check);
    echo "Verify: " . ($row['setting_value'] == 'works' ? "OK" : "FAILED") . "\n";
    mysqli_query($conn, "DELETE FROM web_settings WHERE setting_key = '$test_key'");
} else {
    echo "Update failed: " . mysqli_error($conn) . "\n";
}
?>

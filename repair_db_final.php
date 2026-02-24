<?php
require_once "conf/config.php";

echo "--- DEEP CLEAN & REPAIR web_settings ---\n";

// 1. Get all keys
$res = mysqli_query($conn, "SELECT DISTINCT setting_key FROM web_settings");
while ($row = mysqli_fetch_assoc($res)) {
    $key = $row['setting_key'];
    echo "Processing key: $key\n";
    
    // Get the most recent value (latest updated_at or highest ID if exists, but we use updated_at)
    $latest_res = mysqli_query($conn, "SELECT setting_value, updated_at FROM web_settings WHERE setting_key = '$key' ORDER BY updated_at DESC LIMIT 1");
    $latest = mysqli_fetch_assoc($latest_res);
    $val = mysqli_real_escape_string($conn, $latest['setting_value']);
    
    echo "  Latest value found: $val (Updated: ".$latest['updated_at'].")\n";
    
    // Delete ALL rows for this key
    mysqli_query($conn, "DELETE FROM web_settings WHERE setting_key = '$key'");
    
    // Re-insert just the latest one
    mysqli_query($conn, "INSERT INTO web_settings (setting_key, setting_value, updated_at) VALUES ('$key', '$val', '".$latest['updated_at']."')");
}

echo "--- SETTING PRIMARY KEY ---\n";
// Drop existing primary key if any (just in case)
@mysqli_query($conn, "ALTER TABLE web_settings DROP PRIMARY KEY");
$res = mysqli_query($conn, "ALTER TABLE web_settings ADD PRIMARY KEY (setting_key)");

if ($res) {
    echo "SUCCESS: PRIMARY KEY applied and duplicates removed.\n";
} else {
    echo "FAILED to add primary key: " . mysqli_error($conn) . "\n";
}

// Final check
$check = mysqli_query($conn, "DESCRIBE web_settings");
while($row = mysqli_fetch_assoc($check)) {
    if ($row['Field'] == 'setting_key') {
        echo "Final State of setting_key: Key=[" . $row['Key'] . "]\n";
    }
}
?>

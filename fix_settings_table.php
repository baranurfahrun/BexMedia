<?php
require_once "conf/config.php";

echo "--- CHECKING FOR DUPLICATES ---\n";
$res = mysqli_query($conn, "SELECT setting_key, COUNT(*) as cnt FROM web_settings GROUP BY setting_key HAVING cnt > 1");
$duplicates = [];
while ($row = mysqli_fetch_assoc($res)) {
    $duplicates[] = $row['setting_key'];
    echo "Found duplicate: " . $row['setting_key'] . " (" . $row['cnt'] . " rows)\n";
}

if (empty($duplicates)) {
    echo "No duplicates found. Simply adding PRIMARY KEY...\n";
} else {
    echo "Cleaning up duplicates (keeping newest)...\n";
    foreach ($duplicates as $key) {
        // Keep ONLY the latest updated row
        $latest_res = mysqli_query($conn, "SELECT updated_at FROM web_settings WHERE setting_key = '$key' ORDER BY updated_at DESC LIMIT 1");
        $latest = mysqli_fetch_assoc($latest_res)['updated_at'];
        
        mysqli_query($conn, "DELETE FROM web_settings WHERE setting_key = '$key' AND updated_at < '$latest'");
        // If still multiple (same timestamp), keep only one by ID or limit
        mysqli_query($conn, "DELETE FROM web_settings WHERE setting_key = '$key' LIMIT " . (mysqli_num_rows(mysqli_query($conn, "SELECT * FROM web_settings WHERE setting_key = '$key'")) - 1));
    }
}

echo "Applying PRIMARY KEY...\n";
$alter = mysqli_query($conn, "ALTER TABLE web_settings ADD PRIMARY KEY (setting_key)");
if ($alter) {
    echo "SUCCESS: PRIMARY KEY applied.\n";
} else {
    echo "FAILED: " . mysqli_error($conn) . "\n";
}
?>

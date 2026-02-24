<?php
require_once "conf/config.php";
echo "Current host_khanza in DB: [" . get_setting('host_khanza') . "]\n";
$res = mysqli_query($conn, "SELECT last_activity FROM users LIMIT 1"); // Check if DB is alive
if ($res) echo "DB Connection OK\n";
else echo "DB Connection FAIL: " . mysqli_error($conn) . "\n";
?>

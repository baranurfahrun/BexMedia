<?php
require_once "conf/config.php";
$res = mysqli_query($conn, "DESCRIBE users");
echo "TABLE: users\n";
while ($row = mysqli_fetch_assoc($res)) {
    echo "Field: " . $row['Field'] . " | Type: " . $row['Type'] . "\n";
}

$res = mysqli_query($conn, "SHOW TABLES");
echo "\nALL TABLES:\n";
while ($row = mysqli_fetch_array($res)) {
    echo $row[0] . "\n";
}
?>

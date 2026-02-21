<?php
require_once "conf/config.php";
echo "Checking users table...\n";
$res = mysqli_query($conn, "DESCRIBE users");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\nChecking user counts...\n";
$res = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
$row = mysqli_fetch_assoc($res);
echo "Total users: " . $row['total'] . "\n";

echo "\nChecking query result...\n";
$sql = "SELECT id, nama_lengkap, AES_DECRYPT(username, 'bex') as uname, jabatan, last_activity, status FROM users ORDER BY last_activity DESC";
$res = mysqli_query($conn, $sql);
if (!$res) {
    echo "Query Error: " . mysqli_error($conn) . "\n";
} else {
    echo "Found " . mysqli_num_rows($res) . " users.\n";
    while($row = mysqli_fetch_assoc($res)) {
        print_r($row);
    }
}
?>

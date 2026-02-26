<?php
include 'koneksi.php';
echo "<h3>Web Settings Table</h3>";
$res = mysqli_query($conn, "SELECT * FROM web_settings WHERE setting_key LIKE '%wa%' OR setting_key LIKE '%tele%'");
echo "<table border='1'><tr><th>Key</th><th>Value</th></tr>";
while($row = mysqli_fetch_assoc($res)) {
    echo "<tr><td>{$row['setting_key']}</td><td>" . htmlspecialchars($row['setting_value']) . "</td></tr>";
}
echo "</table>";

echo "<h3>WA Setting Table</h3>";
$res2 = mysqli_query($conn, "SHOW TABLES LIKE 'wa_setting'");
if(mysqli_num_rows($res2) > 0) {
    $res3 = mysqli_query($conn, "SELECT * FROM wa_setting");
    echo "<table border='1'><tr><th>ID</th><th>Nama</th><th>Nilai</th></tr>";
    while($row = mysqli_fetch_assoc($res3)) {
        echo "<tr><td>{$row['id']}</td><td>{$row['nama']}</td><td>" . htmlspecialchars($row['nilai']) . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "Table wa_setting not found.";
}
?>

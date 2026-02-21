<?php
require_once "conf/config.php";
$sql = file_get_contents("sql/access_control.sql");
// Split commands by semicolon
$commands = explode(';', $sql);
foreach ($commands as $cmd) {
    if (trim($cmd)) {
        if (mysqli_query($conn, $cmd)) {
            echo "Executed: " . substr(trim($cmd), 0, 50) . "...\n";
        } else {
            echo "Error: " . mysqli_error($conn) . "\n";
        }
    }
}
?>

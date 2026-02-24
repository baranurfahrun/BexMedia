<?php
include 'conf/config.php';
if ($conn_sik) {
    echo "COLUMNS FOR TABLE petugas:\n";
    $res = mysqli_query($conn_sik, "DESCRIBE petugas");
    while($row = mysqli_fetch_assoc($res)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}

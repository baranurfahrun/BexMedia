<?php
include 'conf/config.php';
if ($conn_sik) {
    echo "COLUMNS FOR TABLE pegawai:\n";
    $res = mysqli_query($conn_sik, "DESCRIBE pegawai");
    while($row = mysqli_fetch_assoc($res)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    echo "\nCOLUMNS FOR TABLE departemen:\n";
    $res = mysqli_query($conn_sik, "DESCRIBE departemen");
    if ($res) {
        while($row = mysqli_fetch_assoc($res)) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "Table departemen not found.\n";
    }
} else {
    echo "Connection to Khanza (conn_sik) failed.\n";
}

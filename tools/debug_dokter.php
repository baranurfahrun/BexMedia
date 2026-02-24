<?php
include 'conf/config.php';
if ($conn_sik) {
    echo "COLUMNS FOR TABLE dokter:\n";
    $res = mysqli_query($conn_sik, "DESCRIBE dokter");
    if ($res) {
        while($row = mysqli_fetch_assoc($res)) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "Table dokter not found.\n";
    }
}

<?php
include 'conf/config.php';
if ($conn_sik) {
    echo "TABLES RELATING TO STAFF/USER:\n";
    $res = mysqli_query($conn_sik, "SHOW TABLES");
    while($row = mysqli_fetch_row($res)) {
        if (strpos($row[0], 'pegawai') !== false || strpos($row[0], 'user') !== false || strpos($row[0], 'petugas') !== false) {
            echo "- " . $row[0] . "\n";
        }
    }
}

<?php
include 'conf/config.php';
$res = mysqli_query($conn, "SELECT DISTINCT unit_kerja FROM users");
while($row = mysqli_fetch_assoc($res)) {
    echo "[" . $row['unit_kerja'] . "]\n";
}
echo "\nUNIT_KERJA MASTER TABLE:\n";
$res2 = mysqli_query($conn, "SELECT nama_unit FROM unit_kerja");
while($row = mysqli_fetch_assoc($res2)) {
    echo "{" . $row['nama_unit'] . "}\n";
}

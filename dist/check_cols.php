<?php
include 'koneksi.php';
$res = mysqli_query($conn, "DESCRIBE data_barang_it");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . "\n";
}
?>

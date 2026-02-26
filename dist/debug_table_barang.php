<?php
include 'koneksi.php';
$res = mysqli_query($conn, "SHOW TABLES LIKE 'data_barang_it'");
if(mysqli_num_rows($res) == 0) {
    echo "TABLE MISSING";
} else {
    $res = mysqli_query($conn, "SELECT COUNT(*) as total FROM data_barang_it");
    $row = mysqli_fetch_assoc($res);
    echo "TOTAL: " . $row['total'];
}
?>

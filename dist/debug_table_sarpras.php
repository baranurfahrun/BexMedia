<?php
include 'koneksi.php';
$res = mysqli_query($conn, "SELECT COUNT(*) as total FROM data_barang_ac");
$row = mysqli_fetch_assoc($res);
echo "SARPRAS TOTAL: " . $row['total'];
?>

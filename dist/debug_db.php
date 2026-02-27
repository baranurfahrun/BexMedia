<?php
include 'koneksi.php';
$res = mysqli_query($conn, "DESCRIBE tiket_it_software");
echo "<h3>tiket_it_software</h3><pre>";
while($row = mysqli_fetch_assoc($res)) print_r($row);
echo "</pre>";

$res = mysqli_query($conn, "DESCRIBE tiket_it_hardware");
echo "<h3>tiket_it_hardware</h3><pre>";
while($row = mysqli_fetch_assoc($res)) print_r($row);
echo "</pre>";

$res = mysqli_query($conn, "DESCRIBE tiket_sarpras");
echo "<h3>tiket_sarpras</h3><pre>";
while($row = mysqli_fetch_assoc($res)) print_r($row);
echo "</pre>";
?>

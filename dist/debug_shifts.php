<?php
include 'koneksi.php';
$res = mysqli_query($conn, "SELECT * FROM jam_kerja");
while($row = mysqli_fetch_assoc($res)) {
    echo "ID: " . $row['id'] . " | CODE: " . $row['kode'] . " | NAME: " . $row['nama_jam'] . " | TIME: " . $row['jam_mulai'] . "-" . $row['jam_selesai'] . "\n";
}
?>

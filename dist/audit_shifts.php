<?php
require_once __DIR__ . "/../conf/config.php";
$res = mysqli_query($conn, "SELECT id, kode, nama_jam FROM jam_kerja");
while($row = mysqli_fetch_assoc($res)) {
    echo "ID: " . $row['id'] . " | KODE: '" . $row['kode'] . "' | NAMA: '" . $row['nama_jam'] . "'\n";
}
?>

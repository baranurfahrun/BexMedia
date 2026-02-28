<?php
include 'koneksi.php';
$res = mysqli_query($conn, "SHOW TABLES LIKE 'pengajuan_ganti_jadwal'");
if (mysqli_num_rows($res) == 0) {
    echo "Tabel tidak ada!";
} else {
    $res = mysqli_query($conn, "DESC pengajuan_ganti_jadwal");
    while($row = mysqli_fetch_assoc($res)) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}
?>

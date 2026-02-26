<?php
include_once 'koneksi_wa.php';
header('Content-Type: text/plain');
if ($conn_wa) {
    $res = mysqli_query($conn_wa, "SHOW CREATE TABLE wa_outbox");
    if ($res) {
        $row = mysqli_fetch_row($res);
        echo $row[1];
    } else {
        echo "Gagal mengambil info tabel: " . mysqli_error($conn_wa);
    }
}
?>

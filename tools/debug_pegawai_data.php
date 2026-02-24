<?php
include 'conf/config.php';
if ($conn_sik) {
    echo "SAMPLE DATA FROM pegawai (First 5):\n";
    $res = mysqli_query($conn_sik, "SELECT nik, nama, jbtn, bidang, departemen, tmp_lahir, tgl_lahir, jk, alamat, kota FROM pegawai LIMIT 5");
    while($row = mysqli_fetch_assoc($res)) {
        print_r($row);
    }
}

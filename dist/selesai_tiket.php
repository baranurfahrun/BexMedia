<?php
session_start();
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['tiket_id']);
    $tp = $_POST['tipe'] ?? ''; 
    $tbl = ($tp == 'software' ? 'tiket_it_software' : ($tp == 'hardware' ? 'tiket_it_hardware' : 'tiket_sarpras'));
    $rd = ($tp == 'software' ? 'order_tiket_it_software.php#tiket-saya' : ($tp == 'hardware' ? 'order_tiket_it_hardware.php#tiket-saya' : 'order_tiket_sarpras.php#tiket-saya'));

    $update = mysqli_query($conn, "UPDATE $tbl SET status = 'Selesai', waktu_selesai = NOW() WHERE id = $id");

    if (isset($_POST['ajax'])) {
        echo $update ? "success" : "error";
        exit;
    }

    if ($update) {
        echo "<html><head><script src='assets/modules/jquery.min.js'></script><script src='assets/modules/sweetalert/sweetalert.min.js'></script></head><body><script>$(document).ready(function(){swal('Berhasil!','Tiket Selesai','success').then(function(){window.location.href='$rd';});});</script></body></html>";
    } else {
        echo "<script>alert('Gagal update database.'); window.history.back();</script>";
    }
    exit;
}
header("Location: dashboard.php");
?>

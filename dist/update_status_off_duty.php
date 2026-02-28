<?php
session_start();
include 'security.php'; 
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $status_validasi = $_POST['status_validasi'];
    $catatan_it = mysqli_real_escape_string($conn, $_POST['catatan_it']);

    $validator_id = $_SESSION['user_id'];
    $tanggal_validasi = date("Y-m-d H:i:s");

    $query = "UPDATE laporan_off_duty 
              SET status_validasi = '$status_validasi',
                  catatan_it = '$catatan_it',
                  tanggal_validasi = '$tanggal_validasi',
                  validator_id = '$validator_id'
              WHERE id = $id";

    if (mysqli_query($conn, $query)) {
        $_SESSION['flash_message'] = "success:Status Off-Duty berhasil diperbarui.";
        header("Location: data_off_duty.php");
        exit;
    } else {
        $error = addslashes(mysqli_error($conn));
        $_SESSION['flash_message'] = "error:Gagal memperbarui status: $error";
        header("Location: data_off_duty.php");
        exit;
    }
} else {
    header("Location: data_off_duty.php");
    exit;
}
?>
